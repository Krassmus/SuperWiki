<?php

class AddSuperwikiOnlineField extends Migration {
    
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `superwiki_editors`
            ADD `online` bigint(20) NOT NULL AFTER `latest_change`;
	    ");
    }
	
	public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `superwiki_editors` DROP `online`;
	    ");
    }
}