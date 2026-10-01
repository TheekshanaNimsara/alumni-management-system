<?php
$host = 'localhost';
$dbname = 'alumni_network';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password (leave blank)

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, 3307);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>