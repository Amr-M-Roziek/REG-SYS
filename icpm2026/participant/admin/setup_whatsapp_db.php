<?php
include 'dbconnection.php';

$response = [];

// WhatsApp Queue Table
$sql1 = "CREATE TABLE IF NOT EXISTS whatsapp_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT,
    scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (status),
    INDEX (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($con, $sql1)) {
    $response[] = "Table 'whatsapp_queue' check/creation successful.";
} else {
    $response[] = "Error creating table 'whatsapp_queue': " . mysqli_error($con);
}

// WhatsApp Logs Table
$sql2 = "CREATE TABLE IF NOT EXISTS whatsapp_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50),
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($con, $sql2)) {
    $response[] = "Table 'whatsapp_logs' check/creation successful.";
} else {
    $response[] = "Error creating table 'whatsapp_logs': " . mysqli_error($con);
}

// WhatsApp Settings Table
$sql3 = "CREATE TABLE IF NOT EXISTS whatsapp_settings (
    id INT(1) NOT NULL DEFAULT 1,
    transport_mode VARCHAR(20) DEFAULT 'node',
    node_url VARCHAR(255) DEFAULT 'http://127.0.0.1:3000',
    http_api_url VARCHAR(255) DEFAULT '',
    http_api_token VARCHAR(255) DEFAULT '',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($con, $sql3)) {
    // Insert default if not exists
    $chk = mysqli_query($con, "SELECT id FROM whatsapp_settings WHERE id=1");
    if (mysqli_num_rows($chk) == 0) {
        mysqli_query($con, "INSERT INTO whatsapp_settings (id, transport_mode, node_url, http_api_url, http_api_token) VALUES (1, 'node', 'http://127.0.0.1:3000', '', '')");
        $response[] = "Initialized default settings.";
    }
    $response[] = "Table 'whatsapp_settings' check/creation successful.";
} else {
    $response[] = "Error creating table 'whatsapp_settings': " . mysqli_error($con);
}

if (isset($_GET['json'])) {
    echo json_encode(['status' => 'completed', 'messages' => $response]);
} else {
    echo implode("<br>", $response);
}
?>
