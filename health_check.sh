#!/bin/bash

# HiFastLink System Health Check Script
# Run this daily to ensure all services are running

echo "=== HiFastLink System Health Check ==="
echo "Date: $(date)"
echo

# Check FreeRADIUS
echo "1. Checking FreeRADIUS..."
if pgrep freeradius > /dev/null; then
    echo "✅ FreeRADIUS is running"
else
    echo "❌ FreeRADIUS is not running"
    sudo systemctl start freeradius
    echo "🔄 Attempted to restart FreeRADIUS"
fi

# Check MySQL
echo "2. Checking MySQL..."
if pgrep mysql > /dev/null; then
    echo "✅ MySQL is running"
else
    echo "❌ MySQL is not running"
    sudo systemctl start mysql
    echo "🔄 Attempted to restart MySQL"
fi

# Check Apache/Nginx
echo "3. Checking Web Server..."
if pgrep apache2 > /dev/null || pgrep nginx > /dev/null; then
    echo "✅ Web server is running"
else
    echo "❌ Web server is not running"
fi

# Check RADIUS database connectivity
echo "4. Checking RADIUS Database..."
mysql -u admin -p1a2345678B hifastlink -e "SELECT COUNT(*) as users FROM radcheck;" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✅ RADIUS database is accessible"
else
    echo "❌ RADIUS database connection failed"
fi

# Check Laravel queue worker (if using queues)
echo "5. Checking Laravel Services..."
# Add checks for Laravel scheduler, queue workers, etc.

echo
echo "=== Health Check Complete ==="
echo "Check the logs above for any issues that need attention."