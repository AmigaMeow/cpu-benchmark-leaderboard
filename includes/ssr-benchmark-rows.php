<?php
/**
 * SEO 阶段二 3.3：首页排行榜服务端渲染
 * 输出与 assets/js/main.js displayBenchmarks() 桌面端行模板逐字对齐的前 N 行到 #benchmarkBody。
 * JS 加载后会用相同模板重绘全部数据（内容一致），SSR 仅服务不执行 JS 的爬虫。
 * 数据口径镜像 /api/get_benchmarks.php：status=approved、ORDER BY score DESC。
 */
require_once __DIR__ . '/cpu-i18n.php';

function ssr_bench_escape($text) {
    // 对应 main.js escapeHtml（textContent->innerHTML：只转义 & < >，不转义引号）
    if ($text === null || $text === false || $text === '') return '';
    return htmlspecialchars((string)$text, ENT_NOQUOTES, 'UTF-8');
}

function ssr_bench_localize_core_info($text, $language) {
    return cpu_localize_core_info((string)$text, $language);
}

function ssr_bench_percentage($score) {
    // 分段比例与 main.js calculatePercentage 一致：0-50000 -> 0-25%，50000-1500000 -> 25-100%
    $THRESHOLD = 50000;
    $MAX_SCORE = 1500000;
    $LOW_RANGE_WIDTH = 25;
    $HIGH_RANGE_WIDTH = 75;
    if ($score <= $THRESHOLD) {
        return number_format($score / $THRESHOLD * $LOW_RANGE_WIDTH, 2, '.', '');
    }
    $percentage = $LOW_RANGE_WIDTH + (($score - $THRESHOLD) / ($MAX_SCORE - $THRESHOLD) * $HIGH_RANGE_WIDTH);
    return number_format(min($percentage, 100), 2, '.', '');
}

