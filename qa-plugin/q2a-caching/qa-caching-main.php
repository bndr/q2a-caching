<?php

/**
 * q2a Caching Plugin
 * Caches all pages for unregistered users.
 * @author Vadim Kr. + sama55
 * @copyright (c) 2013 bndr
 * @license http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
define('CACHE_STATUS', (int) qa_opt('plugin_qa_caching_on_off')); // "1" - Turned On, "0" - Turned off
define('CACHE_DIR', QA_BASE_DIR . 'qa-cache'); //Cache Directory
define('CACHE_EXPIRATION', (int) qa_opt('plugin_qa_caching_expiration')); //Cache Expiration In seconds

class qa_caching_main {

    protected $is_logged_in, $cache_file, $html, $debug, $timer;
    protected $allowed_pages = array(
        'question',
        'tags',
        'users',
        'unanswered');

    /**
     * Function that is called at page initialization
     */
    function init_page() {

        $this->is_logged_in = qa_get_logged_in_userid();
        $this->timer = microtime(true);
        $this->cache_file = $this->get_filename();

        if (CACHE_STATUS && $this->check_cache() && $this->do_caching()) {
            $this->get_cache();
        } else if (CACHE_STATUS && $this->do_caching()) {
            ob_start();
        } else {
            return;
        }
    }

    /**
     * Function that is called at the end of page rendering
     * @param type $reason
     * @return type
     */
    function shutdown($reason = false) {
        if (CACHE_STATUS && $this->do_caching() && !$this->is_logged_in && !$this->check_cache()) {
            if(qa_opt('plugin_qa_compress_on_off'))
                $this->html = $this->compress_html(ob_get_contents());
            else
                $this->html = ob_get_contents();
            $total_time = number_format(microtime(true) - $this->timer, 4, ".", "");
            $this->debug .= "\n<!-- ++++++++++++CACHED VERSION++++++++++++++++++\n";
            $this->debug .= "Created on " . date('Y-m-d H:i:s') . "\n";
            $this->debug .= "Generated in " . $total_time . " seconds\n";
            $this->debug .= "++++++++++++CACHED VERSION++++++++++++++++++ -->";
            $this->write_cache();
        }
        return;
    }

    /**
     * Writes file to cache.
     */
    private function write_cache() {
        if (!file_exists(CACHE_DIR))
            mkdir(CACHE_DIR, 0755, TRUE);

        if (is_dir(CACHE_DIR) && is_writable(CACHE_DIR)) {
            if(qa_opt('plugin_qa_debug_on_off'))
                $this->html .= $this->debug;
            if (function_exists("sem_get") && ($mutex = @sem_get(2013, 1, 0644 | IPC_CREAT, 1)) && @sem_acquire($mutex))
                file_put_contents($this->cache_file, $this->html) . sem_release($mutex);
            /**/
            else if (($mutex = @fopen($this->cache_file, "w")) && @flock($mutex, LOCK_EX)) {
                fwrite($mutex, $this->html);
                fflush($mutex);
                flock($mutex, LOCK_UN);
            }
            /**/
        }
    }

    /**
     * Outputs cache to the user
     */
    private function get_cache() {
        exit(file_get_contents($this->cache_file));
    }

    /**
     * Checks if cache exists
     * 
     * @return boolean
     */
    private function check_cache() {
        if (!file_exists($this->cache_file)) {
            return false;
        }
        if (filemtime($this->cache_file) >= strtotime("-" . CACHE_EXPIRATION . " seconds")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the user is allowed to be shown cache.
     * Only non-registered users see the cached version.
     * @return boolean
     */
    private function do_caching() {
        if ($this->is_logged_in) {
            return false;
        }
        //Dont cache the request if it's either POST or PUT
        else if (preg_match("/^(?:POST|PUT)$/i", $_SERVER["REQUEST_METHOD"])) {
            return false;
        }
        if (preg_match("#register#", $_SERVER["REQUEST_URI"])) {
            return false;
        }
        if (preg_match("#login#", $_SERVER["REQUEST_URI"])) {
            return false;
        }
        if (is_array($_COOKIE) && !empty($_COOKIE)) {
            foreach ($_COOKIE as $k => $v) {
                if (preg_match('#session#', $k) && strlen($v))
                    return false;
                if (preg_match("#fbs_#", $k) && strlen($v))
                    return false;
            }
        }
        return true;
    }

    /**
     * @TODO: Set the same header for html pages
     * @param type $headers
     */
    private function set_headers($headers) {

        $headers = headers_list();
    }

    /**
     * Returns a unique filepath+filename to store the cache.
     * @return type
     */
    private function get_filename() {
    
        $md5 = md5($_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
        return CACHE_DIR . "/" . $md5;
    }

    /**
     * What page does the user see?
     * @return boolean
     */
    private function what_page() {
        $query = (isset($_REQUEST['qa']) && $_SERVER['REQUEST_METHOD'] == "GET") ? $_REQUEST['qa'] : FALSE;
        if (!$query)
            return false;

        return $query;
    }

    private function compress_html($html) {

        $search = array(
            '/\n/', // replace end of line by a space
            '/\>[^\S ]+/s', // strip whitespaces after tags, except space
            '/[^\S ]+\</s', // strip whitespaces before tags, except space
            '/(\s)+/s'  // shorten multiple whitespace sequences
        );

        $replace = array(
            ' ',
            '>',
            '<',
            '\\1'
        );

        return preg_replace($search, $replace, $html);
    }

    /**
     * Qache settings form on the admin page.
     * @param type $qa_content
     * @return type
     */
	function option_default($option) {
		switch ($option) {
		case 'plugin_qa_caching_on_off':
			return false;
		case 'plugin_qa_caching_expiration':
			return 7200;
		case 'plugin_qa_compress_on_off':
			return false;
		}
	}
    function admin_form(&$qa_content) {
        $saved = false;

        if (qa_clicked('plugin_qa_caching_submit_button')) {
            qa_opt('plugin_qa_caching_on_off', (int) qa_post_text('plugin_qa_caching_on_off'));
            qa_opt('plugin_qa_caching_expiration', (int) qa_post_text('plugin_qa_caching_expiration'));
            qa_opt('plugin_qa_compress_on_off', (int) qa_post_text('plugin_qa_compress_on_off'));
            qa_opt('plugin_qa_debug_on_off', (int) qa_post_text('plugin_qa_debug_on_off'));
            $saved = true;
            $msg = 'Caching settings saved';
        }
        if (qa_clicked('plugin_qa_caching_reset_button')) {
            qa_opt('plugin_qa_caching_on_off', (int) $this->option_default('plugin_qa_caching_on_off'));
            qa_opt('plugin_qa_caching_expiration', (int) $this->option_default('plugin_qa_caching_expiration'));
            qa_opt('plugin_qa_compress_on_off', (int) $this->option_default('plugin_qa_compress_on_off'));
            qa_opt('plugin_qa_debug_on_off', (int) $this->option_default('plugin_qa_debug_on_off'));
            $saved = true;
            $msg = 'Caching settings reset';
        }

        return array(
            'ok' => $saved ? $msg : null,
            'fields' => array(
                array(
                    'label' => 'Turn the caching On or Off:',
                    'type' => 'checkbox',
                    'value' => (int) qa_opt('plugin_qa_caching_on_off'),
                    'tags' => 'NAME="plugin_qa_caching_on_off"',
                ),
                array(
                    'label' => 'Cache expiration:',
                    'type' => 'number',
                    'value' => (qa_opt('plugin_qa_caching_expiration')) ? ((int) qa_opt('plugin_qa_caching_expiration')) : 7200,
                    'suffix' => 'seconds',
                    'tags' => 'NAME="plugin_qa_caching_expiration"'
                ),
                array(
                    'label' => 'Turn the compress On or Off:',
                    'type' => 'checkbox',
                    'value' => (int) qa_opt('plugin_qa_compress_on_off'),
                    'tags' => 'NAME="plugin_qa_compress_on_off"',
                ),
                array(
                    'label' => 'Turn the debug On or Off:',
                    'type' => 'checkbox',
                    'value' => (int) qa_opt('plugin_qa_debug_on_off'),
                    'tags' => 'NAME="plugin_qa_debug_on_off"',
                ),
            ),
            'buttons' => array(
                array(
                    'label' => 'Save Changes',
                    'tags' => 'NAME="plugin_qa_caching_submit_button"',
                ),
                array(
                    'label' => 'Reset to Defaults',
                    'tags' => 'NAME="plugin_qa_caching_reset_button"',
                ),
            ),
        );
    }

}
