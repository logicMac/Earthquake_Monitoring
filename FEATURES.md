# System Features Overview

## 🎨 Modern Aesthetic Design

### Login Page
- **Beautiful gradient background** (Purple to Indigo)
- **Glass-morphism effect** on login card
- **Smooth animations** and transitions
- **Clean, professional layout**
- **Responsive design** for all screen sizes

### Dashboard
- **Gradient navigation bar** with purple theme
- **Three status cards** with icons:
  - Current Intensity (Blue gradient)
  - Last Event (Purple gradient)
  - System Status (Green gradient)
- **Live graph** with smooth gradient fill
- **Real-time updates** every 2 seconds
- **Alert banner** that appears during high intensity events

### Manage Recipients Page
- **Consistent navigation** across all pages
- **Modern form design** with focus states
- **Color-coded badges** for categories:
  - Admin: Purple
  - Faculty: Blue
  - Staff: Green
  - Student: Gray
- **Interactive table** with hover effects
- **Action buttons** with smooth transitions

---

## 🔐 Security Features

### Authentication System
✓ Secure login required for all pages
✓ Session-based authentication
✓ Password hashing with bcrypt
✓ Automatic redirect to login if not authenticated
✓ Logout functionality
✓ Last login tracking

### Protected Routes
- `index.php` - Dashboard (requires login)
- `manage_recipients.php` - Recipient management (requires login)
- `logout.php` - Logout handler
- `login.php` - Public login page

---

## 📊 Real-Time Monitoring

### Live Dashboard Features
1. **Current Intensity Display**
   - Large, easy-to-read numbers
   - Updates every 2 seconds
   - Shows value in Gal units

2. **Last Event Information**
   - Displays most recent earthquake intensity
   - Shows timestamp of last event
   - Formatted date and time

3. **System Status**
   - Shows "MONITORING" when active
   - Green color indicates healthy system
   - Updates automatically

4. **Seismic Activity Graph**
   - Beautiful gradient line chart
   - Shows last 20 readings
   - Smooth animations
   - Auto-scaling Y-axis
   - Time-based X-axis

5. **Recent Events Table**
   - Lists all recent earthquakes
   - Color-coded intensity values (red for high)
   - Alert status badges
   - Formatted timestamps

---

## 📱 SMS Alert System

### Automated Bulk SMS
- **Trigger**: Intensity ≥ 80 Gal
- **Provider**: Semaphore API
- **Message Format**:
  ```
  EARTHQUAKE ALERT! Intensity: XX.XX Gal detected at 
  Notre Dame - Siena College of Polomolok. 
  Please proceed to the open field immediately. 
  Duck, Cover, and Hold!
  ```

### SMS Logging
- Every SMS attempt is logged
- Tracks success/failure status
- Links to seismic event
- Records recipient information

### Recipient Management
- Add/remove recipients easily
- Toggle active/inactive status
- Categorize by role (student, faculty, staff, admin)
- Bulk operations support

---

## 🔧 Hardware Integration

### ESP32 Features
- **WiFi Connectivity**: Automatic connection and reconnection
- **HTTP POST**: Sends data to server every 2 seconds during events
- **Local Alerts**: Buzzer and LCD display
- **Real-time Processing**: Immediate response to vibrations

### MPU6050 Sensor
- **3-axis accelerometer**: Measures X, Y, Z acceleration
- **Gal Conversion**: Converts raw data to Gal units
- **Threshold Detection**: Monitors for earthquake levels
- **High Accuracy**: Detects even minor vibrations

### Local Alert System
- **Active Buzzer**: Beeps during shaking
- **LCD Display**: Shows current intensity and status
- **Visual Feedback**: "SHAKING DETECT!" message
- **WiFi Status**: Shows connection state

---

## 🎯 Alert Thresholds

### Three-Level System

**Level 1: Normal (0-24 Gal)**
- No alerts triggered
- Data logged for monitoring
- Dashboard updates normally

