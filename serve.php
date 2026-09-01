<?php

// Sends the current course .egf file to users who can access that course.

$course_plugin  = 'EGFGames';
require_once __DIR__.'/config.php';

api_protect_course_script(true);

$plugin         = EGFGames_plugin();
$courseId       = (int) api_get_course_int_id();
$id             = $plugin->sanitizeId((string) ($_GET['id'] ?? ''));
$path           = $plugin->getGamePath($courseId, $id);

if (null === $path)
{
    header('HTTP/1.1 404 Not Found');

    exit($plugin->get_lang('NotFound'));
}

$filename   = basename($path);
$size       = filesize($path);

header('Content-Type: application/egf+zip');
header('Content-Disposition: inline; filename="'.str_replace('"', '', $filename).'"');
header('Content-Length: '.(string) $size);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');

$fp = fopen($path, 'rb');

if ($fp)
{
    fpassthru($fp);
    fclose($fp);
}

exit;
