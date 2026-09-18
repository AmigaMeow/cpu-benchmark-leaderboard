<?php
/**
 * CPU benchmark pages share a small vocabulary that is also used by the
 * server-rendered table.  Keep the English copy here until the content team
 * promotes these entries into the translation editor; all other languages
 * deliberately fall back to the original Chinese text for now.
 */
function cpu_t(string $key, string $zh, array $params = []): string
{
    static $en = [
        'method.title' => 'CoreMark benchmark methodology and ranking rules',
        'method.intro' => 'The NAS CPU ranking and each CPU detail page (for example, Intel N100) are based on CoreMark results submitted from real user devices. This page explains where the data comes from, how it is reviewed and how rankings are calculated.',
        'method.why' => 'Why CoreMark',
        'method.why_text' => 'CoreMark is the industry-standard integer CPU benchmark published by EEMBC. Its single-thread score reflects per-core processing capability and is a practical reference for common low-power NAS CPUs such as N100, J4125 and RK3588. Unlike broad suites such as Geekbench, CoreMark is lightweight and can run on almost any system that can compile C, including Synology, QNAP, Feiniu, OMV and Unraid, making it a good fit for community-submitted data.',
        'method.source' => 'Data sources and test flow',
        'method.step1' => 'Run CoreMark on your own NAS or mini PC (we recommend compiling the official source with all cores enabled).',
        'method.step2' => 'Upload the result on the submission page, optionally including a screenshot, device model and system information.',
        'method.step3' => 'The result enters a review queue and is counted only after a manual plausibility check.',
        'method.step4' => 'Each record keeps its device and system context; the “Test sample details” section lets you inspect records one by one.',
        'method.anomaly' => 'Handling anomalous results',
        'method.anomaly1' => 'Results clearly outside the normal range for a model (overclocking, virtual-machine passthrough or fabrication) are flagged or rejected.',
        'method.anomaly2' => 'When a model has multiple samples, the ranking uses the best valid score. CPU pages also show the best, average, median and sample count.',
        'method.anomaly3' => 'Please report suspicious data through the feedback page so we can review it.',
        'method.algorithm' => 'Ranking algorithm',
        'method.algorithm_text' => 'Models are sorted by the best reviewed CoreMark score in descending order. The progress bar uses a segmented scale (0–50,000 takes 25% of the width and 50,000–1,500,000 takes 75%) so low-power NAS CPUs and high-performance desktop/server CPUs can share one chart.',
        'method.variance' => 'Why scores differ between systems',
        'method.variance_text' => 'Compiler versions, compiler flags, system scheduling and cooling affect CoreMark results. A 5–15% difference for the same CPU on different NAS systems is normal. We record real device environments, so the score represents what you can roughly expect on a NAS rather than a laboratory peak.',
        'method.updated' => 'Data updates',
        'method.updated_text' => 'The ranking and CPU pages update as reviews are approved; each sample’s submission date is shown in its details.',
        'method.citation' => 'Citing this site',
        'method.citation_text' => 'You are welcome to cite the ranking and CPU data on this site. Please credit the source and link back.',
        'method.related' => 'Related pages',
        'method.home' => 'NAS CPU performance ranking (full list)',
        'method.sample_n100' => 'Intel N100 benchmark page (example)',
        'method.sample_rk3588' => 'RK3588 benchmark page',
        'method.submit' => 'Submit my CoreMark result',
        'cpu.not_found' => 'This CPU page does not exist or has not been added yet.',
        'cpu.back_home' => 'Back to the NAS CPU performance ranking',
        'cpu.breadcrumb_home' => 'Home',
        'cpu.breadcrumb_ranking' => 'NAS CPU ranking',
        'cpu.title_suffix' => ' NAS CPU benchmark (CoreMark)',
        'cpu.subtitle_prefix' => 'Based on reviewed CoreMark submissions from real devices. There are ',
        'cpu.subtitle_middle' => ' samples, ranking ',
        'cpu.subtitle_total' => ' among ',
        'cpu.subtitle_suffix' => ' recorded CPUs. ',
        'cpu.method_link' => 'Learn about the methodology and ranking rules',
        'cpu.best' => 'Best CoreMark score',
        'cpu.average' => 'Average score',
        'cpu.median' => 'Median score',
        'cpu.rank' => 'Ranking',
        'cpu.samples' => 'Test samples',
        'cpu.core_spec' => 'Core specification',
        'cpu.tdp' => 'TDP',
        'cpu.process' => 'Process',
        'cpu.samples_detail' => 'Test sample details',
        'cpu.score' => 'Score',
        'cpu.device' => 'Test device',
        'cpu.system' => 'System',
        'cpu.cores' => 'Cores',
        'cpu.submitted' => 'Submitted',
        'cpu.status' => 'Status',
        'cpu.approved' => 'Reviewed',
        'cpu.not_provided' => 'Not provided',
        'cpu.sample_note' => 'All samples come from real user devices and are manually reviewed. Anomalous results may be flagged or removed. If you have run CoreMark on this CPU, please submit your result to help improve the data.',
        'cpu.neighbors' => 'Adjacent CPU rankings',
        'cpu.cpu_model' => 'CPU model',
        'cpu.best_short' => 'Best CoreMark',
        'cpu.gap' => 'Difference from ',
        'cpu.full_ranking' => 'See the full NAS CPU ranking on the home page.',
        'cpu.nas_devices' => 'NAS devices using ',
        'cpu.bays' => ' bays',
        'cpu.overview' => ' overview and use cases',
        'cpu.use_cases' => 'Use cases: ',
        'cpu.related_comparisons' => 'Related comparisons',
        'cpu.tools' => 'Related tools and tutorials',
        'cpu.raid' => 'RAID capacity calculator',
        'cpu.power' => 'NAS power calculator',
        'cpu.docker' => 'Docker Compose editor',
        'cpu.ipv6' => 'IPv6 connectivity test',
        'cpu.port' => 'NAS port forwarding helper',
        'cpu.articles' => 'NAS tutorials and articles',
        'cpu.dataset_name' => ' CoreMark benchmark data ',
        'cpu.dataset_technique' => 'CoreMark (submitted from real user devices and manually reviewed)',
        'compare.not_found' => 'This CPU comparison page does not exist or has not been added yet.',
        'compare.title_suffix' => ': NAS CPU performance comparison (CoreMark)',
        'compare.subtitle' => 'Based on CoreMark results submitted from real devices and manually reviewed.',
        'compare.method' => 'Learn about the methodology and ranking rules',
        'compare.section' => 'CoreMark benchmark comparison',
        'compare.item' => 'Metric',
        'compare.best' => 'Best CoreMark score',
        'compare.average' => 'Average score',
        'compare.rank' => 'Ranking',
        'compare.samples' => 'Test samples',
        'compare.specs' => 'Specification overview',
        'compare.details' => 'Details',
        'compare.benchmark_link' => ' benchmark',
        'compare.percent_prefix' => 'On the best CoreMark score, ',
        'compare.percent_middle' => ' is ',
        'compare.percent_suffix' => '% higher than ',
        'compare.choose' => 'Verdict and recommendation',
        'compare.disclaimer' => 'This conclusion is based on public specifications and measurements on this site for reference. Your workload (bay count, transcoding needs and Docker scale) matters more than a benchmark score.',
        'compare.related' => 'Related tools and pages',
        'compare.full_ranking' => 'Full NAS CPU ranking',
        'compare.raid' => 'RAID capacity calculator',
        'compare.power' => 'NAS power calculator',
        'compare.docker' => 'Docker Compose editor',
        'compare.articles' => 'NAS tutorials and articles',
        'runtime.search_placeholder' => 'Search CPU or device model, e.g. N100, J4125, RK3588',
        'runtime.nas_only' => 'NAS-related only',
        'runtime.all_cpu' => 'Show all CPUs',
        'runtime.loading' => 'Loading data…',
        'runtime.load_failed' => 'Failed to load',
        'runtime.no_data' => 'No data available',
        'runtime.unknown_device' => 'Unknown device',
        'runtime.unknown' => 'Unknown',
        'runtime.test_data' => 'View CoreMark benchmark data',
        'runtime.nas_recommend' => 'View NAS recommendations for this CPU',
        'runtime.test_device' => 'Test device',
        'runtime.submitted' => 'Submitted',
        'runtime.score' => 'CoreMark score',
        'runtime.review' => 'Review',
        'runtime.screenshot' => 'Benchmark screenshot',
        'runtime.screenshot_failed' => 'Screenshot failed to load',
        'runtime.nas_products' => 'NAS products using this CPU',
        'runtime.price_pending' => 'Price pending',
        'runtime.watch_review' => 'Watch the Bilibili review',
        'runtime.buy' => 'Buy now',
        'runtime.no_products' => 'No NAS products using this CPU were found',
        'runtime.retry' => 'Please retry',
        'nav.all_articles' => 'All articles',
        'nav.mobile_menu' => 'Menu',
        'auth.avatar_alt' => 'Avatar',
        'auth.user' => 'User',
        'auth.admin' => 'Admin',
        'auth.logout' => 'Log out',
        'auth.login' => 'Log in',
        'auth.register' => 'Register',
        'auth.confirm_logout' => 'Are you sure you want to log out?',
        'sidebar.ranking' => 'CPU ranking',
        'sidebar.tutorials' => 'NAS tutorials',
        'sidebar.faq' => 'FAQ',
        'sidebar.submit' => 'Submit benchmark',
        'sidebar.sponsor' => 'VIP sponsorship',
        'sidebar.feedback' => 'Feedback',
        'sidebar.nas_tools' => 'NAS tools',
        'sidebar.power' => 'Drive power calculator',
        'sidebar.more_tools' => 'More tools',
        'sidebar.text_tools' => 'Text tools',
        'sidebar.screen_test' => 'Screen test',
        'sidebar.screen_recorder' => 'Screen recorder',
        'sidebar.bandwidth' => 'Bandwidth calculator',
        'sidebar.keyboard_clean' => 'Keyboard cleaning mode',
        'sidebar.all_tools' => 'View all tools',
        'sidebar.profile' => 'View profile',
        'footer.nas_docker' => 'NAS & Docker',
        'footer.link_coremark_method' => 'CoreMark methodology',
        'footer.link_synology' => 'Synology NAS',
        'footer.link_qnap' => 'QNAP NAS',
        'footer.link_ugreen' => 'UGREEN NAS',
        'footer.link_zspace' => 'ZSpace NAS',
        'runtime.search_help' => 'Data comes from CoreMark results submitted by real users and manually reviewed.',
        'runtime.methodology_link' => 'View methodology and ranking rules',
        'runtime.details_help' => 'Click a CPU name to view its measured details.',
        'runtime.export_scope' => 'Export scope',
        'runtime.export_current' => 'Follow current filters',
        'runtime.export_all' => 'Show all CPUs',
        'runtime.arch_x86' => 'x86_64 architecture',
        'runtime.arch_arm' => 'ARM64 architecture',
        'runtime.category_nas_common' => 'Typical NAS',
        'runtime.category_diy_low_power' => 'Low-power DIY',
        'runtime.category_router_common' => 'Typical router',
        'runtime.category_domestic' => 'Domestic platform',
        'runtime.category_server_workstation' => 'Server / workstation',
        'runtime.category_high_performance' => 'High performance',
        'runtime.category_mobile_soc' => 'Mobile SoC',
        'runtime.category_legacy' => 'Legacy platform',
        'runtime.category_other' => 'Other',
        'runtime.badge_router' => 'Router',
        'runtime.badge_server' => 'Server',
        'runtime.badge_high_performance' => 'High performance',
        'runtime.badge_mobile' => 'Mobile',
        'runtime.badge_legacy' => 'Legacy',
        'runtime.badge_non_nas' => 'Not NAS-focused',
        'runtime.badge_non_typical' => 'Non-typical NAS',
        'runtime.badge_high_power' => 'High power',
        'runtime.scenario_generic' => 'General-purpose NAS',
        'runtime.scenario_mobile' => 'Mobile-platform NAS',
        'runtime.scenario_router' => 'Router / gateway',
        'runtime.scenario_server' => 'Server and workstation',
        'runtime.scenario_high_performance' => 'High-performance NAS',
        'runtime.scenario_advanced' => 'Advanced self-hosting',
        'runtime.scenario_mainstream' => 'Mainstream NAS',
        'runtime.scenario_entry' => 'Entry-level NAS',
        'runtime.scenario_basic' => 'Basic storage NAS',
        'runtime.tasks' => 'Typical workloads',
        'runtime.limitations' => 'Limitations',
        'runtime.cpu_count' => '{count} CPUs',
        'runtime.filtered_count' => 'Showing {count} / {total} CPUs',
        'runtime.loading_error' => 'Loading failed: {message}',
        'runtime.network_error' => 'Network error: {message}',
        'runtime.cores_threads' => 'Cores / threads',
        'runtime.submitter' => 'Submitter',
        'runtime.submitted_at' => 'Submitted at',
        'runtime.benchmark_score' => 'Benchmark score',
        'runtime.tdp_power' => 'TDP / power',
        'runtime.efficiency' => 'Efficiency',
        'runtime.memory' => 'Memory',
        'runtime.os' => 'Operating system',
        'runtime.network' => 'Network',
        'runtime.bays' => 'Drive bays',
        'runtime.docker' => 'Docker',
        'runtime.video_review' => 'Video review',
        'runtime.data_source' => 'Data source',
        'runtime.no_results' => 'No matching CPUs found',
        'runtime.copy_success' => 'Command copied',
        'runtime.copy_failed' => 'Copy failed. Please copy the command manually.',
        'runtime.download_preview' => 'Download preview',
        'runtime.export_model' => 'CPU model',
        'runtime.export_cores' => 'Cores',
        'runtime.export_tdp' => 'TDP',
        'runtime.export_score' => 'Score',
        'runtime.server_class' => 'Server class',
        'runtime.flagship' => 'Flagship',
        'runtime.high_end' => 'High-end',
        'runtime.upper_mid_range' => 'Upper mid-range',
        'runtime.mid_range' => 'Mid-range',
        'runtime.entry_level' => 'Entry-level',
        'runtime.low_power' => 'Low power',
        'runtime.arm_nas' => 'ARM NAS',
        'runtime.arm_router' => 'ARM router',
        'runtime.mobile_flagship' => 'Mobile flagship',
        'runtime.mobile_high' => 'High-end mobile',
        'runtime.old_platform' => 'Legacy platform',
        'runtime.es_version' => 'Engineering sample',
        'runtime.overkill' => 'Overkill for typical NAS use',
        'runtime.test_data_title' => 'Measured CoreMark results',
        'runtime.nas_recommendation' => 'NAS product recommendations',
        'runtime.bays_unit' => 'bays',
        'runtime.score_per_watt' => 'Score per watt',
        'runtime.review_label' => 'Review',
        'runtime.loading_failed' => 'Loading failed',
        'runtime.no_nas_products' => 'No NAS products using this CPU were found',
        'runtime.anonymous' => 'Anonymous',
        'runtime.screenshot_alt' => 'CoreMark benchmark screenshot',
        'runtime.screenshot_load_failed' => 'Screenshot failed to load',
        'runtime.data_source_note' => 'Data comes from real-world tests and is provided for reference only.',
        'runtime.export_no_data' => 'No exportable data available',
        'runtime.export_library_loading' => 'The image generation library is still loading. Please try again shortly.',
        'runtime.export_failed' => 'Export failed: {message}',
        'runtime.export_retry' => 'Retry',
        'runtime.export_preview_first' => 'Generate the preview before downloading.',
        'runtime.continuously_updated' => 'Continuously updated',
        'runtime.exported' => 'Exported',
        'runtime.total_cpus' => 'Total CPUs',
        'runtime.scope' => 'Scope',
        'runtime.model' => 'Model',
        'runtime.cores' => 'Cores',
        'runtime.tdp' => 'TDP',
        'runtime.supported' => 'Supported',
        'runtime.view_benchmark' => 'View benchmark',
        'runtime.full_ranking' => 'Full ranking',
        'runtime.docker_supported' => 'Docker supported',
        'runtime.brand_models' => 'Models',
        'runtime.drive_bays' => 'Drive bays',
        'runtime.system' => 'System',
        'runtime.cpu_benchmark_section' => 'Common CPU performance (CoreMark measurements)',
        'runtime.coremark_best' => 'Best CoreMark',
        'runtime.benchmark_details' => 'Benchmark details',
        'runtime.reviewed_data_note' => 'Scores are reviewed CoreMark results submitted by users.',
        'runtime.see_methodology' => 'See the methodology',
        'runtime.related_tools' => 'Related tools',
        'runtime.tools_intro' => 'Useful online tools when configuring a {brand} NAS:',
        'runtime.more_content' => 'More content',
        'runtime.all_brands_ranking' => 'NAS CPU ranking (all brands)',
        'runtime.nas_tutorials' => 'NAS tutorials and articles',
        'runtime.submit_coremark' => 'Submit my CoreMark result',
        'runtime.home' => 'Home',
        'runtime.nas_cpu_ranking' => 'NAS CPU ranking',
        'runtime.nas_model_list' => 'NAS model list',
        'runtime.cpu' => 'CPU',
        'runtime.memory_spec' => 'Memory',
        'runtime.video_review_label' => 'Video review',
        'runtime.nas_products_count' => '{count} products',
        'runtime.unknown_price' => 'Price pending',
        'runtime.watch_video_review' => 'Watch the Bilibili review',
        'runtime.nas_product_specs' => 'NAS products using {cpu}',
        'runtime.cores_performance' => 'Performance cores',
        'runtime.cores_efficiency' => 'Efficiency cores',
    ];

    $text = (i18n_lang() === 'en-US' && isset($en[$key])) ? $en[$key] : $zh;
    foreach ($params as $name => $value) {
        $text = str_replace('{' . $name . '}', (string)$value, $text);
    }
    return $text;
}

