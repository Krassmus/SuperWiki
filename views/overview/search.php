<? if (count($pages)) : ?>
<table class="default">
    <caption>
        <?= _("Suchergebnisse") ?>
    </caption>
    <head>
        <tr>
            <th><?= _("Seitenname") ?></th>
            <th><?= _("Auszug") ?></th>
            <th></th>
        </tr>
    </head>
    <tbody>
    <? foreach ($pages as $page) : ?>
        <? if ($page->isReadable()) : ?>
            <tr>
                <td>
                    <a href="<?= PluginEngine::getLink($plugin, array(), "page/view/".$page->getId()) ?>">
                        <?
                        $content = htmlReady($page['name']);
                        $content = preg_replace(
                            "/(".preg_quote(Request::get("search"), "/").")/i",
                            '<strong>$1</strong>',
                            $content
                        );
                        echo $content
                        ?>
                    </a>
                </td>
                <td>
                    <?
                    $content = strip_tags(formatReady($page['content']));
                    $pos = stripos($content, Request::get("search")) ?: 0;
                    $start = $pos > 50 ? $pos - 50 : 0;
                    $end = strlen($content) > $pos + 200 ? $pos + 200 : strlen($content);
                    $content = substr($content, $start, $end - $start);
                    $content = preg_replace(
                            "/(".preg_quote(Request::get("search"), "/").")/i",
                        '<strong>$1</strong>',
                        $content
                    );
                    echo $content
                    ?>
                </td>
                <td class="actions">
                    <? if (!Request::get("cms_id")) : ?>
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
                    <? endif ?>
                </td>
            </tr>
        <? endif ?>
    <? endforeach ?>
    </tbody>
</table>
<? else : ?>
    <? PageLayout::postMessage(MessageBox::info(_("Die Suche ergab keinen Treffer"))) ?>
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

