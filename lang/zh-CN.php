<?php
/**
 * zh-CN（默认语言，完整 key 清单；其他语言文件只做覆盖）
 */
return [
    'nav.home' => '天梯榜',
    'nav.methodology' => '测试方法',
    'common.cancel' => '取消',

    // 首页
    'home.heading_prefix' => 'NAS CPU 性能',
    'home.heading' => '天梯榜',
    'home.loading' => '加载中...',
    'home.no_data' => '暂无数据',
    'home.retry' => '重试',
    'home.stats.performance' => 'CoreMark 性能榜',
    'home.stats.last_update' => '最后更新',
    'home.notice_title' => '说明：',
    'home.notice_body' => '榜单数据来自真实设备提交的 CoreMark 成绩（人工审核），仅供参考。',
    'home.filter.all' => '全部',
    'home.filter.arm' => 'ARM 架构',
    'home.filter.x86' => 'x86 架构',
    'home.ranking_tab' => '天梯榜',
    'home.ranking_x86_label' => 'x86',
    'home.ranking_arm_label' => 'ARM',
    'home.download_title' => '导出天梯图',
    'home.back_to_top' => '回到顶部',

    // CoreMark 指南
    'home.guide_title' => '如何跑出你的 CoreMark 成绩',
    'home.guide_intro' => '三步即可在你的 NAS / 设备上跑出可对比的 CoreMark 分数：',
    'home.guide_step1_title' => '第一步：SSH 登录设备',
    'home.guide_step1_desc' => '通过 SSH 登录你的 NAS、软路由或开发板（需要可执行命令的权限）。',
    'home.guide_step2_title' => '第二步：运行一键跑分脚本',
    'home.guide_step2_desc' => '在设备上执行下面任意一条命令，脚本会自动编译并运行 CoreMark：',
    'home.guide_or' => '或',
    'home.guide_step3_title' => '第三步：对照天梯榜',
    'home.guide_step3_desc_prefix' => '跑完后回到本页，搜索你的 CPU 型号即可',
    'home.guide_step3_link' => '查看排名与测试方法',
    'home.guide_step3_desc_suffix' => '。',

    // 详情弹层
    'home.detail_title_suffix' => '实测详情',
    'home.detail_test_device' => '测试设备',
    'home.detail_cores' => '核心 / 线程',
    'home.detail_submit_time' => '提交时间',
    'home.detail_score' => 'CoreMark 跑分',
    'home.unknown_device' => '未知设备',
    'home.unknown' => '未知',
    'home.anonymous' => '匿名',
    'home.data_reference_note' => '数据来源于实际测试，仅供参考。',
    'home.detail_load_failed_basic' => '详细数据加载失败，仅显示基本信息。',
    'home.load_failed_prefix' => '加载失败：',
    'home.load_failed_retry' => '加载失败，请稍后重试',

    // 导出模态框
    'home.export_title' => '导出性能天梯图',
    'home.export_subtitle' => '生成一张可分享的榜单图片',
    'home.export_generating_preview' => '正在生成预览...',
    'home.export_format' => '图片格式',
    'home.export_format_png' => 'PNG（推荐）',
    'home.export_format_jpeg' => 'JPEG',
    'home.export_arch_filter' => '架构筛选',
    'home.export_arch_all' => '全部架构',
    'home.export_x86_arch' => 'x86 架构',
    'home.export_arm_arch' => 'ARM 架构',
    'home.export_count' => '数量',
    'home.export_top20' => '前 20 名',
    'home.export_top30' => '前 30 名',
    'home.export_top50' => '前 50 名',
    'home.export_download_image' => '下载图片',
    'home.export_data_loading_alert' => '数据加载失败，请稍后重试',
    'home.export_library_loading_alert' => '图片生成库还未加载完成，请稍后再试',
    'home.export_no_data_error' => '没有可用的数据',
    'home.export_generate_failed_prefix' => '生成失败：',
    'home.export_generate_preview_first_alert' => '请先生成预览',
    'home.export_filename_prefix' => 'CPU-天梯榜',

    // Schema.org
    'home.schema.website_name' => 'CPU 性能天梯榜',
    'home.schema.website_description' => '基于真实设备 CoreMark 实测成绩的 NAS CPU 性能天梯榜',
    'home.schema.breadcrumb_home' => '首页',
    'home.schema.itemlist_name' => 'CPU 性能天梯榜',
    'home.schema.itemlist_description' => '真实设备 CoreMark 成绩排名',
    'home.schema.itemlist_item_name' => 'CPU 跑分',
    'home.schema.itemlist_item_description' => 'CoreMark 实测跑分数据',
    'home.schema.faq_q2_text' => '在设备上执行 curl -fsSL https://17nas.com/coremark/run.sh | sh 即可自动编译并运行 CoreMark。',

    // 页脚统计
    'footer.stat_cpus' => '收录 CPU',
    'footer.stat_benchmarks' => '跑分记录',

    // SEO（留空则回落到 config/site.php 的设置）
    'seo.home.meta_title' => '',
    'seo.home.meta_keywords' => '',
    'seo.home.meta_description' => '',
    'seo.home.og_title' => '',
    'seo.home.og_description' => '',
];
