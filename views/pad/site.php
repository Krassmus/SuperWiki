<input type="hidden" id="seminar_id" value="<?= $_SESSION['SessionSeminar'] ?>">
<input type="hidden" id="site" value="<?= htmlReady($page['name']) ?>">

<h1><?= htmlReady($page['name']) ?></h1>
<div id="superwiki_page_content" data-chdate="<?= htmlReady($page['chdate']) ?>">
    <?= formatReady($page['content']) ?>
</div>
<script>
    STUDIP.SuperWiki = {};
    STUDIP.SuperWiki.periodicalPushData = function () {
        return {
            'seminar_id': jQuery("#seminar_id").val(),
            'site': jQuery("#site").val(),
            'chdate': jQuery("#superwiki_page_content").data("chdate"),
            'mode': "read"
        };
    };
    STUDIP.SuperWiki.updatePage = function (data) {
        jQuery("#superwiki_page_content").data("chdate", data.chdate)
        jQuery("#superwiki_page_content").html(data.html);
    };
</script>


<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/wiki-sidebar.png');

$actions = new ActionsWidget();
if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
    $actions->addLink(_("Wiki-Einstellungen"), PluginEngine::getURL($plugin, array(), "pad/admin"), "icons/16/blue/admin", array('data-dialog' => "true"));
}
if ($page->isEditable()) {
    $actions->addLink(_("Seite bearbeiten"), PluginEngine::getURL($plugin, array(), "pad/edit/".$page->getId()), "icons/16/blue/edit");
}
$actions->addLink(_("Neue Seite anlegen"), PluginEngine::getURL($plugin, array(), "pad/edit"), "icons/16/blue/add");

$sidebar->addWidget($actions);

