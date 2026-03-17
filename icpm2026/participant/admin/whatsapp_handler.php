<?php
session_start();
include 'dbconnection.php';

header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Ensure media_url column exists
$colCheck = mysqli_query($con, "SHOW COLUMNS FROM whatsapp_queue LIKE 'media_url'");
if (mysqli_num_rows($colCheck) == 0) {
    mysqli_query($con, "ALTER TABLE whatsapp_queue ADD COLUMN media_url VARCHAR(255) DEFAULT NULL AFTER message");
}

// Ensure whatsapp_settings table exists
$tableCheck = mysqli_query($con, "SHOW TABLES LIKE 'whatsapp_settings'");
if (mysqli_num_rows($tableCheck) == 0) {
    $sql = "CREATE TABLE whatsapp_settings (
        id INT(1) NOT NULL DEFAULT 1,
        transport_mode VARCHAR(20) DEFAULT 'node',
        node_url VARCHAR(255) DEFAULT 'http://127.0.0.1:3000',
        http_api_url VARCHAR(255) DEFAULT '',
        http_api_token VARCHAR(255) DEFAULT '',
        PRIMARY KEY (id)
    )";
    mysqli_query($con, $sql);
    // Insert default
    mysqli_query($con, "INSERT INTO whatsapp_settings (id, transport_mode, node_url, http_api_url, http_api_token) VALUES (1, 'node', 'http://127.0.0.1:3000', '', '')");
}

// Load Settings
$settingsQuery = mysqli_query($con, "SELECT * FROM whatsapp_settings WHERE id = 1");
$settings = mysqli_fetch_assoc($settingsQuery);

if (!$settings) {
    // Fallback if row deleted
    mysqli_query($con, "INSERT INTO whatsapp_settings (id, transport_mode, node_url, http_api_url, http_api_token) VALUES (1, 'node', 'http://127.0.0.1:3000', '', '')");
    $settings = ['transport_mode' => 'node', 'node_url' => 'http://127.0.0.1:3000', 'http_api_url' => '', 'http_api_token' => ''];
}

$waTransportMode = $settings['transport_mode'];
$nodeServiceUrl  = $settings['node_url']; // Overrides hardcoded logic
$httpApiSendUrl  = $settings['http_api_url'];
$httpApiToken    = $settings['http_api_token'];

$action = isset($_POST['action']) ? $_POST['action'] : '';


function formatWaNumber($phone) {
    // 1. Remove any spaces
    $phone = str_replace(' ', '', $phone);

    // 5. IF START WITH +971 REMOVE +
    if (substr($phone, 0, 4) === '+971') {
        $phone = substr($phone, 1);
    }

    // 4. IF START WITH 00971 REMOVE 00
    if (substr($phone, 0, 5) === '00971') {
        $phone = substr($phone, 2);
    }

    // 2. IF START 05 AND TOTAL DIGITS 10 REMOVE 0 AND ADD 971
    // Count only digits for this check
    $digitCount = preg_match_all("/[0-9]/", $phone);
    if (substr($phone, 0, 2) === '05' && $digitCount === 10) {
        $phone = '971' . substr($phone, 1);
    }

    // 3. IF START WITH 5 ADD 971 BEFORE
    if (substr($phone, 0, 1) === '5') {
        $phone = '971' . $phone;
    }

    // Final clean: ensure only digits are sent to API
    return preg_replace('/[^0-9]/', '', $phone);
}

function callNodeService($endpoint, $method = 'GET', $data = []) {
    global $nodeServiceUrl;
    $ch = curl_init($nodeServiceUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_PROXY, ''); // Bypass any system proxy

    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        return ['status' => 'error', 'message' => 'Node Service Error: ' . curl_error($ch)];
    }
    
    // Add logging to debug response
    if (isset($_GET['debug'])) {
        var_dump($response);
        exit;
    }

    curl_close($ch);
    
    // Attempt to decode JSON
    $decoded = json_decode($response, true);
    
    // If decoding fails, return raw response for debugging
    if (json_last_error() !== JSON_ERROR_NONE) {
        $preview = strip_tags(substr($response, 0, 200));
        return [
            'status' => 'error', 
            'message' => 'Invalid JSON from Node Service. Response: ' . $preview, 
            'raw_response' => $response
        ];
    }
    
    return $decoded;
}

