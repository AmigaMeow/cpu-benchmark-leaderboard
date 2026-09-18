<?php
/**
 * SEO 阶段三 4.3：Benchmark Methodology 页
 * 说明 CoreMark 测试方法、数据来源、审核与排名规则——CPU 页与排行榜引用的落地点。
 */
require_once(__DIR__ . '/includes/i18n.php');
require_once(__DIR__ . '/includes/settings.php');
$siteUrl = rtrim((string)SiteSettings::getSiteUrl(), '/');
$siteName = SiteSettings::getSiteName();
require_once(__DIR__ . '/includes/cpu-i18n.php');

$currentLang = i18n_lang();
$localeColon = $currentLang === 'en-US' ? ':' : '：';
$pageTitle = cpu_t('method.title', 'CoreMark 测试方法与排名规则') . ' | ' . $siteName;
$seoKeywords = 'CoreMark,benchmark methodology,NAS CPU benchmark';
$siteDescription = cpu_t('method.intro', '本站 NAS CPU 天梯图与各 CPU 实体页基于真实设备提交的 CoreMark 成绩。');
$canonicalUrl = $siteUrl . '/benchmark-methodology.php';
$selfCanonicalUrl = i18n_canonical_url($canonicalUrl);
$extraQuery = array_diff(array_keys($_GET), ['lang']);
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="keywords" content="<?php echo htmlspecialchars($seoKeywords); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($siteDescription); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($selfCanonicalUrl); ?>">
<?php i18n_render_hreflang($canonicalUrl); ?>
<?php if ($extraQuery || i18n_lang() !== 'zh-CN'): ?>
    <!-- 本页内容为纯中文（SEO 2.3）：lang 变体与其他查询参数同样 noindex,follow -->
    <meta name="robots" content="noindex,follow">
<?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($siteDescription); ?>">
    <?php include('includes/head-meta.php'); ?>
    <style>
        .method-wrap { max-width: 860px; margin: 0 auto; padding: 24px 16px 60px; }
        .method-wrap h1 { font-size: 26px; color: #1e293b; margin: 12px 0 6px; }
        .method-wrap h2 { font-size: 19px; color: #1e293b; border-left: 4px solid #2563eb; padding-left: 10px; margin: 30px 0 10px; }
        .method-wrap p, .method-wrap li { color: #475569; line-height: 1.8; font-size: 15px; }
        .method-wrap ul, .method-wrap ol { padding-left: 22px; }
        .method-note { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; margin: 16px 0; }
    </style>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="method-wrap">
    <h1><?php echo htmlspecialchars(cpu_t('method.title', 'CoreMark 测试方法与排名规则'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p><?php echo htmlspecialchars(cpu_t('method.intro', '本站 NAS CPU 天梯图与各 CPU 实体页基于真实设备提交的 CoreMark 成绩。'), ENT_QUOTES, 'UTF-8'); ?></p>

    <h2><?php echo cpu_t('method.why', '为什么用 CoreMark'); ?></h2>
    <p><?php echo htmlspecialchars(cpu_t('method.why_text', 'CoreMark 是 EEMBC 发布的行业标准 CPU 整数性能测试。'), ENT_QUOTES, 'UTF-8'); ?></p>

    <h2><?php echo cpu_t('method.source', '数据来源与测试流程'); ?></h2>
    <ol>
        <li><?php echo htmlspecialchars(cpu_t('method.step1', '用户在自己的设备上运行 CoreMark。'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(cpu_t('method.step2', '通过提交页上传成绩。'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(cpu_t('method.step3', '成绩进入审核队列。'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(cpu_t('method.step4', '每条记录保留设备与系统上下文。'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ol>

    <h2><?php echo cpu_t('method.anomaly', '异常成绩处理'); ?></h2>
    <ul>
        <li><?php echo htmlspecialchars(cpu_t('method.anomaly1', '异常成绩会被标记或拒绝。'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(cpu_t('method.anomaly2', '排行榜取最佳有效成绩参与排名。'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(cpu_t('method.anomaly3', '请通过反馈页举报异常数据。'), ENT_QUOTES, 'UTF-8'); ?></li>
    </ul>

    <h2><?php echo cpu_t('method.algorithm', '排名算法'); ?></h2>
    <p><?php echo htmlspecialchars(cpu_t('method.algorithm_text', '按已审核提交的每型号最佳 CoreMark 成绩降序排列。'), ENT_QUOTES, 'UTF-8'); ?></p>

    <h2><?php echo cpu_t('method.variance', '不同系统成绩为何有差异'); ?></h2>
    <p><?php echo htmlspecialchars(cpu_t('method.variance_text', 'CoreMark 成绩会受编译器、系统调度与散热影响。'), ENT_QUOTES, 'UTF-8'); ?></p>

    <div class="method-note">
        <b><?php echo cpu_t('method.updated', '数据更新'); ?><?php echo $localeColon; ?></b><?php echo htmlspecialchars(cpu_t('method.updated_text', '排行榜与 CPU 页面随审核实时更新。'), ENT_QUOTES, 'UTF-8'); ?><br>
        <b><?php echo cpu_t('method.citation', '引用本站数据'); ?><?php echo $localeColon; ?></b><?php echo htmlspecialchars(cpu_t('method.citation_text', '欢迎引用本站排行榜与 CPU 页面数据，请注明来源。'), ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <h2><?php echo cpu_t('method.related', '相关页面'); ?></h2>
    <ul>
        <li><a href="<?php echo htmlspecialchars(i18n_localized_url('/'), ENT_QUOTES, 'UTF-8'); ?>" style="color:#2563eb;"><?php echo cpu_t('method.home', 'NAS CPU 性能天梯图（完整排行榜）'); ?></a></li>
        <li><a href="<?php echo htmlspecialchars(i18n_localized_url('/cpu/intel-n100'), ENT_QUOTES, 'UTF-8'); ?>" style="color:#2563eb;">Intel N100 <?php echo cpu_t('method.sample_n100', '性能测试页（实体页示例）'); ?></a></li>
        <li><a href="<?php echo htmlspecialchars(i18n_localized_url('/cpu/rk3588'), ENT_QUOTES, 'UTF-8'); ?>" style="color:#2563eb;">RK3588 <?php echo cpu_t('method.sample_rk3588', '性能测试页'); ?></a></li>
        <li><a href="<?php echo htmlspecialchars(i18n_localized_url('/'), ENT_QUOTES, 'UTF-8'); ?>" style="color:#2563eb;"><?php echo cpu_t('method.back_home', '返回天梯榜'); ?></a></li>
    </ul>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
