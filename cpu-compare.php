<?php
/**
 * SEO 阶段三 4.2：CPU 对比页 /cpu/{a}-vs-{b}
 * 仅展示 includes/cpu-compare-pairs.php 中手工精选的组合；结论段落人工撰写，不模板生成。
 * 未知组合返回真 404。
 */
require_once(__DIR__ . '/includes/i18n.php');
require_once(__DIR__ . '/includes/settings.php');
$siteUrl = rtrim((string)SiteSettings::getSiteUrl(), '/');
$siteName = SiteSettings::getSiteName();
require_once(__DIR__ . '/includes/cpu-i18n.php');
require_once(__DIR__ . '/includes/cpu-whitelist.php');
require_once(__DIR__ . '/includes/cpu-compare-pairs.php');

$currentLang = i18n_lang();
$localizedUrl = static function ($baseUrl) use ($currentLang) {
    return i18n_localized_url($baseUrl, $currentLang);
};

$aSlugReq = isset($_GET['a']) ? trim((string)$_GET['a']) : '';
$bSlugReq = isset($_GET['b']) ? trim((string)$_GET['b']) : '';

// 在精选组合表中解析（slug 精确匹配，大小写不符 301 到规范形式）
$pair = null;
$pairSlugA = ''; $pairSlugB = '';
foreach ($GLOBALS['CPU_COMPARE_PAIRS'] as $p) {
    $sa = cpu_detail_slugify($p['a']);
    $sb = cpu_detail_slugify($p['b']);
    if (mb_strtolower($aSlugReq) === $sa && mb_strtolower($bSlugReq) === $sb) {
        if ($aSlugReq !== $sa || $bSlugReq !== $sb) {
            header('Location: ' . $localizedUrl($siteUrl . '/cpu/' . $sa . '-vs-' . $sb), true, 301);
            exit;
        }
        $pair = $p; $pairSlugA = $sa; $pairSlugB = $sb;
        break;
    }
}

$cmpExtraQuery = count($_GET) > 2 || ($pair === null && count($_GET) > 0);

// ===== 两侧数据 =====
function cpu_compare_stats($db, $model, $allBest) {
    $rows = $db->query(
        "SELECT score FROM benchmark_submissions WHERE status = 'approved' AND cpu_model = ?",
        [$model]
    ) ?: [];
    if (!$rows) return null;
    $scores = array_map(function ($r) { return (float)$r['score']; }, $rows);
    sort($scores);
    $n = count($scores);
    $rank = 0;
    foreach ($allBest as $i => $r) {
        if ($r['cpu_model'] === $model) { $rank = $i + 1; break; }
    }
    return [
        'model' => $model,
        'count' => $n,
        'max' => max($scores),
        'avg' => array_sum($scores) / $n,
        'rank' => $rank,
        'total' => count($allBest),
    ];
}

$statsA = null; $statsB = null; $specA = null; $specB = null; $pairText = '';
if ($pair !== null) {
    try {
        require_once __DIR__ . '/includes/Database.php';
        $db = Database::getInstance();
        $allBest = $db->query(
            "SELECT cpu_model, MAX(score) AS best FROM benchmark_submissions WHERE status = 'approved' GROUP BY cpu_model ORDER BY best DESC"
        ) ?: [];
        $statsA = cpu_compare_stats($db, $pair['a'], $allBest);
        $statsB = cpu_compare_stats($db, $pair['b'], $allBest);
        $specA = $db->queryOne("SELECT model, architecture, cores, threads, base_frequency, process, tdp FROM cpus WHERE model = ? LIMIT 1", [$pair['a']]);
        $specB = $db->queryOne("SELECT model, architecture, cores, threads, base_frequency, process, tdp FROM cpus WHERE model = ? LIMIT 1", [$pair['b']]);
        $pairText = $currentLang === 'en-US' && !empty($pair['text_en'])
            ? (string)$pair['text_en']
            : (string)$pair['text'];
        if (!$statsA || !$statsB) $pair = null; // 数据缺失不展示，防脏页
    } catch (Exception $e) {
        error_log('cpu-compare failed: ' . $e->getMessage());
        $pair = null;
    }
}

