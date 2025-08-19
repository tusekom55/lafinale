# Hostinger Deployment Guide for GlobalBorsa

## 🚀 Quick Setup for Your Hostinger Account

Your database credentials are already configured:
- **Database:** u225998063_newe
- **Username:** u225998063_newe
- **Password:** 123456Tubb

## 📋 Step-by-Step Deployment

### 1. Upload Files to Hostinger
1. Login to your Hostinger hPanel
2. Go to **File Manager**
3. Navigate to `public_html` folder
4. Upload all project files to this directory

### 2. Set File Permissions
Make sure these files have proper permissions:
```
chmod 755 api/
chmod 644 *.php
chmod 644 config/*.php
chmod 755 assets/
```

### 3. Run Initial Setup
1. Visit: `https://yourdomain.com/setup.php`
2. This will create all database tables
3. **IMPORTANT:** Delete `setup.php` after running it

### 4. Test the Installation
1. Visit: `https://yourdomain.com/`
2. You should see the market page with crypto data
3. Login with: **admin** / **admin123**

### 5. Set Up Cron Job (Optional but Recommended)
1. In hPanel, go to **Advanced → Cron Jobs**
2. Add this cron job to update market data every 5 minutes:
```bash
*/5 * * * * /usr/bin/php /home/u225998063/public_html/api/update_markets.php
```

## 🔧 Hostinger-Specific Configuration

### PHP Settings
Your Hostinger account should support:
- PHP 8.0+ ✅
- MySQL 8.0 ✅
- cURL extension ✅
- PDO extension ✅

### File Structure on Hostinger
```
/home/u225998063/public_html/
├── config/
├── includes/
├── api/
├── assets/
├── index.php
├── login.php
├── register.php
└── ... (other files)
```

## 🌐 Domain Configuration

### If using a custom domain:
1. Update `SITE_URL` in `config/api_keys.php`
2. Update any hardcoded URLs in the code

### If using Hostinger subdomain:
The site should work immediately with your Hostinger subdomain.

## 🔒 Security Recommendations

### After Deployment:
1. **Delete setup.php** - Very important!
2. Change admin password from default
3. Enable SSL in Hostinger hPanel
4. Update `.htaccess` to force HTTPS

### Enable HTTPS:
Uncomment these lines in `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## 📊 Testing Checklist

- [ ] Homepage loads with market data
- [ ] User registration works
- [ ] User login works
- [ ] Admin login works (admin/admin123)
- [ ] Market data updates automatically
- [ ] Responsive design works on mobile
- [ ] Language switcher works (TR/EN)

## 🐛 Common Issues & Solutions

### Market data not loading:
- Check if your server can access external APIs
- Verify CoinGecko API is not blocked
- Check error logs in hPanel

### Database connection errors:
- Verify database credentials in `config/database.php`
- Make sure database exists in hPanel → MySQL Databases

### File permission errors:
- Set proper permissions via File Manager
- Make sure PHP can write to logs/ directory

## 📞 Support

If you encounter issues:
1. Check the error logs in hPanel
2. Verify all files uploaded correctly
3. Test database connection via hPanel → phpMyAdmin
4. Make sure PHP version is 8.0+

## 🎯 Next Steps After Deployment

1. **Customize branding** - Update site name, colors, logo
2. **Add real payment methods** - Integrate actual IBAN/Papara APIs
3. **Implement security** - Add 2FA, rate limiting, etc.
4. **Add more features** - Trading charts, order book, etc.
5. **SEO optimization** - Add meta tags, sitemap, etc.

---

**Your GlobalBorsa exchange is ready to go! 🚀**

**Default Admin Login:**
- Username: `admin`
- Password: `admin123`

**Remember to change the admin password after first login!**
