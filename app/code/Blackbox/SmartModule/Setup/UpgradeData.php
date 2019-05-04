<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Blackbox\SmartModule\Setup;

use Magento\Customer\Model\Attribute\Backend\Data\Boolean;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\DB\DataConverter\SerializedToJson;
use Magento\Framework\DB\FieldDataConversionException;
use Magento\Framework\DB\FieldDataConverterFactory;
use Magento\Framework\FlagManager;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var FieldDataConverterFactory
     */
    private $fieldDataConverterFactory;

    /**
     * @param CustomerSetupFactory $customerSetupFactory
     * @param FieldDataConverterFactory $fieldDataConverterFactory
     * @param FlagManager $flagManager
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        FieldDataConverterFactory $fieldDataConverterFactory,
        FlagManager $flagManager
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->flagManager = $flagManager;
        $this->fieldDataConverterFactory = $fieldDataConverterFactory;
    }

    /**
     * {@inheritdoc}
     * @throws FieldDataConversionException
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '2.0.10', '<')) {
            $this->addSmartModuleUserCustomerAttribute($installer);
        }

        if (version_compare($context->getVersion(), '2.1.3', '<')) {
            $this->convertReviewMessageToJson($installer);
        }

        $installer->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $installer
     */
    private function addSmartModuleUserCustomerAttribute(ModuleDataSetupInterface $installer): void
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $installer]);
        $customerSetup->addAttribute(
            Customer::ENTITY,
            'is_smartmodule_user',
            [
                'type' => 'static',
                'label' => 'Is SmartModule User',
                'input' => 'boolean',
                'backend' => Boolean::class,
                'required' => false,
                'sort_order' => 100,
                'visible' => false,
            ]
        );
        $this->flagManager->saveFlag(
            'blackbox_flag_v_2_0_10',
            __CLASS__ . ':' . __FUNCTION__
        );
    }

    /**
     * @param ModuleDataSetupInterface $installer
     * @throws FieldDataConversionException
     */
    private function convertReviewMessageToJson(ModuleDataSetupInterface $installer): void
    {
        $fieldDataConverter = $this->fieldDataConverterFactory->create(SerializedToJson::class);
        $fieldDataConverter->convert(
            $installer->getConnection(),
            $installer->getTable('email_review_content_table'),
            'review_id',
            'message'
        );
        $this->flagManager->saveFlag(
            'blackbox_flag_v_2_1_3',
            __CLASS__ . ':' . __FUNCTION__
        );
    }
}
