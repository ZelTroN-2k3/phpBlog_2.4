    </main>
    
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3><?php echo sanitize($site_settings['site_name'] ?? SITE_NAME); ?></h3>
                    <p><?php echo sanitize($site_settings['site_description'] ?? ''); ?></p>
                </div>
                
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php">About</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/admin/login.php">Admin Login</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Categories</h3>
                    <ul>
                        <?php 
                        $db = Database::getInstance()->getConnection();
                        $stmt = $db->query("SELECT name, slug FROM categories LIMIT 5");
                        $footer_categories = $stmt->fetchAll();
                        foreach ($footer_categories as $cat): 
                        ?>
                            <li><a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo $cat['slug']; ?>"><?php echo sanitize($cat['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo sanitize($site_settings['site_name'] ?? SITE_NAME); ?>. All rights reserved.</p>
                <p>Powered by phpBlog - A versatile CMS for blogs, portals, magazines and more.</p>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo SITE_URL; ?>/public/js/script.js"></script>
</body>
</html>
