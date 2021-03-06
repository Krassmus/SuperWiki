<form action="<?= PluginEngine::getLink($plugin, array(), "page/permissions/".$page->getId()) ?>"
      method="post"
      class="default">
    <fieldset>
        <legend><?= _("Einstellungen") ?></legend>
        <label>
            <?= _("Wer darf diese Seite lesen?") ?>
            <select name="read_permission">
                <option value="all"<?= $page['read_permission'] === "all" ? " selected" : "" ?>><?= _("Alle") ?></option>
                <option value="tutor"<?= $page['read_permission'] === "tutor" ? " selected" : "" ?>><?= _("Tutoren & Dozenten") ?></option>
                <option value="dozent"<?= $page['read_permission'] === "dozent" ? " selected" : "" ?>><?= _("Nur Dozenten") ?></option>
                <? foreach ($statusgruppen as $statusgruppe) : ?>
                    <option value="<?= $statusgruppe->getId() ?>"<?= $page['read_permission'] === $statusgruppe->getId() ? " selected" : "" ?>><?= htmlReady($statusgruppe['name']) ?></option>
                <? endforeach ?>
            </select>
        </label>
        <label>
            <?= _("Wer darf diese Seite bearbeiten?") ?>
            <select name="write_permission">
                <option value="all"<?= $page['write_permission'] === "all" ? " selected" : "" ?>><?= _("Alle") ?></option>
                <option value="tutor"<?= $page['write_permission'] === "tutor" ? " selected" : "" ?>><?= _("Tutoren & Dozenten") ?></option>
                <option value="dozent"<?= $page['write_permission'] === "dozent" ? " selected" : "" ?>><?= _("Nur Dozenten") ?></option>
                <? foreach ($statusgruppen as $statusgruppe) : ?>
                    <option value="<?= $statusgruppe->getId() ?>"<?= $page['write_permission'] === $statusgruppe->getId() ? " selected" : "" ?>><?= htmlReady($statusgruppe['name']) ?></option>
                <? endforeach ?>
            </select>
        </label>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(_("speichern")) ?>
    </div>
</form>