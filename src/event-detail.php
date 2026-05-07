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

// 获取活动ID
$eventId = intval($_GET['id'] ?? 0);

// 获取活动详情
$event = $db->fetch("
    SELECT e.*, 
           c1.name as category_name,
           c2.name as subcategory_name
    FROM events e
    LEFT JOIN categories c1 ON e.category_id = c1.id
    LEFT JOIN categories c2 ON e.subcategory_id = c2.id
    WHERE e.id = ?
", [$eventId]);

if (!$event) {
    header('Location: /index.php');
    exit;
}

// 获取网站设置
$settings = $db->fetch("SELECT * FROM site_settings ORDER BY id LIMIT 1");

$isExpired = isEventExpired($event['end_time']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> - <?= htmlspecialchars($settings['site_name'] ?? '活动管理系统') ?></title>
    <link rel="stylesheet" href="/assets/frontend.css">
    <link rel="stylesheet" href="/assets/dialog.css">
    <style>
        .detail-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }

        .detail-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .detail-header {
            position: relative;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 40px;
            color: white;
        }

        .detail-header.expired {
            background: linear-gradient(135deg, #718096, #4a5568);
        }

        .expired-badge-large {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--danger-color);
            color: white;
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4);
        }

        .detail-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .detail-category {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .detail-category-tag {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .detail-time {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            opacity: 0.9;
        }

        .detail-poster {
            width: 100%;
            max-height: 500px;
            overflow: hidden;
            background: var(--bg-light);
        }

        .detail-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .detail-poster.expired img {
            filter: grayscale(100%);
        }

        .detail-content {
            padding: 40px;
        }

        .detail-description {
            font-size: 16px;
            line-height: 1.8;
            color: var(--text-color);
            margin-bottom: 30px;
            white-space: pre-wrap;
        }

        .detail-action {
            display: flex;
            justify-content: center;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
            position: relative;
            z-index: 10;
        }

        .detail-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            position: relative;
            z-index: 11;
            pointer-events: auto;
        }

        .detail-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }

        .detail-button:active {
            transform: translateY(-1px);
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 16px 24px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.3s;
            z-index: 1000;
            font-weight: 600;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        @media (max-width: 768px) {
            .detail-title {
                font-size: 24px;
            }

            .detail-content {
                padding: 30px 20px;
            }

            .detail-header {
                padding: 30px 20px;
            }

            .detail-button {
                font-size: 16px;
                padding: 14px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="detail-container">
        <a href="/index.php" class="back-link">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
            返回首页
        </a>

        <div class="detail-card">
            <div class="detail-header <?= $isExpired ? 'expired' : '' ?>">
                <?php if ($isExpired): ?>
                    <div class="expired-badge-large">已过期</div>
                <?php endif; ?>

                <h1 class="detail-title"><?= htmlspecialchars($event['title']) ?></h1>

                <div class="detail-category">
                    <span class="detail-category-tag"><?= htmlspecialchars($event['category_name']) ?></span>
                    <?php if (!empty($event['subcategory_name'])): ?>
                        <span class="detail-category-tag"><?= htmlspecialchars($event['subcategory_name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="detail-time">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="10" cy="10" r="9"/>
                        <path d="M10 5v5l3 3"/>
                    </svg>
                    <span>
                        <?= formatDateTime($event['start_time']) ?> - <?= formatDateTime($event['end_time']) ?>
                    </span>
                </div>
            </div>

            <?php if (!empty($event['poster'])): ?>
                <div class="detail-poster <?= $isExpired ? 'expired' : '' ?>">
                    <img src="<?= htmlspecialchars($event['poster']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                </div>
            <?php endif; ?>

            <div class="detail-content">
                <?php if (!empty($event['description'])): ?>
                    <div class="detail-description">
                        <?= nl2br(htmlspecialchars($event['description'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($event['button_name'])): ?>
                    <div class="detail-action">
                        <?php if ($event['button_type'] === 'link' && !empty($event['button_link'])): ?>
                            <a href="<?= htmlspecialchars($event['button_link']) ?>" target="_blank" class="detail-button">
                                <?= htmlspecialchars($event['button_name']) ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </a>
                        <?php elseif ($event['button_type'] === 'copy' && !empty($event['copy_content'])): ?>
                            <button 
                                class="detail-button" 
                                onclick="copyToClipboard('<?= htmlspecialchars(addslashes($event['copy_content'])) ?>', '<?= htmlspecialchars(addslashes($event['copy_success_message'] ?? '复制成功')) ?>')"
                            >
                                <?= htmlspecialchars($event['button_name']) ?>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        function copyToClipboard(text, message) {
            navigator.clipboard.writeText(text).then(() => {
                toast.success(message);
            }).catch(() => {
                toast.error('复制失败，请手动复制');
            });
        }
    </script>
    <script src="/assets/dialog.js"></script>
</body>
</html>
