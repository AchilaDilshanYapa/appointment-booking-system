<?php
// Set content type to JSON
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "appointment_db";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

// Check if database exists, create if it doesn't
$result = $conn->query("SHOW DATABASES LIKE '$database'");
if ($result->num_rows === 0) {
    if (!$conn->query("CREATE DATABASE $database")) {
        die(json_encode(["success" => false, "message" => "Failed to create database: " . $conn->error]));
    }
}

// Select the database
$conn->select_db($database);

// Check if table exists, create if it doesn't
$result = $conn->query("SHOW TABLES LIKE 'appointments'");
if ($result->num_rows === 0) {
    $sql = "CREATE TABLE appointments (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        country VARCHAR(50) NOT NULL,
        appointment_date DATE NOT NULL,
        comments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($sql)) {
        die(json_encode(["success" => false, "message" => "Failed to create table: " . $conn->error]));
    }
}

// Get form data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$country = $_POST['country'] ?? '';
$date = $_POST['date'] ?? '';
$comments = trim($_POST['comments'] ?? '');

// Validate data
if (empty($name) || empty($email) || empty($phone) || empty($country) || empty($date)) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit;
}

// Validate phone number (only numbers, 10-15 digits)
if (!preg_match("/^\d{10,15}$/", $phone)) {
    echo json_encode(["success" => false, "message" => "Invalid phone number format."]);
    exit;
}

// Validate country is from our list
$validCountries = ["USA", "UK", "Canada", "Australia", "Other"];
if (!in_array($country, $validCountries)) {
    echo json_encode(["success" => false, "message" => "Please select a valid country."]);
    exit;
}

// Validate date is not in the past
$currentDate = date('Y-m-d');
if ($date < $currentDate) {
    echo json_encode(["success" => false, "message" => "Please select a future date."]);
    exit;
}

// Check for duplicate appointment
$stmt = $conn->prepare("SELECT id FROM appointments WHERE email = ? AND appointment_date = ?");
$stmt->bind_param("ss", $email, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "You already have an appointment scheduled for this date."]);
    $stmt->close();
    exit;
}

// Prepare SQL statement for insertion
$stmt = $conn->prepare("INSERT INTO appointments (name, email, phone, country, appointment_date, comments) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $name, $email, $phone, $country, $date, $comments);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Appointment booked successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}

// Close connections
$stmt->close();
$conn->close();
?>