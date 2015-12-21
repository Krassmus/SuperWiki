<?php

require_once __DIR__."/lib/SuperWikiFormat.php";
require_once __DIR__."/lib/SuperwikiPage.class.php";
require_once __DIR__."/lib/SuperwikiVersion.class.php";
require_once __DIR__."/lib/SuperwikiSettings.class.php";
require_once __DIR__ . "/vendor/TextMerger/TextMerger.php";
require_once 'lib/classes/Markup.class.php';

class SuperWiki extends StudIPPlugin implements StandardPlugin, SystemPlugin {

    public function __construct() {
        parent::__construct();
        if (UpdateInformation::isCollecting()) {
            $data = Request::getArray("page_info");
            if (stripos(Request::get("page"), "plugins.php/superwiki") !== false && isset($data['SuperWiki'])) {
                $output = array();
                $page = SuperwikiPage::find($data['SuperWiki']['page_id']);
                if ($data['SuperWiki']['mode'] === "read") {
                    if ($data['SuperWiki']['chdate'] < $page['chdate']) {
                        $output['html'] = $page->wikiFormat();
                        $output['chdate'] = $page['chdate'];
                    }
                } elseif ($data['SuperWiki']['mode'] === "edit" && $page->isEditable()) {
                    $content1 =  studip_utf8decode($data['SuperWiki']['content']);
                    $original_content =  studip_utf8decode($data['SuperWiki']['old_content']);
                    $content2 = $page['content'];
                    $page['content'] = TextMerger::get()->merge($original_content, $content1, $content2);
                    $output['debugcontent'] = $page['content'];
                    if ($page['content'] !== $content2) {
                        $page['last_author'] = $GLOBALS['user']->id;
                        $page->store();
                    }
                    if ($data['SuperWiki']['chdate'] < $page['chdate']) {
                        $output['content'] = $page['content'];
                        $output['chdate'] = $page['chdate'];
                    }
                    //Online users
                    $statement = DBManager::get()->prepare("
                        INSERT INTO superwiki_editors
                        SET user_id = :me,
                            page_id = :page_id,
                            latest_change = UNIX_TIMESTAMP()
                        ON DUPLICATE KEY UPDATE
                            latest_change = UNIX_TIMESTAMP()
                    ");
                    $statement->execute(array(
                        'me' => $GLOBALS['user']->id,
                        'page_id' => $page->getId()
                    ));
                    $statement = DBManager::get()->prepare("
                        SELECT user_id
                        FROM superwiki_editors
                        WHERE page_id = :page_id
                            AND latest_change >= UNIX_TIMESTAMP() - 10
                    ");
                    $statement->execute(array(
                        'page_id' => $page->getId()
                    ));
                    $onlineusers = "";
                    $onlineusers_count = 0;
                    foreach ($statement->fetchAll(PDO::FETCH_COLUMN, 0) as $user_id) {
                        $onlineusers .= '<a href="'.URLHelper::getLink("dispatch.php/profile", array('username' => get_username($user_id))).'" title="'.htmlReady(get_fullname($user_id)).'">'.Avatar::getAvatar($user_id)->getImageTag(Avatar::SMALL).'</a> ';
                        $onlineusers_count++;
                    }
                    if ($onlineusers_count > 1) {
                        $output['onlineusers'] = $onlineusers;
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
        $new_changes = SuperwikiPage::countBySql("seminar_id = ? AND chdate > ? AND last_author != ?", array($course_id, $last_visit, $user_id));
        if ($new_changes) {
            $icon->setURL(PluginEngine::getURL($this, array(), "overview/latest_changes"), array('since' => $last_visit));
            $icon->setImage(Assets::image_path("icons/20/red/new/".($settings['icon'] ?: "wiki")), array('title' => sprintf(_("%s Seiten wurden verändert."), $new_changes)));
        } else {
            $icon->setImage(Assets::image_path("icons/20/grey/".($settings['icon'] ?: "wiki")), array('title' => $settings ? $settings['name'] : _("SuperWiki")));
        }
        return $icon;
    }

    function getTabNavigation($course_id) {
        $settings = SuperwikiSettings::find($course_id);
        $tab = new Navigation($settings ? $settings['name'] : _("SuperWiki"), PluginEngine::getURL($this, array(), "page/view"));
        $tab->setImage(Assets::image_path("icons/16/white/".($settings['icon'] ?: "wiki")));
        $tab->addSubNavigation("wiki", new Navigation($settings ? $settings['name'] : _("SuperWiki"), PluginEngine::getURL($this, array(), "page/view")));
        $tab->addSubNavigation("all", new Navigation(_("Alle Seiten"), PluginEngine::getURL($this, array(), "overview/all")));
        return array('superwiki' => $tab);
    }

    function getNotificationObjects($course_id, $since, $user_id) {
        return null;
    }
}