/**
 * The footer predates the CPU translation work and some English database
 * rows still contain generated labels such as "Link Nat Test". Keep the
 * public footer readable in English while preserving the database-backed
 * translations for all other languages.
 */
function cpu_footer_t(string $key, string $fallback = ''): string
{
    static $en = [
        'footer.stat_cpus' => 'CPUs listed',
        'footer.stat_benchmarks' => 'Benchmarks',
        'footer.stat_articles' => 'Guides',
        'footer.stat_ipv6' => 'IPv6 tests',
        'footer.stats_note' => 'Figures come from our own database, refreshed hourly',
        'footer.about_desc' => 'A professional NAS benchmarking and utility platform based on real CoreMark test data, with practical tools for networking, storage planning, and Docker deployment.',
        'footer.free_notice' => 'Most tools are free to use without signing in. Sponsoring members can unlock additional advanced features.',
        'footer.network_tools' => 'Network Tools',
        'footer.link_nat_test' => 'NAT type test',
        'footer.link_ipv6_test' => 'IPv6 connectivity test',
        'footer.link_ip_test' => 'IP address lookup',
        'footer.link_netdiag' => 'Network diagnostics',
        'footer.link_port_forward' => 'NAS port forwarding helper',
        'footer.link_bandwidth_calc' => 'Bandwidth calculator',
        'footer.link_wol' => 'Wake-on-LAN tool',
        'footer.nas_docker' => 'NAS & Docker',
        'footer.link_cpu_ranking' => 'NAS CPU ranking',
        'footer.link_submit_score' => 'Submit benchmark',
        'footer.link_coremark_method' => 'CoreMark methodology',
        'footer.link_raid_calc' => 'RAID capacity calculator',
        'footer.link_synology' => 'Synology NAS',
        'footer.link_qnap' => 'QNAP NAS',
        'footer.link_ugreen' => 'UGREEN NAS',
        'footer.link_zspace' => 'ZSpace NAS',
        'footer.link_power_calc' => 'NAS power calculator',
        'footer.link_backup_planner' => 'Backup planner',
        'footer.link_docker_registry' => 'Docker registry speed test',
        'footer.link_docker_compose' => 'Docker Compose editor',
        'footer.developer' => 'For developers',
        'footer.link_ip_api' => 'IP lookup API',
        'footer.link_api_docs' => 'API documentation',
        'footer.link_api_pricing' => 'Plans & pricing',
        'footer.link_api_console' => 'API console',
        'footer.link_cli_tools' => 'Command-line endpoints',
        'footer.link_cron' => 'Cron expression generator',
        'footer.link_code_editor' => 'Online code editor',
        'footer.tutorials_more' => 'Tutorials & more',
        'footer.link_all_articles' => 'All articles',
        'footer.link_oec_tutorial' => 'OEC Armbian tutorial',
        'footer.link_nas_gateway' => 'NAS as bypass gateway',
        'footer.link_remote_access' => 'Remote access tutorial',
        'footer.link_faq' => 'FAQ',
        'footer.link_feedback' => 'Feedback',
        'footer.link_sponsor' => 'Sponsor',
        'footer.language' => 'Language',
        'footer.platform_desc' => 'NAS Benchmarking & Utility Platform',
        'footer.link_sitemap' => 'Sitemap',
        'footer.back_to_top' => 'Back to top',
    ];

    static $overrides = [
        'zh-TW' => [
            'footer.about_desc' => '專業的 NAS 效能評測與工具平台，基於 CoreMark 實測資料，提供網路檢測、儲存規劃與 Docker 部署等實用工具。',
            'footer.link_ip_test' => 'IP 位址查詢',
            'footer.link_port_forward' => 'NAS 連接埠轉發工具',
            'footer.link_wol' => 'WOL 遠端喚醒',
            'footer.link_docker_compose' => 'Docker Compose 編輯器',
        ],
        'ja-JP' => [
            'footer.about_desc' => '実測 CoreMark データを基に、ネットワーク診断、ストレージ計画、Docker 導入に役立つツールを提供する NAS 性能評価プラットフォームです。',
            'footer.link_oec_tutorial' => 'OEC Armbian 導入ガイド',
        ],
        'ko-KR' => [
            'footer.about_desc' => '실제 CoreMark 데이터를 기반으로 네트워크 진단, 스토리지 계획, Docker 배포 도구를 제공하는 NAS 성능 평가 플랫폼입니다.',
            'footer.link_oec_tutorial' => 'OEC Armbian 설치 가이드',
        ],
    ];

    $language = i18n_lang();
    if (isset($overrides[$language][$key])) {
        return $overrides[$language][$key];
    }
    if ($language === 'en-US' && isset($en[$key])) {
        return $en[$key];
    }
    if (function_exists('__')) {
        return __($key);
    }
    return $fallback !== '' ? $fallback : $key;
}

