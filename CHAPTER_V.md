# CHAPTER V
# EARTHQUAKE MONITORING AND SMS ALERT SYSTEM FOR ND-SCPM

## System Overview

The Earthquake Monitoring and SMS Alert System is a web-based real-time seismic monitoring solution designed to detect earthquake activities and provide immediate alerts to stakeholders. The system utilizes ESP32 microcontrollers integrated with MPU6050 accelerometer sensors to continuously monitor ground acceleration and seismic intensity. These IoT devices transmit real-time data to a PHP-based web server via HTTPS POST requests, where the data is processed, stored, and analyzed using the Modified Mercalli Intensity (MMI) scale and magnitude estimation algorithms.

The system features a comprehensive dashboard that displays current seismic readings, historical event logs, and real-time intensity graphs. When seismic activity exceeds predefined thresholds, the system automatically triggers local alerts through buzzers and LCD displays on the hardware devices, and simultaneously sends SMS notifications to registered recipients including students, faculty, and staff via the UniSMS API. The admin panel allows administrators to manage alert recipients, view system statistics, generate reports on seismic events and SMS delivery, and monitor system health. The system maintains detailed logs of all seismic events and SMS transmission attempts, ensuring accountability and enabling post-event analysis.

## 5.2 System Objective

The System aims to provide a comprehensive earthquake monitoring solution with the following objectives:

1. To store, access, and manage real-time seismic data including intensity, magnitude, and MMI scale readings.
2. To allow administrators to manage SMS alert recipients and categorize them by role (students, faculty, staff).
3. To monitor and analyze seismic activity patterns and generate operational reports for event tracking.
4. To provide immediate local alerts through buzzer and LCD displays when earthquake thresholds are exceeded.
5. To send automated SMS alerts to registered recipients when seismic intensity reaches critical levels.
6. To maintain comprehensive logs of all seismic events and SMS transmission attempts for audit purposes.
7. To provide a real-time dashboard for monitoring current seismic conditions and historical data.
