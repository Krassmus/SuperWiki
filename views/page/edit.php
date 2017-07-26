<input type="hidden" id="seminar_id" value="<?= htmlReady($page['seminar_id'] ?: $course_id) ?>">
<input type="hidden" id="page_id" value="<?= htmlReady($page->getId()) ?>">

<form action="<?= PluginEngine::getLink($plugin, array(), "page/edit/".$page->getId()) ?>" method="post" id="superwiki_edit_form">
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
    STUDIP.SuperWiki.periodicalPushData = function () {
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
    });
    STUDIP.SuperWiki.updatePage = function (data) {
        if (typeof data.content !== "undefined") {
            var old_content = STUDIP.SuperWiki.oldVersion;
            var new_content = data.content;
            var my_content = jQuery("#superwiki_edit_content").val();
            var content = Textmerger.get().merge(old_content, my_content, new_content);
            var replacements = Textmerger.get().getReplacements(old_content, my_content, new_content);
            if (content !== my_content) {
                var pos1 = null, pos2 = null;
                if (jQuery("#superwiki_edit_content").is(":focus")) {
                    pos1 = document.getElementById("superwiki_edit_content").selectionStart;
                    pos2 = document.getElementById("superwiki_edit_content").selectionEnd;
                }
                jQuery("#superwiki_edit_content").val(content);
                for (var i in replacements.replacements) {
                    var replacement = replacements.replacements[i];
                    if (replacement.origin === "text2") {
                        if (replacements[i].end < pos1) {
                            pos1 = pos1 - replacements[i].end + replacements[i].start + replacements[i].text.length;
                        }
                        if (replacements[i].end < pos2) {
                            pos2 = pos2 - replacements[i].end + replacements[i].start + replacements[i].text.length;
                        }
                    }
                }
                if (pos1 !== null) {
                    document.getElementById("superwiki_edit_content").setSelectionRange(pos1, pos2);
                }
            }
            STUDIP.SuperWiki.oldVersion = data.content;
        }
        //Mitarbeiter aktualisieren:
        jQuery(".coworkerlist").html(data.onlineusers);
    };

</script>
<? endif ?>

<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/wiki-sidebar.png');

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