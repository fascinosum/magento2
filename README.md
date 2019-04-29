<h2>Declaration Schema Workshop</h2>

1. Install Magento 2.3.1 using branch [i2019](https://github.com/fascinosum/magento2/tree/i2019)

2. Switch to the branch [i2019-module](https://github.com/fascinosum/magento2/tree/i2019-module)

    * execute
        ```bash
        bin/magento setup:upgrade --convert-old-scripts 1
        ```
    * check the result in the _app/code/Blackbox/SmartModule/etc/db_schema.xml_ file
    * remove `setup_version` attribute from the `module` node in _app/code/Blackbox/SmartModule/etc/module.xml_
    * execute 
        ```bash
        bin/magento setup:upgrade --dry-run 1
        ```
    * check the result in _dry-run-installation.log_ file. There should not be any changes for the module tables

3.  Switch to the branch [i2019-dynamic-schema-changes](https://github.com/fascinosum/magento2/tree/i2019-dynamic-schema-changes)
    * use 
        ```bash
        bin/magento setup:db-declaration:generate-patch
        ```
        command to create a new schema patch with the name _**EnableAbandonedCartSegmentation**_
        * _use `--type schema` as command parameter_
        
    * copy new changes from the UpgradeSchema file into schema patch
    * run command
        ```bash
        bin/magento setup:upgrade
        ```
        * _If you are faced with some difficulties you can switch to the [i2019-schema-patches](https://github.com/fascinosum/magento2/tree/i2019-schema-patches) branch_
    * check the result in the database. There should the following tables:
        * abandoned_cart_table_index_store_1
        * abandoned_cart_table_index_store_1_replica
    * `patch_list` table should contains row with the patch name `Blackbox\SmartModule\Setup\Patch\Schema\EnableAbandonedCartSegmentation`
    * try to put any executable code, for example,
        ```php
        $connection->dropTable('core_config_data');
        ```
        into the patch file and run
        ```bash
        bin/magento setup:upgrade
        ```
        one more time. Verify that `core_config_data` still exists
4. Switch to the branch [i2019-data-changes](https://github.com/fascinosum/magento2/tree/i2019-data-changes)
    * use 
            ```bash
            bin/magento setup:db-declaration:generate-patch
            ```
            command to create a new data patch with the name _**PrepareInitialConfig**_
    * copy data changes form the InstallData file into _**PrepareInitialConfig**_ data patch
    * in the same way create _**AddSmartModuleUserCustomerAttribute**_ and _**ConvertReviewMessageToJson**_ data patches
    and copy data changes from the UpgradeData file
    * declare correct order for patches using `getDependencies` methods
        * _If you are faced with some difficulties you can switch to the [i2019-data-patches](https://github.com/fascinosum/magento2/tree/i2019-data-patches) branch_
    * execute SQL code 
        ```sql
        INSERT INTO `email_review_table` (`review_id`, `customer_id`, `store_id`) VALUES (1, 1, 1);
        INSERT INTO `email_review_content_table` (`review_id`, `message`) VALUES (1, 'a:2:{s:7:"message";s:12:"Test message";s:6:"rating";s:3:"4.3";}');
        ```
    * execute 
        ```bash
        bin/magento setup:upgrade
        ```
    * check the `patch_list` table, it should contain rows with the following patch data names
        * Blackbox\SmartModule\Setup\Patch\Data\PrepareInitialConfig
        * Blackbox\SmartModule\Setup\Patch\Data\AddSmartModuleUserCustomerAttribute
        * Blackbox\SmartModule\Setup\Patch\Data\ConvertReviewMessageToJson
    * check the `flag` table, there should be 3 new flags with the correct order of Blackbox_SmartModule versions
        * blackbox_flag_v_2_0_0
        * blackbox_flag_v_2_0_4
        * blackbox_flag_v_2_1_3
    * check the `email_review_content_table` table, `message` field must be JSON encoded

