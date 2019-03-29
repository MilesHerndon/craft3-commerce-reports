<?php
/**
 * Commerce Reports plugin for Craft CMS 3.x
 *
 * Plugin to run specific Commerce reports
 *
 * @link      https://milesherndon.com
 * @copyright Copyright (c) 2019 MilesHerndon
 */

namespace milesherndon\commercereports\migrations;

use milesherndon\commercereports\CommerceReports;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * @author    MilesHerndon
 * @package   CommerceReports
 * @since     1.1.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

   /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%commercereports_quantityversions}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%commercereports_quantityversions}}',
                [
                    'id' => $this->primaryKey(),
                    'productId' => $this->integer(),
                    'variantId' => $this->integer(),
                    'sku' => $this->string(),
                    'title' => $this->string(),
                    'price' => $this->float(2)->notNull()->defaultValue(0),
                    'wholesale' => $this->float(2)->notNull()->defaultValue(0),
                    'qtyAdded' => $this->integer()->notNull()->defaultValue(0),
                    'qtyDeleted' => $this->integer()->notNull()->defaultValue(0),
                    'stockRemaining' => $this->integer()->notNull()->defaultValue(0),
                    'productDeleted' => $this->boolean()->notNull()->defaultValue(0),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        // $this->createIndex(
        //     $this->db->getIndexName(
        //         '{{%commercereports_quantityversions}}',
        //         'name',
        //         true
        //     ),
        //     '{{%commercereports_quantityversions}}',
        //     'name',
        //     true
        // );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%commercereports_quantityversions}}', 'productId'
            ),
            '{{%commercereports_quantityversions}}', 'productId',
            '{{%commerce_products}}', 'id'
        );
    }
}
