<?php

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\TextType;
use Mautic\CoreBundle\Exception\SchemaException;

class IndexSchemaHelper
{
    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractMySQLPlatform>
     */
    protected \Doctrine\DBAL\Schema\AbstractSchemaManager $sm;

    /**
     * @var \Doctrine\DBAL\Schema\Schema
     */
    protected $schema;

    /**
     * @var Table
     */
    protected $table;

    /**
     * @var array
     */
    protected $allowedColumns = [];

    /**
     * @var array
     */
    protected $changedIndexes = [];

    /**
     * @var array
     */
    protected $addedIndexes = [];

    /**
     * @var array
     */
    protected $dropIndexes = [];

    /**
     * @param string $prefix
     */
    public function __construct(protected Connection $db, protected $prefix)
    {
        $this->sm     = $this->db->getSchemaManager();
    }

    /**
     * @return $this
     *
     * @throws SchemaException
     */
    public function setName($name)
    {
        if (!$this->sm->tablesExist($this->prefix.$name)) {
            throw new SchemaException("Table $name does not exist!");
        }

        $this->table = $this->sm->listTableDetails($this->prefix.$name);

        return $this;
    }

    public function allowColumn($name): void
    {
        $this->allowedColumns[] = $name;
    }

    /**
     * @param array $options
     *
     * @return $this
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function addIndex($columns, $name, $options = [])
    {
        $columns = $this->getTextColumns($columns);

        if (!empty($columns)) {
            $index = new Index($this->prefix.$name, $columns, false, false, $options);

            if ($this->table->hasIndex($this->prefix.$name)) {
                $this->changedIndexes[] = $index;
            } else {
                $this->addedIndexes[] = $index;
            }
        }

        return $this;
    }

    /**
     * @param mixed  $columns
     * @param string $name
     * @param array  $options
     *
     * @return self
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function dropIndex($columns, $name, $options = [])
    {
        $textColumns = $this->getTextColumns($columns);

        $index = new Index($name, $textColumns, false, false, $options);
        if ($this->table->hasIndex($name)) {
            $this->dropIndexes[] = $index;
        }

        return $this;
    }

    /**
     * Execute changes.
     */
    public function executeChanges(): void
    {
        $platform = $this->sm->getDatabasePlatform();

        $sql = [];
        if (count($this->changedIndexes)) {
            foreach ($this->changedIndexes as $index) {
                $sql[] = $platform->getDropIndexSQL($index, $this->table);
                $sql[] = $platform->getCreateIndexSQL($index, $this->table);
            }
        }

        if (count($this->dropIndexes)) {
            foreach ($this->dropIndexes as $index) {
                $sql[] = $platform->getDropIndexSQL($index, $this->table);
            }
        }

        if (count($this->addedIndexes)) {
            foreach ($this->addedIndexes as $index) {
                $sql[] = $platform->getCreateIndexSQL($index, $this->table);
            }
        }

        if (count($sql)) {
            foreach ($sql as $query) {
                $this->db->executeStatement($query);
            }
            $this->changedIndexes = [];
            $this->dropIndexes    = [];
            $this->addedIndexes   = [];
        }
    }

    /**
     * @return array
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function getTextColumns($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        foreach ($columns as $column) {
            if (!in_array($column, $this->allowedColumns)) {
                $columnSchema = $this->table->getColumn($column);

                $type = $columnSchema->getType();
                if (!$type instanceof TextType) {
                    $this->allowedColumns[] = $columnSchema->getName();
                }
            }
        }

        // Indexes are only allowed on columns that are string
        $columns = array_intersect($columns, $this->allowedColumns);

        return $columns;
    }
}
