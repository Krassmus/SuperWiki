<?php

require_once 'app/controllers/plugin_controller.php';

class PageController extends PluginController {

    protected $allow_nobody = false;

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        Navigation::activateItem("/course/superwiki");
        Navigation::getItem("/course/superwiki")->setImage(Assets::image_path("icons/16/black/wiki"));
        $this->settings = new SuperwikiSettings($_SESSION['SessionSeminar']);
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
        if (!$this->page->isReadable()) {
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
            $this->page->store();
            if (count(SuperwikiPage::findAll($_SESSION['SessionSeminar'])) === 1) {
                $this->settings['indexpage'] = $this->page->getId();
                $this->settings->store();
            }
            PageLayout::postMessage(MessageBox::success(_("Seite gespeichert.")));
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
            $this->settings['create_permission'] = Request::get("create_permission");
            $this->settings->store();
            PageLayout::postMessage(MessageBox::success(_("Daten wurden gespeichert")));
            $this->redirect("superwiki/page/view/".Request::option("page_id"));
        }
    }

    public function timeline_action($page_id)
    {
        $this->page = new SuperwikiPage($page_id);
        if (!$this->page->isReadable()) {
            throw new AccessDeniedException("Keine Berechtigung.");
        }
    }
}