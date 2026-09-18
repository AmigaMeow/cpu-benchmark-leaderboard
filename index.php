<?php
// 强制禁用缓存 - 紧急修复
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// /index.php is a duplicate of /. Internal nginx index redirects keep
// REQUEST_URI as "/", so this only fires on the public /index.php URL.
$indexReq = $_SERVER['REQUEST_URI'] ?? '';
if (preg_match('#^/index\.php(?:\?|$)#', $indexReq)) {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: /' . ($qs !== '' ? '?' . $qs : ''), true, 301);
    exit;
}

// 加载i18n系统
require_once(__DIR__ . '/includes/i18n.php');
require_once(__DIR__ . '/includes/cpu-i18n.php');
$i18n = I18n::getInstance();

// 加载网站设置 - 启用动态SEO
require_once(__DIR__ . '/includes/settings.php');

// 获取SEO设置 - 优先使用i18n的SEO翻译，如果没有则使用默认设置
$seoData = $i18n->seo('home');
$pageTitle = $seoData['meta_title'] ?: SiteSettings::getHomepageTitle();
$seoKeywords = $seoData['meta_keywords'] ?: SiteSettings::getSeoKeywords();
$siteDescription = $seoData['meta_description'] ?: SiteSettings::getSiteDescription();
$ogTitle = $seoData['og_title'] ?: SiteSettings::getHomepageOgTitle();
$ogDescription = $seoData['og_description'] ?: SiteSettings::getHomepageOgDescription();
$siteName = SiteSettings::getSiteName();
$siteUrl = SiteSettings::getSiteUrl();
$currentLang = $i18n->getCurrentLanguage();
$localeColon = $currentLang === 'en-US' ? ':' : '：';
$localeSentenceSeparator = $currentLang === 'en-US' ? '. ' : '；';
// CoreMark 一键跑分脚本地址（可换为你自己托管的脚本）
$coremarkScriptBase = SiteSettings::get('coremark_script_url', '/coremark/run.sh');
$coremarkScriptUrl = $coremarkScriptBase . '?lang=' . rawurlencode($currentLang);
$coremarkCurlCommand = "curl -fsSL '" . $coremarkScriptUrl . "' | sh";
$coremarkWgetCommand = "wget -qO- '" . $coremarkScriptUrl . "' | sh";
$coremarkFaqText = str_replace(
    'curl -fsSL ' . $coremarkScriptBase . ' | sh',
    $coremarkCurlCommand,
    __('home.schema.faq_q2_text')
);

// 获取最后审核通过的数据时间
$pdo = null;
try {
    $config = require(__DIR__ . '/config/database.php');
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $stmt = $pdo->prepare("SELECT MAX(updated_at) as last_approved FROM benchmark_submissions WHERE status = 'approved'");
    $stmt->execute();
    $lastApprovedTime = $stmt->fetchColumn();

    if ($lastApprovedTime) {
        $lastUpdateDate = $currentLang === 'en-US'
            ? date('M j, Y', strtotime($lastApprovedTime))
            : date('Y年n月j日', strtotime($lastApprovedTime));
    } else {
        $lastUpdateDate = $currentLang === 'en-US' ? date('M j, Y') : date('Y年n月j日');
    }
} catch (Exception $e) {
    $lastUpdateDate = $currentLang === 'en-US' ? date('M j, Y') : date('Y年n月j日');
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
    <!-- 页面版本: 2025-12-04-v3-NOCACHE - 添加备注显示功能 + 强制禁用缓存 -->
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="format-detection" content="telephone=no"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <!-- SEO Meta Tags - 动态加载自后台设置 -->
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="keywords" content="<?php echo htmlspecialchars($seoKeywords); ?>"/>
    <meta name="description" content="<?php echo htmlspecialchars($siteDescription); ?>"/>
    <meta name="author" content="<?php echo htmlspecialchars($siteName); ?>">
<?php
    $homeBase = rtrim((string)$siteUrl, '/') . '/';
    $canonicalUrl = i18n_canonical_url($homeBase);
    $indexExtraQuery = array_diff(array_keys($_GET), ['lang']);
?>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">
<?php i18n_render_hreflang($homeBase); ?>
<?php if ($indexExtraQuery): ?>
    <meta name="robots" content="noindex,follow">
<?php endif; ?>

<?php require_once(__DIR__ . '/includes/version.php'); ?>
<?php
// 首页需要加载 layout.css 用于表格样式；独立修订号用于刷新长期缓存
$extra_css = ['/assets/css/layout.css?home_layout=2026082414'];
?>
<?php include('includes/head-meta.php'); ?>

    <!-- 首页搜索工具栏首屏样式：避免 body 内样式到达前出现裸文字/裸输入框闪烁 -->
    <style>
        .benchmark-toolbar {
            max-width: none;
            margin: 16px auto 0;
            padding: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: space-between;
        }

        .benchmark-toolbar.benchmark-toolbar-hidden {
            display: none !important;
        }

        .benchmark-search {
            flex: 1;
            min-width: 0;
        }

        .benchmark-search input {
            width: 100%;
            height: 44px;
            padding: 0 16px;
            border: 1px solid #d8dee6;
            border-radius: 12px;
            background: #fff;
            color: #1f2937;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .benchmark-search input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .benchmark-toggle-btn {
            flex-shrink: 0;
            height: 44px;
            padding: 0 16px;
            border-radius: 12px;
            border: 1px solid #d8dee6;
            background: #fff;
            color: #475569;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .benchmark-toggle-btn:hover {
            border-color: #94a3b8;
            color: #1f2937;
        }

        .benchmark-toggle-btn.benchmark-toggle-active {
            background: linear-gradient(135deg, #0f766e 0%, #0f9f8f 100%);
            border-color: #0f766e;
            color: #fff;
            box-shadow: 0 10px 24px rgba(15, 118, 110, 0.18);
        }

        @media (max-width: 768px) {
            .benchmark-toolbar {
                padding: 0;
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($siteUrl); ?>/">
    <meta property="og:title" content="<?php echo htmlspecialchars($ogTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($ogDescription); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName); ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($siteUrl); ?>/">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($ogTitle); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($ogDescription); ?>">

    <!-- Schema.org 结构化数据 -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?php echo addslashes(__('home.schema.website_name')); ?>",
        "url": "<?php echo addslashes((string)$siteUrl); ?>",
        "description": "<?php echo addslashes(__('home.schema.website_description')); ?>"
    }
    </script>

    <!-- 面包屑导航 -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "<?php echo addslashes(__('home.schema.breadcrumb_home')); ?>",
                "item": "<?php echo addslashes((string)$homeBase); ?>"
            }
        ]
    }
    </script>

    <!-- CPU性能排行榜 ItemList 结构化数据 -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "<?php echo addslashes(__('home.schema.itemlist_name')); ?>",
        "description": "<?php echo addslashes(__('home.schema.itemlist_description')); ?>",
        "url": "<?php echo addslashes((string)$homeBase); ?>",
        "numberOfItems": 100,
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "item": {
                    "@type": "Thing",
                    "name": "<?php echo addslashes(__('home.schema.itemlist_item_name')); ?>",
                    "description": "<?php echo addslashes(__('home.schema.itemlist_item_description')); ?>"
                }
            }
        ]
    }
    </script>
