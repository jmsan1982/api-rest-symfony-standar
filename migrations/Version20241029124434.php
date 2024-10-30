<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241029124434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Inser data into users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO users (name, surname, email, password, role, created_at) VALUES 
            ('jose', 'Domariae', 'admin@admin.com', '1234', 'ROLE_ADMIN', NOW())");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
