<?php
require_once __DIR__ . '/../common.php';
requireLogin();

$db = Database::getInstance();
$pageTitle = '后台首页';

// 获取统计数据
$totalEvents = $db->fetch("SELECT COUNT(*) as count FROM events")['count'];
$totalCategories = $db->fetch("SELECT COUNT(*) as count FROM categories WHERE parent_id = 0")['count'];
$activeEvents = $db->fetch("SELECT COUNT(*) as count FROM events WHERE end_time > NOW()")['count'];
$expiredEvents = $totalEvents - $activeEvents;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - 活动管理系统</title>
    <link rel="stylesheet" href="/assets/admin.css">
    <link rel="stylesheet" href="/assets/dialog.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'layout/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>系统概览</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #667eea;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M3 9h18M9 21V9"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalEvents ?></div>
                        <div class="stat-label">总活动数</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #f093fb;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalCategories ?></div>
                        <div class="stat-label">分类数量</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #4facfe;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $activeEvents ?></div>
                        <div class="stat-label">进行中活动</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #fa709a;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $expiredEvents ?></div>
                        <div class="stat-label">已过期活动</div>
                    </div>
                </div>
            </div>

            <div class="welcome-card">
                <h2>欢迎使用活动管理系统</h2>
                <p>您可以通过左侧菜单管理网站设置、分类和活动</p>
            </div>
        </main>
    </div>
</body>
</html>
