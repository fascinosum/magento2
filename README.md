<h1>Declarative Schema Workshop</h1>

1. <h3>Install Magento</h3>

    * If you have already cloned [magento2](https://github.com/magento/magento2) repository use 
        ```bash
        git remote add workshop git@github.com:fascinosum/magento2.git
        git fetch workshop
        ```
        If you already have a pre-installed environment for you, navigate to the source git directory
        and execute
        ```bash
        git fetch origin
        git checkout i2019
        git pull origin i2019
        ```
        otherwise use
        ```bash
        git clone git@github.com:fascinosum/magento2.git
        ```
    * Checkout to the `i2019` branch
    * Uninstall Magento if it is installed
        ```bash
        bin/magento setup:uninstall -q
        ```
    * Update composer packages
        ```bash
        composer install
        ```
    * Install Magento 2.3.1 using [i2019](https://github.com/fascinosum/magento2/tree/i2019) branch.
        You can use any of the following methods
        * WebSetup Wizard
        * cli command
            ```bash
            bin/magento setup:install --backend-frontname=admin --language=en_US \
            --timezone=America/Los_Angeles --currency=USD --use-secure=0 --use-secure-admin=0 \
            --use-rewrites=1 --admin-user=admin --admin-password=anyrand0mpass \
            --admin-firstname=Admin --admin-lastname=Admin --admin-email=test@example.com \
            --base-url=http://<domain_name>/ --base-url-secure=https://<domain_name>/ \
            --db-user=<db_user> --db-password=<db_password> --db-name=<db_name>
            ```
        * any installation script
        * _**NOTE:** Do not use a database prefix_

2. <h3>Module without dynamic schema changes</h3>

    * Switch to the [i2019-module](https://github.com/fascinosum/magento2/tree/i2019-module) branch
    * Execute
        ```bash
        bin/magento setup:upgrade --convert-old-scripts 1
        ```
    * Check the result in the `app/code/Blackbox/SmartModule/etc/db_schema.xml` file
    * See `Setup\UpgradeSchema::addReplicaTable` executes a direct SQL query. 
    `email_review_table_replica` must be manually declared in `etc/db_schema.xml`.
        
        Copy the declaration of `email_review_table` and change only the attribute `name` to `email_review_table_replica`
    * Remove `setup_version` attribute from the `module` node in `app/code/Blackbox/SmartModule/etc/module.xml`
    * Execute 
        ```bash
        bin/magento setup:upgrade --dry-run 1
        ```
    * Check the result in `var/log/dry-run-installation.log` file. 
        Expected result: the file does not exist or there are no changes for the module tables

3. <h3>Module with dynamic schema changes</h3> 
    
    * Switch to the 
        [i2019-dynamic-schema-changes](https://github.com/fascinosum/magento2/tree/i2019-dynamic-schema-changes) branch
    * Use 
        ```bash
        bin/magento setup:db-declaration:generate-patch --type schema -- Blackbox_SmartModule EnableAbandonedCartSegmentation
        ```
        command to create a new schema patch with the name _**EnableAbandonedCartSegmentation**_
        * _we use `--type schema` as command parameter for schema patches_
        * _**NOTE:** Patches should be named by their purpose._
    * Copy new changes from the `Setup/UpgradeSchema.php` file 
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
    * Implement `PatchVersionInterface` to `EnableAbandonedCartSegmentation`. 
    Use the module version value from the `Setup/UpgradeSchema.php` as a return value for `getVersion` method.
        * _**NOTE:** `PatchVersionInterface` is deprecated since it is used only for migration purposes. 
        New patches that appear after module migration should not implement this interface. 
        Migrated patches MUST implement this interface and return the version they are designated to in legacy setup script.
        This is required for backward compatibility._
    * Run command
        ```bash
        bin/magento setup:upgrade
        ```
        * _If you are faced with some difficulties you can switch to the 
            [i2019-schema-patches](https://github.com/fascinosum/magento2/tree/i2019-schema-patches) branch
        and execute the command again_
    * Check the result in the database. There should the following tables:
        * `abandoned_cart_table_index_store_1`
        * `abandoned_cart_table_index_store_1_tmp`
    * `patch_list` table should contains row with the patch name `Blackbox\SmartModule\Setup\Patch\Schema\EnableAbandonedCartSegmentation`
    * Try to put any executable code, for example,
        ```php
        $connection->dropTable('abandoned_cart_table_index_store_1');
        ```
        into the patch file and run
        ```bash
        bin/magento setup:upgrade
        ```
        one more time. Verify that `abandoned_cart_table_index_store_1` still exists
    * _**NOTE:** After all setup code is migrated to patches the legacy schema setup/upgrade scripts can be deleted._
        
4. <h3>Module with data changes</h3>

    * Switch to the branch [i2019-data-changes](https://github.com/fascinosum/magento2/tree/i2019-data-changes)
    * Use 
        ```bash
        bin/magento setup:db-declaration:generate-patch --type data -- Blackbox_SmartModule PrepareInitialConfig
        ```
        command to create a new data patch with the name _**PrepareInitialConfig**_
    * Copy data changes from the `Setup/InstallData.php` file to the `Setup/Patch/Schema/PrepareInitialConfig::apply` method
    * Create _**AddSmartModuleUserCustomerAttribute**_ and _**ConvertReviewMessageToJson**_ data patches in the same way
    and copy data changes from the `Setup/UpgradeData.php` file
    * Implement `PatchVersionInterface` for all data patches. Declare correct order for patches using module versions 
        from the `UpgradeSchema` as a return value for `getVersion` method. 
        * _Use **2.0.0** as a module version for `PrepareInitialConfig` patch_
    * Execute 
        ```bash
        bin/magento setup:upgrade
        ```
        * _If you are faced with some difficulties you can switch 
            to the [i2019-data-patches](https://github.com/fascinosum/magento2/tree/i2019-data-patches) branch
        and execute the command again_
    * Check the `patch_list` table, it should contain rows with the following patch data names
        * Blackbox\SmartModule\Setup\Patch\Data\PrepareInitialConfig
        * Blackbox\SmartModule\Setup\Patch\Data\AddSmartModuleUserCustomerAttribute
        * Blackbox\SmartModule\Setup\Patch\Data\ConvertReviewMessageToJson
    * Check the `flag` table, there should be 2 new flags with the correct order of Blackbox_SmartModule versions
        * blackbox_flag_v_2_0_10
        * blackbox_flag_v_2_1_3
    * _**NOTE:** Since this is a migration scenario patches do not hold dependencies and the order is recognized by version, 
         however new patches order is recognized by dependencies. 
         Patches may have several dependencies, the final structure is a tree that is applied recursively.
         If you see that your patch requires some data from another patch you MUST add the dependency by refering 
         the class name of the patch that your new one depends on._
    
5. <h3>Module with converted schema</h3>

    * You can use you the existing state of the project or switch 
        to the [i2019-data-patches](https://github.com/fascinosum/magento2/tree/i2019-data-patches) branch
    * Use 
        ```bash
        bin/magento setup:db-declaration:generate-whitelist --module-name Blackbox_SmartModule
        ```
        command to generate `etc/db_schema_whitelist.json`. 
        This file must contain all the entities from the `etc/db_schema.xml` file
    * Remove `<column name="quote_id"/>` from
        ```xml
        <index referenceId="ABANDONED_CART_TABLE_INDEX_QUOTE_ID_STORE_ID_CUSTOMER_ID" indexType="btree">
            <column name="quote_id"/>
            <column name="store_id"/>
            <column name="customer_id"/>
        </index>
        ```
        in `db_schema.xml`
    * Execute 
        ```bash
        bin/magento setup:db-declaration:generate-whitelist --module-name Blackbox_SmartModule
        ```
        one more time
    * Check `etc/db_schema_whitelist.json`, there should be 2 indexer names
        ```json
        "index": {
            "ABANDONED_CART_TABLE_INDEX_QUOTE_ID_STORE_ID_CUSTOMER_ID": true,
            "ABANDONED_CART_TABLE_INDEX_STORE_ID_CUSTOMER_ID": true
        }
        ```
