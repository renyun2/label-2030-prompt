<header class="admin-header">
    <div class="header-content">
        <h1><?= $pageTitle ?? '后台管理' ?></h1>
        <div class="header-user">
            <span>欢迎, <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
        </div>
    </div>
</header>
