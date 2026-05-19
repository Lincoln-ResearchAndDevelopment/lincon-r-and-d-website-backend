<?php
include "inc/db.php";

$sql_supervisors = "CREATE TABLE IF NOT EXISTS `supervisors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sql_psa = "CREATE TABLE IF NOT EXISTS `psa_projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `studentid` varchar(100) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `report_file` varchar(255) NOT NULL,
  `live_link` varchar(255) DEFAULT NULL,
  `github_link` varchar(255) DEFAULT NULL,
  `supervisor_id` int NOT NULL,
  `is_approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql_supervisors) === TRUE) {
    echo "Supervisors table created successfully\n";
} else {
    echo "Error creating supervisors table: " . $conn->error . "\n";
}

if ($conn->query($sql_psa) === TRUE) {
    echo "PSA projects table created successfully\n";
} else {
    echo "Error creating psa projects table: " . $conn->error . "\n";
}

$conn->close();
?>
