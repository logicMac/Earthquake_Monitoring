# Render Deployment Troubleshooting

## Issue: PHP Code Displayed as Plain Text

**Symptoms:**
- You see raw PHP code like `<?php require_once...` instead of rendered HTML
- The page shows PHP source code instead of executing it

**Root Cause:**
Apache is not processing PHP files - it's serving them as plain text.

---

## Solution Steps:

### Step 1: Verify Dockerfile

Your Dockerfile should include this critical section:

```dockerfile
# Enable .htaccess support and PHP handler
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    <FilesMatch \.php$>\n\
        SetHandler application/x-httpd-php\n\
    </FilesMatch>\n\
</Directory>' > /etc/apache2/conf-available/docker-php.conf \
    && a2enconf docker-php
```

This ensures:
- ✅ PHP files are handled by the PHP processor
- ✅ .htaccess files are respected
- ✅ Directory permissions are correct

### Step 2: Update .htaccess

Your `.htaccess` file should be:

```apache
RewriteEngine On

# Prevent directory listing
Options -Indexes

# Set default file
DirectoryIndex index.php

# Error pages
ErrorDocument 404 /index.php

# Ensure PHP files are processed
<FilesMatch \.php$>
    SetHandler application/x-httpd-php
</FilesMatch>
```

**Common mistakes:**
- ❌ `RewriteBase /client_earthquake/` - Wrong for Render (use root)
- ❌ `ErrorDocument 404 /client_earthquake/index.php` - Should be `/index.php`

### Step 3: Test PHP Processing

After deploying, visit:
```
https://your-service.onrender.com/phpinfo.php
```

**Expected:** PHP info page with version, modules, configuration  
**If you see raw code:** PHP is not processing

### Step 4: Check Render Logs

1. Go to Render Dashboard
2. Open your web service
3. Click **"Logs"** tab
4. Look for errors like:
   - `AH01630: client denied by server configuration`
   - `File does not exist: /var/www/html/index.php`
   - `PHP Warning: ...`

### Step 5: Force Rebuild

Sometimes Render caches the old image:

1. In Render Dashboard, go to your web service
2. Click **"Manual Deploy"** → **"Clear build cache & deploy"**
3. Wait for rebuild to complete (5-10 minutes)

---

## Quick Fixes:

### Fix 1: Rebuild with Updated Files

```bash
# Commit the fixed files
git add Dockerfile .htaccess
git commit -m "Fix PHP processing on Render"
git push origin main
```

Render will auto-deploy the changes.

### Fix 2: Verify File Permissions in Container

Add this to your Dockerfile before the CMD line:

```dockerfile
# Debug: Show file permissions
RUN ls -la /var/www/html/ && \
    ls -la /etc/apache2/mods-enabled/php* && \
    ls -la /etc/apache2/conf-enabled/
```

This helps debug permission issues in the build logs.

### Fix 3: Test Locally with Docker

Before pushing to Render, test locally:

```bash
# Build the image
docker build -t earthquake-test .

# Run the container
docker run -p 8080:80 earthquake-test

# Visit http://localhost:8080
```

**If it works locally but not on Render:**
- Check Render environment variables
- Verify database connection
- Check Render's build/deploy logs

---

## Common Render-Specific Issues:

### Issue: Environment Variables Not Loading

**Solution:**
1. Render Dashboard → Your Service → "Environment"
2. Verify all variables are set:
   ```
   DB_HOST=...
   DB_USER=...
   DB_PASS=...
   DB_NAME=earthquake_monitoring
   ```
3. Click **"Save Changes"**
4. Render will auto-redeploy

### Issue: Database Connection Failed

**Symptoms:**
- `Connection refused`
- `Access denied for user`

**Solution:**
1. Use **External Database URL**, not Internal
2. Verify MySQL user has remote access:
   ```sql
   GRANT ALL PRIVILEGES ON earthquake_monitoring.* TO 'user'@'%' IDENTIFIED BY 'password';
   FLUSH PRIVILEGES;
   ```

### Issue: File Not Found (404)

**Solution:**
- Check file exists in repository
- File names are case-sensitive on Linux
- Verify `.htaccess` DirectoryIndex is set

### Issue: Blank White Page

**Symptoms:**
- No PHP code shown
- No HTML rendered
- Blank screen

**Solution:**
1. Check Render logs for PHP errors
2. Enable error display temporarily:
   ```php
   // Add to top of index.php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
3. Common causes:
   - Database connection failed
   - Missing `config/database.php`
   - PHP syntax error

---

## Verification Checklist:

- [ ] Dockerfile includes PHP handler configuration
- [ ] `.htaccess` has correct paths (no `/client_earthquake/`)
- [ ] `phpinfo.php` shows PHP info page (not raw code)
- [ ] Environment variables are set in Render
- [ ] Database is accessible from Render
- [ ] Build logs show no errors
- [ ] Apache access logs show requests

---

## Still Not Working?

### Last Resort Steps:

1. **Check Apache Configuration Inside Container:**
   
   Add to Dockerfile before CMD:
   ```dockerfile
   RUN apache2ctl -M | grep php
   RUN cat /etc/apache2/mods-enabled/php*.conf
   ```

2. **Enable Debug Mode:**
   
   Add to Dockerfile:
   ```dockerfile
   ENV APACHE_LOG_LEVEL=debug
   ```

3. **Contact Render Support:**
   
   If nothing works, open a support ticket with:
   - Your service name
   - Build logs
   - Runtime logs
   - `phpinfo.php` URL

---

## Success Indicators:

✅ `phpinfo.php` shows PHP version 8.2  
✅ `index.php` shows dashboard (not raw code)  
✅ Login page loads correctly  
✅ No PHP errors in Render logs  
✅ Database connection successful  

Once all these are green, your deployment is successful! 🎉
