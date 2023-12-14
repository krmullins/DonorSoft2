<?php
	define('PREPEND_PATH', '../../');
	define('APP_PATH', __DIR__ . '/' . PREPEND_PATH);
	require(APP_PATH . 'lib.php');
	require(__DIR__ . '/DataTalk.php');
     
    /* grant access to the groups 'Admins' */
    $group = getMemberInfo()['group'] ?? '';
    if(!in_array($group, ['Admins']))
		die("Access denied");

	
	DataTalk::route();

