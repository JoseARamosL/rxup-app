<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209152146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game ADD team1_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD team2_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CE72BCFA4 FOREIGN KEY (team1_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CF59E604A FOREIGN KEY (team2_id) REFERENCES game (id)');
        $this->addSql('CREATE INDEX IDX_232B318CE72BCFA4 ON game (team1_id)');
        $this->addSql('CREATE INDEX IDX_232B318CF59E604A ON game (team2_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CE72BCFA4');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CF59E604A');
        $this->addSql('DROP INDEX IDX_232B318CE72BCFA4');
        $this->addSql('DROP INDEX IDX_232B318CF59E604A');
        $this->addSql('ALTER TABLE game DROP team1_id');
        $this->addSql('ALTER TABLE game DROP team2_id');
    }
}
