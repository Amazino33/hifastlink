#!/bin/bash
# MikroTik RADIUS Test Script
# Run this on your Ubuntu server to test RADIUS communication

echo "=== MikroTik RADIUS Test ==="

# Test 1: Check if RADIUS is running
echo "1. Checking FreeRADIUS status..."
if pgrep freeradius > /dev/null; then
    echo "✅ FreeRADIUS is running"
else
    echo "❌ FreeRADIUS is not running"
    sudo systemctl start freeradius
fi

# Test 2: Test RADIUS authentication
echo "2. Testing RADIUS authentication..."
echo "Testing user: testuser"
radtest testuser testpass 142.93.47.189 0 YourSharedSecret123

# Test 3: Check RADIUS clients
echo "3. Checking RADIUS clients..."
sudo grep -A 5 "client" /etc/freeradius/3.0/clients.conf

# Test 4: Check user in database
echo "4. Checking user in RADIUS database..."
mysql -u admin -p1a2345678B hifastlink -e "SELECT username, attribute, value FROM radcheck WHERE username='testuser';"

# Test 5: Check recent accounting
echo "5. Checking recent accounting data..."
mysql -u admin -p1a2345678B hifastlink -e "SELECT username, acctstarttime, acctstoptime, acctinputoctets, acctoutputoctets FROM radacct ORDER BY acctstarttime DESC LIMIT 5;"

echo "=== Test Complete ==="
echo "If authentication test shows 'Access-Accept', RADIUS is working!"
echo "Check Laravel logs for accounting data: tail -f storage/logs/laravel.log"