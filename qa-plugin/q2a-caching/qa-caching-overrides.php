<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

/*
Return the full form security (anti-CSRF protection) code for an $action (any string) performed within
QA_FORM_EXPIRY_SECS of now by the current user.
*/
/* This is unnecessary
function qa_get_form_security_code($action) {
    $main = new qa_caching_main;
    if($main->now_caching()) {
		qa_set_form_security_key();
        $timestamp = session_id();
        return (int)qa_is_logged_in().'-'.$timestamp.'-'.qa_calc_form_security_hash($action, $timestamp);
    } else {
        return qa_get_form_security_code_base($action);
    }
}
 This is unnecessary */
/*
Return whether $value matches the expected form security (anti-CSRF protection) code for $action (any string) and
that the code has not expired (if more than QA_FORM_EXPIRY_SECS have passed). Logs causes for suspicion.
*/
/* This is unnecessary
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
            $parts=explode('-', $value);
            if (count($parts)==3) {
                $loggedin=$parts[0];
                $timestamp=$parts[1];
                $hash=$parts[2];
                //$timenow=qa_opt('db_time');
                //if ($timestamp>$timenow)
                //    $reportproblems[]='time '.($timestamp-$timenow).'s in future';
                //elseif ($timestamp<($timenow-QA_FORM_EXPIRY_SECS))
                //    $silentproblems[]='timeout after '.($timenow-$timestamp).'s';
                if ($timestamp != session_id())
                    $reportproblems[]='session mismatch';

                if (qa_is_logged_in()) {
                    if (!$loggedin)
                        $silentproblems[]='now logged in';
                } else {
                    if ($loggedin)
                        $silentproblems[]='now logged out';
                    else {
                        $key=@$_COOKIE['qa_key'];
                        if (!isset($key))
                            $silentproblems[]='key cookie missing';
                        elseif (!strlen($key))
                            $silentproblems[]='key cookie empty';
                        elseif (strlen($key)!=QA_FORM_KEY_LENGTH)
                            $reportproblems[]='key cookie '.$key.' invalid';
                    }
                }
                if (empty($silentproblems) && empty($reportproblems))
                    if (strtolower(qa_calc_form_security_hash($action, $timestamp))!=strtolower($hash))
                        $reportproblems[]='code mismatch';
            } else {
                $reportproblems[]='code '.$value.' malformed';
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
 This is unnecessary */

/*
	Omit PHP closing tag to help avoid accidental output
*/
