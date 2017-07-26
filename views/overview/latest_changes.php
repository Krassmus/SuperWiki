<? if (count($pages)) : ?>
<table class="default">
    <caption>
        <?= _("Letzte Änderungen") ?>
    </caption>
    <thead>
        <tr>
            <th><?= _("Seite") ?></th>
            <th><?= _("Letzter Autor") ?></th>
            <th><?= _("Letzte Änderung") ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($pages as $page) : ?>
        <? if ($page->isReadable()) : ?>
            <tr>
                <td>
                    <a href="<?= PluginEngine::getLink($plugin, array(), "page/view/".$page->getId()) ?>">
                        <?= htmlReady($page['name']) ?>
                    </a>
                </td>
                <td>
                    <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => get_username($page['last_author']))) ?>">
                        <?= Avatar::getAvatar($page['last_author'])->getImageTag(Avatar::SMALL) ?>
                        <?= htmlReady(get_fullname($page['last_author'])) ?>
                    </a>
                </td>
                <td>
                    <?= date("G:i d.n.Y", $page['chdate']) ?>
                </td>
                <td>
                    <? if ($GLOBALS['perm']->have_studip_perm("tutor", $course_id) && $page['write_permission'] !== "all") : ?>
                        <?= Assets::img("icons/20/black/lock-locked", array('class' => "text-bottom", 'title' => _("Seite ist schreibgeschützt."))) ?>
                    <? endif ?>
                    <? if ($GLOBALS['perm']->have_studip_perm("tutor", $course_id) && $page['read_permission'] !== "all") : ?>
                        <?= Assets::img("icons/20/black/visibility-invisible", array('class' => "text-bottom", 'title' => _("Seite ist lesegeschützt."))) ?>
                    <? endif ?>
                    <? if ($page->isEditable()) : ?>
                        <a href="<?= PluginEngine::getLink($plugin, array(), "page/edit/".$page->getId()) ?>">
                            <?= Assets::img("icons/20/blue/edit", array('class' => "text-bottom")) ?>
                        </a>
                    <? endif ?>
                </td>
            </tr>
        <? endif ?>
    <? endforeach ?>
    </tbody>
</table>
<? else : ?>
    <? PageLayout::postMessage(MessageBox::info(_("Noch keine Seiten vorhanden."))) ?>
<? endif ?>

<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/wiki-sidebar.png');

$actions = new ActionsWidget();
if ($GLOBALS['perm']->have_studip_perm("tutor", $course_id)) {
    $actions->addLink(
        _("Wiki-Einstellungen"),
        PluginEngine::getURL($plugin, array(), "page/admin"),
        version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=") ? Icon::create("admin", "clickable") : "icons/16/blue/admin",
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


