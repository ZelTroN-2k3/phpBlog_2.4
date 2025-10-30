# phpBlog 2.4 - News, Blog & Magazine CMS

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue.svg)
![MySQL Version](https://img.shields.io/badge/MySQL-%3E%3D5.7-blue.svg)

## 📖 Description

**phpBlog** est un CMS (Content Management System) polyvalent. Il peut être utilisé pour les blogs, les portails, les sites Web d'entreprises et d'agences, les magazines, les journaux et bien d'autres. Il est propre, simple, léger, réactif et convivial.

Avec l'aide de son puissant panneau d'administration, vous pouvez gérer les publications, les catégories, les commentaires, la galerie, les pages, les widgets, les thèmes, les paramètres du site Web, les messages et bien d'autres.

**phpBlog** is a versatile Content Management System. It can be used for blogs, portals, corporate and agency websites, magazines, newspapers, and much more. It is clean, simple, lightweight, responsive, and user-friendly.

With the help of its powerful admin panel, you can manage posts, categories, comments, gallery, pages, widgets, themes, website settings, messages, and much more.

## ✨ Features

### Content Management
- 📝 **Post Management** - Create, edit, delete, and publish blog posts with rich content
- 📁 **Category Management** - Organize content with unlimited categories and subcategories
- 💬 **Comment System** - Built-in comment system with moderation and spam control
- 📄 **Page Management** - Create static pages for About, Contact, etc.

### Media & Design
- 🖼️ **Media Gallery** - Upload and manage images and media files
- 🧩 **Widget System** - Customizable widgets for sidebar, footer, and header
- 🎨 **Theme System** - Support for multiple themes
- 📱 **Responsive Design** - Mobile-friendly and works on all devices

### Administration
- 🔐 **User Authentication** - Secure login system with role-based access
- ⚙️ **Site Settings** - Configure all aspects of your website
- ✉️ **Message System** - Internal messaging for administrators
- 📊 **Dashboard** - Overview of site statistics and recent activities

### Technical Features
- 🚀 **Lightweight** - Fast loading times and minimal resource usage
- 🔒 **Secure** - Built with security best practices
- 🌐 **SEO Friendly** - Clean URLs and proper meta tags
- 💾 **Database Driven** - MySQL/MariaDB backend

## 🛠️ Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## 📦 Installation

See [INSTALL.md](INSTALL.md) for detailed installation instructions.

### Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/ZelTroN-2k3/phpBlog_2.4.git
   cd phpBlog_2.4
   ```

2. **Create database and import schema**
   ```bash
   mysql -u root -p
   CREATE DATABASE phpblog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   exit;
   mysql -u root -p phpblog < install.sql
   ```

3. **Configure settings**
   - Edit `config/config.php` with your database credentials
   - Update `SITE_URL` with your domain

4. **Set permissions**
   ```bash
   chmod 755 public/uploads
   ```

5. **Access the application**
   - Frontend: `http://your-domain.com`
   - Admin: `http://your-domain.com/admin/login.php`
   - Default credentials: `admin` / `admin123`

## 📚 Documentation

### Admin Panel Access
- URL: `/admin/login.php`
- Default Username: `admin`
- Default Password: `admin123`

### File Structure
```
phpBlog_2.4/
├── admin/              # Admin panel files
│   ├── includes/       # Admin templates
│   ├── index.php       # Dashboard
│   ├── posts.php       # Post management
│   ├── categories.php  # Category management
│   ├── comments.php    # Comment management
│   ├── pages.php       # Page management
│   ├── media.php       # Media gallery
│   ├── widgets.php     # Widget management
│   ├── settings.php    # Site settings
│   └── messages.php    # Messages
├── config/             # Configuration files
│   └── config.php      # Main configuration
├── includes/           # Core includes
│   ├── database.php    # Database connection
│   ├── functions.php   # Helper functions
│   ├── auth.php        # Authentication
│   └── header/footer   # Public templates
├── public/             # Public assets
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   └── uploads/       # Uploaded media
├── assets/            # Additional assets
│   ├── themes/        # Theme files
│   └── widgets/       # Widget files
├── index.php          # Homepage
├── post.php           # Single post view
├── category.php       # Category view
└── install.sql        # Database schema
```

## 🎯 Usage

### Creating Your First Post
1. Log in to admin panel
2. Navigate to **Posts** → **Add New Post**
3. Enter title, content, and select category
4. Click **Create Post** to publish

### Managing Comments
1. Go to **Comments** in admin panel
2. Approve, mark as spam, or delete comments
3. Comments require approval before appearing on site

### Customizing Settings
1. Navigate to **Settings**
2. Update site name, tagline, and description
3. Configure posts per page, date format, etc.
4. Choose active theme

## 🔒 Security

- Passwords are hashed using bcrypt
- SQL injection protection with prepared statements
- XSS protection with input sanitization
- CSRF protection recommended for production
- Session-based authentication

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-source and available under the MIT License.

## 👤 Author

ZelTroN-2k3

## 🙏 Acknowledgments

- Built with PHP and MySQL
- Responsive design with modern CSS
- Clean and intuitive user interface

---

**Note**: This is version 2.4 of phpBlog CMS. Make sure to change default credentials after installation and keep your installation updated.