function callHttpApiService($data = []) {
    global $httpApiSendUrl, $httpApiToken;
    if (empty($httpApiSendUrl)) {
        return ['status' => 'error', 'message' => 'HTTP API not configured'];
    }
    $ch = curl_init($httpApiSendUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_PROXY, '');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $headers = ['Content-Type: application/json'];
    if (!empty($httpApiToken)) {
        $headers[] = 'Authorization: Bearer ' . $httpApiToken;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        return ['status' => 'error', 'message' => 'HTTP API Error: ' . curl_error($ch)];
    }
    curl_close($ch);
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['status'])) {
        return $decoded;
    }
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['status' => 'success'];
    }
    return ['status' => 'error', 'message' => 'HTTP API unexpected response'];
}

if ($action === 'get_status') {
    if ($waTransportMode === 'http_api') {
        echo json_encode(['status' => 'READY', 'ready' => true, 'qr' => null]);
    } else {
        $res = callNodeService('/status');
        echo json_encode($res);
    }
    exit;
}

if ($action === 'logout') {
    if ($waTransportMode === 'http_api') {
        echo json_encode(['status' => 'success', 'message' => 'Logout not required for HTTP API']);
    } else {
        $res = callNodeService('/logout', 'POST');
        echo json_encode($res);
    }
    exit;
}

if ($action === 'add_to_queue' || $action === 'add_bulk_by_criteria') {
    $userIds = [];
    $messageTemplate = isset($_POST['message']) ? $_POST['message'] : '';
    $scheduledAt = (isset($_POST['scheduled_at']) && !empty($_POST['scheduled_at'])) ? $_POST['scheduled_at'] : date('Y-m-d H:i:s');
    
    if ($action === 'add_bulk_by_criteria') {
        $criteria = isset($_POST['criteria']) ? $_POST['criteria'] : 'all';
        $sql = "SELECT id FROM users WHERE contactno IS NOT NULL AND contactno != ''";
        
        if ($criteria === 'pending_cert') {
            $sql .= " AND (certificate_sent = 0 OR certificate_sent IS NULL)";
        }
        // Add more criteria as needed
        
        $res = mysqli_query($con, $sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $userIds[] = $row['id'];
        }
    } else {
        $userIds = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];
    }
    
    if (empty($userIds) || empty($messageTemplate)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing data or no users found']);
        exit;
    }
    
    $count = 0;
    foreach ($userIds as $uid) {
        // Fetch user details for template replacement
        $uQuery = mysqli_query($con, "SELECT * FROM users WHERE id='$uid'");
        $user = mysqli_fetch_assoc($uQuery);
        
        if ($user) {
            $phone = formatWaNumber($user['contactno']);
            if (empty($phone) || strlen($phone) < 8) continue; // Skip invalid phones
            
            // Template Replacement
            $msg = $messageTemplate;
            $msg = str_replace('{name}', $user['fname'] . ' ' . $user['lname'], $msg);
            $msg = str_replace('{email}', $user['email'], $msg);
            
            // Secure Link Generation
            $secret_salt = 'ICPM2026_Secure_Salt';
            $hash = md5($user['id'] . $secret_salt);
            $link = "https://reg-sys.com/icpm2026/participant/download-certificate.php?id=" . $user['id'] . "&hash=" . $hash;
            $msg = str_replace('{certificate_link}', $link, $msg);
            $mediaUrl = $link; // For PDF generation
            
            // Check for duplicates in pending queue
            $dupCheck = mysqli_query($con, "SELECT id FROM whatsapp_queue WHERE user_id='$uid' AND status='pending'");
            if (mysqli_num_rows($dupCheck) > 0) continue;
            
            // Insert into Queue
            $stmt = mysqli_prepare($con, "INSERT INTO whatsapp_queue (user_id, phone_number, message, media_url, status, scheduled_at) VALUES (?, ?, ?, ?, 'pending', ?)");
            mysqli_stmt_bind_param($stmt, "issss", $uid, $phone, $msg, $mediaUrl, $scheduledAt);
            if (mysqli_stmt_execute($stmt)) {
                $count++;
            }
        }
    }
    
    echo json_encode(['status' => 'success', 'message' => "Queued $count messages"]);
    exit;
}