</head>
<?php include('includes/header.php'); ?>
<script>
window.CURRENT_LANGUAGE = <?php echo json_encode($currentLang, JSON_UNESCAPED_UNICODE); ?>;
window.I18N = <?php echo json_encode([
    'loading' => __('home.loading'),
    'no_data' => __('home.no_data'),
    'last_update_prefix' => __('home.stats.last_update'),
    'ranking_board' => __('home.stats.performance'),
    'guide_title' => __('home.guide_title'),
    'guide_intro' => __('home.guide_intro'),
    'guide_step1_title' => __('home.guide_step1_title'),
    'guide_step1_desc' => __('home.guide_step1_desc'),
    'guide_step2_title' => __('home.guide_step2_title'),
    'guide_step2_desc' => __('home.guide_step2_desc'),
    'guide_or' => __('home.guide_or'),
    'guide_step3_title' => __('home.guide_step3_title'),
    'guide_step3_desc_prefix' => __('home.guide_step3_desc_prefix'),
    'guide_step3_link' => __('home.guide_step3_link'),
    'guide_step3_desc_suffix' => __('home.guide_step3_desc_suffix'),
    'notice_title' => __('home.notice_title'),
    'notice_body' => __('home.notice_body'),
    'detail_title_suffix' => __('home.detail_title_suffix'),
    'unknown_device' => __('home.unknown_device'),
    'unknown' => __('home.unknown'),
    'anonymous' => __('home.anonymous'),
    'detail_test_device' => __('home.detail_test_device'),
    'detail_cores' => __('home.detail_cores'),
    'detail_submit_time' => __('home.detail_submit_time'),
    'detail_score' => __('home.detail_score'),
    'data_reference_note' => __('home.data_reference_note'),
    'detail_load_failed_basic' => __('home.detail_load_failed_basic'),
    'load_failed_prefix' => __('home.load_failed_prefix'),
    'load_failed_retry' => __('home.load_failed_retry'),
    'locale_colon' => $localeColon,
    'ranking_x86_label' => __('home.ranking_x86_label'),
    'ranking_arm_label' => __('home.ranking_arm_label'),
    'export_x86_arch' => __('home.export_x86_arch'),
    'export_arm_arch' => __('home.export_arm_arch'),
    'export_data_loading_alert' => __('home.export_data_loading_alert'),
    'export_library_loading_alert' => __('home.export_library_loading_alert'),
    'export_no_data_error' => __('home.export_no_data_error'),
    'export_generate_failed_prefix' => __('home.export_generate_failed_prefix'),
    'retry' => __('home.retry'),
    'export_generate_preview_first_alert' => __('home.export_generate_preview_first_alert'),
    'export_filename_prefix' => __('home.export_filename_prefix'),
    'search_placeholder' => cpu_t('runtime.search_placeholder', '搜索 CPU / 设备型号，例如 N100、J4125、RK3588'),
    'nas_only' => cpu_t('runtime.nas_only', '只看 NAS 相关'),
    'all_cpu' => cpu_t('runtime.all_cpu', '显示全部 CPU'),
    'export_scope' => cpu_t('runtime.export_scope', '导出范围'),
    'export_current' => cpu_t('runtime.export_current', '跟随当前筛选'),
    'export_all' => cpu_t('runtime.export_all', '显示全部 CPU'),
    'arch_x86' => cpu_t('runtime.arch_x86', 'x86_64 架构'),
    'arch_arm' => cpu_t('runtime.arch_arm', 'ARM64 架构'),
    'search_help' => cpu_t('runtime.search_help', '数据来自用户真实设备提交的 CoreMark 成绩（人工审核）。'),
    'methodology_link' => cpu_t('runtime.methodology_link', '查看测试方法与排名规则'),
    'details_help' => cpu_t('runtime.details_help', '点击 CPU 名称可查看该处理器的实测详情页。'),
    'runtime.category_nas_common' => cpu_t('runtime.category_nas_common', '成品 NAS 常见'),
    'runtime.category_diy_low_power' => cpu_t('runtime.category_diy_low_power', 'DIY 低功耗'),
    'runtime.category_router_common' => cpu_t('runtime.category_router_common', '软路由常见'),
    'runtime.category_domestic' => cpu_t('runtime.category_domestic', '国产芯片'),
    'runtime.category_server_workstation' => cpu_t('runtime.category_server_workstation', '服务器 / 工作站'),
    'runtime.category_high_performance' => cpu_t('runtime.category_high_performance', '高性能桌面 / 移动'),
    'runtime.category_mobile_soc' => cpu_t('runtime.category_mobile_soc', '手机 / 平板'),
    'runtime.category_legacy' => cpu_t('runtime.category_legacy', '旧款平台'),
    'runtime.category_other' => cpu_t('runtime.category_other', '其他'),
    'runtime.badge_router' => cpu_t('runtime.badge_router', '软路由'),
    'runtime.badge_server' => cpu_t('runtime.badge_server', '服务器'),
    'runtime.badge_high_performance' => cpu_t('runtime.badge_high_performance', '高性能'),
    'runtime.badge_mobile' => cpu_t('runtime.badge_mobile', '移动端'),
    'runtime.badge_legacy' => cpu_t('runtime.badge_legacy', '旧款'),
    'runtime.badge_non_nas' => cpu_t('runtime.badge_non_nas', '非 NAS 设备'),
    'runtime.badge_non_typical' => cpu_t('runtime.badge_non_typical', '非典型 NAS'),
    'runtime.badge_high_power' => cpu_t('runtime.badge_high_power', '高功耗'),
    'runtime.scenario_generic' => cpu_t('runtime.scenario_generic', '通用 NAS 平台'),
    'runtime.scenario_mobile' => cpu_t('runtime.scenario_mobile', '移动平台 NAS'),
    'runtime.scenario_router' => cpu_t('runtime.scenario_router', '路由器 / 网关'),
    'runtime.scenario_server' => cpu_t('runtime.scenario_server', '服务器与工作站'),
    'runtime.scenario_high_performance' => cpu_t('runtime.scenario_high_performance', '高性能 NAS'),
    'runtime.scenario_advanced' => cpu_t('runtime.scenario_advanced', '进阶自托管'),
    'runtime.scenario_mainstream' => cpu_t('runtime.scenario_mainstream', '主流家庭 NAS'),
    'runtime.scenario_entry' => cpu_t('runtime.scenario_entry', '入门家庭 NAS'),
    'runtime.scenario_basic' => cpu_t('runtime.scenario_basic', '基础存储 NAS'),
    'runtime.tasks' => cpu_t('runtime.tasks', '典型任务'),
    'runtime.limitations' => cpu_t('runtime.limitations', '限制因素'),
    'runtime.cpu_count' => cpu_t('runtime.cpu_count', '{count} 款 CPU'),
    'runtime.filtered_count' => cpu_t('runtime.filtered_count', '显示 {count} / 共 {total} 款 CPU'),
    'runtime.loading_error' => cpu_t('runtime.loading_error', '加载失败：{message}'),
    'runtime.network_error' => cpu_t('runtime.network_error', '网络错误：{message}'),
    'runtime.cores_threads' => cpu_t('runtime.cores_threads', '核心 / 线程'),
    'runtime.benchmark_score' => cpu_t('runtime.benchmark_score', 'CoreMark 跑分'),
    'runtime.tdp_power' => cpu_t('runtime.tdp_power', 'TDP / 功耗'),
    'runtime.efficiency' => cpu_t('runtime.efficiency', '能效比'),
    'runtime.data_source' => cpu_t('runtime.data_source', '数据来源'),
    'runtime.no_results' => cpu_t('runtime.no_results', '没有匹配的 CPU'),
    'runtime.copy_success' => cpu_t('runtime.copy_success', '已复制到剪贴板'),
    'runtime.copy_failed' => cpu_t('runtime.copy_failed', '复制失败，请手动复制'),
    'runtime.download_preview' => cpu_t('runtime.download_preview', '下载预览'),
    'runtime.export_model' => cpu_t('runtime.export_model', 'CPU 型号'),
    'runtime.export_cores' => cpu_t('runtime.export_cores', '核心'),
    'runtime.export_tdp' => cpu_t('runtime.export_tdp', 'TDP'),
    'runtime.export_score' => cpu_t('runtime.export_score', '跑分'),
    'runtime.test_data_title' => cpu_t('runtime.test_data_title', '实测跑分数据'),
    'runtime.loading' => cpu_t('runtime.loading', '加载中...'),
    'runtime.unknown_device' => cpu_t('runtime.unknown_device', '未知设备'),
    'runtime.unknown' => cpu_t('runtime.unknown', '未知'),
    'runtime.anonymous' => cpu_t('runtime.anonymous', '匿名'),
    'runtime.data_source_note' => cpu_t('runtime.data_source_note', '数据来源于实际测试，仅供参考'),
    'runtime.export_no_data' => cpu_t('runtime.export_no_data', '没有可用的数据'),
    'runtime.export_library_loading' => cpu_t('runtime.export_library_loading', '图片生成库还未加载完成，请稍后再试'),
    'runtime.export_failed' => cpu_t('runtime.export_failed', '生成失败：{message}'),
    'runtime.export_retry' => cpu_t('runtime.export_retry', '重试'),
    'runtime.export_preview_first' => cpu_t('runtime.export_preview_first', '请先生成预览'),
    'runtime.continuously_updated' => cpu_t('runtime.continuously_updated', '持续更新'),
    'runtime.exported' => cpu_t('runtime.exported', '导出时间'),
    'runtime.total_cpus' => cpu_t('runtime.total_cpus', 'CPU 总数'),
    'runtime.scope' => cpu_t('runtime.scope', '范围'),
    'runtime.model' => cpu_t('runtime.model', '型号'),
    'runtime.cores' => cpu_t('runtime.cores', '核心'),
    'runtime.tdp' => cpu_t('runtime.tdp', '功耗'),
    'runtime.full_ranking' => cpu_t('runtime.full_ranking', '完整天梯图'),
    'runtime.docker_supported' => cpu_t('runtime.docker_supported', '支持 Docker')

], JSON_UNESCAPED_UNICODE); ?>;
const I18N = window.I18N;
const siteName = <?php echo json_encode($siteName, JSON_UNESCAPED_UNICODE); ?>;
const siteUrl = <?php echo json_encode((string)$siteUrl, JSON_UNESCAPED_UNICODE); ?>;
</script>

<!-- Swup 主容器 - 页面过渡动画容器 -->
<main id="swup" class="transition-fade">

