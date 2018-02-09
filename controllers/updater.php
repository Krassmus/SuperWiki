<?php
class UpdaterController extends PluginController
{
    protected $allow_nobody = true;

    public function superwikiupdate_action()
    {
        $output = array();
        if (Request::isPost()) {
            $page = SuperwikiPage::find(Request::option("page_id"));
            if (Request::get('mode') === "read") {
                if (Request::get('chdate') < $page['chdate']) {
                    $output['html'] = $page->wikiFormat();
                    $output['chdate'] = $page['chdate'];
                }
            } elseif (Request::get('mode') === "edit" && $page->isEditable()) {
                if (Request::submitted("content") && Request::submitted("old_content")) {
                    //do the merging only if needed.
                    $content1 = str_replace("\r", "", Request::get('content'));
                    $original_content = str_replace("\r", "", Request::get('old_content'));
                    $content2 = str_replace("\r", "", $page['content']);
                    $page['content'] = \Superwiki\Textmerger::get()->merge(
                        $original_content,
                        $content1,
                        $content2
                    );
                    //$output['debugcontent'] = $page['content'];
                    if ($page['content'] !== $content2) {
                        $page['last_author'] = $GLOBALS['user']->id;
                        $page->store();
                    }
                }

                $output['content'] = $page['content'];
                $output['chdate'] = $page['chdate'];

                //Online users
                $statement = DBManager::get()->prepare("
                    INSERT INTO superwiki_editors
                    SET user_id = :me,
                        page_id = :page_id,
                        online = UNIX_TIMESTAMP(),
                        latest_change = '0'
                    ON DUPLICATE KEY UPDATE
                        online = UNIX_TIMESTAMP(),
                        latest_change = IF(:changed, UNIX_TIMESTAMP(), latest_change)
                ");
                $statement->execute(array(
                    'me' => $GLOBALS['user']->id,
                    'page_id' => $page->getId(),
                    'changed' => Request::submitted("content") && ($content1 !== $original_content) ? 1 : 0
                ));
                $statement = DBManager::get()->prepare("
                    SELECT user_id, latest_change
                    FROM superwiki_editors
                    WHERE page_id = :page_id
                        AND online >= UNIX_TIMESTAMP() - 6
                ");
                $statement->execute(array(
                    'page_id' => $page->getId()
                ));
                $onlineusers = "";
                $onlineusers_count = 0;
                foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $user) {
                    $this->user_id = $user['user_id'];
                    $this->writing = ($user['latest_change'] >= (time() - 3));
                    $onlineusers .= $this->render_template_as_string("page/_online_user.php");
                    $onlineusers_count++;
                }
                $output['onlineusers_count'] = $onlineusers_count;
                $output['onlineusers'] = $onlineusers;
            }

            //WebRTC stuff:
            $open_offers = DBManager::get()->prepare("
                SELECT *
                FROM superwiki_connections
                WHERE page_id = :page_id
                    AND answerer_id = :me
                    AND answer_sdp IS NULL
            ");
            $open_offers->execute(array(
                'page_id' => $page->getId(),
                'me' => $GLOBALS['user']->id
            ));
            $output['open_offers'] = $open_offers->fetchAll(PDO::FETCH_ASSOC);

            //part4: collect all connections initiated by that user
            $answers = DBManager::get()->prepare("
                SELECT *
                FROM superwiki_connections
                WHERE page_id = :page_id
                    AND user_id = :me
                    AND answer_sdp IS NOT NULL
            ");
            $answers->execute(array(
                'page_id' => $page->getId(),
                'me' => $GLOBALS['user']->id
            ));
            $output['answered_connections'] = $answers->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->render_json($output);
    }

}
