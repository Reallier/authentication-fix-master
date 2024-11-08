<?php

namespace Example\Addons\Authentication\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Sms\V20210111\Models\SendSmsRequest;
use TencentCloud\Sms\V20210111\SmsClient;
use Example\Addons\Authentication\Model\ModuleConfig;
use Example\Addons\Authentication\Model\UserPhoneStatus;
use Example\Addons\Authentication\Model\UserStatus;

class PhoneAuthController extends Controller
{

    // API 基础信息
    public $qcloud_api_key;
    public $qcloud_api_secret;
    public $sms_app_id;
    public $sms_signature;
    public $sms_template_id;
    public $client;

    public function __construct()
    {
        $this->qcloud_api_key = ModuleConfig::getConfig("authentication", "qcloud_api_key");
        $this->qcloud_api_secret = ModuleConfig::getConfig("authentication", "qcloud_api_secret");
        $this->sms_app_id = ModuleConfig::getConfig("authentication", "sms_app_id");
        $this->sms_signature = ModuleConfig::getConfig("authentication", "sms_signature");
        $this->sms_template_id = ModuleConfig::getConfig("authentication", "sms_template_id");
        try {

            // 实例化一个认证对象，入参需要传入腾讯云账户secretId，secretKey,此处还需注意密钥对的保密
            // 密钥可前往https://console.cloud.tencent.com/cam/capi网站进行获取
            $cred = new Credential($this->qcloud_api_key, $this->qcloud_api_secret);

            // 实例化一个client选项，可选的，没有特殊需求可以跳过
            $clientProfile = new ClientProfile();
            // 实例化要请求产品的client对象,clientProfile是可选的
            $this->client = new SmsClient($cred, "ap-guangzhou", $clientProfile);
        } catch (TencentCloudSDKException $e) {
            echo $e;
        }
    }

    public function sendCode(Request $request)
    {
//        authentication_activate();
        $phone = $request->input("data.phone");
        $status = UserPhoneStatus::firstOrCreate([
            "uid" => $_SESSION["uid"]
        ], [
            "phone_no" => $phone,
        ]);
        // 根据 code_send_at 判断是否可以发送
        // 要求在 60s 之内不可以重复发送
        if (isset($status->code_send_at)) {
            $now = time();
            $send_at = strtotime($status->code_send_at);
            if ($now - $send_at < 60) {
                return [
                    'success' => false,
                    'data'    => [
                        // 报告剩余多少秒
                        'remain'  => 60 - ($now - $send_at),
                        "message" => "请勿频繁发送验证码"
                    ],
                ];
            }
        }


        $code = random_int(100000, 999999);
        $params = [
            "PhoneNumberSet"   => [$phone],
            "SmsSdkAppId"      => $this->sms_app_id,
            "SignName"         => $this->sms_signature,
            "TemplateId"       => $this->sms_template_id,
            "TemplateParamSet" => [(string)$code]
        ];
        $req = new SendSmsRequest();
        $req->fromJsonString(json_encode($params));
        // 入库手机号
        try {
            $resp = $this->client->SendSms($req);
            // 输出json格式的字符串回包
//            echo $resp->toJsonString();
            $result = json_decode($resp->toJsonString(), true);
            if ($result["SendStatusSet"][0]["Code"] !== "Ok") {
                return [
                    'success' => false,
                    'data'    => [
                        "message" => "发送失败"
                    ],
                ];
            }
            // 确认发送成功了,在数据库记录时间
            // 类型为时间戳
            UserPhoneStatus::where('uid', $_SESSION["uid"])
                           ->update([
                               'code_send_at'          => Carbon::now(),
                               "phone_validation_code" => $code,
                           ]);
            return [
                'success' => true,
                'data'    => [
                    "message" => "已发送"
                ],
            ];
        } catch (Exception $e) {
            echo $e;
            return [
                'success' => false,
                'data'    => [
                    "message" => "发送失败"
                ],
            ];
        }
    }

    public function check(Request $request)
    {
        $code = $request->input("data.code");
        $status = UserPhoneStatus::where("uid", $_SESSION["uid"])
                                 ->first();
        if (isset($status->phone_validation_code)) {
            if ($status->phone_validation_code == $code) {
                // 验证成功
                UserPhoneStatus::where('uid', $_SESSION["uid"])
                               ->update([
                                   // 删掉验证码
                                   "phone_validation_code" => null,
                               ]);
                UserStatus::updateOrCreate([
                    "uid" => $_SESSION["uid"],
                ], [
                    "phone_status" => true,
                ]);
                return [
                    'success' => true,
                    'data'    => [
                        "message" => "验证成功"
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'data'    => [
                        "message" => "验证码错误"
                    ],
                ];
            }
        } else {
            return [
                'success' => false,
                'data'    => [
                    "message" => "请先发送验证码"
                ],
            ];
        }
    }

    public function status(Request $request)
    {
        $status = UserPhoneStatus::where("uid", $_SESSION["uid"])
                                 ->first();
        // 检查是否有验证码
        if (isset($status->phone_validation_code)) {
            $hasCodeSend = true;
        }
        return [
            'success' => true,
            'data'    => [
                "status"      => isset($status->phone_status),
                "hasCodeSend" => isset($hasCodeSend),
            ],
        ];
    }
}