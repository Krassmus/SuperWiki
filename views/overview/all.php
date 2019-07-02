<? if (count($pages)) : ?>
<table class="default">
    <head>
        <tr>
            <th></th>
            <th><?= _("Seitenname") ?></th>
            <th><?= _("Letzte Änderung") ?></th>
            <th></th>
        </tr>
    </head>
    <tbody>
    <? foreach ($pages as $page) : ?>
        <? if ($page->isReadable()) : ?>
            <tr>
                <td style="width: 20px;">
                    <? if ($page->getId() === $page->settings['indexpage']) : ?>
                        <?= Icon::create("arr_2right", "info")->asImg(20, array('class' => "text-bottom", 'title' => _("Startseite"))) ?>
                    <? endif ?>
                </td>
                <td>
                    <a href="<?= PluginEngine::getLink($plugin, array(), "page/view/".$page->getId()) ?>">
                        <?= htmlReady($page['name']) ?>
                    </a>
                </td>
                <td>
                    <?= date("d.m.Y", $page['chdate']) ?>
                </td>
                <td class="actions">
                    <? if ($GLOBALS['perm']->have_studip_perm("tutor", $course_id) && $page['write_permission'] !== "all") : ?>
                        <?= Icon::create("lock-locked", "clickable")->asImg(20, array('class' => "text-bottom", 'title' => _("Seite ist schreibgeschützt."))) ?>
                    <? endif ?>
                    <? if ($GLOBALS['perm']->have_studip_perm("tutor", $course_id) && $page['read_permission'] !== "all") : ?>
                        <?= Icon::create("visibility-invisible", "clickable")->asImg(20, array('class' => "text-bottom", 'title' => _("Seite ist lesegeschützt."))) ?>
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

if (!$cms) {
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
}

