<?php

class SuperwikiVersion extends SimpleORMap {

    protected static function configure($config = array())
    {
        $config['db_table'] = 'superwiki_versions';
        parent::configure($config);
    }
}