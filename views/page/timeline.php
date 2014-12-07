<div class="snapshot_info">

</div>

<div id="timeline_slider" style="width: calc(100% - 20px); margin-bottom: 20px;"></div>
<script>
    jQuery(function () {
        jQuery("#timeline_slider").slider({
            'max': <?= count($page->versions) + 1 ?>,
            'min': 1,
            'value': <?= count($page->versions) + 1 ?>,
            'slide': function (event, ui) {
                jQuery(".superwiki_content:visible:not(#version_" + ui.value + ")").hide();
                jQuery("#version_" + ui.value).show();
            }
        });
    });
</script>


<h1><?= htmlReady($page['name']) ?></h1>

<div class="versions">
    <div class="superwiki_content" id="version_<?= count($page->versions) + 1 ?>"><?= $page->wikiFormat() ?></div>

    <? foreach ($page->versions as $key => $version) : ?>
        <div class="superwiki_content" id="version_<?= count($page->versions) - $key ?>" style="display: none;"><?= $version->wikiFormat() ?></div>
    <? endforeach ?>
</div>

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

$views = new ViewsWidget();
$views->addLink(_("Seite"), PluginEngine::getLink($plugin, array(), "page/view/".$page->getId()));
$views->addLink(_("Historie"), PluginEngine::getLink($plugin, array(), "page/timeline/".$page->getId()))->setActive(true);

$sidebar->addWidget($views);


