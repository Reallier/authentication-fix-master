<?php

namespace Example\Addons\Authentication\Model;

use Illuminate\Database\Eloquent\Model;

class UserCertStatus extends Model
{
    protected $table = 'auth_plus_cert';
    protected $primaryKey = 'id';
    #定义字段白名单，允许操作表中的哪些字段
    protected $fillable
        = [
            "uid",
            'out_trade_no',
            "date",
            "cert_name",
            "cert_no",
            "certify_id"
        ];
}