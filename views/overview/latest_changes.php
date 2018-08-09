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
                        <?= Icon::create("lock-locked", "info")->asImg(20, array('class' => "text-bottom", 'title' => _("Seite ist schreibgeschützt."))) ?>
                    <? endif ?>
                    <? if ($GLOBALS['perm']->have_studip_perm("tutor", $course_id) && $page['read_permission'] !== "all") : ?>
                        <?= Icon::create("visibility-invisible", "info")->asImg(20, array('class' => "text-bottom", 'title' => _("Seite ist lesegeschützt."))) ?>
                    <? endif ?>
                    <? if ($page->isEditable()) : ?>
                        <a href="<?= PluginEngine::getLink($plugin, array(), "page/edit/".$page->getId()) ?>">
                            <?= Icon::create("edit", "clickable")->asImg(20, array('class' => "text-bottom")) ?>
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

$search = new SearchWidget(PluginEngine::getURL($plugin, array(), "overview/search"));
$search->addNeedle(
    _("Suche nach ..."),
    "search",
    true
);
$sidebar->addWidget($search);

$actions = new ActionsWidget();
if ($GLOBALS['perm']->have_studip_perm("tutor", $course_id)) {
    $actions->addLink(
        _("Wiki-Einstellungen"),
        PluginEngine::getURL($plugin, array(), "page/admin"),
        Icon::create("admin", "clickable"),
        array('data-dialog' => "true")
    );
}
if ($settings->haveCreatePermission()) {
    $actions->addLink(
        _("Neue Seite anlegen"),
        PluginEngine::getURL($plugin, array(), "page/edit"),
        Icon::create("add", "clickable")
    );
}
$sidebar->addWidget($actions);


