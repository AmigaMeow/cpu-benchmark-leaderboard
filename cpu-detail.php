<?php
/**
 * SEO 阶段三 4.1：CPU 实体页 /cpu/{slug}
 * 数据来自 cpus / benchmark_submissions 表。
 * 未知 slug 返回真 404（参照 article.php 模式）；首批仅白名单内 CPU 可访问。
 */
require_once(__DIR__ . '/includes/i18n.php');
require_once(__DIR__ . '/includes/settings.php');
$siteUrl = rtrim((string)SiteSettings::getSiteUrl(), '/');
$siteName = SiteSettings::getSiteName();
require_once(__DIR__ . '/includes/cpu-i18n.php');
require_once(__DIR__ . '/includes/cpu-whitelist.php');

$currentLang = i18n_lang();
$localizedUrl = static function ($baseUrl) use ($currentLang) {
    return i18n_localized_url($baseUrl, $currentLang);
};

$cpuWhitelist = $GLOBALS['CPU_WHITELIST'];
$cpuSlugOverrides = $GLOBALS['CPU_SLUG_OVERRIDES'];

// ===== 解析 slug =====
$requestSlug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$cpuModel = null;
$cpuSlug = '';
if ($requestSlug !== '') {
    foreach ($cpuWhitelist as $m) {
        $s = cpu_detail_slugify($m, $cpuSlugOverrides);
        if (mb_strtolower($requestSlug) === $s) {
            // 大小写/尾部差异统一 301 到小写规范形式
            if ($requestSlug !== $s) {
                header('Location: ' . $localizedUrl($siteUrl . '/cpu/' . $s), true, 301);
                exit;
            }
            $cpuModel = $m;
            $cpuSlug = $s;
            break;
        }
    }
}

// 本页无任何合法查询参数：带参数访问一律 noindex（防参数页进索引）
$cpuExtraQuery = count($_GET) > 1 || ($requestSlug === '' && count($_GET) > 0);

// ===== 数据查询 =====
$cpuStats = null; $cpuRank = 0; $cpuTotal = 0; $cpuNeighbors = []; $cpuRow = null; $cpuSamples = [];
if ($cpuModel !== null) {
    try {
        require_once __DIR__ . '/includes/Database.php';
        $db = Database::getInstance();

        // 全部已审核型号的最佳成绩（用于排名与相邻对比）
        $allBest = $db->query(
            "SELECT cpu_model, MAX(score) AS best FROM benchmark_submissions WHERE status = 'approved' GROUP BY cpu_model ORDER BY best DESC"
        ) ?: [];
        $cpuTotal = count($allBest);
        $rankMap = [];
        foreach ($allBest as $i => $r) {
            $rankMap[$r['cpu_model']] = $i + 1;
        }
        $cpuRank = $rankMap[$cpuModel] ?? 0;

        // 相邻排名（±3）
        $idx = $cpuRank - 1;
        for ($j = max(0, $idx - 3); $j < min(count($allBest), $idx + 4); $j++) {
            $cpuNeighbors[] = [
                'model' => $allBest[$j]['cpu_model'],
                'best' => (float)$allBest[$j]['best'],
                'rank' => $j + 1,
            ];
        }

        // 该型号全部样本
        $cpuSamples = $db->query(
            "SELECT score, test_device, device_brand, device_model, os_info, cores, submitted_at, updated_at
             FROM benchmark_submissions WHERE status = 'approved' AND cpu_model = ? ORDER BY score DESC",
            [$cpuModel]
        ) ?: [];
        if ($cpuSamples) {
            $scores = array_map(function ($r) { return (float)$r['score']; }, $cpuSamples);
            sort($scores);
            $n = count($scores);
            $median = ($n % 2) ? $scores[(int)(($n - 1) / 2)] : ($scores[$n / 2 - 1] + $scores[$n / 2]) / 2;
            $cpuStats = [
                'count' => $n,
                'max' => max($scores),
                'min' => min($scores),
                'avg' => array_sum($scores) / $n,
                'median' => $median,
                'arch' => '',
                'lastmod' => '',
            ];
            foreach ($cpuSamples as $sm) {
                $cpuStats['lastmod'] = max($cpuStats['lastmod'], (string)$sm['updated_at']);
            }
        }

        // cpus 表规格
        $cpuRow = $db->queryOne("SELECT model, architecture, cores, threads, base_frequency, max_frequency, process, tdp, description, highlights, use_cases FROM cpus WHERE model = ? LIMIT 1", [$cpuModel]);

    } catch (Exception $e) {
        error_log('cpu-detail failed for ' . $cpuModel . ': ' . $e->getMessage());
        $cpuModel = null;
    }
}

