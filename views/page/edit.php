<input type="hidden" id="seminar_id" value="<?= htmlReady($page['seminar_id'] ?: $course_id) ?>">
<input type="hidden" id="page_id" value="<?= htmlReady($page->getId()) ?>">

<form action="<?= PluginEngine::getLink($plugin, array(), "page/save/".$page->getId()) ?>" method="post" id="superwiki_edit_form">
    <? if ($page->isNew()) : ?>
        <input type="text" name="name" style="display: block; width: calc(100% - 8px); font-size: 1.3em; font-weight: bold;" required onChange="STUDIP.SuperWiki.checkPageName.call(this);">
    <? else : ?>
        <h1>
            <?= htmlReady($page['name']) ?>
        </h1>
        <input type="hidden" name="page_id" value="<?= htmlReady($page->getId()) ?>">
    <? endif ?>
    <textarea
        name="content"
        id="superwiki_edit_content"
        style="width: calc(100% - 8px); height: 300px;"
        ><?= htmlReady($page['content']) ?></textarea>
    <?= \Studip\Button::create(_("Bearbeiten beenden")) ?>
</form>


<? if (class_exists("RTCRoom")) {
    echo RTCRoom::get("SuperWiki.editing.".$page->getId(), $page['seminar_id'])->render();
} ?>

<? if (!$page->isNew()) : ?>
<script>
    STUDIP.SuperWiki = STUDIP.SuperWiki || {};
    STUDIP.SuperWiki.oldVersion = jQuery("#superwiki_edit_content").val();

    <? if (Config::get()->SUPERWIKI_USE_OWN_UPDATER) : ?>
        jQuery(function () {
            STUDIP.SuperWiki.oldVersion = jQuery("#superwiki_edit_content").val();
            STUDIP.SuperWiki.oldVersionChdate = '<?= (int) $page['chdate'] ?>';
        });
        window.setInterval(STUDIP.SuperWiki.pushData, 2000);
    <? else: ?>
        jQuery(function () {
            STUDIP.SuperWiki.periodicalPushData = function () {
                if (STUDIP.SuperWiki.oldVersion !== STUDIP.SuperWiki.oldOldVersion) {
                    //the request went wrong and we need to resend the old-old-data:
                    STUDIP.SuperWiki.oldVersion = STUDIP.SuperWiki.oldOldVersion;
                }
                var push = {
                    'seminar_id': jQuery("#seminar_id").val(),
                    'page_id': jQuery("#page_id").val(),
                    'mode': "edit"
                };
                if (jQuery("#superwiki_edit_content").val() !== STUDIP.SuperWiki.oldVersion) {
                    push.content = jQuery("#superwiki_edit_content").val();
                    push.old_content = STUDIP.SuperWiki.oldVersion;
                }
                STUDIP.SuperWiki.oldVersion = jQuery("#superwiki_edit_content").val();
                return push;
            };
            jQuery(function () {
                STUDIP.SuperWiki.oldVersion = jQuery("#superwiki_edit_content").val();
                STUDIP.SuperWiki.oldOldVersion = STUDIP.SuperWiki.oldVersion;
            });
        });
    <? endif ?>

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
            PluginEngine::getURL($plugin, array(), "page/permissions/".$page->getId()),
            version_compare($GLOBALS['SOFTWARE_VERSION'], "3.4", ">=") ? Icon::create("roles", "clickable") : "icons/16/blue/roles",
            array('data-dialog' => "true")
        );
    }
}
if (!$page->isNew() && $settings->haveRenamePermission()) {
    $actions->addLink(
        _("Seite umbenennen"),
        PluginEngine::getURL($plugin, array(), "page/rename/".$page->getId()),
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

if (!$page->isNew() && $page->isEditable()) {
    $cowriter = new Widget();
    $cowriter->title = _("Aktuelle Mitarbeiter");
    $coworker = "";
    foreach ($onlineusers as $user) {
        $coworker .= $this->render_partial("page/_online_user.php", array(
            'user_id' => $user['user_id'],
            'writing' => $user['latest_change'] >= time() - 3)
        );
    }
    $cowriter->addElement(new WidgetElement('<ul class="clean coworkerlist">'.$coworker.'</ul>'));
    $sidebar->addWidget($cowriter);
}

if (!$page->isNew()) {
    $views = new ViewsWidget();
    $views->addLink(_("Aktuelle Seite"), PluginEngine::getLink($plugin, array(), "page/view/".$page->getId()))->setActive(true);
    $views->addLink(_("Autorenänderungen"), PluginEngine::getLink($plugin, array(), "page/changes/".$page->getId()));
    $views->addLink(_("Historie"), PluginEngine::getLink($plugin, array(), "page/timeline/".$page->getId()));
    $sidebar->addWidget($views);
}
