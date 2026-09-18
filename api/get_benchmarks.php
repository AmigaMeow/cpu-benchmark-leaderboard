<?php
/**
 * API: 获取天梯榜数据（开源版）
 *
 * 只返回公开字段；不含任何提交者隐私信息
 * （邮箱 / IP / user_id / 昵称 / 头像 / 截图 / 内部审核备注一律不查、不传）。
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getPDO();

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 200)) : 50;
    $arch = isset($_GET['arch']) ? $_GET['arch'] : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $offset = ($page - 1) * $limit;

    $where = ["bs.status = :status"];
    $params = [':status' => 'approved'];

    if ($arch && in_array($arch, ['x86_64', 'ARM64', 'ARMv7', 'Other'], true)) {
        $where[] = 'bs.architecture = :arch';
        $params[':arch'] = $arch;
    }

    if ($search !== '') {
        $searchValue = '%' . $search . '%';
        $where[] = '(bs.cpu_model LIKE :search1 OR bs.device_brand LIKE :search2 OR bs.device_model LIKE :search3)';
        $params[':search1'] = $searchValue;
        $params[':search2'] = $searchValue;
        $params[':search3'] = $searchValue;
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    // 一个型号只计一次（取最高分那条样本），排行榜按型号而非按样本数
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT bs.cpu_model) as total FROM benchmark_submissions bs $whereSQL");
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['total'];

    // 仅公开字段：型号 / 设备（用户自报的设备名，不含个人信息）/ 架构 / 规格 / 分数 / 时间
    $sql = "SELECT
        bs.id,
        bs.cpu_model,
        bs.test_device,
        bs.device_brand,
        bs.device_model,
        COALESCE(bs.test_device, CONCAT(COALESCE(bs.device_brand, ''), ' ', COALESCE(bs.device_model, ''))) as device_name,
        bs.architecture,
        COALESCE(c.cores, bs.cores) as cores,
        c.threads,
        c.tdp,
        COALESCE(bs.test_device, CONCAT(COALESCE(bs.device_brand, ''), ' ', COALESCE(bs.device_model, ''))) as device_info,
        bs.score,
        bs.os_info,
        bs.created_at
        FROM benchmark_submissions bs
        LEFT JOIN cpus c ON bs.cpu_model = c.model
        JOIN (
            SELECT cpu_model, MAX(score) AS best_score, MIN(id) AS best_id
            FROM benchmark_submissions
            WHERE status = 'approved'
            GROUP BY cpu_model
        ) best ON best.cpu_model = bs.cpu_model AND best.best_id = bs.id
        $whereSQL
        ORDER BY bs.score DESC
        LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $executeParams = $params;
    $executeParams[':limit'] = $limit;
    $executeParams[':offset'] = $offset;

    foreach ($executeParams as $key => $value) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }

    $stmt->execute();
    $data = $stmt->fetchAll();

    $rank = $offset + 1;
    foreach ($data as &$item) {
        $item['rank'] = $rank++;
        if (!isset($item['id'])) {
            $item['id'] = $item['rank'];
        }

        $cores = $item['cores'] ?? '';
        $threads = $item['threads'] ?? null;

        if ($cores && strpos($cores, '核') !== false) {
            $item['core_info'] = $cores;
        } elseif ($cores && $threads) {
            $item['core_info'] = $cores . '核' . $threads . '线';
        } elseif ($cores) {
            $item['core_info'] = $cores . '核';
        } else {
            $item['core_info'] = '';
        }

        $item['has_real_test'] = true;
        $item['device_name'] = trim((string)$item['device_name']);
        $item['device_info'] = trim((string)$item['device_info']);
    }
    unset($item);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    error_log('get_benchmarks failed: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => '服务器错误',
    ], JSON_UNESCAPED_UNICODE);
}
