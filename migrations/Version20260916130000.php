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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les codes NAF autorisés et le code NAF du profil prestataire.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE allowed_naf_code (code VARCHAR(6) NOT NULL, label VARCHAR(255) NOT NULL, is_active BOOLEAN DEFAULT TRUE NOT NULL, PRIMARY KEY(code))');
        $this->addSql('ALTER TABLE prestataire_profile ADD naf_code VARCHAR(6) DEFAULT NULL');
        $this->addSql(<<<'SQL'
INSERT INTO allowed_naf_code (code, label, is_active) VALUES ('41.20A', 'Construction de maisons individuelles', TRUE),('41.20B', 'Construction d''autres bâtiments', TRUE),('43.11Z', 'Travaux de démolition', TRUE),('43.12A', 'Travaux de terrassement courants', TRUE),('43.12B', 'Travaux de terrassement spécialisés', TRUE),('43.13Z', 'Forages et sondages', TRUE),('43.21A', 'Installation électrique dans tous locaux', TRUE),('43.21B', 'Installation électrique sur la voie publique', TRUE),('43.22A', 'Installation d''eau et de gaz', TRUE),('43.22B', 'Installation d''équipements thermiques et climatisation', TRUE),('43.29A', 'Travaux d''isolation', TRUE),('43.29B', 'Autres travaux d''installation', TRUE),('43.31Z', 'Travaux de plâtrerie', TRUE),('43.32A', 'Menuiserie bois et PVC', TRUE),('43.32B', 'Menuiserie métallique et serrurerie', TRUE),('43.32C', 'Agencement de lieux de vente', TRUE),('43.33Z', 'Revêtement des sols et murs', TRUE),('43.34Z', 'Peinture et vitrerie', TRUE),('43.39Z', 'Autres travaux de finition', TRUE),('43.91A', 'Travaux de charpente', TRUE),('43.91B', 'Travaux de couverture', TRUE),('43.99A', 'Travaux d''étanchéification', TRUE),('43.99B', 'Montage de structures métalliques', TRUE),('43.99C', 'Maçonnerie générale et gros œuvre', TRUE),('43.99D', 'Autres travaux spécialisés de construction', TRUE),('43.99E', 'Location avec opérateur de matériel de construction', TRUE),('81.10Z', 'Activités combinées de soutien lié aux bâtiments', TRUE),('81.21Z', 'Nettoyage courant des bâtiments', TRUE),('81.22Z', 'Autres activités de nettoyage des bâtiments et nettoyage industriel', TRUE),('81.29A', 'Désinfection, désinsectisation, dératisation', TRUE),('81.29B', 'Autres activités de nettoyage', TRUE),('81.30Z', 'Services d''aménagement paysager', TRUE),('33.12Z', 'Réparation de machines et équipements mécaniques', TRUE),('33.13Z', 'Réparation de matériels électroniques et optiques', TRUE),('33.14Z', 'Réparation d''équipements électriques', TRUE),('33.19Z', 'Réparation d''autres équipements', TRUE),('95.11Z', 'Réparation d''ordinateurs', TRUE),('95.12Z', 'Réparation d''équipements de communication', TRUE),('95.21Z', 'Réparation de produits électroniques grand public', TRUE),('95.22Z', 'Réparation d''électroménager et équipements maison/jardin', TRUE),('95.23Z', 'Réparation de chaussures et articles en cuir', TRUE),('95.24Z', 'Réparation de meubles et équipements du foyer', TRUE),('95.25Z', 'Réparation d''articles d''horlogerie et bijouterie', TRUE),('95.29Z', 'Réparation d''autres biens personnels et domestiques', TRUE),('45.20A', 'Entretien et réparation de véhicules automobiles légers', TRUE),('45.20B', 'Entretien et réparation d''autres véhicules automobiles', TRUE),('45.40Z', 'Commerce et réparation de motocycles', TRUE),('62.01Z', 'Programmation informatique', TRUE),('62.02A', 'Conseil en systèmes et logiciels informatiques', TRUE),('62.02B', 'Tierce maintenance de systèmes et applications', TRUE),('62.03Z', 'Gestion d''installations informatiques', TRUE),('62.09Z', 'Autres activités informatiques', TRUE),('63.11Z', 'Traitement de données et hébergement', TRUE),('63.12Z', 'Portails Internet', TRUE),('74.10Z', 'Activités spécialisées de design', TRUE),('74.20Z', 'Activités photographiques', TRUE),('74.30Z', 'Traduction et interprétation', TRUE),('69.10Z', 'Activités juridiques', TRUE),('69.20Z', 'Activités comptables', TRUE),('70.21Z', 'Conseil en relations publiques et communication', TRUE),('70.22Z', 'Conseil pour les affaires et la gestion', TRUE),('71.11Z', 'Activités d''architecture', TRUE),('71.12A', 'Activité des géomètres', TRUE),('71.12B', 'Ingénierie et études techniques', TRUE),('73.11Z', 'Activités des agences de publicité', TRUE),('73.12Z', 'Régie publicitaire de médias', TRUE),('74.90A', 'Activité des économistes de la construction', TRUE),('74.90B', 'Activités spécialisées, scientifiques et techniques diverses', TRUE),('82.11Z', 'Services administratifs combinés de bureau', TRUE),('82.19Z', 'Préparation de documents et activités de bureau', TRUE),('82.20Z', 'Activités de centres d''appels', TRUE),('82.30Z', 'Organisation de foires et congrès', TRUE),('82.99Z', 'Autres activités de soutien aux entreprises', TRUE),('80.10Z', 'Activités de sécurité privée', TRUE),('80.20Z', 'Activités liées aux systèmes de sécurité', TRUE),('80.30Z', 'Activités d''enquête', TRUE),('85.51Z', 'Enseignement de disciplines sportives', TRUE),('85.52Z', 'Enseignement culturel', TRUE),('85.59A', 'Formation continue d''adultes', TRUE),('85.59B', 'Autres enseignements', TRUE),('88.10A', 'Aide à domicile', TRUE),('88.91A', 'Accueil de jeunes enfants', TRUE),('96.01A', 'Blanchisserie-teinturerie de gros', TRUE),('96.01B', 'Blanchisserie-teinturerie de détail', TRUE),('96.02A', 'Coiffure', TRUE),('96.02B', 'Soins de beauté', TRUE),('96.03Z', 'Services funéraires', TRUE),('96.04Z', 'Entretien corporel', TRUE),('96.09Z', 'Autres services personnels', TRUE),('49.41A', 'Transports routiers de fret interurbains', TRUE),('49.41B', 'Transports routiers de fret de proximité', TRUE),('49.42Z', 'Services de déménagement', TRUE),('53.20Z', 'Autres activités de poste et de courrier', TRUE),('56.21Z', 'Services des traiteurs', TRUE),('90.02Z', 'Activités de soutien au spectacle vivant', TRUE)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestataire_profile DROP naf_code');
        $this->addSql('DROP TABLE allowed_naf_code');
    }
}
