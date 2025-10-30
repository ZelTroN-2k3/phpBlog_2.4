<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

session_start();

$db = Database::getInstance()->getConnection();

// Get settings
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll();
$site_settings = [];
foreach ($settings as $setting) {
    $site_settings[$setting['setting_key']] = $setting['setting_value'];
}

$page_title = 'About Us';

include __DIR__ . '/includes/header_public.php';
?>

<div class="container single-post-container">
    <article class="single-post">
        <header class="post-header">
            <h1>About <?php echo sanitize($site_settings['site_name'] ?? SITE_NAME); ?></h1>
        </header>
        
        <div class="post-content-full">
            <p>
                <strong><?php echo sanitize($site_settings['site_name'] ?? SITE_NAME); ?></strong> est un CMS (Content Management System) polyvalent. 
                Il peut être utilisé pour les blogs, les portails, les sites Web d'entreprises et d'agences, les magazines, 
                les journaux et bien d'autres. Il est propre, simple, léger, réactif et convivial.
            </p>
            
            <p>
                <strong><?php echo sanitize($site_settings['site_name'] ?? SITE_NAME); ?></strong> is a versatile Content Management System. 
                It can be used for blogs, portals, corporate and agency websites, magazines, newspapers, and much more. 
                It is clean, simple, lightweight, responsive, and user-friendly.
            </p>
            
            <h2>Features</h2>
            
            <h3>Content Management</h3>
            <ul>
                <li><strong>Post Management</strong> - Create, edit, delete, and publish blog posts with rich content</li>
                <li><strong>Category Management</strong> - Organize content with unlimited categories</li>
                <li><strong>Comment System</strong> - Built-in comment system with moderation and spam control</li>
                <li><strong>Page Management</strong> - Create static pages for different purposes</li>
            </ul>
            
            <h3>Media & Design</h3>
            <ul>
                <li><strong>Media Gallery</strong> - Upload and manage images and media files</li>
                <li><strong>Widget System</strong> - Customizable widgets for sidebar, footer, and header</li>
                <li><strong>Theme System</strong> - Support for multiple themes</li>
                <li><strong>Responsive Design</strong> - Mobile-friendly and works on all devices</li>
            </ul>
            
            <h3>Administration</h3>
            <ul>
                <li><strong>Powerful Admin Panel</strong> - Manage all aspects of your website from one place</li>
                <li><strong>User Authentication</strong> - Secure login system with role-based access</li>
                <li><strong>Site Settings</strong> - Configure all aspects of your website</li>
                <li><strong>Message System</strong> - Internal messaging for administrators</li>
                <li><strong>Dashboard</strong> - Overview of site statistics and recent activities</li>
            </ul>
            
            <h3>Technical Features</h3>
            <ul>
                <li><strong>Lightweight</strong> - Fast loading times and minimal resource usage</li>
                <li><strong>Secure</strong> - Built with security best practices</li>
                <li><strong>SEO Friendly</strong> - Clean URLs and proper meta tags</li>
                <li><strong>Database Driven</strong> - MySQL/MariaDB backend</li>
            </ul>
            
            <h2>Our Mission</h2>
            <p>
                Our mission is to provide a simple yet powerful content management system that anyone can use to create 
                and manage their website. Whether you're running a personal blog, a news portal, a magazine, or a corporate 
                website, phpBlog provides all the tools you need to succeed.
            </p>
            
            <h2>Get Started</h2>
            <p>
                Ready to get started? Visit our <a href="<?php echo SITE_URL; ?>/admin/login.php">admin panel</a> to begin 
                managing your content. If you need help, check out our documentation or contact support.
            </p>
        </div>
    </article>
</div>

<?php include __DIR__ . '/includes/footer_public.php'; ?>
