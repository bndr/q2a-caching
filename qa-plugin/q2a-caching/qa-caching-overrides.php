<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

/*
Return the user identification cookie sent by the browser for this page request, or null if none
*/
function qa_cookie_get() {
    $main = new qa_caching_main;
    if($main->now_caching()) {
        return null;
    }
    return qa_cookie_get_base();
}

/*
    Omit PHP closing tag to help avoid accidental output
*/
