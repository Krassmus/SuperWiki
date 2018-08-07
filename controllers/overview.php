<?php

require_once 'app/controllers/plugin_controller.php';

class OverviewController extends PluginController {

    protected $allow_nobody = false;

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->course_id = class_exists("Context") ? Context::getId() : $_SESSION['SessionSeminar'];
        $this->settings = new SuperwikiSettings($this->course_id);
        Navigation::activateItem("/course/superwiki/all");
        Navigation::getItem("/course/superwiki")->setImage(
            Icon::create(($this->settings['icon'] ?: "wiki"), "info_alt")
        );
        PageLayout::setTitle(Context::getHeaderLine()  . " - ".($this->settings && $this->settings['name'] ? $this->settings['name'] : Config::get()->SUPERWIKI_NAME));
        Helpbar::Get()->addLink(_("Wikilinks und Navigation"), "https://github.com/Krassmus/SuperWiki/wiki/Wikilinks-und-Navigation", null, "_blank");
        Helpbar::Get()->addLink(_("Unsichtbare Wikiseiten"), "https://github.com/Krassmus/SuperWiki/wiki/Unsichtbare-Wikiseiten", null, "_blank");
        Helpbar::Get()->addLink(sprintf(_("%s für Gruppenaufgaben"), Config::get()->SUPERWIKI_NAME), "https://github.com/Krassmus/SuperWiki/wiki/SuperWiki-f%C3%BCr-Gruppenaufgaben", null, "_blank");
        //Helpbar::Get()->addLink(_("Superwiki für Lernorganisation"), "https://github.com/Krassmus/SuperWiki/wiki/Wikilinks-und-Navigation", null, "_blank");
        Helpbar::Get()->addLink(sprintf(_("Präsentationen mit %s"), Config::get()->SUPERWIKI_NAME), "https://github.com/Krassmus/SuperWiki/wiki/Pr%C3%A4sentationen-mit-SuperWiki", null, "_blank");

        if ($GLOBALS['perm']->have_perm("root")) {
            Helpbar::Get()->addLink(_("PHP-Test"), URLHelper::getURL("plugins_packages/RasmusFuhse/SuperWiki/vendor/Textmerger/test/php.php"), null, "_blank");
            Helpbar::Get()->addLink(_("JS-Test"), URLHelper::getURL("plugins_packages/RasmusFuhse/SuperWiki/vendor/Textmerger/test/js.html"), null, "_blank");
        }
    }

    public function all_action()
    {
        $this->pages = SuperwikiPage::findAll($this->course_id);
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