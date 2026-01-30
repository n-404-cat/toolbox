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
        $total = $this->incrementFileValue($this->dataFile, 'total');

        // 更新今日访问量
        $todayVisits = $this->incrementFileValue($this->dailyFile, 'visits', ['date' => $this->today]);

        // 清除缓存，确保数据实时更新
        Cache::delete('site_stats');

        return [
            'total_visits' => $total,
            'today_visits' => $todayVisits
        ];
    }

    /**
     * 原子增加文件中的数值
     */
    protected function incrementFileValue($file, $key, $extraData = [])
    {
        $fp = fopen($file, 'c+');
        if (!$fp) {
            return 0;
        }

        $value = 0;
        // 尝试获取排他锁，等待获取
        if (flock($fp, LOCK_EX)) {
            $content = '';
            while (!feof($fp)) {
                $content .= fread($fp, 8192);
            }

            $data = json_decode($content, true) ?: [];
            $value = ($data[$key] ?? 0) + 1;

            $data[$key] = $value;
            $data['updated_at'] = time();
            foreach ($extraData as $k => $v) {
                $data[$k] = $v;
            }

            // 清空文件并写入新内容
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return $value;
    }

    /**
     * 获取总访问量
     */
    public function getTotalVisits()
    {
        return $this->getFileValue($this->dataFile, 'total');
    }

    /**
     * 获取今日访问量
     */
    public function getTodayVisits()
    {
        return $this->getFileValue($this->dailyFile, 'visits');
    }

    /**
     * 获取文件中的数值
     */
    protected function getFileValue($file, $key)
    {
        if (!file_exists($file)) {
            return 0;
        }

        $fp = fopen($file, 'r');
        if (!$fp) {
            return 0;
        }

        $value = 0;
        // 尝试获取共享锁
        if (flock($fp, LOCK_SH)) {
            $content = '';
            while (!feof($fp)) {
                $content .= fread($fp, 8192);
            }
            $data = json_decode($content, true);
            $value = $data[$key] ?? 0;
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return $value;
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
                if ($file == $this->dataFile)
                    continue;

                // 提取日期
                $filename = basename($file, '.json');
                if (strtotime($filename) < strtotime('-30 day')) {
                    unlink($file);
                }
            }
        }
    }
}