<?php
require_once 'config.php';

echo "<h2>Database Schema Verification</h2>";

// Check if employees table exists and has required columns
$sql = "DESCRIBE employees";
$result = $conn->query($sql);

if ($result) {
    echo "<h3>Employees Table Schema:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $has_department_id = false;
    $has_section_id = false;
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
        
        if ($row['Field'] == 'department_id') $has_department_id = true;
        if ($row['Field'] == 'section_id') $has_section_id = true;
    }
    echo "</table>";
    
    echo "<h3>Required Columns Check:</h3>";
    echo "department_id: " . ($has_department_id ? "✓ Present" : "✗ Missing") . "<br>";
    echo "section_id: " . ($has_section_id ? "✓ Present" : "✗ Missing") . "<br>";
} else {
    echo "Error checking employees table: " . $conn->error;
}

// Check if emp_department table exists
$sql = "SELECT COUNT(*) as count FROM emp_department";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    echo "<h3>emp_department table: ✓ Present (" . $row['count'] . " records)</h3>";
} else {
    echo "<h3>emp_department table: ✗ Missing or error: " . $conn->error . "</h3>";
}

// Check if emp_sections table exists
$sql = "SELECT COUNT(*) as count FROM emp_sections";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    echo "<h3>emp_sections table: ✓ Present (" . $row['count'] . " records)</h3>";
} else {
    echo "<h3>emp_sections table: ✗ Missing or error: " . $conn->error . "</h3>";
}

// Check if emp_roles table exists
$sql = "SELECT COUNT(*) as count FROM emp_roles";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    echo "<h3>emp_roles table: ✓ Present (" . $row['count'] . " records)</h3>";
} else {
    echo "<h3>emp_roles table: ✗ Missing or error: " . $conn->error . "</h3>";
}

// Sample data check - employees with department_id and section_id
$sql = "SELECT id, card_no, fullname, department_id, section_id, role_id FROM employees WHERE department_id IS NOT NULL AND section_id IS NOT NULL LIMIT 5";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<h3>Sample Employee Data (with department_id and section_id):</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Card No</th><th>Name</th><th>Department ID</th><th>Section ID</th><th>Role ID</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['card_no'] . "</td>";
        echo "<td>" . $row['fullname'] . "</td>";
        echo "<td>" . $row['department_id'] . "</td>";
        echo "<td>" . $row['section_id'] . "</td>";
        echo "<td>" . $row['role_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h3>No employees found with both department_id and section_id populated</h3>";
}

$conn->close();
?>

