<?php

require_once 'app/controllers/plugin_controller.php';

class PageController extends PluginController {

    protected $allow_nobody = false;

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (Request::get("cms_id")) {
            $this->cms = SuperwikiCMS::find(Request::get("cms_id"));
            $this->settings = new SuperwikiSettings($this->cms['seminar_id']);
            URLHelper::bindLinkParam("cms_id", $this->cms->getId());
            $navigation = preg_split("/\//", $this->cms['navigation'], -1, PREG_SPLIT_NO_EMPTY);
            if (count($navigation) === 1) {
                $navigation[] = "superwiki_subtab";
                Navigation::addItem(
                    $this->cms['navigation']."/superwiki_subtab",
                    new Navigation($this->cms['title'], Navigation::getItem($this->cms['navigation'])->getURL())
                );
            }
            if (count($navigation) === 2) {
                $navigation[] = "superwiki_subsubtab";
                Navigation::addItem($this->cms['navigation']."/superwiki_subtab/superwiki_subsubtab", new Navigation($this->cms['title'], Navigation::getItem($this->cms['navigation'])->getURL()));
                Navigation::addItem($this->cms['navigation']."/superwiki_subtab/superwiki_all", new Navigation(_("Alle Seiten"), PluginEngine::getURL($this->plugin, array('cms_id' => $this->cms->getId()), "overview/all")));
            }
            $navigation = "/".implode("/", $navigation);
            Navigation::activateItem($navigation);
        } else {
            $this->course_id = Context::getId();
            $this->settings = new SuperwikiSettings($this->course_id);
            if (Navigation::hasItem("/course/superwiki/wiki")) {
                Navigation::activateItem("/course/superwiki/wiki");
            }
            Navigation::getItem("/course/superwiki")->setImage(
                Icon::create(($this->settings['icon'] ?: "wiki"), "info")
            );
            PageLayout::setTitle(Context::getHeaderLine()  . " - ".($this->settings && $this->settings['name'] ? $this->settings['name'] : Config::get()->SUPERWIKI_NAME));
            Helpbar::Get()->addLink(_("Wikilinks und Navigation"), "https://github.com/Krassmus/SuperWiki/wiki/Wikilinks-und-Navigation", null, "_blank");
            Helpbar::Get()->addLink(_("Unsichtbare Wikiseiten"), "https://github.com/Krassmus/SuperWiki/wiki/Unsichtbare-Wikiseiten", null, "_blank");
            Helpbar::Get()->addLink(sprintf(_("%s für Gruppenaufgaben"), Config::get()->SUPERWIKI_NAME), "https://github.com/Krassmus/SuperWiki/wiki/SuperWiki-für-Gruppenaufgaben", null, "_blank");
            //Helpbar::Get()->addLink(_("Superwiki für Lernorganisation"), "https://github.com/Krassmus/SuperWiki/wiki/Wikilinks-und-Navigation", null, "_blank");
            Helpbar::Get()->addLink(sprintf(_("Präsentationen mit %s"), Config::get()->SUPERWIKI_NAME), "https://github.com/Krassmus/SuperWiki/wiki/Präsentationen-mit-SuperWiki", null, "_blank");
        }
        PageLayout::addScript($this->plugin->getPluginURL()."/vendor/Textmerger/Textmerger.js");
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/superwiki.js");

