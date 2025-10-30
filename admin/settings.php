<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance()->getConnection();
$page_title = 'Site Settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if ($key !== 'submit') {
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([sanitize($value), $key]);
            }
        }
        setFlashMessage('Settings updated successfully', 'success');
        redirect(SITE_URL . '/admin/settings.php');
    } catch (PDOException $e) {
        setFlashMessage('Error updating settings', 'error');
    }
}

// Get all settings
$stmt = $db->query("SELECT * FROM settings ORDER BY setting_key");
$settings = $stmt->fetchAll();

// Group settings by key for easy access
$settings_array = [];
foreach ($settings as $setting) {
    $settings_array[$setting['setting_key']] = $setting['setting_value'];
}

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Site Settings</h1>
</div>

<form method="POST" action="" class="form">
    <h2>General Settings</h2>
    
    <div class="form-group">
        <label for="site_name">Site Name</label>
        <input type="text" id="site_name" name="site_name" value="<?php echo sanitize($settings_array['site_name'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label for="site_tagline">Site Tagline</label>
        <input type="text" id="site_tagline" name="site_tagline" value="<?php echo sanitize($settings_array['site_tagline'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label for="site_description">Site Description</label>
        <textarea id="site_description" name="site_description" rows="4"><?php echo sanitize($settings_array['site_description'] ?? ''); ?></textarea>
    </div>
    
    <h2>Display Settings</h2>
    
    <div class="form-row">
        <div class="form-group col-4">
            <label for="posts_per_page">Posts Per Page</label>
            <input type="number" id="posts_per_page" name="posts_per_page" value="<?php echo sanitize($settings_array['posts_per_page'] ?? '10'); ?>">
        </div>
        
        <div class="form-group col-4">
            <label for="date_format">Date Format</label>
            <input type="text" id="date_format" name="date_format" value="<?php echo sanitize($settings_array['date_format'] ?? 'F j, Y'); ?>">
        </div>
        
        <div class="form-group col-4">
            <label for="time_format">Time Format</label>
            <input type="text" id="time_format" name="time_format" value="<?php echo sanitize($settings_array['time_format'] ?? 'g:i a'); ?>">
        </div>
    </div>
    
    <h2>Theme Settings</h2>
    
    <div class="form-row">
        <div class="form-group col-6">
            <label for="theme">Active Theme</label>
            <select id="theme" name="theme">
                <option value="default" <?php echo ($settings_array['theme'] ?? 'default') === 'default' ? 'selected' : ''; ?>>Default</option>
                <option value="modern" <?php echo ($settings_array['theme'] ?? '') === 'modern' ? 'selected' : ''; ?>>Modern</option>
                <option value="classic" <?php echo ($settings_array['theme'] ?? '') === 'classic' ? 'selected' : ''; ?>>Classic</option>
            </select>
        </div>
        
        <div class="form-group col-6">
            <label for="timezone">Timezone</label>
            <select id="timezone" name="timezone">
                <option value="UTC" <?php echo ($settings_array['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                <option value="America/New_York" <?php echo ($settings_array['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>America/New York</option>
                <option value="Europe/Paris" <?php echo ($settings_array['timezone'] ?? '') === 'Europe/Paris' ? 'selected' : ''; ?>>Europe/Paris</option>
                <option value="Asia/Tokyo" <?php echo ($settings_array['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo</option>
            </select>
        </div>
    </div>
    
    <h2>Comment Settings</h2>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="comments_enabled" value="1" <?php echo ($settings_array['comments_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
            Enable Comments
        </label>
    </div>
    
    <div class="form-actions">
        <button type="submit" name="submit" class="btn btn-primary">Save Settings</button>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
