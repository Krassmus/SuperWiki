<?php

class AddLinkIcon extends Migration {
    
    public function up() {
        DBManager::get()->exec("
            ALTER TABLE `superwiki_settings`
            ADD `link_icon` varchar(64) NOT NULL DEFAULT 'wiki' AFTER `icon`;
	    ");

    }
	
	public function down() {
        DBManager::get()->exec("
            ALTER TABLE `superwiki_settings` DROP `link_icon`;
        ");
    }
}