<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Blackbox\SmartModule\Setup;

use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Store\Model\Store;

/**
 * @inheritDoc
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    const EMAIL_REVIEW_CONTENT_TABLE = 'email_review_content_table';

    const ABANDONED_CART_INDEX_TABLE = 'abandoned_cart_table_index';

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $this->addTimestampFieldsToAbandonedCartTable($installer);
            $this->removeForeignKeyFromCustomerIdField($installer);
        }
        if (version_compare($context->getVersion(), '2.0.2', '<')) {
            $this->addReplicaTable(
                $installer,
                InstallSchema::EMAIL_REVIEW_TABLE,
                implode('_', [InstallSchema::EMAIL_REVIEW_TABLE, 'replica'])
            );
        }
        if (version_compare($context->getVersion(), '2.0.3', '<')) {
            $this->createReviewContentTable($installer);
        }
        if (version_compare($context->getVersion(), '2.0.5', '<')) {
            $this->recreateFeedbackIndexTmpTable($installer);
        }
        if (version_compare($context->getVersion(), '2.0.6', '<')) {
            $this->createAbandonedCartIndexTable(
                $installer->getTable(self::ABANDONED_CART_INDEX_TABLE),
                $installer
            );
            $this->createAbandonedCartIndexTable(
                $installer->getTable(self::ABANDONED_CART_INDEX_TABLE) . '_replica',
                $installer
            );
        }

        /** Can not be converted to a declaration */
        if (version_compare($context->getVersion(), '2.1.5', '<')) {
            $this->enableAbandonedCartSegmentation($installer);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    private function addTimestampFieldsToAbandonedCartTable(SchemaSetupInterface $installer)
    {
        $table = $installer->getTable(InstallSchema::ABANDONED_CART_TABLE);
        $connection = $installer->getConnection();
        
        $connection
            ->addColumn(
                $table,
                'created_at',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'comment' => 'Created At'
                ]
            );
        $connection
            ->addColumn(
                $table,
                'updated_at',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'comment' => 'Updated at'
                ]
            );
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    private function removeForeignKeyFromCustomerIdField(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $installer->getConnection()->dropForeignKey(
            $connection->getTableName(InstallSchema::EMAIL_REVIEW_TABLE),
            $installer->getFkName(
                $connection->getTableName(InstallSchema::EMAIL_REVIEW_TABLE),
                'customer_id',
                $connection->getTableName(InstallSchema::EMAIL_CONTACT_TABLE),
                'customer_id'
            )
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     * @param string $existingTable
     * @param string $replicaTable
     */
    private function addReplicaTable(SchemaSetupInterface $installer, string $existingTable, string $replicaTable)
    {
        $connection = $installer->getConnection();
        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s LIKE %s',
            $connection->quoteIdentifier($installer->getTable($replicaTable)),
            $connection->quoteIdentifier($installer->getTable($existingTable))
        );
        $connection->query($sql);
    }

    /**
     * Create review table.
     *
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    private function createReviewContentTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable(self::EMAIL_REVIEW_CONTENT_TABLE);
        $this->dropTableIfExists($installer, $tableName);

        $table = $installer->getConnection()->newTable($tableName);
        $this->addColumnsToReviewContentTable($table);
        $this->addIndexesToReviewContentTable($installer, $table);
        $this->addForeignKeysToReviewContentTable($installer, $table);
        $table->setComment('Reviews Table');
        $installer->getConnection()->createTable($table);
    }

    /**
     * Add columns to review content table.
     *
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addColumnsToReviewContentTable(Table $table)
    {
        $table
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'primary' => true,
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Primary Key'
            )->addColumn(
                'review_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Review Id'
            )->addColumn(
                'message',
                Table::TYPE_TEXT,
                1024,
                ['nullable' => false],
                'Message'
            );
    }

    /**
     * Add indexes to review content table.
     *
     * @param SchemaSetupInterface $installer
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addIndexesToReviewContentTable(SchemaSetupInterface $installer, Table $table)
    {
        $tableName = $table->getName();
        $table
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable($tableName),
                    ['review_id']
                ),
                ['review_id']
            );
    }

    /**
     * Add foreign keys to review content table.
     *
     * @param SchemaSetupInterface $installer
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addForeignKeysToReviewContentTable(SchemaSetupInterface $installer, Table $table)
    {
        $connection = $installer->getConnection();
        $table->addForeignKey(
            $installer->getFkName(
                $table->getName(),
                'review_id',
                $connection->getTableName(InstallSchema::EMAIL_REVIEW_TABLE),
                'review_id'
            ),
            'review_id',
            $installer->getTable(InstallSchema::EMAIL_REVIEW_TABLE),
            'review_id',
            Table::ACTION_CASCADE
        );
    }

    /**
     * Drop existing tables.
     *
     * @param SchemaSetupInterface $installer
     * @param string $tableName
     */
    private function dropTableIfExists(SchemaSetupInterface $installer, string $tableName)
    {
        $connection = $installer->getConnection();
        if ($connection->isTableExists($installer->getTable($tableName))) {
            $connection->dropTable(
                $installer->getTable($tableName)
            );
        }
    }

    /**
     * Drop and recreate feedback index tmp table
     *
     * Before this update the table was created without usage engine=MEMORY.
     *
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    private function recreateFeedbackIndexTmpTable(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $tableName = $connection->getTableName(InstallSchema::EMAIL_FEEDBACK_INDEX_TMP_TABLE);

        $installer->getConnection()->dropTable($tableName);

        $table = $connection
            ->newTable($tableName)
            ->addColumn(
                'feedback_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'primary' => true,
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Primary Key'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false],
                'Customer ID'
            )->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'nullable' => false],
                'Store Id'
            )->addColumn(
                'message_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false],
                'Message ID'
            )->setOption(
                'type',
                Mysql::ENGINE_MEMORY
            )
            ->setComment('Feedback Table');

        $connection->createTable($table);
    }

    /**
     * Create abandoned cart index table.
     *
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    private function createAbandonedCartIndexTable(string $tableName, SchemaSetupInterface $installer)
    {
        $this->dropTableIfExists($installer, $tableName);

        $table = $installer->getConnection()->newTable($tableName);
        $this->addColumnsToAbandonedCartIndexTable($table);
        $this->addIndexesToAbandonedCartIndexTable($installer, $table);
        $table->setComment('Abandoned Cart Index Table');
        $installer->getConnection()->createTable($table);
    }

    /**
     * Add columns to abandoned cart index table.
     *
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addColumnsToAbandonedCartIndexTable(Table $table)
    {
        $table
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'primary' => true,
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Primary Key'
            )->addColumn(
                'quote_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Quote Id'
            )->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                10,
                ['unsigned' => true, 'nullable' => true],
                'Store Id'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
                'Customer ID'
            )->addColumn(
                'is_active',
                Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                'Quote Active'
            );
    }

    /**
     * Add indexes to abandoned cart index table.
     *
     * @param SchemaSetupInterface $installer
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addIndexesToAbandonedCartIndexTable(SchemaSetupInterface $installer, Table $table)
    {
        $tableName = $table->getName();
        $table
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable($tableName),
                    ['quote_id', 'store_id', 'customer_id']
                ),
                ['quote_id', 'store_id', 'customer_id']
            );
    }

    /**
     * Separate abandoned cart indexer tables by store.
     *
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function enableAbandonedCartSegmentation(SchemaSetupInterface $setup): void
    {
        $connection = $setup->getConnection();
        $indexerTableName = $setup->getTable(self::ABANDONED_CART_INDEX_TABLE);
        $storeSelect = $connection->select()
            ->from($setup->getTable('store'))
            ->where('store_id > 0');

        foreach ($connection->fetchAll($storeSelect) as $storeData) {
            $indexTable =  implode('_', [$indexerTableName, Store::ENTITY, $storeData['store_id']]);
            if (!$connection->isTableExists($indexTable)) {
                $connection->createTable(
                    $connection->createTableByDdl(
                        $indexerTableName,
                        $indexTable
                    )
                );
            }
            if (!$connection->isTableExists($indexTable . '_replica')) {
                $connection->createTable(
                    $connection->createTableByDdl(
                        $indexerTableName,
                        $indexTable . '_replica'
                    )
                );
            }
        }
    }
}