        if ($GLOBALS['perm']->have_perm("root")) {
            Helpbar::Get()->addLink(_("PHP-Test"), URLHelper::getURL("plugins_packages/RasmusFuhse/SuperWiki/vendor/Textmerger/test/php.php"), null, "_blank");
            Helpbar::Get()->addLink(_("JS-Test"), URLHelper::getURL("plugins_packages/RasmusFuhse/SuperWiki/vendor/Textmerger/test/js.html"), null, "_blank");
        }
    }

    public function view_action($page_id = null)
    {
        $this->page = $page_id ? new SuperwikiPage($page_id) : new SuperwikiPage($this->settings['indexpage'] ?: null);
        if ($this->cms) {
            if (!$this->cms['active'] || $this->cms['seminar_id'] !== $this->page['seminar_id']) {
                throw new AccessDeniedException();
            }
            if (!$this->page->isNew() && !$this->page->isReadable('cms')) {
                throw new AccessDeniedException("Keine Berechtigung.");
            }
            PageLayout::setTitle($this->cms['title'].": ".$this->page['name']);
        } else {
            if ($page_id) {
                if ($this->page['seminar_id'] !== $this->course_id) {
                    throw new AccessDeniedException("Not in right course");
                }
                $history = $_SESSION['SuperWiki_History'][$this->course_id] ?? [];
                if ($history[count($history) - 1] !== $page_id) {
                    $history[] = $page_id;
                    if (count($history) > 6) {
                        array_shift($history);
                    }
                }
                $_SESSION['SuperWiki_History'][$this->course_id] = $history;
            }
            if (!$this->page->isNew() && !$this->page->isReadable()) {
                throw new AccessDeniedException("Keine Berechtigung.");
            }
        }
    }

    public function edit_action($page_id = null)
    {
        if ($page_id) {
            $this->page = new SuperwikiPage($page_id);

            if ($this->page['seminar_id'] !== $this->course_id) {
                throw new AccessDeniedException("Not in right course");
            }
        } else {
            $this->page = SuperwikiPage::findByName(Request::get("name"), $this->course_id);
            if (!$this->page) {
                $this->page = new SuperwikiPage();
                $this->page['name'] = Request::get("name");
                $this->page['seminar_id'] = $this->course_id;
            }
        }
        if ((!$this->page->isNew() && !$this->page->isEditable()) || ($this->page->isNew() && !$this->settings->haveCreatePermission())) {
            throw new AccessDeniedException("Keine Berechtigung.");
        }

        if (!$this->page->isNew()) {
            $statement = DBManager::get()->prepare("
                    INSERT INTO superwiki_editors
                    SET user_id = :me,
                        page_id = :page_id,
                        online = UNIX_TIMESTAMP(),
                        latest_change = '0'
                    ON DUPLICATE KEY UPDATE
                        online = UNIX_TIMESTAMP()
                ");
            $statement->execute(array(
                'me' => $GLOBALS['user']->id,
                'page_id' => $page_id
            ));
        }

        if (!$this->page->isNew()) {
            $this->onlineusers = $this->page->getActiveUsers();
        }
    }

    public function save_action($page_id = null)
    {
        if ($page_id) {
            $this->page = new SuperwikiPage($page_id);

            if ($this->page['seminar_id'] !== $this->course_id) {
                throw new AccessDeniedException("Not in right course");
            }
        } else {
            $this->page = SuperwikiPage::findByName(Request::get("name"), $this->course_id);
            if (!$this->page) {
                $this->page = new SuperwikiPage();
                $this->page['name'] = Request::get("name");
                $this->page['seminar_id'] = $this->course_id;
            }
        }
        if ((!$this->page->isNew() && !$this->page->isEditable()) || ($this->page->isNew() && !$this->settings->haveCreatePermission())) {
            throw new AccessDeniedException("Keine Berechtigung.");
        }

        if (!$this->page->isNew()) {
            $statement = DBManager::get()->prepare("
                    INSERT INTO superwiki_editors
                    SET user_id = :me,
                        page_id = :page_id,
                        online = UNIX_TIMESTAMP(),
                        latest_change = '0'
                    ON DUPLICATE KEY UPDATE
                        online = UNIX_TIMESTAMP()
                ");
            $statement->execute(array(
                'me' => $GLOBALS['user']->id,
                'page_id' => $page_id
            ));
        }


        if (Request::isPost()
                && (!$this->page->isNew() || $this->settings->haveCreatePermission())
                && ($this->page->isNew() || $this->page->isEditable())) {
            $this->page['content'] = trim(Request::get("content"));
            if (!$this->page['content']) {
                $this->page['content'] = null;
            }
            if ($this->page->isNew()) {
                $this->page['name'] = Request::get("name");
                $this->page['seminar_id'] = $this->course_id;
            }
            $success = $this->page->store();
            if (count(SuperwikiPage::findAll($this->course_id)) === 1) {
                $this->settings['indexpage'] = $this->page->getId();
                $this->settings->store();
            }
            if ($success > 0 && $success !== false) {
                PageLayout::postMessage(MessageBox::success(_("Seite gespeichert.")));
            } elseif($success === false) {
                PageLayout::postMessage(MessageBox::error(_("Ein Fehler ist aufgetreten.")));
            }
        }
        $this->redirect("page/view/".$this->page->getId());
    }

    public function rename_action($page_id)
    {
        $this->page = new SuperwikiPage($page_id);
        if ($this->page['seminar_id'] !== $this->course_id) {
            throw new AccessDeniedException("Not in right course");
        }
        $this->settings = new SuperwikiSettings($this->page['seminar_id']);
        if (!$this->settings->haveRenamePermission()) {
            throw new AccessDeniedException("You have not enough permission.");
        }
        if (Request::isPost() && Request::get("new_name")) {
            $this->page['name'] = Request::get("new_name");
            $this->page->store();
            PageLayout::postMessage(MessageBox::success(_("Seite wurde umbenannt.")));
            $this->redirect("page/view/".$this->page->getId());
        }
    }

    public function admin_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $this->course_id)) {
            throw new AccessDeniedException();
        }
        PageLayout::setTitle(sprintf(_("%s Einstellungen"), Config::get()->SUPERWIKI_NAME));
        if (Request::isPost()) {
            $this->settings['name'] = Request::get("name");
            $this->settings['indexpage'] = Request::get("indexpage");
            $this->settings['icon'] = Request::get("icon", "wiki");
            $this->settings['link_icon'] = Request::get("link_icon", "wiki");
            $this->settings['create_permission'] = Request::get("create_permission");
            $this->settings['rename_permission'] = Request::get("rename_permission");
            $this->settings->store();
            PageLayout::postMessage(MessageBox::success(_("Daten wurden gespeichert.")));
            $this->redirect("page/view/".Request::option("page_id"));
        }
    }

    public function permissions_action($page_id)
    {
        $this->page = new SuperwikiPage($page_id);
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $this->page['seminar_id'])) {
            throw new AccessDeniedException("Keine Berechtigung.");
        }
        PageLayout::setTitle(_("Seiteneinstellungen ".$this->page['name']));
        if (Request::isPost()) {
            $this->page['read_permission'] = Request::get('read_permission');
            $this->page['write_permission'] = Request::get('write_permission');
            $this->page->store();
            PageLayout::postMessage(MessageBox::success(_("Seiteneinstellungen bearbeitet.")));
            $this->redirect("page/view/".$page_id);
        }
        $this->statusgruppen = Statusgruppen::findBySeminar_id($this->page['seminar_id']);
    }

    public function timeline_action($page_id)
    {
        $this->page = new SuperwikiPage($page_id);
        if (!$this->page->isReadable()) {
            throw new AccessDeniedException("Keine Berechtigung.");
        }
        if (Request::isPost() && Request::option("version_id")) {
            if (!$this->page->isEditable()) {
                throw new AccessDeniedException("Keine Berechtigung.");
            }
            $version = new SuperwikiVersion(Request::option("version_id"));
            if ($version['page_id'] === $page_id) {
                $this->page['content'] = $version['content'];
                $this->page['last_author'] = $GLOBALS['user']->id;
                $this->page->store();
                PageLayout::postMessage(MessageBox::success(_("Alte Version der Seite wiederhergestellt.")));
                $this->redirect("page/view/".$page_id);
            }
        }
    }

    /**
     * Displays the changes of this wiki-page.
     * @param $page_id
     * @throws AccessDeniedException if page is not readable
     */
    public function changes_action($page_id)
    {
        $this->page = new SuperwikiPage($page_id);
        if (!$this->page->isReadable()) {
            throw new AccessDeniedException(                                                                                                                                                                                                                                                                                                 "Keine Berechtigung.");
        }
    }

    /**
     * Saves given files (dragged into the textarea) and returns the link to the
     * file to the user as json.
     * @throws AccessDeniedException
     */
    public function post_files_action() {
        if (!Request::isPost()
            || !$GLOBALS['perm']->have_studip_perm("autor", $this->course_id)) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        //check folders
        $db = DBManager::get();
        $folder_id = md5("Superwiki_".$this->course_id."_".$GLOBALS['user']->id);
        $parent_folder_id = md5("Superwiki_".$this->course_id);
        $folder = $db->query("
            SELECT *
            FROM folders
            WHERE id = ".$db->quote($folder_id)."
        ")->fetch(PDO::FETCH_COLUMN, 0);
        if (!$folder) {
            $folder = $db->query("
                SELECT *
                FROM folders
                WHERE id = ".$db->quote($parent_folder_id)."
            ")->fetch(PDO::FETCH_COLUMN, 0);
            if (!$folder) {
                $db->exec( "
                    INSERT IGNORE INTO folders
                    SET id = ".$db->quote($parent_folder_id).",
                        parent_id = '',
                        range_id = ".$db->quote($this->course_id).",
                        range_type = 'course',
                        folder_type = 'StandardFolder',
                        user_id = ".$db->quote($GLOBALS['user']->id).",
                        name = ".$db->quote("SuperwikiDateien").",
                        data_content = '[]',
                        description = ".$db->quote(_('Dateien des Superwikis')).",
                        mkdate = ".$db->quote(time()).",
                        chdate = ".$db->quote(time())."
                    ");
            }
            $db->exec("
                INSERT IGNORE INTO folders
                SET id = ".$db->quote($folder_id).",
                    parent_id = ".$db->quote($parent_folder_id).",
                    range_id = ".$db->quote($this->course_id).",
                    range_type = 'course',
                    folder_type = 'StandardFolder',
                    user_id = ".$db->quote($GLOBALS['user']->id).",
                    name = ".$db->quote(get_fullname()).",
                    data_content = '[]',
                    description = ".$db->quote(_('Dateien des Nutzers')).",
                    mkdate = ".$db->quote(time()).",
                    chdate = ".$db->quote(time())."
            ");
        }

        $folder = Folder::find($folder_id)->getTypedFolder();

        $output = array();

        foreach ($_FILES as $file) {
            $standardfile = StandardFile::create($file);
            $error = $folder->validateUpload($standardfile, $GLOBALS['user']->id);
            if ($error != null) {
                $output['errors'][] = $file['name'] . ': ' . $error;
                continue;
            }
            if ($standardfile->getSize()) {
                $standardfile = $folder->addFile($standardfile);

                if (!$standardfile instanceof FileType) {
                    $error_message = _('Die hochgeladene Datei kann nicht verarbeitet werden!');

                    if ($standardfile instanceof MessageBox) {
                        $error_message .= ' ' . $standardfile->message;
                    }
                    PageLayout::postError($error_message);
                } else {
                    $type = null;
                    strpos($standardfile->getMimeType(), 'image') === false || $type = "img";
                    strpos($standardfile->getMimeType(), 'video') === false || $type = "video";
                    if (strpos($standardfile->getMimeType(), 'audio') !== false || strpos($standardfile->getFilename(), '.ogg') !== false) {
                        $type = "audio";
                    }
                    if ($type) {
                        $output['inserts'][] = "[".$type."]".$standardfile->getDownloadURL();
                    } else {
                        $output['inserts'][] = "[".$standardfile->getFilename()."]".$standardfile->getDownloadURL();
                    }
                }
            }

        }
        $this->render_json($output);
    }

    public function check_new_page_name_action() {
        $output = array();
        if (!$GLOBALS['perm']->have_studip_perm("user", Request::option("seminar_id"))) {
            throw new AccessDeniedException("kein Zugriff");
        }
        $page = SuperwikiPage::findOneBySQL("seminar_id = ? and name = ?", array(Request::option("seminar_id"), Request::get("name")));
        if ($page) {
            if (!$page->isReadable()) {
                $output['error'] = _("Es gibt bereits eine versteckte Wikiseite. Sie dürfen diese weder sehen noch bearbeiten. Suchen Sie sich einen anderen Namen aus.");
            } elseif(!$page->isEditable()) {
                $output['error'] = _("Es gibt diese Wikiseite bereits, aber Sie dürfen sie nicht bearbeiten. Suchen Sie sich einen anderen Namen aus.");
            } else {
                $output['error'] = _("Diese Wikiseite gibt es bereits. Bearbeiten Sie diese doch, anstatt eine neue zu erstellen.");
            }
        }

        $this->render_json($output);
    }

}
