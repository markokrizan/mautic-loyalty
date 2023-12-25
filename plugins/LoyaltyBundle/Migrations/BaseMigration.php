<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Migrations;

use Doctrine\DBAL\Schema\Exception\TableDoesNotExist;
use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

abstract class BaseMigration extends AbstractMigration
{
    private $pluginTablePrefix = 'loyalty_';

    protected function getNameWithPrefix(String $tableName): String
    {
        return $this->concatPrefix($this->pluginTablePrefix) . $tableName;
    }

    protected function tableExists(Schema $schema, String $tableName): bool
    {
        $fullName = $this->getNameWithPrefix($tableName);

        try {
            return (bool)$schema->getTable($fullName);
        } catch (TableDoesNotExist $e) {
            return false;
        }
    }
}
