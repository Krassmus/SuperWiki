<form action="<?= PluginEngine::getLink($plugin, array(), "page/rename/".$page->getId()) ?>" method="post" class="default">
    <fieldset>
        <legend>
            <?= sprintf(_("'%s' umbenennen"), htmlReady($page['name']))?>
        </legend>
        <label>
            <?= _("Neuer Name") ?>
            <input type="text" name="new_name" value="<?= htmlReady($page['name']) ?>" required>
        </label>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>