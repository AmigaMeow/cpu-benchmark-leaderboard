<?php
/**
 * SEO 阶段三 4.2：CPU 对比页首批组合（手工精选，结论为人工撰写——计划书要求"不纯模板生成"）
 * slug 顺序 = a-vs-b；两端型号必须在 benchmark_submissions 有已审核数据（同时建议在 CPU_WHITELIST 内以便互链）。
 */
if (!defined('CPU_COMPARE_PAIRS_LOADED')) {
    define('CPU_COMPARE_PAIRS_LOADED', 1);

    $GLOBALS['CPU_COMPARE_PAIRS'] = [
        [
            'a' => 'Intel N100', 'b' => 'J4125',
            'text' => 'N100 是 J4125 的跨代继任者：Intel 7 制程、能效比大幅提升（6W vs 10W），CoreMark 成绩高出约 65%。做 NAS 的话两者 4 核 4 线程规格相同，日常文件服务、Docker 轻量应用都够用，但 N100 的 AV1 硬解和更强单核让 Jellyfin/Emby 转码和下一代系统支持明显更从容。新购机无脑选 N100；J4125 机器（含群晖 DS220+/DS920+ 同代平台）继续服役完全没问题，除非你需要 4K 转码或多容器并行。',
            'text_en' => 'N100 is the generational successor to J4125. Its Intel 7 process and 6 W power envelope deliver about 65% more CoreMark performance than the 10 W J4125. Both are 4-core/4-thread NAS platforms that handle file serving and light Docker workloads, but N100 adds AV1 decode, stronger single-core performance and a longer software runway for Jellyfin or Emby transcoding. Choose N100 for a new build; an existing J4125 system can keep running unless you need 4K transcoding or many containers.',
        ],
        [
            'a' => 'Intel N100', 'b' => 'N5105',
            'text_en' => 'Both are low-power 4-core/4-thread platforms. N100 (Intel 7, 6 W) is about 23% faster than N5105 (Jasper Lake, 10 W) while using less power. N5105 is still adequate for 4K H.265 hardware decode and pure storage, but N100 offers better expansion and longer-term driver support for Docker, virtual machines and newer graphics stacks. Prefer N100 when pricing is close.',
        ],
        [
            'a' => 'Intel N100', 'b' => 'R1600',
            'text_en' => 'R1600 is a 15 W embedded AMD Zen dual-core/four-thread chip. SMT helps its multi-thread throughput, but it still trails N100 clearly in CoreMark and single-core work. Its advantages are ECC memory support and AMD PCIe connectivity, which suit a DIY storage server. For a typical NAS, N100 is easier to live with: roughly half the power, integrated transcoding support and a broader accessory ecosystem.',
        ],
        [
            'a' => 'RK3588', 'b' => 'Intel N100',
            'text_en' => 'This is the classic ARM-eight-core versus x86-four-core matchup. RK3588\'s four performance and four efficiency cores can approach or occasionally match N100 in multi-threaded CoreMark, while N100 retains the edge in single-thread performance and software compatibility. RK3588 brings an NPU, 4K120 output and inexpensive multi-bay ARM NAS systems; N100 is more predictable with x86 Docker images, virtual machines and Intel QSV transcoding. Choose RK3588 for the ARM ecosystem, or N100 for hassle-free self-hosting.',
        ],
        [
            'a' => 'Intel N100', 'b' => 'Intel N150',
            'text_en' => 'N150 is N100\'s direct Twin Lake successor. It keeps the 4-core/4-thread, 6 W design, raises clocks slightly and improves CoreMark by roughly 10%. NAS behavior is otherwise almost identical, including AV1 decode and DDR5 support. Pick N150 for a new build when prices are similar; a discounted N100 is still a sensible choice.',
        ],
        [
            'a' => 'Intel N100', 'b' => 'Intel N5095',
            'text_en' => 'N5095 (Jasper Lake, 15 W) was a popular previous-generation entry chip. N100 delivers about 30% more performance at less than half the power, so it is a broad upgrade. Existing N5095/N5105-era NAS systems remain serviceable, but a new mini PC or NAS board should generally favor the faster, cooler and more efficient N100 or N150 at a similar price.',
        ],
        [
            'a' => 'Intel N100', 'b' => 'N97',
            'text_en' => 'N97 is effectively a higher-clocked N100: both use the 4-core/4-thread Alder Lake-N design, but N97 allows a 12 W TDP for higher sustained performance. It suits a compact router/NAS with adequate cooling. Choose N100 for the quietest 6 W operation, or N97 when performance takes priority and thermals allow it.',
        ],
        [
            'a' => 'J4125', 'b' => 'N5105',
            'text_en' => 'Both are 10 W-class 4-core/4-thread chips, but Jasper Lake N5105 is about 34% faster than Gemini Lake Refresh J4125. Its 24-EU UHD graphics also improve 4K decode and display output over UHD 600. Either generation works for file storage; N5105 leaves more headroom for 4K media and Docker. There is usually no reason to replace a working J4125 solely for this difference.',
        ],
        [
            'a' => 'J4125', 'b' => 'R1600',
            'text_en' => 'J4125 wins on Intel graphics and ecosystem compatibility, while R1600 counters with Zen SMT and ECC support. R1600 is slightly ahead in CoreMark, but J4125\'s UHD transcoding and broad compatibility with NAS systems are friendlier for ordinary deployments. Pick R1600 when ZFS, ECC and experimentation matter more than integrated graphics.',
        ],
        [
            'a' => 'AMD Ryzen V1500B', 'b' => 'Intel N100',
            'text_en' => 'V1500B is the embedded Zen 4-core/8-thread APU used in systems such as Synology DS923+ and DS1522+. Its multi-thread throughput is clearly stronger than N100 for Btrfs snapshots, concurrent volumes and virtual machines, but it has no integrated graphics for hardware transcoding and platforms cost more. Choose an N100 mini NAS for value and versatility; choose V1500B for a stable, storage-first system when Plex/Jellyfin transcoding is not required.',
        ],
        [
            'a' => 'RK3588', 'b' => 'RK3568',
            'text_en' => 'RK3588 (4x A76 + 4x A55, 8 nm) is a generational leap over RK3568 (4x A55, 22 nm), scoring about 190% higher in CoreMark and adding a 6T NPU, 8K decode and USB 3/PCIe 3 expansion. RK3568 is cheaper and lower-power for a basic downloader or single-drive NAS; choose RK3588 when budget allows, especially for Docker or 4K media.',
        ],
        [
            'a' => 'Intel N5095', 'b' => 'N5105',
            'text_en' => 'Both are Jasper Lake 4-core/4-thread chips. N5095 clocks slightly higher at 15 W, while N5105 is more efficient at 10 W; the CoreMark gap is small. NAS experience is broadly the same, so choose based on total system price, cooling and noise rather than paying a premium for the model name.',
        ],
        [
            'a' => 'Intel N100', 'b' => 'N95',
            'text_en' => 'N95 and N100 share the same architecture and 4-core/4-thread layout. Differences are mainly graphics EU count and clock details, with only about a 1–2% CoreMark gap in everyday NAS use. Buy whichever complete system is cheaper; N100 is the safer pick when graphics transcoding headroom matters.',
        ],
        [
            'a' => 'N355', 'b' => 'Intel N100',
            'text_en' => 'N355 is Intel\'s 2025 entry-level 4-core/4-thread Twin Lake chip. It sits in the same general class as N100 and N150, with final performance depending on cooling and memory configuration. For NAS use, all three are efficient, capable options; prioritize total price and board quality, and do not upgrade an existing N100 without a concrete need.',
        ],
    ];
}
