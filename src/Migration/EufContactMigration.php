<?php

declare(strict_types=1);

/*
 * Contact Element for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2022, Erdmann & Freunde
 * @author     Erdmann & Freunde <https://erdmann-freunde.de>
 * @license    MIT
 * @link       http://github.com/nutshell-framework/contact-element
 */

namespace Nutshell\ContactElement\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class EufContactMigration extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();
        // Prüfen ob die Tabelle tl_content existiert
        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_content');

        // Prüfen ob die neuen Spalten bereits existieren
        $newColumnsExist = isset(
            $columns['contactname'],
            $columns['contactposition'],
            $columns['contactemail'],
            $columns['contactphone'],
            $columns['contactdescription']
        );

        // Migration nur ausführen, wenn die neuen Spalten noch nicht existieren
        return !$newColumnsExist;
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns('tl_content');

        // Neue Spalten hinzufügen
        $this->connection->executeQuery("
            ALTER TABLE tl_content
            ADD COLUMN contactName varchar(255) NOT NULL DEFAULT '',
            ADD COLUMN contactPosition varchar(255) NOT NULL DEFAULT '',
            ADD COLUMN contactEmail varchar(255) NOT NULL DEFAULT '',
            ADD COLUMN contactPhone varchar(255) NOT NULL DEFAULT '',
            ADD COLUMN contactDescription mediumtext NULL
        ");

        // Nur Daten migrieren, wenn die alten Spalten existieren
        $oldColumnsExist = isset(
            $columns['contact_name'],
            $columns['contact_position'],
            $columns['contact_email'],
            $columns['contact_description']
        );

        if ($oldColumnsExist) {
            $stmt = $this->connection->prepare('
                UPDATE tl_content
                SET
                    contactName = contact_name,
                    contactPosition = contact_position,
                    contactEmail = contact_email,
                    contactDescription = contact_description
                WHERE type = :type
            ');

            $stmt->bindValue('type', 'contact');
            $stmt->execute();
        }

        return $this->createResult(true, 'Contact element migration completed');
    }
}
