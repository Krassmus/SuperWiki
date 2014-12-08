<?php

require_once 'app/controllers/plugin_controller.php';

class OverviewController extends PluginController {

    protected $allow_nobody = false;

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        Navigation::activateItem("/course/superwiki/all");
        Navigation::getItem("/course/superwiki")->setImage(Assets::image_path("icons/16/black/wiki"));
        $this->settings = new SuperwikiSettings($_SESSION['SessionSeminar']);
    }

    public function all_action()
    {
        $this->pages = SuperwikiPage::findAll($_SESSION['SessionSeminar']);
    }


}