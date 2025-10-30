# phpBlog 2.4

**A secure, fast, and user-friendly Blog & Magazine CMS**

phpBlog is a lightweight Content Management System built with procedural PHP without any framework. It features a clean, responsive design and robust security measures, making it perfect for blogs, news sites, magazines, and personal websites.

## 🔒 Security Features

phpBlog 2.4 is designed with security as a top priority:

### Authentication & Authorization
- **Secure Password Hashing**: Uses bcrypt (cost factor 12) for password storage
- **Rate Limiting**: Prevents brute force attacks with configurable login attempt limits
- **Session Security**: HTTP-only cookies, secure session configuration, and automatic regeneration
- **Role-Based Access Control**: Admin, Editor, and Author roles with permission checks

### Input Protection
- **CSRF Protection**: Token-based protection on all forms
- **XSS Prevention**: Comprehensive input sanitization and output encoding
- **SQL Injection Protection**: PDO with prepared statements throughout
- **HTML Sanitization**: Whitelist-based HTML filtering for content

### Additional Security
- **Security Headers**: X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, CSP
- **File Upload Validation**: MIME type and extension checking
- **Secure Random Generation**: Cryptographically secure random tokens
- **Error Logging**: Sensitive errors logged, not displayed to users

## ✨ Features

- **User Management**: Secure user registration and authentication
- **Post Management**: Create, edit, and publish blog posts
- **Category System**: Organize content with categories
- **Comment System**: Moderate and manage user comments
- **Responsive Design**: Mobile-friendly interface
- **Admin Dashboard**: Intuitive control panel with statistics
- **Clean Code**: Well-organized, procedural PHP code for easy customization

## 📋 Requirements

- PHP 7.3 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx web server
- mod_rewrite (Apache) or equivalent

## 🚀 Installation

1. **Clone or download** the repository to your web server

2. **Configure your web server** to point to the phpBlog directory

3. **Run the installer** by visiting `http://yoursite.com/install.php`

4. **Follow the installation steps**:
   - Step 1: Configure database connection
   - Step 2: Import database schema
   - Step 3: Create admin account
   - Step 4: Complete installation

5. **Delete install.php** after installation for security

6. **Login** at `http://yoursite.com/login.php`

## 📁 Directory Structure

```
phpBlog_2.4/
├── admin/              # Admin panel files
├── assets/             # CSS, JavaScript, and images
│   ├── css/           # Stylesheets
│   └── js/            # JavaScript files
├── config/            # Configuration files
│   ├── config.php     # Main configuration
│   └── schema.sql     # Database schema
├── includes/          # Core functionality
│   ├── auth.php       # Authentication system
│   ├── database.php   # Database class
│   └── security.php   # Security functions
├── public/            # Public assets
├── uploads/           # User uploaded files
├── index.php          # Homepage
├── login.php          # Login page
└── install.php        # Installation script
```

## 🔐 Default Credentials

After installation, you will set your own admin credentials. The default credentials in the SQL file are:
- **Username**: admin
- **Password**: admin123

**⚠️ IMPORTANT**: Change these immediately after installation!

## 🛡️ Security Best Practices

1. **Change default credentials** immediately after installation
2. **Use HTTPS** in production environments
3. **Keep PHP updated** to the latest stable version
4. **Set proper file permissions**:
   - Directories: 755
   - Files: 644
   - config/config.php: 600
5. **Enable PHP security features** (disable dangerous functions)
6. **Regular backups** of database and files
7. **Monitor logs** for suspicious activity

## 🎨 Customization

phpBlog is built with procedural PHP, making it easy to customize:

- **Modify templates**: Edit PHP files directly
- **Add features**: Extend existing functionality
- **Custom styling**: Modify CSS files in `assets/css/`
- **Database changes**: Use migration scripts

## 📝 Usage

### Creating Posts
1. Login to admin panel
2. Navigate to Posts > New Post
3. Enter title, content, and select category
4. Publish or save as draft

### Managing Users
1. Admin users can access Users section
2. Create, edit, or delete user accounts
3. Assign roles: Admin, Editor, or Author

### Site Settings
1. Access Settings in admin panel
2. Configure site name, description
3. Manage comment settings
4. Adjust posts per page

## 🤝 Contributing

Contributions are welcome! Please ensure any pull requests maintain the security standards and coding style of the project.

## 📄 License

This project is open source and available under the MIT License.

## 🐛 Security Issues

If you discover a security vulnerability, please email security@phpblog.local instead of using the issue tracker.

## 📞 Support

For questions and support, please open an issue on GitHub.

---

**Built with security, speed, and simplicity in mind** ❤️
