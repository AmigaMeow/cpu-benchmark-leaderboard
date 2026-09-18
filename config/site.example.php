<?php
// Copy to config/site.php and adjust. Every key is optional — the getters in
// includes/settings.php fall back to built-in defaults.
return [
    'site_name' => getenv('SITE_NAME') ?: 'Benchmark Leaderboard',
    'site_url' => getenv('SITE_URL') ?: 'https://example.com',
    'site_description' => 'Community CPU benchmark leaderboard powered by CoreMark submissions.',
    'seo_keywords' => 'CoreMark,NAS,CPU benchmark,leaderboard',
    'homepage_title' => 'CPU Benchmark Leaderboard',
    'homepage_og_title' => 'CPU Benchmark Leaderboard',
    'homepage_og_description' => 'Community CPU benchmark leaderboard powered by CoreMark submissions.',
    // 一键 CoreMark 跑分脚本地址（可换成你自己托管的脚本）
    'coremark_script_url' => getenv('COREMARK_SCRIPT_URL') ?: '/coremark/run.sh',
];
