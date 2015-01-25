<?php

require_once __DIR__."/models/SuperwikiPage.class.php";
require_once __DIR__."/models/SuperwikiVersion.class.php";
require_once __DIR__."/models/SuperwikiSettings.class.php";
require_once __DIR__."/vendor/TextMerger.php";

class SuperWiki extends StudIPPlugin implements StandardPlugin {

    public function __construct() {
        parent::__construct();
        if (UpdateInformation::isCollecting()) {
            $data = Request::getArray("page_info");
            if (stripos(Request::get("page"), "plugins.php/superwiki") !== false && isset($data['SuperWiki'])) {
                $output = array();
                $page = SuperwikiPage::find($data['SuperWiki']['page_id']);
                if ($data['SuperWiki']['mode'] === "read") {
                    if ($data['SuperWiki']['chdate'] < $page['chdate']) {
                        $output['html'] = formatReady($page['content']);
                        $output['chdate'] = $page['chdate'];
                    }
                }
                if ($data['SuperWiki']['mode'] === "edit") {
                    $content1 =  studip_utf8decode($data['SuperWiki']['content']);
                    $original_content =  studip_utf8decode($data['SuperWiki']['old_content']);
                    $content2 = $page['content'];
                    //$page['content'] = $page->merge($content1, $content2, $original_content);
                    $page['content'] = TextMerger::get()->merge($original_content, $content1, $content2);
                    if ($page['content'] !== $content2) {
                        $page['last_author'] = $GLOBALS['user']->id;
                        $page->store();
                    }
                    if ($data['SuperWiki']['chdate'] < $page['chdate']) {
                        $output['content'] = $page['content'];
                        $output['chdate'] = $page['chdate'];
                    }
                }
                if (count($output)) {
                    UpdateInformation::setInformation("SuperWiki.updatePage", $output);
                }
            }
        }
    }

    /**
     * Initializes the plugin when actually invoked. Injects stylesheets into
     * the page layout.
     */
    public function initialize()
    {
        $this->addStylesheet('assets/superwiki.less');
    }

    function getInfoTemplate($course_id) {
        return null;
    }

    function getIconNavigation($course_id, $last_visit, $user_id) {
        $settings = SuperwikiSettings::find($course_id);
        $icon = new Navigation($settings ? $settings['name'] : _("SuperWiki"), PluginEngine::getURL($this, array(), "page/view"));
        $new_changes = SuperwikiPage::countBySql("chdate > ? AND last_author != ?", array($last_visit, $user_id));
        if ($new_changes) {
            $icon->setImage(Assets::image_path("icons/20/red/".$settings['icon']), array('title' => sprintf(_("%s Seiten wurden ver�ndert."), $new_changes)));
        } else {
            $icon->setImage(Assets::image_path("icons/20/grey/".$settings['icon']), array('title' => $settings ? $settings['name'] : _("SuperWiki")));
        }
        return $icon;
    }

    function getTabNavigation($course_id) {
        $settings = SuperwikiSettings::find($course_id);
        $tab = new Navigation($settings ? $settings['name'] : _("SuperWiki"), PluginEngine::getURL($this, array(), "page/view"));
        $tab->setImage(Assets::image_path("icons/16/white/".$settings['icon']));
        $tab->addSubNavigation("wiki", new Navigation($settings ? $settings['name'] : _("SuperWiki"), PluginEngine::getURL($this, array(), "page/view")));
        $tab->addSubNavigation("all", new Navigation(_("Alle Seiten"), PluginEngine::getURL($this, array(), "overview/all")));
        return array('superwiki' => $tab);
    }

    function getNotificationObjects($course_id, $since, $user_id) {
        return null;
    }
}