<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo sanitize($site_settings['site_name'] ?? SITE_NAME); ?></title>
    <meta name="description" content="<?php echo sanitize($site_settings['site_description'] ?? ''); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="<?php echo SITE_URL; ?>/index.php">
                        <h1><?php echo sanitize($site_settings['site_name'] ?? SITE_NAME); ?></h1>
                    </a>
                </div>
                
                <nav class="main-nav">
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php">About</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo SITE_URL; ?>/admin/index.php">Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="site-main">
