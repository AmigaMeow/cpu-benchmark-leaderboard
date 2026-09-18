-- CPU Benchmark Leaderboard — 开源版数据库结构
--
-- 说明：benchmark_submissions 已做隐私脱敏——
--   不含 submitter_name / submitter_email / submitter_ip / user_id /
--   screenshot_url / screenshot_sha256 / reviewed_by / reject_reason / notes 等字段。
-- 所有数据均为示例数据，仅用于演示。

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `cpus`;
CREATE TABLE `cpus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(100) NOT NULL COMMENT 'CPU型号',
  `architecture` enum('x86_64','ARM64','ARMv7','Other') NOT NULL COMMENT '架构',
  `cores` varchar(50) DEFAULT NULL COMMENT '核心数描述',
  `threads` int(11) DEFAULT NULL COMMENT '线程数',
  `base_frequency` varchar(20) DEFAULT NULL COMMENT '基础频率',
  `max_frequency` varchar(20) DEFAULT NULL COMMENT '最高频率',
  `process` varchar(20) DEFAULT NULL COMMENT '制程工艺',
  `tdp` varchar(20) DEFAULT NULL COMMENT 'TDP功耗',
  `release_date` date DEFAULT NULL COMMENT '发布日期',
  `coremark_score` int(11) DEFAULT NULL COMMENT 'CoreMark分数',
  `has_real_test` tinyint(1) DEFAULT '0' COMMENT '是否有实测数据',
  `real_test_id` int(11) DEFAULT NULL COMMENT '关联实测跑分ID',
  `description` longtext COMMENT 'CPU详细介绍(Markdown)',
  `highlights` text COMMENT '产品亮点(JSON数组)',
  `use_cases` text COMMENT '适用场景',
  `chart_data` json DEFAULT NULL COMMENT '图表数据(JSON)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:1显示 0隐藏',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序权重',
  `tags` json DEFAULT NULL COMMENT '标签(JSON数组)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `model` (`model`),
  KEY `architecture` (`architecture`),
  KEY `coremark_score` (`coremark_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CPU信息表';

DROP TABLE IF EXISTS `benchmark_submissions`;
CREATE TABLE `benchmark_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpu_model` varchar(100) NOT NULL COMMENT 'CPU型号',
  `device_brand` varchar(100) DEFAULT NULL COMMENT '设备品牌',
  `device_model` varchar(100) DEFAULT NULL COMMENT '设备型号',
  `test_device` varchar(255) DEFAULT NULL COMMENT '测试设备描述（用户自报，可选）',
  `architecture` varchar(20) DEFAULT NULL COMMENT '架构',
  `cores` int(11) DEFAULT NULL COMMENT '核心数',
  `score` decimal(10,2) NOT NULL COMMENT 'CoreMark分数',
  `os_info` varchar(255) DEFAULT NULL COMMENT '操作系统信息',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT '审核状态',
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cpu_model` (`cpu_model`),
  KEY `status` (`status`),
  KEY `score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='跑分提交表（隐私脱敏版）';

SET FOREIGN_KEY_CHECKS = 1;
