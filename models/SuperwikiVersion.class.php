<?php

class SuperwikiVersion extends SimpleORMap {

    static public function findByPage_id($page_id)
    {
        return self::findBySQL("page_id = ? ORDER BY chdate DESC", array($page_id));
    }

    protected static function configure($config = array())
    {
        $config['db_table'] = 'superwiki_versions';
        parent::configure($config);
    }

    public function wikiFormat()
    {
        $text = formatReady($this['content']);
        $pages = SuperwikiPage::findBySQL("seminar_id = ? AND content IS NOT NULL AND content != '' ORDER BY CHAR_LENGTH(name) DESC", array($this['seminar_id']));
        foreach ($pages as $page) {
            if ($page->getId() !== $this['page_id']) {
                $text = preg_replace("/(\s)".$page['name']."/", '$1<a href="'.URLHelper::getLink("plugins.php/superwiki/page/view/".$page->getId(), array('cid' => $page['seminar_id'])).'">'.Assets::img("icons/16/blue/wiki", array('class' => "text-bottom"))." ".htmlReady($page['name']).'</a>', $text);
            }
        }
        return $text;
    }
}