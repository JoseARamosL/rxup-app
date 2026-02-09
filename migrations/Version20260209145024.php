<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209145024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Hash de la contraseña 'nopassword'
        $password = '$2y$13$X7A/KqJ3D1.mP8E9xU2Yue.M8GvH6K2.W8P3G8E9xU2Yue.M8GvH6';

        $jugadores = [
            ['email' => '', 'name' => 'MAYETO', 'phone' => '677707575'],
            ['email' => '', 'name' => 'RAFI', 'phone' => '611024691'],
            ['email' => '', 'name' => 'ADRI GONZALEZ', 'phone' => '601286020'],
            ['email' => '', 'name' => 'JOSE M', 'phone' => ''],
            ['email' => '', 'name' => 'VICTOR M', 'phone' => ''],
            ['email' => '', 'name' => 'ANTONIO', 'phone' => ''],
            ['email' => '', 'name' => 'DARIO', 'phone' => ''],
            ['email' => '', 'name' => 'FELIPE', 'phone' => ''],
            ['email' => '', 'name' => 'JULITO', 'phone' => ''],
            ['email' => '', 'name' => 'FRANCIS', 'phone' => ''],
            ['email' => '', 'name' => 'ALE MORENO', 'phone' => ''],
            ['email' => '', 'name' => 'ABRAHAM', 'phone' => ''],
            ['email' => '', 'name' => 'DANI DOBLADO', 'phone' => ''],
            ['email' => '', 'name' => 'MANOLI', 'phone' => ''],
            ['email' => '', 'name' => 'ALVARO VALEN', 'phone' => ''],
            ['email' => '', 'name' => 'JOSE MARI', 'phone' => ''],
            ['email' => '', 'name' => 'ZEQUI', 'phone' => ''],
            ['email' => '', 'name' => 'VICTOR', 'phone' => ''],
            ['email' => '', 'name' => 'GONZALO', 'phone' => ''],
            ['email' => '', 'name' => 'ESCOLA', 'phone' => ''],
            ['email' => 'j.ramoslado2001@gmail.com', 'name' => 'JOSE RAMOS', 'phone' => '601295358'],
            ['email' => '', 'name' => 'ANTONIO SIBON', 'phone' => ''],
            ['email' => '', 'name' => 'CRISTIAN MARTINEZ', 'phone' => ''],
            ['email' => '', 'name' => 'NICO', 'phone' => ''],
            ['email' => '', 'name' => 'RODRI', 'phone' => ''],
            ['email' => '', 'name' => 'ANDREA', 'phone' => ''],
            ['email' => '', 'name' => 'CELIA', 'phone' => ''],
            ['email' => '', 'name' => 'NINO', 'phone' => ''],
            ['email' => '', 'name' => 'CARRILLO', 'phone' => ''],
            ['email' => '', 'name' => 'RAFA', 'phone' => ''],
            ['email' => '', 'name' => 'SERGIO', 'phone' => ''],
            ['email' => '', 'name' => 'ANTOÑITO', 'phone' => ''],
            ['email' => '', 'name' => 'JAIRO', 'phone' => ''],
            ['email' => '', 'name' => 'MARCOS', 'phone' => ''],
            ['email' => '', 'name' => 'JUANMA', 'phone' => ''],
            ['email' => '', 'name' => 'JUANJO', 'phone' => ''],
            ['email' => '', 'name' => 'MENDY', 'phone' => ''],
            ['email' => '', 'name' => 'ALBERTO', 'phone' => ''],
        ];

        foreach ($jugadores as $j) {
            // Si el email está vacío, le inventamos uno temporal basado en el nombre
            $email = $j['email'] ?: strtolower(str_replace(' ', '.', $j['name'])) . '@rxup.com';

            foreach ($jugadores as $j) {
                // 1. Generar Email único si está vacío
                $email = $j['email'] ?: strtolower(str_replace(' ', '.', $j['name'])) . '@rxup.com';

                // 2. Generar Teléfono único si está vacío para evitar el error 'uniq_identifier_phone'
                // Usamos un prefijo + un número aleatorio o el índice para que no se repita el vacío
                $phone = $j['phone'] ?: '000' . str_pad((string)array_search($j, $jugadores), 6, '0', STR_PAD_LEFT);

                $this->addSql(sprintf(
                    'INSERT INTO "user" (email, roles, password, name, phone)
                    VALUES (\'%s\', \'%s\', \'%s\', \'%s\', \'%s\')
                    ON CONFLICT (email) DO NOTHING',
                    $email,
                    '["ROLE_USER"]',
                    $password,
                    $j['name'],
                    $phone
                ));
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
