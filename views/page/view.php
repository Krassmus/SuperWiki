<? if (count($_SESSION['SuperWiki_History'][$_SESSION['SessionSeminar']]) > 1) : ?>
    <div id="superwiki_history" style="font-size: 0.8em;">
        <ol style="margin: 0px; list-style-type: none; padding: 0px; margin-bottom: 13px;">
            <? foreach ($_SESSION['SuperWiki_History'][$_SESSION['SessionSeminar']] as $key => $page_id) : ?>
                <? if ($key < count($_SESSION['SuperWiki_History'][$_SESSION['SessionSeminar']]) - 1) : ?>
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

<input type="hidden" id="seminar_id" value="<?= $_SESSION['SessionSeminar'] ?>">
<input type="hidden" id="site" value="<?= htmlReady($page['name']) ?>">

<h1><?= htmlReady($page['name'] ?: "intro") ?></h1>
<div id="superwiki_page_content" data-chdate="<?= htmlReady($page['chdate']) ?>">
    <? if ($page->isNew()) : ?>
        <?= _("Dieses Wiki ist schon super. Aber leider trotzdem noch leer.") ?>
    <? else : ?>
        <?= $page->wikiFormat() ?>
    <? endif ?>
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
    $actions->addLink(_("Wiki-Einstellungen"), PluginEngine::getURL($plugin, array(), "page/admin"), "icons/16/blue/admin", array('data-dialog' => "true"));
}
if ($page->isEditable()) {
    $actions->addLink(_("Seite bearbeiten"), PluginEngine::getURL($plugin, array(), "page/edit/".$page->getId()), "icons/16/blue/edit");
}
if ($settings->haveCreatePermission()) {
    $actions->addLink(_("Neue Seite anlegen"), PluginEngine::getURL($plugin, array(), "page/edit"), "icons/16/blue/add");
}

$sidebar->addWidget($actions);

