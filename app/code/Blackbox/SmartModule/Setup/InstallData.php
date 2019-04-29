<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Blackbox\SmartModule\Setup;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagManager;
use Magento\Framework\Math\Random;
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
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     * @param Random $random
     * @param FlagManager $flagManager
     */
    public function __construct(
        ConfigInterface $config,
        Random $random,
        FlagManager $flagManager
    ) {
        $this->randomMath = $random;
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
     * Generate and save code.
     *
     * @throws LocalizedException
     */
    private function generateAndSaveCode()
    {
        $this->config->saveConfig(
            'smartmodule/generator/random_key',
            $this->randomMath->getRandomString(10),
            'default',
            '0'
        );
    }

    /**
     * @throws LocalizedException
     */
    private function prepareInitialConfig(): void
    {
        $this->generateAndSaveCode();
        $this->flagManager->saveFlag(
            'blackbox_flag_v_2_0_0',
            __FUNCTION__
        );
    }
}
