<input type="hidden" id="seminar_id" value="<?= htmlReady($page['seminar_id']) ?>">
<input type="hidden" id="page_id" value="<?= htmlReady($page->getId()) ?>">

<form action="?" method="post">
    <? if ($page->isNew()) : ?>
        <input type="text" name="name" style="display: block; width: calc(100% - 8px); font-size: 1.3em; font-weight: bold;" required>
    <? else : ?>
        <h1><?= htmlReady($page['name']) ?></h1>
        <input type="hidden" name="page_id" value="<?= htmlReady($page->getId()) ?>">
    <? endif ?>
    <textarea
        name="content"
        id="superwiki_edit_content"
        data-old_content="<?= htmlReady($page['content']) ?>"
        data-chdate="<?= htmlReady($page['chdate']) ?>"
        style="width: calc(100% - 8px); height: 300px;"
        ><?= htmlReady($page['content']) ?></textarea>
    <?= \Studip\Button::create(_("speichern")) ?>
</form>

<? if (!$page->isNew()) : ?>
<script>
    STUDIP.SuperWiki = STUDIP.SuperWiki || {};
    STUDIP.SuperWiki.periodicalPushData = function () {
        return {
            'seminar_id': jQuery("#seminar_id").val(),
            'page_id': jQuery("#page_id").val(),
            'content': jQuery("#superwiki_edit_content").val(),
            'old_content': jQuery("#superwiki_edit_content").data("old_content"),
            'chdate': jQuery("#superwiki_edit_content").data("chdate"),
            'mode': "edit"
        };
    };
    STUDIP.SuperWiki.updatePage = function (data) {
        var old_content = jQuery("#superwiki_edit_content").data("old_content");
        var new_content = data.content;
        var my_content = jQuery("#superwiki_edit_content").val();
        var content = STUDIP.SuperWiki.merge(my_content, new_content, old_content);
        if (content !== my_content) {
            jQuery("#superwiki_edit_content").val(content);
        }
        jQuery("#superwiki_edit_content").data("old_content", content);
        jQuery("#superwiki_edit_content").data("chdate", data.chdate);
    };
</script>
<? endif ?>

<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/wiki-sidebar.png');

$actions = new ActionsWidget();
$actions->addLink(_("Neue Seite anlegen"), PluginEngine::getURL($plugin, array(), "pad/edit"), "icons/16/blue/add");

$sidebar->addWidget($actions);
