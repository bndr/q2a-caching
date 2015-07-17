<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}
require_once QA_PLUGIN_DIR.'q2a-caching/qa-caching-main.php';

class qa_caching_event {
    function process_event ($event, $userid, $handle, $cookieid, $params) {
        $events = QA_CACHING_EXPIRATION_EVENTS;
        $events = explode(',', str_replace(array("\r\n", "\r", "\n", " "), '', $events));
        if(in_array($event, $events)) {
			$main = new qa_caching_main;
            $main->unlinkRecursive(QA_CACHING_DIR);
        }
    }
}

/*
	Omit PHP closing tag to help avoid accidental output
*/
