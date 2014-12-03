<?php

require_once __DIR__."/models/WikiPage.class.php";

class SuperWiki extends StudIPPlugin implements StandardPlugin {

    public function __construct() {
        parent::__construct();
        if (UpdateInformation::isCollecting()) {
            $data = Request::getArray("page_info");
            if (stripos(Request::get("page"), "plugins.php/superwiki") !== false && isset($data['SuperWiki'])) {
                $output = array();
                $page = WikiPage::findByName($data['SuperWiki']['site'], $data['SuperWiki']['seminar_id']);
                if ($data['SuperWiki']['chdate'] < $page['chdate']) {
                    $output['html'] = formatReady($page['content']);
                    $output['chdate'] = $page['chdate'];
                    UpdateInformation::setInformation("SuperWiki.updatePage", $output);
                }

            }
        }
    }

    function getInfoTemplate($course_id) {
        return null;
    }

    function getIconNavigation($course_id, $last_visit, $user_id) {
        return null;
    }

    function getTabNavigation($course_id) {
        $tab = new Navigation(_("SuperWiki"), PluginEngine::getURL($this, array(), "pad/site"));
        $tab->setImage(Assets::image_path("icons/32/white/wiki"));
        return array('superwiki' => $tab);
    }

    function getNotificationObjects($course_id, $since, $user_id) {
        return null;
    }
}