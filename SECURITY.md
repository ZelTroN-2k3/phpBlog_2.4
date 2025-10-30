# Security Policy

## Overview

phpBlog 2.4 is built with security as a top priority. This document outlines the security features implemented and best practices for maintaining a secure installation.

## Security Features

### 1. Authentication Security

#### Password Security
- **Bcrypt Hashing**: All passwords are hashed using `password_hash()` with bcrypt algorithm (cost factor: 12)
- **No Plain Text Storage**: Passwords are never stored in plain text
- **Automatic Rehashing**: Support for password rehashing when cost factor changes

#### Session Management
- **Secure Session Configuration**:
  - HTTP-only cookies to prevent XSS access
  - SameSite=Strict to prevent CSRF
  - Automatic session regeneration on login
  - Configurable session timeout (default: 1 hour)
- **Session Validation**: User agent and IP validation available
- **Secure Session Destruction**: Proper cleanup on logout

#### Rate Limiting
- **Login Attempt Limiting**: 
  - Maximum 5 failed attempts (configurable)
  - 15-minute lockout period
  - Per-username tracking
- **Automatic Cleanup**: Old attempts are automatically removed

### 2. Input Validation & Sanitization

#### XSS Prevention
- **Output Encoding**: All user input is encoded using `htmlspecialchars()` with ENT_QUOTES
- **HTML Sanitization**: Content uses whitelist-based tag filtering
- **JavaScript Prevention**: Inline scripts are stripped from user content

#### SQL Injection Protection
- **PDO Prepared Statements**: All database queries use parameterized statements
- **No String Concatenation**: Database queries never concatenate user input
- **Type Validation**: Input types are validated before use

#### CSRF Protection
- **Token Generation**: Cryptographically secure random tokens
- **Token Validation**: All forms require valid CSRF token
- **Token Rotation**: Tokens are regenerated on sensitive operations

### 3. File Upload Security

- **MIME Type Validation**: Server-side MIME type checking using `finfo`
- **Extension Validation**: Whitelist of allowed file extensions
- **Size Limits**: Configurable maximum file size (default: 5MB)
- **Secure Storage**: Uploads stored outside web root when possible
- **Filename Sanitization**: Random filenames to prevent overwrites
- **PHP Execution Prevention**: PHP disabled in upload directories

### 4. HTTP Security Headers

The following security headers are automatically set:

```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';
```

### 5. Access Control

#### Role-Based Access Control (RBAC)
- **Three Roles**: Admin, Editor, Author
- **Permission Hierarchy**: Admin > Editor > Author
- **Function-Level Checks**: Permission checks before sensitive operations

#### Direct Access Prevention
- **Constant Check**: Files check for `BASE_PATH` definition
- **404 on Direct Access**: Includes return 404 if accessed directly

### 6. Database Security

- **Parameterized Queries**: PDO with bound parameters
- **Least Privilege**: Database user should have minimal required permissions
- **Character Encoding**: UTF8MB4 for full Unicode support
- **Foreign Keys**: Referential integrity with cascading deletes

### 7. Error Handling

- **Production Mode**: Errors logged, not displayed
- **Detailed Logging**: Comprehensive error logging for debugging
- **No Information Disclosure**: Generic error messages to users

## Security Best Practices

### For Administrators

1. **Change Default Credentials**
   - Immediately change default admin username and password
   - Use strong passwords (12+ characters, mixed case, numbers, symbols)

2. **Enable HTTPS**
   - Use SSL/TLS certificates
   - Redirect HTTP to HTTPS
   - Update `$secure` flag in session configuration

3. **File Permissions**
   ```bash
   chmod 755 directories
   chmod 644 files
   chmod 600 config/config.php
   chmod 600 config/install.lock
   ```

4. **Keep Software Updated**
   - Update PHP to latest stable version
   - Keep MySQL/MariaDB updated
   - Monitor security advisories

5. **Database Security**
   - Use strong database passwords
   - Create dedicated database user with minimal permissions
   - Regular database backups

6. **Web Server Configuration**
   - Disable directory browsing
   - Hide server version information
   - Configure proper error pages
   - Enable mod_security (Apache)

### For Developers

1. **Code Security**
   - Always use prepared statements
   - Sanitize all input
   - Encode all output
   - Validate file uploads
   - Check CSRF tokens

2. **Testing**
   - Test with security scanners
   - Perform code reviews
   - Test error conditions
   - Validate all user input

3. **Deployment**
   - Remove install.php after installation
   - Set proper error_reporting
   - Disable display_errors in production
   - Enable error logging

## Reporting Security Issues

If you discover a security vulnerability in phpBlog, please report it responsibly:

1. **DO NOT** create a public GitHub issue
2. Email details to: security@phpblog.local
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

We will acknowledge receipt within 48 hours and provide a timeline for fixes.

## Security Checklist

### Initial Setup
- [ ] Run installation wizard
- [ ] Change default admin credentials
- [ ] Delete install.php
- [ ] Set proper file permissions
- [ ] Configure database with strong password
- [ ] Enable HTTPS
- [ ] Configure security headers
- [ ] Test login rate limiting

### Ongoing Maintenance
- [ ] Regular security updates
- [ ] Monitor error logs
- [ ] Review user accounts
- [ ] Backup database regularly
- [ ] Check file upload directory
- [ ] Review and rotate session keys
- [ ] Monitor login attempts
- [ ] Keep PHP and MySQL updated

## Security Audit Log

### Version 2.4
- Implemented bcrypt password hashing
- Added CSRF protection
- Implemented rate limiting
- Added security headers
- Implemented file upload validation
- Added XSS prevention
- Implemented SQL injection protection
- Added secure session management

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [Web Security Cheat Sheet](https://cheatsheetseries.owasp.org/)

---

**Last Updated**: October 2025  
**Version**: 2.4
