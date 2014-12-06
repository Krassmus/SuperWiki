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
        data-chdate="<?= htmlReady($page['chdate']) ?>"
        style="width: calc(100% - 8px); height: 300px;"
        ><?= htmlReady($page['content']) ?></textarea>
    <?= \Studip\Button::create(_("speichern")) ?>
</form>


<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/wiki-sidebar.png');

$actions = new ActionsWidget();
$actions->addLink(_("Neue Seite anlegen"), PluginEngine::getURL($plugin, array(), "pad/edit"), "icons/16/blue/add");

$sidebar->addWidget($actions);

