<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace App\Tests\Integration;

use App\Entity\PrestataireInterventionZone;
use App\Entity\PrestataireProfile;
use App\Entity\PrestataireService;
use App\Entity\Service;
use App\Entity\ServiceCategory;
use App\Entity\User;
use App\Enum\PrestataireProfileStatusEnum;
use App\Enum\SearchVisibilityEnum;
use App\Enum\UserStatusEnum;
use App\Enum\VerificationStatusEnum;
use App\EventSubscriber\PrestataireSearchSubscriber;
use App\Repository\PrestataireSearchReadRepository;
use App\Repository\UserRepository;
use App\Search\PrestataireDocumentMapper;
use App\Service\CompanyRegistryClient;
use App\Service\CompanyVerificationManager;
use App\Service\ElasticsearchClient;
use App\Service\PrestataireProfileManager;
use App\Service\PrestataireSearchIndexer;
use App\Service\PrestataireSearchQueue;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\String\Slugger\AsciiSlugger;

// Uses an isolated, disposable PostgreSQL schema and an in-memory HTTP Elasticsearch emulator.
final class PrestataireSearchSynchronizationTest extends TestCase
{
    private Connection $connection;
    private EntityManager $manager;
    private PrestataireSearchQueue $queue;
    private ElasticsearchClient $elasticsearch;
    private string $schema;
    private array $documents = [];
    private bool $available = true;