function ssr_bench_row($item, $index, $language = 'zh-CN') {
    $e = 'ssr_bench_escape';
    // SEO 阶段三：白名单 CPU 名称链接到实体页（与 main.js cpuNameHtml 一致）
    if (!function_exists('cpu_detail_slugify')) {
        require_once __DIR__ . '/cpu-whitelist.php';
    }
    $cpuNameCell = in_array($item['cpu_model'], $GLOBALS['CPU_WHITELIST'] ?? [], true)
        ? '<a class="cpu-name-text" href="' . $e(i18n_localized_url('/cpu/' . cpu_detail_slugify($item['cpu_model']), $language)) . '">' . $e($item['cpu_model']) . '</a>'
        : '<span class="cpu-name-text">' . $e($item['cpu_model']) . '</span>';
    $percentage = ssr_bench_percentage($item['score']);

    // 分割线：仅第一行（$index === 0）带数字标签
    $markers = '';
    foreach ([0, 50000, 200000, 600000, 1000000, 1500000] as $m) {
        $pos = ssr_bench_percentage($m);
        $markers .= $index === 0
            ? '<div class="score-marker" style="left: ' . $pos . '%;"><span class="score-label">' . number_format($m) . '</span></div>' . "\n                            "
            : '<div class="score-marker" style="left: ' . $pos . '%;"></div>' . "\n                            ";
    }

    $isX86 = $item['architecture'] === 'x86_64';
    $gradientClass = $isX86 ? 'blue-gradient' : 'green-gradient';
    $hasRealTest = true; // 与 API 后处理一致：所有 CPU 均显示实测跑分图标
    $uniqueId = 'speedometer-' . (int)($item['id'] ?: $index);
    $localizedCoreInfo = cpu_localize_core_info((string)$item['core_info'], $language);
    $coreInfo = $localizedCoreInfo !== '' ? $localizedCoreInfo : ($language === 'zh-CN' ? '未知' : 'Unknown');
    $scoreInt = (int)$item['score'];
    $scoreText = number_format($scoreInt);

    // 图标容器（与 JS 相同：有图标才创建）
    $iconsHtml = '<span style="margin-left: 2px; white-space: nowrap;">';

    // 实测跑分速度表图标（SVG 与 main.js 完全一致）
    $iconsHtml .= '<span class="icon-tooltip" data-tooltip="' . $e(cpu_t('runtime.test_data', '查看CoreMark跑分数据')) . '"'
        . ' onclick="showRealTestModal(event, \'' . $e($item['cpu_model']) . '\', ' . (int)($item['id'] ?: 0) . ', \'\', \'' . $e($coreInfo) . '\', ' . $scoreInt . ')"'
        . ' onmouseover="var svg=this.querySelector(\'svg\'); svg.style.transform=\'scale(1.2)\'; svg.style.color=\'#dc3545\'; document.getElementById(\'' . $uniqueId . '\').querySelector(\'.needle-animation\').beginElement();"'
        . ' onmouseout="var svg=this.querySelector(\'svg\'); svg.style.transform=\'scale(1)\'; svg.style.color=\'#000\';"'
        . ' style="display: inline-block; margin-right: 0px; vertical-align: middle; cursor: pointer;">'
        . '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" id="' . $uniqueId . '" class="speedometer-icon"'
        . ' style="color: #000; transition: all 0.3s; vertical-align: middle; display: block; pointer-events: none;">'
        . '<g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">'
        . '<path stroke-dasharray="64" stroke-dashoffset="64" d="M12 3c4.97 0 9 4.03 9 9c0 4.97 -4.03 9 -9 9c-4.97 0 -9 -4.03 -9 -9c0 -4.97 4.03 -9 9 -9Z">'
        . '<animate fill="freeze" attributeName="stroke-dashoffset" dur="0.6s" values="64;0"/></path>'
        . '<path stroke-dasharray="8" stroke-dashoffset="8" d="M12 12h-5.5">'
        . '<animate fill="freeze" attributeName="stroke-dashoffset" begin="1.3s" dur="0.2s" values="8;0"/>'
        . '<animateTransform class="needle-animation" fill="freeze" attributeName="transform" begin="indefinite" dur="0.4s" type="rotate" values="0 12 12;65 12 12"/></path></g>'
        . '<g fill="currentColor">'
        . '<path fill-opacity="0" d="M12 21C9.41 21 7.15 20.79 5.94 19L12 21L18.06 19C16.85 20.79 14.59 21 12 21Z">'
        . '<animate fill="freeze" attributeName="d" begin="0.6s" dur="0.4s" values="M12 21C9.41 21 7.15 20.79 5.94 19L12 21L18.06 19C16.85 20.79 14.59 21 12 21Z;M12 16C9.41 16 7.15 17.21 5.94 19L12 21L18.06 19C16.85 17.21 14.59 16 12 16Z"/>'
        . '<set fill="freeze" attributeName="fill-opacity" begin="0.6s" to="1"/></path>'
        . '<circle cx="7" cy="12" r="0" transform="rotate(15 12 12)"><animate fill="freeze" attributeName="r" begin="0.9s" dur="0.2s" values="0;1"/></circle>'
        . '<circle cx="7" cy="12" r="0" transform="rotate(65 12 12)"><animate fill="freeze" attributeName="r" begin="0.95s" dur="0.2s" values="0;1"/></circle>'
        . '<circle cx="7" cy="12" r="0" transform="rotate(115 12 12)"><animate fill="freeze" attributeName="r" begin="1s" dur="0.2s" values="0;1"/></circle>'
        . '<circle cx="7" cy="12" r="0" transform="rotate(165 12 12)"><animate fill="freeze" attributeName="r" begin="1.05s" dur="0.2s" values="0;1"/></circle>'
        . '<circle cx="12" cy="12" r="0"><animate fill="freeze" attributeName="r" begin="1.3s" dur="0.2s" values="0;2"/></circle>'
        . '</g></svg></span>';

    $iconsHtml .= '</span>';

    // 进度条内容（与 JS 相同阈值：<20000 短条外部分数；20000-120000 小字号）
    $score = $item['score'];
    if ($score < 20000) {
        $barContent = '<div class="ratio ' . $gradientClass . '" style="width: ' . $percentage . '%;">'
            . '<div class="socinfo" style="font-size: 10px;">' . $e($localizedCoreInfo) . '</div></div>'
            . '<div class="bar-text-outside" style="left: ' . $percentage . '%;">'
            . '<span class="score-value" style="font-size: 14px; font-weight: bold;">' . $scoreText . '</span></div>';
    } else {
        $isMediumBar = $score < 120000;
        $fontSizeClass = $isMediumBar ? 'style="font-size: 11px;"' : '';
        $scoreFontSize = $isMediumBar ? 'style="font-size: 12px;"' : '';
        $barContent = '<div class="ratio ' . $gradientClass . '" style="width: ' . $percentage . '%;">'
            . '<div class="socinfo" ' . $fontSizeClass . '>' . $e($localizedCoreInfo) . '</div>'
            . '<span class="score-text" ' . $scoreFontSize . '>' . $scoreText . '</span></div>';
    }

    return '<tr>'
        . '<td class="socName">' . $cpuNameCell . $iconsHtml . '</td>'
        . '<td><div class="ratioBar">' . $markers . $barContent . '</div></td>'
        . '</tr>';
}

/**
 * 渲染前 $limit 行；失败返回 null（调用方回退到原加载占位行）。
 */
function ssr_render_benchmark_rows($pdo, $limit = 30, $language = 'zh-CN') {
    $sql = "SELECT bs.id, bs.cpu_model, bs.architecture, COALESCE(c.cores, bs.cores) AS cores, c.threads, bs.score
            FROM benchmark_submissions bs
            LEFT JOIN cpus c ON bs.cpu_model = c.model
            WHERE bs.status = 'approved'
            ORDER BY bs.score DESC
            LIMIT " . (int)$limit;
    $stmt = $pdo->query($sql);
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if (!$rows) {
        return null;
    }
    // core_info 生成逻辑镜像 /api/get_benchmarks.php
    foreach ($rows as &$item) {
        $cores = $item['cores'] ?? '';
        $threads = $item['threads'] ?? null;
        if ($cores !== '' && strpos((string)$cores, '核') !== false) {
            $item['core_info'] = $cores;
        } elseif ($cores !== '' && $threads) {
            $item['core_info'] = $cores . '核' . $threads . '线';
        } elseif ($cores !== '') {
            $item['core_info'] = $cores . '核';
        } else {
            $item['core_info'] = '';
        }
    }
    unset($item);

    $html = '';
    foreach ($rows as $i => $item) {
        $html .= ssr_bench_row($item, $i, $language);
    }
    return $html;
}
