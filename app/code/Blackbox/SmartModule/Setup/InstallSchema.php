<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Blackbox\SmartModule\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @inheritDoc
 */
class InstallSchema implements InstallSchemaInterface
{
    const EMAIL_CONTACT_TABLE = 'email_contact_table';

    const EMAIL_REVIEW_TABLE = 'email_review_table';

    const ABANDONED_CART_TABLE = 'abandoned_cart_table';

    const EMAIL_FEEDBACK_INDEX_TMP_TABLE = 'email_feedback_index_tmp';

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        $this->createContactTable($installer);
        $this->createReviewTable($installer);
        $this->createAbandonedCartTable($installer);
        $this->createFeedbackTmpTable($installer);

        $installer->endSetup();
    }

    /**
     * Create contact table.
     *
     * @param SchemaSetupInterface $installer
     * @return null
     * @throws \Zend_Db_Exception
     */
    private function createContactTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable(self::EMAIL_CONTACT_TABLE);
        $this->dropTableIfExists($installer, $tableName);

        $table = $installer->getConnection()->newTable($tableName);
        $this->addColumnsToContactTable($table);
        $this->addIndexesToContactTable($installer, $table);

        $this->addForeignKeysToContactTable($installer, $table);

        $table->setComment('Contacts Table');
        $installer->getConnection()->createTable($table);
    }

    /**
     * Add columns to contact table.
     *
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addColumnsToContactTable(Table $table)
    {
        $table
            ->addColumn(
                'email_contact_id',
                Table::TYPE_INTEGER,
                10,
                [
                    'primary' => true,
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Primary Key'
            )->addColumn(
                'is_guest',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Is Guest'
            )->addColumn(
                'contact_id',
                Table::TYPE_TEXT,
                15,
                ['unsigned' => true, 'nullable' => true],
                'Connector Contact ID'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false],
                'Customer ID'
            )->addColumn(
                'website_id',
                Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Website ID'
            )->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )->addColumn(
                'email',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Customer Email'
            )->addColumn(
                'is_subscriber',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Is Subscriber'
            )->addColumn(
                'subscriber_status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Subscriber status'
            );
    }

    /**
     * Add indexes to contact table.
     *
     * @param SchemaSetupInterface $installer
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addIndexesToContactTable(SchemaSetupInterface $installer, Table $table)
    {
        $tableName = $table->getName();
        $table
            ->addIndex(
                $installer->getIdxName($tableName, ['is_guest']),
                ['is_guest']
            )->addIndex(
                $installer->getIdxName(
                    $tableName,
                    ['customer_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['customer_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $installer->getIdxName($tableName, ['website_id']),
                ['website_id']
            )->addIndex(
                $installer->getIdxName($tableName, ['is_subscriber']),
                ['is_subscriber']
            )->addIndex(
                $installer->getIdxName($tableName, ['subscriber_status']),
                ['subscriber_status']
            )->addIndex(
                $installer->getIdxName($tableName, ['email']),
                ['email']
            )->addIndex(
                $installer->getIdxName($tableName, ['contact_id']),
                ['contact_id']
            );
    }

    /**
     * Add foreign keys to contact table.
     *
     * @param SchemaSetupInterface $installer
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addForeignKeysToContactTable(SchemaSetupInterface $installer, Table $table): void
    {
        $connection = $installer->getConnection();
        $table->addForeignKey(
            $installer->getFkName(
                $table->getName(),
                'website_id',
                $connection->getTableName('store_website'),
                'website_id'
            ),
            'website_id',
            $installer->getTable('store_website'),
            'website_id',
            Table::ACTION_CASCADE
        );
    }

    /**
     * Create review table.
     *
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    private function createReviewTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable(self::EMAIL_REVIEW_TABLE);
        $this->dropTableIfExists($installer, $tableName);

        $table = $installer->getConnection()->newTable($tableName);
        $this->addColumnsToReviewTable($table);
        $this->addIndexesToReviewTable($installer, $table);
        $this->addForeignKeysToReviewTable($installer, $table);
        $table->setComment('Reviews Table');
        $installer->getConnection()->createTable($table);
    }

    /**
     * Add columns to review table.
     *
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addColumnsToReviewTable(Table $table)
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
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [],
                'Creation Time'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                [],
                'Update Time'
            );
    }

    /**
     * Add indexes to review table.
     *
     * @param SchemaSetupInterface $installer
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addIndexesToReviewTable(SchemaSetupInterface $installer, Table $table)
    {
        $tableName = $table->getName();
        $table
            ->addIndex(
                $installer->getIdxName(
                    $installer->getTable($tableName),
                    ['review_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['review_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $installer->getIdxName(
                    $installer->getTable($tableName),
                    ['customer_id']
                ),
                ['customer_id']
            )->addIndex(
                $installer->getIdxName(
                    $installer->getTable($tableName),
                    ['store_id']
                ),
                ['store_id']
            )->addIndex(
                $installer->getIdxName(
                    $installer->getTable($tableName),
                    ['created_at']
                ),
                ['created_at']
            )->addIndex(
                $installer->getIdxName(
                    $installer->getTable($tableName),
                    ['updated_at']
                ),
                ['updated_at']
            );
    }

    /**
     * Add foreign keys to review table.
     *
     * @param SchemaSetupInterface $installer
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addForeignKeysToReviewTable(SchemaSetupInterface $installer, Table $table): void
    {
        $connection = $installer->getConnection();
        $table->addForeignKey(
            $installer->getFkName(
                $table->getName(),
                'customer_id',
                $connection->getTableName(self::EMAIL_CONTACT_TABLE),
                'customer_id'
            ),
            'customer_id',
            $installer->getTable(self::EMAIL_CONTACT_TABLE),
            'customer_id',
            Table::ACTION_CASCADE
        );
    }

    /**
     * Create abandoned cart table
     *
     * @param SchemaSetupInterface $installer
     * @param string $tableName
     * @throws \Zend_Db_Exception
     */
    public function createAbandonedCartTable(SchemaSetupInterface $installer)
    {
        $tableName = $installer->getTable(self::ABANDONED_CART_TABLE);
        $table = $installer->getConnection()->newTable($installer->getTable($tableName));
        $this->addColumnToAbandonedCartTable($table);
        $this->addIndexesToAbandonedCartTable($installer, $table);
        $table->setComment('Abandoned Carts Table');
        $installer->getConnection()->createTable($table);
    }

    /**
     * Add columns to abandoned cart table.
     *
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addColumnToAbandonedCartTable(Table $table)
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
                'email',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Email'
            )->addColumn(
                'status',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Contact Status'
            )->addColumn(
                'is_active',
                Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'nullable' => false, 'default' => '1'],
                'Quote Active'
            )->addColumn(
                'quote_updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                [],
                'Quote updated at'
            )->addColumn(
                'abandoned_cart_number',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Abandoned Cart number'
            )->addColumn(
                'items_count',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => 0],
                'Quote items count'
            )->addColumn(
                'items_ids',
                Table::TYPE_TEXT,
                255,
                ['unsigned' => true, 'nullable' => true],
                'Quote item ids'
            );
    }

    /**
     * Add indexes to abandoned cart table.
     *
     * @param SchemaSetupInterface $installer
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addIndexesToAbandonedCartTable(SchemaSetupInterface $installer, Table $table)
    {
        $tableName = $table->getName();
        $table
            ->addIndex(
                $installer->getIdxName($tableName, ['quote_id']),
                ['quote_id']
            )->addIndex(
                $installer->getIdxName($tableName, ['store_id']),
                ['store_id']
            )->addIndex(
                $installer->getIdxName($tableName, ['customer_id']),
                ['customer_id']
            )->addIndex(
                $installer->getIdxName($tableName, ['email']),
                ['email']
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
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    private function createFeedbackTmpTable(SchemaSetupInterface $installer)
    {
        $connection = $installer->getConnection();
        $tableName = $installer->getTable(self::EMAIL_FEEDBACK_INDEX_TMP_TABLE);
        $this->dropTableIfExists($installer, $tableName);

        $table = $connection->newTable($tableName);
        $this->addColumnsToFeedbackTable($table);
        $table->setComment('Feedback Table');
        $connection->createTable($table);
    }

    /**
     * Add columns to feedback table.
     *
     * @param Table $table
     * @throws \Zend_Db_Exception
     */
    private function addColumnsToFeedbackTable(Table $table)
    {
        $table
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
                'message',
                Table::TYPE_INTEGER,
                11,
                ['unsigned' => true, 'nullable' => false],
                'Message ID'
            );
    }
}