    protected function setUp(): void
    {
        if ('1' !== getenv('ELASTICSEARCH_INTEGRATION')) {
            self::markTestSkipped('Run with ELASTICSEARCH_INTEGRATION=1 to use a disposable local PostgreSQL schema.');
        }
        $localFile = \dirname(__DIR__, 2).'/.env.local';
        $local = is_file($localFile) ? (new Dotenv())->parse(file_get_contents($localFile), $localFile) : [];
        $url = getenv('SEARCH_TEST_DATABASE_URL') ?: ($local['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? '');
        $params = (new DsnParser(['postgresql' => 'pdo_pgsql', 'postgres' => 'pdo_pgsql']))->parse($url);
        self::assertContains($params['host'] ?? '', ['127.0.0.1', 'localhost'], 'Integration tests require local PostgreSQL.');
        $this->connection = DriverManager::getConnection($params);
        $this->schema = 'search_sync_test_'.bin2hex(random_bytes(8));
        $this->connection->executeStatement('CREATE SCHEMA '.$this->schema);
        $this->connection->executeStatement('SET search_path TO '.$this->schema);
        $config = ORMSetup::createAttributeMetadataConfig([\dirname(__DIR__, 2).'/src/Entity'], true);
        $config->enableNativeLazyObjects(true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(\CASE_LOWER));
        $config->setRepositoryFactory(new class implements RepositoryFactory {
            public function getRepository(EntityManagerInterface $entityManager, string $entityName): EntityRepository
            {
                return new EntityRepository($entityManager, $entityManager->getClassMetadata($entityName));
            }
        });
        $config->setIdentityGenerationPreferences([PostgreSQLPlatform::class => ClassMetadata::GENERATOR_TYPE_IDENTITY]);
        $this->manager = new EntityManager($this->connection, $config);
        (new SchemaTool($this->manager))->createSchema($this->manager->getMetadataFactory()->getAllMetadata());
        $this->elasticsearch = new ElasticsearchClient('https://localhost:9200', '', '', true);
        $this->resetHttpClient();
        $logger = new NullLogger();
        $this->queue = new PrestataireSearchQueue($this->connection, new PrestataireSearchReadRepository($this->manager), new PrestataireSearchIndexer($this->elasticsearch, new PrestataireDocumentMapper()), $logger);
        $listener = new PrestataireSearchSubscriber($this->queue, $logger);
        $this->manager->getEventManager()->addEventListener(['onFlush', 'postPersist', 'postUpdate', 'postRemove', 'postFlush'], $listener);
    }

    protected function tearDown(): void
    {
        if (isset($this->connection, $this->schema)) {
            while ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            $this->connection->executeStatement('SET search_path TO public');
            $this->connection->executeStatement('DROP SCHEMA '.$this->schema.' CASCADE');
            $this->connection->close();
        }
    }

    public function testVerificationChangesServicesZonesAndSuspensionAreImmediatelySynchronized(): void
    {
        $profile = $this->createProfile();
        $id = $profile->getId();
        self::assertSame('Entreprise test', $this->documents[$id]['companyName'], json_encode($this->documents));
        self::assertSame(0, $this->jobCount());

        $category = (new ServiceCategory())->setName('Travaux')->setSlug('travaux')->setPosition(0)->setIsActive(true);
        $service = (new Service())->setName('Plomberie')->setSlug('plomberie')->setCategory($category)->setPosition(0)->setIsActive(true);
        $offer = (new PrestataireService())->setPrestataire($profile)->setService($service)->setTitle('Dépannage');
        $zone = (new PrestataireInterventionZone())->setPrestataireProfile($profile)->setCity('Lille');
        foreach ([$category, $service, $offer, $zone] as $entity) {
            $this->manager->persist($entity);
        }
        $this->manager->flush();
        self::assertSame('Dépannage', $this->documents[$id]['services'][0]['title']);
        self::assertSame('Lille', $this->documents[$id]['zones'][0]['city']);
        $service->setName('Chauffage');
        $this->manager->flush();
        self::assertSame('Chauffage', $this->documents[$id]['services'][0]['service']['name']);
        $this->manager->remove($offer);
        $this->manager->remove($zone);
        $this->manager->flush();
        self::assertSame([], $this->documents[$id]['services']);
        self::assertSame([], $this->documents[$id]['zones']);
        $profile->setProfileStatus(PrestataireProfileStatusEnum::SUSPENDED);
        $this->manager->flush();
        self::assertArrayNotHasKey($id, $this->documents);
        $profile->setProfileStatus(PrestataireProfileStatusEnum::ACTIVE)->setVerificationStatus(VerificationStatusEnum::MANUALLY_VERIFIED);
        $this->manager->flush();
        self::assertArrayHasKey($id, $this->documents, json_encode($this->connection->fetchAllAssociative('SELECT * FROM prestataire_search_job')));
        $profile->setSearchVisibility(SearchVisibilityEnum::HIDDEN);
        $this->manager->flush();
        self::assertArrayNotHasKey($id, $this->documents);
    }

    public function testAcceptedSiretPreviewPublishesProfileAndChangedPreviewIsRejected(): void
    {
        $profile = $this->createProfile();
        $profile->setVerificationStatus(VerificationStatusEnum::NOT_VERIFIED);
        $this->manager->flush();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $stack = new RequestStack();
        $stack->push($request);
        $manager = new CompanyVerificationManager(new PrestataireProfileManager($this->manager, $this->createStub(UserRepository::class), new AsciiSlugger()), $stack);
        $registry = new CompanyRegistryClient(new MockHttpClient(static fn () => new MockResponse(json_encode(['results' => [[
            'siren' => '123456789', 'nom_complet' => 'Entreprise officielle',
            'siege' => ['siret' => '12345678900011', 'etat_administratif' => 'A'],
        ]]], \JSON_THROW_ON_ERROR))), 'https://registry.example');
        $manager->buildPreview($profile, $registry);
        self::assertSame(['isVerified' => true, 'isActive' => true], $manager->applyAcceptedPreview($profile));
        $this->manager->flush();
        self::assertSame('Entreprise officielle', $this->documents[$profile->getId()]['companyName']);
        self::assertSame('COMPANY_VERIFIED', $this->documents[$profile->getId()]['verificationStatus']);
        $manager->buildPreview($profile, $registry);
        $profile->setSiret('12345678900029');
        $this->expectException(\RuntimeException::class);
        $manager->applyAcceptedPreview($profile);
    }

    public function testOutageKeepsJobAndRecoveryIndexesTheLatestProfile(): void
    {
        $profile = $this->createProfile();
        $this->available = false;
        $profile->setCompanyName('Entreprise actualisée');
        $this->manager->flush();
        self::assertSame(1, $this->jobCount());
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT attempts FROM prestataire_search_job'));
        self::assertSame('Entreprise test', $this->documents[$profile->getId()]['companyName']);
        $this->available = true;
        $this->resetHttpClient();
        $this->connection->executeStatement("UPDATE prestataire_search_job SET available_at = date_trunc('second', CURRENT_TIMESTAMP)");
        self::assertSame(['processed' => 1, 'failed' => 0], $this->queue->process());
        self::assertSame('Entreprise actualisée', $this->documents[$profile->getId()]['companyName']);
        self::assertSame(0, $this->jobCount());
    }

    public function testRollbackDoesNotPublishAnUncommittedChangeOrLeaveAJob(): void
    {
        $profile = $this->createProfile();
        $this->connection->beginTransaction();
        $profile->setCompanyName('Modification annulée');
        $this->manager->flush();
        self::assertSame(1, $this->jobCount());
        self::assertSame('Entreprise test', $this->documents[$profile->getId()]['companyName']);
        $this->connection->rollBack();
        self::assertSame(0, $this->jobCount());
    }

    public function testChangedSiretRevokesVerificationAndPhysicalDeletionRemovesDocument(): void
    {
        $profile = $this->createProfile();
        $profile->setSiret('12345678900029');
        $this->manager->flush();
        self::assertSame(VerificationStatusEnum::NOT_VERIFIED, $profile->getVerificationStatus());
        self::assertArrayNotHasKey($profile->getId(), $this->documents);
        $profile->setVerificationStatus(VerificationStatusEnum::COMPANY_VERIFIED)->setVerifiedAt(new \DateTimeImmutable());
        $this->manager->flush();
        $id = $profile->getId();
        self::assertArrayHasKey($id, $this->documents);
        $this->manager->remove($profile);
        $this->manager->flush();
        self::assertArrayNotHasKey($id, $this->documents);
        self::assertSame(0, $this->jobCount());
    }

    private function createProfile(): PrestataireProfile
    {
        $user = (new User())->setEmail('test@example.invalid')->setPassword('test')->setStatus(UserStatusEnum::ACTIVE);
        $profile = (new PrestataireProfile())->setCompanyName('Entreprise test')->setSlug('entreprise-test')->setSiret('12345678900011')
            ->setProfileStatus(PrestataireProfileStatusEnum::ACTIVE)->setVerificationStatus(VerificationStatusEnum::COMPANY_VERIFIED)->setAccount($user);
        $this->manager->persist($user);
        $this->manager->persist($profile);
        $this->manager->flush();

        return $profile;
    }

    private function jobCount(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM prestataire_search_job');
    }

    private function resetHttpClient(): void
    {
        $handler = function (RequestInterface $request): \GuzzleHttp\Promise\PromiseInterface {
            if (!$this->available) {
                return Create::rejectionFor(new ConnectException('Simulated outage', $request));
            }
            $path = $request->getUri()->getPath();
            $response = [];
            $status = 200;
            if ('/_alias/prestataires_search' === $path) {
                $response = ['physical_test_index' => ['aliases' => ['prestataires_search' => ['is_write_index' => true]]]];
            } elseif (preg_match('#/prestataires_search/_doc/(\d+)#', $path, $match)) {
                $id = $match[1];
                if ('PUT' === $request->getMethod()) {
                    $this->documents[$id] = json_decode((string) $request->getBody(), true, 512, \JSON_THROW_ON_ERROR);
                    self::assertStringContainsString('refresh=wait_for', $request->getUri()->getQuery());
                    self::assertStringContainsString('require_alias=true', $request->getUri()->getQuery());
                    $response = ['result' => 'updated'];
                } else {
                    $status = isset($this->documents[$id]) ? 200 : 404;
                    unset($this->documents[$id]);
                    $response = ['result' => 200 === $status ? 'deleted' : 'not_found'];
                }
            }

            return Create::promiseFor(new Response($status, ['X-Elastic-Product' => 'Elasticsearch', 'Content-Type' => 'application/json'], json_encode($response, \JSON_THROW_ON_ERROR)));
        };
        $client = ClientBuilder::create()->setHosts(['https://localhost:9200'])->setHttpClient(new Client(['handler' => $handler]))->build();
        (new \ReflectionProperty($this->elasticsearch, 'client'))->setValue($this->elasticsearch, $client);
    }
}
