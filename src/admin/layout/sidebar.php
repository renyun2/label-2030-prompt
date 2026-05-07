<nav class="admin-sidebar">
    <div class="sidebar-header">
        <h2>活动管理系统</h2>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="/admin/index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <span class="menu-icon">📊</span>
                <span>系统概览</span>
            </a>
        </li>
        <li>
            <a href="/admin/settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                <span class="menu-icon">⚙️</span>
                <span>网站设置</span>
            </a>
        </li>
        <li>
            <a href="/admin/categories.php" class="<?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
                <span class="menu-icon">📁</span>
                <span>分类管理</span>
            </a>
        </li>
        <li>
            <a href="/admin/events.php" class="<?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : '' ?>">
                <span class="menu-icon">🎯</span>
                <span>活动列表</span>
            </a>
        </li>
        <li>
            <a href="/admin/event-create.php" class="<?= basename($_SERVER['PHP_SELF']) == 'event-create.php' ? 'active' : '' ?>">
                <span class="menu-icon">➕</span>
                <span>创建活动</span>
            </a>
        </li>
        <li>
            <a href="/" target="_blank">
                <span class="menu-icon">🌐</span>
                <span>查看前台</span>
            </a>
        </li>
        <li>
            <a href="/admin/logout.php">
                <span class="menu-icon">🚪</span>
                <span>退出登录</span>
            </a>
        </li>
    </ul>
</nav>
