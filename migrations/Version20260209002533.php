<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209002533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Sustituye los valores por los tuyos
        $this->addSql("INSERT INTO \"user\" (email, roles, password, phone, name) VALUES (
            'rxup2025@gmail.com',
            '[\"ROLE_ADMIN\"]',
            '$2y$13$fiJ5K.wu6NS9cNz/zYNhOu7fP5sYDoLPCuppmlPKOEicIjUHGq766',
            '619337494',
            'Ale Torrejón'
        )");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
