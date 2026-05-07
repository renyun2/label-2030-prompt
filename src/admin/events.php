<?php
require_once __DIR__ . '/../common.php';
requireLogin();

$db = Database::getInstance();
$pageTitle = '活动列表';

$success = '';
$error = '';

// 处理删除
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $event = $db->fetch("SELECT poster FROM events WHERE id = ?", [$id]);
    
    if ($event) {
        // 删除海报文件
        if (!empty($event['poster'])) {
            $posterFile = __DIR__ . '/../' . $event['poster'];
            if (file_exists($posterFile)) {
                unlink($posterFile);
            }
        }
        
        $db->execute("DELETE FROM events WHERE id = ?", [$id]);
        $success = '活动已删除';
    }
}

// 处理排序更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    $orders = json_decode($_POST['orders'], true);
    foreach ($orders as $order) {
        $db->execute("UPDATE events SET sort_order = ? WHERE id = ?", [$order['order'], $order['id']]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// 获取所有活动
$events = $db->fetchAll("
    SELECT e.*, 
           c1.name as category_name,
           c2.name as subcategory_name
    FROM events e
    LEFT JOIN categories c1 ON e.category_id = c1.id
    LEFT JOIN categories c2 ON e.subcategory_id = c2.id
    ORDER BY e.sort_order, e.id DESC
");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - 活动管理系统</title>
    <link rel="stylesheet" href="/assets/admin.css">
    <link rel="stylesheet" href="/assets/dialog.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body>
    <?php include 'layout/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'layout/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>活动列表</h1>
                <a href="/admin/event-create.php" class="btn btn-primary">创建活动</a>
            </div>

            <div class="content-card">
                <?php if (empty($events)): ?>
                    <div class="empty-state">
                        <p>暂无活动</p>
                        <a href="/admin/event-create.php" class="btn btn-primary" style="margin-top: 20px;">创建第一个活动</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" style="margin-bottom: 20px;">
                        💡 提示：可以拖拽活动行来调整排序
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th width="50">排序</th>
                                    <th width="80">海报</th>
                                    <th>活动名称</th>
                                    <th>分类</th>
                                    <th>活动时间</th>
                                    <th>状态</th>
                                    <th width="180">操作</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-tbody">
                                <?php foreach ($events as $event): ?>
                                    <tr class="sortable-item" data-id="<?= $event['id'] ?>">
                                        <td style="text-align: center; cursor: move;">☰</td>
                                        <td>
                                            <?php if (!empty($event['poster'])): ?>
                                                <img src="<?= htmlspecialchars($event['poster']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; background: #e2e8f0; border-radius: 4px;"></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($event['title']) ?></strong>
                                            <?php if (!empty($event['description'])): ?>
                                                <br><small style="color: #666;"><?= mb_substr(htmlspecialchars($event['description']), 0, 50) ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($event['category_name']) ?>
                                            <?php if (!empty($event['subcategory_name'])): ?>
                                                <br><small>└─ <?= htmlspecialchars($event['subcategory_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?= formatDateTime($event['start_time']) ?><br>
                                                至<br>
                                                <?= formatDateTime($event['end_time']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if (isEventExpired($event['end_time'])): ?>
                                                <span class="badge badge-danger">已过期</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">进行中</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="/admin/event-edit.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-primary">编辑</a>
                                                <a href="?delete=<?= $event['id'] ?>" class="btn btn-sm btn-danger delete-event-btn">删除</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // 拖拽排序
        const tbody = document.getElementById('sortable-tbody');
        if (tbody) {
            new Sortable(tbody, {
                animation: 150,
                handle: '.sortable-item',
                onEnd: function(evt) {
                    const orders = [];
                    const rows = tbody.querySelectorAll('tr');
                    
                    rows.forEach((row, index) => {
                        orders.push({
                            id: row.dataset.id,
                            order: index
                        });
                    });

                    // 发送更新请求
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=update_order&orders=' + JSON.stringify(orders)
                    });
                }
            });
        }

        // 处理删除确认
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($success): ?>
                toast.success('<?= addslashes($success) ?>');
            <?php endif; ?>
            <?php if ($error): ?>
                toast.error('<?= addslashes($error) ?>');
            <?php endif; ?>

            document.querySelectorAll('.delete-event-btn').forEach(btn => {
                btn.addEventListener('click', async function(e) {
                    e.preventDefault();
                    const confirmed = await dialog.confirm('确定删除该活动吗？删除后将无法恢复！', '删除确认');
                    if (confirmed) {
                        window.location.href = this.href;
                    }
                });
            });
        });
    </script>
    <script src="/assets/dialog.js"></script>
</body>
</html>
