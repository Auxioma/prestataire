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

use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Rules\File\DirectoryNameRule;
use TwigCsFixer\Rules\File\FileExtensionRule;
use TwigCsFixer\Rules\File\FileNameRule;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$templateDirectory = __DIR__.'/templates';

/*
 * --------------------------------------------------------------------------
 * Finder
 * --------------------------------------------------------------------------
 *
 * Limite l'analyse aux templates Twig du projet.
 */
$finder = Finder::create()
    ->in($templateDirectory)
    ->ignoreVCSIgnored(true);

/*
 * --------------------------------------------------------------------------
 * Coding standards
 * --------------------------------------------------------------------------
 *
 * Standard Twig CS Fixer + règles complémentaires adaptées
 * à une application Symfony professionnelle.
 */
$ruleset = new Ruleset();

$ruleset->addStandard(new TwigCsFixer());

/*
 * Convention Symfony :
 *
 * templates/
 * ├── account/
 * │   └── user_profile.html.twig
 * ├── property/
 * │   ├── index.html.twig
 * │   └── _card.html.twig
 * └── security/
 *     └── login.html.twig
 */
$ruleset->addRule(
    new DirectoryNameRule(
        baseDirectory: $templateDirectory,
    ),
);

$ruleset->addRule(
    new FileNameRule(
        baseDirectory: $templateDirectory,
        optionalPrefix: '_',
    ),
);

$ruleset->addRule(
    new FileExtensionRule(),
);

/*
 * --------------------------------------------------------------------------
 * Configuration
 * --------------------------------------------------------------------------
 */
$config = new Config();

$config->setFinder($finder);
$config->setRuleset($ruleset);

/*
 * Active également les règles qui ne peuvent pas être corrigées
 * automatiquement, afin qu'elles soient détectées pendant le lint.
 */
$config->allowNonFixableRules();

/*
 * Cache local pour accélérer les analyses suivantes.
 */
$config->setCacheFile(
    __DIR__.'/.twig-cs-fixer.cache',
);

return $config;
