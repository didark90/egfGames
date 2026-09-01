<?php

/**
 * Homepage of the EGFGames course tool.
 * Teacher: import / delete. Learner: play.
 */

$course_plugin  = 'EGFGames';
require_once __DIR__.'/config.php';

api_protect_course_script(true);

$plugin         = EGFGames_plugin();
$courseId       = (int) api_get_course_int_id();
$isTeacher      = EGFGames_is_teacher();
$cidReq         = api_get_cidreq();
$base           = api_get_path(WEB_PLUGIN_PATH).'EGFGames/';

$message        = '';
$messageType    = 'success';

if ( $isTeacher && 'POST' === ($_SERVER['REQUEST_METHOD'] ?? '') )
{
    $action = (string) ($_POST['action'] ?? '');

    if ('upload' === $action)
    {
        if ( !EGFGames_check_token('upload', $_POST['token'] ?? null) )
        {
            $message        = $plugin->get_lang('InvalidToken');
            $messageType    = 'error';
        }
        
        elseif ( empty($_FILES['egf_file']) )
        {
            $message        = $plugin->get_lang('NoFile');
            $messageType    = 'error';
        }
        
        else
        {
            $result = $plugin->saveUploadedGame($courseId, $_FILES['egf_file']);

            if ( !empty($result['ok']) )
            {
                $message    = $plugin->get_lang('UploadOk');
            }
            
            else
            {
                $errorKey   = $result['error'] ?? 'upload_error';
                $map        = [
                    'not_egf'           => 'NotEgf',
                    'upload_invalid'    => 'NoFile',
                    'upload_error'      => 'UploadError',
                    'move_failed'       => 'UploadError',
                ];
                $message        = $plugin->get_lang($map[$errorKey] ?? 'UploadError');
                $messageType    = 'error';
            }
        }
    }
    
    elseif ('delete' === $action)
    {
        if ( !EGFGames_check_token('delete', $_POST['token'] ?? null) )
        {
            $message        = $plugin->get_lang('InvalidToken');
            $messageType    = 'error';
        }
        
        else
        {
            $ok             = $plugin->deleteGame( $courseId, (string) ($_POST['id'] ?? '') );
            $message        = $ok ? $plugin->get_lang('DeleteOk') : $plugin->get_lang('DeleteError');
            $messageType    = $ok ? 'success' : 'error';
        }
    }
}

$games          = $plugin->listGames($courseId);
$htmlHeadXtra[] = '<style>
.EGFGames-wrap{max-width:960px;margin:0 auto;padding:8px 0 32px;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
.EGFGames-msg{padding:10px 14px;border-radius:8px;margin-bottom:16px}
.EGFGames-msg.success{background:#ecfdf3;color:#166534;border:1px solid #bbf7d0}
.EGFGames-msg.error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.EGFGames-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:16px}
.EGFGames-card h3{margin:0 0 12px;font-size:1.05rem}
.EGFGames-drop{border:2px dashed #cbd5e1;border-radius:10px;padding:18px;text-align:center;background:#f8fafc}
.EGFGames-drop input[type=file]{margin:10px 0}
.EGFGames-btn{display:inline-block;background:#2563eb;color:#fff;border:0;border-radius:8px;padding:8px 14px;cursor:pointer;text-decoration:none;font-size:.95rem}
.EGFGames-btn:hover{background:#1d4ed8;color:#fff}
.EGFGames-btn.secondary{background:#e5e7eb;color:#111827}
.EGFGames-btn.danger{background:#dc2626}
.EGFGames-list{display:grid;gap:10px}
.EGFGames-item{display:flex;justify-content:space-between;gap:12px;align-items:center;border:1px solid #e5e7eb;border-radius:10px;padding:12px 14px;background:#fafafa}
.EGFGames-item strong{display:block}
.EGFGames-item small{color:#6b7280}
.EGFGames-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.EGFGames-empty{color:#6b7280;padding:12px 0}
@media(max-width:700px){.EGFGames-item{flex-direction:column;align-items:flex-start}}
</style>';

Display::display_header( $plugin->getToolTitle() );

echo '<div class="EGFGames-wrap">';

if ('' !== $message)
{
    echo '<div class="EGFGames-msg '.$messageType.'">'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</div>';
}

if ($isTeacher)
{
    echo '<div class="EGFGames-card"><h3>'.htmlspecialchars($plugin->get_lang('ImportGame'), ENT_QUOTES, 'UTF-8').'</h3>';
    echo '<form class="EGFGames-drop" method="post" enctype="multipart/form-data">';
    echo '<input type="hidden" name="action" value="upload">';
    echo '<input type="hidden" name="token" value="'.htmlspecialchars(EGFGames_token('upload'), ENT_QUOTES, 'UTF-8').'">';
    echo '<p>'.htmlspecialchars($plugin->get_lang('ImportHelp'), ENT_QUOTES, 'UTF-8').'</p>';
    echo '<input type="file" name="egf_file" accept=".egf,application/zip" required>';
    echo '<div><button class="EGFGames-btn" type="submit">'.htmlspecialchars($plugin->get_lang('Import'), ENT_QUOTES, 'UTF-8').'</button></div>';
    echo '</form></div>';
}

echo '<div class="EGFGames-card"><h3>'.htmlspecialchars($plugin->get_lang('GameList'), ENT_QUOTES, 'UTF-8').'</h3>';

if (!$games)
{
    echo '<p class="EGFGames-empty">'.htmlspecialchars($plugin->get_lang('NoGames'), ENT_QUOTES, 'UTF-8').'</p>';
}

else
{
    echo '<div class="EGFGames-list">';

    foreach ($games as $game)
    {
        $playUrl = $base.'play.php?id='.rawurlencode($game['id']).'&'.$cidReq;
        echo '<div class="EGFGames-item"><div>';
        echo '<strong>'.htmlspecialchars($game['title'], ENT_QUOTES, 'UTF-8').'</strong>';
        echo '<small>'.htmlspecialchars($game['filename'].' · '.$plugin->formatSize($game['size']), ENT_QUOTES, 'UTF-8').'</small>';
        echo '</div><div class="EGFGames-actions">';
        echo '<a class="EGFGames-btn" href="'.htmlspecialchars($playUrl, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($plugin->get_lang('Play'), ENT_QUOTES, 'UTF-8').'</a>';
        
        if ($isTeacher)
        {
            echo '<form method="post" onsubmit="return confirm('.json_encode($plugin->get_lang('ConfirmDelete')).');">';
            echo '<input type="hidden" name="action" value="delete">';
            echo '<input type="hidden" name="id" value="'.htmlspecialchars($game['id'], ENT_QUOTES, 'UTF-8').'">';
            echo '<input type="hidden" name="token" value="'.htmlspecialchars(EGFGames_token('delete'), ENT_QUOTES, 'UTF-8').'">';
            echo '<button class="EGFGames-btn danger" type="submit">'.htmlspecialchars($plugin->get_lang('Delete'), ENT_QUOTES, 'UTF-8').'</button>';
            echo '</form>';
        }
        
        echo '</div></div>';
    }
    echo '</div>';
}

echo '</div>';
echo '<p><a class="EGFGames-btn secondary" href="'.htmlspecialchars(EGFGames_course_home_url(), ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($plugin->get_lang('BackToCourse'), ENT_QUOTES, 'UTF-8').'</a></p>';
echo '</div>';

Display::display_footer();