<!-- 主内容区域 -->
<div id="mainLayout">
    <h1 id="caption"><?php echo __('home.heading_prefix'); ?><b><?php echo __('home.heading'); ?></b></h1>

    <!-- 更新时间标识 -->
    <div class="stats-banner">
        <div class="stats-container">
            <div class="stat-item">
                <i class="fas fa-chart-line"></i>
                <strong><?php echo __('home.stats.performance'); ?></strong>
            </div>
            <div class="stat-item">
                <i class="fas fa-clock"></i>
                <span id="lastUpdateTime"><?php echo __('home.stats.last_update'); ?><?php echo $localeColon; ?> <?php echo htmlspecialchars($lastUpdateDate); ?></span>
            </div>
            <div class="stat-item">
                <i class="fas fa-database"></i>
                <span id="totalCPUs"><?php echo __('home.loading'); ?></span>
            </div>
        </div>
    </div>

    <p class="notice p-2">
        <b><?php echo __('home.notice_title'); ?></b><?php echo __('home.notice_body'); ?>
        <a href="/benchmark-methodology.php" class="faq-link">
            <i class="fas fa-question-circle"></i> <?php echo htmlspecialchars(cpu_t('runtime.methodology_link', '查看测试方法与排名规则')); ?>
        </a>
    </p>

    <!-- 筛选按钮 -->
    <div id="filterButtons">
        <a class="downBtn allcpu" id="btn1" href="javascript:void(0)" onclick="filterArch('')"><b><?php echo __('home.filter.all'); ?></b></a>
        <a class="downBtn" id="btn2" href="javascript:void(0)" onclick="filterArch('ARM')"><b><?php echo __('home.filter.arm'); ?></b></a>
        <a class="downBtn" id="btn3" href="javascript:void(0)" onclick="filterArch('x86_64')"><b><?php echo __('home.filter.x86'); ?></b></a>
        <a class="downBtn ranking-btn" id="btn4" href="javascript:void(0)" onclick="showRanking()" style="background: transparent; color: #dc2626; border: 2px solid #dc2626;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle;margin-right:4px;margin-top:-2px;">
                <rect width="24" height="24" fill="none"/>
                <g fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8 10.167l4.123-4.124c1.125-1.124 1.688-1.687 2.308-2.14A9.9 9.9 0 0 1 18.74 2.12C19.499 2 20.293 2 21.885 2c.083 0 .115.038.115.115c0 1.59 0 2.386-.119 3.145a9.9 9.9 0 0 1-1.784 4.309c-.453.62-1.016 1.183-2.14 2.308L13.833 16"/>
                    <path stroke-linejoin="round" d="M10.341 8.098c-1.703 0-3.843-.36-5.437.3C3.737 8.88 2.878 10 2 10.878l3.306 1.418c.876.375.34 1.48.195 2.206c-.161.808-.152.838.43 1.42l2.147 2.146c.582.583.612.592 1.42.43c.725-.145 1.831-.68 2.206.196L13.121 22c.878-.878 1.998-1.737 2.481-2.904c.66-1.594.3-3.734.3-5.437"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 20l-1 1m-7-9l-1 1"/>
                    <path stroke-linecap="square" d="M15 4.08c1.2.18 2.46.66 3.161 1.38c.897.792 1.519 1.86 1.759 3.54"/>
                    <path stroke-linecap="round" d="M17.94 6.06L16.5 7.5"/>
                </g>
            </svg>
            <b style="display:inline-block;vertical-align:middle;"><?php echo __('home.ranking_tab'); ?></b>
        </a>
    </div>

    <!-- 天梯榜容器 -->
    <div class="ranking-container" id="rankingContainer" style="display:none;">
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i> <?php echo __('home.loading'); ?>
        </div>
    </div>

    <!-- 表格内容容器 -->
    <div id="tableContentWrapper">
    <div class="benchmark-toolbar">
        <div class="benchmark-search">
            <input type="search" id="benchmarkSearchInput" placeholder="<?php echo htmlspecialchars(cpu_t('runtime.search_placeholder', '搜索 CPU / 设备型号，例如 N100、J4125、RK3588'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
        </div>
        <button type="button" id="benchmarkNasToggle" class="benchmark-toggle-btn benchmark-toggle-active" onclick="toggleNasOnly()">
            <?php echo htmlspecialchars(cpu_t('runtime.nas_only', '只看 NAS 相关'), ENT_QUOTES, 'UTF-8'); ?>
        </button>
    </div>
    <p style="font-size: 12px; color: #94a3b8; margin: 6px 2px 10px;">
        <?php echo htmlspecialchars(cpu_t('runtime.search_help', '数据来自用户真实设备提交的 CoreMark 成绩（人工审核）。'), ENT_QUOTES, 'UTF-8'); ?> <a href="<?php echo htmlspecialchars(i18n_localized_url('/benchmark-methodology.php'), ENT_QUOTES, 'UTF-8'); ?>" style="color:#2563eb;"><?php echo htmlspecialchars(cpu_t('runtime.methodology_link', '查看测试方法与排名规则'), ENT_QUOTES, 'UTF-8'); ?></a><?php echo $localeSentenceSeparator; ?><?php echo htmlspecialchars(cpu_t('runtime.details_help', '点击 CPU 名称可查看该处理器的实测详情页。'), ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <style>
        a.cpu-name-text, a.mobile-card-cpu { color: inherit; text-decoration: none; cursor: pointer; }
        a.cpu-name-text:hover, a.mobile-card-cpu:hover { color: #2563eb; text-decoration: underline; }
        /* 移动端：CSS 先于 JS 切换排行榜形态（阈值与 main.js innerWidth<=767 一致），
           避免刷新时桌面表格在窄屏闪现 */
        @media (max-width: 767px) {
            #mainForm { display: none !important; }
            #mobileCards { display: block !important; }
        }
    </style>
    <!-- 移动端卡片容器 -->
    <div id="mobileCards" style="display:none;"><?php
    // SEO 3.3：排行榜桌面行与移动卡片均服务端渲染；
    // 移动端由上面的 CSS 立即显示卡片，JS 加载后重绘全量数据
    $ssrBenchRows = null;
    $ssrBenchCards = null;
    try {
        require_once __DIR__ . '/includes/ssr-benchmark-rows.php';
        require_once __DIR__ . '/includes/ssr-benchmark-cards.php';
        if ($pdo instanceof PDO) {
            $ssrBenchRows = ssr_render_benchmark_rows($pdo, 30, $currentLang);
            $ssrBenchCards = ssr_render_benchmark_mobile_cards($pdo, 30, $currentLang);
        }
    } catch (Throwable $ssrBenchEx) {
        error_log('index benchmark SSR failed: ' . $ssrBenchEx->getMessage());
    }
    if (!empty($ssrBenchCards)) {
        echo $ssrBenchCards;
    }
    ?></div>
    <!-- 数据表格（桌面端） -->
    <table class="data" id="mainForm" border="0">
        <colgroup>
            <col class="cpu-name-column">
            <col>
        </colgroup>
        <tbody id="benchmarkBody"<?php if (!empty($ssrBenchRows)) echo ' data-ssr="1"'; ?>>
            <?php if (!empty($ssrBenchRows)): ?>
            <?php echo $ssrBenchRows; ?>
            <?php else: ?>
            <tr>
                <td colspan="2" style="text-align: center; padding: 40px;">
                    <?php echo __('home.loading'); ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- CoreMark 测试指南 - 优化版 -->
    <div class="coremark-guide">
        <div class="guide-header">
            <svg class="guide-icon" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <g fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z"/>
                    <path stroke-linecap="round" d="m9 12 2 2 4-4"/>
                </g>
            </svg>
            <h2><?php echo __('home.guide_title'); ?></h2>
        </div>

        <div class="guide-info">
            <p><?php echo __('home.guide_intro'); ?></p>
            <div class="guide-tags">
                <span class="tag">x86_64</span>
                <span class="tag">ARM64</span>
                <span class="tag">ARMv7</span>
            </div>
        </div>

        <div class="guide-steps">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3><?php echo __('home.guide_step1_title'); ?></h3>
                    <p><?php echo __('home.guide_step1_desc'); ?></p>
                </div>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3><?php echo __('home.guide_step2_title'); ?></h3>
                    <p><?php echo __('home.guide_step2_desc'); ?></p>

                    <div class="command-box">
                        <code><?php echo htmlspecialchars($coremarkCurlCommand, ENT_QUOTES, 'UTF-8'); ?></code>
                        <button class="copy-btn" onclick="copyCommand(<?php echo htmlspecialchars(json_encode($coremarkCurlCommand, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>

                    <div class="command-divider"><?php echo __('home.guide_or'); ?></div>

                    <div class="command-box">
                        <code><?php echo htmlspecialchars($coremarkWgetCommand, ENT_QUOTES, 'UTF-8'); ?></code>
                        <button class="copy-btn" onclick="copyCommand(<?php echo htmlspecialchars(json_encode($coremarkWgetCommand, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3><?php echo __('home.guide_step3_title'); ?></h3>
                    <p><?php echo __('home.guide_step3_desc_prefix'); ?><a href="/benchmark-methodology.php" class="submit-link"><?php echo __('home.guide_step3_link'); ?></a><?php echo __('home.guide_step3_desc_suffix'); ?></p>
                </div>
            </div>
        </div>
    </div>
    </div><!-- 关闭 tableContentWrapper -->

    <style>
    .coremark-guide {
        max-width: 800px;
        margin: 60px auto 0;
        padding: 0 20px;
    }

    .guide-header {
        text-align: center;
        margin-bottom: 32px;
    }

    .guide-icon {
        width: 48px;
        height: 48px;
        color: #2c3e50;
        margin-bottom: 16px;
    }

    .guide-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: #1a1a1a;
        margin: 0;
    }

    .guide-info {
        background: #f8f8f8;
        border: 1px solid #e8e8e8;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 32px;
        text-align: center;
    }

    .guide-info p {
        font-size: 14px;
        color: #666;
        margin: 0 0 12px 0;
        line-height: 1.6;
    }

    .guide-tags {
        display: flex;
        gap: 8px;
        justify-content: center;
    }

    .guide-tags .tag {
        background: #fff;
        border: 1px solid #e0e0e0;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        color: #666;
        font-weight: 500;
    }

    .guide-steps {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .step-card {
        background: #fff;
        border: 1px solid #e8e8e8;
        border-radius: 12px;
        padding: 24px;
        display: flex;
        gap: 20px;
        transition: all 0.3s ease;
    }

    .step-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .step-number {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #2c3e50 0%, #1a1a1a 100%);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 700;
    }

    .step-content {
        flex: 1;
    }

    .step-content h3 {
        font-size: 18px;
        font-weight: 600;
        color: #1a1a1a;
        margin: 0 0 8px 0;
    }

    .step-content p {
        font-size: 14px;
        color: #666;
        margin: 0 0 16px 0;
        line-height: 1.6;
    }

    .step-content p:last-child {
        margin-bottom: 0;
    }

    .command-box {
        background: #f5f5f5;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .command-box:last-of-type {
        margin-bottom: 0;
    }

    .command-box code {
        flex: 1;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        color: #2c3e50;
        word-break: break-all;
    }

    .copy-btn {
        background: #6c757d;
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .copy-btn:hover {
        background: #5a6268;
        transform: translateY(-1px);
    }

    .copy-btn i {
        margin-right: 4px;
    }

    .command-divider {
        text-align: center;
        font-size: 13px;
        color: #999;
        margin: 12px 0;
        position: relative;
    }

    .command-divider::before,
    .command-divider::after {
        content: '';
        position: absolute;
        top: 50%;
        width: calc(50% - 30px);
        height: 1px;
        background: #e0e0e0;
    }

    .command-divider::before {
        left: 0;
    }

    .command-divider::after {
        right: 0;
    }

    .submit-link {
        color: #2c3e50;
        font-weight: 600;
        text-decoration: none;
        border-bottom: 2px solid #2c3e50;
        transition: all 0.2s ease;
    }

    .submit-link:hover {
        color: #1a1a1a;
        border-bottom-color: #1a1a1a;
    }

    /* 移动端优化 */
    @media (max-width: 768px) {
        .coremark-guide {
            margin-top: 40px;
            padding: 0 16px;
        }

        .guide-header h2 {
            font-size: 22px;
        }

        .guide-icon {
            width: 40px;
            height: 40px;
        }

        .guide-info {
            padding: 16px;
        }

        .step-card {
            flex-direction: column;
            padding: 20px;
            gap: 16px;
        }

        .step-number {
            width: 36px;
            height: 36px;
            font-size: 16px;
        }

        .step-content h3 {
            font-size: 16px;
        }

        .command-box {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        .command-box code {
            font-size: 12px;
        }

        .copy-btn {
            width: 100%;
        }
    }
    </style>
</div>

<!-- 右下角悬浮下载按钮 -->
<div id="downloadFloatBtn" class="download-float-btn" onclick="showExportModal()" title="<?php echo __('home.download_title'); ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
        <rect width="24" height="24" fill="none"/>
        <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.5">
            <path stroke-linejoin="round" d="M21.25 13V8.5a5 5 0 0 0-5-5h-8.5a5 5 0 0 0-5 5v7a5 5 0 0 0 5 5h6.26"/>
            <path stroke-linejoin="round" d="m3.01 17l2.74-3.2a2.2 2.2 0 0 1 2.77-.27a2.2 2.2 0 0 0 2.77-.27l2.33-2.33a4 4 0 0 1 5.16-.43l2.47 1.91M8.01 10.17a1.66 1.66 0 1 0-.02-3.32a1.66 1.66 0 0 0 .02 3.32"/>
            <path stroke-miterlimit="10" d="M18.707 20v-5"/>
            <path stroke-linejoin="round" d="m16.414 17.895l1.967 1.967a.46.46 0 0 0 .652 0L21 17.895"/>
        </g>
    </svg>
</div>

<!-- 返回顶部按钮 -->
<div id="backToTop" class="back-to-top-btn" onclick="scrollToTop()" title="<?php echo __('home.back_to_top'); ?>" style="display: none;">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
        <rect width="24" height="24" fill="none"/>
        <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
            <path d="M16.061 10.404L14 17h-4l-2.061-6.596a6 6 0 0 1 .998-5.484l2.59-3.315a.6.6 0 0 1 .946 0l2.59 3.315a6 6 0 0 1 .998 5.484M10 20c0 2 2 3 2 3s2-1 2-3m-5.5-7.5C5 15 7 19 7 19l3-2m5.931-4.5c3.5 2.5 1.5 6.5 1.5 6.5l-3-2"/>
            <path d="M12 11a2 2 0 1 1 0-4a2 2 0 0 1 0 4"/>
        </g>
    </svg>
</div>

</main>
<!-- Swup 容器结束 -->

<?php include('includes/footer.php'); ?>

<!-- 详情Modal - 原生实现 -->
<div class="native-modal" id="detailModal" style="display:none;">
    <div class="native-modal-backdrop" onclick="closeDetailModal()"></div>
    <div class="native-modal-dialog">
        <div class="native-modal-content">
            <div class="native-modal-header">
                <h5 class="native-modal-title" id="detailModalTitle"></h5>
                <button type="button" class="native-modal-close" onclick="closeDetailModal()">
                    <span>&times;</span>
                </button>
            </div>
            <div class="native-modal-body" id="detailModalBody">
                <!-- 动态内容 -->
            </div>
        </div>
    </div>
</div>
<style>
.native-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1050;
    overflow: auto;
}
.native-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1;
}
.native-modal-dialog {
    position: relative;
    max-width: 800px;
    margin: 30px auto;
    z-index: 2;
    padding: 0 15px;
}
.native-modal-content {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.native-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
    border-bottom: 1px solid #e8e8e8;
}
.native-modal-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}
.native-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    line-height: 1;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
}
.native-modal-close:hover {
    color: #333;
}
.native-modal-body {
    padding: 20px;
}

