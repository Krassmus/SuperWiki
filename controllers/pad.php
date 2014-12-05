<?php

require_once 'app/controllers/plugin_controller.php';

class PadController extends PluginController {

    protected $allow_nobody = false;

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        Navigation::activateItem("/course/superwiki");
        Navigation::getItem("/course/superwiki")->setImage(Assets::image_path("icons/16/black/wiki"));
    }

    public function site_action($page_id = null)
    {
        if ($page_id) {
            $this->page = new SuperwikiPage($page_id);
        } else {
            $this->page = new SuperwikiPage();
        }
    }

    public function edit_action($page_id = null)
    {
        if ($page_id) {
            $this->page = new SuperwikiPage($page_id);
        } else {
            $this->page = new SuperwikiPage();
        }
        if (!$this->page->isEditable()) {
            throw new AccessDeniedException();
        }
        if (Request::isPost()) {
            $this->page['content'] = trim(Request::get("content"));
            if (!$this->page['content']) {
                $this->page['content'] = null;
            }
            if ($this->page->isNew()) {
                $this->page['name'] = Request::get("name");
                $this->page['seminar_id'] = $_SESSION['SessionSeminar'];
            }
            $this->page->store();
            PageLayout::postMessage(MessageBox::success(_("Seite gespeichert.")));
            $this->redirect("superwiki/pad/site/".$this->page->getId());
        }
    }

    public function admin_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
            throw new AccessDeniedException();
        }
        $this->settings = new SuperwikiSettings($_SESSION['SessionSeminar']);
        PageLayout::setTitle(_("SuperWiki Einstellungen"));
        if (Request::isPost()) {
            $this->settings['name'] = Request::get("name");
            $this->settings['indexpage'] = Request::get("indexpage");
            $this->settings->store();
            PageLayout::postMessage(MessageBox::success(_("Daten wurden gespeichert")));
            $this->redirect("superwiki/pad/site/".Request::option("page_id"));
        }
    }
}