if ($action === 'add_bulk_csv') {
    $recipients = isset($_POST['recipients']) ? json_decode($_POST['recipients'], true) : [];
    $messageTemplate = isset($_POST['message']) ? $_POST['message'] : '';
    
    if (empty($recipients) || empty($messageTemplate)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing data']);
        exit;
    }
    
    $count = 0;
    foreach ($recipients as $r) {
        $phone = isset($r['phone']) ? $r['phone'] : '';
        $name = isset($r['name']) ? $r['name'] : '';
        
        // Clean phone
        $phone = formatWaNumber($phone);
        if (empty($phone)) continue;
        
        // Template Replacement
        $msg = $messageTemplate;
        $msg = str_replace('{name}', $name, $msg);
        $msg = str_replace('{phone}', $phone, $msg); // Allow phone in msg
        
        // Use 0 for user_id for CSV imports
        $uid = 0;
        
        // Insert into Queue
        $stmt = mysqli_prepare($con, "INSERT INTO whatsapp_queue (user_id, phone_number, message, status) VALUES (?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt, "iss", $uid, $phone, $msg);
        if (mysqli_stmt_execute($stmt)) {
            $count++;
        }
    }
    echo json_encode(['status' => 'success', 'message' => "Queued $count messages from CSV"]);
    exit;
}

if ($action === 'process_queue') {
    // Check daily rate limit (1000 messages/24h)
    $limitCheck = mysqli_query($con, "SELECT COUNT(*) as cnt FROM whatsapp_queue WHERE status='sent' AND sent_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $dailyCount = mysqli_fetch_assoc($limitCheck)['cnt'];
    
    if ($dailyCount >= 1000) {
        echo json_encode(['status' => 'error', 'message' => 'Daily limit of 1000 messages reached.']);
        exit;
    }

    // Fetch pending items (limit 5 per batch to avoid timeouts)
    $limit = 5;
    $query = mysqli_query($con, "SELECT * FROM whatsapp_queue WHERE status='pending' AND scheduled_at <= NOW() ORDER BY created_at ASC LIMIT $limit");
    
    $processed = 0;
    $errors = 0;
    
    while ($row = mysqli_fetch_assoc($query)) {
        // Mark as processing
        mysqli_query($con, "UPDATE whatsapp_queue SET status='processing' WHERE id=" . $row['id']);
        
        if ($waTransportMode === 'http_api') {
            $res = callHttpApiService([
                'phone' => $row['phone_number'],
                'message' => $row['message'],
                'media_url' => $row['media_url']
            ]);
        } else {
            $endpoint = '/send';
            $payload = [
                'phone' => $row['phone_number'],
                'message' => $row['message']
            ];
            if (!empty($row['media_url'])) {
                $endpoint = '/send-pdf';
                $payload['pdf_url'] = $row['media_url'];
            }
            $res = callNodeService($endpoint, 'POST', $payload);
        }
        
        // Mask phone for logging
        $maskedPhone = substr($row['phone_number'], 0, 3) . '****' . substr($row['phone_number'], -4);
        
        if (isset($res['status']) && $res['status'] === 'success') {
            mysqli_query($con, "UPDATE whatsapp_queue SET status='sent', sent_at=NOW() WHERE id=" . $row['id']);
            $processed++;
            // Log success
            mysqli_query($con, "INSERT INTO whatsapp_logs (action, details) VALUES ('send_bulk', 'Sent to $maskedPhone (Queue ID: {$row['id']})')");
        } else {
            $errorMsg = mysqli_real_escape_string($con, isset($res['message']) ? $res['message'] : 'Unknown error');
            mysqli_query($con, "UPDATE whatsapp_queue SET status='failed', error_message='$errorMsg' WHERE id=" . $row['id']);
            $errors++;
            // Log failure
            mysqli_query($con, "INSERT INTO whatsapp_logs (action, details) VALUES ('send_fail', 'Failed to $maskedPhone: $errorMsg')");
        }
        
        // Small delay to be nice to the API
        sleep(2);
    }
    
    // Check remaining
    $remQuery = mysqli_query($con, "SELECT COUNT(*) as cnt FROM whatsapp_queue WHERE status='pending'");
    $rem = mysqli_fetch_assoc($remQuery)['cnt'];
    
    echo json_encode([
        'status' => 'success', 
        'processed' => $processed, 
        'errors' => $errors,
        'remaining' => $rem
    ]);
    exit;
}

if ($action === 'get_queue_stats') {
    $q = mysqli_query($con, "SELECT status, COUNT(*) as cnt FROM whatsapp_queue GROUP BY status");
    $stats = [];
    while($row = mysqli_fetch_assoc($q)) {
        $stats[$row['status']] = $row['cnt'];
    }
    echo json_encode(['status' => 'success', 'data' => $stats]);
    exit;
}

if ($action === 'send_test') {
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $phone = formatWaNumber($phone);
    if (empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Phone number required']);
        exit;
    }
    
    if ($waTransportMode === 'http_api') {
        $res = callHttpApiService([
            'phone' => $phone,
            'message' => "This is a test message from ICPM 2026 System.\nSent at: " . date('Y-m-d H:i:s')
        ]);
    } else {
        $res = callNodeService('/send', 'POST', [
            'phone' => $phone,
            'message' => "This is a test message from ICPM 2026 System.\nSent at: " . date('Y-m-d H:i:s')
        ]);
    }
    
    // Log it
    $status = (isset($res['status']) && $res['status'] === 'success') ? 'success' : 'failed';
    $maskedPhone = substr($phone, 0, 3) . '****' . substr($phone, -4);
    $logMsg = "Test message to $maskedPhone: $status";
    mysqli_query($con, "INSERT INTO whatsapp_logs (action, details) VALUES ('send_test', '$logMsg')");
    
    echo json_encode($res);
    exit;
}

if ($action === 'get_settings') {
    $q = mysqli_query($con, "SELECT * FROM whatsapp_settings WHERE id=1");
    $data = mysqli_fetch_assoc($q);
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

if ($action === 'save_settings') {
    $mode = isset($_POST['transport_mode']) ? $_POST['transport_mode'] : 'node';
    $nodeUrl = isset($_POST['node_url']) ? $_POST['node_url'] : '';
    $apiUrl = isset($_POST['http_api_url']) ? $_POST['http_api_url'] : '';
    $apiToken = isset($_POST['http_api_token']) ? $_POST['http_api_token'] : '';
    
    // Basic validation
    if ($mode !== 'node' && $mode !== 'http_api') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid transport mode']);
        exit;
    }
    
    $stmt = mysqli_prepare($con, "UPDATE whatsapp_settings SET transport_mode=?, node_url=?, http_api_url=?, http_api_token=? WHERE id=1");
    mysqli_stmt_bind_param($stmt, "ssss", $mode, $nodeUrl, $apiUrl, $apiToken);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Settings saved']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . mysqli_error($con)]);
    }
    exit;
}

if ($action === 'clear_queue') {
    mysqli_query($con, "DELETE FROM whatsapp_queue WHERE status='pending'");
    echo json_encode(['status' => 'success', 'message' => 'Pending queue cleared']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
