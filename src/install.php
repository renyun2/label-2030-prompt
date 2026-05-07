<?php
session_start();

// 检查是否已安装
$installedLockFile = __DIR__ . '/config/installed.lock';
if (file_exists($installedLockFile)) {
    header('Location: /index.php');
    exit;
}

$error = '';
$success = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// 处理安装请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 第一步：保存数据库配置到session
    if (isset($_POST['step1'])) {
        $_SESSION['db_config'] = [
            'host' => trim($_POST['db_host'] ?? ''),
            'dbname' => trim($_POST['db_name'] ?? ''),
            'username' => trim($_POST['db_user'] ?? ''),
            'password' => $_POST['db_pass'] ?? ''
        ];
        
        // 验证数据库连接
        try {
            $dsn = "mysql:host={$_SESSION['db_config']['host']};charset=utf8mb4";
            $pdo = new PDO($dsn, $_SESSION['db_config']['username'], $_SESSION['db_config']['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 测试创建数据库
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$_SESSION['db_config']['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            header('Location: install.php?step=2');
            exit;
        } catch (PDOException $e) {
            $error = '数据库连接失败: ' . $e->getMessage();
        }
    }
    
    // 第二步：创建管理员账户并初始化数据库
    if (isset($_POST['step2'])) {
        $adminUsername = trim($_POST['admin_user'] ?? '');
        $adminPassword = $_POST['admin_pass'] ?? '';
        $adminPasswordConfirm = $_POST['admin_pass_confirm'] ?? '';
        
        if (empty($adminUsername) || empty($adminPassword)) {
            $error = '请填写管理员用户名和密码';
        } elseif (strlen($adminPassword) < 6) {
            $error = '密码长度至少为6个字符';
        } elseif ($adminPassword !== $adminPasswordConfirm) {
            $error = '两次输入的密码不一致';
        } else {
            try {
                $db_config = $_SESSION['db_config'];
                $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // 创建网站设置表
                $pdo->exec("DROP TABLE IF EXISTS settings");
                $pdo->exec("CREATE TABLE settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    site_name VARCHAR(255) NOT NULL DEFAULT '活动管理系统',
                    site_description TEXT,
                    site_keywords VARCHAR(500),
                    footer_text VARCHAR(500),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                // 插入默认设置
                $stmt = $pdo->prepare("INSERT INTO settings (site_name, site_description, site_keywords, footer_text) VALUES (?, ?, ?, ?)");
                $stmt->execute(['活动管理系统', '专业的活动发布与管理平台', '活动,管理,发布', '© 2026 活动管理系统. All rights reserved.']);

                // 创建管理员表
                $pdo->exec("DROP TABLE IF EXISTS admins");
                $pdo->exec("CREATE TABLE admins (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                // 插入管理员账户
                $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                $stmt->execute([$adminUsername, $hashedPassword]);

                // 创建分类表
                $pdo->exec("DROP TABLE IF EXISTS events");
                $pdo->exec("DROP TABLE IF EXISTS categories");
                $pdo->exec("CREATE TABLE categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    parent_id INT DEFAULT 0,
                    sort_order INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_parent (parent_id),
                    INDEX idx_sort (sort_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                // 创建活动表
                $pdo->exec("CREATE TABLE events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    poster VARCHAR(255),
                    category_id INT NOT NULL,
                    subcategory_id INT DEFAULT NULL,
                    button_type ENUM('link', 'copy') NOT NULL,
                    button_name VARCHAR(100),
                    button_link VARCHAR(500),
                    copy_content TEXT,
                    copy_success_message VARCHAR(255),
                    start_time DATETIME NOT NULL,
                    end_time DATETIME NOT NULL,
                    sort_order INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_category (category_id),
                    INDEX idx_subcategory (subcategory_id),
                    INDEX idx_time (start_time, end_time),
                    INDEX idx_sort (sort_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                // 写入数据库配置文件
                $configContent = "<?php\nreturn [\n";
                $configContent .= "    'host' => " . var_export($db_config['host'], true) . ",\n";
                $configContent .= "    'dbname' => " . var_export($db_config['dbname'], true) . ",\n";
                $configContent .= "    'username' => " . var_export($db_config['username'], true) . ",\n";
                $configContent .= "    'password' => " . var_export($db_config['password'], true) . ",\n";
                $configContent .= "    'charset' => 'utf8mb4'\n";
                $configContent .= "];\n";
                
                $configDir = __DIR__ . '/config';
                if (!is_dir($configDir)) {
                    mkdir($configDir, 0755, true);
                }
                file_put_contents($configDir . '/database.php', $configContent);

                // 创建安装标记文件
                file_put_contents($installedLockFile, date('Y-m-d H:i:s'));

                $success = '安装成功！正在跳转到登录页面...';
                $_SESSION['install_complete'] = true;
                header('refresh:2;url=/admin/login.php');
            } catch (PDOException $e) {
                $error = '数据库初始化失败: ' . $e->getMessage();
            }
        }
    }
}

// 获取保存的数据库配置（如果有）
$db_config = $_SESSION['db_config'] ?? [
    'host' => 'mysql',
    'dbname' => 'domo',
    'username' => 'domo',
    'password' => '8dZnsYMbizSPzk6c'
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - 活动管理系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .install-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }

        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .install-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .install-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .install-content {
            padding: 40px;
        }

        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .step.active .step-circle {
            background: #667eea;
            color: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        .step.completed .step-circle {
            background: #10b981;
            color: white;
        }

        .step-label {
            font-size: 13px;
            color: #666;
        }

        .step.active .step-label {
            color: #667eea;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-hint {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            color: #666;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .required {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>🎉 欢迎安装活动管理系统</h1>
            <p>一个现代化的活动发布与管理平台</p>
        </div>

        <div class="install-content">
            <div class="steps">
                <div class="step <?php echo $step === 1 ? 'active' : ($step > 1 ? 'completed' : ''); ?>">
                    <div class="step-circle">1</div>
                    <div class="step-label">数据库配置</div>
                </div>
                <div class="step <?php echo $step === 2 ? 'active' : ($step > 2 ? 'completed' : ''); ?>">
                    <div class="step-circle">2</div>
                    <div class="step-label">管理员设置</div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>❌ 错误：</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>✅ 成功：</strong> <?php echo htmlspecialchars($success); ?>
                </div>
                <div class="loading active">
                    <div class="spinner"></div>
                    <p>正在跳转...</p>
                </div>
            <?php elseif ($step === 1): ?>
                <div class="info-box">
                    💡 请填写MySQL数据库连接信息。系统将自动创建数据库（如果不存在）。
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>数据库主机 <span class="required">*</span></label>
                        <input type="text" name="db_host" value="<?php echo htmlspecialchars($db_config['host']); ?>" required placeholder="localhost 或 mysql">
                        <div class="input-hint">通常为 localhost 或 mysql（Docker环境）</div>
                    </div>

                    <div class="form-group">
                        <label>数据库名称 <span class="required">*</span></label>
                        <input type="text" name="db_name" value="<?php echo htmlspecialchars($db_config['dbname']); ?>" required placeholder="domo">
                        <div class="input-hint">系统将使用的数据库名称</div>
                    </div>

                    <div class="form-group">
                        <label>数据库用户名 <span class="required">*</span></label>
                        <input type="text" name="db_user" value="<?php echo htmlspecialchars($db_config['username']); ?>" required placeholder="domo">
                    </div>

                    <div class="form-group">
                        <label>数据库密码</label>
                        <input type="password" name="db_pass" value="<?php echo htmlspecialchars($db_config['password']); ?>" placeholder="数据库密码（如有）">
                    </div>

                    <button type="submit" name="step1" class="btn">下一步：设置管理员</button>
                </form>

            <?php elseif ($step === 2): ?>
                <div class="info-box">
                    👤 请设置系统管理员账户信息。此账户将用于登录后台管理系统。
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>管理员用户名 <span class="required">*</span></label>
                        <input type="text" name="admin_user" value="admin" required placeholder="admin">
                        <div class="input-hint">用于登录后台的用户名</div>
                    </div>

                    <div class="form-group">
                        <label>管理员密码 <span class="required">*</span></label>
                        <input type="password" name="admin_pass" required placeholder="请输入密码（至少6位）">
                        <div class="input-hint">建议使用包含字母、数字的强密码</div>
                    </div>

                    <div class="form-group">
                        <label>确认密码 <span class="required">*</span></label>
                        <input type="password" name="admin_pass_confirm" required placeholder="请再次输入密码">
                    </div>

                    <button type="submit" name="step2" class="btn">完成安装</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
