<input type="hidden" id="seminar_id" value="<?= htmlReady($page['seminar_id'] ?: $_SESSION['SessionSeminar']) ?>">
<input type="hidden" id="page_id" value="<?= htmlReady($page->getId()) ?>">

<div class="coworker" style="text-align: right;<?= $onlineusers && (count($onlineusers) > 1) ? "visibility: visible" : "visibility: hidden;" ?>">
    <div class="avatars"><?
        foreach ($onlineusers as $user_id) {
            echo '<a href="'.URLHelper::getLink("dispatch.php/profile", array('username' => get_username($user_id))) .'">'.Avatar::getAvatar($user_id)->getImageTag(Avatar::SMALL).'</a> ';
        }
    ?></div>
</div>

<form action="<?= PluginEngine::getLink($plugin, array(), "page/edit/".$page->getId()) ?>" method="post" id="superwiki_edit_form">
    <? if ($page->isNew()) : ?>
        <input type="text" name="name" style="display: block; width: calc(100% - 8px); font-size: 1.3em; font-weight: bold;" required onChange="STUDIP.SuperWiki.checkPageName.call(this);">
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
    <?= \Studip\Button::create(_("Bearbeiten beenden")) ?>
</form>

<? if (class_exists("RTCRoom")) {
    echo RTCRoom::get("SuperWiki.editing.".$page->getId(), $page['seminar_id'])->render();
} ?>

<? if (!$page->isNew()) : ?>
<script>
    STUDIP.SuperWiki = STUDIP.SuperWiki || {};
    STUDIP.SuperWiki.periodicalPushData = function () {
        var old_content = jQuery("#superwiki_edit_content").data("old_content");
        jQuery("#superwiki_edit_content").data("old_content", jQuery("#superwiki_edit_content").val());
        return {
            'seminar_id': jQuery("#seminar_id").val(),
            'page_id': jQuery("#page_id").val(),
            'content': jQuery("#superwiki_edit_content").val(),
            'old_content': old_content,
            'chdate': jQuery("#superwiki_edit_content").data("chdate"),
            'mode': "edit"
        };
    };
    STUDIP.SuperWiki.updatePage = function (data) {
        if (data.content) {
            var old_content = jQuery("#superwiki_edit_content").data("old_content");
            var new_content = data.content;
            var my_content = jQuery("#superwiki_edit_content").val();
            //var content = STUDIP.SuperWiki.merge(my_content, new_content, old_content);
            var content = TextMerger.get().merge(old_content, my_content, new_content);
            var replacements = TextMerger.get()._getReplacements(my_content, content);
            if (content !== my_content) {
                var pos1 = null, pos2 = null;
                if (jQuery("#superwiki_edit_content").is(":focus")) {
                    pos1 = document.getElementById("superwiki_edit_content").selectionStart;
                    pos2 = document.getElementById("superwiki_edit_content").selectionEnd;
                }
                jQuery("#superwiki_edit_content").val(content);
                for (var i in replacements) {
                    if (replacements[i].end < pos1) {
                        pos1 = pos1 - replacements[i].end + replacements[i].start + replacements[i].text.length;
                    }
                    if (replacements[i].end < pos2) {
                        pos2 = pos2 - replacements[i].end + replacements[i].start + replacements[i].text.length;
                    }
                }
                if (pos1 !== null) {
                    document.getElementById("superwiki_edit_content").setSelectionRange(pos1, pos2);
                }
            }
            jQuery("#superwiki_edit_content").data("old_content", content);
            jQuery("#superwiki_edit_content").data("chdate", data.chdate);
        }
        if (data.onlineusers) {
            jQuery(".coworker").css("visibility", "visible");
            jQuery(".coworker .avatars").html(data.onlineusers);
        } else {
            jQuery(".coworker .avatars").html('');
            jQuery(".coworker").css("visibility", "hidden");
        }
    };

</script>
<? endif ?>

<?
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/wiki-sidebar.png');

$actions = new ActionsWidget();
if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
    $actions->addLink(_("Wiki-Einstellungen"), PluginEngine::getURL($plugin, array(), "page/admin"), "icons/16/blue/admin", array('data-dialog' => "true"));
    if (!$page->isNew()) {
        $actions->addLink(_("Seiten-Einstellungen"), PluginEngine::getURL($plugin, array(), "page/permissions/".$page->getId()), "icons/16/blue/roles", array('data-dialog' => "true"));
    }
}
$actions->addLink(_("Neue Seite anlegen"), PluginEngine::getURL($plugin, array(), "page/edit"), "icons/16/blue/add");

$sidebar->addWidget($actions);

