<?php

namespace Example\Addons\Authentication\Model;

use Illuminate\Database\Eloquent\Model;

class ModuleConfig extends Model
{
    protected $table = 'tbladdonmodules';
    protected $fillable = ['module', 'setting', "value"];

    protected $moduleName = "authentication";


    public function getAlipayAppId()
    {
        return $this->where('module', "")->where('setting', 'AppId')->first()->value;
    }

    public function getMerchantPrivateKey(){
        return $this->where('module', "")->where('setting', 'MerchantPrivateKey')->first()->value;
    }
    public function scopeOfModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public static function getConfig($module, $configKey){
        return self::where('module', $module)->where('setting', $configKey)->first()->value;
    }
 
}