<?php

if (!function_exists("get_app_path")) {
    function get_app_path()
    {
        return realpath(dirname(__FILE__) . '/../..');
    }
}

$app_path = get_app_path();

@include("{$app_path}/defaultLang.php");
@include("{$app_path}/language.php");
@include("{$app_path}/lib.php");
@include("{$app_path}/plugins/plugins-resources/AppGiniPlugin.php");
@include("{$app_path}/plugins/plugins-resources/ProgressLog.php");
