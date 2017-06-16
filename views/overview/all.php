<? if (count($pages)) : ?>
<table class="default">
    <tbody>
    <? foreach ($pages as $page) : ?>
        <? if ($page->isReadable()) : ?>
            <tr>
                <td style="width: 20px;">
                    <? if ($page->getId() === $page->settings['indexpage']) : ?>
                        <?= Assets::img("icons/16/black/arr_2right", array('class' => "text-bottom", 'title' => _("Startseite"))) ?>
                    <? endif ?>
                </td>
                <td>
                    <a href="<?= PluginEngine::getLink($plugin, array(), "page/view/".$page->getId()) ?>">
                        <?= htmlReady($page['name']) ?>
                    </a>
                </td>
                <td>
                    <? if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar']) && $page['write_permission'] !== "all") : ?>
                        <?= Assets::img("icons/20/black/lock-locked", array('class' => "text-bottom", 'title' => _("Seite ist schreibgeschützt."))) ?>
                    <? endif ?>
                    <? if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar']) && $page['read_permission'] !== "all") : ?>
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
if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
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


