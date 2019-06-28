<form action="<?= PluginEngine::getLink($plugin, array(), "cms/save/".$cms->getId()) ?>"
      method="post"
      class="default">

    <label>
        <?= _("Von Veranstaltung") ?>
        <? $course_search = QuickSearch::get("data[seminar_id]", StandardSearch::get("Seminar_id"));
        if ($cms['seminar_id']) {
            $course_search->defaultValue($cms['seminar_id'], Course::find($cms['seminar_id'])->name);
        }
        echo $course_search->render() ?>
    </label>

    <label>
        <?= _("Titel") ?>
        <input type="text" maxlength="64" name="data[title]" value="<?= htmlReady($cms['title']) ?>">
    </label>

    <label>
        <?= _("Navigationspfad") ?>
        <input type="text" name="data[navigation]" value="<?= htmlReady($cms['navigation']) ?>" placeholder="z.B. /start/impressum">
    </label>

    <label>
        <?= _("Icon") ?>
        <input type="text" name="data[icon]" value="<?= htmlReady($cms['icon']) ?>">
    </label>

    <label>
        <?= _("Beschreibung") ?>
        <input type="text" name="data[description]" value="<?= htmlReady($cms['description']) ?>">
    </label>

    <input type="hidden" name="data[active]" value="0">
    <label>
        <input type="checkbox"
               name="data[active]"
               value="1"<?= $cms['active'] ? " checked" : "" ?>>
        <?= _("Aktiv") ?>
    </label>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>