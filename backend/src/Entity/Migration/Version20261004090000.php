<?php

declare(strict_types=1);

namespace App\Entity\Migration;

use Doctrine\DBAL\Schema\Schema;

final class Version20261004090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add UPC metadata and optional Spotify album-art credentials.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                ALTER TABLE station_media
                    ADD upc VARCHAR(20) DEFAULT NULL
            SQL
        );

        $this->addSql(
            <<<'SQL'
                ALTER TABLE settings
                    ADD spotify_client_id VARCHAR(255) DEFAULT NULL,
                    ADD spotify_client_secret VARCHAR(255) DEFAULT NULL
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE station_media DROP upc');
        $this->addSql(
            <<<'SQL'
                ALTER TABLE settings
                    DROP spotify_client_id,
                    DROP spotify_client_secret
            SQL
        );
    }
}
