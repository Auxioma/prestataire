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

namespace App\Core\EventSubscriber;

use App\Core\DataFixtures\FixtureLoadContext;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FixtureLoadContextSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly FixtureLoadContext $context)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => 'onConsoleCommand'];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if ('doctrine:fixtures:load' === $event->getCommand()?->getName()) {
            $this->context->setDryRun((bool) $event->getInput()->getOption('dry-run'));
        }
    }
}
