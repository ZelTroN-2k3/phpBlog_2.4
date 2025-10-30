<?php
/**
 * phpBlog Installation Script
 * Secure installation and setup
 */

define('BASE_PATH', __DIR__);
require_once 'config/config.php';

$error = '';
$success = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Check if already installed
$install_lock = BASE_PATH . '/config/install.lock';
if (file_exists($install_lock)) {
    die('phpBlog is already installed. Delete config/install.lock to reinstall.');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // Database connection test
        try {
            $dsn = 'mysql:host=' . $_POST['db_host'] . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $_POST['db_user'], $_POST['db_pass']);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . $_POST['db_name'] . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Update config file
            $config_content = file_get_contents('config/config.php');
            $config_content = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', '" . $_POST['db_host'] . "');", $config_content);
            $config_content = str_replace("define('DB_NAME', 'phpblog');", "define('DB_NAME', '" . $_POST['db_name'] . "');", $config_content);
            $config_content = str_replace("define('DB_USER', 'root');", "define('DB_USER', '" . $_POST['db_user'] . "');", $config_content);
            $config_content = str_replace("define('DB_PASS', '');", "define('DB_PASS', '" . addslashes($_POST['db_pass']) . "');", $config_content);
            
            // Generate site key
            $site_key = bin2hex(random_bytes(32));
            $config_content = str_replace("define('SITE_KEY', '');", "define('SITE_KEY', '" . $site_key . "');", $config_content);
            
            file_put_contents('config/config.php', $config_content);
            
            $success = 'Database connection successful!';
            header('Location: install.php?step=2');
            exit();
        } catch (PDOException $e) {
            $error = 'Database connection failed: ' . $e->getMessage();
        }
    } elseif ($step === 2) {
        // Import database schema
        try {
            require_once 'config/config.php';
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            
            $schema = file_get_contents('config/schema.sql');
            $pdo->exec($schema);
            
            $success = 'Database schema imported successfully!';
            header('Location: install.php?step=3');
            exit();
        } catch (PDOException $e) {
            $error = 'Schema import failed: ' . $e->getMessage();
        }
    } elseif ($step === 3) {
        // Create admin user
        try {
            require_once 'config/config.php';
            require_once 'includes/security.php';
            
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            
            $username = $_POST['admin_username'];
            $email = $_POST['admin_email'];
            $password = hash_password($_POST['admin_password']);
            
            // Update admin user
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = 1");
            $stmt->execute([$username, $email, $password]);
            
            // Create install lock file
            file_put_contents($install_lock, date('Y-m-d H:i:s'));
            
            header('Location: install.php?step=4');
            exit();
        } catch (PDOException $e) {
            $error = 'Admin user creation failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>phpBlog Installation</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .install-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .install-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .install-step {
            flex: 1;
            text-align: center;
            padding: 1rem;
            background: #ecf0f1;
            margin: 0 0.25rem;
            border-radius: 4px;
        }
        .install-step.active {
            background: #3498db;
            color: white;
        }
        .install-step.completed {
            background: #27ae60;
            color: white;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <h1 style="text-align: center; margin-bottom: 2rem;">phpBlog Installation</h1>
        
        <div class="install-steps">
            <div class="install-step <?php echo $step === 1 ? 'active' : ($step > 1 ? 'completed' : ''); ?>">
                1. Database
            </div>
            <div class="install-step <?php echo $step === 2 ? 'active' : ($step > 2 ? 'completed' : ''); ?>">
                2. Schema
            </div>
            <div class="install-step <?php echo $step === 3 ? 'active' : ($step > 3 ? 'completed' : ''); ?>">
                3. Admin
            </div>
            <div class="install-step <?php echo $step === 4 ? 'active' : ''; ?>">
                4. Complete
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($step === 1): ?>
            <h2>Step 1: Database Configuration</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="phpblog" required>
                </div>
                <div class="form-group">
                    <label>Database User</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Continue</button>
            </form>
        
        <?php elseif ($step === 2): ?>
            <h2>Step 2: Import Database Schema</h2>
            <p>Click the button below to import the database schema.</p>
            <form method="POST">
                <button type="submit" class="btn btn-primary btn-block">Import Schema</button>
            </form>
        
        <?php elseif ($step === 3): ?>
            <h2>Step 3: Create Admin Account</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Admin Username</label>
                    <input type="text" name="admin_username" required>
                </div>
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" required>
                </div>
                <div class="form-group">
                    <label>Admin Password</label>
                    <input type="password" name="admin_password" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create Admin</button>
            </form>
        
        <?php elseif ($step === 4): ?>
            <h2>Installation Complete!</h2>
            <div class="alert alert-success">
                <p>phpBlog has been successfully installed with the following security features:</p>
                <ul>
                    <li>✓ CSRF Protection</li>
                    <li>✓ XSS Prevention</li>
                    <li>✓ SQL Injection Protection (PDO)</li>
                    <li>✓ Secure Password Hashing</li>
                    <li>✓ Session Security</li>
                    <li>✓ Rate Limiting</li>
                    <li>✓ Security Headers</li>
                    <li>✓ File Upload Validation</li>
                </ul>
            </div>
            <a href="login.php" class="btn btn-primary btn-block">Go to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
