<?php

/*
  Plugin Name: q2a-caching
  Plugin URI: https://github.com/bndr/q2a-caching
  Plugin Description: Question2Answer Caching plugin
  Plugin Version: 0.1
  Plugin Date: 2013-05-02
  Plugin Author: Vadim Kr. bndr
  Plugin License: http://creativecommons.org/licenses/by-sa/3.0/legalcode
  Plugin Minimum Question2Answer Version: 1.5
 */

if (!defined('QA_VERSION'))
{ // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}
/**
 * Register the plugin
 */
qa_register_plugin_module(
        'process', // type of module
        'qa-caching-main.php', // PHP file containing module class
        'qa_caching_main', // module class name in that PHP file
        'q2a Caching Plugin' // human-readable name of module
);

qa_opt('plugin_qa_caching_on_off', 1);
qa_opt('plugin_qa_caching_expiration', 7200);