function cpu_localize_core_info(string $text, ?string $language = null): string
{
    $language = $language ?: i18n_lang();
    if ($text === '' || $language !== 'en-US') return $text;
    $text = strtr($text, ['十六'=>'16','十四'=>'14','十二'=>'12','十'=>'10','八'=>'8','六'=>'6','四'=>'4','三'=>'3','双'=>'2','二'=>'2','一'=>'1']);
    $unit = static fn($n, $s, $p) => ((int)$n === 1 ? $s : $p);
    $text = preg_replace_callback('/(\d+)\s*C\s*\/\s*(\d+)\s*T(?:核)?(?:\s*(\d+)\s*(?:线程|線程|线|線))?/iu', static function($m) use ($unit) {
        $cores = $m[1];
        $threads = $m[2];
        return $cores.' '.$unit($cores,'core','cores').' / '.$threads.' '.$unit($threads,'thread','threads');
    }, $text);
    $text = preg_replace_callback('/(\d+)\s*C\s*(?:\/\s*)?(\d+)\s*T/iu', static function($m) use ($unit) {
        $cores = $m[1];
        $threads = $m[2];
        return $cores.' '.$unit($cores,'core','cores').' / '.$threads.' '.$unit($threads,'thread','threads');
    }, $text);
    $text = preg_replace_callback('/(\d+)\s*(?:核心|核)\s*(\d+)\s*(?:线程|線程|线|線)/u', static function($m) use ($unit) { return $m[1].' '.$unit($m[1],'core','cores').' / '.$m[2].' '.$unit($m[2],'thread','threads'); }, $text);
    $text = preg_replace_callback('/(\d+)\s*(性能核|大核)/u', static function($m) use ($unit) { return $m[1].' performance '.$unit($m[1],'core','cores'); }, $text);
    $text = preg_replace_callback('/(\d+)\s*(能效核|小核)/u', static function($m) use ($unit) { return $m[1].' efficiency '.$unit($m[1],'core','cores'); }, $text);
    $text = preg_replace_callback('/(\d+)\s*(?:核心|核)/u', static function($m) use ($unit) { return $m[1].' '.$unit($m[1],'core','cores'); }, $text);
    $text = preg_replace_callback('/(\d+)\s*(?:线程|線程|线|線)/u', static function($m) use ($unit) { return $m[1].' '.$unit($m[1],'thread','threads'); }, $text);
    $text = str_replace(['核架构','架构'], ['-core architecture','architecture'], $text);
    $text = preg_replace('/\s*\+\s*/', ' + ', $text);
    return trim(preg_replace('/\s{2,}/', ' ', $text));
}