if ($pair === null) {
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow', true);
    $pageTitle = $currentLang === 'en-US' ? 'Page not found - ' . $siteName : '页面不存在 - ' . $siteName;
    $pageDesc = cpu_t('compare.not_found', '该 CPU 对比页面不存在或暂未收录。');
    $canonicalUrl = '';
} else {
    $diffPctAbs = number_format(abs(($statsA['max'] - $statsB['max']) / max(1, min($statsA['max'], $statsB['max'])) * 100), 1);
    if ($currentLang === 'en-US') {
        $pageTitle = $pair['a'] . ' vs ' . $pair['b'] . cpu_t('compare.title_suffix', ': NAS CPU performance comparison (CoreMark)') . ' | ' . $siteName;
        $pageDesc = $pair['a'] . ' vs ' . $pair['b'] . ' NAS comparison: their best reviewed CoreMark scores differ by ' . $diffPctAbs . '%, with specifications, power and Docker/media workload guidance.';
    } else {
        $pageTitle = $pair['a'] . ' vs ' . $pair['b'] . '：NAS CPU 性能与 CoreMark 跑分对比 | ' . $siteName;
        $pageDesc = $pair['a'] . ' 与 ' . $pair['b'] . ' 的 NAS 选型对比：CoreMark 实测成绩相差 ' . $diffPctAbs . '%，附核心规格、功耗与 Docker/影音场景建议。';
    }
    $canonicalUrl = i18n_canonical_url($siteUrl . '/cpu/' . $pairSlugA . '-vs-' . $pairSlugB, $currentLang);
}
$esc = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

