<?php
/**
 * 页脚（开源版）：简介 + 实时统计 + 链接 + 移动端底部导航。
 *
 * 页面可在 include 前设置 $footerLinks（同 $navLinks 结构）追加链接列。
 */
require_once __DIR__ . '/footer-stats.php';
$footerStats = footer_site_stats();
$footerLang = i18n_lang();
$footerUrl = static function (string $path): string {
    return htmlspecialchars(i18n_localized_url($path), ENT_QUOTES, 'UTF-8');
};
$footerLinks = $footerLinks ?? [];
?>
<footer class="site-footer">
    <div class="site-footer-inner">
        <p class="site-footer-desc"><?php echo htmlspecialchars(SiteSettings::getSiteDescription()); ?></p>
        <?php if (!empty($footerStats)): ?>
        <p class="site-footer-stats">
            <span><?php echo htmlspecialchars(__('footer.stat_cpus')); ?>: <?php echo footer_stat_number($footerStats['cpus'], $footerLang); ?></span>
            <span><?php echo htmlspecialchars(__('footer.stat_benchmarks')); ?>: <?php echo footer_stat_number($footerStats['benchmarks'], $footerLang); ?></span>
        </p>
        <?php endif; ?>
        <?php if (!empty($footerLinks)): ?>
        <p class="site-footer-links">
            <?php foreach ($footerLinks as $i => $link): ?>
                <?php if ($i > 0): ?> · <?php endif; ?><a href="<?php echo $footerUrl($link['href']); ?>"><?php echo htmlspecialchars($link['label']); ?></a>
            <?php endforeach; ?>
        </p>
        <?php endif; ?>
        <p class="site-footer-copy">MIT License · Powered by CoreMark data</p>
    </div>
</footer>
<style>
.site-footer {
    margin-top: 60px;
    padding: 32px 20px 80px;
    border-top: 1px solid #e8e8e8;
    background: #fafafa;
    text-align: center;
    color: #666;
    font-size: 14px;
}
.site-footer-inner { max-width: 800px; margin: 0 auto; }
.site-footer-desc { margin: 0 0 10px; }
.site-footer-stats span { margin: 0 8px; }
.site-footer-links a { color: #2563eb; text-decoration: none; }
.site-footer-links a:hover { text-decoration: underline; }
.site-footer-copy { color: #999; font-size: 12px; margin-top: 12px; }
</style>

<?php include __DIR__ . '/mobile-nav.php'; ?>
