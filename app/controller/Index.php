<?php

namespace app\controller;

use app\service\VisitStats;
class Index extends Base
{
    public function index()
    {
        // 获取访问量数据
        $visitStats = new VisitStats();
        $stats = $visitStats->getStats();
        return view('', $stats);
    }

    public function stars()
    {
        // 获取访问量数据
        $visitStats = new VisitStats();
        $stats = $visitStats->getStats();
        return view('', $stats);
    }
}
