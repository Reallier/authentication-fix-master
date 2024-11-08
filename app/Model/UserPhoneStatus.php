<?php

namespace Example\Addons\Authentication\Model;

use Illuminate\Database\Eloquent\Model;

class UserPhoneStatus extends Model
{
    protected $table = 'auth_plus_phone';
    protected $primaryKey = 'id';
    #定义字段白名单，允许操作表中的哪些字段
    protected $fillable
        = [
            "uid",
            'phone_no',
            "code_send_at",
            "phone_validation_code"
        ];
}