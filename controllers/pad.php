<?php

require_once 'app/controllers/plugin_controller.php';

class PadController extends PluginController {

    function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        Navigation::activateItem("/course/superwiki");
    }

    public function site_action()
    {
        $name = Request::get("s", "intro");
        $this->page = WikiPage::findByName($name, $_SESSION['SessionSeminar']);
    }

    public function edit_action() {
        $name = Request::get("s", "intro");
    }
}