<?php

use Example\Addons\Authentication\Model\ModuleConfig;
use Example\Addons\Authentication\Model\UserStatus;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/vendor/autoload.php';

$force = (boolean)ModuleConfig::getConfig("authentication", "force");
if (!$force) {
    return;
}
// 如果开启强制验证，现在就可以开始工作了

add_hook("ShoppingCartCheckoutOutput", 1, function ($vars) {
    $userStatus = UserStatus::find($_SESSION["uid"]);
    $url = explode('m=', $_SERVER['REQUEST_URI']);
    //print_r($url[1]);die();
    if ($url[1] != "authentication") {
        if (!$userStatus->cert_status) {
            // 给我冲去跳转
            header("Location: /index.php?m=authentication#/?popup=true");
        }
    }
});