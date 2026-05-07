<?php
require_once __DIR__ . '/init.php';

// 辅助函数
function isEventExpired($endTime) {
    return strtotime($endTime) < time();
}

function formatDateTime($datetime) {
    return date('Y-m-d H:i', strtotime($datetime));
}

$db = Database::getInstance();

// 获取网站设置
$settings = $db->fetch("SELECT * FROM site_settings ORDER BY id LIMIT 1");

// 获取分类过滤
$categoryId = intval($_GET['category'] ?? 0);
$subCategoryId = intval($_GET['subcategory'] ?? 0);

// 获取所有一级分类
$categories = $db->fetchAll("SELECT * FROM categories WHERE parent_id = 0 ORDER BY sort_order, id");

// 获取活动列表
$sql = "SELECT e.*, c1.name as category_name, c2.name as subcategory_name 
        FROM events e 
        LEFT JOIN categories c1 ON e.category_id = c1.id 
        LEFT JOIN categories c2 ON e.subcategory_id = c2.id 
        WHERE 1=1";

$params = [];

if ($categoryId > 0) {
    $sql .= " AND e.category_id = ?";
    $params[] = $categoryId;
}

if ($subCategoryId > 0) {
    $sql .= " AND e.subcategory_id = ?";
    $params[] = $subCategoryId;
}

$sql .= " ORDER BY e.sort_order, e.id DESC";

$events = $db->fetchAll($sql, $params);

// 如果选择了分类，获取其子分类
$subCategories = [];
if ($categoryId > 0) {
    $subCategories = $db->fetchAll("SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order, id", [$categoryId]);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_name']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($settings['site_description']) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($settings['site_keywords']) ?>">
    <link rel="stylesheet" href="/assets/frontend.css">
    <link rel="stylesheet" href="/assets/dialog.css">
</head>
<body>
    <!-- 头部 -->
    <header class="site-header">
        <div class="container">
            <h1 class="site-title"><?= htmlspecialchars($settings['site_name']) ?></h1>
            <p class="site-description"><?= htmlspecialchars($settings['site_description']) ?></p>
        </div>
    </header>

    <!-- 分类导航 -->
    <nav class="category-nav">
        <div class="container">
            <div class="category-pills">
                <a href="/" class="category-pill <?= $categoryId == 0 ? 'active' : '' ?>">
                    全部
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?category=<?= $cat['id'] ?>" class="category-pill <?= $categoryId == $cat['id'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($subCategories)): ?>
                <div class="sub-category-pills">
                    <a href="?category=<?= $categoryId ?>" class="sub-category-pill <?= $subCategoryId == 0 ? 'active' : '' ?>">
                        全部
                    </a>
                    <?php foreach ($subCategories as $sub): ?>
                        <a href="?category=<?= $categoryId ?>&subcategory=<?= $sub['id'] ?>" class="sub-category-pill <?= $subCategoryId == $sub['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($sub['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- 活动列表 -->
    <main class="main-content">
        <div class="container">
            <?php if (empty($events)): ?>
                <div class="empty-state">
                    <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    <h3>暂无活动</h3>
                    <p>该分类下暂时没有活动</p>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                        <?php
                        $isExpired = isEventExpired($event['end_time']);
                        ?>
                        <div class="event-card <?= $isExpired ? 'expired' : '' ?>" onclick="window.location.href='/event-detail.php?id=<?= $event['id'] ?>'">
                            <?php if ($isExpired): ?>
                                <div class="expired-badge">已过期</div>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['poster'])): ?>
                                <div class="event-poster">
                                    <img src="<?= htmlspecialchars($event['poster']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="event-content">
                                <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                                
                                <div class="event-category">
                                    <span class="category-tag"><?= htmlspecialchars($event['category_name']) ?></span>
                                    <?php if (!empty($event['subcategory_name'])): ?>
                                        <span class="category-tag sub"><?= htmlspecialchars($event['subcategory_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($event['description'])): ?>
                                    <p class="event-description"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                                <?php endif; ?>
                                
                                <div class="event-time">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="8" cy="8" r="7"/>
                                        <path d="M8 4v4l3 2"/>
                                    </svg>
                                    <span>
                                        <?= formatDateTime($event['start_time']) ?> - <?= formatDateTime($event['end_time']) ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($event['button_name'])): ?>
                                    <div class="event-action">
                                        <?php if ($event['button_type'] === 'link' && !empty($event['button_link'])): ?>
                                            <a href="<?= htmlspecialchars($event['button_link']) ?>" target="_blank" class="event-button" onclick="event.stopPropagation();">
                                                <?= htmlspecialchars($event['button_name']) ?>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                                </svg>
                                            </a>
                                        <?php elseif ($event['button_type'] === 'copy' && !empty($event['copy_content'])): ?>
                                            <button 
                                                class="event-button copy-button" 
                                                onclick="event.stopPropagation(); copyToClipboard('<?= htmlspecialchars(addslashes($event['copy_content'])) ?>', '<?= htmlspecialchars(addslashes($event['copy_success_message'] ?? '复制成功')) ?>');"
                                            >
                                                <?= htmlspecialchars($event['button_name']) ?>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- 底部 -->
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($settings['site_name']) ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Toast 提示 -->
    <div id="toast" class="toast"></div>

    <script>
        // 复制到剪贴板
        function copyToClipboard(text, message) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast(message || '复制成功');
                }).catch(function(err) {
                    console.error('复制失败:', err);
                    showToast('复制失败，请手动复制');
                });
            } else {
                // 降级方案
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    showToast(message || '复制成功');
                } catch (err) {
                    showToast('复制失败，请手动复制');
                }
                document.body.removeChild(textarea);
            }
        }

        // 显示提示
        function showToast(message) {
            toast.success(message);
        }
    </script>
    <script src="/assets/dialog.js"></script>
</body>
</html>
