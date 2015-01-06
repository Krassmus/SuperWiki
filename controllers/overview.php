<?php

require_once 'app/controllers/plugin_controller.php';

class OverviewController extends PluginController {

    protected $allow_nobody = false;

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->settings = new SuperwikiSettings($_SESSION['SessionSeminar']);
        Navigation::activateItem("/course/superwiki/all");
        Navigation::getItem("/course/superwiki")->setImage(Assets::image_path("icons/16/black/".$this->settings['icon']));
        PageLayout::setTitle($GLOBALS['SessSemName']["header_line"]." - ".$this->settings['name']);
    }

    public function all_action()
    {
        $this->pages = SuperwikiPage::findAll($_SESSION['SessionSeminar']);
    }


}