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

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class ConvertReviewMessageToJson implements
    DataPatchInterface
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
     * @param FieldDataConverterFactory $fieldDataConverterFactory
     * @param FlagManager $flagManager
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        FieldDataConverterFactory $fieldDataConverterFactory,
        FlagManager $flagManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->fieldDataConverterFactory = $fieldDataConverterFactory;
        $this->flagManager = $flagManager;
    }

    /**
     * Do Upgrade
     *
     * @throws \Magento\Framework\DB\FieldDataConversionException
     */
    public function apply()
    {
        $fieldDataConverter = $this->fieldDataConverterFactory->create(SerializedToJson::class);
        $fieldDataConverter->convert(
            $this->moduleDataSetup->getConnection(),
            $this->moduleDataSetup->getTable('email_review_content_table'),
            'review_id',
            'message'
        );
        $this->flagManager->saveFlag(
            'blackbox_flag_v_2_1_3',
            __CLASS__ . __FUNCTION__
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
}
