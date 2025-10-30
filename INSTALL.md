# phpBlog Installation Guide

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## Installation Steps

### 1. Database Setup

Create a new MySQL database and import the schema:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE phpblog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

Import the database schema:

```bash
mysql -u root -p phpblog < install.sql
```

### 2. Configuration

Edit the configuration file at `config/config.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'phpblog');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

Also update the site URL:

```php
define('SITE_URL', 'http://your-domain.com');
```

### 3. File Permissions

Make sure the uploads directory is writable:

```bash
chmod 755 public/uploads
```

### 4. Web Server Configuration

#### Apache

Create an `.htaccess` file in the root directory:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

#### Nginx

Add this to your server block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

### 5. Access the Application

**Frontend**: Navigate to `http://your-domain.com`

**Admin Panel**: Navigate to `http://your-domain.com/admin/login.php`

**Default Admin Credentials**:
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT**: Change the default admin password immediately after first login!

## Post-Installation

### Security Recommendations

1. **Change Admin Password**: Go to Settings and change the default admin password
2. **Update Configuration**: Review and update all configuration settings in `config/config.php`
3. **File Permissions**: Ensure proper file permissions are set
4. **Enable HTTPS**: Use SSL certificate for secure connections
5. **Regular Backups**: Set up automated database and file backups

### Initial Setup

1. Log in to the admin panel
2. Go to **Settings** and configure your site information
3. Create **Categories** for your content
4. Add your first **Post**
5. Configure **Widgets** for your sidebar
6. Customize your **Theme** settings

## Features

- ✅ Post Management (Create, Edit, Delete)
- ✅ Category Management
- ✅ Comment Management with Moderation
- ✅ Page Management
- ✅ Media Gallery with File Upload
- ✅ Widget System
- ✅ Theme Support
- ✅ Site Settings
- ✅ Message System
- ✅ User Management
- ✅ Responsive Design
- ✅ Clean and Modern UI

## Support

For issues and questions, please visit the GitHub repository.

## License

This project is open-source and available under the MIT License.
