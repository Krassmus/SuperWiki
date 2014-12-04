<?php

class SuperwikiSettings extends SimpleORMap {

    protected static function configure($config = array())
    {
        $config['db_table'] = 'superwiki_settings';
        parent::configure($config);
    }
}