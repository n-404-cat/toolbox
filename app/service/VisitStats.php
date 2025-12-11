<?php

namespace app\service;

use think\facade\Cache;

class VisitStats
{
    // 访问量数据文件路径
    protected $dataFile;
    // 每日访问量文件路径
    protected $dailyFile;
    // 当天日期
    protected $today;

    public function __construct()
    {
        // 确保数据目录存在
        $dataDir = app()->getRootPath() . 'runtime/visit_stats/';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $this->dataFile = $dataDir . 'total.json';
        $this->today = date('Y-m-d');
        $this->dailyFile = $dataDir . $this->today . '.json';
    }

    /**
     * 增加访问量
     */
    public function increment()
    {
        // 更新总访问量
        $total = $this->getTotalVisits();
        $total++;
        $this->saveTotalVisits($total);

        // 更新今日访问量
        $todayVisits = $this->getTodayVisits();
        $todayVisits++;
        $this->saveTodayVisits($todayVisits);

        // 清除缓存，确保数据实时更新
        Cache::delete('site_stats');

        return [
            'total_visits' => $total,
            'today_visits' => $todayVisits
        ];
    }

    /**
     * 获取总访问量
     */
    public function getTotalVisits()
    {
        if (file_exists($this->dataFile)) {
            $content = file_get_contents($this->dataFile);
            $data = json_decode($content, true);
            return $data['total'] ?? 0;
        }
        return 0;
    }

    /**
     * 保存总访问量
     */
    protected function saveTotalVisits($total)
    {
        $data = ['total' => $total, 'updated_at' => time()];
        file_put_contents($this->dataFile, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取今日访问量
     */
    public function getTodayVisits()
    {
        if (file_exists($this->dailyFile)) {
            $content = file_get_contents($this->dailyFile);
            $data = json_decode($content, true);
            return $data['visits'] ?? 0;
        }
        return 0;
    }

    /**
     * 保存今日访问量
     */
    protected function saveTodayVisits($visits)
    {
        $data = ['visits' => $visits, 'date' => $this->today, 'updated_at' => time()];
        file_put_contents($this->dailyFile, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取访问量数据
     */
    public function getStats()
    {
        // 先尝试从缓存获取
        $stats = Cache::get('site_stats', []);
        if (empty($stats)) {
            $stats = [
                'total_visits' => $this->getTotalVisits(),
                'today_visits' => $this->getTodayVisits()
            ];
            // 缓存1分钟，既保证性能又能及时更新
            Cache::set('site_stats', $stats, 60);
        }
        return $stats;
    }

    /**
     * 清理过期的每日访问量文件
     */
    public function cleanupExpiredFiles()
    {
        $dataDir = app()->getRootPath() . 'runtime/visit_stats/';
        $files = glob($dataDir . '*.json');
        if ($files) {
            foreach ($files as $file) {
                // 跳过总访问量文件
                if ($file == $this->dataFile) continue;

                // 提取日期
                $filename = basename($file, '.json');
                if (strtotime($filename) < strtotime('-30 day')) {
                    unlink($file);
                }
            }
        }
    }
}