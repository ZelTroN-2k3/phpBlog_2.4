<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo SITE_NAME; ?></h2>
                <p>Admin Panel</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="<?php echo SITE_URL; ?>/admin/index.php" class="nav-item">
                    <span class="icon">🏠</span> Dashboard
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/posts.php" class="nav-item">
                    <span class="icon">📝</span> Posts
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/categories.php" class="nav-item">
                    <span class="icon">📁</span> Categories
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/comments.php" class="nav-item">
                    <span class="icon">💬</span> Comments
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/pages.php" class="nav-item">
                    <span class="icon">📄</span> Pages
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/media.php" class="nav-item">
                    <span class="icon">🖼️</span> Gallery
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/widgets.php" class="nav-item">
                    <span class="icon">🧩</span> Widgets
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="nav-item">
                    <span class="icon">⚙️</span> Settings
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/messages.php" class="nav-item">
                    <span class="icon">✉️</span> Messages
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo sanitize($_SESSION['full_name'] ?? $_SESSION['username']); ?></strong>
                    <small><?php echo sanitize($_SESSION['user_role']); ?></small>
                </div>
                <a href="<?php echo SITE_URL; ?>/admin/logout.php" class="btn btn-sm btn-danger">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle">☰</button>
                </div>
                <div class="topbar-right">
                    <a href="<?php echo SITE_URL; ?>/index.php" target="_blank" class="btn btn-sm">View Site</a>
                </div>
            </div>
            
            <div class="content-wrapper">
                <?php 
                $flash = getFlashMessage();
                if ($flash): 
                ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>
