<?php

require_once __DIR__ . "/lib/SuperwikiFormat.php";
require_once __DIR__."/lib/SuperwikiPage.php";
require_once __DIR__."/lib/SuperwikiVersion.php";
require_once __DIR__."/lib/SuperwikiSettings.php";
require_once __DIR__."/lib/SuperwikiCMS.php";
require_once __DIR__ . "/vendor/Textmerger/Textmerger.php";
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
                    if ($data['SuperWiki']['content'] || $data['SuperWiki']['old_content']) {
                        $content1 = str_replace("\r", "", $data['SuperWiki']['content']);
                        $original_content = str_replace("\r", "", $data['SuperWiki']['old_content']);
                        $content2 = str_replace("\r", "", $page['content']);
                        $output['content_server'] = $content2;
                        $merged = \Superwiki\Textmerger::get()->merge(
                            $original_content,
                            $content1,
                            $content2
                        );

                        if ($page['content'] !== $merged) {
                            $page['content'] = $merged;
                            $page['last_author'] = $GLOBALS['user']->id;
                            $output['content'] = $merged;
                            $page->store();
                        }
                    }
                    //Online users
                    $statement = DBManager::get()->prepare("
                        INSERT INTO superwiki_editors
                        SET user_id = :me,
                            page_id = :page_id,
                            online = UNIX_TIMESTAMP(),
                            latest_change = 0
                        ON DUPLICATE KEY UPDATE
                            online = UNIX_TIMESTAMP(),
                            latest_change = IF(:changed, UNIX_TIMESTAMP(), latest_change)
                    ");
                    $statement->execute(array(
                        'me' => $GLOBALS['user']->id,
                        'page_id' => $page->getId(),
                        'changed' => ($data['SuperWiki']['content'] || $data['SuperWiki']['old_content']) ? 1 : 0
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

        if ($GLOBALS['perm']->have_perm("root") && Navigation::hasItem("/admin/locations")) {
            $nav = new Navigation(_("Superwiki CMS"), PluginEngine::getURL($this, array(), "cms/overview"));
            Navigation::addItem("/admin/locations/superwikicms", $nav);
        }
        foreach (SuperwikiCMS::findBySQL("active = '1' ORDER BY title ASC") as $cms) {
            $nav = new Navigation(
                $cms['title'],
                PluginEngine::getURL($this, array('cms_id' => $cms->getId()), "page/view")
            );
            if ($cms['icon']) {
                $nav->setImage(Icon::create($cms['icon'], "navigation"));
            }
            if ($cms['description']) {
                $nav->setDescription($cms['description']);
            }

            $navigation_path = preg_split("/\//", $cms['navigation'], -1, PREG_SPLIT_NO_EMPTY);
            array_pop($navigation_path);
            $navigation_path = "/".implode("/", $navigation_path);
            if (Navigation::hasItem($navigation_path)) {
                Navigation::addItem($cms['navigation'], $nav);
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

    public function getIconNavigation($course_id, $last_visit, $user_id)
    {
        $settings = SuperwikiSettings::find($course_id);
        $icon = new Navigation(
            $settings && $settings['name'] ?: Config::get()->SUPERWIKI_NAME,
            PluginEngine::getURL($this, [], 'page/view')
        );
        $new_changes = SuperwikiPage::countBySql("seminar_id = ? AND chdate > ? AND last_author != ?", [$course_id, $last_visit, $user_id]);
        if ($new_changes) {
            $icon->setURL(PluginEngine::getURL($this, [], 'overview/latest_changes'), ['since' => $last_visit]);
            $icon->setImage(Icon::create(($settings['icon'] ?? 'wiki') . '+new', Icon::ROLE_NEW));
        } else {
            $icon->setImage(Icon::create($settings['icon'] ?? 'wiki', Icon::ROLE_INACTIVE), ['title' => $settings['name'] ?? _("SuperWiki")]);
        }
        return $icon;
    }

    public function getTabNavigation($course_id)
    {
        $settings = SuperwikiSettings::find($course_id) ?? [
            'name' => null,
            'icon' => null,
        ];

        $name = $settings['name'] ?: Config::get()->SUPERWIKI_NAME;
        $tab = new Navigation(
            $name,
            PluginEngine::getURL($this, [], 'page/view')
        );
        $tab->setImage(Icon::create($settings['icon'] ?? 'wiki', Icon::ROLE_INFO_ALT));
        $tab->addSubNavigation('wiki', new Navigation($name, PluginEngine::getURL($this, [], 'page/view')));
        $tab->addSubNavigation('all', new Navigation(_('Alle Seiten'), PluginEngine::getURL($this, [], 'overview/all')));
        return ['superwiki' => $tab];
    }

    function getNotificationObjects($course_id, $since, $user_id) {
        return null;
    }

    function getPluginname() {
        return Config::get()->SUPERWIKI_NAME;
    }

    function getMetadata() {
        $metadata = parent::getMetadata();
        $metadata['pluginname'] = Config::get()->SUPERWIKI_NAME;
        $metadata['descriptionlong'] = str_replace("SuperWiki", Config::get()->SUPERWIKI_NAME, $metadata['descriptionlong']);
        $metadata['summary'] = str_replace("SuperWiki", Config::get()->SUPERWIKI_NAME, $metadata['summary']);
        return $metadata;
    }
}
