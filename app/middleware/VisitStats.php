<?php

namespace app\middleware;

use app\service\VisitStats as VisitStatsService;

class VisitStats
{
    /**
     * 处理请求
     */
    public function handle($request, \Closure $next)
    {
        // 执行请求处理
        $response = $next($request);

        // 增加访问量
        $visitStats = new VisitStatsService();
        $visitStats->increment();

        return $response;
    }
}