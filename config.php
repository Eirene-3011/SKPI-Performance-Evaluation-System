<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "password123";
$dbname = "performance_evaluation_db";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    // Database created successfully or already exists
} else {
    echo "Error creating database: " . $conn->error;
}

// Close connection and reconnect to the database
$conn->close();

// Reconnect to the specific database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

