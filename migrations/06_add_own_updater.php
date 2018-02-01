<?php

class AddOwnUpdater extends Migration {
    
    public function up()
    {
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `cowriter_connections` (
                `page_id` varchar(32) NOT NULL,
                `user_id` varchar(32) NOT NULL,
                `offer_sdp` text NOT NULL,
                `answerer_id` varchar(32) NOT NULL,
                `answer_sdp` text,
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`page_id`,`user_id`,`answerer_id`)
            )
        ");

        DBManager::get()->exec("
            INSERT IGNORE INTO `config` (`config_id`, `parent_id`, `field`, `value`, `is_default`, `type`, `range`, `section`, `position`, `mkdate`, `chdate`, `description`, `comment`, `message_template`) 
            VALUES
                (MD5('SUPERWIKI_USE_OWN_UPDATER'), '', 'SUPERWIKI_USE_OWN_UPDATER', '1', 0, 'boolean', 'global', 'SuperWiki', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'Soll ein eigener Updater (1) genutzt werden oder der JSUpdater des Kerns (0)?', '', '')
        ");
    }
	
	public function down()
    {
        DBManager::get()->exec("
            DROP TABLE IF EXISTS `cowriter_connections`;
	    ");
    }
}