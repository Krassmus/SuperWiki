<?php

class AddOwnUpdater extends Migration {
    
    public function up()
    {
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `superwiki_connections` (
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

        Config::get()->create(
            "SUPERWIKI_USE_OWN_UPDATER",
            array(
                'type' => "boolean",
                'range' => "global",
                'section' => "Superwiki",
                'value' => 1,
                'description' => "Soll ein eigener Updater (1) genutzt werden oder der JSUpdater des Kerns (0)?"
            )
        );
        Config::get()->create(
            "SUPERWIKI_NAME",
            array(
                'type' => "string",
                'range' => "global",
                'section' => "Superwiki",
                'value' => "Superwiki",
                'description' => "Mit welchem Namen soll SuperWiki angezeigt werden?"
            )
        );
    }
	
	public function down()
    {
        DBManager::get()->exec("
            DROP TABLE IF EXISTS `superwiki_connections`;
	    ");
        Config::get()->delete("SUPERWIKI_USE_OWN_UPDATER");
        Config::get()->delete("SUPERWIKI_NAME");
    }
}