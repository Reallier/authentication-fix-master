<?php

namespace Example\Addons\Authentication\Model;

use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{

    protected $table = 'auth_plus_status';
    protected $primaryKey = 'id';
    #定义字段白名单，允许操作表中的哪些字段
    protected $fillable
        = [
            "uid",
            'cert_status',
            "phone_status",
        ];
}