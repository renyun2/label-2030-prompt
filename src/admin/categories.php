<?php
require_once __DIR__ . '/../common.php';
requireLogin();

$db = Database::getInstance();
$pageTitle = '分类管理';

$success = '';
$error = '';

// 处理分类操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $parentId = intval($_POST['parent_id'] ?? 0);
        
        if (empty($name)) {
            $error = '分类名称不能为空';
        } else {
            $db->execute("INSERT INTO categories (name, parent_id) VALUES (?, ?)", [$name, $parentId]);
            $success = '分类添加成功';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        // 检查是否有子分类
        $hasChildren = $db->fetch("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?", [$id])['count'];
        if ($hasChildren > 0) {
            $error = '该分类下有子分类，无法删除';
        } else {
            // 检查是否有活动使用该分类
            $hasEvents = $db->fetch("SELECT COUNT(*) as count FROM events WHERE category_id = ? OR subcategory_id = ?", [$id, $id])['count'];
            if ($hasEvents > 0) {
                $error = '该分类下有活动，无法删除';
            } else {
                $db->execute("DELETE FROM categories WHERE id = ?", [$id]);
                $success = '分类删除成功';
            }
        }
    }
}

// 获取所有一级分类
$categories = $db->fetchAll("SELECT * FROM categories WHERE parent_id = 0 ORDER BY sort_order, id");

// 获取所有二级分类
$subCategories = [];
foreach ($categories as $category) {
    $subs = $db->fetchAll("SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order, id", [$category['id']]);
    $subCategories[$category['id']] = $subs;
}
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
                <h1>分类管理</h1>
            </div>

            <div class="content-card">
                <h3 style="margin-bottom: 20px;">添加一级分类</h3>
                <form method="POST" style="margin-bottom: 30px;">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="parent_id" value="0">
                    <div class="form-row">
                        <div class="form-group" style="margin-bottom: 0;">
                            <input type="text" name="name" placeholder="请输入分类名称" required>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">添加分类</button>
                        </div>
                    </div>
                </form>

                <h3 style="margin-bottom: 20px;">分类列表</h3>
                
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <p>暂无分类，请先添加分类</p>
                    </div>
                <?php else: ?>
                    <ul class="category-tree">
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <div class="category-item">
                                    <strong><?= htmlspecialchars($category['name']) ?></strong>
                                    <div class="action-buttons">
                                        <button onclick="showAddSubForm(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')" class="btn btn-sm btn-success">添加子分类</button>
                                        <form method="POST" style="display: inline;" class="delete-form">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">删除</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if (isset($subCategories[$category['id']]) && !empty($subCategories[$category['id']])): ?>
                                    <ul class="sub-categories">
                                        <?php foreach ($subCategories[$category['id']] as $sub): ?>
                                            <li>
                                                <div class="category-item">
                                                    <span>└─ <?= htmlspecialchars($sub['name']) ?></span>
                                                    <form method="POST" style="display: inline;" class="delete-form delete-sub">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">删除</button>
                                                    </form>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                
                                <div id="add-sub-form-<?= $category['id'] ?>" style="display: none; margin-top: 10px; margin-left: 30px;">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="parent_id" value="<?= $category['id'] ?>">
                                        <div class="form-row">
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <input type="text" name="name" placeholder="请输入子分类名称" required>
                                            </div>
                                            <div>
                                                <button type="submit" class="btn btn-sm btn-success">添加</button>
                                                <button type="button" onclick="hideAddSubForm(<?= $category['id'] ?>)" class="btn btn-sm btn-secondary">取消</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="/assets/dialog.js"></script>
    <script>
        <?php if ($success): ?>
            toast.success('<?= addslashes($success) ?>');
        <?php endif; ?>
        <?php if ($error): ?>
            toast.error('<?= addslashes($error) ?>');
        <?php endif; ?>

        function showAddSubForm(categoryId, categoryName) {
            document.getElementById('add-sub-form-' + categoryId).style.display = 'block';
        }

        function hideAddSubForm(categoryId) {
            document.getElementById('add-sub-form-' + categoryId).style.display = 'none';
        }

        // 处理删除确认
        document.addEventListener('DOMContentLoaded', function() {
            // 主分类删除
            document.querySelectorAll('.delete-form:not(.delete-sub)').forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const confirmed = await dialog.confirm('确定删除该分类吗？删除后将无法恢复！', '删除确认');
                    if (confirmed) {
                        this.submit();
                    }
                });
            });

            // 子分类删除
            document.querySelectorAll('.delete-form.delete-sub').forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const confirmed = await dialog.confirm('确定删除该子分类吗？删除后将无法恢复！', '删除确认');
                    if (confirmed) {
                        this.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>
