<ol class="clean">
    <? $new_version = $page['content'] ?>
    <? if (count($page->versions) === 0) : ?>
        <li>
            <?= _("Seite wurde angelegt.") ?>
        </li>
    <? else : ?>
        <? foreach ($page->versions as $version) : ?>
        <li>
            <div class="header"></div>
            <? $changes = TextMerger::get()->_getReplacements($version['content'], $new_version) ?>
            <? foreach ($changes as $change) : ?>
                <? $start = max($change['start'] - 10, 0) ?>
                <? $start = substr($new_version, $start, $change['start'] - $start) ?>
                <? $end = min($change['end'] + 10, strlen($new_version) - 1) ?>
                <? $end = substr($new_version, $change['end'], $end - $change['start']) ?>
                <div class="after"><span class="start"><?= htmlReady($start) ?></span><span class=""><?= htmlReady($change['text']) ?></span><span class="end"><?= htmlReady($end) ?></span></div>
                <!--
                <div class="before"><span class="start"><?= htmlReady($start) ?></span><span class=""><?= htmlReady(substr($new_version, $change['start'], $change['end'] - $change['start'])) ?></span><span class="end"><?= htmlReady($change['end']) ?></span></div>
                -->
            <? endforeach ?>
            </li>
        <? endforeach ?>
    <? endif ?>
</ol>


<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/wiki-sidebar.png');

$actions = new ActionsWidget();
if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
    $actions->addLink(_("Wiki-Einstellungen"), PluginEngine::getURL($plugin, array(), "page/admin"), "icons/16/blue/admin", array('data-dialog' => "true"));
    if (!$page->isNew()) {
        $actions->addLink(_("Seiten-Einstellungen"), PluginEngine::getURL($plugin, array(), "page/permissions/".$page->getId()), "icons/16/blue/roles", array('data-dialog' => "true"));
    }
}
if ($page->isEditable()) {
    $actions->addLink(_("Seite bearbeiten"), PluginEngine::getURL($plugin, array(), "page/edit/".$page->getId()), "icons/16/blue/edit");
}
if ($settings->haveCreatePermission()) {
    $actions->addLink(_("Neue Seite anlegen"), PluginEngine::getURL($plugin, array(), "page/edit"), "icons/16/blue/add");
}
$sidebar->addWidget($actions);

if (!$page->isNew()) {
    $views = new ViewsWidget();
    $views->addLink(_("Aktuelle Seite"), PluginEngine::getLink($plugin, array(), "page/view/".$page->getId()));
    $views->addLink(_("Autorenänderungen"), PluginEngine::getLink($plugin, array(), "page/changes/".$page->getId()))->setActive(true);
    $views->addLink(_("Historie"), PluginEngine::getLink($plugin, array(), "page/timeline/".$page->getId()));
    $sidebar->addWidget($views);
}