// ===== 404（真 404，参照 article.php 模式） =====
if ($cpuModel === null || $cpuStats === null) {
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow', true);
    $pageTitle = cpu_t('cpu.not_found_title', 'Page not found');
    $pageDesc = cpu_t('cpu.not_found', 'This CPU page does not exist or has not been added yet.');
} else {
    if ($currentLang === 'en-US') {
        $pageTitle = $cpuModel . cpu_t('cpu.title_suffix', ' NAS CPU benchmark (CoreMark)') . ' | ' . $siteName;
        $pageDesc = $cpuModel . ' CoreMark benchmark: best score ' . number_format($cpuStats['max']) .
            ', ranked #' . $cpuRank . ' among ' . $cpuTotal . ' CPUs recorded by ' . $siteName . ' (' .
            $cpuStats['count'] . ' real test samples). View specifications, nearby rankings and NAS devices.';
    } else {
        $pageTitle = $cpuModel . ' NAS 性能测试：CoreMark 跑分与天梯排名 | ' . $siteName;
        $pageDesc = $cpuModel . ' 的 CoreMark 实测跑分：最佳 ' . number_format($cpuStats['max']) .
            ' 分，在 ' . $siteName . ' 收录的 ' . $cpuTotal . ' 款 NAS CPU 中排名第 ' . $cpuRank .
            ' 位（' . $cpuStats['count'] . ' 份真实测试样本）。查看核心规格、相邻 CPU 对比与推荐 NAS 机型。';
    }
}

$cpuCanonical = $cpuSlug !== '' ? i18n_canonical_url($siteUrl . '/cpu/' . $cpuSlug, $currentLang) : '';
$esc = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

