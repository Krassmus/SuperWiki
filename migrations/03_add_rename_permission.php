<?php

class AddRenamePermission extends Migration {
    
    public function up() {
        DBManager::get()->exec("
            ALTER TABLE `superwiki_settings`
            ADD `rename_permission` VARCHAR(32) NOT NULL DEFAULT 'all' AFTER `create_permission`;
	    ");

    }
	
	public function down() {
        DBManager::get()->exec("
            ALTER TABLE `superwiki_settings` DROP `rename_permission`;
        ");
    }
}