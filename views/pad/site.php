<input type="hidden" id="seminar_id" value="<?= $_SESSION['SessionSeminar'] ?>">
<input type="hidden" id="site" value="<?= htmlReady($page['name']) ?>">

<div id="superwiki_page_content" data-chdate="<?= htmlReady($page['chdate']) ?>">
    <?= formatReady($page['content']) ?>
</div>
<script>
    STUDIP.SuperWiki = {};
    STUDIP.SuperWiki.periodicalPushData = function () {
        return {
            'seminar_id': jQuery("#seminar_id").val(),
            'site': jQuery("#site").val(),
            'chdate': jQuery("#superwiki_page_content").data("chdate"),
            'mode': "read"
        };
    };
    STUDIP.SuperWiki.updatePage = function (data) {
        jQuery("#superwiki_page_content").data("chdate", data.chdate)
        jQuery("#superwiki_page_content").html(data.html);
    }
</script>