function cpu_detail_neighbor_name($model, $whitelist, $overrides, $language = 'zh-CN') {
    if (!in_array($model, $whitelist, true)) return $model;
    return '<a href="' . htmlspecialchars(i18n_localized_url('/cpu/' . cpu_detail_slugify($model, $overrides), $language), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($model, ENT_QUOTES, 'UTF-8') . '</a>';
}
?>
<!doctype html>
<html lang="<?php echo $esc($currentLang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $esc($pageTitle); ?></title>
    <meta name="description" content="<?php echo $esc($pageDesc); ?>">
    <?php if ($cpuCanonical !== ''): ?>
    <link rel="canonical" href="<?php echo $esc($cpuCanonical); ?>">
    <?php i18n_render_hreflang($siteUrl . '/cpu/' . $cpuSlug); ?>
    <?php else: ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <?php if ($cpuExtraQuery): ?>
    <meta name="robots" content="noindex,follow">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo $esc($siteName); ?>">
    <meta property="og:title" content="<?php echo $esc($pageTitle); ?>">
    <meta property="og:description" content="<?php echo $esc($pageDesc); ?>">
    <?php if ($cpuCanonical !== ''): ?>
    <meta property="og:url" content="<?php echo $esc($cpuCanonical); ?>">
    <?php endif; ?>
    <?php include('includes/head-meta.php'); ?>
    <style>
        .cpu-detail-wrap { max-width: 960px; margin: 0 auto; padding: 24px 16px 60px; }
        .cpu-breadcrumb { font-size: 13px; color: #64748b; margin: 12px 0 4px; }
        .cpu-breadcrumb a { color: #2563eb; text-decoration: none; }
        .cpu-h1 { font-size: 28px; font-weight: 700; color: #1e293b; margin: 8px 0 6px; }
        .cpu-sub { color: #475569; line-height: 1.7; margin: 0 0 20px; }
        .cpu-stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin: 20px 0; }
        .cpu-stat-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; text-align: center; }
        .cpu-stat-card .v { font-size: 20px; font-weight: 700; color: #1e293b; }
        .cpu-stat-card .k { font-size: 12px; color: #64748b; margin-top: 4px; }
        .cpu-section { margin: 34px 0; }
        .cpu-section h2 { font-size: 20px; color: #1e293b; border-left: 4px solid #2563eb; padding-left: 10px; margin: 0 0 14px; }
        table.cpu-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        table.cpu-table th, table.cpu-table td { border: 1px solid #e2e8f0; padding: 9px 12px; text-align: left; }
        table.cpu-table th { background: #f1f5f9; color: #334155; }
        table.cpu-table tr.cpu-current { background: #eff6ff; }
        table.cpu-table a { color: #2563eb; text-decoration: none; }
        .cpu-nas-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin: 10px 0; }
        .cpu-nas-card b { color: #1e293b; }
        .cpu-links a { display: inline-block; margin: 0 14px 8px 0; color: #2563eb; text-decoration: none; border-bottom: 1px dashed #93c5fd; }
        .cpu-note { font-size: 13px; color: #64748b; line-height: 1.7; }
    </style>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="cpu-detail-wrap">
<?php if ($cpuModel === null || $cpuStats === null): ?>
    <div style="text-align:center; padding:80px 20px;">
        <p style="font-size:20px; color:#475569;"><?php echo $esc(cpu_t('cpu.not_found', 'This CPU page does not exist or has not been added yet.')); ?></p>
        <a href="<?php echo $esc($localizedUrl('/')); ?>" style="color:#2563eb;"><?php echo $esc(cpu_t('cpu.back_home', 'Back to the NAS CPU performance ranking')); ?></a>
    </div>
<?php else: ?>
    <nav class="cpu-breadcrumb" aria-label="<?php echo $esc(cpu_t('cpu.breadcrumb_label', 'Breadcrumb')); ?>">
        <a href="<?php echo $esc($localizedUrl('/')); ?>"><?php echo $esc(cpu_t('cpu.breadcrumb_home', 'Home')); ?></a> › <a href="<?php echo $esc($localizedUrl('/#ranking')); ?>"><?php echo $esc(cpu_t('cpu.breadcrumb_ranking', 'NAS CPU ranking')); ?></a> › <?php echo $esc($cpuModel); ?>
    </nav>
    <h1 class="cpu-h1"><?php echo $esc($cpuModel); ?><?php echo $esc(cpu_t('cpu.title_suffix', ' NAS CPU benchmark (CoreMark)')); ?></h1>
    <p class="cpu-sub"><?php echo $esc(cpu_t('cpu.subtitle_prefix', 'Based on reviewed CoreMark submissions from real devices. There are ')); ?><?php echo $cpuStats['count']; ?><?php echo $esc(cpu_t('cpu.subtitle_middle', ' samples, ranking ')); ?>#<?php echo $cpuRank; ?><?php echo $esc(cpu_t('cpu.subtitle_total', ' among ')); ?><?php echo $cpuTotal; ?><?php echo $esc(cpu_t('cpu.subtitle_suffix', ' recorded CPUs. ')); ?><a href="<?php echo $esc($localizedUrl('/benchmark-methodology.php')); ?>"><?php echo $esc(cpu_t('cpu.method_link', 'Learn about the methodology and ranking rules')); ?></a>.</p>

    <div class="cpu-stats-grid">
        <div class="cpu-stat-card"><div class="v"><?php echo number_format($cpuStats['max']); ?></div><div class="k"><?php echo $esc(cpu_t('cpu.best', 'Best CoreMark score')); ?></div></div>
        <div class="cpu-stat-card"><div class="v"><?php echo number_format(round($cpuStats['avg'])); ?></div><div class="k"><?php echo $esc(cpu_t('cpu.average', 'Average score')); ?></div></div>
        <div class="cpu-stat-card"><div class="v"><?php echo number_format($cpuStats['median']); ?></div><div class="k"><?php echo $esc(cpu_t('cpu.median', 'Median score')); ?></div></div>
        <div class="cpu-stat-card"><div class="v">#<?php echo $cpuRank; ?> / <?php echo $cpuTotal; ?></div><div class="k"><?php echo $esc(cpu_t('cpu.rank', 'Ranking')); ?></div></div>
        <div class="cpu-stat-card"><div class="v"><?php echo $cpuStats['count']; ?></div><div class="k"><?php echo $esc(cpu_t('cpu.samples', 'Test samples')); ?></div></div>
        <?php if ($cpuRow): ?>
        <div class="cpu-stat-card"><div class="v"><?php echo $esc(cpu_localize_core_info((string)($cpuRow['cores'] ?: '-'), $currentLang)); ?></div><div class="k"><?php echo $esc(cpu_t('cpu.core_spec', 'Core specification')); ?></div></div>
        <?php if (!empty($cpuRow['tdp'])): ?><div class="cpu-stat-card"><div class="v"><?php echo $esc($cpuRow['tdp']); ?></div><div class="k"><?php echo $esc(cpu_t('cpu.tdp', 'TDP')); ?></div></div><?php endif; ?>
        <?php if (!empty($cpuRow['process'])): ?><div class="cpu-stat-card"><div class="v"><?php echo $esc($cpuRow['process']); ?></div><div class="k"><?php echo $esc(cpu_t('cpu.process', 'Process')); ?></div></div><?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="cpu-section">
        <h2><?php echo $esc(cpu_t('cpu.samples_detail', 'Test sample details')); ?></h2>
        <table class="cpu-table">
            <thead><tr><th><?php echo $esc(cpu_t('cpu.score', 'Score')); ?></th><th><?php echo $esc(cpu_t('cpu.device', 'Test device')); ?></th><th><?php echo $esc(cpu_t('cpu.system', 'System')); ?></th><th><?php echo $esc(cpu_t('cpu.cores', 'Cores')); ?></th><th><?php echo $esc(cpu_t('cpu.submitted', 'Submitted')); ?></th><th><?php echo $esc(cpu_t('cpu.status', 'Status')); ?></th></tr></thead>
            <tbody>
            <?php foreach ($cpuSamples as $sm): ?>
                <tr>
                    <td><?php echo number_format((float)$sm['score']); ?></td>
                    <td><?php echo $esc($sm['test_device'] ?: trim(($sm['device_brand'] ?? '') . ' ' . ($sm['device_model'] ?? '')) ?: cpu_t('cpu.not_provided', 'Not provided')); ?></td>
                    <td><?php echo $esc($sm['os_info'] ?: '-'); ?></td>
                    <td><?php echo $esc(cpu_localize_core_info((string)($sm['cores'] ?: '-'), $currentLang)); ?></td>
                    <td><?php echo $esc($sm['submitted_at'] ? date('Y-m-d', strtotime($sm['submitted_at'])) : '-'); ?></td>
                    <td><?php echo $esc(cpu_t('cpu.approved', 'Reviewed')); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="cpu-note"><?php echo $esc(cpu_t('cpu.sample_note', 'All samples come from real user devices and are manually reviewed. Anomalous results may be flagged or removed. If you have run CoreMark on this CPU, please submit your result to help improve the data.')); ?></p>
    </div>

    <div class="cpu-section">
        <h2><?php echo $esc(cpu_t('cpu.neighbors', 'Adjacent CPU rankings')); ?></h2>
        <table class="cpu-table">
            <thead><tr><th><?php echo $esc(cpu_t('cpu.rank', 'Ranking')); ?></th><th><?php echo $esc(cpu_t('cpu.cpu_model', 'CPU model')); ?></th><th><?php echo $esc(cpu_t('cpu.best_short', 'Best CoreMark')); ?></th><th><?php echo $esc(cpu_t('cpu.gap', 'Difference from ') . $cpuModel); ?></th></tr></thead>
            <tbody>
            <?php foreach ($cpuNeighbors as $nb): ?>
                <tr<?php echo $nb['model'] === $cpuModel ? ' class="cpu-current"' : ''; ?>>
                    <td>#<?php echo $nb['rank']; ?></td>
                    <td><?php echo cpu_detail_neighbor_name($nb['model'], $cpuWhitelist, $cpuSlugOverrides, $currentLang); ?></td>
                    <td><?php echo number_format($nb['best']); ?></td>
                    <td><?php
                        if ($nb['model'] === $cpuModel) { echo '—'; }
                        else {
                            $diff = ($nb['best'] - $cpuStats['max']) / max(1, $cpuStats['max']) * 100;
                            echo ($diff >= 0 ? '+' : '') . number_format($diff, 1) . '%';
                        }
                    ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="cpu-note"><?php echo $esc(cpu_t('cpu.full_ranking', 'See the full NAS CPU ranking on the home page.')); ?></p>
    </div>

    <?php
    $cpuOverview = $cpuRow ? (string)($cpuRow['description_en'] ?? '') : '';
    if ($currentLang !== 'en-US' || trim($cpuOverview) === '') {
        $cpuOverview = $cpuRow ? (string)($cpuRow['description'] ?? '') : '';
    }
    $cpuUseCases = $cpuRow ? (string)($cpuRow['use_cases_en'] ?? '') : '';
    if ($currentLang !== 'en-US' || trim($cpuUseCases) === '') {
        $cpuUseCases = $cpuRow ? (string)($cpuRow['use_cases'] ?? '') : '';
    }
    ?>
    <?php if ($cpuRow && trim($cpuOverview) !== ''): ?>
    <div class="cpu-section">
        <h2><?php echo $esc($cpuModel . cpu_t('cpu.overview', ' overview and use cases')); ?></h2>
        <p class="cpu-sub"><?php echo $esc($cpuOverview); ?></p>
        <?php if (trim($cpuUseCases) !== ''): ?><p class="cpu-note"><?php echo $esc(cpu_t('cpu.use_cases', 'Use cases: ') . $cpuUseCases); ?></p><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php
    // 相关对比互链（计划书 4.2：对比页 <-> CPU 详情页互链）
    $cpuCompareLinks = [];
    if (file_exists(__DIR__ . '/includes/cpu-compare-pairs.php')) {
        require_once __DIR__ . '/includes/cpu-compare-pairs.php';
        foreach ($GLOBALS['CPU_COMPARE_PAIRS'] as $cpuPair) {
            if ($cpuPair['a'] === $cpuModel || $cpuPair['b'] === $cpuModel) {
                $cpuCompareLinks[] = '<a href="' . htmlspecialchars($localizedUrl('/cpu/' . cpu_detail_slugify($cpuPair['a']) . '-vs-' . cpu_detail_slugify($cpuPair['b'])), ENT_QUOTES, 'UTF-8') . '">' 
                    . htmlspecialchars($cpuPair['a'] . ' vs ' . $cpuPair['b'], ENT_QUOTES, 'UTF-8') . '</a>';
            }
        }
    }
    ?>
    <?php if ($cpuCompareLinks): ?>
    <div class="cpu-section">
        <h2><?php echo $esc($cpuModel . ' ' . cpu_t('cpu.related_comparisons', 'Related comparisons')); ?></h2>
        <div class="cpu-links"><?php echo implode('', $cpuCompareLinks); ?></div>
    </div>
    <?php endif; ?>

    <div class="cpu-section">
        <h2><?php echo $esc(cpu_t('cpu.tools', 'Related links')); ?></h2>
        <div class="cpu-links">
            <a href="<?php echo $esc($localizedUrl('/benchmark-methodology.php')); ?>"><?php echo $esc(cpu_t('cpu.method_link', 'Benchmark methodology and ranking rules')); ?></a>
        </div>
    </div>
    </div>

    <script type="application/ld+json">
    <?php echo json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => cpu_t('cpu.breadcrumb_home', 'Home'), 'item' => $localizedUrl($siteUrl . '/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => cpu_t('cpu.breadcrumb_ranking', 'NAS CPU ranking'), 'item' => $localizedUrl($siteUrl . '/#ranking')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $cpuModel],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    </script>
    <script type="application/ld+json">
    <?php echo json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Dataset',
        'name' => $cpuModel . cpu_t('cpu.dataset_name', ' CoreMark benchmark data'),
        'description' => $pageDesc,
        'url' => $cpuCanonical,
        'creator' => ['@type' => 'Organization', 'name' => $siteName, 'url' => $siteUrl . '/'],
        'variableMeasured' => ['CoreMark score'],
        'measurementTechnique' => cpu_t('cpu.dataset_technique', 'CoreMark (submitted from real user devices and manually reviewed)'),
        'isAccessibleForFree' => true,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    </script>
<?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
