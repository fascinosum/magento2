<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Blackbox\SmartModule\Setup;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @inheritDoc
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var \Magento\Framework\Math\Random
     */
    private $randomMath;

    /**
     * @var \Magento\Framework\FlagManager
     */
    private $flagManager;

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    private $config;

    /**
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config
     * @param \Magento\Framework\Math\Random $randomMath
     * @param \Magento\Framework\FlagManager $flagManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config,
        \Magento\Framework\Math\Random $randomMath,
        \Magento\Framework\FlagManager $flagManager
    ) {
        $this->randomMath = $randomMath;
        $this->flagManager = $flagManager;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     * @throws LocalizedException
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $this->prepareInitialConfig();

        $setup->endSetup();
    }

    /**
     * @throws LocalizedException
     */
    private function prepareInitialConfig()
    {
        $this->config->saveConfig(
            'smartmodule/generator/random_key',
            $this->randomMath->getRandomString(10),
            'default',
            '0'
        );
        $this->flagManager->saveFlag(
            'blackbox_flag_v_2_0_0',
            __CLASS__ . ':' . __FUNCTION__
        );
    }
}
