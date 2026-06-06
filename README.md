# Arduino-Based Earthquake Detection & Monitoring System
**Notre Dame - Siena College of Polomolok**

## System Overview
Real-time seismic detection system using ESP32, MPU6050 sensor, with web-based monitoring and automated SMS alerts.

## Hardware Components
- **ESP32 DevKit V1** - Main microcontroller with WiFi
- **MPU6050** - 3-axis accelerometer (I2C: SDA=21, SCL=22)
- **Active Buzzer** - GPIO 25 for local alerts
- **I2C LCD 16x2** - Display (I2C Address: 0x27)

## Software Stack
- **Backend**: PHP 7.4+ with MySQL
- **Frontend**: Tailwind CSS + Chart.js
- **SMS API**: Semaphore (https://semaphore.co)

## Installation

### 1. Database Setup
```bash
# Import the database schema
mysql -u root -p < database/schema.sql
```

### 2. Configure Database Connection
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'earthquake_monitoring');
```

### 3. Configure SMS API
Edit `config/database.php`:
```php
define('SMS_API_KEY', 'your_semaphore_api_key');
```

Get your API key from: https://semaphore.co

### 4. Arduino Setup
1. Install Arduino IDE
2. Install required libraries:
   - MPU6050 by Electronic Cats
   - LiquidCrystal_I2C
   - WiFi (built-in)
   - HTTPClient (built-in)

3. Open `arduino/esp32_earthquake_detector/esp32_earthquake_detector.ino`
4. Update WiFi credentials:
```cpp
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";
```

5. Update server URL:
```cpp
const char* serverUrl = "http://YOUR_SERVER_IP/receive_data.php";
```

6. Upload to ESP32

## Hardware Wiring

### MPU6050 to ESP32
- VCC → 3.3V
- GND → GND
- SDA → GPIO 21
- SCL → GPIO 22

### LCD I2C to ESP32
- VCC → 5V
- GND → GND
- SDA → GPIO 21
- SCL → GPIO 22

### Active Buzzer to ESP32
- Positive → GPIO 25
- Negative → GND

## Usage

### Login to System
```
http://localhost/client_earthquake/login.php
```

**Default Credentials:**
- Username: `admin`
- Password: `admin123`

### Access Dashboard
After login, you'll be redirected to the dashboard automatically.

### Manage Recipients
Click "Manage Recipients" button in the navigation bar.

### API Endpoint (ESP32)
```
POST http://localhost/client_earthquake/receive_data.php
Parameters: intensity, device_id
```

## Alert Thresholds
- **Low Threshold**: 25 Gal (local buzzer only)
- **High Threshold**: 80 Gal (SMS alerts sent)

## Features
✓ Secure admin authentication system
✓ Real-time seismic monitoring
✓ Beautiful gradient UI with modern design
✓ Live graph visualization with smooth animations
✓ Automated bulk SMS alerts
✓ Local buzzer and LCD warnings
✓ Recipient management system
✓ SMS delivery logging
✓ Responsive design for all devices

## System Workflow
1. ESP32 detects vibrations via MPU6050
2. Local alert: Buzzer + LCD display
3. Data sent to PHP server via WiFi
4. Server logs data in MySQL
5. If threshold exceeded: Bulk SMS sent
6. Dashboard updates in real-time

## Troubleshooting

### ESP32 won't connect to WiFi
- Check SSID and password
- Ensure 2.4GHz WiFi (ESP32 doesn't support 5GHz)

### MPU6050 not detected
- Check I2C wiring
- Try I2C scanner sketch to verify address

### SMS not sending
- Verify Semaphore API key
- Check account balance
- Review `sms_logs` table for errors

### Dashboard not updating
- Check `receive_data.php` is accessible
- Verify MySQL connection
- Check browser console for errors

## License
Educational use - Notre Dame - Siena College of Polomolok

## Support
For technical support, contact the IT department.
