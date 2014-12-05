<?php

require_once __DIR__."/models/SuperwikiPage.class.php";
require_once __DIR__."/models/SuperwikiVersion.class.php";
require_once __DIR__."/models/SuperwikiSettings.class.php";

class SuperWiki extends StudIPPlugin implements StandardPlugin {

    public function __construct() {
        parent::__construct();
        if (UpdateInformation::isCollecting()) {
            $data = Request::getArray("page_info");
            if (stripos(Request::get("page"), "plugins.php/superwiki") !== false && isset($data['SuperWiki'])) {
                $output = array();
                $page = WikiPage::findByName($data['SuperWiki']['site'], $data['SuperWiki']['seminar_id']);
                if ($data['SuperWiki']['mode'] === "read") {
                    if ($data['SuperWiki']['chdate'] < $page['chdate']) {
                        $output['html'] = formatReady($page['content']);
                        $output['chdate'] = $page['chdate'];
                    }
                }
                if (count($output)) {
                    UpdateInformation::setInformation("SuperWiki.updatePage", $output);
                }
            }
        }
    }

    function getInfoTemplate($course_id) {
        return null;
    }

    function getIconNavigation($course_id, $last_visit, $user_id) {
        $settings = SuperwikiSettings::find($course_id);
        $tab = new Navigation($settings ? $settings['name'] : _("SuperWiki"), PluginEngine::getURL($this, array(), "pad/site"));
        $tab->setImage(Assets::image_path("icons/20/grey/wiki"));
        return $tab;
    }

    function getTabNavigation($course_id) {
        $settings = SuperwikiSettings::find($course_id);
        $tab = new Navigation($settings ? $settings['name'] : _("SuperWiki"), PluginEngine::getURL($this, array(), "pad/site"));
        $tab->setImage(Assets::image_path("icons/16/white/wiki"));
        return array('superwiki' => $tab);
    }

    function getNotificationObjects($course_id, $since, $user_id) {
        return null;
    }
}