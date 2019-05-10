<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Blackbox\SmartModule\Setup\Patch\Data;

use Magento\Framework\DB\DataConverter\SerializedToJson;
use Magento\Framework\DB\FieldDataConverterFactory;
use Magento\Framework\FlagManager;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class ConvertReviewMessageToJson implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var FieldDataConverterFactory
     */
    private $fieldDataConverterFactory;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param FlagManager $flagManager
     * @param FieldDataConverterFactory $fieldDataConverterFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        FlagManager $flagManager,
        FieldDataConverterFactory $fieldDataConverterFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->flagManager = $flagManager;
        $this->fieldDataConverterFactory = $fieldDataConverterFactory;
    }

    /**
     * Do Upgrade
     *
     * @throws \Magento\Framework\DB\FieldDataConversionException
     */
    public function apply()
    {
        $setup = $this->moduleDataSetup;
        $fieldDataConverter = $this->fieldDataConverterFactory->create(SerializedToJson::class);
        $fieldDataConverter->convert(
            $setup->getConnection(),
            $setup->getTable('email_review_content_table'),
            'review_id',
            'message'
        );
        $this->flagManager->saveFlag(
            'blackbox_flag_v_2_1_3',
            __CLASS__ . ':' . __FUNCTION__
        );
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
        return [
            AddSmartModuleUserCustomerAttribute::class
        ];
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
        return '2.1.3';
    }
}
