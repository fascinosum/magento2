<h2>Declarative Schema Workshop</h2>

1. Install Magento 2.3.1 using branch [i2019](https://github.com/fascinosum/magento2/tree/i2019)

2. <h5>Module without dynamic schema changes</h5>

    * Switch to the branch [i2019-module](https://github.com/fascinosum/magento2/tree/i2019-module)
    * execute
        ```bash
        bin/magento setup:upgrade --convert-old-scripts 1
        ```
    * check the result in the `app/code/Blackbox/SmartModule/etc/db_schema.xml` file
    * see `Setup\UpgradeSchema::addReplicaTable` executes a direct SQL query. 
    `email_review_table_replica` must be manually declared in `etc/db_schema.xml`.
        
        Copy the declaration of `email_review_table` and change only the attribute `name` to `email_review_table_replica`
    * remove `setup_version` attribute from the `module` node in `app/code/Blackbox/SmartModule/etc/module.xml`
    * execute 
        ```bash
        bin/magento setup:upgrade --dry-run 1
        ```
    * check the result in `var/log/dry-run-installation.log` file. 
    Expected result: the file does not exist or there are no changes for the module tables

3. <h5>Module with dynamic schema changes</h5> 
    
    * Switch to the branch [i2019-dynamic-schema-changes](https://github.com/fascinosum/magento2/tree/i2019-dynamic-schema-changes)
    * use 
        ```bash
        bin/magento setup:db-declaration:generate-patch --type schema -- Blackbox_SmartModule EnableAbandonedCartSegmentation
        ```
        command to create a new schema patch with the name _**EnableAbandonedCartSegmentation**_
        * _we use `--type schema` as command parameter for schema patches_
        * _**NOTE:** Patches should be named by their purpose._
    * copy new changes from the `Setup/UpgradeSchema.php` file 
        to the `Setup/Patch/Schema/EnableAbandonedCartSegmentation::apply` method (use code from `enableAbandonedCartSegmentation` method).
        
        Add
        ```php
           $setup = $this->moduleDataSetup;
           $setup->startSetup();
         ```
        at the very beginning of the `Setup/Patch/Schema/EnableAbandonedCartSegmentation::apply` method and
        add
        ```php
            $setup->endSetup();
        ```
        at the end
    * implement `PatchVersionInterface` to `EnableAbandonedCartSegmentation`. 
    Use the module version value from the `Setup/UpgradeSchema.php` as a return value for `getVersion` method.
        * _**NOTE:** `PatchVersionInterface` is deprecated since it is used only for migration purposes. 
        New patches that appear after module migration should not implement this interface. 
        Migrated patches MUST implement this interface and return the version they are designated to in legacy setup script.
        This is required for backward compatibility._
    * run command
        ```bash
        bin/magento setup:upgrade
        ```
        * _If you are faced with some difficulties you can switch to the [i2019-schema-patches](https://github.com/fascinosum/magento2/tree/i2019-schema-patches) branch
        and execute the command again_
    * check the result in the database. There should the following tables:
        * `abandoned_cart_table_index_store_1`
        * `abandoned_cart_table_index_store_1_tmp`
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
    * _**NOTE:** After all setup code is migrated to patches the legacy schema setup/upgrade scripts can be deleted._
        
4. <h5>Module with data changes</h5>

    * Switch to the branch [i2019-data-changes](https://github.com/fascinosum/magento2/tree/i2019-data-changes)
    * use 
        ```bash
        bin/magento setup:db-declaration:generate-patch --type data -- Blackbox_SmartModule PrepareInitialConfig
        ```
        command to create a new data patch with the name _**PrepareInitialConfig**_
    * copy data changes from the `Setup/InstallData.php` file to the `Setup/Patch/Schema/PrepareInitialConfig::apply` method
    * in the same way create _**AddSmartModuleUserCustomerAttribute**_ and _**ConvertReviewMessageToJson**_ data patches
    and copy data changes from the `Setup/UpgradeData.php` file
    * implement `PatchVersionInterface` for all data patches. Declare correct order for patches using module versions 
    from the `UpgradeSchema` as a return value for `getVersion` method. 
        * _Use **2.0.0** as a module version for `PrepareInitialConfig` patch_
    * execute 
        ```bash
        bin/magento setup:upgrade
        ```
        * _If you are faced with some difficulties you can switch to the [i2019-data-patches](https://github.com/fascinosum/magento2/tree/i2019-data-patches) branch
        and execute the command again_
    * check the `patch_list` table, it should contain rows with the following patch data names
        * Blackbox\SmartModule\Setup\Patch\Data\PrepareInitialConfig
        * Blackbox\SmartModule\Setup\Patch\Data\AddSmartModuleUserCustomerAttribute
        * Blackbox\SmartModule\Setup\Patch\Data\ConvertReviewMessageToJson
    * check the `flag` table, there should be 2 new flags with the correct order of Blackbox_SmartModule versions
        * blackbox_flag_v_2_0_10
        * blackbox_flag_v_2_1_3
    * _**NOTE:** Since this is a migration scenario patches do not hold dependencies and the order is recognized by version, 
     however new patches order is recognized by dependencies. 
     Patches may have several dependencies, the final structure is a tree that is applied recursively.
     If you see that your patch requires some data from another patch you MUST add the dependency by refering the class name of the patch that your new one depends on._
    
5. <h5>Module with converted schema</h5>

    * You can use you the existing state of the project or switch to the branch [i2019-data-patches](https://github.com/fascinosum/magento2/tree/i2019-data-patches)
    * use 
        ```bash
        bin/magento setup:db-declaration:generate-whitelist --module-name Blackbox_SmartModule
        ```
        command to generate `etc/db_schema_whitelist.json`. 
        This file must contain all the entities from the `etc/db_schema.xml` file
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
    * check `etc/db_schema_whitelist.json`, there should be 2 indexer names
        ```json
        "index": {
            "ABANDONED_CART_TABLE_INDEX_QUOTE_ID_STORE_ID_CUSTOMER_ID": true,
            "ABANDONED_CART_TABLE_INDEX_STORE_ID_CUSTOMER_ID": true
        }
        ```
