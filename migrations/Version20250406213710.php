<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250406213710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ebay_inventory_items ADD locale VARCHAR(255) NOT NULL, ADD color VARCHAR(255) DEFAULT NULL, ADD material VARCHAR(255) DEFAULT NULL, ADD brand VARCHAR(255) DEFAULT NULL, ADD image_urls JSON DEFAULT NULL COMMENT '(DC2Type:json)', ADD `condition` VARCHAR(255) DEFAULT NULL, ADD currency VARCHAR(255) DEFAULT NULL, ADD availability_quantity INT DEFAULT NULL, CHANGE marketplace_id size VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ebay_inventory_items ADD marketplace_id VARCHAR(255) DEFAULT NULL, DROP locale, DROP size, DROP color, DROP material, DROP brand, DROP image_urls, DROP `condition`, DROP currency, DROP availability_quantity
        SQL);
    }
}
