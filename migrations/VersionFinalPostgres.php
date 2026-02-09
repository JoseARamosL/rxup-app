<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionFinalPostgres extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Consolidación de tablas Padel App para PostgreSQL';
    }

    public function up(Schema $schema): void
    {
        // 1. Crear tabla USER (con comillas dobles obligatorias en Postgres)
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_PHONE ON "user" (phone)');

        // 2. Crear tabla TOURNAMENT (TINYINT cambia a BOOLEAN)
        $this->addSql('CREATE TABLE tournament (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, description TEXT DEFAULT NULL, active BOOLEAN NOT NULL DEFAULT TRUE, has_ranking BOOLEAN NOT NULL DEFAULT TRUE, PRIMARY KEY (id))');

        // 3. Crear tabla GAME (DATETIME cambia a TIMESTAMP)
        $this->addSql('CREATE TABLE game (id SERIAL NOT NULL, score VARCHAR(50) DEFAULT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, court VARCHAR(255) DEFAULT NULL, phase VARCHAR(50) DEFAULT NULL, tournament_id INT NOT NULL, player1_id INT DEFAULT NULL, player2_id INT DEFAULT NULL, player3_id INT DEFAULT NULL, player4_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_232B318C33D1A3E7 ON game (tournament_id)');
        $this->addSql('CREATE INDEX IDX_232B318CC0990423 ON game (player1_id)');
        $this->addSql('CREATE INDEX IDX_232B318CD22CABCD ON game (player2_id)');
        $this->addSql('CREATE INDEX IDX_232B318C6A90CCA8 ON game (player3_id)');
        $this->addSql('CREATE INDEX IDX_232B318CF747F411 ON game (player4_id)');

        // 4. Foreign Keys
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CC0990423 FOREIGN KEY (player1_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CD22CABCD FOREIGN KEY (player2_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C6A90CCA8 FOREIGN KEY (player3_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CF747F411 FOREIGN KEY (player4_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // 5. Messenger Messages (Postgres Version)
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');

        // 6. INSERT ADMIN (Ojo al escapar el $ en el password para PHP)
        $this->addSql("INSERT INTO \"user\" (email, roles, password, phone, name) VALUES (
            'rxup2025@gmail.com',
            '[\"ROLE_ADMIN\"]',
            '$2y$13\$fiJ5K.wu6NS9cNz/zYNhOu7fP5sYDoLPCuppmlPKOEicIjUHGq766',
            '619337494',
            'Ale Torrejón'
        )");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
