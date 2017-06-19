<table class="default nohover changes">
    <thead>
        <tr>
            <th></th>
            <th><?= _("Änderung") ?></th>
            <th><?= _("Datum") ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($page->versions as $version) : ?>
        <tr>
            <td>
                <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => get_username($version['last_author']))) ?>" title="<?= htmlReady(get_fullname($version['last_author'])) ?>">
                    <?= Avatar::getAvatar($version['last_author'])->getImageTag(Avatar::SMALL) ?>
                </a>
            </td>
            <td>
                <? $changes = Textmerger::get()->getReplacements($version['content'], $new_version, $version['content']) ?>
                <? foreach ($changes as $change) : ?>
                    <? if (($change->start !== $change->end) || ($change->text !== "")) : ?>
                        <? $start = max($change->start - 10, 0) ?>
                        <? $start = substr($new_version, $start, $change->start - $start) ?>
                        <? $end = min($change->end + 10, strlen($new_version) - 1) ?>
                        <? $end = substr($new_version, $change->end, $end - $change->start) ?>
                        <div class="change">
                            <span class="start"><?= nl2br(htmlReady($start)) ?></span>
                            <span class="changedtext"><?= nl2br(htmlReady($change->text)) ?></span>
                            <span class="end"><?= nl2br(htmlReady($end)) ?></span>
                        </div>
                    <? endif ?>
                <? endforeach ?>
            </td>
            <td><?= date("G:i d.m.Y", $version['mkdate']) ?></td>
        </tr>
        <? $new_version = $version['content'] ?>
    <? endforeach ?>
    </tbody>
</table>

<style>
    .changes .start {
        color: #dddddd;
    }
    .changes .end {
        color: #dddddd;
    }
</style>

<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/wiki-sidebar.png');

$actions = new ActionsWidget();
if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
    $actions->addLink(
        _("Wiki-Einstellungen"),
        PluginEngine::getURL($plugin, array(), "page/admin"),
        version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=") ? Icon::create("admin", "clickable") : "icons/16/blue/admin",
        array('data-dialog' => "true")
    );
    if (!$page->isNew()) {
        $actions->addLink(
            _("Seiten-Einstellungen"),
            PluginEngine::getURL($plugin, array(), "page/permissions/".$page->getId()),
            version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=") ? Icon::create("roles", "clickable") : "icons/16/blue/roles",
            array('data-dialog' => "true")
        );
    }
}
if ($page->isEditable()) {
    $actions->addLink(
        _("Seite bearbeiten"),
        PluginEngine::getURL($plugin, array(), "page/edit/".$page->getId()),
        version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=") ? Icon::create("edit", "clickable") :  "icons/16/blue/edit"
    );
}
if (!$page->isNew() && $settings->haveRenamePermission()) {
    $actions->addLink(
        _("Seite umbenennen"),
        PluginEngine::getURL($plugin, array(), "page/rename/".$page->getId()),
        version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=") ? Icon::create("edit", "clickable") : "icons/16/blue/edit",
        array('data-dialog' => "true")
    );
}
if ($settings->haveCreatePermission()) {
    $actions->addLink(
        _("Neue Seite anlegen"),
        PluginEngine::getURL($plugin, array(), "page/edit"),
        version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=") ? Icon::create("add", "clickable") : "icons/16/blue/add");
}
$sidebar->addWidget($actions);

if (!$page->isNew()) {
    $views = new ViewsWidget();
    $views->addLink(_("Aktuelle Seite"), PluginEngine::getLink($plugin, array(), "page/view/".$page->getId()));
    $views->addLink(_("Autorenänderungen"), PluginEngine::getLink($plugin, array(), "page/changes/".$page->getId()))->setActive(true);
    $views->addLink(_("Historie"), PluginEngine::getLink($plugin, array(), "page/timeline/".$page->getId()));
    $sidebar->addWidget($views);
}
