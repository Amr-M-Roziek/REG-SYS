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

if (isset($_GET['json'])) {
    echo json_encode(['status' => 'completed', 'messages' => $response]);
} else {
    echo implode("<br>", $response);
}
?>
