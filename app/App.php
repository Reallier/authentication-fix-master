<?php

namespace Example\Addons\Authentication;


use Example\Addons\Authentication\Dispatch\AddonDispatcher;
use Example\Addons\Authentication\Dispatch\ServerDispatcher;

class App
{
    public string $moduleName = '';

    public function __construct()
    {
        // 初始化这个 SDK
        $this->moduleName = "auth";
    }

    public function run($scene, $caller, $params)
    {
        // 去掉前缀,只保留函数名称
        $caller = str_replace($this->moduleName . '_', '', $caller);
        // 切换路由
        switch ($scene) {
            case "addon":
                $dispatcher = new AddonDispatcher($caller, $params);
                return $dispatcher->dispatch();
            case "server":
                $dispatcher = new ServerDispatcher($caller, $params);
        }

    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }


}