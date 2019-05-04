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
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class AddSmartModuleUserCustomerAttribute implements DataPatchInterface, PatchVersionInterface
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
            'blackbox_flag_v_2_0_10',
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
            PrepareInitialConfig::class
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
        return '2.0.10';
    }
}
