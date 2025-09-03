# Portfolio Security Checklist

## Database Security
- [x] Use prepared statements for all queries
- [x] Enable strict SQL mode
- [x] Sanitize all user inputs
- [x] Use proper error handling without exposing sensitive data
- [x] Implement rate limiting for contact form

## Authentication & Session Security
- [x] Use secure password hashing (Argon2ID)
- [x] Implement session timeout
- [x] Use CSRF tokens for all forms
- [x] Validate session consistency
- [x] Secure session configuration (httponly, secure, samesite)

## File Upload Security
- [x] Validate file types and extensions
- [x] Check MIME types
- [x] Scan for malicious content
- [x] Generate secure filenames
- [x] Set proper file permissions
- [x] Limit file sizes

## Input Validation & Sanitization
- [x] Sanitize all user inputs
- [x] Validate email addresses
- [x] Validate URLs
- [x] Check input lengths
- [x] Implement spam protection (honeypot, patterns)

## Security Headers
- [x] X-Frame-Options: DENY
- [x] X-Content-Type-Options: nosniff
- [x] X-XSS-Protection: 1; mode=block
- [x] Content-Security-Policy
- [x] Referrer-Policy
- [ ] Strict-Transport-Security (enable when using HTTPS)

## Error Handling & Logging
- [x] Log security events
- [x] Log admin actions
- [x] Hide sensitive error information from users
- [x] Implement proper error pages

## Additional Security Measures
- [x] Rate limiting for forms and admin actions
- [x] IP-based restrictions (optional)
- [x] Secure token generation
- [x] Admin action logging

## Deployment Security Checklist
- [ ] Change default database credentials
- [ ] Enable HTTPS and update security headers
- [ ] Set proper file permissions (644 for files, 755 for directories)
- [ ] Remove or secure phpinfo() and other debug files
- [ ] Configure web server to deny access to sensitive files
- [ ] Enable error logging but disable display_errors in production
- [ ] Update email configuration in config/email.php
- [ ] Run create_admin_user.php to create secure admin account
- [ ] Remove or secure database setup scripts

## Regular Maintenance
- [ ] Monitor error logs regularly
- [ ] Update dependencies and PHP version
- [ ] Review and rotate admin passwords
- [ ] Backup database regularly
- [ ] Monitor for suspicious activity
\`\`\`

```htaccess file=".htaccess"
# Security configurations for Apache
# Place this file in the root directory of your portfolio

# Deny access to sensitive files
<Files ~ "^\.">
    Order allow,deny
    Deny from all
</Files>

<FilesMatch "\.(sql|log|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Deny access to config and scripts directories
<Directory "config">
    Order allow,deny
    Deny from all
</Directory>

<Directory "scripts">
    Order allow,deny
    Deny from all
</Directory>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "DENY"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Uncomment when using HTTPS
    # Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
</IfModule>

# Prevent access to PHP files in uploads directory
<Directory "assets/images">
    <FilesMatch "\.php$">
        Order allow,deny
        Deny from all
    </FilesMatch>
</Directory>

# Enable compression for better performance
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Browser caching for static assets
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/webp "access plus 1 month"
</IfModule>
