<?php
/**
 * API: 获取CPU天梯榜数据
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // 连接数据库
    $config = require(__DIR__ . '/../config/database.php');
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    // 获取所有有跑分的CPU，按分数降序排列
    // 天梯榜分数取「该型号已审核样本的最高分」，与其他排名口径一致
    $sql = "SELECT c.id, c.model, c.architecture, c.cores, c.threads,
                   c.base_frequency, c.max_frequency, c.tdp,
                   MAX(bs.score) AS coremark_score
            FROM cpus c
            JOIN benchmark_submissions bs
              ON bs.cpu_model = c.model AND bs.status = 'approved'
            WHERE c.status = 1
            GROUP BY c.id, c.model, c.architecture, c.cores, c.threads,
                     c.base_frequency, c.max_frequency, c.tdp
            HAVING coremark_score > 0
            ORDER BY coremark_score DESC";
    $stmt = $pdo->query($sql);
    $cpus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 转换数据类型
    foreach ($cpus as &$cpu) {
        $cpu['id'] = (int)$cpu['id'];
        $cpu['coremark_score'] = (int)$cpu['coremark_score'];
    }
    unset($cpu); // 解除引用，防止后续循环出错

    // 构建天梯榜行数据 - 按跑分顺序排列，相近分数对齐
    // 分数差异阈值：15%以内视为相近
    $score_threshold_percent = 0.15;
    $rows = [];

    foreach ($cpus as $cpu) {
        $placed = false;

        // 尝试放入已有行中（相同或相近分数）
        foreach ($rows as &$row) {
            // 计算分数差异百分比（相对于较大值）
            $score_diff_percent = abs($row['score'] - $cpu['coremark_score']) / max($row['score'], $cpu['coremark_score']);

            // 如果该架构位置为空，且分数相近（差异在阈值内），则放入
            if ($cpu['architecture'] === 'x86_64' && $row['x86'] === null && $score_diff_percent <= $score_threshold_percent) {
                $row['x86'] = $cpu;
                $placed = true;
                break;
            } elseif ($cpu['architecture'] === 'ARM64' && $row['arm'] === null && $score_diff_percent <= $score_threshold_percent) {
                $row['arm'] = $cpu;
                $placed = true;
                break;
            }
        }
        unset($row); // 解除引用，防止后续代码覆盖最后一个元素

        // 如果没有放入现有行，创建新行
        if (!$placed) {
            $new_row = [
                'score' => $cpu['coremark_score'],
                'x86' => null,
                'arm' => null
            ];

            if ($cpu['architecture'] === 'x86_64') {
                $new_row['x86'] = $cpu;
            } else {
                $new_row['arm'] = $cpu;
            }

            $rows[] = $new_row;
        }
    }

    // 添加排名编号
    foreach ($rows as $index => &$row) {
        $row['rank'] = $index + 1;
    }

    echo json_encode([
        'success' => true,
        'rows' => $rows,
        'total_rows' => count($rows)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    error_log('get_ranking_data failed: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ], JSON_UNESCAPED_UNICODE);
}