function cpu_compare_spec_cell($spec, $language = 'zh-CN') {
    $esc = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    if (!$spec) return '<td>-</td>';
    $bits = [];
    if (!empty($spec['architecture'])) $bits[] = $esc($spec['architecture']);
    if (!empty($spec['cores'])) $bits[] = $esc($spec['cores']) . ($language === 'en-US' ? ' cores' : ' 核心');
    if (!empty($spec['threads'])) $bits[] = $spec['threads'] . ($language === 'en-US' ? ' threads' : ' 线程');
    if (!empty($spec['tdp'])) $bits[] = 'TDP ' . $esc($spec['tdp']);
    if (!empty($spec['process'])) $bits[] = $esc($spec['process']);
    if (!empty($spec['base_frequency'])) $bits[] = $esc($spec['base_frequency']);
    return '<td>' . implode('<br>', $bits) . '</td>';
}
?>
<!doctype html>
<html lang="<?php echo $esc($currentLang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $esc($pageTitle); ?></title>
    <meta name="description" content="<?php echo $esc($pageDesc); ?>">
    <?php if ($canonicalUrl !== ''): ?>
    <link rel="canonical" href="<?php echo $esc($canonicalUrl); ?>">
    <?php i18n_render_hreflang($siteUrl . '/cpu/' . $pairSlugA . '-vs-' . $pairSlugB); ?>
    <?php else: ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <?php if ($cmpExtraQuery): ?>
    <meta name="robots" content="noindex,follow">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo $esc($siteName); ?>">
    <meta property="og:title" content="<?php echo $esc($pageTitle); ?>">
    <meta property="og:description" content="<?php echo $esc($pageDesc); ?>">
    <?php if ($canonicalUrl !== ''): ?>
    <meta property="og:url" content="<?php echo $esc($canonicalUrl); ?>">
    <?php endif; ?>
    <?php include('includes/head-meta.php'); ?>
    <style>
        .cpu-cmp-wrap { max-width: 960px; margin: 0 auto; padding: 24px 16px 60px; }
        .cpu-breadcrumb { font-size: 13px; color: #64748b; margin: 12px 0 4px; }
        .cpu-breadcrumb a { color: #2563eb; text-decoration: none; }
        .cpu-cmp-h1 { font-size: 26px; font-weight: 700; color: #1e293b; margin: 8px 0 6px; }
        .cpu-cmp-sub { color: #475569; line-height: 1.7; margin: 0 0 18px; }
        table.cmp-table { width: 100%; border-collapse: collapse; font-size: 14px; margin: 14px 0; }
        table.cmp-table th, table.cmp-table td { border: 1px solid #e2e8f0; padding: 10px 14px; text-align: left; vertical-align: top; }
        table.cmp-table th { background: #f1f5f9; color: #334155; }
        table.cmp-table td.win { background: #eff6ff; font-weight: 600; }
        table.cmp-table a { color: #2563eb; text-decoration: none; }
        .cpu-cmp-verdict { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #2563eb; border-radius: 10px; padding: 16px 20px; margin: 18px 0; color: #334155; line-height: 1.9; font-size: 15px; }
        .cpu-section { margin: 30px 0; }
        .cpu-section h2 { font-size: 19px; color: #1e293b; border-left: 4px solid #2563eb; padding-left: 10px; margin: 0 0 12px; }
        .cpu-links a { display: inline-block; margin: 0 14px 8px 0; color: #2563eb; text-decoration: none; border-bottom: 1px dashed #93c5fd; }
    </style>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="cpu-cmp-wrap">
<?php if ($pair === null): ?>
    <div style="text-align:center; padding:80px 20px;">
        <p style="font-size:20px; color:#475569;"><?php echo $esc(cpu_t('compare.not_found', '该对比页面不存在或暂未收录。')); ?></p>
        <a href="<?php echo $esc($localizedUrl('/')); ?>" style="color:#2563eb;"><?php echo $esc($currentLang === 'en-US' ? 'Back to the NAS CPU ranking' : '返回首页 NAS CPU 天梯图'); ?></a>
    </div>
<?php else: ?>
    <nav class="cpu-breadcrumb" aria-label="<?php echo $esc($currentLang === 'en-US' ? 'Breadcrumb' : '面包屑'); ?>">
        <a href="<?php echo $esc($localizedUrl('/')); ?>"><?php echo $esc($currentLang === 'en-US' ? 'Home' : '首页'); ?></a> › <a href="<?php echo $esc($localizedUrl('/#ranking')); ?>"><?php echo $esc($currentLang === 'en-US' ? 'NAS CPU ranking' : 'NAS CPU 天梯图'); ?></a> ›
        <?php echo $esc($pair['a']); ?> vs <?php echo $esc($pair['b']); ?>
    </nav>
    <h1 class="cpu-cmp-h1"><?php echo $esc($pair['a']); ?> vs <?php echo $esc($pair['b']); ?><?php echo $currentLang === 'en-US' ? ': NAS CPU performance comparison (CoreMark)' : '：NAS CPU 性能对比（CoreMark）'; ?></h1>
    <p class="cpu-cmp-sub"><?php echo $esc($currentLang === 'en-US' ? 'Based on reviewed CoreMark results submitted from real devices.' : '基于真实设备提交的 CoreMark 实测数据（人工审核）。'); ?>
        <a href="<?php echo $esc($localizedUrl('/benchmark-methodology.php')); ?>"><?php echo $esc(cpu_t('compare.method', '了解测试方法与排名规则')); ?></a><?php echo $currentLang === 'en-US' ? '.' : '。'; ?></p>

    <div class="cpu-section">
        <h2><?php echo $esc(cpu_t('compare.section', 'CoreMark 实测对比')); ?></h2>
        <table class="cmp-table">
            <thead>
                <tr><th><?php echo $esc(cpu_t('compare.item', '项目')); ?></th><th><?php echo $esc($pair['a']); ?></th><th><?php echo $esc($pair['b']); ?></th></tr>
            </thead>
            <tbody>
                <tr>
                    <th><?php echo $esc(cpu_t('compare.best', 'CoreMark 最佳成绩')); ?></th>
                    <td class="<?php echo $statsA['max'] >= $statsB['max'] ? 'win' : ''; ?>"><?php echo number_format($statsA['max']); ?></td>
                    <td class="<?php echo $statsB['max'] > $statsA['max'] ? 'win' : ''; ?>"><?php echo number_format($statsB['max']); ?></td>
                </tr>
                <tr>
                    <th><?php echo $esc(cpu_t('compare.average', '平均分')); ?></th>
                    <td><?php echo number_format(round($statsA['avg'])); ?></td>
                    <td><?php echo number_format(round($statsB['avg'])); ?></td>
                </tr>
                <tr>
                    <th><?php echo $esc(cpu_t('compare.rank', '天梯排名')); ?></th>
                    <td>#<?php echo $statsA['rank']; ?> / <?php echo $statsA['total']; ?></td>
                    <td>#<?php echo $statsB['rank']; ?> / <?php echo $statsB['total']; ?></td>
                </tr>
                <tr>
                    <th><?php echo $esc(cpu_t('compare.samples', '测试样本')); ?></th>
                    <td><?php echo $statsA['count']; ?> <?php echo $currentLang === 'en-US' ? 'samples' : '份'; ?></td>
                    <td><?php echo $statsB['count']; ?> <?php echo $currentLang === 'en-US' ? 'samples' : '份'; ?></td>
                </tr>
                <tr>
                    <th><?php echo $esc(cpu_t('compare.specs', '规格概览')); ?></th>
                    <?php echo cpu_compare_spec_cell($specA, $currentLang); ?>
                    <?php echo cpu_compare_spec_cell($specB, $currentLang); ?>
                </tr>
                <tr>
                    <th><?php echo $esc(cpu_t('compare.details', '详情页')); ?></th>
                    <td><a href="<?php echo $esc($localizedUrl('/cpu/' . $pairSlugA)); ?>"><?php echo $esc($pair['a']); ?> <?php echo $esc(cpu_t('compare.benchmark_link', '性能测试')); ?></a></td>
                    <td><a href="<?php echo $esc($localizedUrl('/cpu/' . $pairSlugB)); ?>"><?php echo $esc($pair['b']); ?> <?php echo $esc(cpu_t('compare.benchmark_link', '性能测试')); ?></a></td>
                </tr>
            </tbody>
        </table>
        <?php
        $cmpWinner = $statsA['max'] >= $statsB['max'] ? $pair['a'] : $pair['b'];
        $cmpLoser = $statsA['max'] >= $statsB['max'] ? $pair['b'] : $pair['a'];
        $cmpWinScore = max($statsA['max'], $statsB['max']);
        $cmpLoseScore = min($statsA['max'], $statsB['max']);
        $diffPct = ($cmpWinScore - $cmpLoseScore) / max(1, $cmpLoseScore) * 100;
        ?>
        <?php if ($currentLang === 'en-US'): ?>
        <p class="cpu-cmp-sub">On the best CoreMark score, <?php echo $esc($cmpWinner); ?> is <?php echo number_format($diffPct, 1); ?>% higher than <?php echo $esc($cmpLoser); ?>.</p>
        <?php else: ?>
        <p class="cpu-cmp-sub">CoreMark 最佳成绩上，<?php echo $esc($cmpWinner); ?>比 <?php echo $esc($cmpLoser); ?>高 <?php echo number_format($diffPct, 1); ?>%。</p>
        <?php endif; ?>
    </div>

    <div class="cpu-section">
        <h2><?php echo $esc(cpu_t('compare.choose', '怎么选：结论与建议')); ?></h2>
        <div class="cpu-cmp-verdict"><?php echo $esc($pairText); ?></div>
        <p class="cpu-cmp-sub" style="font-size:13px;color:#64748b;"><?php echo $esc(cpu_t('compare.disclaimer', '结论基于公开规格与本站收录的实测数据撰写，供选型参考；你的使用场景（盘位数、转码需求、Docker 规模）永远比跑分更重要。')); ?></p>
    </div>

    <div class="cpu-section">
        <h2><?php echo $esc(cpu_t('compare.related', '相关工具与页面')); ?></h2>
        <div class="cpu-links">
            <a href="<?php echo $esc($localizedUrl('/cpu/' . $pairSlugA)); ?>"><?php echo $esc($pair['a']); ?> <?php echo $esc($currentLang === 'en-US' ? 'details' : '详情页'); ?></a>
            <a href="<?php echo $esc($localizedUrl('/cpu/' . $pairSlugB)); ?>"><?php echo $esc($pair['b']); ?> <?php echo $esc($currentLang === 'en-US' ? 'details' : '详情页'); ?></a>
            <a href="<?php echo $esc($localizedUrl('/#ranking')); ?>"><?php echo $esc(cpu_t('compare.full_ranking', '完整 NAS CPU 天梯图')); ?></a>
        </div>
    </div>

    <script type="application/ld+json">
    <?php echo json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => $currentLang === 'en-US' ? 'Home' : '首页', 'item' => $localizedUrl($siteUrl . '/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $currentLang === 'en-US' ? 'NAS CPU ranking' : 'NAS CPU 天梯图', 'item' => $localizedUrl($siteUrl . '/#ranking')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $pair['a'] . ' vs ' . $pair['b']],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    </script>
<?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
