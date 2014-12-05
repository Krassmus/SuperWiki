<form action="<?= PluginEngine::getLink($plugin, array(), "pad/admin") ?>" method="post" class="studip_form">
    <input type="hidden" name="page_id" value="<?= Request::option("page_id") ?>">
    <fieldset>
        <legend>
            <?= _("Einstellungen") ?>
        </legend>
        <label>
            <?= _("Name des Reiters") ?>
            <input type="text" name="name" value="<?= htmlReady($settings['name']) ?>">
        </label>

        <label>
            <?= _("Startseite des Wikis") ?>
            <select name="indexpage">
                <option value="intro">intro</option>
            </select>
        </label>

        <label>
            <?= _("Wer darf neue Seiten erstellen?") ?>
            <select name="create_permission">
                <option value="all"><?= _("jeder") ?></option>
                <option value="tutor"><?= _("Tutoren und Dozenten") ?></option>
            </select>
        </label>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(_("speichern")) ?>
    </div>
</form>