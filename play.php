<?php

// Embeds the EGF reader and opens the selected .egf game.

$course_plugin = 'EGFGames';
require_once __DIR__.'/config.php';

api_protect_course_script(true);

$plugin         = EGFGames_plugin();
$courseId       = (int) api_get_course_int_id();
$cidReq         = api_get_cidreq();
$id             = $plugin->sanitizeId((string) ($_GET['id'] ?? ''));
$path           = $plugin->getGamePath($courseId, $id);

if (null === $path)
{
    api_not_allowed(true);
}

$base   = api_get_path(WEB_PLUGIN_PATH).'EGFGames/';
$games  = $plugin->listGames($courseId);
$title  = $id;

foreach ($games as $game)
{
    if ($game['id'] === $id)
    {
        $title = $game['title'];
        break;
    }
}

$egfUrl         = $base.'serve.php?id='.rawurlencode($id).'&'.$cidReq;
$readerBase     = $base.'resources/reader/';
$readerLang     = EGFGames_interface_lang();
$backUrl        = $base.'start.php?'.$cidReq;

$html           = file_get_contents(__DIR__.'/resources/reader/index.html');

if (false === $html)
{
    exit($plugin->get_lang('ReaderMissing'));
}

$html           = str_replace('./', $readerBase, $html);
$html           = preg_replace('#<script src="[^"]*config\.js"></script>#', '', $html);

$config         = '<script>window.EGF_READER_CONFIG=Object.freeze({preloadedEgfUrl:'.
    json_encode($egfUrl, JSON_UNESCAPED_SLASHES).
    ',defaultLang:'.json_encode($readerLang).
    ',defaultTheme:"light"});</script>';

$html           = str_replace('<script src="'.$readerBase.'jszip.min.js"></script>', $config."\n".'<script src="'.$readerBase.'jszip.min.js"></script>', $html);

$bar            = '<div style="position:sticky;top:0;z-index:80;background:#0f172a;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:8px 14px;font-family:system-ui,sans-serif">'
    .'<span>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</span>'
    .'<a style="color:#fff;text-decoration:none;background:#2563eb;padding:6px 10px;border-radius:8px" href="'
    .htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8').'">'
    .htmlspecialchars($plugin->get_lang('BackToList'), ENT_QUOTES, 'UTF-8')
    .'</a></div>';

$html           = preg_replace('/<body[^>]*>/', '$0'.$bar, $html, 1);
$html           = preg_replace('/<title>.*?<\/title>/', '<title>'.htmlspecialchars($title.' — '.$plugin->getToolTitle(), ENT_QUOTES, 'UTF-8').'</title>', $html, 1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: SAMEORIGIN');
echo $html;

exit;
