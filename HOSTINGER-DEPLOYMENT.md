# Hostinger Deployment Guide

This guide will help you deploy your Laravel chat application to Hostinger hosting.

## Pre-deployment Checklist ✅

- [x] Assets compiled for production (`npm run production`)
- [x] Production environment files created (`.env.production`)
- [x] Storage permissions script ready (`setup-storage.sh`)
- [x] Database configuration verified
- [x] .htaccess file present in public folder

## Hostinger Requirements

- PHP 8.2 or higher
- MySQL database
- Composer (usually available on Hostinger)
- SSH access (recommended for initial setup)

## Step-by-Step Deployment

### 1. Prepare Your Files

Before uploading, ensure you have:
- All compiled assets in `public/` folder
- Updated `.env.production` file with your Hostinger settings
- Storage permissions script

### 2. Database Setup

1. **Create MySQL Database:**
   - Log into Hostinger hPanel
   - Go to "Databases" → "MySQL Databases"
   - Create a new database and user
   - Note down: database name, username, password

2. **Update Environment File:**
   - Copy `.env.production` to `.env`
   - Update database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

### 3. File Upload

**Option A: File Manager (Easy)**
1. Log into Hostinger hPanel
2. Open "File Manager"
3. Navigate to `public_html` folder
4. Upload all files (except node_modules, .git)
5. Extract if uploaded as zip

**Option B: SSH/FTP (Recommended)**
1. Connect via SSH or SFTP
2. Upload all files to `public_html`
3. Ensure proper file structure

### 4. Post-Upload ConfigurationAdil123@

1. **Set Storage Permissions:**
   ```bash
   # Via SSH or run the script
   bash setup-storage.sh
   # OR manually:
   chmod -R 755 storage bootstrap/cache
   ```

2. **Create Storage Link:**
   ```bash
   php artisan storage:link
   ```

3. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

4. **Run Database Migrations:**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

5. **Clear Caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

### 5. Environment Configuration

Update your `.env` file with production values:

```env
# Basic Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (from step 2)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_hostinger_database
DB_USERNAME=your_hostinger_user
DB_PASSWORD=your_hostinger_password

# Email (Hostinger SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"

# Pusher (for real-time chat)
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

### 6. Domain Configuration

1. **Point Domain to Hostinger:**
   - Update nameservers to Hostinger's
   - Or add domain in hPanel

2. **SSL Certificate:**
   - Enable SSL in hPanel
   - Update APP_URL to use https://

### 7. WordPress Integration (if needed)

If using WordPress integration:
1. Set up WordPress database connection
2. Update WP-related environment variables
3. Ensure WordPress site is accessible

## Troubleshooting

### Common Issues:

**500 Server Error:**
- Check storage permissions: `chmod -R 755 storage bootstrap/cache`
- Verify .env file exists and is readable
- Check error logs in hPanel

**Database Connection Error:**
- Verify database credentials in .env
- Ensure database exists and user has permissions
- Check if host should be 'localhost' or specific IP

**File Upload Issues:**
- Check file upload limits in PHP settings
- Verify storage permissions
- Ensure storage/app/public exists

**Real-time Features Not Working:**
- Verify Pusher credentials
- Check if WebSockets are supported by Hostinger
- Test with WebSocket services

### Log Files:
- Application logs: `storage/logs/laravel.log`
- Server logs: Available in hPanel

## Performance Optimization

1. **Enable Caching:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Optimize Autoloader:**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. **Queue Configuration:**
   - For production, consider using database queue
   - Set up queue worker if needed

## Security Checklist

- [x] APP_DEBUG=false in production
- [x] APP_ENV=production
- [x] Strong APP_KEY generated
- [x] Database credentials secure
- [x] File permissions properly set
- [x] SSL certificate enabled
- [x] Error reporting disabled in production

## Maintenance

### Regular Tasks:
- Monitor storage/logs/laravel.log
- Keep Laravel and dependencies updated
- Backup database regularly
- Monitor disk space usage

### Updates:
1. Test locally first
2. Backup database and files
3. Upload new files
4. Run migrations if needed
5. Clear caches

## Support

If you encounter issues:
1. Check Hostinger documentation
2. Review Laravel deployment guides
3. Check application logs
4. Contact Hostinger support if server-related

---

**Note:** Always test your deployment process on a staging environment before deploying to production.
