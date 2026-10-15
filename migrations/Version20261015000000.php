<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE availability ADD end_time TIME(0) WITHOUT TIME ZONE NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE availability DROP COLUMN end_time');
    }
}
