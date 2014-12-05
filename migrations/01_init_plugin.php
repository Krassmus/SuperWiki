<?php


class InitPlugin extends Migration {
    
	function description() {
        return 'initializes the database for this plugin';
    }

    public function up() {
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `superwiki_settings` (
                `seminar_id` varchar(32) NOT NULL,
                `name` varchar(64) NOT NULL DEFAULT 'SuperWiki',
                `indexpage` VARCHAR(32) NULL,
                `create_permission` varchar(32) NOT NULL DEFAULT 'all',
                PRIMARY KEY (`seminar_id`),
                KEY `indexpage` (`indexpage`)
            ) ENGINE=MyISAM
	    ");
	    DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `superwiki_pages` (
                `page_id` varchar(32) NOT NULL,
                `seminar_id` varchar(32) NOT NULL,
                `name` varchar(128) NOT NULL,
                `content` text NULL,
                `read_permission` varchar(32) NOT NULL DEFAULT 'all',
                `write_permission` varchar(32) NOT NULL DEFAULT 'all',
                `last_author` VARCHAR(32) NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`page_id`),
                KEY `read_permission` (`read_permission`),
                KEY `write_permission` (`write_permission`),
                KEY `seminar_id` (`seminar_id`)
            ) ENGINE=MyISAM
	    ");
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `superwiki_versions` (
                `version_id` varchar(32) NOT NULL,
                `page_id` varchar(32) NOT NULL,
                `seminar_id` varchar(32) NOT NULL,
                `name` varchar(128) NOT NULL,
                `content` text NULL,
                `read_permission` varchar(32) NOT NULL DEFAULT 'all',
                `write_permission` varchar(32) NOT NULL DEFAULT 'all',
                `last_author` VARCHAR(32) NOT NULL,
                `chdate` bigint(20) NOT NULL,
                `mkdate` bigint(20) NOT NULL,
                PRIMARY KEY (`version_id`),
                KEY `page_id` (`page_id`),
                KEY `read_permission` (`read_permission`),
                KEY `write_permission` (`write_permission`),
                KEY `seminar_id` (`seminar_id`)
            ) ENGINE=MyISAM
	    ");
    }
	
	public function down() {
        DBManager::get()->exec("
            DROP TABLE IF EXISTS `superwiki_settings`
        ");
        DBManager::get()->exec("
            DROP TABLE IF EXISTS `superwiki_pages`
        ");
    }
}