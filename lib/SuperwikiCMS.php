<?php

class SuperwikiCMS extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'superwiki_cms';
        parent::configure($config);
    }
}