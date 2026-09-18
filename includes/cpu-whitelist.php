<?php
/**
 * CPU 实体页白名单（单一数据源，cpu-detail.php 使用）
 * 只有名单内的型号才有 /cpu/{slug} 详情页；不在名单内的型号返回 404。
 * 按需要把型号加进名单即可。
 */
if (!defined('CPU_WHITELIST_LOADED')) {
    define('CPU_WHITELIST_LOADED', 1);

    $GLOBALS['CPU_WHITELIST'] = [
        // x86
        'Intel N100', 'Intel N150', 'Intel N305', 'N95', 'N97', 'N355',
        'J4125', 'J3455', 'N5105', 'Intel N5095', 'N5095A',
        'R1600', 'AMD Ryzen V1500B', 'AMD Ryzen 5 5600U', 'AMD Ryzen 7 5825U',
        'i5-1235U', 'i3-1215U', 'i5-8259U', 'Intel Pentium Gold 8505',
        'AMD EPYC 7D12', 'AI 9 HX PRO 370', 'i5-13500H', 'i7-12700H',
        'Intel Xeon E-2234',
        // ARM
        'RK3588', 'RK3568', 'RK3576', 'RK3566', 'RK3399',
        'Amlogic S922X', 'Allwinner H618', 'Broadcom BCM2712',
        'Qualcomm IPQ9574', 'MediaTek Filogic 830', 'M4',
    ];
    $GLOBALS['CPU_SLUG_OVERRIDES'] = [
        'Intel Pentium Gold 8505' => 'pentium-gold-8505',
        'M4' => 'apple-m4',
    ];
}

function cpu_detail_slugify($model, $overrides = null) {
    if ($overrides === null) {
        $overrides = $GLOBALS['CPU_SLUG_OVERRIDES'];
    }
    if (isset($overrides[$model])) return $overrides[$model];
    $s = strtolower(trim($model));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}
