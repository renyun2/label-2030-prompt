<?php
require_once __DIR__ . '/../common.php';
requireLogin();

$db = Database::getInstance();
$pageTitle = '网站设置';

$success = '';
$error = '';

// 获取当前设置
$settings = $db->fetch("SELECT * FROM site_settings ORDER BY id LIMIT 1");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = $_POST['site_name'] ?? '';
    $siteDescription = $_POST['site_description'] ?? '';
    $siteKeywords = $_POST['site_keywords'] ?? '';

    if (empty($siteName)) {
        $error = '网站名称不能为空';
    } else {
        $sql = "UPDATE site_settings SET site_name = ?, site_description = ?, site_keywords = ? WHERE id = ?";
        $db->execute($sql, [$siteName, $siteDescription, $siteKeywords, $settings['id']]);
        $success = '网站设置已更新';
        $settings = $db->fetch("SELECT * FROM site_settings WHERE id = ?", [$settings['id']]);
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
                <h1>网站设置</h1>
            </div>

            <div class="content-card">
                <form method="POST">
                    <div class="form-group">
                        <label>网站名称 *</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>网站描述</label>
                        <textarea name="site_description"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>网站关键词</label>
                        <input type="text" name="site_keywords" value="<?= htmlspecialchars($settings['site_keywords']) ?>">
                        <div class="form-help">多个关键词用逗号分隔</div>
                    </div>

                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
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
    </script>
</body>
</html>