**Level 2: Local Alert (25-79 Gal)**
- Buzzer activates
- LCD shows warning
- Data sent to server
- No SMS sent

**Level 3: Emergency (80+ Gal)**
- Buzzer activates
- LCD shows warning
- Data sent to server
- **SMS sent to all active recipients**
- Alert banner appears on dashboard

---

## 💾 Database Structure

### Tables

**admin_users**
- Stores admin login credentials
- Tracks last login time
- Secure password hashing

**seismic_logs**
- Records all earthquake events
- Stores intensity and timestamp
- Tracks alert status

**alert_recipients**
- Manages SMS recipient list
- Categorizes by role
- Active/inactive status

**sms_logs**
- Logs all SMS attempts
- Links to seismic events
- Tracks delivery status

---

## 🌐 API Endpoints

### POST /receive_data.php
**Purpose**: Receives data from ESP32

**Parameters**:
- `intensity` (float): Earthquake intensity in Gal
- `device_id` (string): ESP32 identifier

**Response**:
```json
{
  "status": "success",
  "log_id": 123,
  "intensity": 85.50,
  "alert_sent": true
}
```

### GET /api/get_data.php
**Purpose**: Provides data for dashboard

**Response**:
```json
{
  "latest": {
    "id": 123,
    "intensity": 85.50,
    "timestamp": "2026-04-02 14:30:00",
    "alert_sent": true
  },
  "recent": [...]
}
```

---

## 📈 Performance

### Real-Time Updates
- Dashboard refreshes every 2 seconds
- No page reload required
- Smooth chart animations
- Minimal server load

### Efficient Data Flow
1. ESP32 detects vibration
2. Sends HTTP POST immediately
3. Server processes and logs
4. SMS sent if threshold exceeded
5. Dashboard updates automatically

---

## 🎨 Design System

### Color Palette
- **Primary**: Purple (#667eea)
- **Secondary**: Indigo (#764ba2)
- **Success**: Green (#10b981)
- **Warning**: Yellow (#f59e0b)
- **Danger**: Red (#ef4444)
- **Neutral**: Gray shades

### Typography
- **Font**: Inter (Google Fonts)
- **Weights**: 300, 400, 500, 600, 700, 800, 900
- **Hierarchy**: Clear heading and body text distinction

### Components
- **Cards**: Rounded corners, subtle shadows
- **Buttons**: Gradient backgrounds, hover effects
- **Forms**: Focus states, validation feedback
- **Tables**: Hover rows, color-coded badges
- **Badges**: Rounded pills with category colors

---

## 🔄 System Workflow

```
┌─────────────┐
│   ESP32     │
│  + MPU6050  │
└──────┬──────┘
       │ Detects Vibration
       ↓
┌─────────────┐
│   Buzzer    │ ← Local Alert
│   + LCD     │
└─────────────┘
       │
       │ WiFi HTTP POST
       ↓
┌─────────────┐
│ PHP Server  │
│  + MySQL    │
└──────┬──────┘
       │
       ├─→ Log to Database
       │
       ├─→ Check Threshold
       │
       └─→ Send SMS (if ≥80 Gal)
              │
              ↓
       ┌─────────────┐
       │  Semaphore  │
       │  SMS API    │
       └──────┬──────┘
              │
              ↓
       ┌─────────────┐
       │ Recipients  │
       │   Phones    │
       └─────────────┘
              ↑
              │
       ┌─────────────┐
       │  Dashboard  │ ← Real-time Updates
       │  (Browser)  │
       └─────────────┘
```

---

## ✨ User Experience

### Smooth Interactions
- Instant feedback on all actions
- Loading states for async operations
- Success/error messages
- Confirmation dialogs for destructive actions

### Accessibility
- High contrast colors
- Clear typography
- Keyboard navigation support
- Responsive touch targets

### Mobile Friendly
- Responsive grid layouts
- Touch-optimized buttons
- Readable text sizes
- Collapsible navigation

---

**Built with modern web technologies for Notre Dame - Siena College of Polomolok**
