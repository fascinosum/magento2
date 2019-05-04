<h2>Declaration Schema Workshop</h2>

1. Install Magento 2.3.1 using branch [i2019](https://github.com/fascinosum/magento2/tree/i2019)

2. <h5>Module without dynamic schema changes</h5>

    * Switch to the branch [i2019-module](https://github.com/fascinosum/magento2/tree/i2019-module)
    * execute
        ```bash
        bin/magento setup:upgrade --convert-old-scripts 1
        ```
    * check the result in the `app/code/Blackbox/SmartModule/etc/db_schema.xml` file
    * remove `setup_version` attribute from the `module` node in `app/code/Blackbox/SmartModule/etc/module.xml`
    * execute 
        ```bash
        bin/magento setup:upgrade --dry-run 1
        ```
    * check the result in `dry-run-installation.log` file. There should not be any changes for the module tables

3. <h5>Module with dynamic schema changes</h5> 
    
    * Switch to the branch [i2019-dynamic-schema-changes](https://github.com/fascinosum/magento2/tree/i2019-dynamic-schema-changes)
    * use 
        ```bash
        bin/magento setup:db-declaration:generate-patch
        ```
        command to create a new schema patch with the name _**EnableAbandonedCartSegmentation**_
        * _use `--type schema` as command parameter_
        
    * copy new changes from the `UpgradeSchema` file into schema patch (use code from enableAbandonedCartSegmentation method)
    * implement `PatchVersionInterface` to `EnableAbandonedCartSegmentation`. 
    Use the module version value from the `UpgradeSchema` as a return value for `getVersion` method
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
        $connection->dropTable('abandoned_cart_table_index_store_1');
        ```
        into the patch file and run
        ```bash
        bin/magento setup:upgrade
        ```
        one more time. Verify that `abandoned_cart_table_index_store_1` still exists
        
4. <h5>Module with data changes</h5>

    * Switch to the branch [i2019-data-changes](https://github.com/fascinosum/magento2/tree/i2019-data-changes)
    * use 
        ```bash
        bin/magento setup:db-declaration:generate-patch
        ```
        command to create a new data patch with the name _**PrepareInitialConfig**_
    * copy data changes form the `InstallData` file into `PrepareInitialConfig` data patch
    * in the same way create _**AddSmartModuleUserCustomerAttribute**_ and _**ConvertReviewMessageToJson**_ data patches
    and copy data changes from the `UpgradeData` file
    * declare correct order for patches using module versions 
    from the `UpgradeSchema` as a return value for `getVersion` method. 
    Use **2.0.0** as a module version for `PrepareInitialConfig` patch
        * _If you are faced with some difficulties you can switch to the [i2019-data-patches](https://github.com/fascinosum/magento2/tree/i2019-data-patches) branch_
    * execute 
        ```bash
        bin/magento setup:upgrade
        ```
    * check the `patch_list` table, it should contain rows with the following patch data names
        * Blackbox\SmartModule\Setup\Patch\Data\PrepareInitialConfig
        * Blackbox\SmartModule\Setup\Patch\Data\AddSmartModuleUserCustomerAttribute
        * Blackbox\SmartModule\Setup\Patch\Data\ConvertReviewMessageToJson
    * check the `flag` table, there should be 2 new flags with the correct order of Blackbox_SmartModule versions
        * blackbox_flag_v_2_0_10
        * blackbox_flag_v_2_1_3
5. <h5>Module with converted schema</h5>

    * Switch to the branch [i2019-data-patches](https://github.com/fascinosum/magento2/tree/i2019-data-patches)
    * use 
        ```bash
        bin/magento setup:db-declaration:generate-whitelist --module-name Blackbox_SmartModule
        ```
        command to generate `db_schema_whitelist.json`
    * remove `<column name="quote_id"/>` from
        ```xml
        <index referenceId="ABANDONED_CART_TABLE_INDEX_QUOTE_ID_STORE_ID_CUSTOMER_ID" indexType="btree">
            <column name="quote_id"/>
            <column name="store_id"/>
            <column name="customer_id"/>
        </index>
        ```
        in `db_schema.xml`
    * execute 
        ```bash
        bin/magento setup:db-declaration:generate-whitelist --module-name Blackbox_SmartModule
        ```
        one more time
    * check `db_schema_whitelist.json`, there should be 2 indexer names
        ```json
        "index": {
            "ABANDONED_CART_TABLE_INDEX_QUOTE_ID_STORE_ID_CUSTOMER_ID": true,
            "ABANDONED_CART_TABLE_INDEX_STORE_ID_CUSTOMER_ID": true
        }
        ```
