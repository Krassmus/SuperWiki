<form action="<?= PluginEngine::getLink($plugin, array(), "page/admin") ?>" method="post" class="studip_form">
    <input type="hidden" name="page_id" value="<?= Request::option("page_id") ?>">
    <fieldset>
        <legend>
            <?= _("Einstellungen") ?>
        </legend>
        <label>
            <?= _("Name des Reiters") ?>
            <input type="text" name="name" value="<?= htmlReady($settings['name']) ?>">
        </label>

        <? $allpages = SuperwikiPage::findAll($_SESSION['SessionSeminar']) ?>
        <? if (count($allpages)) : ?>
            <label>
                <?= _("Startseite des Wikis") ?>
                <select name="indexpage">
                    <? foreach ($allpages as $page) : ?>
                        <option value="<?= $page->getId() ?>"<?= $page->getId() === $settings['indexpage'] ? " selected" : "" ?>><?= htmlReady($page['name']) ?></option>
                    <? endforeach ?>
                </select>
            </label>
        <? endif ?>

        <label>
            <?= _("Wer darf neue Seiten erstellen?") ?>
            <select name="create_permission">
                <option value="all"<?= $settings['create_permission'] === "all" ? " selected" : "" ?>><?= _("jeder") ?></option>
                <option value="tutor"<?= $settings['create_permission'] === "tutor" ? " selected" : "" ?>><?= _("Tutoren und Dozenten") ?></option>
            </select>
        </label>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(_("speichern")) ?>
    </div>
</form>