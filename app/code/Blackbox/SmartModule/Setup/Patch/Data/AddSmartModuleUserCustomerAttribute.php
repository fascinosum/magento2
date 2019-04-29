<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Blackbox\SmartModule\Setup\Patch\Data;

use Magento\Customer\Model\Attribute\Backend\Data\Boolean;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\FlagManager;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class AddSmartModuleUserCustomerAttribute implements
    DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param FlagManager $flagManager
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        FlagManager $flagManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->flagManager = $flagManager;
    }

    /**
     * Do Upgrade
     */
    public function apply()
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
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
            'blackbox_flag_v_2_0_4',
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
            PrepareInitialConfig::class
        ];
    }
}
