<?php

class InitCoworker extends Migration {
    
    public function up() {
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `superwiki_editors` (
                `user_id` varchar(32) NOT NULL,
                `page_id` varchar(32) NOT NULL,
                `latest_change` bigint(20) NOT NULL,
                UNIQUE KEY `unique_users` (`user_id`, `page_id`),
                KEY `user_id` (`user_id`),
                KEY `page_id` (`page_id`)
            )
	    ");

    }
	
	public function down() {
        DBManager::get()->exec("
            DROP TABLE IF EXISTS `superwiki_editors`
        ");
    }
}