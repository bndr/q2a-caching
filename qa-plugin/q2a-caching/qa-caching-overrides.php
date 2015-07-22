<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

/*
Return whether $value matches the expected form security (anti-CSRF protection) code for $action (any string) and
that the code has not expired (if more than QA_FORM_EXPIRY_SECS have passed). Logs causes for suspicion.
*/
function qa_check_form_security_code($action, $value) {
    $main = new qa_caching_main;
    if($main->now_caching()) {
        $reportproblems=array();
        $silentproblems=array();
        if (!isset($value)) {
            $silentproblems[]='code missing';
        } else if (!strlen($value)) {
            $silentproblems[]='code empty';
        } else {
            if (empty($silentproblems) && empty($reportproblems)) {
                if($value != session_id()) {
                    $reportproblems[]='code mismatch';
                }
            }
        }
        if (count($reportproblems)) {
            @error_log(
                'PHP Question2Answer form security violation for '.$action.
                ' by '.(qa_is_logged_in() ? ('userid '.qa_get_logged_in_userid()) : 'anonymous').
                ' ('.implode(', ', array_merge($reportproblems, $silentproblems)).')'.
                ' on '.@$_SERVER['REQUEST_URI'].
                ' via '.@$_SERVER['HTTP_REFERER']
            );
        }
        return (empty($silentproblems) && empty($reportproblems));
    } else {
        return qa_check_form_security_code_base($action, $value);
    }
}

/*
	Omit PHP closing tag to help avoid accidental output
*/
