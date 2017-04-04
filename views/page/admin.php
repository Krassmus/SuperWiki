<form action="<?= PluginEngine::getLink($plugin, array(), "page/admin") ?>" method="post" class="default">
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

        <label>
            <?= _("Wer darf Seiten umbenennen?") ?>
            <select name="rename_permission">
                <option value="all"<?= $settings['create_permission'] === "all" ? " selected" : "" ?>><?= _("jeder") ?></option>
                <option value="tutor"<?= $settings['create_permission'] === "tutor" ? " selected" : "" ?>><?= _("Tutoren und Dozenten") ?></option>
                <option value="dozent"<?= $settings['create_permission'] === "dozent" ? " selected" : "" ?>><?= _("Nur Dozenten") ?></option>
            </select>
        </label>
    </fieldset>
    <fieldset>
        <legend>
            <?= _("Icon des Superwikis") ?>
        </legend>
        <div>
            <? $icons = array("wiki", "info-circle", "info-small", "infopage", "exclaim", "link-intern", "literature", "log") ?>
            <? foreach ($icons as $icon) : ?>
            <label>
                <input type="radio" name="icon" value="<?= htmlReady($icon) ?>"<?= $icon === $settings['icon'] ? " checked" : "" ?>>
                <?= Assets::img("icons/20/black/".$icon, array('class' => "text-bottom")) ?>
            </label>
            <? endforeach ?>
        </div>
    </fieldset>
    <fieldset>
        <legend>
            <?= _("Icon der Links") ?>
        </legend>
        <div>
            <? $icons = array("wiki", "info-circle", "info-small", "infopage", "exclaim", "link-intern", "literature", "log") ?>
            <? foreach ($icons as $icon) : ?>
                <label>
                    <input type="radio" name="link_icon" value="<?= htmlReady($icon) ?>"<?= $icon === $settings['link_icon'] ? " checked" : "" ?>>
                    <?= Assets::img("icons/20/black/".$icon, array('class' => "text-bottom")) ?>
                </label>
            <? endforeach ?>
        </div>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(_("speichern")) ?>
    </div>
</form>