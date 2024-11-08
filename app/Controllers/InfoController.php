<?php

namespace Example\Addons\Authentication\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use WHMCS\Authentication\CurrentUser;
use WHMCS\Config\Setting;
use Example\Addons\Authentication\Model\UserCertStatus;
use Example\Addons\Authentication\Model\UserPhoneStatus;
use Example\Addons\Authentication\Model\UserStatus;

class InfoController extends Controller
{

    function currentStatus()
    {
//        $currentUser = new CurrentUser;
        $user = UserStatus::where("uid",$_SESSION["uid"])->first();
        $certStatus = UserCertStatus::where("uid",$_SESSION["uid"])->first();
        $phoneStatus = UserPhoneStatus::where("uid",$_SESSION["uid"])->first();
        // 返回 JSON
        $result = [
            'success' => true,
            'data'    => [
                // null 则转换为 0
                'certStatus'  => (bool)$user->cert_status,
                'phoneStatus' => (bool)$user->phone_status,
                "companyName" => Setting::getValue('CompanyName'),
            ],
        ];
        // 如果已经通过实人认证
        if ($user->cert_status) {
            $maskedName = self::mask($certStatus->cert_name, '*', 1);
            $maskedId = self::mask($certStatus->cert_no, '*', 3, 17-3-3);
            $result["data"]["certInfo"] = [
                "name" => $maskedName,
                "id"   => $maskedId,
            ];
        }
        // 如果已经通过手机认证
        if ($user->phone_status) {
            $maskedPhone = self::mask($phoneStatus->phone_no, '*', 3, 4);
            $result["data"]["phoneInfo"] = [
                "phone" => $maskedPhone,
            ];
        }
        return $result;
    }

    public static function mask($string, $character, $index, $length = null, $encoding = 'UTF-8')
    {
        if ($character === '') {
            return $string;
        }

        if (is_null($length) && PHP_MAJOR_VERSION < 8) {
            $length = mb_strlen($string, $encoding);
        }

        $segment = mb_substr($string, $index, $length, $encoding);

        if ($segment === '') {
            return $string;
        }

        $strlen = mb_strlen($string, $encoding);
        $startIndex = $index;

        if ($index < 0) {
            $startIndex = $index < -$strlen ? 0 : $strlen + $index;
        }

        $start = mb_substr($string, 0, $startIndex, $encoding);
        $segmentLen = mb_strlen($segment, $encoding);
        $end = mb_substr($string, $startIndex + $segmentLen);

        return $start . str_repeat(mb_substr($character, 0, 1, $encoding), $segmentLen) . $end;
    }
}