/* 桌面端标签统一样式 - 确保所有标签高度一致 */
.native-modal-body .nas-product-specs span {
    line-height: 1 !important;
    vertical-align: middle;
    display: inline-block;
}

@media (max-width: 768px) {
    .native-modal-dialog {
        margin: 10px auto;
        max-width: 100%;
    }

    .native-modal-body {
        padding: 12px;
    }

    /* Modal内的NAS产品信息移动端优化 */
    .native-modal-body .nas-product-specs {
        font-size: 13px !important;
        gap: 5px !important;
        line-height: 1.8 !important;
        justify-content: flex-start !important;
    }

    .native-modal-body .nas-product-specs span {
        padding: 4px 8px !important;
        font-size: 12px !important;
        white-space: nowrap;
        display: inline-block;
        margin-bottom: 3px;
        line-height: 1 !important;
        vertical-align: middle;
    }

    /* Modal内的产品卡片移动端优化 */
    .native-modal-body .nas-product-card {
        margin-bottom: 12px !important;
    }

    .native-modal-body .nas-product-content {
        padding: 10px !important;
    }

    .native-modal-body .nas-product-title {
        font-size: 14px !important;
        margin-bottom: 8px !important;
    }
}

@media (max-width: 480px) {
    .native-modal-body {
        padding: 10px;
    }

    .native-modal-body .nas-product-specs {
        font-size: 12px !important;
        gap: 4px !important;
        line-height: 2 !important;
    }

    .native-modal-body .nas-product-specs span {
        padding: 3px 7px !important;
        font-size: 11px !important;
        margin-bottom: 3px;
        line-height: 1 !important;
        vertical-align: middle;
    }
}

/* 天梯榜容器样式 */
.ranking-container {
    position: relative;
    padding: 20px 20px 60px;
    max-width: 1200px;
    margin: 20px auto 0;
    pointer-events: auto;
}
.ranking-container .labels {
    display: flex;
    justify-content: space-between;
    padding: 0 100px;
    margin-bottom: 30px;
    margin-top: 30px;
    font-weight: 700;
    font-size: 18px;
}
.ranking-container .labels span:first-child {
    color: #2980b9;
}
.ranking-container .labels span:last-child {
    color: #27ae60;
}

.benchmark-toolbar {
    max-width: none;
    margin: 16px auto 0;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: space-between;
}

.benchmark-toolbar.benchmark-toolbar-hidden {
    display: none !important;
}

.benchmark-search {
    flex: 1;
    min-width: 0;
}

.benchmark-search input {
    width: 100%;
    height: 44px;
    padding: 0 16px;
    border: 1px solid #d8dee6;
    border-radius: 12px;
    background: #fff;
    color: #1f2937;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.benchmark-search input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.benchmark-toggle-btn {
    flex-shrink: 0;
    height: 44px;
    padding: 0 16px;
    border-radius: 12px;
    border: 1px solid #d8dee6;
    background: #fff;
    color: #475569;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.benchmark-toggle-btn:hover {
    border-color: #94a3b8;
    color: #1f2937;
}

