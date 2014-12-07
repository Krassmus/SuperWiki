<form action="<?= PluginEngine::getLink($plugin, array(), "page/permissions/".$page->getId()) ?>" method="post" class="studip_form">
    <fieldset>
        <legend><?= _("Einstellungen") ?></legend>
        <label>
            <?= _("Wer darf diese Seite lesen?") ?>
            <select name="read_permission">
                <option value="all"><?= _("jeder") ?></option>
                <option value="tutor"<?= $page['read_permission'] === "tutor" ? " selected" : "" ?>><?= _("Tutoren & Dozenten") ?></option>
            </select>
        </label>
        <label>
            <?= _("Wer darf diese Seite bearbeiten?") ?>
            <select name="write_permission">
                <option value="all"><?= _("jeder") ?></option>
                <option value="tutor"<?= $page['write_permission'] === "tutor" ? " selected" : "" ?>><?= _("Tutoren & Dozenten") ?></option>
            </select>
        </label>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(_("speichern")) ?>
    </div>
</form>