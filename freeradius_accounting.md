# FreeRADIUS Accounting Configuration for MikroTik

## 1. Enable Accounting in FreeRADIUS
Edit `/etc/freeradius/3.0/sites-enabled/default`:

```
accounting {
    # ... existing configuration ...

    # Add MikroTik accounting
    detail {
        filename = ${radacctdir}/detail-%Y%m%d
    }

    # Send accounting data to Laravel
    exec {
        program = "/usr/bin/curl -X POST -H 'Content-Type: application/x-www-form-urlencoded' --data-binary @- http://your-hostinger-domain.com/api/radius/accounting"
        input_pairs = request
        output_pairs = reply
        wait = yes
        shell_escape = yes
    }

    # Update database
    sql {
        reference = "%{tolower:type.%{Acct-Status-Type}}"
    }

    # Log to files
    radutmp
    sradutmp
}
```

## 2. Configure Accounting Attributes
Edit `/etc/freeradius/3.0/dictionary`:

```
# MikroTik specific attributes
ATTRIBUTE Mikrotik-Rate-Limit 14988 string
ATTRIBUTE Mikrotik-Group 14989 string
ATTRIBUTE Mikrotik-Advertise-URL 14990 string
ATTRIBUTE Mikrotik-Advertise-Interval 14991 integer
ATTRIBUTE Mikrotik-Recv-Limit 14992 integer
ATTRIBUTE Mikrotik-Xmit-Limit 14993 integer
```

## 3. Restart FreeRADIUS
```bash
sudo systemctl restart freeradius
```

## 4. Test Accounting
```bash
# Monitor RADIUS logs
sudo tail -f /var/log/freeradius/radius.log

# Check accounting data in database
mysql -u admin -p hifastlink -e "SELECT * FROM radacct ORDER BY acctstarttime DESC LIMIT 1;"
```

## 5. Laravel Accounting Endpoint
Your Laravel endpoint should receive:
- `Acct-Status-Type`: Start, Stop, Interim-Update
- `User-Name`: Username
- `Acct-Session-Time`: Session duration
- `Acct-Input-Octets`: Download bytes
- `Acct-Output-Octets`: Upload bytes
- `Framed-IP-Address`: User IP
- `Calling-Station-Id`: MAC address