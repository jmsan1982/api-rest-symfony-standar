<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241029125106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO videos (user_id, title, description, url, status, created_at, updated_at) VALUES 
            (1, 'como se llame', 'description la que sea', 'https://youtu.be/Y29DRKMvfYQ?si=XKgAnVXFVoszNeJI', 'importatnt', NOW(), NOW())");

        $this->addSql("INSERT INTO videos (user_id, title, description, url, status, created_at, updated_at) VALUES 
            (1, 'como se llame pero 2', 'description la que sea pero 2', 'https://youtu.be/Y29DRKMvfYQ?si=XKgAnVXFVoszNeJI', '', NOW(), NOW())");
    }

    public function down(Schema $schema): void
    {

    }
}
