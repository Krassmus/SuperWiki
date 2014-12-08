<ul>
    <? foreach ($pages as $page) : ?>
        <li>
            <a href="<?= PluginEngine::getLink($plugin, array(), "page/view/".$page->getId()) ?>">
                <?= htmlReady($page['name']) ?>
            </a>
        </li>
    <? endforeach ?>
</ul>

<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/wiki-sidebar.png');

$actions = new ActionsWidget();
if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
    $actions->addLink(_("Wiki-Einstellungen"), PluginEngine::getURL($plugin, array(), "page/admin"), "icons/16/blue/admin", array('data-dialog' => "true"));
}
if ($settings->haveCreatePermission()) {
    $actions->addLink(_("Neue Seite anlegen"), PluginEngine::getURL($plugin, array(), "page/edit"), "icons/16/blue/add");
}
$sidebar->addWidget($actions);


