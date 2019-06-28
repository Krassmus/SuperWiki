<table class="default">
    <thead>
        <tr>
            <th>

            </th>
            <th>
                <?= _("Titel") ?>
            </th>
            <th>
                <?= _("Veranstaltung") ?>
            </th>
            <th class="actions">

            </th>
        </tr>
    </thead>
    <tbody>
        <? if (!count($cms)) : ?>
            <tr>
                <td colspan="4">
                    <?= _("Noch gibt es keine Superwikis aus Veranstaltungen, die als Content-Management-System eingebunden sind. Sie können aber eines erstellen.") ?>
                </td>
            </tr>
        <? endif ?>
        <? foreach ($cms as $c) : ?>
            <tr>
                <td></td>
                <td>
                    <?= htmlReady($c['title']) ?>
                </td>
                <td>
                    <a href="<?= PluginEngine::getLink($plugin, array('cid' => $c['seminar_id']), "page/view") ?>">
                        <?= Icon::create("seminar", "clickable")->asImg(16, array('class' => "text-bottom")) ?>
                        <?= htmlReady(Course::find($c['seminar_id'])->name) ?>
                    </a>
                </td>
                <td class="actions">
                    <a href="<?= PluginEngine::getLink($plugin, array(), "cms/edit/".$c->getId()) ?>" data-dialog>
                        <?= Icon::create("edit", "clickable") ?>
                    </a>
                </td>
            </tr>
        <? endforeach ?>
    </tbody>
</table>

<?

$actions = new ActionsWidget();
$actions->addLink(
    _("Neues CMS erstellen"),
    PluginEngine::getURL($plugin, array(), "cms/edit"),
    Icon::create("add", "clickable"),
    array(
        'data-dialog' => 1
    )
);
Sidebar::Get()->addWidget($actions);