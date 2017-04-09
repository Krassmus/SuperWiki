<li>
    <a href="<?= URLHelper::getLink("dispatch.php/profile", array('username' => get_username($user_id))) ?>" title="<?= htmlReady(get_fullname($user_id)) ?>">
        <?= Avatar::getAvatar($user_id)->getImageTag(Avatar::SMALL) ?>
        <?= htmlReady(get_fullname($user_id)) ?>
    </a>
    <? if ($writing) : ?>
        <?= Icon::create("comment", "inactive")->asImg(16, array('class' => "text-bottom")) ?>
    <? endif ?>
</li>