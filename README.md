# Mail Server Log Monitor and Report to AbuseIPDB Script 

This script monitors mail server logs and reports suspicious IP addresses to AbuseIPDB. It helps identify potential hackers and bothacks by tracking login attempts.

**Features:**

* Monitors mail server logs for login attempts (via "postfix/smtpd" or "dovecot: imap-login").
* Filters out normal disconnections (inactivity, logout).
* Reports suspicious IP addresses to AbuseIPDB based on a configurable time frame.
* Saves reported IP addresses and timestamps for future reference.

**Configuration:**

* `logFile`: Path to the mail server log file (e.g., `/var/log/mail.log`).
* `timeFrame`: Time window (in seconds) to consider repeated login attempts (default: 3600 seconds - 1 hour).
* `apiKey`: Your AbuseIPDB API key (replace with your actual key).
* `categoryID`: AbuseIPDB category ID for hacking, email spam, spoofing (default: 11,15,17).
* `lastReportedFile`: Path to the file storing previously reported IP addresses and timestamps.