.benchmark-toggle-btn.benchmark-toggle-active {
    background: linear-gradient(135deg, #0f766e 0%, #0f9f8f 100%);
    border-color: #0f766e;
    color: #fff;
    box-shadow: 0 10px 24px rgba(15, 118, 110, 0.18);
}

@media (max-width: 768px) {
    .benchmark-toolbar {
        padding: 0;
        flex-direction: column;
        align-items: stretch;
    }

    .benchmark-toggle-btn {
        width: 100%;
    }
}
.ranking-container .axis {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: 100px;
    bottom: 40px;
    border-left: 2px dashed #ddd;
    z-index: 0;
    pointer-events: none;
}
.ranking-container .row {
    position: relative;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.4s ease, transform 0.4s ease;
}
.ranking-container .row.visible {
    opacity: 1;
    transform: translateY(0);
}
.ranking-container .side {
    display: flex;
    padding: 0 10px;
}
.ranking-container .side-left {
    justify-content: flex-end;
}
.ranking-container .side-right {
    justify-content: flex-start;
}
.ranking-container .rank-circle {
    width: 40px;
    height: 40px;
    background: #e74c3c;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index: 1;
    flex-shrink: 0;
}
.ranking-container .row:nth-child(1) .rank-circle {
    width: 48px;
    height: 48px;
    font-size: 20px;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #8b4513;
    box-shadow: 0 4px 15px rgba(255,215,0,0.4);
}
.ranking-container .card {
    width: 100%;
    background: #ccc;
    border-radius: 8px;
    position: relative;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    overflow: hidden;
    display: flex;
    align-items: stretch;
    min-height: 54px;
}
.ranking-container .side-left .card {
    background: #2980b9;
}
.ranking-container .side-right .card {
    background: #27ae60;
}
.ranking-container .view-normal {
    width: 100%;
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: #ffffff;
    gap: 12px;
}
.ranking-container .side-left .view-normal {
    justify-content: flex-start;
    text-align: left;
}
.ranking-container .side-right .view-normal {
    justify-content: flex-start;
    text-align: left;
}
.ranking-container .score {
    font-family: 'Roboto Mono', 'Consolas', monospace;
    font-weight: 700;
    font-size: 16px;
    opacity: 0.95;
    white-space: nowrap;
}
.ranking-container .cpu-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}
.ranking-container .name {
    font-size: 15px;
    font-weight: 600;
    line-height: 1.3;
    word-break: break-word;
}
.ranking-container .cores {
    font-size: 12px;
    opacity: 0.85;
    font-weight: 500;
}
.ranking-container .side-left .cpu-info {
    align-items: flex-start;
    text-align: left;
}
.ranking-container .side-right .cpu-info {
    align-items: flex-start;
    text-align: left;
}
.ranking-container .side-right .score {
    text-align: right;
}
@media (max-width: 768px) {
    .ranking-modal-dialog {
        margin: 10px auto;
        max-width: 100%;
    }

    /* 移动端天梯榜优化 */
    .ranking-container {
        padding: 10px 5px 40px;
        margin: 10px auto 0;
    }

    .ranking-container .labels {
        display: none;
    }

    .ranking-container .axis {
        display: none;
    }

    .ranking-container .row {
        gap: 8px;
        margin-bottom: 12px;
    }

    .ranking-container .side {
        padding: 0 3px;
    }

    .ranking-container .rank-circle {
        width: 24px;
        height: 24px;
        line-height: 24px;
        font-size: 11px;
        border-width: 2px;
    }

    .ranking-container .row:nth-child(1) .rank-circle {
        width: 28px;
        height: 28px;
        line-height: 28px;
        font-size: 14px;
    }

    .ranking-container .card {
        min-height: 50px;
        border-radius: 6px;
    }

    .ranking-container .view-normal {
        padding: 8px 10px;
        gap: 6px;
    }

    .ranking-container .score {
        font-size: 11px;
    }

    .ranking-container .name {
        font-size: 12px;
        line-height: 1.2;
        display: -webkit-box !important;
        -webkit-box-orient: vertical !important;
        -webkit-line-clamp: 3 !important;
        overflow: hidden !important;
        word-break: break-word !important;
    }

    .ranking-container .cores {
        font-size: 10px;
    }

    .ranking-container .cpu-info {
        gap: 2px;
    }
}

/* 自定义快速tooltip - 立即显示 */
.icon-tooltip {
    position: relative;
}

.icon-tooltip::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(-8px);
    background: rgba(0, 0, 0, 0.9);
    color: #fff;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.15s ease;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.icon-tooltip::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(-2px);
    border: 5px solid transparent;
    border-top-color: rgba(0, 0, 0, 0.9);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.15s ease;
    z-index: 1000;
}

.icon-tooltip:hover::after,
.icon-tooltip:hover::before {
    opacity: 1;
}

/* 移动端禁用tooltip */
@media (max-width: 768px) {
    .icon-tooltip::after,
    .icon-tooltip::before {
        display: none;
    }

    /* 移动端隐藏跑分图标，避免与NAS推荐图标重叠 */
    .speedometer-icon {
        display: none !important;
    }

    /* 或者通过父容器隐藏 */
    span[data-tooltip="查看CoreMark跑分数据"] {
        display: none !important;
    }
}
</style>

<script>
// 注意：showDetailModal和closeDetailModal已移至main.js，确保Swup切换后可用

