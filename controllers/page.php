<?php

require_once 'app/controllers/plugin_controller.php';

class PageController extends PluginController {

    protected $allow_nobody = false;

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->settings = new SuperwikiSettings($_SESSION['SessionSeminar']);
        Navigation::activateItem("/course/superwiki/wiki");
        Navigation::getItem("/course/superwiki")->setImage(Assets::image_path("icons/16/black/".$this->settings['icon']));
        PageLayout::addScript($this->plugin->getPluginURL()."/vendor/TextMerger/TextMerger.js");
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/superwiki.js");
        PageLayout::setTitle($GLOBALS['SessSemName']["header_line"]." - ".$this->settings['name']);
    }

    public function view_action($page_id = null)
    {
        if ($page_id) {
            $this->page = new SuperwikiPage($page_id);
            $history = $_SESSION['SuperWiki_History'][$_SESSION['SessionSeminar']];
            if ($history[count($history) - 1] !== $page_id) {
                $history[] = $page_id;
                if (count($history) > 6) {
                    array_shift($history);
                }
            }
            $_SESSION['SuperWiki_History'][$_SESSION['SessionSeminar']] = $history;
        } else {
            $this->page = new SuperwikiPage($this->settings['indexpage'] ?: null);
        }
        if (!$this->page->isNew() && !$this->page->isReadable()) {
            throw new AccessDeniedException("Keine Berechtigung.");
        }
    }

    public function edit_action($page_id = null)
    {
        if ($page_id) {
            $this->page = new SuperwikiPage($page_id);
        } else {
            $this->page = SuperwikiPage::findByName(Request::get("name"), $_SESSION['SessionSeminar']);
            if (!$this->page) {
                $this->page = new SuperwikiPage();
            }
        }
        if ((!$this->page->isNew() && !$this->page->isEditable()) || ($this->page->isNew() && !$this->settings->haveCreatePermission())) {
            throw new AccessDeniedException("Keine Berechtigung.");
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
                $this->page['seminar_id'] = $_SESSION['SessionSeminar'];
            }
            $this->page['last_author'] = $GLOBALS['user']->id;
            $success = $this->page->store();
            if (count(SuperwikiPage::findAll($_SESSION['SessionSeminar'])) === 1) {
                $this->settings['indexpage'] = $this->page->getId();
                $this->settings->store();
            }
            if ($success > 0 && $success !== false) {
                PageLayout::postMessage(MessageBox::success(_("Seite gespeichert.")));
            } elseif($success === false) {
                PageLayout::postMessage(MessageBox::error(_("Ein Fehler ist aufgetreten.")));
            }
            $this->redirect("superwiki/page/view/".$this->page->getId());
        }
    }

    public function admin_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException();
        }
        PageLayout::setTitle(_("SuperWiki Einstellungen"));
        if (Request::isPost()) {
            $this->settings['name'] = Request::get("name");
            $this->settings['indexpage'] = Request::get("indexpage");
            $this->settings['icon'] = Request::get("icon", "wiki");
            $this->settings['create_permission'] = Request::get("create_permission");
            $this->settings->store();
            PageLayout::postMessage(MessageBox::success(_("Daten wurden gespeichert")));
            $this->redirect("superwiki/page/view/".Request::option("page_id"));
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
            $this->redirect("superwiki/page/view/".$page_id);
        }
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
                $this->redirect("superwiki/page/view/".$page_id);
            }
        }
    }

    /**
     * Saves given files (dragged into the textarea) and returns the link to the
     * file to the user as json.
     * @throws AccessDeniedException
     */
    public function post_files_action() {
        if (!Request::isPost()
            || !$GLOBALS['perm']->have_studip_perm("autor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        //check folders
        $db = DBManager::get();
        $folder_id = md5("Superwiki_".$_SESSION['SessionSeminar']."_".$GLOBALS['user']->id);
        $parent_folder_id = md5("Superwiki_".$_SESSION['SessionSeminar']);
        $folder_id = $parent_folder_id;
        $folder = $db->query(
            "SELECT * " .
            "FROM folder " .
            "WHERE folder_id = ".$db->quote($folder_id)." " .
            "")->fetch(PDO::FETCH_COLUMN, 0);
        if (!$folder) {
            $folder = $db->query(
                "SELECT * " .
                "FROM folder " .
                "WHERE folder_id = ".$db->quote($parent_folder_id)." " .
                "")->fetch(PDO::FETCH_COLUMN, 0);
            if (!$folder) {
                $db->exec(
                    "INSERT IGNORE INTO folder " .
                    "SET folder_id = ".$db->quote($parent_folder_id).", " .
                        "range_id = ".$db->quote($_SESSION['SessionSeminar']).", " .
                        "seminar_id = ".$db->quote($context).", " .
                        "user_id = ".$db->quote($GLOBALS['user']->id).", " .
                        "name = ".$db->quote("SuperwikiDateien").", " .
                        "permission = '7', " .
                        "mkdate = ".$db->quote(time()).", " .
                        "chdate = ".$db->quote(time())." " .
                        "");
            }
            $db->exec(
                "INSERT IGNORE INTO folder " .
                "SET folder_id = ".$db->quote($folder_id).", " .
                    "range_id = ".$db->quote($parent_folder_id).", " .
                    "seminar_id = ".$db->quote($_SESSION['SessionSeminar']).", " .
                    "user_id = ".$db->quote($GLOBALS['user']->id).", " .
                    "name = ".$db->quote(get_fullname()).", " .
                    "permission = '7', " .
                    "mkdate = ".$db->quote(time()).", " .
                    "chdate = ".$db->quote(time())." " .
            "");
        }

        $output = array();

        foreach ($_FILES as $file) {
            $GLOBALS['msg'] = '';
            validate_upload($file);
            if ($GLOBALS['msg']) {
                $output['errors'][] = $file['name'] . ': ' . decodeHTML(trim(substr($GLOBALS['msg'],6), '§'));
                continue;
            }
            if ($file['size']) {
                $document['name'] = $document['filename'] = studip_utf8decode(strtolower($file['name']));
                $document['user_id'] = $GLOBALS['user']->id;
                $document['author_name'] = get_fullname();
                $document['seminar_id'] = $_SESSION['SessionSeminar'];
                $document['range_id'] = $folder_id;
                $document['filesize'] = $file['size'];

                $newfile = StudipDocument::createWithFile($file['tmp_name'], $document);
                $success = (bool)$newfile;

                if ($success) {
                    $url = GetDownloadLink($newfile->getId(), $newfile['filename']);
                    $type = null;
                    strpos($file['type'], 'image') === false || $type = "img";
                    strpos($file['type'], 'video') === false || $type = "video";
                    if (strpos($file['type'], 'audio') !== false || strpos($document['filename'], '.ogg') !== false) {
                        $type = "audio";
                    }
                    if ($type) {
                        $output['inserts'][] = "[".$type."]".$url;
                    } else {
                        $output['inserts'][] = "[".$document['filename']."]".$url;
                    }
                }
            }
        }
        $this->render_json($output);
    }
}