<?php

/*
  Plugin Name: q2a-caching
  Plugin URI: https://github.com/sama55/q2a-caching
  Plugin Description: Question2Answer Caching plugin
  Plugin Version: 0.2
  Plugin Date: 2015-07-17
  Plugin Author: Vadim Kr. bndr + sama55
  Plugin License: http://creativecommons.org/licenses/by-sa/3.0/legalcode
  Plugin Minimum Question2Answer Version: 1.5
 */

if (!defined('QA_VERSION'))
{ // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

/**
 * Include the caching for registered users
 */
include_once(dirname(__FILE__).'/qa-caching-registered.php');

/**
 * Register the plugin
 */
qa_register_plugin_module(
        'process', // type of module
        'qa-caching-main.php', // PHP file containing module class
        'qa_caching_main', // module class name in that PHP file
        'q2a Caching Plugin' // human-readable name of module
);
qa_register_plugin_module(
        'event', // type of module
        'qa-caching-event.php', // PHP file containing module class
        'qa_caching_event', // module class name in that PHP file
        'q2a Caching Plugin Event Handler' // human-readable name of module
);

