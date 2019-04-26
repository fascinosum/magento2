<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Blackbox\SmartModule\Setup\Patch\Schema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Store\Model\Store;

/**
 * Class EnableAbandonedCartSegmentation.
 */
class EnableAbandonedCartSegmentation implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * {@inheritdoc}
     * @throws \Zend_Db_Exception
     */
    public function apply()
    {
        $setup = $this->schemaSetup;
        $setup->startSetup();

        $connection = $setup->getConnection();
        $indexerTableName = $setup->getTable('abandoned_cart_table_index');
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

        $setup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
