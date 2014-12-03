<?php

class WikiPage extends SimpleORMap {

    static public function findByName($name, $seminar_id)
    {
        $pages = self::findBySQL("name = :name AND seminar_id = :seminar_id", array('name' => $name, 'seminar_id' => $seminar_id));
        return count($pages) ? $pages[0] : null;
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'superwiki_pages';
        parent::configure($config);
    }
}