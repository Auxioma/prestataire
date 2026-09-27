<?php

declare(strict_types=1);

/*
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

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$fileHeaderComment = <<<'COMMENT'
Copyright (c) 2026 AUXIOMA Web Agency.

Projet : TrouveMoi

Tous droits réservés.

Ce fichier fait partie du projet TrouveMoi,
développé par AUXIOMA Web Agency.

Toute reproduction, modification, distribution ou utilisation,
totale ou partielle, sans autorisation écrite préalable,
est strictement interdite.
COMMENT;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude([
        'var',
        'public/bundles',
        'public/build',
    ])
    ->notPath([
        'public/index.php',
        'importmap.php',
    ]);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        /*
         * Standards Symfony.
         */
        '@Symfony' => true,
        '@Symfony:risky' => true,

        /*
         * Modernisation PHP 8.4.
         */
        '@PHP8x4Migration' => true,

        /*
         * Typage strict.
         *
         * Important :
         * @Symfony:risky désactive normalement strict_types.
         * On force donc son activation ici.
         */
        'declare_strict_types' => [
            'strategy' => 'add_when_missing',
        ],

        /*
         * Copyright / propriété intellectuelle.
         */
        'header_comment' => [
            'header' => $fileHeaderComment,
            'separate' => 'both',
            'comment_type' => 'PHPDoc',
            'location' => 'after_declare_strict',
        ],

        /*
         * Structure PHP.
         */
        'linebreak_after_opening_tag' => true,
        'single_blank_line_at_eof' => true,
        'no_closing_tag' => true,

        /*
         * Imports.
         */
        'ordered_imports' => [
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'no_unused_imports' => true,
        'single_import_per_statement' => true,

        /*
         * PHPDoc.
         */
        'phpdoc_order' => [
            'order' => [
                'param',
                'return',
                'throws',
            ],
        ],
        'phpdoc_align' => [
            'align' => 'vertical',
        ],
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_useless_inheritdoc' => true,

        /*
         * Nettoyage du code.
         */
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_unreachable_default_argument_value' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,

        /*
         * PHP moderne.
         */
        'modernize_types_casting' => true,
        'ternary_to_null_coalescing' => true,

        /*
         * Comparaisons strictes.
         */
        'strict_comparison' => true,
        'strict_param' => true,

        /*
         * Chaînes multibytes.
         */
        'mb_str_functions' => true,

        /*
         * PHPUnit.
         */
        'php_unit_strict' => true,
        'php_unit_construct' => true,

        /*
         * Symfony préfère normalement séparer les groupes d'import.
         * Pour TrouveMoi, on conserve volontairement un seul bloc.
         */
        'blank_line_between_import_groups' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache');