<?php

/**
 * EGFGames plugin metadata
 */

$plugin_info = [
    'title'         => 'EGFGames',
    'comment'       => 'Import and play Educational Game Format (.egf) packages in a course.',
    'version'       => '1.0.0',
    'author'        => 'Hervé Yvis',
    'plugin_class'  => 'EGFGamesPlugin',
];

if ( is_file(__DIR__.'/EGFGamesPlugin.php') )
{
    require_once __DIR__.'/EGFGamesPlugin.php';

    if ( class_exists('EGFGamesPlugin') )
    {
        try
        {
            $plugin                         = EGFGamesPlugin::create();
            $plugin_info                    = array_merge($plugin_info, $plugin->get_info());
            $plugin_info['title']           = $plugin->getToolTitle();
            $plugin_info['comment']         = $plugin->get_lang('plugin_comment');
            $plugin_info['plugin_class']    = 'EGFGamesPlugin';
        }
        
        catch (Throwable $e)
        {
            // Fallback: static $plugin_info is enough for the admin plugins screen.
        }
    }
}