/**
 * Localize the NAS catalog values used by CPU pages and recommendation cards.
 *
 * NAS data predates the translation editor, so English currently uses a small
 * terminology map and can transparently prefer future *_en columns when they
 * are added. CPU/NAS model identifiers and URLs are intentionally untouched.
 */
function cpu_localize_nas_value($value, string $field = '', ?string $language = null)
{
    $language = $language ?: i18n_lang();
    if ($language !== 'en-US' || $value === null || $value === '') return $value;

    if (is_array($value)) {
        $localized = [];
        foreach ($value as $key => $item) {
            $localized[$key] = cpu_localize_nas_value($item, $field, $language);
        }
        return $localized;
    }
    if (!is_scalar($value)) return $value;

    $text = trim((string)$value);
    if ($text === '') return $text;

    // A bays value is rendered with the UI's "bays" suffix, so return only
    // the number for this field. Other fields keep the descriptive "-bay".
    if ($field === 'bays') {
        $text = strtr($text, [
            '单盘位' => '1', '双盘位' => '2', '四盘位' => '4',
            '六盘位' => '6', '八盘位' => '8', '三盘位' => '3',
        ]);
    }

    $replacements = [
        '4GB(可扩展32G)' => '4GB (expandable to 32GB)',
        '4GB（可扩展32G）' => '4GB (expandable to 32GB)',
        '可扩展32G' => 'expandable to 32GB',
        '可扩展 32G' => 'expandable to 32GB',
        '全闪四盘位' => '4-bay all-flash',
        '双盘位私有云' => '2-bay private cloud',
        '双盘位' => '2-bay', '单盘位' => '1-bay',
        '四盘位' => '4-bay', '六盘位' => '6-bay', '八盘位' => '8-bay',
        '三盘位' => '3-bay', '全闪' => 'all-flash',
        '私有云' => 'private cloud', '个人云' => 'personal cloud',
        '性能版' => 'performance edition', '准系统' => 'barebone',
        '迷你主机' => 'mini PC', '自购' => 'self-purchased',
        '双2.5G' => 'dual 2.5GbE', '双 2.5G' => 'dual 2.5GbE',
        '万兆' => '10GbE', '千兆' => '1GbE', '2.5G' => '2.5GbE',
        '系统' => 'system', '支持' => 'supported', '不支持' => 'not supported',
    ];
    return trim(strtr($text, $replacements));
}

