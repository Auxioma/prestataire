<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

abstract class BaseFixture extends Fixture
{
    private const PLACEHOLDER_DIR = __DIR__.'/Resources/placeholders';
    private const CACHE_DIR = '/tmp/fixtures-images';

    protected Generator $faker;

    /**
     * @var array<string, list<string>>
     */
    private static array $placeholderCache = [];

    public function __construct(
        protected readonly SluggerInterface $slugger,
    ) {
        $this->faker = Factory::create('fr_FR');
        $this->faker->seed(20260709);
    }

    protected function slugify(string $value): string
    {
        return mb_strtolower($this->slugger->slug($value)->toString());
    }

    protected function randomDateTimeImmutable(
        string $start = '-2 years',
        string $end = 'now'
    ): \DateTimeImmutable {
        /** @var \DateTime $date */
        $date = $this->faker->dateTimeBetween($start, $end, 'Europe/Paris');

        return \DateTimeImmutable::createFromMutable($date);
    }

    protected function decimal(float $min, float $max): string
    {
        return number_format(
            $this->faker->randomFloat(2, $min, $max),
            2,
            '.',
            ''
        );
    }

    protected function time(string $hour): \DateTime
    {
        return new \DateTime($hour, new \DateTimeZone('Europe/Paris'));
    }

    /**
     * Utilise un placeholder local.
     */
    protected function attachPlaceholder(
        object $entity,
        string $setter,
        string $group,
        ?int $index = null,
        ?string $originalName = null
    ): void {
        $path = $this->placeholderPath($group, $index);

        $entity->{$setter}(new UploadedFile(
            $path,
            $originalName ?? basename($path),
            mime_content_type($path) ?: 'image/jpeg',
            null,
            true
        ));
    }

    /**
     * Télécharge une image distante et l'attache à l'entité.
     */
    protected function attachRemoteImage(
        object $entity,
        string $setter,
        string $url
    ): void {
        $path = $this->downloadImage($url);

        $entity->{$setter}(new UploadedFile(
            $path,
            basename($path),
            mime_content_type($path) ?: 'image/jpeg',
            null,
            true
        ));
    }

    protected function placeholderPath(string $group, ?int $index = null): string
    {
        if (!isset(self::$placeholderCache[$group])) {
            $paths = glob(sprintf('%s/%s/*', self::PLACEHOLDER_DIR, $group));

            sort($paths);

            self::$placeholderCache[$group] = $paths ?: [];
        }

        if (self::$placeholderCache[$group] === []) {
            throw new \RuntimeException(sprintf(
                'Aucun placeholder disponible pour le groupe "%s".',
                $group
            ));
        }

        if ($index === null) {
            return self::$placeholderCache[$group][array_rand(self::$placeholderCache[$group])];
        }

        return self::$placeholderCache[$group][$index % count(self::$placeholderCache[$group])];
    }

    /**
     * Télécharge une image et la met en cache.
     */
    protected function downloadImage(string $url): string
    {
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0777, true);
        }

        $extension = pathinfo(
            parse_url($url, PHP_URL_PATH),
            PATHINFO_EXTENSION
        );

        if (!$extension) {
            $extension = 'jpg';
        }

        $file = self::CACHE_DIR.'/'.md5($url).'.'.$extension;

        if (!file_exists($file)) {
            $fp = fopen($file, 'wb');

            if (!$fp) {
                throw new \RuntimeException(sprintf(
                    'Impossible de créer le fichier "%s".',
                    $file
                ));
            }

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FAILONERROR => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Symfony Fixtures',
            ]);

            curl_exec($ch);

            if (curl_errno($ch)) {
                fclose($fp);
                curl_close($ch);

                @unlink($file);

                throw new \RuntimeException(sprintf(
                    'Erreur CURL : %s',
                    curl_error($ch)
                ));
            }

            curl_close($ch);
            fclose($fp);
        }

        return $file;
    }
}