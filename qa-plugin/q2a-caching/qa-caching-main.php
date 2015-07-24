<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

/**
 * q2a Caching Plugin
 * Caches all pages for unregistered users.
 * @author Vadim Kr. + sama55
 * @copyright (c) 2015 bndr + sama55
 * @license http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
define('QA_CACHING_DIR', QA_BASE_DIR . 'qa-cache'); //Cache Directory
define('QA_CACHING_DIR_MOBILE', QA_BASE_DIR . 'qa-cache/mobile'); //Cache Directory for mobile
define('QA_CACHING_STATUS', (int) qa_opt('qa_caching_enabled')); // "1" - Turned On, "0" - Turned off
define('QA_CACHING_EXCLUDED_REQUESTS', qa_opt('qa_caching_excluded_requests')); //Excluded cache entries
define('QA_CACHING_EXPIRATION_TIME', (int) qa_opt('qa_caching_expiration_time')); //Cache Expiration In seconds
define('QA_CACHING_EXPIRATION_EVENTS', qa_opt('qa_caching_expiration_events')); //Cache Expiration events
define('QA_CACHING_COMPRESS', (int) qa_opt('qa_caching_compress')); //Compressed cache
define('QA_CACHING_DEBUG', (int) qa_opt('qa_caching_debug')); //Output debug infomation

class qa_caching_main {
    protected $is_logged_in, $cache_file, $html, $debug, $timer;
    /**
     * Function that is called at page initialization
     */
    function init_page() {
        $this->is_logged_in = qa_get_logged_in_userid();
        $this->timer = microtime(true);
        $this->cache_file = $this->get_filename();
        if($this->should_clear_caching()) {
            $this->clear_cache();
        }
        if ($this->check_cache() && $this->do_caching()) {
            $this->get_cache();
        } else if ($this->do_caching()) {
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
        if ($this->do_caching() && !$this->is_logged_in && !$this->check_cache()) {
            if(QA_CACHING_COMPRESS) {
                $this->html = $this->compress_html(ob_get_contents());
            } else {
                $this->html = ob_get_contents();
            }
            if(strpos($this->html, qa_lang_html('main/page_not_found')) !== false) { // if 404 page
                return;
            }
            if (QA_DEBUG_PERFORMANCE) {
                $endtag = '</html>';
                $rpos = strrpos($this->html, $endtag);
                if($rpos !== false) {
                    $this->html = substr($this->html, 0, $rpos+strlen($endtag));
                }
            }
            $total_time = number_format(microtime(true) - $this->timer, 4, ".", "");
            $this->debug .= "\n<!-- ++++++++++++CACHED VERSION++++++++++++++++++\n";
            $this->debug .= "Created on " . date('Y-m-d H:i:s') . "\n";
            $this->debug .= "Generated in " . $total_time . " seconds\n";
            $this->debug .= "++++++++++++CACHED VERSION++++++++++++++++++ -->\n";
            $this->write_cache();
        }
        return;
    }
    /**
     * Writes file to cache.
     */
    private function write_cache() {
        if (!file_exists(QA_CACHING_DIR)) {
            mkdir(QA_CACHING_DIR, 0755, TRUE);
        }
        if (is_dir(QA_CACHING_DIR) && is_writable(QA_CACHING_DIR)) {
            if (qa_opt('site_theme') != qa_opt('site_theme_mobile') && !file_exists(QA_CACHING_DIR_MOBILE)) {
                mkdir(QA_CACHING_DIR_MOBILE, 0755, TRUE);
            }
            if(QA_CACHING_DEBUG) {
                $this->html .= $this->debug;
            }
            if (function_exists("sem_get") && ($mutex = @sem_get(2013, 1, 0644 | IPC_CREAT, 1)) && @sem_acquire($mutex)) {
                file_put_contents($this->cache_file, $this->html) . sem_release($mutex);
            /**/
            } else if (($mutex = @fopen($this->cache_file, "w")) && @flock($mutex, LOCK_EX)) {
                fwrite($mutex, $this->html);
                fflush($mutex);
                flock($mutex, LOCK_UN);
            }
            /**/
        }
    }
    /**
     * Decision to clear cache
     * @return boolean
     */
    private function should_clear_caching() {
       if ($this->is_logged_in) {
            if(qa_request_part(0) == 'admin') {
                if($_SERVER["REQUEST_METHOD"] == 'POST' || $_SERVER["REQUEST_METHOD"] == 'PUT') {
                   return true;
                }
            }
        }
        return false;
    }
    /**
     * Clear cache.
     */
    public function clear_cache() {
        $this->unlinkRecursive(QA_CACHING_DIR);
    }
    /**
     * Recursively delete files in specific folder.
     */
    private function unlinkRecursive($dir, $deleteFolder=false, $deleteRootToo=false) {
        if(!$dh = @opendir($dir)) {
            return;
        }
        while (false !== ($obj = readdir($dh))) {
            if($obj == '.' || $obj == '..') {
                continue;
            } else if(is_dir($obj) && !$deleteFolder) {
                continue;
            }
            if(!@unlink($dir . '/' . $obj)) {
                $this->unlinkRecursive($dir.'/'.$obj, $deleteFolder, false);
            }
        }
        closedir($dh);
        if($deleteRootToo) {
            @rmdir($dir);
        }
    }
    /**
     * Outputs cache to the user
     */
    private function get_cache() {
        global $qa_usage;
        qa_db_connect('qa_page_db_fail_handler');

        qa_page_queue_pending();
        qa_load_state();
        qa_check_login_modules();
       
        qa_check_page_clicks();

        $contents = file_get_contents($this->cache_file);

        $qa_content = array();	// Dummy contents
        $userid = qa_get_logged_in_userid();
        $questionid = qa_request_part(0);
        $cookieid = qa_cookie_get(true);
        if(is_numeric($questionid)) {
            $question = qa_db_select_with_pending(qa_db_full_post_selectspec($userid, $questionid));
            if( is_numeric($questionid)
             && qa_opt('do_count_q_views')
             && !preg_match("/^(?:POST|PUT)$/i", $_SERVER["REQUEST_METHOD"])
             && !qa_is_http_post()
             && qa_is_human_probably()
             && (
                !$question['views'] || ( // if it has more than zero views
                     (($question['lastviewip']!=qa_remote_ip_address()) || (!isset($question['lastviewip']))) // then it must be different IP from last view
                  && (($question['createip']!=qa_remote_ip_address()) || (!isset($question['createip']))) // and different IP from the creator
                  && (($question['userid']!=$userid) || (!isset($question['userid']))) // and different user from the creator
                  && (($question['cookieid']!=$cookieid) || (!isset($question['cookieid']))) // and different cookieid from the creator
                  )
                )
              )
            {
                $qa_content['inc_views_postid'] = $questionid;
            } else {
                $qa_content['inc_views_postid'] = null;
            }
            qa_do_content_stats($qa_content);
        }

        if(QA_DEBUG_PERFORMANCE) {
            ob_start();
            $qa_usage->output();
            $contents .= ob_get_contents();
            ob_end_clean();
        }

        qa_db_disconnect();

        exit($contents);
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
        if (filemtime($this->cache_file) >= strtotime("-" . QA_CACHING_EXPIRATION_TIME . " seconds")) {
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
        if(empty($this->cache_file)) {
            return false;
        }
        if(!QA_CACHING_STATUS) {
            return false;
        }
        if($this->is_logged_in) {
            return false;
        } else if (preg_match("/^(?:POST|PUT)$/i", $_SERVER["REQUEST_METHOD"])) {
            return false;
        }
        if(qa_request_part(0) == 'admin') {
            return false;
        }
        if (is_array($_COOKIE) && !empty($_COOKIE)) {
            foreach ($_COOKIE as $k => $v) {
                if (preg_match('#session#', $k) && strlen($v)) {
                    return false;
                }
                if (preg_match("#fbs_#", $k) && strlen($v)) {
                    return false;
                }
            }
        }
        $requests = QA_CACHING_EXCLUDED_REQUESTS;
        if(!empty($requests)) {
            $requests = explode(',', str_replace(array("\r\n", "\r", "\n"), '', $requests));
        } else {
            $requests = array();
        }
        if(in_array(qa_request(), $requests)) {
            return false;
        }
        return true;
    }
    /**
     * Checks if the user is allowed to be shown cache.
     * Only non-registered users see the cached version.
     * @return boolean
     */
    public function now_caching() {
        if(!QA_CACHING_STATUS) {
            return false;
        }
        if(qa_get_logged_in_userid()) {
            return false;
        }
        if(qa_request_part(0) == 'admin') {
            return false;
        }
        $requests = QA_CACHING_EXCLUDED_REQUESTS;
        if(!empty($requests)) {
            $requests = explode(',', str_replace(array("\r\n", "\r", "\n"), '', $requests));
        } else {
            $requests = array();
        }
        if(in_array(qa_request(), $requests)) {
            return false;
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
        if(!qa_is_mobile_probably() || qa_opt('site_theme') == qa_opt('site_theme_mobile')) {
            return QA_CACHING_DIR . "/" . $md5;
        } else {
            return QA_CACHING_DIR_MOBILE . "/" . $md5;
        }
    }
    /**
     * What page does the user see?
     * @return boolean
     */
    private function what_page() {
        $query = (isset($_REQUEST['qa']) && $_SERVER['REQUEST_METHOD'] == "GET") ? $_REQUEST['qa'] : FALSE;
        if (!$query) {
            return false;
        }
        return $query;
    }
/*
    private function compress_html($html) {
        $searchs = array(
            '/<!--[\s\S]*?-->/s', // remove comment
            '/\t/', // remove tab
        );
        $replaces = array(
            '',
            '',
        );
        return $this->remove_newline(preg_replace($searchs, $replaces, $html));
    }
    private function remove_newline($html) {
        $lines = explode("\n", $html);
        $lines = array_filter($lines, 'strlen');
        $html = implode("\n", $lines);
        return $html;
    }
*/
    private function compress_html($html) {
        require_once QA_PLUGIN_DIR.'q2a-caching/tools/minify/HTML.php';
        return Minify_HTML::minify($html);
    }
    /**
     * Qache settings form on the admin page.
     * @param type $qa_content
     * @return type
     */
    function option_default($option) {
        switch ($option) {
        case 'qa_caching_enabled':
            return false;
        case 'qa_caching_excluded_requests':
            return '';
        case 'qa_caching_expiration_time':
            return 3600;
        case 'qa_caching_expiration_events':
            return  'q_post,a_post,c_post'.PHP_EOL
                   //.',q_queue,a_queue,c_queue'.PHP_EOL
                   .',q_edit,a_edit,c_edit'.PHP_EOL
                   .',q_close,q_reopen'.PHP_EOL
                   .',a_select,a_unselect'.PHP_EOL
                   .',q_flag,a_flag,c_flag,q_unflag,a_unflag,c_unflag,q_clearflags,a_clearflags,c_clearflags'.PHP_EOL
                   .',q_hide,a_hide,c_hide,q_reshow,a_reshow,c_reshow'.PHP_EOL
                   //.',q_approve,a_approve,c_approve,q_reject,a_reject,c_reject'.PHP_EOL
                   .',q_requeue,a_requeue,c_requeue'.PHP_EOL
                   .',q_delete,a_delete,c_delete'.PHP_EOL
                   .',q_claim,a_claim,c_claim'.PHP_EOL
                   .',q_move'.PHP_EOL
                   .',a_to_c'.PHP_EOL
                   .',q_vote_up,q_vote_down,q_vote_nil,a_vote_up,a_vote_down,a_vote_nil'.PHP_EOL
                   .',q_favorite,q_unfavorite'.PHP_EOL
                   .',u_register'.PHP_EOL
                   //.',u_login,u_logout'.PHP_EOL
                   //.',u_confirmed'.PHP_EOL
                   //.',u_reset'.PHP_EOL
                   //.',u_save'.PHP_EOL
                   //.',u_password'.PHP_EOL
                   .',u_edit'.PHP_EOL
                   //.',u_message'.PHP_EOL
                   //.',u_wall_post'.PHP_EOL
                   //.',u_wall_delete'.PHP_EOL
                   .',u_level'.PHP_EOL
                   //.',u_block,u_unblock'.PHP_EOL
                   .',u_delete'.PHP_EOL
                   //.',u_favorite,u_unfavorite'.PHP_EOL
                   //.',ip_block,ip_unblock'.PHP_EOL
                   //.',tag_favorite,tag_unfavorite'.PHP_EOL
                   //.',cat_favorite,cat_unfavorite'.PHP_EOL
                   //.',feedback'.PHP_EOL
                   //.',search'.PHP_EOL
                   ;
        case 'qa_caching_compress':
            return false;
        case 'qa_caching_debug':
            return true;
        }
    }
    function admin_form(&$qa_content) {
        $saved = false;
        if (qa_clicked('qa_caching_submit_button')) {
            qa_opt('qa_caching_enabled', (int) qa_post_text('qa_caching_enabled'.'_field'));
            qa_opt('qa_caching_excluded_requests', qa_post_text('qa_caching_excluded_requests'.'_field'));
            qa_opt('qa_caching_expiration_time', (int) qa_post_text('qa_caching_expiration_time'.'_field'));
            qa_opt('qa_caching_expiration_events', qa_post_text('qa_caching_expiration_events'.'_field'));
            qa_opt('qa_caching_compress', (int) qa_post_text('qa_caching_compress'.'_field'));
            qa_opt('qa_caching_debug', (int) qa_post_text('qa_caching_debug'.'_field'));
            $saved = true;
            $msg = 'Caching settings saved';
        }
        if (qa_clicked('qa_caching_reset_button')) {
            qa_opt('qa_caching_enabled', (int) $this->option_default('qa_caching_enabled'));
            qa_opt('qa_caching_excluded_requests', $this->option_default('qa_caching_excluded_requests'));
            qa_opt('qa_caching_expiration_time', (int) $this->option_default('qa_caching_expiration_time'));
            qa_opt('qa_caching_expiration_events', $this->option_default('qa_caching_expiration_events'));
            qa_opt('qa_caching_compress', (int) $this->option_default('qa_caching_compress'));
            qa_opt('qa_caching_debug', (int) $this->option_default('qa_caching_debug'));
            $saved = true;
            $msg = 'Caching settings reset';
        }
        if (qa_clicked('qa_caching_clear_cache')) {
            $this->clear_cache();
        }
        $rules = array();
        $rules['qa_caching_excluded_requests'] = 'qa_caching_enabled_field';
        $rules['qa_caching_expiration_time'] = 'qa_caching_enabled_field';
        $rules['qa_caching_expiration_events'] = 'qa_caching_enabled_field';
        $rules['qa_caching_compress'] = 'qa_caching_enabled_field';
        $rules['qa_caching_debug'] = 'qa_caching_enabled_field';
        qa_set_display_rules($qa_content, $rules);
        return array(
            'ok' => $saved ? $msg : null,
            'fields' => array(
                array(
                    'label' => 'Enable cache:',
                    'type' => 'checkbox',
                    'value' => (int) qa_opt('qa_caching_enabled'),
                    'tags' => 'NAME="qa_caching_enabled_field" id="qa_caching_enabled_field"',
                ),
                array(
                    'id' => 'qa_caching_excluded_requests',
                    'label' => 'Excluded requests: (Comma-separated)',
                    'type' => 'textarea',
                    'rows' => 5,
                    'value' => qa_opt('qa_caching_excluded_requests'),
                    'tags' => 'NAME="qa_caching_excluded_requests_field"',
                ),
                array(
                    'id' => 'qa_caching_expiration_time',
                    'label' => 'Expiration time:',
                    'type' => 'number',
                    'value' => (qa_opt('qa_caching_expiration_time')) ? ((int) qa_opt('qa_caching_expiration_time')) : 3600,
                    'suffix' => 'seconds',
                    'tags' => 'NAME="qa_caching_expiration_time_field"'
                ),
                array(
                    'id' => 'qa_caching_expiration_events',
                    'label' => 'Expiration events: (Comma-separated)',
                    'type' => 'textarea',
                    'rows' => 20,
                    'value' => qa_opt('qa_caching_expiration_events'),
                    'tags' => 'NAME="qa_caching_expiration_events_field"',
                ),
                array(
                    'id' => 'qa_caching_compress',
                    'label' => 'Compress cache:',
                    'type' => 'checkbox',
                    'value' => (int) qa_opt('qa_caching_compress'),
                    'tags' => 'NAME="qa_caching_compress_field"',
                ),
                array(
                    'id' => 'qa_caching_debug',
                    'label' => 'Output debug comment:',
                    'type' => 'checkbox',
                    'value' => (int) qa_opt('qa_caching_debug'),
                    'tags' => 'NAME="qa_caching_debug_field"',
                ),
            ),
            'buttons' => array(
                array(
                    'label' => 'Save Changes',
                    'tags' => 'NAME="qa_caching_submit_button"',
                ),
                array(
                    'label' => 'Reset to Defaults',
                    'tags' => 'NAME="qa_caching_reset_button"',
                ),
                array(
                    'label' => 'Clear cache',
                    'tags' => 'NAME="qa_caching_clear_cache"',
                ),
            ),
        );
    }
}
