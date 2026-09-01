<?php

require_once __DIR__.'/config.php';

if ( !api_is_platform_admin() )
{
    require_once __DIR__.'/EGFGamesPlugin.php';
    
    exit(EGFGamesPlugin::create()->get_lang('AdminOnly'));
}

EGFGamesPlugin::create()->install();
