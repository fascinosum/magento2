<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Blackbox\SmartModule\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

/**
 * Class EnableAbandonedCartSegmentation.
 */
class EnableAbandonedCartSegmentation implements SchemaPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     * @throws \Zend_Db_Exception
     */
    public function apply()
    {
        $setup = $this->moduleDataSetup;
        $setup->startSetup();

        $connection = $setup->getConnection();
        $indexerTableName = $setup->getTable('abandoned_cart_table_index');
        $storeSelect = $connection->select()
            ->from($setup->getTable('store'))
            ->where('store_id > 0');

        foreach ($connection->fetchAll($storeSelect) as $storeData) {
            $indexTable =  implode(
                '_',
                [$indexerTableName, \Magento\Store\Model\Store::ENTITY, $storeData['store_id']]
            );
            if (!$connection->isTableExists($indexTable)) {
                $connection->createTable(
                    $connection->createTableByDdl(
                        $indexerTableName,
                        $indexTable
                    )
                );
            }
            $tmpTableName = implode('_', [$indexTable, 'tmp']);
            if (!$connection->isTableExists($tmpTableName)) {
                $connection->createTable(
                    $connection->createTableByDdl(
                        $indexerTableName,
                        $tmpTableName
                    )
                );
            }
        }

        $setup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * This version associate patch with Magento setup version.
     * For example, if Magento current setup version is 2.0.3 and patch version is 2.0.2 than
     * this patch will be added to registry, but will not be applied, because it is already applied
     * by old mechanism of UpgradeData.php script
     *
     *
     * @return string
     * @deprecated 102.0.0 since appearance, required for backward compatibility
     */
    public static function getVersion()
    {
        return '2.1.5';
    }
}
