<?php

class AddCms extends Migration {
    
    public function up()
    {
        DBManager::get()->exec("
            CREATE TABLE `superwiki_cms` (
                `cms_id` varchar(32) NOT NULL DEFAULT '',
                `seminar_id` varchar(32) DEFAULT NULL,
                `active` tinyint(4) DEFAULT NULL,
                `title` varchar(64) DEFAULT NULL,
                `description` text,
                `navigation` varchar(64) DEFAULT NULL,
                `icon` varchar(128) DEFAULT NULL,
                `chdate` int(11) DEFAULT NULL,
                `mkdate` int(11) DEFAULT NULL,
                PRIMARY KEY (`cms_id`),
                KEY `seminar_id` (`seminar_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
        ");


    }
	
	public function down()
    {
        DBManager::get()->exec("
            DROP TABLE IF EXISTS `superwiki_cms`;
	    ");
    }
}