# Quick Setup Guide
**Notre Dame - Siena College of Polomolok Earthquake Monitoring System**

## 🚀 Quick Start (5 Minutes)

### Step 1: Import Database
Open your browser and go to phpMyAdmin:
```
http://localhost/phpmyadmin
```

1. Click "Import" tab
2. Choose file: `database/schema.sql`
3. Click "Go"

### Step 2: Configure SMS API
Edit `config/database.php` and add your Semaphore API key:
```php
define('SMS_API_KEY', 'your_actual_api_key_here');
```

Get your free API key at: https://semaphore.co

### Step 3: Access the System
Open your browser:
```
http://localhost/client_earthquake/login.php
```

**Login with:**
- Username: `admin`
- Password: `admin123`

### Step 4: Add Recipients
1. Click "Manage Recipients" in the navigation
2. Add phone numbers for students, faculty, and staff
3. Make sure numbers are in format: `09171234567`

---

## 🔧 Arduino ESP32 Setup

### Install Arduino IDE
Download from: https://www.arduino.cc/en/software

### Install Required Libraries
In Arduino IDE, go to: Tools → Manage Libraries

Install these libraries:
1. **MPU6050** by Electronic Cats
2. **LiquidCrystal_I2C** by Frank de Brabander

### Configure ESP32 Code
Open: `arduino/esp32_earthquake_detector/esp32_earthquake_detector.ino`

Update these lines:
```cpp
const char* ssid = "YourWiFiName";
const char* password = "YourWiFiPassword";
const char* serverUrl = "http://192.168.1.100/client_earthquake/receive_data.php";
```

**To find your server IP:**
- Open Command Prompt
- Type: `ipconfig`
- Look for "IPv4 Address"

### Upload to ESP32
1. Connect ESP32 to computer via USB
2. Select: Tools → Board → ESP32 Dev Module
3. Select: Tools → Port → (your COM port)
4. Click Upload button

---

## 🔌 Hardware Wiring

### MPU6050 Sensor
```
MPU6050    →    ESP32
VCC        →    3.3V
GND        →    GND
SDA        →    GPIO 21
SCL        →    GPIO 22
```

### LCD Display (I2C)
```
LCD        →    ESP32
VCC        →    5V
GND        →    GND
SDA        →    GPIO 21
SCL        →    GPIO 22
```

### Active Buzzer
```
Buzzer     →    ESP32
Positive   →    GPIO 25
Negative   →    GND
```

---

## ✅ Testing

### Test 1: Check Database
Go to phpMyAdmin and verify these tables exist:
- admin_users
- seismic_logs
- alert_recipients
- sms_logs

### Test 2: Login to Dashboard
1. Go to login page
2. Enter admin credentials
3. You should see the dashboard with 3 cards

### Test 3: Add Test Recipient
1. Click "Manage Recipients"
2. Add your own phone number
3. Category: Admin
4. Click "Add Recipient"

### Test 4: ESP32 Connection
1. Open Arduino Serial Monitor (115200 baud)
2. You should see:
   - "Connecting WiFi..."
   - "WiFi Connected"
   - IP address displayed

### Test 5: Shake the Sensor
1. Gently shake the MPU6050
2. Buzzer should beep
3. LCD should show "SHAKING DETECT!"
4. Dashboard should update with new reading

---

## 🎯 Alert Thresholds

| Intensity | Action |
|-----------|--------|
| 0-25 Gal | Normal monitoring |
| 25-79 Gal | Local buzzer + LCD alert |
| 80+ Gal | Buzzer + LCD + SMS to all recipients |

---

## 🆘 Troubleshooting

### Problem: Can't login
**Solution:** Make sure you imported the database schema. Default password is `admin123`

### Problem: ESP32 won't connect to WiFi
**Solution:** 
- Check SSID and password are correct
- Make sure you're using 2.4GHz WiFi (not 5GHz)
- ESP32 doesn't support 5GHz networks

### Problem: MPU6050 not detected
**Solution:**
- Check wiring connections
- Make sure SDA/SCL are on GPIO 21/22
- Try different I2C address (0x68 or 0x69)

### Problem: SMS not sending
**Solution:**
- Verify your Semaphore API key in `config/database.php`
- Check your Semaphore account has credits
- Phone numbers must be in format: 09XXXXXXXXX

### Problem: Dashboard not updating
**Solution:**
- Check ESP32 is connected to WiFi
- Verify server URL in Arduino code is correct
- Open browser console (F12) to check for errors

---

## 📱 Change Admin Password

After first login, you should change the default password:

1. Go to phpMyAdmin
2. Open `earthquake_monitoring` database
3. Click `admin_users` table
4. Click "Edit" on the admin user
5. In password field, enter: `MD5` and your new password
6. Click "Go"

Or use this SQL:
```sql
UPDATE admin_users 
SET password = '$2y$10$YOUR_HASHED_PASSWORD' 
WHERE username = 'admin';
```

---

## 🎨 System Features

### Beautiful Modern UI
- Gradient purple theme
- Smooth animations
- Responsive design
- Real-time updates every 2 seconds

### Security
- Session-based authentication
- Password hashing with bcrypt
- Protected admin pages

### Monitoring
- Live seismic activity graph
- Current intensity display
- Event history table
- System status indicators

### Alerts
- Automated SMS via Semaphore
- Local buzzer warnings
- LCD display messages
- Alert status tracking

---

## 📞 Support

For technical issues:
- Check the main README.md
- Review Arduino Serial Monitor output
- Check browser console for JavaScript errors
- Verify database connections in phpMyAdmin

---

**System developed for Notre Dame - Siena College of Polomolok**
*Earthquake Detection & Monitoring System v1.0*
