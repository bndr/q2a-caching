<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
require_once QA_PLUGIN_DIR.'q2a-caching/qa-caching-main.php';

class qa_html_theme_layer extends qa_html_theme_base {
    public function doctype() {
        if(QA_CACHING_STATUS && !qa_is_logged_in()) {
            if(isset($this->content['notices']))
                unset($this->content['notices']);
        }
        qa_html_theme_base::doctype();
    }
    public function head_script() {
        qa_html_theme_base::head_script();
        if(QA_CACHING_STATUS && !qa_is_logged_in()) {
            $this->output('<script src="'.qa_path('qa-plugin/q2a-caching/js/qa-caching.js', null, null, QA_URL_FORMAT_NEAT).'"></script>');
			
        }
    }
}

/*
	Omit PHP closing tag to help avoid accidental output
*/
