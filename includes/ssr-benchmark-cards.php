<?php
/**
 * SEO 移动端修复（2026-09-01）：首页排行榜移动卡片服务端渲染。
 * 配合 index.php 的 CSS 媒体查询先于 JS 切换，消除移动端刷新时桌面表格闪现。
 * 模板与 main.js 移动分支逐字对齐（cpuNameHtml 白名单链接、escapeJsArg+decodeURIComponent、localizeCoreInfo）。
 * 独立于 ssr-benchmark-rows.php（该文件服务桌面表格行），互不影响。
 */
require_once __DIR__ . '/cpu-i18n.php';

function ssr_cards_escape($text) {
    // 对应 main.js escapeHtml（textContent->innerHTML：只转义 & < >，不转义引号）
    if ($text === null || $text === false || $text === '') return '';
    return htmlspecialchars((string)$text, ENT_NOQUOTES, 'UTF-8');
}

function ssr_cards_js_arg($value) {
    // 对应 main.js escapeJsArg = encodeURIComponent；onclick 内由 decodeURIComponent 还原
    return rawurlencode((string)$value);
}

function ssr_cards_percentage($score) {
    // 分段比例与 main.js calculatePercentage 一致：0-50000 -> 0-25%，50000-1500000 -> 25-100%
    if ($score <= 50000) {
        return number_format($score / 50000 * 25, 2, '.', '');
    }
    return number_format(min(25 + (($score - 50000) / 1450000 * 75), 100), 2, '.', '');
}

/**
 * 共用数据获取：已审核排行榜（score 降序全量，PHP 侧截取前 $limit 行），失败返回 null。
 * SQL 为常量单条、无任何参数或拼接。
 * （等价于原 COLLATE utf8mb4_general_ci 关联）。
 */
function ssr_cards_fetch_rows($pdo, $limit = 30) {
    $rows = $pdo->query("SELECT bs.id, bs.cpu_model, bs.architecture, COALESCE(c.cores, bs.cores) AS cores, c.threads, bs.score FROM benchmark_submissions bs LEFT JOIN cpus c ON bs.cpu_model = c.model WHERE bs.status = 'approved' ORDER BY bs.score DESC")->fetchAll(PDO::FETCH_ASSOC);
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
    return array_slice($rows, 0, max(1, (int)$limit));
}

/**
 * 单张移动卡片（与 main.js 移动分支模板逐字对齐）。
 */
function ssr_cards_mobile_card($item, $index, $language = 'zh-CN') {
    $e = 'ssr_cards_escape';
    if (!function_exists('cpu_detail_slugify')) {
        require_once __DIR__ . '/cpu-whitelist.php';
    }
    $percentage = ssr_cards_percentage($item['score']);
    $localizedCoreInfo = cpu_localize_core_info((string)$item['core_info'], $language);
    $coreInfo = $localizedCoreInfo !== '' ? $localizedCoreInfo : ($language === 'zh-CN' ? '未知' : 'Unknown');
    $isX86 = $item['architecture'] === 'x86_64';
    $gradientClass = $isX86 ? 'blue-gradient' : 'green-gradient';
    $archLabel = $isX86 ? 'x86' : 'ARM';
    $archClass = $isX86 ? 'arch-x86' : 'arch-arm';
    $hasRealTest = true; // 与 API 后处理一致

    // CPU 名称（白名单 → 链接，与 main.js cpuNameHtml('mobile-card-cpu') 一致）
    if (in_array($item['cpu_model'], $GLOBALS['CPU_WHITELIST'] ?? [], true)) {
        $cpuCell = '<a class="mobile-card-cpu" href="' . $e(i18n_localized_url('/cpu/' . cpu_detail_slugify($item['cpu_model']), $language)) . '">' . $e($item['cpu_model']) . '</a>';
    } else {
        $cpuCell = '<span class="mobile-card-cpu">' . $e($item['cpu_model']) . '</span>';
    }

    // 整卡点击（与 main.js clickHandler 同构）
    $cardScore = (int)$item['score'];
    $clickHandler = ' onclick="showRealTestModal(event, decodeURIComponent(\'' . ssr_cards_js_arg($item['cpu_model']) . '\'), ' . (int)($item['id'] ?: 0) . ', \'\', decodeURIComponent(\'' . ssr_cards_js_arg($coreInfo) . '\'), ' . $cardScore . ')"';

    return '<div class="mobile-card' . ($hasRealTest ? ' mobile-card-clickable' : '') . '"' . $clickHandler . '>'
        . '<div class="mobile-card-header">'
        . '<span class="mobile-card-rank">#' . ($index + 1) . '</span>'
        . $cpuCell
        . '<span class="mobile-card-arch ' . $archClass . '">' . $archLabel . '</span>'
        . '</div>'
        . '<div class="mobile-card-bar"><div class="mobile-card-bar-track">'
        . '<div class="ratio ' . $gradientClass . '" style="width: ' . $percentage . '%;">'
        . '<span class="mobile-card-info">' . $e($coreInfo) . '</span></div></div>'
        . '<span class="mobile-card-score">' . number_format($cardScore) . '</span>'
        . '</div></div>';
}

/**
 * 渲染前 $limit 张移动卡片；失败返回 null（调用方保持空容器，由 JS 兜底渲染）。
 */
function ssr_render_benchmark_mobile_cards($pdo, $limit = 30, $language = 'zh-CN') {
    $rows = ssr_cards_fetch_rows($pdo, $limit);
    if (!$rows) {
        return null;
    }
    $html = '';
    foreach ($rows as $i => $item) {
        $html .= ssr_cards_mobile_card($item, $i, $language);
    }
    return $html;
}
