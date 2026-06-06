# InfinityFree Deployment Guide
## ND-SCPM Earthquake Monitoring System

## Step 1: Get Your Database Credentials

1. Login to InfinityFree Control Panel
2. Go to **MySQL Databases**
3. Note down these details:
   ```
   MySQL Hostname: sqlXXX.infinityfreeapp.com
   MySQL Username: epizXXXXX_username
   MySQL Password: (your password)
   Database Name: epizXXXXX_dbname
   ```

## Step 2: Update Database Configuration

1. Open `config/database.php`
2. Update these lines with YOUR credentials:

```php
define('DB_HOST', 'sqlXXX.infinityfreeapp.com');  // Your MySQL hostname
define('DB_USER', 'epizXXXXX_username');          // Your MySQL username
define('DB_PASS', 'your_password_here');          // Your MySQL password
define('DB_NAME', 'epizXXXXX_dbname');            // Your database name
```

## Step 3: Import Database

1. Go to InfinityFree Control Panel
2. Click **phpMyAdmin**
3. Select your database (epizXXXXX_dbname)
4. Click **Import** tab
5. Choose file: `database/schema.sql`
6. Click **Go**
7. Wait for success message

## Step 4: Upload Files

Upload ALL files to `htdocs` folder via:
- File Manager (in control panel), OR
- FTP client (FileZilla)

**Important folders to upload:**
```
htdocs/
├── api/
├── arduino/
├── config/
├── database/
├── includes/
├── index.php
├── login.php
├── create_admin.php
├── manage_recipients.php
├── reports.php
├── receive_data.php
└── ... (all other files)
```

## Step 5: Create Admin User

1. Visit: `https://your-site.infinityfreeapp.com/create_admin.php`
2. You should see success message
3. Default credentials:
   - Username: `adminSienna`
   - Password: `admin123`

## Step 6: Test Your Site

1. Visit: `https://your-site.infinityfreeapp.com/debug_check.php`
2. Check all items show ✅
3. If you see ❌, fix those issues
4. Visit: `https://your-site.infinityfreeapp.com/login.php`
5. Login with admin credentials

## Common Errors & Solutions

### Error 500 - Internal Server Error

**Cause 1: Database credentials not updated**
- Solution: Update `config/database.php` with correct credentials

**Cause 2: Database not imported**
- Solution: Import `database/schema.sql` via phpMyAdmin

**Cause 3: No admin user**
- Solution: Run `create_admin.php`

**Cause 4: Session issues**
- Solution: Already fixed in updated `includes/auth.php`

### Error: "Database connection error"

1. Check database credentials in `config/database.php`
2. Make sure database exists in phpMyAdmin
3. Make sure tables are imported

### Error: "Invalid username or password"

1. Make sure you ran `create_admin.php`
2. Check if admin_users table has data:
   ```sql
   SELECT * FROM admin_users;
   ```
3. If empty, run `create_admin.php` again

### Login redirects to login page (loop)

1. Clear browser cookies
2. Try different browser
3. Check if sessions are working in `debug_check.php`

## InfinityFree Limitations

⚠️ **Important Notes:**
1. **No real-time updates** - InfinityFree has request limits
2. **Slow database** - Free hosting has performance limits
3. **No cron jobs** - Can't run scheduled tasks
4. **Limited bandwidth** - 10GB/month
5. **No email** - SMS alerts work, but email might not

## Recommended for Production

For actual deployment at ND-SCPM, consider:
1. **Paid hosting** (₱100-300/month)
2. **Local server** (Raspberry Pi or old PC)
3. **School server** (if available)

InfinityFree is good for:
- ✅ Testing and demos
- ✅ Thesis presentation
- ✅ Development
- ❌ NOT for 24/7 production use

## Support

If you still have issues:
1. Run `debug_check.php` and screenshot results
2. Check InfinityFree error logs in control panel
3. Check browser console for JavaScript errors (F12)

## Security Notes

After deployment:
1. Delete `debug_check.php` (security risk)
2. Delete `create_admin.php` (after creating admin)
3. Change default admin password
4. Update SMS API key in `config/database.php`
