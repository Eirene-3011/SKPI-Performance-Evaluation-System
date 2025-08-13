<?php
require_once 'config.php';

// Create employees table
$sql_employees = "CREATE TABLE IF NOT EXISTS employees (
    id INT PRIMARY KEY,
    card_no VARCHAR(50),
    firstname VARCHAR(100),
    middlename VARCHAR(100),
    lastname VARCHAR(100),
    suffixname VARCHAR(50),
    fullname VARCHAR(255),
    fullname2 VARCHAR(255),
    fullname3 VARCHAR(255),
    department_id INT,
    position_id INT,
    designation_id INT,
    section_id INT,
    shift_id INT,
    role_id INT,
    birthdate DATE,
    birthplace VARCHAR(255),
    gender VARCHAR(10),
    civil_status VARCHAR(50),
    with_child TINYINT(1),
    profile_img VARCHAR(255),
    contact VARCHAR(50),
    address TEXT,
    email VARCHAR(100),
    work_email VARCHAR(100),
    hired_date DATE,
    agency VARCHAR(50),
    employment_status VARCHAR(50),
    gov_sss VARCHAR(50),
    gov_pagibig VARCHAR(50),
    gov_philhealth VARCHAR(50),
    gov_tin VARCHAR(50),
    incase_name VARCHAR(255),
    incase_relationship VARCHAR(100),
    incase_contact VARCHAR(50),
    incase_address TEXT,
    resign_flag TINYINT(1),
    resign_date DATE,
    resign_reason TEXT,
    is_inactive TINYINT(1),
    created_date DATETIME,
    updated_date DATETIME,
    password VARCHAR(255)
)";

// Create emp_department table
$sql_emp_department = "CREATE TABLE IF NOT EXISTS emp_department (
    id INT PRIMARY KEY,
    name VARCHAR(100),
    is_disabled TINYINT(1)
)";

// Create emp_positions table
$sql_emp_positions = "CREATE TABLE IF NOT EXISTS emp_positions (
    id INT PRIMARY KEY,
    name VARCHAR(100),
    is_disabled TINYINT(1),
    group_name VARCHAR(100)
)";

// Create emp_sections table
$sql_emp_sections = "CREATE TABLE IF NOT EXISTS emp_sections (
    id INT PRIMARY KEY,
    name VARCHAR(100),
    order_num INT,
    is_disabled TINYINT(1)
)";

// Create emp_roles table
$sql_emp_roles = "CREATE TABLE IF NOT EXISTS emp_roles (
    id INT PRIMARY KEY,
    name VARCHAR(100),
    order_num INT,
    is_disabled TINYINT(1)
)";

// Create evaluations table
$sql_evaluations = "CREATE TABLE IF NOT EXISTS evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    evaluation_reason VARCHAR(100),
    evaluation_date DATE,
    period_covered_from DATE,
    period_covered_to DATE,
    status VARCHAR(50) DEFAULT 'pending',
    current_evaluator_role_id INT DEFAULT 1,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (current_evaluator_role_id) REFERENCES emp_roles(id)
)";

// Create evaluation_criteria table
$sql_evaluation_criteria = "CREATE TABLE IF NOT EXISTS evaluation_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    criteria_name VARCHAR(255),
    criteria_description TEXT,
    max_score INT DEFAULT 5,
    order_num INT
)";

// Create evaluation_responses table
$sql_evaluation_responses = "CREATE TABLE IF NOT EXISTS evaluation_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT,
    criteria_id INT,
    evaluator_id INT,
    score INT,
    comments TEXT,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id),
    FOREIGN KEY (criteria_id) REFERENCES evaluation_criteria(id),
    FOREIGN KEY (evaluator_id) REFERENCES employees(id)
)";

// Create evaluation_workflow table with sequential flow support
$sql_evaluation_workflow = "CREATE TABLE IF NOT EXISTS evaluation_workflow (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT,
    evaluator_role_id INT,
    evaluator_id INT,
    status VARCHAR(50) DEFAULT 'pending',
    step_order INT,
    completed_date DATETIME,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id),
    FOREIGN KEY (evaluator_role_id) REFERENCES emp_roles(id),
    FOREIGN KEY (evaluator_id) REFERENCES employees(id)
)";

// Create evaluation_workflow_stages table to define the evaluation sequence
$sql_evaluation_workflow_stages = "CREATE TABLE IF NOT EXISTS evaluation_workflow_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT,
    role_name VARCHAR(100),
    step_order INT,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (role_id) REFERENCES emp_roles(id)
)";

// Execute table creation queries
$tables = [
    'employees' => $sql_employees,
    'emp_department' => $sql_emp_department,
    'emp_positions' => $sql_emp_positions,
    'emp_sections' => $sql_emp_sections,
    'emp_roles' => $sql_emp_roles,
    'evaluations' => $sql_evaluations,
    'evaluation_criteria' => $sql_evaluation_criteria,
    'evaluation_responses' => $sql_evaluation_responses,
    'evaluation_workflow' => $sql_evaluation_workflow,
    'evaluation_workflow_stages' => $sql_evaluation_workflow_stages
];

foreach ($tables as $table_name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table '$table_name' created successfully<br>";
    } else {
        echo "Error creating table '$table_name': " . $conn->error . "<br>";
    }
}

// Insert evaluation criteria
$criteria = [
    ['Compliance to Company Policy', 'Obedience to Company , Safety and 5s Policy', 5, 1],
    ['Job Knowledge/Technical Skills', 'Understands job responsibilities and scope of authority. Possesses required skills, knowledge and abilities to completely perform the job.', 5, 2],
    ['Quality and Quantity of Work', 'Work is performed with efficiency, consistency and timely. Demonstrates accuracy, neatness and effectiveness.', 5, 3],
    ['Attendance and Punctuality', 'Consistency in coming to work daily and conforming to schedule work hours.', 5, 4],
    ['Communication, Cooperation and Teamwork', 'Written and oral communications are clear, organized and effective, listens and comprehends well. Respectful of colleagues when working with others and makes valuable contributions to help the group achieve its goals.', 5, 5]
];

$sql_insert_criteria = "INSERT IGNORE INTO evaluation_criteria (criteria_name, criteria_description, max_score, order_num) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql_insert_criteria);

foreach ($criteria as $criterion) {
    $stmt->bind_param("ssii", $criterion[0], $criterion[1], $criterion[2], $criterion[3]);
    $stmt->execute();
}

echo "Evaluation criteria inserted successfully<br>";

// Insert evaluation workflow stages (HR -> Shift Leader -> Supervisor -> Manager)
$workflow_stages = [
    [1, 'HR', 1],
    [4, 'Shift Leader', 2],
    [3, 'Supervisor', 3],
    [2, 'Manager', 4]
];

$sql_insert_stages = "INSERT IGNORE INTO evaluation_workflow_stages (role_id, role_name, step_order) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql_insert_stages);

foreach ($workflow_stages as $stage) {
    $stmt->bind_param("isi", $stage[0], $stage[1], $stage[2]);
    $stmt->execute();
}

echo "Evaluation workflow stages inserted successfully<br>";

$conn->close();
echo "<br>Database setup completed successfully!";