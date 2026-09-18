function t(key, fallback) {
    return (window.I18N && window.I18N[key]) || fallback || key;
}

function formatMessage(template, params = {}) {
    return String(template || '').replace(/\{(\w+)\}/g, (_, key) =>
        Object.prototype.hasOwnProperty.call(params, key) ? params[key] : `{${key}}`
    );
}

function currentLanguageIsEnglish() {
    return String(window.CURRENT_LANGUAGE || document.documentElement.lang || '').toLowerCase() === 'en-us';
}

// 全局状态
const state = {
    currentArch: '',
    currentSearch: '',
    nasOnly: false,
    allData: []
};

const EXCLUDED_DEFAULT_CATEGORIES = new Set([
    'server-workstation',
    'high-performance',
    'mobile-soc',
    'legacy'
]);

const MANUAL_VISIBLE_CATEGORY_TAGS = new Set([
    'entry',
    'low-power',
    'mid-range',
    'mid-high',
    'arm-nas',
    'arm-router'
]);

const MANUAL_HIDDEN_CATEGORY_TAGS = new Set([
    'server',
    'flagship',
    'high-end',
    'mobile-flagship',
    'mobile-high'
]);

// 详情Modal函数（移动到main.js确保Swup切换后可用）
function showDetailModal() {
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeDetailModal() {
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// API 基础路径
const API_BASE = '/api';

console.log('Main.js loaded - Version 8.0 with Notes Display');

// CPU分类规则配置
const cpuCategories = {
    'nas-common': {
        label: '成品NAS常见',
        i18nKey: 'runtime.category_nas_common',
        priority: 1,
        color: '#10b981',
        patterns: [
            /^N\d{2,4}$/i,
            /^J\d{4}$/i,
            /^Atom C\d{4}$/i,
            /^RK\d{4}$/i,
            /^(Amlogic )?(A\d{3}D|S\d{3}[A-Z]\d?)$/i,
            /^RTD\d{4}[A-Z]?$/i,
        ]
    },
    'diy-low-power': {
        label: 'DIY低功耗',
        i18nKey: 'runtime.category_diy_low_power',
        priority: 2,
        color: '#3b82f6',
        patterns: [
            /i[3579]-\d{4,5}[UT]E?$/i,
            /R[357]-\d{4}U$/i,
            /Ryzen [357] \d{4}U$/i,
            /Ryzen V\d{4}B$/i,
            /奔腾\d{4}$/,
            /^G\d{4}T$/i,
        ]
    },
    'router-common': {
        label: '软路由常见',
        i18nKey: 'runtime.category_router_common',
        priority: 3,
        color: '#f59e0b',
        patterns: [
            /^MT\d{4}[A-Z]$/i,
            /^IPQ\d{4}$/i,
            /^BCM\d{4}$/i,
        ]
    },
    'domestic': {
        label: '国产芯片',
        i18nKey: 'runtime.category_domestic',
        priority: 4,
        color: '#14b8a6',
        patterns: [
            /^KX-/i,
            /^AL\d{3}$/i,
        ]
    },
    'server-workstation': {
        label: '服务器/工作站',
        i18nKey: 'runtime.category_server_workstation',
        priority: 5,
        color: '#8b5cf6',
        badge: '高功耗',
        patterns: [
            /^AMD EPYC/i,
            /^(Intel )?Xeon/i,
            /^E[35]-\d{4}/i,
        ]
    },
    'high-performance': {
        label: '高性能桌面/移动',
        i18nKey: 'runtime.category_high_performance',
        priority: 6,
        color: '#ef4444',
        badge: '非典型NAS',
        patterns: [
            /i[3579]-\d{4,5}H[KX]?$/i,
            /R[357]-\d{4}H[S]?$/i,
            /Ryzen [357] \d{4}H[S]?$/i,
            /^Ultra [579]/i,
            /^AI \d+/i,
            /i[3579]-\d{4,5}K[FS]?$/i,
        ]
    },
    'mobile-soc': {
        label: '手机/平板',
        i18nKey: 'runtime.category_mobile_soc',
        priority: 7,
        color: '#ec4899',
        badge: '非NAS设备',
        patterns: [
            /^M\d+$/i,
            /骁龙|8 Gen|8E/i,
            /^Kirin/i,
        ]
    },
    'legacy': {
        label: '旧款',
        i18nKey: 'runtime.category_legacy',
        priority: 8,
        color: '#6b7280',
        patterns: [
            /i[3579]-[1-6]\d{3}[A-Z]?$/i,
            /^G\d{3}[05]$/i,
            /^J[13]\d{3}$/i,
            /T\d{4}$/i,
            /3\d{3}U$/i,
        ]
    }
};

// 判断CPU属于哪个分类
function categorizeCPU(cpuModel) {
    for (const [key, category] of Object.entries(cpuCategories)) {
        for (const pattern of category.patterns) {
            if (pattern.test(cpuModel)) {
                return {
                    key: key,
                    label: category.label,
                    i18nKey: category.i18nKey,
                    color: category.color,
                    badge: category.badge || null,
                    priority: category.priority
                };
            }
        }
    }
    return {
        key: 'other',
        label: '其他',
        i18nKey: 'runtime.category_other',
        color: '#9ca3af',
        badge: null,
        priority: 99
    };
}

function isDefaultVisibleCPU(itemOrCpuModel) {
    const item = typeof itemOrCpuModel === 'object' && itemOrCpuModel !== null
        ? itemOrCpuModel
        : { cpu_model: itemOrCpuModel };

    const categoryTag = String(item.category_tag || '').trim().toLowerCase();
    if (categoryTag) {
        if (MANUAL_VISIBLE_CATEGORY_TAGS.has(categoryTag)) {
            return true;
        }

        if (MANUAL_HIDDEN_CATEGORY_TAGS.has(categoryTag)) {
            return false;
        }
    }

    const category = categorizeCPU(item.cpu_model || '');
    return !EXCLUDED_DEFAULT_CATEGORIES.has(category.key);
}

function normalizeSearchTerm(value) {
    return String(value || '').trim().toLowerCase();
}

function updateNasToggleUI() {
    const toggle = document.getElementById('benchmarkNasToggle');
    if (!toggle) return;

    toggle.textContent = state.nasOnly ? t('nas_only', '只看 NAS 相关') : t('all_cpu', '显示全部 CPU');
    toggle.classList.toggle('benchmark-toggle-active', state.nasOnly);
}

function setBenchmarkToolbarVisibility(visible) {
    const toolbar = document.querySelector('.benchmark-toolbar');
    if (!toolbar) return;
    toolbar.classList.toggle('benchmark-toolbar-hidden', !visible);
}

function shouldShowBenchmarkToolbar() {
    const rankingContainer = document.getElementById('rankingContainer');

    const rankingVisible = rankingContainer && window.getComputedStyle(rankingContainer).display !== 'none';

    return !rankingVisible;
}

function applyCurrentFilters() {
    let filteredData = Array.isArray(state.allData) ? [...state.allData] : [];

    if (state.currentArch) {
        if (state.currentArch === 'ARM') {
            filteredData = filteredData.filter(item =>
                item.architecture === 'ARM64' ||
                item.architecture === 'ARMv7' ||
                item.architecture === 'ARM'
            );
        } else {
            filteredData = filteredData.filter(item => item.architecture === state.currentArch);
        }
    }

    if (state.nasOnly) {
        filteredData = filteredData.filter(item => isDefaultVisibleCPU(item));
    }

    const searchTerm = normalizeSearchTerm(state.currentSearch);
    if (searchTerm) {
        filteredData = filteredData.filter(item => {
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

    displayBenchmarks(filteredData);
}

// 生成CPU分类标签HTML
function generateCPUBadge(cpuModel) {
    const category = categorizeCPU(cpuModel);
    const categoryLabel = t(category.i18nKey, category.label);
    let badgeHTML = '';

    // 典型NAS设备和"其他"分类不显示标签
    const typicalNAS = ['nas-common', 'diy-low-power', 'domestic', 'other'];

    if (!typicalNAS.includes(category.key)) {
        const labelMap = {
            'router-common': 'runtime.badge_router',
            'server-workstation': 'runtime.badge_server',
            'high-performance': 'runtime.badge_high_performance',
            'mobile-soc': 'runtime.badge_mobile',
            'legacy': 'runtime.badge_legacy'
        };

        const shortLabel = labelMap[category.key] ? t(labelMap[category.key], categoryLabel) : categoryLabel;
        const tooltipText = category.key === 'legacy'
            ? `${categoryLabel} - ${currentLanguageIsEnglish() ? 'discontinued or second-hand hardware' : '停产或二手设备'}`
            : categoryLabel;

        badgeHTML = `<span class="cpu-category-badge" style="display:inline-block;background:${category.color};color:#fff;padding:3px 8px;border-radius:3px;font-size:12px;margin-left:8px;" title="${tooltipText}">${shortLabel}</span>`;
    }

    // 警告标签
    if (category.badge) {
            const warningMap = {
                '非NAS设备': { short: 'runtime.badge_non_nas', full: 'runtime.badge_non_nas' },
                '非典型NAS': { short: 'runtime.badge_non_typical', full: 'runtime.badge_non_typical' },
                '高功耗': { short: 'runtime.badge_high_power', full: 'runtime.badge_high_power' }
            };
            const warning = warningMap[category.badge];
            const warningShort = warning ? t(warning.short, category.badge) : category.badge;
            const warningFull = warning ? t(warning.full, category.badge) : category.badge;
            badgeHTML += `<span class="cpu-warning-badge" style="display:inline-block;background:#fbbf24;color:#78350f;padding:3px 8px;border-radius:3px;font-size:12px;margin-left:6px;" title="${escapeHtml(warningFull)}">${escapeHtml(warningShort)}</span>`;
    }

    return badgeHTML;
}

// 获取排行榜数据
async function fetchBenchmarks() {
    console.log('fetchBenchmarks called');
    const tbody = document.getElementById('benchmarkBody');

    if (!tbody) {
        console.error('benchmarkBody not found!');
        return;
    }

    console.log('tbody found, fetching data...');
    // SEO SSR：服务端已渲染行时不再打加载占位，避免首屏可见闪烁
    if (tbody.getAttribute('data-ssr') !== '1') {
        tbody.innerHTML = `<tr><td colspan="2" style="text-align: center; padding: 40px; color: #666;">${escapeHtml(t('runtime.loading', '加载中...'))}</td></tr>`;
    }

    try {
        const url = `${API_BASE}/get_benchmarks.php?page=1&limit=200`;
        console.log('Fetching from:', url);

        const response = await fetch(url);
        console.log('Response status:', response.status);

        const result = await response.json();
        console.log('Data received:', result);

        if (result.success && result.data && Array.isArray(result.data)) {
            state.allData = result.data;
            console.log('Data count:', result.data.length);
            applyCurrentFilters();
        } else {
            console.error('API returned error:', result.error || result.message);
            tbody.innerHTML = `<tr><td colspan="2" style="text-align: center; padding: 40px; color: red;">${escapeHtml(formatMessage(t('runtime.loading_error', 'Loading failed: {message}'), {message: result.error || result.message || t('runtime.unknown', 'Unknown')}))}</td></tr>`;
        }
    } catch (error) {
        console.error('Fetch error:', error);
        tbody.innerHTML = `<tr><td colspan="2" style="text-align: center; padding: 40px; color: red;">${escapeHtml(formatMessage(t('runtime.network_error', '网络错误：{message}'), {message: error.message}))}</td></tr>`;
    }
}

// 显示排行榜数据（进度条样式 + 图标）
function displayBenchmarks(data) {
    console.log('displayBenchmarks called with', data.length, 'items');
    const tbody = document.getElementById('benchmarkBody');

    // 更新CPU总数显示
    const totalCPUsElement = document.getElementById('totalCPUs');
    if (totalCPUsElement) {
        if (data && data.length > 0) {
            const totalCount = Array.isArray(state.allData) ? state.allData.length : data.length;
            const filtersApplied = Boolean(state.currentArch || normalizeSearchTerm(state.currentSearch) || state.nasOnly);
            totalCPUsElement.textContent = filtersApplied
                ? formatMessage(t('runtime.filtered_count', '显示 {count} / 共 {total} 款 CPU'), {count: data.length, total: totalCount})
                : formatMessage(t('runtime.cpu_count', '共收录 {count} 款 CPU'), {count: totalCount});
        } else {
            const totalCount = Array.isArray(state.allData) ? state.allData.length : 0;
            totalCPUsElement.textContent = totalCount > 0
                ? `${t('runtime.no_results', '当前筛选无结果')} / ${formatMessage(t('runtime.cpu_count', '共收录 {count} 款 CPU'), {count: totalCount})}`
                : formatMessage(t('runtime.cpu_count', '共收录 {count} 款 CPU'), {count: 0});
        }
    }

    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="2" style="text-align: center; padding: 40px; color: #666;">${escapeHtml(t('no_data', '暂无数据'))}</td></tr>`;
        return;
    }

    // 分段比例显示：0-50000占25%宽度，50000-1500000占75%宽度
    // 适度拉长低分数段，让低分CPU显示更多内容
    const THRESHOLD = 50000;      // 分段阈值
    const MAX_SCORE = 1500000;    // 最大分数（提高到150万以容纳高性能服务器CPU）
    const LOW_RANGE_WIDTH = 25;   // 低分段占25%宽度
    const HIGH_RANGE_WIDTH = 75;  // 高分段占75%宽度

    // 计算分段比例位置
    const calculatePercentage = (score) => {
        if (score <= THRESHOLD) {
            // 0-50000 映射到 0-25%
            return (score / THRESHOLD * LOW_RANGE_WIDTH).toFixed(2);
        } else {
            // 50000-1500000 映射到 25-100%
            const percentage = LOW_RANGE_WIDTH + ((score - THRESHOLD) / (MAX_SCORE - THRESHOLD) * HIGH_RANGE_WIDTH);
            // 确保不超过100%（防止未来有更高分数时溢出）
            return Math.min(percentage, 100).toFixed(2);
        }
    };

    // 定义分割线的分数位置（更新到150万以适应高性能服务器CPU）
    const scoreMarkers = [0, 50000, 200000, 600000, 1000000, 1500000];

    // 生成分割线HTML（带数字标签 - 仅第一行）
    const generateMarkers = (withLabels = false) => {
        return scoreMarkers
            .map(score => {
                const position = calculatePercentage(score);
                if (withLabels) {
                    return `
                        <div class="score-marker" style="left: ${position}%;">
                            <span class="score-label">${score.toLocaleString()}</span>
                        </div>
                    `;
                } else {
                    return `<div class="score-marker" style="left: ${position}%;"></div>`;
                }
            })
            .join('');
    };

    console.log('Using segmented scale: 0-50000 → 0-25%, 50000-600000 → 25-100%');

    // 检测是否为移动端
    const isMobile = window.innerWidth <= 767;

    // 移动端：使用卡片式布局
    if (isMobile) {
        const mobileContainer = document.getElementById('mobileCards');
        const table = document.getElementById('mainForm');
        if (mobileContainer && table) {
            table.style.display = 'none';
            mobileContainer.style.display = 'block';

            const cards = data.map((item, index) => {
                const percentage = calculatePercentage(item.score);
                const coreInfo = localizeCoreInfo(item.core_info || '');
                const isX86 = item.architecture === 'x86_64';
                const gradientClass = isX86 ? 'blue-gradient' : 'green-gradient';
                const archLabel = isX86 ? 'x86' : 'ARM';
                const archClass = isX86 ? 'arch-x86' : 'arch-arm';
                const hasRealTest = item.has_real_test || item.id;
                // 点击事件
                const clickHandler = hasRealTest
                    ? `onclick="showRealTestModal(event, decodeURIComponent('${escapeJsArg(item.cpu_model)}'), ${item.id || 0}, '', decodeURIComponent('${escapeJsArg(coreInfo || localizedUnknown())}'), ${item.score})"`
                    : '';

                return `
                <div class="mobile-card ${hasRealTest ? 'mobile-card-clickable' : ''}" ${clickHandler}>
                    <div class="mobile-card-header">
                        <span class="mobile-card-rank">#${index + 1}</span>
                        ${cpuNameHtml(item.cpu_model, 'mobile-card-cpu')}
                        <span class="mobile-card-arch ${archClass}">${archLabel}</span>
                    </div>
                    <div class="mobile-card-bar">
                        <div class="mobile-card-bar-track">
                            <div class="ratio ${gradientClass}" style="width: ${percentage}%;">
                                <span class="mobile-card-info">${escapeHtml(coreInfo)}</span>
                            </div>
                        </div>
                        <span class="mobile-card-score">${parseInt(item.score).toLocaleString()}</span>
                    </div>
                </div>`;
            }).join('');

            mobileContainer.innerHTML = cards;
            console.log('Mobile cards displayed successfully');
            return;
        }
    }

    // 桌面端：保持原有 table 渲染
    const table = document.getElementById('mainForm');
    const mobileContainer = document.getElementById('mobileCards');
    if (table) table.style.display = '';
    if (mobileContainer) mobileContainer.style.display = 'none';

    const rows = data.map((item, index) => {
        const percentage = calculatePercentage(item.score);
        const localizedCoreInfo = localizeCoreInfo(item.core_info || '');

        // 只在第一行显示数字标签
        const markersHtml = generateMarkers(index === 0);

        // 判断架构类型
        const isX86 = item.architecture === 'x86_64';
        const gradientClass = isX86 ? 'blue-gradient' : 'green-gradient';

        // 准备图标 - 检查是否有图标需要显示
        const hasRealTest = item.has_real_test || item.id;
        const hasIcons = !isMobile && hasRealTest;

        let iconsHtml = '';

        // 只有当有图标时才创建容器
        if (hasIcons) {
            iconsHtml = '<span style="margin-left: 2px; white-space: nowrap;">';
        }

        // 桌面端：显示实测跑分图标 + NAS推荐图标
        // 桌面端：显示实测跑分图标
        {
            // 实测跑分图标 - 悬停触发动画和变红色的SVG（仅桌面端）
            if (hasRealTest) {
                const uniqueId = `speedometer-${item.id || index}`;
                // 使用core_info字段作为核心信息显示（例如：8核16线）
                const coreInfo = localizedCoreInfo || localizedUnknown();

                iconsHtml += `<span class="icon-tooltip" data-tooltip="${escapeHtml(t('runtime.test_data', '查看 CoreMark 跑分数据'))}"
                    onclick="showRealTestModal(event, decodeURIComponent('${escapeJsArg(item.cpu_model)}'), ${item.id || 0}, '', decodeURIComponent('${escapeJsArg(coreInfo)}'), ${item.score})"
                    onmouseover="var svg=this.querySelector('svg'); svg.style.transform='scale(1.2)'; svg.style.color='#dc3545'; document.getElementById('${uniqueId}').querySelector('.needle-animation').beginElement();"
                    onmouseout="var svg=this.querySelector('svg'); svg.style.transform='scale(1)'; svg.style.color='#000';"
                    style="display: inline-block; margin-right: 0px; vertical-align: middle; cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" id="${uniqueId}" class="speedometer-icon"
                    style="color: #000; transition: all 0.3s; vertical-align: middle; display: block; pointer-events: none;">
                    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                        <path stroke-dasharray="64" stroke-dashoffset="64" d="M12 3c4.97 0 9 4.03 9 9c0 4.97 -4.03 9 -9 9c-4.97 0 -9 -4.03 -9 -9c0 -4.97 4.03 -9 9 -9Z">
                            <animate fill="freeze" attributeName="stroke-dashoffset" dur="0.6s" values="64;0"/>
                        </path>
                        <path stroke-dasharray="8" stroke-dashoffset="8" d="M12 12h-5.5">
                            <animate fill="freeze" attributeName="stroke-dashoffset" begin="1.3s" dur="0.2s" values="8;0"/>
                            <animateTransform class="needle-animation" fill="freeze" attributeName="transform" begin="indefinite" dur="0.4s" type="rotate" values="0 12 12;65 12 12"/>
                        </path>
                    </g>
                    <g fill="currentColor">
                        <path fill-opacity="0" d="M12 21C9.41 21 7.15 20.79 5.94 19L12 21L18.06 19C16.85 20.79 14.59 21 12 21Z">
                            <animate fill="freeze" attributeName="d" begin="0.6s" dur="0.4s" values="M12 21C9.41 21 7.15 20.79 5.94 19L12 21L18.06 19C16.85 20.79 14.59 21 12 21Z;M12 16C9.41 16 7.15 17.21 5.94 19L12 21L18.06 19C16.85 17.21 14.59 16 12 16Z"/>
                            <set fill="freeze" attributeName="fill-opacity" begin="0.6s" to="1"/>
                        </path>
                        <circle cx="7" cy="12" r="0" transform="rotate(15 12 12)">
                            <animate fill="freeze" attributeName="r" begin="0.9s" dur="0.2s" values="0;1"/>
                        </circle>
                        <circle cx="7" cy="12" r="0" transform="rotate(65 12 12)">
                            <animate fill="freeze" attributeName="r" begin="0.95s" dur="0.2s" values="0;1"/>
                        </circle>
                        <circle cx="7" cy="12" r="0" transform="rotate(115 12 12)">
                            <animate fill="freeze" attributeName="r" begin="1s" dur="0.2s" values="0;1"/>
                        </circle>
                        <circle cx="7" cy="12" r="0" transform="rotate(165 12 12)">
                            <animate fill="freeze" attributeName="r" begin="1.05s" dur="0.2s" values="0;1"/>
                        </circle>
                        <circle cx="12" cy="12" r="0">
                            <animate fill="freeze" attributeName="r" begin="1.3s" dur="0.2s" values="0;2"/>
                        </circle>
                    </g>
                </svg></span>`;
            }
        }


        // 关闭图标容器（只有创建了容器才关闭）
        if (hasIcons) {
            iconsHtml += '</span>';
        }

        // 判断显示方式（基于实际分数）
        // 分段比例：0-50000占25%，50000+占75%
        // 显示策略：<20000分核心信息在内部，分数在外部；>=20000全部在内部
        const score = item.score;
        const isVeryShortBar = score < 20000;    // < 20000分：核心信息在内部，分数在外部（从RTD1296开始）

        let barContent;
        if (isVeryShortBar) {
            // 短进度条（< 20,000分）：核心信息在内部，分数在外部
            barContent = `
                <div class="ratio ${gradientClass}" style="width: ${percentage}%;">
                    <div class="socinfo" style="font-size: 10px;">${escapeHtml(localizedCoreInfo)}</div>
                </div>
                <div class="bar-text-outside" style="left: ${percentage}%;">
                    <span class="score-value" style="font-size: 14px; font-weight: bold;">${parseInt(item.score).toLocaleString()}</span>
                </div>
            `;
        } else {
            // 正常进度条（>= 20,000分）：文字显示在内部，根据分数调整字体大小
            const isMediumBar = score < 120000;  // 20000-120000使用更小字体
            const fontSizeClass = isMediumBar ? 'style="font-size: 11px;"' : '';
            const scoreFontSize = isMediumBar ? 'style="font-size: 12px;"' : '';

            barContent = `
                <div class="ratio ${gradientClass}" style="width: ${percentage}%;">
                    <div class="socinfo" ${fontSizeClass}>${escapeHtml(localizedCoreInfo)}</div>
                    <span class="score-text" ${scoreFontSize}>${parseInt(item.score).toLocaleString()}</span>
                </div>
            `;
        }

        return `
        <tr>
            <td class="socName">
                ${cpuNameHtml(item.cpu_model, 'cpu-name-text')}${iconsHtml}
            </td>
            <td>
                <div class="ratioBar">
                    ${markersHtml}
                    ${barContent}
                </div>
            </td>
        </tr>
        `;
    }).join('');

    tbody.innerHTML = rows;
    console.log('Benchmarks displayed successfully with icons');
}

// 显示实测跑分 Modal
async function showRealTestModal(event, cpuModel, cpuId, deviceInfo, coreInfo, score) {
    if (event) event.stopPropagation();

    const safeCpuModel = escapeHtml(cpuModel);
    const testTitle = t('runtime.test_data_title', '实测跑分数据');
    // 标题统一通过 textContent 写入，避免把 CPU 名称当作 HTML。
    document.getElementById('detailModalTitle').textContent = `${cpuModel} - ${testTitle}`;
    document.getElementById('detailModalBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> ' + escapeHtml(t('runtime.loading', '加载中...')) + '</div>';
    showDetailModal();

    try {
        const language = window.CURRENT_LANGUAGE || document.documentElement.lang || 'zh-CN';
        const response = await fetch(`/api/public/benchmark-detail.php?cpu_model=${encodeURIComponent(cpuModel)}&lang=${encodeURIComponent(language)}`);
        const result = await response.json();

        if (result.code === 0 && result.data) {
            const data = result.data;

            // 生成标签HTML（优先使用数据库标签，没有则自动生成）
            let cpuBadge = '';

            // 如果数据库有标签，使用数据库标签
            if (data.category_tag || data.warning_tag) {
                if (data.category_tag) {
                    const tagMap = {
                        'server': { label: t('runtime.server_class', '服务器级'), color: '#8b5cf6' },
                        'flagship': { label: t('runtime.flagship', '旗舰级'), color: '#ef4444' },
                        'high-end': { label: t('runtime.high_end', '高端'), color: '#f59e0b' },
                        'mid-high': { label: t('runtime.upper_mid_range', '中高端'), color: '#10b981' },
                        'mid-range': { label: t('runtime.mid_range', '中端'), color: '#3b82f6' },
                        'entry': { label: t('runtime.entry_level', '入门级'), color: '#6b7280' },
                        'low-power': { label: t('runtime.low_power', '低功耗'), color: '#14b8a6' },
                        'arm-nas': { label: t('runtime.arm_nas', 'ARM NAS'), color: '#8b5cf6' },
                        'arm-router': { label: t('runtime.arm_router', 'ARM路由器'), color: '#f59e0b' },
                        'mobile-flagship': { label: t('runtime.mobile_flagship', '移动旗舰'), color: '#ec4899' },
                        'mobile-high': { label: t('runtime.mobile_high', '移动高端'), color: '#ec4899' }
                    };
                    const tag = tagMap[data.category_tag];
                    if (tag) {
                        cpuBadge += `<span style="display:inline-block;background:${tag.color};color:#fff;padding:3px 8px;border-radius:3px;font-size:12px;margin-left:8px;">${tag.label}</span>`;
                    }
                }

                if (data.warning_tag) {
                    const warningMap = {
                        'high-power': { label: t('runtime.badge_high_power', '高功耗'), color: '#fbbf24' },
                        'old-platform': { label: t('runtime.old_platform', '老旧平台'), color: '#9ca3af' },
                        'es-version': { label: t('runtime.es_version', 'ES版本'), color: '#f59e0b' },
                        'overkill': { label: t('runtime.overkill', '性能过剩'), color: '#10b981' }
                    };
                    const warning = warningMap[data.warning_tag];
                    if (warning) {
                        cpuBadge += `<span style="display:inline-block;background:${warning.color};color:#78350f;padding:3px 8px;border-radius:3px;font-size:12px;margin-left:6px;">${warning.label}</span>`;
                    }
                }
            } else {
                // 数据库没有标签，使用自动生成
                cpuBadge = generateCPUBadge(cpuModel);
            }

            // 更新标题（不添加标签，因为card-title旁边已经有标签了）
            // Keep the runtime title in the selected language after the API response.
            document.getElementById('detailModalTitle').textContent = `${cpuModel} - ${testTitle}`;
            // 标题统一通过 textContent 写入，避免把 CPU 名称当作 HTML。
            // The legacy SVG title assignment above is kept for icon compatibility;
            // restore the translated text after the API response so it cannot
            // overwrite the selected language.
            document.getElementById('detailModalTitle').textContent = `${cpuModel} - ${testTitle}`;

            // 优先使用 test_device 字段，其次是组合的 device_info
            const testDevice = data.test_device || data.device_info || t('runtime.unknown_device', '未知设备');
            const cores = localizeCoreInfo(data.core_info || data.cores || t('runtime.unknown', '未知'));
            const submitTime = data.submitted_at || data.created_at || '';
            const safeTestDevice = escapeHtml(testDevice);
            const safeCores = escapeHtml(cores);
            const safeSubmitTime = escapeHtml(submitTime);

            let html = `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title text-primary">${safeCpuModel}${cpuBadge}</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>${t('runtime.test_device', '测试设备')}:</strong> <span class="text-success">${safeTestDevice}</span></p>
                                <p class="mb-2"><strong>${t('runtime.cores_threads', '核心 / 线程')}:</strong> ${safeCores}</p>
                                ${submitTime ? `<p class="mb-2"><strong>${t('runtime.submitted_at', '提交时间')}:</strong> ${safeSubmitTime}</p>` : ''}
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>${t('runtime.benchmark_score', 'CoreMark 跑分')}:</strong> <span class="text-danger" style="font-size: 1.5em; font-weight: bold;">${parseInt(data.score).toLocaleString()}</span></p>
                                ${data.tdp || (data.tdp && data.score) ? `
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;">
                                    ${data.tdp ? `
                                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: ${
                                        parseInt(data.tdp) <= 15 ? '#ecfdf5' :
                                        parseInt(data.tdp) <= 45 ? '#fef3c7' :
                                        parseInt(data.tdp) <= 95 ? '#fed7aa' : '#fee2e2'
                                    }; border-radius: 6px; border: 1px solid ${
                                        parseInt(data.tdp) <= 15 ? '#10b981' :
                                        parseInt(data.tdp) <= 45 ? '#f59e0b' :
                                        parseInt(data.tdp) <= 95 ? '#f97316' : '#ef4444'
                                    };">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="${
                                            parseInt(data.tdp) <= 15 ? '#059669' :
                                            parseInt(data.tdp) <= 45 ? '#d97706' :
                                            parseInt(data.tdp) <= 95 ? '#ea580c' : '#dc2626'
                                        }" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                        </svg>
                                        <span style="font-size: 12px; color: #64748b;">${t('runtime.tdp_power', 'TDP / 功耗')}</span>
                                        <span style="font-size: 14px; font-weight: 600; color: ${
                                            parseInt(data.tdp) <= 15 ? '#065f46' :
                                            parseInt(data.tdp) <= 45 ? '#92400e' :
                                            parseInt(data.tdp) <= 95 ? '#9a3412' : '#991b1b'
                                        };">${data.tdp}</span>
                                    </div>
                                    ` : ''}
                                    ${data.tdp && data.score ? `
                                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #eff6ff; border-radius: 6px; border: 1px solid #3b82f6;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                        </svg>
                                        <span style="font-size: 12px; color: #64748b;">${t('runtime.efficiency', '能效比')}</span>
                                        <span style="font-size: 14px; font-weight: 600; color: #1e40af;">${Math.round(parseInt(data.score) / parseInt(data.tdp)).toLocaleString()} 分/W</span>
                                    </div>
                                    ` : ''}
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;


            html += `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> ${t('runtime.data_source_note', 'Data comes from real-world tests and is provided for reference only.')}
                </div>
            `;

            document.getElementById('detailModalBody').innerHTML = html;
        } else {
            // API失败时使用基本信息fallback
            deviceInfo = deviceInfo || t('runtime.unknown_device', '未知设备');
            coreInfo = localizeCoreInfo(coreInfo || t('runtime.unknown', '未知'));
            const safeDeviceInfo = escapeHtml(deviceInfo);
            const safeCoreInfo = escapeHtml(coreInfo);
            const safeFallbackCpu = escapeHtml(cpuModel);

            let html = `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title text-primary">${safeFallbackCpu}</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>${t('runtime.test_device', '测试设备')}:</strong> <span class="text-success">${safeDeviceInfo}</span></p>
                                <p class="mb-2"><strong>${t('runtime.cores_threads', '核心 / 线程')}:</strong> ${safeCoreInfo}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>${t('runtime.benchmark_score', 'CoreMark score')}:</strong> <span class="text-danger" style="font-size: 1.5em; font-weight: bold;">${Number(score || 0).toLocaleString()}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> ${t('runtime.data_source_note', 'Data comes from real-world tests and is provided for reference only.')}
                </div>
            `;
            document.getElementById('detailModalBody').innerHTML = html;
        }
    } catch (error) {
        console.error('加载详细数据失败:', error);
        // 出错时使用基本信息fallback
        deviceInfo = deviceInfo || t('runtime.unknown_device', '未知设备');
        coreInfo = localizeCoreInfo(coreInfo || t('runtime.unknown', '未知'));
        const safeDeviceInfo = escapeHtml(deviceInfo);
        const safeCoreInfo = escapeHtml(coreInfo);
        const safeFallbackCpu = escapeHtml(cpuModel);

        let html = `
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title text-primary">${safeFallbackCpu}</h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>${t('runtime.test_device', '测试设备')}:</strong> <span class="text-success">${safeDeviceInfo}</span></p>
                            <p class="mb-2"><strong>${t('runtime.cores_threads', '核心 / 线程')}:</strong> ${safeCoreInfo}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>${t('runtime.benchmark_score', 'CoreMark score')}:</strong> <span class="text-danger" style="font-size: 1.5em; font-weight: bold;">${Number(score || 0).toLocaleString()}</span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> ${t('runtime.data_source_note', 'Data comes from real-world tests and is provided for reference only.')}
            </div>
        `;
        document.getElementById('detailModalBody').innerHTML = html;
    }
}

// XSS 防护
// SEO 阶段三：白名单 CPU 名称链接到 /cpu/{slug} 实体页（与 SSR 渲染一致）
function cpuPageLink(model) {
    const slug = (window.CPU_PAGE_SLUGS || {})[model];
    return slug ? `/cpu/${slug}${currentLanguageIsEnglish() ? '?lang=en-US' : ''}` : null;
}

function cpuNameHtml(model, cssClass) {
    const href = cpuPageLink(model);
    const name = escapeHtml(model);
    return href ? `<a class="${cssClass}" href="${href}">${name}</a>` : `<span class="${cssClass}">${name}</span>`;
}

function localizedUnknown() {
    return String(window.CURRENT_LANGUAGE || document.documentElement.lang || '').toLowerCase() === 'zh-cn'
        ? '未知'
        : 'Unknown';
}

function localizeCoreInfo(value) {
    let text = String(value || '').trim();
    const language = String(window.CURRENT_LANGUAGE || document.documentElement.lang || '').toLowerCase();
    if (!text || language === 'zh-cn') return text;

    const numberWords = {
        '十六': '16', '十四': '14', '十二': '12', '十': '10',
        '八': '8', '六': '6', '四': '4', '三': '3',
        '双': '2', '二': '2', '一': '1'
    };
    Object.entries(numberWords).forEach(([source, replacement]) => {
        text = text.split(source).join(replacement);
    });

    const unit = (count, singular, plural) => String(count) === '1' ? singular : plural;
    // Normalize legacy mixed notation such as "16C/32T核" before handling
    // fully Chinese core/thread descriptions.
    text = text.replace(/(\d+)\s*C\s*\/\s*(\d+)\s*T核(?:\s*\d+\s*(?:线程|線程|线|線))?/giu,
        (_, cores, threads) => `${cores} ${unit(cores, 'core', 'cores')} / ${threads} ${unit(threads, 'thread', 'threads')}`);
    text = text.replace(/(\d+)\s*(?:核心|核)\s*(\d+)\s*(?:线程|線程|线|線)/gu,
        (_, cores, threads) => `${cores} ${unit(cores, 'core', 'cores')} / ${threads} ${unit(threads, 'thread', 'threads')}`);
    text = text.replace(/(\d+)\s*(?:核心|核)\s*(\d+)(?=\s*\()/gu,
        (_, cores, threads) => `${cores} ${unit(cores, 'core', 'cores')} / ${threads} ${unit(threads, 'thread', 'threads')}`);
    text = text.replace(/(\d+)\s*核架构/gu, '$1-core architecture');

    const typedCorePatterns = [
        ['性能核', 'performance'],
        ['大核', 'performance'],
        ['能效核', 'efficiency'],
        ['小核', 'efficiency']
    ];
    typedCorePatterns.forEach(([source, label]) => {
        text = text.replace(new RegExp(`(\\d+(?:\\s*\\+\\s*\\d+)*)\\s*${source}`, 'gu'),
            (_, count) => `${count} ${label} ${unit(count, 'core', 'cores')}`);
    });

    text = text.replace(/(\d+(?:\s*\+\s*\d+)*)\s*(?:核心|核)/gu,
        (_, count) => `${count} ${unit(count, 'core', 'cores')}`);
    text = text.replace(/(\d+)\s*(?:线程|線程|线|線)/gu,
        (_, count) => `${count} ${unit(count, 'thread', 'threads')}`);
    text = text.replace(/核架构/gu, 'core architecture').replace(/架构/gu, 'architecture');
    text = text.replace(/(cores?|threads?)(?=[A-Z])/g, '$1 ');
    return text.replace(/\s*\+\s*/g, ' + ').replace(/\s{2,}/g, ' ').trim();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJsArg(value) {
    return encodeURIComponent(String(value == null ? '' : value));
}

// 架构筛选
function filterArch(arch) {
    console.log('filterArch called:', arch);
    state.currentArch = arch;
    setBenchmarkToolbarVisibility(true);

    // 隐藏天梯榜和推荐NAS，显示表格
    console.log('Checking hideRanking:', typeof hideRanking);
    if (typeof hideRanking === 'function') {
        console.log('Calling hideRanking...');
        hideRanking();
    } else {
        console.log('hideRanking is not a function!');
    }

    if (typeof hideRecommended === 'function') {
        hideRecommended();
    }

    // 更新按钮状态
    document.querySelectorAll('.downBtn').forEach(btn => {
        btn.classList.remove('allcpu');
    });

    // 重置天梯榜和推荐NAS按钮样式为红色边框（未选中状态）
    const btn4 = document.getElementById('btn4');
    if (btn4) {
        btn4.style.background = 'transparent';
        btn4.style.color = '#dc2626';
        btn4.style.border = '2px solid #dc2626';
    }
    const btn5 = document.getElementById('btn5');
    if (btn5) {
        btn5.style.background = 'transparent';
        btn5.style.color = '#dc2626';
        btn5.style.border = '2px solid #dc2626';
    }

    if (arch === '') {
        document.getElementById('btn1').classList.add('allcpu');
    } else if (arch === 'ARM') {
        document.getElementById('btn2').classList.add('allcpu');
    } else if (arch === 'x86_64') {
        document.getElementById('btn3').classList.add('allcpu');
    }

    applyCurrentFilters();
}

function toggleNasOnly() {
    state.nasOnly = !state.nasOnly;
    updateNasToggleUI();

    if (typeof hideRanking === 'function') hideRanking();
    if (typeof hideRecommended === 'function') hideRecommended();

    applyCurrentFilters();
}

function initBenchmarkToolbar() {
    const searchInput = document.getElementById('benchmarkSearchInput');
    if (!searchInput || searchInput.dataset.bound === '1') {
        updateNasToggleUI();
        setBenchmarkToolbarVisibility(shouldShowBenchmarkToolbar());
        return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const initialQuery = urlParams.get('q') || '';
    if (initialQuery && !state.currentSearch) {
        state.currentSearch = initialQuery;
    }

    searchInput.value = state.currentSearch;
    searchInput.dataset.bound = '1';

    searchInput.addEventListener('input', function () {
        state.currentSearch = this.value;

        if (typeof hideRanking === 'function') hideRanking();
        if (typeof hideRecommended === 'function') hideRecommended();

        applyCurrentFilters();
    });

    updateNasToggleUI();
    setBenchmarkToolbarVisibility(shouldShowBenchmarkToolbar());
}

// 复制命令
function copyCommand(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            alert(t('runtime.copy_success', '已复制到剪贴板！'));
        }).catch(err => {
            console.error('复制失败:', err);
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

// 备用复制方法
function fallbackCopy(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        alert(t('runtime.copy_success', '已复制到剪贴板！'));
    } catch (err) {
        console.error('复制失败:', err);
        alert(t('runtime.copy_failed', '复制失败，请手动复制'));
    }
    document.body.removeChild(textArea);
}

// 页面加载完成后初始化 - 优化版
console.log('Document readyState:', document.readyState);

// 初始化函数
function initApp() {
    console.log('initApp called, readyState:', document.readyState);

    // 检查必要的 DOM 元素是否存在
    const tbody = document.getElementById('benchmarkBody');

    if (!tbody) {
        console.error('❌ benchmarkBody not found! Retrying in 200ms...');
        // 如果元素不存在，延迟重试（最多重试5次）
        if (!window.initAppRetryCount) {
            window.initAppRetryCount = 0;
        }
        window.initAppRetryCount++;

        if (window.initAppRetryCount < 5) {
            setTimeout(initApp, 200);
        } else {
            console.error('❌ benchmarkBody not found after 5 retries, giving up');
            // 即使失败也要触发事件，让其他初始化继续
            window.dispatchEvent(new CustomEvent('mainjs:loaded'));
        }
        return;
    }

    console.log('✅ benchmarkBody found, initializing...');
    // 重置重试计数
    window.initAppRetryCount = 0;

    // 使用 setTimeout 确保 DOM 完全渲染
    setTimeout(function() {
        initBenchmarkToolbar();
        fetchBenchmarks();

        // 触发自定义事件，通知 swup-init.js main.js 已加载完成
        window.dispatchEvent(new CustomEvent('mainjs:loaded'));
    }, 0);
}

// 多种方式确保正确初始化
if (document.readyState === 'loading') {
    // DOM 还在加载中
    console.log('Waiting for DOMContentLoaded...');
    document.addEventListener('DOMContentLoaded', initApp);
} else if (document.readyState === 'interactive' || document.readyState === 'complete') {
    // DOM 已经加载完成
    console.log('DOM already loaded, initializing...');
    initApp();
} else {
    // 降级方案
    console.log('Using fallback initialization');
    window.addEventListener('load', initApp);
}

// 窗口大小变化时，跨越移动/桌面阈值自动重新渲染
(function() {
    let wasMobile = window.innerWidth <= 767;
    let resizeTimer;
    window.addEventListener('resize', function() {
        const isMobile = window.innerWidth <= 767;
        if (isMobile !== wasMobile) {
            // 立刻隐藏当前内容，避免错乱
            const table = document.getElementById('mainForm');
            const cards = document.getElementById('mobileCards');
            if (table) table.style.display = 'none';
            if (cards) cards.style.display = 'none';

            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                wasMobile = isMobile;
                if (state.allData && state.allData.length > 0) {
                    applyCurrentFilters();
                }
            }, 100);
        }
    });
})();
