<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250522205531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE ebay_listings (id INT AUTO_INCREMENT NOT NULL, inventory_item_id INT NOT NULL, sku VARCHAR(255) NOT NULL, offer_id VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, ebay_response JSON DEFAULT NULL COMMENT '(DC2Type:json)', start_time DATETIME DEFAULT NULL, end_time DATETIME DEFAULT NULL, marketplace_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL, INDEX IDX_1175B2B2536BF4A2 (inventory_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ebay_listings ADD CONSTRAINT FK_1175B2B2536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES ebay_inventory_items (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ebay_inventory_items CHANGE currency currency VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ebay_listings DROP FOREIGN KEY FK_1175B2B2536BF4A2
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE ebay_listings
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ebay_inventory_items CHANGE currency currency VARCHAR(255) DEFAULT NULL
        SQL);
    }
}
