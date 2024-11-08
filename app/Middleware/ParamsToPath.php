<?php

namespace Example\Addons\Authentication\Middleware;

use Closure;

/**
 * Class ParamsToPath
 * @package Example\Addons\Authentication\Middleware
 * 从请求参数中提取 Path 信息，然后重写到请求路径里
 * 实现伪路由
 */
class ParamsToPath
{
    public function handle($request, Closure $next)
    {
        // 从参数获取 Path
        $apiPath = $request->query->get("path");
        // 将 Path 重写到路径里
        $request->server->set("REQUEST_URI", $apiPath);
        return $next($request);
    }
}