<form action="?" method="post" class="studip_form">
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
                <option value="">intro</option>
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
</form>