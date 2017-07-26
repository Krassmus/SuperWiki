<?php

require_once __DIR__."/lib/SuperWikiFormat.php";
require_once __DIR__."/lib/SuperwikiPage.class.php";
require_once __DIR__."/lib/SuperwikiVersion.class.php";
require_once __DIR__."/lib/SuperwikiSettings.class.php";
require_once __DIR__ . "/vendor/Textmerger/Textmerger.php";
require_once 'lib/classes/Markup.class.php';

class SuperWiki extends StudIPPlugin implements StandardPlugin, SystemPlugin {

    public function __construct() {
        parent::__construct();
        if (UpdateInformation::isCollecting()) {
            $data = studip_utf8decode(Request::getArray("page_info"));
            if (stripos(Request::get("page"), "plugins.php/superwiki") !== false && isset($data['SuperWiki'])) {
                $output = array();
                $page = SuperwikiPage::find($data['SuperWiki']['page_id']);
                if ($data['SuperWiki']['mode'] === "read") {
                    if ($data['SuperWiki']['chdate'] < $page['chdate']) {
                        $output['html'] = $page->wikiFormat();
                        $output['chdate'] = $page['chdate'];
                    }
                } elseif ($data['SuperWiki']['mode'] === "edit" && $page->isEditable()) {
                    $content1 = str_replace("\r", "", $data['SuperWiki']['content']);
                    $original_content = str_replace("\r", "", $data['SuperWiki']['old_content']);
                    $content2 = str_replace("\r", "", $page['content']);
                    if ($original_content || $content1) {
                        $page['content'] = Textmerger::get()->merge(
                            $original_content,
                            $content1,
                            $content2
                        );
                        if ($page['content'] !== $content2) {
                            $page['last_author'] = $GLOBALS['user']->id;
                            $page->store();
                        }
                    }
                    if ($content1 !== $page['content']) {
                        $output['content'] = $page['content'];
                    }
                    //Online users
                    $statement = DBManager::get()->prepare("
                        INSERT INTO superwiki_editors
                        SET user_id = :me,
                            page_id = :page_id,
                            online = UNIX_TIMESTAMP()
                        ON DUPLICATE KEY UPDATE
                            online = UNIX_TIMESTAMP(),
                            latest_change = IF(:changed, UNIX_TIMESTAMP(), latest_change)
                    ");
                    $statement->execute(array(
                        'me' => $GLOBALS['user']->id,
                        'page_id' => $page->getId(),
                        'changed' => ($content1 !== $original_content) ? 1 : 0
                    ));
                    $statement = DBManager::get()->prepare("
                        SELECT user_id, latest_change
                        FROM superwiki_editors
                        WHERE page_id = :page_id
                            AND online >= UNIX_TIMESTAMP() - 10
                    ");
                    $statement->execute(array(
                        'page_id' => $page->getId()
                    ));
                    $onlineusers = "";
                    $onlineusers_count = 0;
                    $tf = new Flexi_TemplateFactory(__DIR__."/views");
                    $template = $tf->open("page/_online_user.php");
                    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $user) {
                        $template->set_attribute("user_id", $user['user_id']);
                        $template->set_attribute("writing", $user['latest_change'] >= time() - 4);
                        $onlineusers .= $template->render();
                        $onlineusers_count++;
                    }
                    $output['onlineusers_count'] = $onlineusers_count;
                    $output['onlineusers'] = $onlineusers;
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
            $icon->setImage(version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                ? Icon::create(($settings['icon'] ?: "wiki")."+new", "new")
                : Assets::image_path("icons/20/red/new/".($settings['icon'] ?: "wiki")), array('title' => sprintf(_("%s Seiten wurden ver�ndert."), $new_changes)));
        } else {
            $icon->setImage(version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                ? Icon::create(($settings['icon'] ?: "wiki"), "inactive")
                : Assets::image_path("icons/20/grey/".($settings['icon'] ?: "wiki")), array('title' => $settings ? $settings['name'] : _("SuperWiki")));
        }
        return $icon;
    }

    function getTabNavigation($course_id) {
        $settings = SuperwikiSettings::find($course_id);
        $tab = new Navigation($settings ? $settings['name'] : _("SuperWiki"), PluginEngine::getURL($this, array(), "page/view"));
        $tab->setImage(version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
            ? Icon::create(($settings['icon'] ?: "wiki"), "info_alt")
            : Assets::image_path("icons/16/white/".($settings['icon'] ?: "wiki")));
        $tab->addSubNavigation("wiki", new Navigation($settings ? $settings['name'] : _("SuperWiki"), PluginEngine::getURL($this, array(), "page/view")));
        $tab->addSubNavigation("all", new Navigation(_("Alle Seiten"), PluginEngine::getURL($this, array(), "overview/all")));
        return array('superwiki' => $tab);
    }

    function getNotificationObjects($course_id, $since, $user_id) {
        return null;
    }
}