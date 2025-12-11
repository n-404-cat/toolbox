<?php

namespace app\controller;


use think\facade\View;
use app\service\VisitStats;

class User extends Base
{
    public function index()
    {
        $oauth_modes = get_enabled_oauth_mode();

        $arr = [];
        $user = get_user();
        foreach ($oauth_modes as $v) {
            if (!empty($user->oauth[$v])) {
                $arr[$v] = $user->oauth[$v];
            } else {
                $arr[$v] = 0;
            }
        }

        // 获取访问量数据
        $visitStats = new VisitStats();
        $stats = $visitStats->getStats();

        View::assign([
            'oauth' => $arr,
            'total_visits' => $stats['total_visits'],
            'today_visits' => $stats['today_visits'],
        ]);
        return view();
    }
}
