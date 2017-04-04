<?php

require_once 'app/controllers/plugin_controller.php';

class OverviewController extends PluginController {

    protected $allow_nobody = false;

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->settings = new SuperwikiSettings($_SESSION['SessionSeminar']);
        Navigation::activateItem("/course/superwiki/all");
        Navigation::getItem("/course/superwiki")->setImage(
            version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=")
                ? Icon::create(($this->settings['icon'] ?: "wiki"), "info")
                : Assets::image_path("icons/16/black/".($this->settings['icon'] ?: "wiki"))
        );
        PageLayout::setTitle($GLOBALS['SessSemName']["header_line"]." - ".$this->settings['name']);
        Helpbar::Get()->addLink(_("Wikilinks und Navigation"), "https://github.com/Krassmus/SuperWiki/wiki/Wikilinks-und-Navigation", null, "_blank");
        Helpbar::Get()->addLink(_("Unsichtbare Wikiseiten"), "https://github.com/Krassmus/SuperWiki/wiki/Unsichtbare-Wikiseiten", null, "_blank");
        Helpbar::Get()->addLink(_("SuperWiki für Gruppenaufgaben"), "https://github.com/Krassmus/SuperWiki/wiki/SuperWiki-f%C3%BCr-Gruppenaufgaben", null, "_blank");
        //Helpbar::Get()->addLink(_("Superwiki für Lernorganisation"), "https://github.com/Krassmus/SuperWiki/wiki/Wikilinks-und-Navigation", null, "_blank");
        Helpbar::Get()->addLink(_("Präsentationen mit SuperWiki"), "https://github.com/Krassmus/SuperWiki/wiki/Pr%C3%A4sentationen-mit-SuperWiki", null, "_blank");
    }

    public function all_action()
    {
        $this->pages = SuperwikiPage::findAll($_SESSION['SessionSeminar']);
    }

    public function latest_changes_action()
    {
        if (Request::int("since")) {
            $this->pages = SuperwikiPage::findBySql("seminar_id = ? AND chdate > ? ORDER BY chdate DESC", array($_SESSION['SessionSeminar'], Request::int("since")));
        }
    }

}