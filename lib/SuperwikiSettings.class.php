<?php

class SuperwikiSettings extends SimpleORMap {

    protected static function configure($config = array())
    {
        $config['db_table'] = 'superwiki_settings';
        parent::configure($config);
    }

    public function haveCreatePermission($user_id = null)
    {
        $user_id || $user_id = $GLOBALS['user']->id;
        switch ($this['create_permission']) {
            case "all":
                return true;
            case "tutor":
                return $GLOBALS['perm']->have_studip_perm("tutor", $this['seminar_id'], $user_id);
            case "dozent":
                return $GLOBALS['perm']->have_studip_perm("dozent", $this['seminar_id'], $user_id);
        }
        return $this->isNew();
    }
}