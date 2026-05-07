<?php
require_once __DIR__ . '/../common.php';
requireLogin();

$db = Database::getInstance();
$pageTitle = '创建活动';

$success = '';
$error = '';

// 获取所有一级分类
$categories = $db->fetchAll("SELECT * FROM categories WHERE parent_id = 0 ORDER BY sort_order, id");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = intval($_POST['category_id'] ?? 0);
    $subCategoryId = !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $buttonType = $_POST['button_type'] ?? '';
    $buttonName = trim($_POST['button_name'] ?? '');
    $buttonLink = trim($_POST['button_link'] ?? '');
    $copyContent = trim($_POST['copy_content'] ?? '');
    $copySuccessMessage = trim($_POST['copy_success_message'] ?? '');
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';

    // 验证
    if (empty($name)) {
        $error = '活动名称不能为空';
    } elseif ($categoryId == 0) {
        $error = '请选择分类';
    } elseif (empty($startTime) || empty($endTime)) {
        $error = '请选择活动时间';
    } elseif (strtotime($endTime) <= strtotime($startTime)) {
        $error = '结束时间必须大于开始时间';
    } elseif (!in_array($buttonType, ['link', 'copy'])) {
        $error = '请选择按钮类型';
    } else {
        // 处理海报上传
        $posterPath = '';
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['poster']);
            if ($uploadResult['success']) {
                $posterPath = $uploadResult['path'];
            } else {
                $error = $uploadResult['message'];
            }
        }

        if (empty($error)) {
            // 获取最大排序值
            $maxSort = $db->fetch("SELECT MAX(sort_order) as max_sort FROM events")['max_sort'] ?? 0;
            
            $sql = "INSERT INTO events (category_id, subcategory_id, title, description, poster, button_type, button_name, button_link, copy_content, copy_success_message, start_time, end_time, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [
                $categoryId,
                $subCategoryId,
                $name,
                $description,
                $posterPath,
                $buttonType,
                $buttonName,
                $buttonLink,
                $copyContent,
                $copySuccessMessage,
                $startTime,
                $endTime,
                $maxSort + 1
            ]);
            
            $success = '活动创建成功';
            
            // 清空表单
            $_POST = [];
        }
    }
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
                <h1>创建活动</h1>
            </div>

            <div class="content-card">
                <form method="POST" enctype="multipart/form-data" id="eventForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>一级分类 *</label>
                            <select name="category_id" id="category_id" required onchange="loadSubCategories()">
                                <option value="">请选择分类</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>二级分类（可选）</label>
                            <select name="subcategory_id" id="subcategory_id">
                                <option value="">无</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>活动名称 *</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>活动介绍</label>
                        <textarea name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>海报上传</label>
                        <input type="file" name="poster" accept="image/*" onchange="previewImage(this)">
                        <img id="posterPreview" class="image-preview" style="display: none;">
                    </div>

                    <div class="form-group">
                        <label>按钮类型 *</label>
                        <select name="button_type" id="button_type" required onchange="toggleButtonFields()">
                            <option value="">请选择</option>
                            <option value="link" <?= (isset($_POST['button_type']) && $_POST['button_type'] == 'link') ? 'selected' : '' ?>>链接跳转</option>
                            <option value="copy" <?= (isset($_POST['button_type']) && $_POST['button_type'] == 'copy') ? 'selected' : '' ?>>复制按钮</option>
                        </select>
                    </div>

                    <div class="form-group" id="buttonNameField" style="display: none;">
                        <label>按钮名称 *</label>
                        <input type="text" name="button_name" id="button_name" value="<?= htmlspecialchars($_POST['button_name'] ?? '') ?>">
                    </div>

                    <div id="linkFields" style="display: none;">
                        <div class="form-group">
                            <label>链接地址 *</label>
                            <input type="url" name="button_link" value="<?= htmlspecialchars($_POST['button_link'] ?? '') ?>">
                        </div>
                    </div>

                    <div id="copyFields" style="display: none;">
                        <div class="form-group">
                            <label>复制内容 *</label>
                            <textarea name="copy_content"><?= htmlspecialchars($_POST['copy_content'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>复制成功提示</label>
                            <input type="text" name="copy_success_message" value="<?= htmlspecialchars($_POST['copy_success_message'] ?? '复制成功') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>开始时间 *</label>
                            <input type="datetime-local" name="start_time" value="<?= $_POST['start_time'] ?? '' ?>" required>
                        </div>

                        <div class="form-group">
                            <label>结束时间 *</label>
                            <input type="datetime-local" name="end_time" value="<?= $_POST['end_time'] ?? '' ?>" required>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">创建活动</button>
                        <a href="/admin/events.php" class="btn btn-secondary">返回列表</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // 加载子分类
        function loadSubCategories() {
            const categoryId = document.getElementById('category_id').value;
            const subCategorySelect = document.getElementById('subcategory_id');
            
            subCategorySelect.innerHTML = '<option value="">无</option>';
            
            if (!categoryId) return;

            fetch('/admin/api/get-subcategories.php?parent_id=' + categoryId)
                .then(response => response.json())
                .then(data => {
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name;
                        subCategorySelect.appendChild(option);
                    });
                });
        }

        // 切换按钮字段显示
        function toggleButtonFields() {
            const buttonType = document.getElementById('button_type').value;
            const buttonNameField = document.getElementById('buttonNameField');
            const linkFields = document.getElementById('linkFields');
            const copyFields = document.getElementById('copyFields');
            
            if (buttonType === 'link') {
                buttonNameField.style.display = 'block';
                linkFields.style.display = 'block';
                copyFields.style.display = 'none';
            } else if (buttonType === 'copy') {
                buttonNameField.style.display = 'block';
                linkFields.style.display = 'none';
                copyFields.style.display = 'block';
            } else {
                buttonNameField.style.display = 'none';
                linkFields.style.display = 'none';
                copyFields.style.display = 'none';
            }
        }

        // 预览图片
        function previewImage(input) {
            const preview = document.getElementById('posterPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 页面加载时初始化
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($success): ?>
                toast.success('<?= addslashes($success) ?>');
                setTimeout(() => { window.location.href = '/admin/events.php'; }, 2000);
            <?php endif; ?>
            <?php if ($error): ?>
                toast.error('<?= addslashes($error) ?>');
            <?php endif; ?>

            toggleButtonFields();
            if (document.getElementById('category_id').value) {
                loadSubCategories();
            }
        });
    </script>
    <script src="/assets/dialog.js"></script>
</body>
</html>
