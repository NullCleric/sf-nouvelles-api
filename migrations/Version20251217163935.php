<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217163935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_389b7832b36786b');
        $this->addSql('ALTER INDEX uniq_389b783989d9b62 RENAME TO uniq_tag_slug');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX uniq_389b7832b36786b ON tag (title)');
        $this->addSql('ALTER INDEX uniq_tag_slug RENAME TO uniq_389b783989d9b62');
    }
}