/**
 * Localize one NAS product in place while preserving the API's existing shape.
 * Future *_en fields are preferred automatically when present in the row.
 */
function cpu_localize_nas_product(array $product, ?string $language = null): array
{
    $language = $language ?: i18n_lang();
    if ($language !== 'en-US') return $product;

    $fields = [
        'model', 'memory', 'os', 'description', 'recommendation_reason',
        'highlights', 'use_cases', 'tags', 'featured_label', 'bays', 'network_port',
    ];
    foreach ($fields as $field) {
        $englishField = $field . '_en';
        if (isset($product[$englishField]) && trim((string)$product[$englishField]) !== '') {
            $product[$field] = $product[$englishField];
        } elseif (array_key_exists($field, $product)) {
            $product[$field] = cpu_localize_nas_value($product[$field], $field, $language);
        }
    }
    return $product;
}

/**
 * Return the columns that are available on a small, explicitly allowed set of
 * public catalog tables.  Internationalized columns are deployed separately
 * from the application code, so public APIs must remain compatible with an
 * older schema while those migrations are pending.
 */
function cpu_i18n_table_columns(PDO $pdo, string $table): array
{
    $allowed = ['benchmark_submissions', 'cpus'];
    if (!in_array($table, $allowed, true)) {
        return [];
    }

    try {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
        $columns = $statement ? $statement->fetchAll(PDO::FETCH_COLUMN, 0) : [];
        return array_fill_keys(array_map('strval', $columns), true);
    } catch (Throwable $e) {
        // The caller can still use its required legacy fields if metadata is
        // temporarily unavailable; never turn this compatibility check into
        // a user-facing fatal error.
        return [];
    }
}

/**
 * Build a safe SELECT list for an aliased table. Missing columns become NULL
 * under their expected alias, preserving the JSON shape consumed by clients.
 */
function cpu_i18n_select_columns(array $available, string $alias, array $fields): string
{
    $parts = [];
    foreach ($fields as $field) {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field)) {
            continue;
        }
        $qualified = $alias !== '' ? $alias . '.' . $field : $field;
        $parts[] = isset($available[$field]) ? $qualified : 'NULL AS ' . $field;
    }
    return implode(",\n         ", $parts);
}