// 打开CPU详情模态框 - 用于天梯榜
// 加载NAS产品推荐
async function openCPUDetailModal(cpuModel, score, coreInfo, deviceInfo) {
    const escModal = (value) => String(value == null ? '' : value).replace(/[&<>"']/g,
        (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    cpuModel = escModal(cpuModel);
    coreInfo = escModal(coreInfo);
    deviceInfo = escModal(deviceInfo);

    // 设置标题（与主页一致的动画SVG图标）
    document.getElementById('detailModalTitle').innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" style="vertical-align: middle; margin-right: 8px; color: #000;"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path stroke-dasharray="64" stroke-dashoffset="64" d="M12 3c4.97 0 9 4.03 9 9c0 4.97 -4.03 9 -9 9c-4.97 0 -9 -4.03 -9 -9c0 -4.97 4.03 -9 9 -9Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.6s" values="64;0"/></path><path stroke-dasharray="8" stroke-dashoffset="8" d="M12 12h-5.5"><animate fill="freeze" attributeName="stroke-dashoffset" begin="1.3s" dur="0.2s" values="8;0"/><animateTransform fill="freeze" attributeName="transform" begin="1.3s" dur="0.4s" type="rotate" values="0 12 12;65 12 12"/></path></g><g fill="currentColor"><path fill-opacity="0" d="M12 21C9.41 21 7.15 20.79 5.94 19L12 21L18.06 19C16.85 20.79 14.59 21 12 21Z"><animate fill="freeze" attributeName="d" begin="0.6s" dur="0.4s" values="M12 21C9.41 21 7.15 20.79 5.94 19L12 21L18.06 19C16.85 20.79 14.59 21 12 21Z;M12 16C9.41 16 7.15 17.21 5.94 19L12 21L18.06 19C16.85 17.21 14.59 16 12 16Z"/><set fill="freeze" attributeName="fill-opacity" begin="0.6s" to="1"/></path><circle cx="7" cy="12" r="0" transform="rotate(15 12 12)"><animate fill="freeze" attributeName="r" begin="0.9s" dur="0.2s" values="0;1"/></circle><circle cx="7" cy="12" r="0" transform="rotate(65 12 12)"><animate fill="freeze" attributeName="r" begin="0.95s" dur="0.2s" values="0;1"/></circle><circle cx="7" cy="12" r="0" transform="rotate(115 12 12)"><animate fill="freeze" attributeName="r" begin="1.05s" dur="0.2s" values="0;1"/></circle><circle cx="7" cy="12" r="0" transform="rotate(165 12 12)"><animate fill="freeze" attributeName="r" begin="1.05s" dur="0.2s" values="0;1"/></circle><circle cx="12" cy="12" r="0"><animate fill="freeze" attributeName="r" begin="1.3s" dur="0.2s" values="0;2"/></circle></g></svg> ' + cpuModel + ' - ' + I18N.detail_title_suffix;

    document.getElementById('detailModalBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> ' + I18N.loading + '</div>';
    showDetailModal();

    try {
        const language = window.CURRENT_LANGUAGE || document.documentElement.lang || 'zh-CN';
        const response = await fetch(`/api/public/benchmark-detail.php?cpu_model=${encodeURIComponent(cpuModel)}&lang=${encodeURIComponent(language)}`);
        const result = await response.json();

        if (result.code === 0 && result.data) {
            const data = result.data;
            const testDevice = escModal(data.test_device || data.device_info || I18N.unknown_device);
            const cores = escModal(data.cores || coreInfo || I18N.unknown);
            const submitTime = escModal(data.submitted_at || data.created_at || '');

            let html = `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title text-primary">${cpuModel}</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>${I18N.detail_test_device}:</strong> <span class="text-success">${testDevice}</span></p>
                                <p class="mb-2"><strong>${I18N.detail_cores}:</strong> ${cores}</p>
                                ${submitTime ? `<p class="mb-2"><strong>${I18N.detail_submit_time}:</strong> ${submitTime}</p>` : ''}
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>${I18N.detail_score}:</strong> <span class="text-danger" style="font-size: 1.5em; font-weight: bold;">${parseInt(data.score).toLocaleString()}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            html += `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> ${I18N.data_reference_note}
                </div>
            `;

            document.getElementById('detailModalBody').innerHTML = html;
        } else {
            // API失败时使用fallback
            deviceInfo = deviceInfo || I18N.unknown_device;
            coreInfo = coreInfo || I18N.unknown;

            let html = `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title text-primary">${cpuModel}</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>${I18N.detail_test_device}:</strong> <span class="text-success">${deviceInfo}</span></p>
                                <p class="mb-2"><strong>${I18N.detail_cores}:</strong> ${coreInfo}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>${I18N.detail_score}:</strong> <span class="text-danger" style="font-size: 1.5em; font-weight: bold;">${score.toLocaleString()}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> ${I18N.data_reference_note}
                </div>
            `;

            document.getElementById('detailModalBody').innerHTML = html;
        }
    } catch (error) {
        console.error('加载详细数据失败:', error);
        deviceInfo = deviceInfo || I18N.unknown_device;
        coreInfo = coreInfo || I18N.unknown;

        let html = `
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title text-primary">${cpuModel}</h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>${I18N.detail_test_device}:</strong> <span class="text-success">${deviceInfo}</span></p>
                            <p class="mb-2"><strong>${I18N.detail_cores}:</strong> ${coreInfo}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>${I18N.detail_score}:</strong> <span class="text-danger" style="font-size: 1.5em; font-weight: bold;">${score.toLocaleString()}</span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> ${I18N.detail_load_failed_basic}
            </div>
        `;

        document.getElementById('detailModalBody').innerHTML = html;
    }
}
// 天梯榜显示函数
let rankingDataLoaded = false;

function showRanking() {
    // 隐藏表格内容容器和推荐NAS容器
    const tableWrapper = document.getElementById('tableContentWrapper');
    if (tableWrapper) tableWrapper.style.display = 'none';
    if (typeof setBenchmarkToolbarVisibility === 'function') {
        setBenchmarkToolbarVisibility(false);
    } else {
        const toolbar = document.querySelector('.benchmark-toolbar');
        if (toolbar) toolbar.classList.add('benchmark-toolbar-hidden');
    }

    // 显示天梯榜
    document.getElementById('rankingContainer').style.display = 'block';

    // 更新按钮状态
    updateButtonState('btn4');

    // 加载数据（只加载一次）
    if (!rankingDataLoaded) {
        loadRankingData();
    }
}

function hideRanking() {
    console.log('hideRanking called');
    // 显示表格内容容器
    const tableWrapper = document.getElementById('tableContentWrapper');
    console.log('tableWrapper:', tableWrapper);
    if (tableWrapper) tableWrapper.style.display = '';  // 使用空字符串恢复默认display值
    if (typeof setBenchmarkToolbarVisibility === 'function') {
        setBenchmarkToolbarVisibility(true);
    } else {
        const toolbar = document.querySelector('.benchmark-toolbar');
        if (toolbar) toolbar.classList.remove('benchmark-toolbar-hidden');
    }

    // 隐藏天梯榜
    const rankingContainer = document.getElementById('rankingContainer');
    console.log('rankingContainer:', rankingContainer);
    if (rankingContainer) rankingContainer.style.display = 'none';
}
async function loadRankingData() {
    try {
        const response = await fetch('/api/get_ranking_data.php');
        const result = await response.json();

        if (result.success) {
            renderRankingData(result.rows);
            rankingDataLoaded = true;
        } else {
            document.getElementById('rankingContainer').innerHTML = '<div style="text-align:center;padding:40px;color:#dc3545;">' + I18N.load_failed_prefix + ' ' + result.message + '</div>';
        }
    } catch (error) {
        console.error('加载天梯榜数据失败:', error);
        document.getElementById('rankingContainer').innerHTML = '<div style="text-align:center;padding:40px;color:#dc3545;">' + I18N.load_failed_retry + '</div>';
    }
}

function updateButtonState(activeId) {
    ['btn1', 'btn2', 'btn3', 'btn4'].forEach(id => {
        const btn = document.getElementById(id);
        if (id === activeId) {
            if (id === 'btn4') {
                // 天梯榜按钮激活时：红色填充，白色文字
                btn.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                btn.style.color = '#fff';
                btn.style.border = 'none';
            } else {
                btn.classList.add('allcpu');
            }
        } else {
            if (id === 'btn4') {
                // 天梯榜按钮未激活时：透明背景，红色边框
                btn.style.background = 'transparent';
                btn.style.color = '#dc2626';
                btn.style.border = '2px solid #dc2626';
            } else {
                btn.classList.remove('allcpu');
            }
        }
    });
}

function renderRankingData(rows) {
    let html = `
        <div class="labels">
            <span>${I18N.ranking_x86_label}</span>
            <span>${I18N.ranking_arm_label}</span>
        </div>
        <div class="axis"></div>
    `;

    rows.forEach((row, index) => {
        const rank = row.rank;
        const x86 = row.x86;
        const arm = row.arm;

        html += '<div class="row">';

        // X86 左侧
        html += '<div class="side side-left">';
        if (x86) {
            html += `
                <div class="card" onclick="openCPUDetailModal('${escapeHtml(x86.model)}', ${x86.coremark_score}, '${escapeHtml(x86.cores || '')}', '')">
                    <div class="view-normal">
                        <span class="score">${numberFormat(x86.coremark_score)}</span>
                        <div class="cpu-info">
                            <span class="name">${escapeHtml(x86.model)}</span>
                            <span class="cores">${escapeHtml(x86.cores || 'N/A')}</span>
                        </div>
                    </div>
                </div>
            `;
        }
        html += '</div>';

        // 排名圈
        html += `<div class="rank-circle">${rank}</div>`;

        // ARM 右侧
        html += '<div class="side side-right">';
        if (arm) {
            html += `
                <div class="card" onclick="openCPUDetailModal('${escapeHtml(arm.model)}', ${arm.coremark_score}, '${escapeHtml(arm.cores || '')}', '')">
                    <div class="view-normal">
                        <div class="cpu-info">
                            <span class="name">${escapeHtml(arm.model)}</span>
                            <span class="cores">${escapeHtml(arm.cores || 'N/A')}</span>
                        </div>
                        <span class="score">${numberFormat(arm.coremark_score)}</span>
                    </div>
                </div>
            `;
        }
        html += '</div>';

        html += '</div>';
    });

    document.getElementById('rankingContainer').innerHTML = html;

    // 滚动动画
    setTimeout(() => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 30);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('#rankingContainer .row').forEach(row => {
            observer.observe(row);
        });
    }, 100);
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function numberFormat(num) {
    return parseInt(num).toLocaleString();
}

// ESC键关闭
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailModal();
    }
});

</script>

<!-- html2canvas - 用于生成性能天梯图 -->
<script src="<?php echo asset_url_auto('/assets/js/html2canvas.min.js'); ?>"></script>

<!-- Main Script - 使用defer延迟加载，不阻塞渲染 + 时间戳强制刷新 -->
<?php
// SEO 阶段三：排行榜 CPU 名称可点击进入实体页（JS 渲染路径与 SSR 输出保持一致）
require_once __DIR__ . '/includes/cpu-whitelist.php';
$cpuPageSlugMap = [];
foreach ($GLOBALS['CPU_WHITELIST'] as $cpuWhitelistModel) {
    $cpuPageSlugMap[$cpuWhitelistModel] = cpu_detail_slugify($cpuWhitelistModel);
}
?>
<script>window.CPU_PAGE_SLUGS = <?php echo json_encode($cpuPageSlugMap, JSON_UNESCAPED_UNICODE); ?>;</script>
<script src="<?php echo asset_url_auto('/assets/js/main.js'); ?>" defer></script>

<!-- 性能天梯图导出模态框 -->
<div id="exportModal" class="export-modal-overlay" style="display: none;">
    <div class="export-modal-container">
        <div class="export-modal-card">
            <button class="export-modal-close" onclick="closeExportModal()">
                <i class="fas fa-times"></i>
            </button>

            <h2 class="export-modal-title"><?php echo __('home.export_title'); ?></h2>
            <p class="export-modal-subtitle"><?php echo __('home.export_subtitle'); ?></p>

            <div class="export-preview-container" id="exportPreviewContainer">
                <div class="export-loading" id="exportLoading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p><?php echo __('home.export_generating_preview'); ?></p>
                </div>
                <canvas id="exportCanvas" style="display: none; max-width: 100%; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></canvas>
            </div>

            <div class="export-options">
                <label class="export-option-label">
                    <span><?php echo __('home.export_format'); ?></span>
                    <select id="exportFormat" class="export-select">
                        <option value="png"><?php echo __('home.export_format_png'); ?></option>
                        <option value="jpeg"><?php echo __('home.export_format_jpeg'); ?></option>
                    </select>
                </label>

                <label class="export-option-label">
                    <span><?php echo htmlspecialchars(cpu_t('runtime.export_scope', '导出范围'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <select id="exportScope" class="export-select" onchange="regeneratePreview()">
                        <option value="current" selected><?php echo htmlspecialchars(cpu_t('runtime.export_current', '跟随当前筛选'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="all"><?php echo htmlspecialchars(cpu_t('runtime.export_all', '显示全部 CPU'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </label>

                <label class="export-option-label">
                    <span><?php echo __('home.export_arch_filter'); ?></span>
                    <select id="exportArchFilter" class="export-select" onchange="regeneratePreview()">
                        <option value="all" selected><?php echo __('home.export_arch_all'); ?></option>
                        <option value="x86_64"><?php echo htmlspecialchars(cpu_t('runtime.arch_x86', 'x86_64 架构'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="ARM64"><?php echo htmlspecialchars(cpu_t('runtime.arch_arm', 'ARM64 架构'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </label>

                <label class="export-option-label">
                    <span><?php echo __('home.export_count'); ?></span>
                    <select id="exportCount" class="export-select" onchange="regeneratePreview()">
                        <option value="20"><?php echo __('home.export_top20'); ?></option>
                        <option value="30" selected><?php echo __('home.export_top30'); ?></option>
                        <option value="50"><?php echo __('home.export_top50'); ?></option>
                        <option value="all"><?php echo __('home.filter.all'); ?></option>
                    </select>
                </label>
            </div>

            <div class="export-actions">
                <button class="export-btn export-btn-primary" onclick="downloadExportImage()">
                    <i class="fas fa-download"></i> <?php echo __('home.export_download_image'); ?>
                </button>
                <button class="export-btn export-btn-secondary" onclick="closeExportModal()">
                    <?php echo __('common.cancel'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 隐藏的导出容器 -->
<div id="exportContainer" style="position: fixed; left: -9999px; top: 0; width: 1200px; background: #fff;">
    <!-- 动态生成的内容将插入这里 -->
</div>

<style>
/* 悬浮下载按钮 - 仅图标 */
.download-float-btn {
    position: fixed;
    right: 30px;
    bottom: 30px;
    background: transparent;
    color: #2c3e50;
    width: auto;
    height: auto;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    z-index: 999;
    border: none;
    padding: 0;
}

.download-float-btn:hover {
    color: #34495e;
    transform: scale(1.15);
}

.download-float-btn svg {
    width: 38px;
    height: 38px;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

@media (max-width: 768px) {
    .download-float-btn {
        right: 20px;
        bottom: 80px; /* 60px导航栏高度 + 20px间距，避免遮挡底部导航 */
    }

    .download-float-btn svg {
        width: 32px;
        height: 32px;
    }
}

/* 返回顶部按钮 */
.back-to-top-btn {
    position: fixed;
    right: 30px;
    bottom: 85px; /* 在下载按钮上方 */
    background: transparent;
    color: #dc2626;
    width: auto;
    height: auto;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    z-index: 999;
    border: none;
    padding: 0;
}

.back-to-top-btn:hover {
    color: #b91c1c;
    transform: translateY(-5px);
}

.back-to-top-btn svg {
    width: 42px;
    height: 42px;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

@media (max-width: 768px) {
    .back-to-top-btn {
        right: 20px;
        bottom: 130px; /* 在下载按钮上方（80px + 32px图标 + 18px间距） */
    }

    .back-to-top-btn svg {
        width: 36px;
        height: 36px;
    }
}

/* 导出模态框 */
.export-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.export-modal-container {
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
}

.export-modal-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    position: relative;
}

.export-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 36px;
    height: 36px;
    border: none;
    background: #f5f5f5;
    color: #666;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    font-size: 16px;
}

.export-modal-close:hover {
    background: #e8e8e8;
    color: #333;
    transform: rotate(90deg);
}

.export-modal-title {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin: 0 0 8px 0;
}

.export-modal-subtitle {
    font-size: 14px;
    color: #666;
    margin: 0 0 25px 0;
}

.export-preview-container {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.export-loading {
    text-align: center;
    color: #666;
}

.export-loading i {
    font-size: 32px;
    margin-bottom: 12px;
    color: #667eea;
}

.export-loading p {
    margin: 0;
    font-size: 14px;
}

.export-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.export-option-label {
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.export-select {
    padding: 10px 14px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.export-select:focus {
    outline: none;
    border-color: #667eea;
}

.export-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.export-btn {
    padding: 12px 28px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.export-btn-primary {
    background: #2c3e50;
    color: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.export-btn-primary:hover {
    background: #34495e;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}

.export-btn-secondary {
    background: #f1f3f5;
    color: #666;
}

.export-btn-secondary:hover {
    background: #e9ecef;
}

@media (max-width: 768px) {
    .export-modal-card {
        padding: 20px;
    }

    .export-actions {
        flex-direction: column;
    }

    .export-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// 显示导出模态框
function showExportModal() {
    // 检查数据是否已加载
    if (!state.allData || state.allData.length === 0) {
        alert(t('runtime.export_no_data', '没有可用的数据'));
        return;
    }

    // 检查 html2canvas 是否已加载
    if (typeof html2canvas === 'undefined') {
        alert(t('runtime.export_library_loading', '图片生成库还未加载完成，请稍后再试'));
        return;
    }

    document.getElementById('exportModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // 重置状态
    document.getElementById('exportCanvas').style.display = 'none';
    document.getElementById('exportLoading').style.display = 'block';

    // 延迟生成预览，确保DOM渲染完成
    setTimeout(generateExportPreview, 300);
}

// 关闭导出模态框
function closeExportModal() {
    document.getElementById('exportModal').style.display = 'none';
    document.body.style.overflow = '';
}

// 重新生成预览
function regeneratePreview() {
    document.getElementById('exportCanvas').style.display = 'none';
    document.getElementById('exportLoading').style.display = 'block';
    setTimeout(generateExportPreview, 300);
}

function getCurrentFilteredBenchmarkData() {
    let data = Array.isArray(state.allData) ? [...state.allData] : [];

    if (state.currentArch) {
        if (state.currentArch === 'ARM') {
            data = data.filter(item =>
                item.architecture === 'ARM64' ||
                item.architecture === 'ARMv7' ||
                item.architecture === 'ARM'
            );
        } else {
            data = data.filter(item => item.architecture === state.currentArch);
        }
    }

    if (state.nasOnly) {
        data = data.filter(item => isDefaultVisibleCPU(item));
    }

    const searchTerm = normalizeSearchTerm(state.currentSearch);
    if (searchTerm) {
        data = data.filter(item => {
            const haystack = [
                item.cpu_model,
                item.test_device,
                item.device_name,
                item.device_brand,
                item.device_model,
                item.architecture
            ].join(' ').toLowerCase();

            return haystack.includes(searchTerm);
        });
    }

    return data;
}

// 生成导出预览
async function generateExportPreview() {
    try {
        console.log('开始生成预览...');

        // 再次检查数据
        if (!state.allData || state.allData.length === 0) {
            throw new Error(t('runtime.export_no_data', '没有可用的数据'));
        }

        const count = document.getElementById('exportCount').value;
        const archFilter = document.getElementById('exportArchFilter').value;
        const exportScope = document.getElementById('exportScope').value;

        let data = exportScope === 'current'
            ? getCurrentFilteredBenchmarkData()
            : [...state.allData];

        // 额外按导出选项里的架构筛选
        if (archFilter !== 'all') {
            data = data.filter(item => item.architecture === archFilter);
        }

        // 再按数量截取
        const displayData = count === 'all' ? data : data.slice(0, parseInt(count));

        console.log('准备渲染数据，共', displayData.length, '条');

        // 生成导出HTML
        const exportHTML = generateExportHTML(displayData, {
            totalCount: data.length,
            scope: exportScope
        });
        document.getElementById('exportContainer').innerHTML = exportHTML;

        console.log('HTML已生成，准备调用html2canvas');

        // 等待DOM更新
        await new Promise(resolve => setTimeout(resolve, 100));

        // 使用 html2canvas 生成图片
        const container = document.getElementById('exportContainer');
        const canvas = await html2canvas(container, {
            scale: 2,
            backgroundColor: '#ffffff',
            logging: false,
            width: 1200,
            windowWidth: 1200,
            useCORS: true,
            allowTaint: true
        });

        console.log('Canvas生成成功，尺寸:', canvas.width, 'x', canvas.height);

        // 显示预览
        const previewCanvas = document.getElementById('exportCanvas');
        const ctx = previewCanvas.getContext('2d');

        // 设置预览尺寸
        const maxWidth = 800;
        const scale = maxWidth / canvas.width;
        previewCanvas.width = canvas.width * scale;
        previewCanvas.height = canvas.height * scale;

        ctx.drawImage(canvas, 0, 0, previewCanvas.width, previewCanvas.height);

        // 保存原始canvas供下载使用
        window.exportCanvasData = canvas;

        document.getElementById('exportLoading').style.display = 'none';
        previewCanvas.style.display = 'block';

        console.log('预览生成完成');

    } catch (error) {
        console.error('生成预览失败:', error);
        const errorMessage = formatMessage(t('runtime.export_failed', currentLanguageIsEnglish() ? 'Generation failed: {message}' : '生成失败：{message}'), {
            message: error && error.message ? error.message : t('runtime.unknown', '未知')
        });
        const safeErrorMessage = typeof escapeHtml === 'function'
            ? escapeHtml(errorMessage)
            : String(errorMessage).replace(/[&<>\"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#039;'}[ch]));
        document.getElementById('exportLoading').innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i><p style="color: #dc3545;">' + safeErrorMessage + '</p><button onclick="regeneratePreview()" style="margin-top: 10px; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer;">' + t('runtime.export_retry', '重试') + '</button>';
    }
}

// 生成导出HTML内容（极客风格：黑白灰绿配色）
function generateExportHTML(data, options = {}) {
    const now = new Date();
    const dateStr = currentLanguageIsEnglish()
        ? now.toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'})
        : now.getFullYear() + '年' + (now.getMonth() + 1) + '月' + now.getDate() + '日';
    const totalCount = typeof options.totalCount === 'number' ? options.totalCount : data.length;
    const scopeText = options.scope === 'all'
        ? t('runtime.export_all', '显示全部 CPU')
        : t('runtime.export_current', '跟随当前筛选');
    const safe = value => typeof escapeHtml === 'function'
        ? escapeHtml(String(value ?? ''))
        : String(value ?? '').replace(/[&<>\"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#039;'}[ch]));

    // 计算进度条百分比（与主页一致）
    const THRESHOLD = 50000;
    const MAX_SCORE = 1500000;  // 提高到150万以容纳高性能服务器CPU
    const LOW_RANGE_WIDTH = 25;
    const HIGH_RANGE_WIDTH = 75;

    const calculatePercentage = (score) => {
        if (score <= THRESHOLD) {
            return (score / THRESHOLD * LOW_RANGE_WIDTH).toFixed(2);
        } else {
            const percentage = LOW_RANGE_WIDTH + ((score - THRESHOLD) / (MAX_SCORE - THRESHOLD) * HIGH_RANGE_WIDTH);
            // 确保不超过100%
            return Math.min(percentage, 100).toFixed(2);
        }
    };

    let html = `
        <div style="padding: 50px 40px; font-family: 'Consolas', 'Monaco', 'Courier New', monospace; background: #0a0a0a;">
            <!-- 头部标题 - 极客风格 -->
            <div style="margin-bottom: 30px; border-left: 4px solid #00ff41; padding-left: 20px;">
                <h1 style="margin: 0 0 8px 0; font-size: 36px; font-weight: 700; color: #00ff41; letter-spacing: 1px;">
                    ${safe(siteName)} CPU Performance Ladder
                </h1>
                <p style="margin: 0; font-size: 14px; color: #888; font-family: 'Consolas', monospace;">
                    CoreMark Benchmark Rankings / Last Update: ${safe(dateStr)} / ${safe(t('runtime.scope', '范围'))}: ${safe(scopeText)}
                </p>
            </div>

            <!-- 排行榜表格 - 紧凑布局 -->
            <div style="background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 8px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; border: 0; table-layout: fixed;">
                    <!-- 表头 -->
                    <thead>
                        <tr style="background: #0f0f0f; border-bottom: 2px solid #00ff41;">
                            <th style="padding: 18px 2px 18px 20px; text-align: left; font-size: 18px; color: #00ff41; font-weight: 700; letter-spacing: 0.5px; width: 240px;">
                                ${safe(t('runtime.export_model', 'CPU 型号'))}
                            </th>
                            <th style="padding: 18px 8px 18px 3px; text-align: left; font-size: 18px; color: #00ff41; font-weight: 700; letter-spacing: 0.5px; width: 140px;">
                                ${safe(t('runtime.export_cores', '核心'))}
                            </th>
                            <th style="padding: 18px 8px; text-align: center; font-size: 18px; color: #00ff41; font-weight: 700; letter-spacing: 0.5px; width: 110px; white-space: nowrap;">
                                ${safe(t('runtime.export_tdp', '功耗'))}
                            </th>
                            <th style="padding: 18px 20px; text-align: left; font-size: 18px; color: #00ff41; font-weight: 700; letter-spacing: 0.5px;">
                                ${safe(t('runtime.export_score', '跑分'))}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    data.forEach((item, index) => {
        const score = item.score || 0;
        const percentage = calculatePercentage(score);
        const isX86 = item.architecture === 'x86_64';
        const tdp = item.tdp || '';

        // 根据TDP值设置颜色
        let tdpColor = '#888';
        if (tdp) {
            const tdpValue = parseInt(tdp);
            if (tdpValue <= 15) {
                tdpColor = '#10b981'; // 绿色 - 低功耗
            } else if (tdpValue <= 45) {
                tdpColor = '#f59e0b'; // 黄色 - 中等功耗
            } else if (tdpValue <= 95) {
                tdpColor = '#f97316'; // 橙色 - 较高功耗
            } else {
                tdpColor = '#ef4444'; // 红色 - 高功耗
            }
        }

        // 极客配色：x86用蓝色，ARM用绿色
        const barColor = isX86
            ? 'linear-gradient(to right, #1e3a8a, #3b82f6)'  // 深蓝到蓝
            : 'linear-gradient(to right, #065f46, #10b981)'; // 深绿到绿

        // 行背景：斑马条纹
        const rowBg = index % 2 === 0 ? '#1a1a1a' : '#151515';
        const hoverBg = '#202020';

        html += `
            <tr style="background: ${rowBg}; border-bottom: 1px solid #252525;">
                <!-- CPU型号 -->
                <td style="padding: 14px 2px 14px 20px; vertical-align: middle; width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <span style="font-size: 20px; font-weight: 700; color: #e0e0e0; font-family: 'Consolas', monospace;">
                        ${safe(item.cpu_model || t('runtime.unknown', '未知'))}
                    </span>
                </td>

                <!-- 核心配置 -->
                <td style="padding: 14px 8px 14px 3px; vertical-align: middle; width: 140px;">
                    <span style="font-size: 18px; color: #bbb; font-family: 'Consolas', monospace; font-weight: 600;">
                        ${safe(localizeCoreInfo(item.core_info || t('runtime.unknown', '未知')))}
                    </span>
                </td>

                <!-- TDP功耗 -->
                <td style="padding: 14px 8px; vertical-align: middle; width: 110px; text-align: center;">
                    <span style="font-size: 18px; color: ${tdpColor}; font-family: 'Consolas', monospace; font-weight: 700;">
                        ${safe(tdp || '-')}
                    </span>
                </td>

                <!-- 性能进度条 -->
                <td style="padding: 14px 20px; vertical-align: middle;">
                    <div style="width: 100%; height: 38px; position: relative; background: #0f0f0f; border: 1px solid #2a2a2a; border-radius: 5px; overflow: hidden;">
                        <div style="height: 100%; width: ${percentage}%; background: ${barColor}; position: relative; transition: width 0.3s ease;">
                            <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #fff; font-weight: 700; font-size: 18px; font-family: 'Consolas', monospace; text-shadow: 0 2px 4px rgba(0,0,0,0.7);">
                                ${score.toLocaleString()}
                            </span>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    });

    html += `
                    </tbody>
                </table>
            </div>

            <!-- 底部信息 - 极客风格 -->
            <div style="margin-top: 25px; padding: 15px 20px; background: #0f0f0f; border: 1px solid #2a2a2a; border-radius: 6px; border-left: 4px solid #00ff41;">
                <p style="margin: 0 0 5px 0; font-size: 13px; color: #00ff41; font-weight: 600; font-family: 'Consolas', monospace;">
                    > ${safe(siteUrl)} - NAS CPU Benchmark Platform
                </p>
                <p style="margin: 0; font-size: 11px; color: #666; font-family: 'Consolas', monospace;">
                    ${safe(t('runtime.total_cpus', 'CPU 总数'))}: ${totalCount} | ${safe(t('runtime.exported', '导出'))}: ${data.length} | ${safe(t('runtime.continuously_updated', '持续更新'))}
                </p>
            </div>
        </div>
    `;

    return html;
}

// 下载导出图片
function downloadExportImage() {
    if (!window.exportCanvasData) {
        alert(t('runtime.export_preview_first', '请先生成预览'));
        return;
    }

    const format = document.getElementById('exportFormat').value;
    const mimeType = format === 'png' ? 'image/png' : 'image/jpeg';
    const extension = format;

    const link = document.createElement('a');
    const filenamePrefix = currentLanguageIsEnglish()
        ? 'cpu-ranking'
        : t('export_filename_prefix', 'CPU性能天梯图');
    link.download = `${filenamePrefix}_${new Date().getTime()}.${extension}`;
    link.href = window.exportCanvasData.toDataURL(mimeType, 0.95);
    link.click();
}

// ESC键关闭
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('exportModal');
        if (modal && modal.style.display === 'flex') {
            closeExportModal();
        }
    }
});

// 返回顶部功能
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// 监听滚动事件，控制返回顶部按钮显示
window.addEventListener('scroll', function() {
    const backToTopBtn = document.getElementById('backToTop');
    if (!backToTopBtn) return;
    if (window.pageYOffset > 300) {
        backToTopBtn.style.display = 'flex';
    } else {
        backToTopBtn.style.display = 'none';
    }
});
</script>
<!-- 评价功能已启用 - v8.0 -->
</body>
</html>
