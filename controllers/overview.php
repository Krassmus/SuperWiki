<?php

require_once 'app/controllers/plugin_controller.php';

class OverviewController extends PluginController {

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
                Navigation::addItem($this->cms['navigation']."/superwiki_subtab", new Navigation($this->cms['title'], Navigation::getItem($this->cms['navigation'])->getURL()));
            }
            if (count($navigation === 2)) {
                $navigation[] = "superwiki_subsubtab";
                Navigation::addItem($this->cms['navigation']."/superwiki_subtab/superwiki_subsubtab", new Navigation($this->cms['title'], Navigation::getItem($this->cms['navigation'])->getURL()));
                Navigation::addItem($this->cms['navigation']."/superwiki_subtab/superwiki_all", new Navigation(_("Alle Seiten"), PluginEngine::getURL($this->plugin, array('cms_id' => $this->cms->getId()), "overview/all")));
            }
            $navigation = "/".implode("/", $navigation);
            Navigation::activateItem($navigation);
        } else {
            $this->course_id = Context::getId();
            $this->settings = new SuperwikiSettings($this->course_id);
            Navigation::activateItem("/course/superwiki/all");
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

        if ($GLOBALS['perm']->have_perm("root")) {
            Helpbar::Get()->addLink(_("PHP-Test"), URLHelper::getURL("plugins_packages/RasmusFuhse/SuperWiki/vendor/Textmerger/test/php.php"), null, "_blank");
            Helpbar::Get()->addLink(_("JS-Test"), URLHelper::getURL("plugins_packages/RasmusFuhse/SuperWiki/vendor/Textmerger/test/js.html"), null, "_blank");
        }
    }

    public function all_action()
    {
        if ($this->cms) {
            if (!$this->cms['active']) {
                throw new AccessDeniedException();
            }
            PageLayout::setTitle($this->cms['title'].": ".$this->page['name']);
            $this->pages = SuperwikiPage::findAll($this->cms['seminar_id']);
        } else {
            $this->pages = SuperwikiPage::findAll($this->course_id);
        }
    }

    public function latest_changes_action()
    {
        if (Request::int("since")) {
            $course_id = class_exists("Context") ? Context::getId() : $_SESSION['SessionSeminar'];
            $this->pages = SuperwikiPage::findBySql("seminar_id = ? AND chdate > ? ORDER BY chdate DESC", array(
                $course_id,
                Request::int("since")
            ));
        }
    }

    public function search_action()
    {
        if ($this->cms) {
            if (!$this->cms['active']) {
                throw new AccessDeniedException();
            }
            $this->course_id = $this->cms['seminar_id'];
        }
        if (!Request::get("search")) {
            $this->redirect("overview/all");
        }
        $this->pages = SuperwikiPage::findBySQL("
            seminar_id = :course_id
            AND (name LIKE :search OR content LIKE :search)
            ORDER BY CASE WHEN name LIKE :search THEN 1 WHEN content LIKE :search THEN 2 END
        ", array(
            'course_id' => $this->course_id,
            'search' => "%".Request::get("search")."%"
        ));
    }

}