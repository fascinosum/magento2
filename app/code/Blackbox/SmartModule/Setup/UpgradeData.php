<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Blackbox\SmartModule\Setup;

use Magento\Framework\DB\FieldDataConversionException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Customer\Setup\CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var \Magento\Framework\FlagManager
     */
    private $flagManager;

    /**
     * @var \Magento\Framework\DB\FieldDataConverterFactory
     */
    private $fieldDataConverterFactory;

    /**
     * @param \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
     * @param \Magento\Framework\FlagManager $flagManager
     * @param \Magento\Framework\DB\FieldDataConverterFactory $fieldDataConverterFactory
     */
    public function __construct(
        \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory,
        \Magento\Framework\FlagManager $flagManager,
        \Magento\Framework\DB\FieldDataConverterFactory $fieldDataConverterFactory
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
        $setup->startSetup();
        if (version_compare($context->getVersion(), '2.0.10', '<')) {
            $this->addSmartModuleUserCustomerAttribute($setup);
        }

        if (version_compare($context->getVersion(), '2.1.3', '<')) {
            $this->convertReviewMessageToJson($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function addSmartModuleUserCustomerAttribute(ModuleDataSetupInterface $setup)
    {
        /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        $customerSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'is_smartmodule_user',
            [
                'type' => 'static',
                'label' => 'Is SmartModule User',
                'input' => 'boolean',
                'backend' => \Magento\Customer\Model\Attribute\Backend\Data\Boolean::class,
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
     * @param ModuleDataSetupInterface $setup
     * @throws FieldDataConversionException
     */
    private function convertReviewMessageToJson(ModuleDataSetupInterface $setup)
    {
        $fieldDataConverter = $this->fieldDataConverterFactory
            ->create(\Magento\Framework\DB\DataConverter\SerializedToJson::class);
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
}
