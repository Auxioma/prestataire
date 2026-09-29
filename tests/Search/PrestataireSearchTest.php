<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Tests\Search;

use App\Account\Entity\User;
use App\Account\Enum\UserStatusEnum;
use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Enum\PrestataireProfileStatusEnum;
use App\Prestataire\Enum\VerificationStatusEnum;
use App\Search\Enum\SearchVisibilityEnum;
use App\Search\Indexing\PrestataireIndexDefinition;
use App\Search\Service\ElasticsearchClient;
use App\Search\Service\PrestataireSearchEligibility;
use App\Search\Service\PrestataireSearchService;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class PrestataireSearchTest extends TestCase
{
    public function testAllSearchPathsUseTheSameAliasAndStatusFilter(): void
    {
        $history = [];
        $mock = new MockHandler(array_fill(0, 3, new Response(200, ['X-Elastic-Product' => 'Elasticsearch', 'Content-Type' => 'application/json'], '{"hits":{"total":{"value":0},"hits":[]}}')));
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));
        $wrapper = new ElasticsearchClient('https://localhost:9200', '', '', true);
        $client = ClientBuilder::create()->setHosts(['https://localhost:9200'])->setHttpClient(new Client(['handler' => $stack]))->build();
        (new \ReflectionProperty($wrapper, 'client'))->setValue($wrapper, $client);
        $search = new PrestataireSearchService($wrapper);
        $search->search('plombier');
        $search->autocomplete('plombier');
        $search->browseSearch('plombier');
        foreach ($history as $item) {
            self::assertSame('/'.PrestataireIndexDefinition::ALIAS.'/_search', $item['request']->getUri()->getPath());
            $body = json_decode((string) $item['request']->getBody(), true, 512, \JSON_THROW_ON_ERROR);
            self::assertContains(PrestataireSearchEligibility::filter(), $body['query']['bool']['filter']);
        }
        self::assertSame('keyword', PrestataireIndexDefinition::body()['mappings']['properties']['profileStatus']['type']);
    }

    public function testHiddenSuspendedAndUnverifiedProfilesAreExcluded(): void
    {
        $profile = (new PrestataireProfile())->setCompanyName('Entreprise')->setSlug('entreprise')
            ->setProfileStatus(PrestataireProfileStatusEnum::ACTIVE)->setVerificationStatus(VerificationStatusEnum::COMPANY_VERIFIED);
        $account = (new User())->setStatus(UserStatusEnum::ACTIVE);
        $profile->setAccount($account);
        self::assertTrue(PrestataireSearchEligibility::isEligible($profile));
        $profile->setSearchVisibility(SearchVisibilityEnum::HIDDEN);
        self::assertFalse(PrestataireSearchEligibility::isEligible($profile));
        $profile->setSearchVisibility(SearchVisibilityEnum::NORMAL)->setVerificationStatus(VerificationStatusEnum::MANUALLY_VERIFIED);
        self::assertTrue(PrestataireSearchEligibility::isEligible($profile));
        $account->setStatus(UserStatusEnum::SUSPENDED);
        self::assertFalse(PrestataireSearchEligibility::isEligible($profile));
        $account->setStatus(UserStatusEnum::ACTIVE);
        $profile->setVerificationStatus(VerificationStatusEnum::NOT_VERIFIED);
        self::assertFalse(PrestataireSearchEligibility::isEligible($profile));
    }
}
