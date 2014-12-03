<?php


class InitPlugin extends Migration {
    
	function description() {
        return 'initializes the database for this plugin';
    }

    public function up() {
	    DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `superwiki_pages` (
                `page_id` varchar(32) NOT NULL,
                `seminar_id` varchar(32) NOT NULL,
                `name` varchar(128) NOT NULL,
                `content` text NOT NULL,
                `permission` varchar(32) NOT NULL DEFAULT 'all',
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`page_id`),
                KEY `permission` (`permission`),
                KEY `seminar_id` (`seminar_id`)
            ) ENGINE=MyISAM
	    ");
    }
	
	public function down() {
        DBManager::get()->exec("
            DROP TABLE IF EXISTS `superwiki_pages`
        ");
    }
}