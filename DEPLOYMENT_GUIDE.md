# Deployment Guide
**Notre Dame - Siena College of Polomolok Earthquake Monitoring System**

## Network Requirements

### Current Setup (Local Network Only)
- ESP32 and Server must be on the **same WiFi network**
- Server IP: `192.168.1.x` (local network)
- Works only within school premises

---

## Deployment Options

### Option 1: Local Network (Current Setup)
**Best for:** Testing and development

**Requirements:**
- WAMP server running on local computer
- ESP32 connected to same WiFi
- Both devices on same network

**Configuration:**
```cpp
// In Arduino code
const char* serverUrl = "http://192.168.1.100/client_earthquake/receive_data.php";
```

**Pros:**
- ✅ Simple setup
- ✅ No internet required
- ✅ Fast response time
- ✅ Free

**Cons:**
- ❌ Only works on same WiFi
- ❌ Server must always be running
- ❌ Not accessible remotely

---

### Option 2: Port Forwarding (Recommended for School)
**Best for:** School-wide deployment with remote monitoring

**Setup Steps:**

1. **Find Your Local IP:**
   ```bash
   ipconfig
   # Look for IPv4 Address: 192.168.1.100
   ```

2. **Set Static IP:**
   - Go to Network Settings
   - Set static IP (e.g., 192.168.1.100)
   - Note your gateway (router IP)

3. **Configure Router:**
   - Login to router (usually 192.168.1.1)
   - Find "Port Forwarding" or "Virtual Server"
   - Add rule:
     - External Port: 80
     - Internal IP: 192.168.1.100
     - Internal Port: 80
     - Protocol: TCP

4. **Get Public IP:**
   - Visit: https://whatismyipaddress.com/
   - Note your public IP (e.g., 203.123.45.67)

5. **Update Arduino Code:**
   ```cpp
   const char* serverUrl = "http://203.123.45.67/client_earthquake/receive_data.php";
   ```

6. **Configure WAMP:**
   - Edit `httpd-vhosts.conf`
   - Allow access from all IPs:
   ```apache
   <VirtualHost *:80>
       Require all granted
   </VirtualHost>
   ```

**Pros:**
- ✅ Accessible from anywhere
- ✅ No hosting costs
- ✅ Full control

**Cons:**
- ❌ Requires router access
- ❌ Public IP may change
- ❌ Security concerns

**Security Tips:**
- Use strong admin password
- Enable HTTPS (SSL certificate)
- Configure firewall rules
- Regular security updates

---

### Option 3: Cloud Hosting (Best for Production)
**Best for:** Professional deployment with high availability

**Recommended Providers:**

#### A. Shared Hosting (Easiest)
**Providers:** Hostinger, Bluehost, SiteGround
**Cost:** $3-10/month

**Steps:**
1. Purchase hosting plan with PHP & MySQL
2. Upload files via FTP/cPanel
3. Import database via phpMyAdmin
4. Update Arduino code with domain:
   ```cpp
   const char* serverUrl = "http://ndscpm-earthquake.com/receive_data.php";
   ```

#### B. VPS Hosting (More Control)
**Providers:** DigitalOcean, Vultr, Linode
**Cost:** $5-20/month

**Steps:**
1. Create Ubuntu server
2. Install LAMP stack:
   ```bash
   sudo apt update
   sudo apt install apache2 php mysql-server
   ```
3. Upload files and configure
4. Point domain to server IP

#### C. Free Options (Limited)
**Providers:** InfinityFree, 000webhost
**Cost:** Free (with limitations)

**Pros:**
- ✅ Accessible from anywhere
- ✅ Professional domain name
- ✅ High uptime (99.9%)
- ✅ Automatic backups
- ✅ SSL certificates included
- ✅ No router configuration needed

**Cons:**
- ❌ Monthly cost
- ❌ Requires domain name
- ❌ Setup complexity

---

### Option 4: Dynamic DNS (DDNS)
**Best for:** Home/school with changing IP

**Providers:** No-IP, DuckDNS, Dynu (Free)

