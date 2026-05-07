<?php
// 检查是否已安装
$installedLockFile = __DIR__ . '/config/installed.lock';
if (!file_exists($installedLockFile)) {
    // 如果是API请求，返回JSON
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'System not installed']);
        exit;
    }
    // 否则跳转到安装页面
    if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
        header('Location: /install.php');
        exit;
    }
}

require_once __DIR__ . '/core/Database.php';

function initDatabase() {
    $db = Database::getInstance()->getConnection();
    
    // 检查是否已经初始化
    try {
        $result = $db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'domo' AND table_name = 'site_settings'");
        $row = $result->fetch();
        if ($row['count'] > 0) {
            return true; // 已经初始化
        }
    } catch (PDOException $e) {
        // 表不存在，继续初始化
    }

    // 创建网站设置表
    $db->exec("CREATE TABLE IF NOT EXISTS `site_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `site_name` varchar(255) NOT NULL DEFAULT '活动管理系统',
        `site_description` text,
        `site_keywords` varchar(500),
        `site_logo` varchar(255),
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 插入默认网站设置（仅在空表时插入）
    $db->exec("INSERT INTO `site_settings` (`site_name`, `site_description`, `site_keywords`) 
               SELECT '活动管理系统', '一个现代化的活动创建与管理系统', '活动,管理,系统'
               FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `site_settings`)");

    // 创建分类表
    $db->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `parent_id` int(11) DEFAULT 0,
        `sort_order` int(11) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `parent_id` (`parent_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 创建活动表
    $db->exec("CREATE TABLE IF NOT EXISTS `events` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `category_id` int(11) NOT NULL,
        `sub_category_id` int(11) DEFAULT NULL,
        `name` varchar(255) NOT NULL,
        `description` text,
        `poster` varchar(255),
        `button_type` enum('link','copy') NOT NULL,
        `button_name` varchar(100),
        `button_link` varchar(500),
        `copy_content` text,
        `copy_success_message` varchar(255),
        `start_time` datetime NOT NULL,
        `end_time` datetime NOT NULL,
        `sort_order` int(11) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `category_id` (`category_id`),
        KEY `sub_category_id` (`sub_category_id`),
        KEY `sort_order` (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 创建管理员表
    $db->exec("CREATE TABLE IF NOT EXISTS `admins` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 插入默认管理员账号 (admin/admin123)，已存在则跳过
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $db->exec("INSERT IGNORE INTO `admins` (`username`, `password`) VALUES ('admin', '{$password}')");

    return true;
}

// 初始化数据库
initDatabase();
