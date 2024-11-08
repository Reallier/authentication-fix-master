<?php

include_once(__DIR__ . DS . 'vendor' . DS . 'autoload.php');

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCS\Config\Setting;
use Example\Addons\Authentication\Dispatcher\ApiDispatcher;
use Example\Addons\Authentication\Model\ModuleConfig;

function authentication_config()
{
//    $appId = ModuleConfig::getConfig("authentication", "app_id");
    // 返回结果
    $configarray = [
        'name'        => 'Authentication',
        'description' => '更好的实名验证',
        'version'     => '1.4',
        'author'      => "",
        'fields'      => [
            "app_id"               => [
                "FriendlyName" => "应用ID",
                "Type"         => "text",
                "Size"         => "25",
            ],
            "merchant_private_key" => [
                "FriendlyName" => "商户私钥 (SHA2私钥)",
                'Type'         => 'textarea',
                'Rows'         => '9',
                'Description'  => "",
            ],
            "alipay_public_key"    => [
                "FriendlyName" => "支付宝公钥 (SHA2公钥)",
                'Type'         => 'textarea',
                'Rows'         => '9',
                'Description'  => "",
            ],
            "qcloud_api_key"       => [
                "FriendlyName" => "腾讯云 API Key",
                'Type'         => 'text',
                'Description'  => "",
            ],
            "qcloud_api_secret"    => [
                "FriendlyName" => "腾讯云 API Secret",
                'Type'         => 'text',
                'Description'  => "",
            ],
            "sms_app_id"           => [
                "FriendlyName" => "腾讯云短信 APP ID",
                'Type'         => 'text',
                'Description'  => "",
            ],
            "sms_signature"        => [
                "FriendlyName" => "腾讯云短信签名",
                'Type'         => 'text',
                'Description'  => "",
            ],
            "sms_template_id"      => [
                "FriendlyName" => "腾讯云短信模板 ID",
                'Type'         => 'text',
                'Description'  => "",
            ],
            "force_verify"         => [
                'FriendlyName' => '强制认证',
                'Type'         => 'yesno',
                'Description'  => '是否开启强制实名认证。',
            ],
            "auth_method"          => [
                "FriendlyName" => "验证方式",
                "Type"         => "dropdown",
                "Options"      =>
                    "FACE,CERT_PHOTO,CERT_PHOTO_FACE,SMART_FACE",
                "Description"  => "<br>FACE：多因子人脸认证<br>CERT_PHOTO：多因子证照认证<br>CERT_PHOTO_FACE ：多因子证照和人脸认证<br>SMART_FACE：多因子快捷认证",
                "Default"      => "3",
            ],
            "debug"                => [
                'FriendlyName' => '调试模式',
                'Type'         => 'yesno',
                'Description'  => '仅限开发使用',
            ],      
            "force"                => [
                'FriendlyName' => '强制实名认证',
                'Type'         => 'yesno',
                'Description'  => '打开后即可强制用户实名',
            ],
        ]
    ];


    return $configarray;
}

function authentication_activate()
{
    // 存放验证信息
    try {
        if (!Capsule::schema()->hasTable('auth_plus_status')) {
            Capsule::schema()->create('auth_plus_status', function ($table) {
                $table->id();
                $table->integer('uid')->unique();
                // 身份证认证状态
                $table->boolean('cert_status')->default(0);
                // 手机号验证状态
                $table->boolean('phone_status')->default(0);
                $table->timestamps();
            });
        }
    } catch (Exception $e) {
        return [
            'status'      => 'error',
            'description' => '不能创建表 auth_plus_status: ' . $e->getMessage()
        ];
    }
    // 身份证验证表
    try {
        if (!Capsule::schema()->hasTable('auth_plus_cert')) {
            Capsule::schema()->create('auth_plus_cert', function ($table) {
                $table->id();
                $table->integer('uid')->unique();
                // 支付宝验证流水号
                $table->text('out_trade_no')->nullable();
                $table->dateTime('date')->default('0000-00-00 00:00:00');
                // 姓名
                $table->text('cert_name')->nullable();
                // 身份证号
                $table->text('cert_no')->nullable();
                // 支付宝返回的身份而验证 ID 用于后续查询或继续验证
                $table->text('certify_id')->nullable();
                $table->timestamps();
            });
        }
    } catch (Exception $e) {
        return [
            'status'      => 'error',
            'description' => '不能创建表 auth_plus_biz: ' . $e->getMessage()
        ];
    }
    // 手机验证表
    try {
        if (!Capsule::schema()->hasTable('auth_plus_phone')) {
            Capsule::schema()->create('auth_plus_phone', function ($table) {
                $table->id();
                $table->integer('uid')->unique();
                // 记录手机号
                $table->text('phone_no')->nullable();
                // 记录短信发送时间
                $table->timestamp('code_send_at')->nullable();
                // 记录验证码用
                $table->text('phone_validation_code')->nullable();
                $table->timestamps();
            });
        }
    } catch (Exception $e) {
        return [
            'status'      => 'error',
            'description' => '不能创建表 auth_plus_phone: ' . $e->getMessage()
        ];
    }

    return [
        'status'      => 'success',
        'description' => '模块激活成功. 点击 配置 对模块进行设置。'
    ];
}

function authentication_deactive()
{
}

function authentication_clientarea($vars)
{
    $modulelink = $vars['modulelink'];
    if (isset($_GET['type'])) {
        switch ($_GET['type']) {
            case'api':
                $apiDispatcher = new ApiDispatcher();
                return [];
        }
    }
    // 准备正式渲染内容
    $debug = (boolean)ModuleConfig::getConfig("authentication", "debug");
    // 首先读取 manifest.json
    $manifest = json_decode(file_get_contents(__DIR__ . DS . "clientarea-dist/dist/server/client.manifest.json"),
        true,
        512,
        JSON_THROW_ON_ERROR);
    // 遍历数组,找到 isEntry 为 true 的文件
    $entry = "";
    foreach ($manifest as $key => $value) {
        if ($value["isEntry"]) {
            $entry = $key;
            break;
        }
    }
    // 然后提取对应的 JS 和 CSS 文件名
    $mainJSFile = $manifest[$entry]["file"];
    $mainCSSFile = $manifest[$entry]["css"][0];

    // 获取 WHMCS 系统 URL
    $url = Setting::getValue('SystemURL');
    $assetsUrl = "${url}/modules/addons/${GLOBALS["m"]}";
    if ($debug) {
        // 调试模式下采用动态加载 Nuxt 地址
        $templateFile = "templates/clientarea-nuxt-dev";
    } else {
        $templateFile = "templates/clientarea-dist";
    }
    return [
        'pagetitle'    => '实名认证',
        'breadcrumb'   => array('index.php?m=authentication' => '实名认证'),
        //        'templatefile' => 'templates/clientarea-nuxt-dev',
        //        'templatefile' => 'templates/clientarea-dist',
        "templatefile" => $templateFile,
        'requirelogin' => true, # accepts true/false
        'forcessl'     => false, # accepts true/false
        "vars"         => [
            "devPath"     => __DIR__,
            'assetsUrl'   => $assetsUrl,
            'mainJSFile'  => $mainJSFile,
            'mainCSSFile' => $mainCSSFile,
        ]
    ];
}