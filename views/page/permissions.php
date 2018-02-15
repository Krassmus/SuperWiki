<form action="<?= PluginEngine::getLink($plugin, array(), "page/permissions/".$page->getId()) ?>" method="post" class="studip_form">
    <fieldset>
        <legend><?= _("Einstellungen") ?></legend>
        <label>
            <?= _("Wer darf diese Seite lesen?") ?>
            <select name="read_permission">
                <option value="all"><?= _("Alle") ?></option>
                <option value="tutor"<?= $page['read_permission'] === "tutor" ? " selected" : "" ?>>
                    <?= _("Tutorinnen und Tutoren & Lehrende") ?>
                </option>
                <option value="dozent"<?= $page['read_permission'] === "dozent" ? " selected" : "" ?>>
                    <?= _("Nur Lehrende") ?>
                </option>
                <? foreach ($statusgruppen as $statusgruppe) : ?>
                    <option value="<?= $statusgruppe->getId() ?>"<?= $page['read_permission'] === $statusgruppe->getId() ? " selected" : "" ?>><?= htmlReady($statusgruppe['name']) ?></option>
                <? endforeach ?>
            </select>
        </label>
        <label>
            <?= _("Wer darf diese Seite bearbeiten?") ?>
            <select name="write_permission">
                <option value="all"><?= _("Alle") ?></option>
                <option value="tutor"<?= $page['write_permission'] === "tutor" ? " selected" : "" ?>>
                    <?= _("Tutorinnen und Tutoren & Lehrende") ?>
                </option>
                <option value="dozent"<?= $page['write_permission'] === "dozent" ? " selected" : "" ?>>
                    <?= _("Nur Lehrende") ?>
                </option>
                <? foreach ($statusgruppen as $statusgruppe) : ?>
                    <option value="<?= $statusgruppe->getId() ?>"<?= $page['write_permission'] === $statusgruppe->getId() ? " selected" : "" ?>><?= htmlReady($statusgruppe['name']) ?></option>
                <? endforeach ?>
            </select>
        </label>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>