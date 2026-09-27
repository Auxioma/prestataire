<?php

namespace App\EventSubscriber;

use App\DataFixtures\FixtureLoadContext;
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
