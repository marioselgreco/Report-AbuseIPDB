<?php
// Path to the mail log file
$logFile = '/var/log/mail.log';

// Define time frame (in seconds) to consider for repeated login attempts
$timeFrame = 3600; // 60 minutes

// AbuseIPDB API Key
$apiKey = 'add_your_api';

// AbuseIPDB category ID for email spam
$categoryID = "11,15,17";

// File to store last reported time for each IP address
$lastReportedFile = '/home/last_reported.json';

// Function to report IP address to AbuseIPDB
function reportToAbuseIPDB($ipAddress, $apiKey, $categoryID) {
    // Prepare POST data
    $postData = http_build_query([
        'ip' => $ipAddress,
        'categories' => $categoryID,
        'comment' => 'Automated report from mail server logs'
    ]);

    // Set up cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.abuseipdb.com/api/v2/report');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Key: ' . $apiKey,
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if ($response === false) {
        echo 'Error reporting IP address to AbuseIPDB: ' . curl_error($ch);
    } else {
        // Decode the JSON response
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['data']['ipAddress'])) {
            echo "Reported IP address: {$responseData['data']['ipAddress']} to AbuseIPDB\n";
        } else {
            echo "Failed to report IP address to AbuseIPDB: " . $response . "\n";
        }
    }

    // Close cURL session
    curl_close($ch);
}

// Function to load last reported times from file
function loadLastReportedTimes($file) {
    if (file_exists($file)) {
        $data = file_get_contents($file);
        return json_decode($data, true);
    }
    return [];
}

// Function to save last reported times to file
function saveLastReportedTimes($times, $file) {
    file_put_contents($file, json_encode($times));
}

// Load last reported times
$lastReportedTimes = loadLastReportedTimes($lastReportedFile);

// Open the log file
$handle = fopen($logFile, 'r');

// Array to store login attempts per IP address
$loginAttempts = [];

// Read the log file line by line
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        // Check if the line contains login attempt information
        if (strpos($line, 'postfix/smtpd') !== false || strpos($line, 'dovecot: imap-login') !== false) {
            // Check if the line contains phrases indicating normal disconnections
            if (strpos($line, 'Disconnected: Inactivity') !== false || strpos($line, 'Disconnected: Aborted login by logging out') !== false) {
                continue; // Skip this line
            }

            // Extract the IP address from the log entry
            preg_match('/(?:[0-9]{1,3}\.){3}[0-9]{1,3}/', $line, $matches);
            if (!empty($matches)) {
                $ipAddress = $matches[0];

                // Check if the IP address was reported in the last 30 minutes
                if (!isset($lastReportedTimes[$ipAddress]) || (time() - $lastReportedTimes[$ipAddress]) >= $timeFrame) {
                    // Report IP address to AbuseIPDB
                    reportToAbuseIPDB($ipAddress, $apiKey, $categoryID);

                    // Update last reported time for the IP address
                    $lastReportedTimes[$ipAddress] = time();
                }
            }
        }
    }

    // Save last reported times to file
    saveLastReportedTimes($lastReportedTimes, $lastReportedFile);

    fclose($handle);
} else {
    // Error opening the file
    echo "Error opening the log file.";
}
