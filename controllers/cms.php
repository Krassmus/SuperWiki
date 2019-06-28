<?php

class CmsController extends PluginController
{
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!$GLOBALS['perm']->have_perm("root")) {
            throw new AccessDeniedException();
        }
    }

    public function overview_action()
    {
        Navigation::activateItem("/admin/locations/superwikicms");
        PageLayout::setTitle(_("Content-Management über Superwiki"));
        $this->cms = SuperwikiCMS::findBySQL("1 ORDER BY title ASC");
    }

    public function edit_action($cms_id = null)
    {
        $this->cms = new SuperwikiCMS($cms_id);
    }

    public function save_action($cms_id = null)
    {
        $this->cms = new SuperwikiCMS($cms_id);
        if (Request::isPost()) {
            $this->cms->setData(Request::getArray("data"));
            $this->cms->store();
        }
        $this->redirect("cms/overview");
    }
}