<?php
/**
 * 页脚展示用的站点实时数据（开源版：只统计 cpus / benchmark_submissions）。
 * 文件缓存 1 小时，查库失败则返回空数组不显示。
 */

if (!function_exists('footer_site_stats')) {

    function footer_site_stats(): array
    {
        $cacheFile = __DIR__ . '/../cache/footer/stats.json';
        $ttl = 3600;

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['cpus'])) {
                return $cached;
            }
        }

        $stats = footer_site_stats_query();
        if ($stats === null) {
            return [];
        }

        $dir = dirname($cacheFile);
        if (is_dir($dir) || @mkdir($dir, 0755, true) || is_dir($dir)) {
            @file_put_contents($cacheFile, json_encode($stats, JSON_UNESCAPED_UNICODE), LOCK_EX);
        }

        return $stats;
    }

    function footer_site_stats_query(): ?array
    {
        if (!class_exists('Database')) {
            $path = __DIR__ . '/Database.php';
            if (!is_file($path)) {
                return null;
            }
            require_once $path;
        }

        try {
            $db = Database::getInstance();
            $row = $db->queryOne(
                "SELECT
                    (SELECT COUNT(*) FROM cpus)                                              AS cpus,
                    (SELECT COUNT(*) FROM benchmark_submissions WHERE status = 'approved')   AS benchmarks"
            );
        } catch (Throwable $e) {
            error_log('footer stats query failed: ' . $e->getMessage());
            return null;
        }

        if (!$row) {
            return null;
        }

        return [
            'cpus'       => (int) $row['cpus'],
            'benchmarks' => (int) $row['benchmarks'],
        ];
    }

    function footer_stat_number(int $n, string $lang): string
    {
        $useCjk = ($lang === 'zh-CN' || $lang === 'zh-TW' || $lang === 'ja-JP');

        if ($useCjk) {
            if ($n >= 10000) {
                $tenThousandsUnit = $lang === 'zh-TW' ? '萬' : '万';
                return rtrim(rtrim(number_format($n / 10000, 1), '0'), '.') . $tenThousandsUnit;
            }
            return number_format($n);
        }

        if ($n >= 1000) {
            return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
        }
        return number_format($n);
    }
}
