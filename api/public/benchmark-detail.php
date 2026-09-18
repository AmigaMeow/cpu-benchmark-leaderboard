<?php
/**
 * API: CPU 实测详情（开源版）
 *
 * 只返回公开字段；提交者身份、截图、审核备注等隐私/内部字段已移除。
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/cpu-i18n.php';

try {
    $cpuModel = $_GET['cpu_model'] ?? '';
    if ($cpuModel === '') {
        echo json_encode(['code' => 1, 'message' => '缺少参数']);
        exit;
    }

    $db = Database::getInstance();
    $language = (($_GET['lang'] ?? 'zh-CN') === 'en-US') ? 'en-US' : 'zh-CN';

    $data = $db->queryOne(
        "SELECT
            bs.cpu_model,
            bs.test_device,
            bs.device_brand,
            bs.device_model,
            bs.architecture,
            COALESCE(c.cores, bs.cores) AS cores,
            bs.score,
            bs.submitted_at,
            bs.created_at,
            bs.os_info,
            c.tdp,
            c.base_frequency,
            c.max_frequency,
            c.process
         FROM benchmark_submissions bs
         LEFT JOIN cpus c ON bs.cpu_model = c.model
         WHERE bs.cpu_model = ? AND bs.status = 'approved'
         ORDER BY bs.score DESC LIMIT 1",
        [$cpuModel]
    );

    if ($data) {
        $data['device_info'] = trim((string)($data['test_device'] ?? ''))
            ?: trim(trim((string)($data['device_brand'] ?? '')) . ' ' . trim((string)($data['device_model'] ?? '')));

        if ($language === 'en-US') {
            $data['cores'] = cpu_localize_core_info((string)($data['cores'] ?? ''), $language);
        }

        echo json_encode(['code' => 0, 'data' => $data]);
    } else {
        echo json_encode(['code' => 1, 'message' => '未找到数据']);
    }
} catch (Exception $e) {
    error_log('benchmark-detail failed: ' . $e->getMessage());
    echo json_encode(['code' => 500, 'message' => '服务器错误']);
}
