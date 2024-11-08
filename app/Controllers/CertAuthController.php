<?php

namespace Example\Addons\Authentication\Controllers;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use Alipay\EasySDK\Member\Identification\Models\IdentityParam;
use Alipay\EasySDK\Member\Identification\Models\MerchantConfig;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use WHMCS\Config\Setting;
use Example\Addons\Authentication\Model\ModuleConfig;
use Example\Addons\Authentication\Model\UserCertStatus;
use Example\Addons\Authentication\Model\UserStatus;

class CertAuthController extends Controller
{


    public ResponseChecker $responseChecker;

    public function __construct()
    {
        Factory::setOptions($this->getOptions());
        $this->responseChecker = new ResponseChecker();
    }

    function submit(Request $request)
    {
//        authentication_activate();
        // 查询是否过实名,防止绕过前端提交请求
        $authStatus = UserStatus::firstOrCreate([
            "uid" => $_SESSION["uid"]
        ]);
        if ($authStatus->cert_status) {
            return [
                'success' => true,
                'message' => '您已经通过实名认证了',
            ];
        }
        // 从输入的 JSON 中解析 certName 和 certNo
        $certName = $request->input("data.certName");
        $certNo = $request->input("data.certNo");
        // 先在数据库查询是否有以前的记录
        $status = UserCertStatus::firstOrCreate([
            "uid" => $_SESSION["uid"]
        ], [
            "cert_name" => $certName,
            "cert_no"   => $certNo,
        ]);
        // 如果有则判断这次验证有效
        if (isset($status->certify_id)) {
            // 含有 CertifyId 的时候,可以直接查询然后开始干活
            $result = Factory::member()->identification()->query($status->certify_id);
            if ($this->responseChecker->success($result)) {
                // 说明验证有效,可以执行打开验证
                $result = Factory::member()->identification()->certify($status->certify_id);
                $this->responseChecker = new ResponseChecker();
                if ($this->responseChecker->success($result)) {
                    $qrCode = $result->body;
                    return [
                        'success' => 'true',
                        'data'    => [
                            'code' => $qrCode,
                        ],
                    ];
                }
            }
        }
        // 没有 CertifyId 或者说过期了,那就重新生成一个
        // 准备订单号
        $outerOrderNoPrefix = 'ExAuth' . date('YmdHis');
        $fullOuterOrderNo = $outerOrderNoPrefix . $this->getRandStr(32 - strlen($outerOrderNoPrefix));
        // 准备 SDK 调用参数
        $merchantConfig = $this->getMerchantConfig();
        $identityParam = $this->getIdentityParam($certName, $certNo);
        // 向支付宝提交初始化请求
        $certifyIdResult = $this->getCertifyId($fullOuterOrderNo, $identityParam, $merchantConfig);
        if (!$certifyIdResult["ok"]) {
            return $certifyIdResult["result"];
        }
        $certifyId = $certifyIdResult["result"];

        // 既然数据无误,那就应该入库
        $status = UserCertStatus::where("uid", $_SESSION["uid"])
                                ->where("cert_name", $certName)
                                ->where("cert_no", $certNo)
                                ->update([
                                    "out_trade_no" => $fullOuterOrderNo,
                                    "certify_id"   => $certifyId,
                                ]);
        // 现在开启验证
        $result = Factory::member()->identification()->certify($certifyId);
        $this->responseChecker = new ResponseChecker();
        if ($this->responseChecker->success($result)) {
            $qrCode = $result->body;
            return [
                'success' => 'true',
                'data'    => [
                    'code' => $qrCode,
                ],
            ];
        }

        return [
            'success' => 'false',
            'data'    => [
                'message' => "开启认证失败",
            ],
        ];
    }

    function check(Request $request)
    {
        // 这段代码仅限调试用
//        return [
//            'success' => 'true',
//            'data'    => [
//                'certStatus' => true,
//            ],
//        ];
        // 从输入的 JSON 中解析 certName 和 certNo
        $certName = $request->input("data.certName");
        $certNo = $request->input("data.certNo");
        // 先在数据库查询是否有以前的记录
        $status = UserCertStatus::where("uid", $_SESSION["uid"])
                                ->where("cert_name", $certName)
                                ->where("cert_no", $certNo)
                                ->get()->first();
        if (!isset($status->certify_id)) {
            return [
                'success' => 'false',
                'data'    => [
                    'message' => "没有找到认证记录",
                ],
            ];
        }
        // 含有 CertifyId 的时候,可以直接查询然后开始干活
        $result = Factory::member()->identification()->query($status->certify_id);
        if (!$this->responseChecker->success($result)) {
            // 状态异常
            return [
                'success' => 'false',
                'data'    => [
                    'message' => $this->responseChecker->message(),
                ],
            ];
        }
        // 请求正确,判断结果
        if ($result->passed === "T") {
            // 入库
            UserStatus::where("uid", $_SESSION["uid"])
                      ->update([
                          "cert_status" => true
                      ]);
            // 通过
            return [
                'success' => 'true',
                'data'    => [
                    'certStatus' => true,
                ],
            ];
        }
    }

    public function getOptions()
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';
        $options->appId = ModuleConfig::getConfig("authentication", "app_id");
        $options->merchantPrivateKey = ModuleConfig::getConfig("authentication", "merchant_private_key");
        //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        $options->alipayPublicKey = ModuleConfig::getConfig("authentication", "alipay_public_key");
        $systemUrl = Setting::getValue('SystemURL');
        //可设置异步通知接收服务地址（可选）
//        $options->notifyUrl = $systemUrl . "/modules/addons/authentication/notifyUrl.php";
        return $options;
    }

    public function getReturnUrl()
    {
        $systemUrl = Setting::getValue('SystemURL');
        return $systemUrl . "/modules/addons/authentication/returnUrl.php";
    }

    public function getMerchantConfig()
    {
        $returnUrl = $this->getReturnUrl();
        $merchantConfig = new MerchantConfig();
//        $merchantConfig->returnUrl = $returnUrl;
        $merchantConfig->returnUrl = "";
        return $merchantConfig;
    }

    public function getIdentityParam($certName, $certNo)
    {
        $identityParam = new IdentityParam();
        $identityParam->identityType = "CERT_INFO";
        $identityParam->certType = "IDENTITY_CARD";
        $identityParam->certName = $certName;
        $identityParam->certNo = $certNo;
        return $identityParam;
    }

    private function getRandStr(int $param)
    {
        // 生成一段随机字符串
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randStr = '';
        for ($i = 0; $i < $param; $i++) {
            $randStr .= $str[random_int(0, strlen($str) - 1)];
        }
        return $randStr;
    }

    private function getCertifyId($fullOuterOrderNo, $identityParam, $merchantConfig): ?array
    {
        try {
            $result = Factory::member()
                             ->identification()
                             ->init($fullOuterOrderNo, "FACE", $identityParam, $merchantConfig);
            $responseChecker = new ResponseChecker();
            if ($responseChecker->success($result)) {
                $certifyId = $result->certifyId;
                return [
                    "ok"     => true,
                    "result" => $certifyId,
                ];
            }
            return [
                "ok"     => false,
                "result" => [
                    'status' => 'error',
                    'data'   => [
                        'message' => "提交失败,要不要检查一下?",
                    ],
                ]
            ];
        } catch (Exception $e) {
            return [
                "ok"     => false,
                "result" => [
                    'status' => 'error',
                    'data'   => [
                        'message' => "",
                    ],
                ]
            ];
        }
    }
}