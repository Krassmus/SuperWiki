<? if (count((array) $_SESSION['SuperWiki_History'][$course_id]) > 1) : ?>
    <div id="superwiki_history" style="font-size: 0.8em;">
        <ol style="margin: 0px; list-style-type: none; padding: 0px; margin-bottom: 13px;">
            <? foreach ($_SESSION['SuperWiki_History'][$course_id] as $key => $page_id) : ?>
                <? if ($key < count($_SESSION['SuperWiki_History'][$course_id]) - 1) : ?>
                    <li style="display: inline-block; padding-left: 30px; background: 8px center url('<?= Assets::image_path("icons/grey/arr_2right.svg") ?>') no-repeat; background-size: 14px 14px;">
                        <a href="<?= PluginEngine::getLink($plugin, array(), "page/view/".$page_id) ?>">
                            <?= htmlReady(SuperwikiPage::find($page_id)->name) ?>
                        </a>
                    </li>
                <? endif ?>
            <? endforeach ?>
        </ol>
    </div>
<? endif ?>

<input type="hidden" id="seminar_id" value="<?= htmlReady($course_id) ?>">
<input type="hidden" id="page_id" value="<?= htmlReady($page->getId()) ?>">

<div class="full_wiki_page">
    <h1><?= htmlReady($page['name'] ?: "intro") ?></h1>
    <div class="superwiki_content" data-chdate="<?= htmlReady($page['chdate']) ?>" id="superwiki_page_content">
        <? if ($page->isNew()) : ?>
            <?= _("Dieses Wiki ist schon super. Aber leider trotzdem noch leer.") ?>
        <? else : ?>
            <?= $page->wikiFormat() ?>
        <? endif ?>
    </div>
</div>

<div id="superwiki_presentation"></div>

<? if (!$page->isNew()) : ?>
<script>
    STUDIP.SuperWiki = STUDIP.SuperWiki || {};
    STUDIP.SuperWiki.periodicalPushData = function () {
        return {
            'seminar_id': jQuery("#seminar_id").val(),
            'site': jQuery("#site").val(),
            'page_id': jQuery("#page_id").val(),
            'chdate': jQuery("#superwiki_page_content").data("chdate"),
            'mode': "read"
        };
    };
    STUDIP.SuperWiki.updatePage = function (data) {
        if (data.html && data.chdate) {
            jQuery("#superwiki_content").data("chdate", data.chdate)
            jQuery("#superwiki_page_content").html(data.html);
            STUDIP.Markup.element("#superwiki_page_content");
        }
    };
</script>
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
            version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=") ? Icon::create("admin", "clickable") : "icons/16/blue/admin",
            array('data-dialog' => "true")
        );
        if (!$page->isNew()) {
            $actions->addLink(
                _("Seiten-Einstellungen"),
                PluginEngine::getURL($plugin, array(), "page/permissions/" . $page->getId()),
                version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=") ? Icon::create("roles", "clickable") : "icons/16/blue/roles",
                array('data-dialog' => "true")
            );
        }
    }
    if ($page->isEditable()) {
        $actions->addLink(
            _("Seite bearbeiten"),
            PluginEngine::getURL($plugin, array(), "page/edit/" . $page->getId()),
            version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=") ? Icon::create("edit", "clickable") : "icons/16/blue/edit"
        );
    }
    if (!$page->isNew() && $settings->haveRenamePermission()) {
        $actions->addLink(
            _("Seite umbenennen"),
            PluginEngine::getURL($plugin, array(), "page/rename/" . $page->getId()),
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
        $views->addLink(_("Aktuelle Seite"), PluginEngine::getLink($plugin, array(), "page/view/" . $page->getId()))->setActive(true);
        $views->addLink(_("Vollbild"), "#", null, array('onClick' => "STUDIP.SuperWiki.requestFullscreen(); return false;"));
        $views->addLink(_("Autorenänderungen"), PluginEngine::getLink($plugin, array(), "page/changes/" . $page->getId()));
        $views->addLink(_("Historie"), PluginEngine::getLink($plugin, array(), "page/timeline/" . $page->getId()));
        $sidebar->addWidget($views);
    }
}
