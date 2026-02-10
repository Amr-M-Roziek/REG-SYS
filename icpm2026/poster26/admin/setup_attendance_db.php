<?php
require_once 'dbconnection.php';

// Create lectures table
$sql_lectures = "CREATE TABLE IF NOT EXISTS `lectures` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `lecturer_name` VARCHAR(255),
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL,
  `location` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($con, $sql_lectures)) {
    echo "Table 'lectures' created or already exists.<br>";
} else {
    echo "Error creating table 'lectures': " . mysqli_error($con) . "<br>";
}

// Create attendance table
$sql_attendance = "CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lecture_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `scan_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('present', 'absent', 'late') DEFAULT 'present',
  FOREIGN KEY (`lecture_id`) REFERENCES `lectures`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_attendance` (`lecture_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($con, $sql_attendance)) {
    echo "Table 'attendance' created or already exists.<br>";
} else {
    echo "Error creating table 'attendance': " . mysqli_error($con) . "<br>";
}

echo "Database setup completed.";
?>