**Setup:**
1. Register free DDNS account
2. Get hostname: `ndscpm.ddns.net`
3. Install DDNS client on server
4. Configure port forwarding
5. Update Arduino code:
   ```cpp
   const char* serverUrl = "http://ndscpm.ddns.net/client_earthquake/receive_data.php";
   ```

**Pros:**
- ✅ Free domain name
- ✅ Works with changing IP
- ✅ Easy setup

**Cons:**
- ❌ Still requires port forwarding
- ❌ Free domains may expire

---

## Recommended Setup for ND-SCPM

### Phase 1: Testing (Current)
- Use local network setup
- Test all features
- Train staff

### Phase 2: School Deployment
- Implement Port Forwarding + DDNS
- Configure router for external access
- Set up monitoring from admin office

### Phase 3: Production (Future)
- Move to cloud hosting
- Get professional domain
- Enable HTTPS/SSL
- Set up automatic backups

---

## Network Diagram

### Local Network Setup:
```
[ESP32] --WiFi--> [Router] --LAN--> [WAMP Server]
                     |
                  [Admin PC Browser]
```

### Cloud Setup:
```
[ESP32] --WiFi--> [Internet] ---> [Cloud Server]
                                        |
[Admin PC] --Internet--> [Cloud Server Dashboard]
```

### Port Forwarding Setup:
```
[ESP32] --WiFi--> [Internet] ---> [Router] --Port 80--> [WAMP Server]
                                                              |
[Remote PC] --Internet--> [Router] --Port 80--> [WAMP Server]
```

---

## Security Considerations

### For Local Network:
- Change default admin password
- Disable guest WiFi access to server
- Use strong WiFi password

### For Port Forwarding:
- Use HTTPS (SSL certificate)
- Configure firewall rules
- Limit access by IP if possible
- Regular security updates
- Strong admin passwords

### For Cloud Hosting:
- Enable SSL certificate (Let's Encrypt)
- Use environment variables for sensitive data
- Regular backups
- Monitor access logs
- Keep software updated

---

## Troubleshooting

### ESP32 Can't Connect to Server

**Check 1: Same Network?**
```bash
# On server, check IP
ipconfig

# ESP32 should connect to same network
```

**Check 2: Firewall**
```bash
# Windows: Allow port 80
# Control Panel > Firewall > Allow app
```

**Check 3: WAMP Configuration**
```apache
# httpd.conf - Allow network access
Require all granted
```

**Check 4: Test URL**
```bash
# From another device on network
http://192.168.1.100/client_earthquake/receive_data.php
```

### Can't Access from Outside Network

**Check 1: Port Forwarding**
- Verify router configuration
- Check external port is 80

**Check 2: Public IP**
- Confirm public IP hasn't changed
- Use DDNS if IP changes frequently

**Check 3: ISP Restrictions**
- Some ISPs block port 80
- Try port 8080 instead

---

## Cost Comparison

| Option | Setup Cost | Monthly Cost | Difficulty |
|--------|-----------|--------------|------------|
| Local Network | Free | Free | Easy |
| Port Forwarding | Free | Free | Medium |
| DDNS | Free | Free | Medium |
| Shared Hosting | $0-50 | $3-10 | Easy |
| VPS Hosting | $0 | $5-20 | Hard |
| Free Hosting | Free | Free | Easy |

---

## Recommended Configuration

For **Notre Dame - Siena College**, I recommend:

**Short-term (1-3 months):**
- Local network setup for testing
- Train staff and students
- Verify all features work

**Medium-term (3-6 months):**
- Implement Port Forwarding + DDNS
- Enable remote monitoring
- Set up backup system

**Long-term (6+ months):**
- Move to cloud hosting
- Professional domain: earthquake.ndscpm.edu.ph
- Enable HTTPS
- Implement advanced analytics

---

## Support

For technical assistance:
- Check Arduino Serial Monitor for connection errors
- Verify server logs in WAMP
- Test API endpoint manually
- Review firewall settings

**Contact IT Department for:**
- Router configuration
- Network permissions
- Firewall rules
- Domain registration
