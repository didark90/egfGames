<?php

require_once dirname(__DIR__, 2).'/main/inc/global.inc.php';
require_once __DIR__.'/EGFGamesPlugin.php';

function EGFGames_plugin(): EGFGamesPlugin
{
    return EGFGamesPlugin::create();
}

function EGFGames_is_teacher(): bool
{
    return api_is_allowed_to_edit(false, true);
}

function EGFGames_token(string $action = 'EGFGames'): string
{
    $key = 'EGFGames_token_'.$action;

    if ( empty($_SESSION[$key]) )
    {
        $_SESSION[$key] = bin2hex(random_bytes(16));
    }

    return (string) $_SESSION[$key];
}

function EGFGames_check_token(string $action, ?string $submitted): bool
{
    $expected = (string) ($_SESSION['EGFGames_token_'.$action] ?? '');

    return '' !== $expected && hash_equals($expected, (string) $submitted);
}

function EGFGames_course_home_url(): string
{
    $courseId   = (int) api_get_course_int_id();
    $sessionId  = (int) api_get_session_id();

    return api_get_path(WEB_PATH).'course/'.$courseId.'/home?sid='.$sessionId;
}

function EGFGames_interface_lang(): string
{
    $raw = '';

    if ( function_exists('api_get_language_isocode') )
    {
        $raw = (string) api_get_language_isocode();
    }

    elseif ( function_exists('api_get_interface_language') )
    {
        $raw = (string) api_get_interface_language();
    }

    elseif ( !empty($GLOBALS['language_interface']) )
    {
        $raw = (string) $GLOBALS['language_interface'];
    }
    
    elseif ( !empty($_SESSION['user_language_choice']) )
    {
        $raw = (string) $_SESSION['user_language_choice'];
    }

    $iso        = strtolower(substr($raw, 0, 2));
    $allowed    = ['en', 'fr', 'es', 'pt', 'zh', 'ar', 'hi', 'ur', 'ru'];

    return in_array($iso, $allowed, true) ? $iso : 'fr';
}
