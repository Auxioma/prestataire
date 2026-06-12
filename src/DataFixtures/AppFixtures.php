<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Fichier laissé vide volontairement.
        // Les vraies fixtures sont désormais réparties dans des classes dédiées.
    }
}