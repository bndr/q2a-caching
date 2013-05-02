<?php

/**
 * q2a Caching Plugin
 * Caches all pages for unregistered users.
 * @author Vadim Kr.
 * @copyright (c) 2013 bndr
 * @license http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */

define('CACHE_STATUS', (int) qa_opt('plugin_qa_caching_on_off')); // "1" - Turned On, "0" - Turned off
define('CACHE_DIR', QA_BASE_DIR . 'qa-cache'); //Cache Directory
define('CACHE_EXPIRATION', 7200); //Cache Expiration In seconds

class qa_caching_main
{

    protected $is_logged_in, $cache_file, $html, $debug;

    /**
     * Constructor
     */
    function __construct()
    {
        $this->is_logged_in = qa_get_logged_in_userid();
    }

    /**
     * Function that is called at page initialization
     */
    function init_page()
    {
        $this->cache_file = $this->get_filename();

        if (CACHE_STATUS && $this->check_cache() && $this->do_caching())
        {
            $this->get_cache();
        }
        else
        {
            ob_start();
        }
    }

    /**
     * Function that is called at the end of page rendering
     * @param type $reason
     * @return type
     */
    function shutdown( $reason = false )
    {
        if (CACHE_STATUS && $this->do_caching() && !$this->is_logged_in && !$this->check_cache())
        {
            $this->html = ob_get_contents();
            $this->debug .= "++++++++++++CACHED VERSION++++++++++++++++++";
            $this->write_cache();
        }
        return;
    }

    /**
     * Writes file to cache.
     */
    private function write_cache()
    {
        if (!file_exists(CACHE_DIR))
            mkdir(CACHE_DIR, 0755, TRUE);

        if (is_dir(CACHE_DIR) && is_writable(CACHE_DIR))
        {
            if (function_exists("sem_get") && ($mutex = @sem_get(1976, 1, 0644 | IPC_CREAT, 1)) && @sem_acquire($mutex))
                file_put_contents($this->cache_file, $this->html . $this->debug) . sem_release($mutex);
            /**/
            else if (($mutex = @fopen($this->cachefile, "w")) && @flock($mutex, LOCK_EX))
                file_put_contents($this->cache_file, $this->html . $this->debug) . flock($mutex, LOCK_UN);
            /**/
        }
    }

    /**
     * Outputs cache to the user
     */
    private function get_cache()
    {
        exit(file_get_contents($this->cache_file));
    }

    /**
     * Checks if cache exists
     * 
     * @return boolean
     */
    private function check_cache()
    {
        if (!file_exists($this->cache_file))
        {
            return false;
        }
        if (filemtime($this->cache_file) >= strtotime("-" . CACHE_EXPIRATION . " seconds"))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Checks if the user is allowed to be shown cache.
     * Only non-registered users see the cached version.
     * @return boolean
     */
    private function do_caching()
    {
        if ($this->is_logged_in)
        {
            return false;
        }
        if (preg_match("/\/(?:ask|)\.php/", $_SERVER["REQUEST_URI"]))
        {
            return false;
        }
        if (is_array($_COOKIE) && !empty($_COOKIE))
        {
            foreach ($_COOKIE as $k => $v)
            {
                if (preg_match('#session#', $k) && strlen($v))
                    return false;
            }
        }
        return true;
    }

    /**
     * @TODO: Set the same header for html pages
     * @param type $headers
     */
    private function set_headers( $headers )
    {

        $headers = headers_list();
    }

    /**
     * Returns a unique filepath+filename to store the cache.
     * @return type
     */
    private function get_filename()
    {
        $md5_1 = md5($_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
        $md5_2 = md5(preg_replace("/\:[0-9]+$/", "", $_SERVER["HTTP_HOST"]) . $_SERVER["REQUEST_URI"]);
        return CACHE_DIR . "/" . $md5_1 . "-" . $md5_2;
    }

    /**
     * Qache settings form on the admin page.
     * @param type $qa_content
     * @return type
     */
    function admin_form( &$qa_content )
    {
        $saved = false;

        if (qa_clicked('plugin_qa_caching_submit_button'))
        {
            qa_opt('plugin_qa_caching_on_off', (int) qa_post_text('plugin_qa_caching_on_off'));
            $saved = true;
        }

        return array(
            'ok'      => $saved ? 'Caching settings saved' : null,
            'fields'  => array(
                array(
                    'label' => 'Turn the caching On:',
                    'type'  => 'checkbox',
                    'value' => (int) qa_opt('plugin_qa_caching_on_off'),
                    'tags'  => 'NAME="plugin_qa_caching_on_off"',
                )
            ),
            'buttons' => array(
                array(
                    'label' => 'Save Changes',
                    'tags'  => 'NAME="plugin_qa_caching_submit_button"',
                ),
            ),
        );
    }

}