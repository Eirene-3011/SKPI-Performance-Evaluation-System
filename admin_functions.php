<?php
require_once 'config.php';
require_once 'database_functions_enhanced.php';
require_once 'auth.php';

// Define Admin role ID safely
if (!defined('ADMIN_ROLE_ID')) {
    define('ADMIN_ROLE_ID', 0);
}

// Function to add a new employee
function addEmployee($card_no, $firstname, $middlename, $lastname, $suffixname, $department_id, $position_id, $section_id, $role_id, $hired_date)
{
    global $conn;

    // Generate fullname variations
    $fullname = trim($firstname . ' ' . $middlename . ' ' . $lastname . ' ' . $suffixname);
    $fullname2 = trim($lastname . ', ' . $firstname . ' ' . $middlename . ' ' . $suffixname);
    $fullname3 = trim($firstname . ' ' . $lastname);

    // Get next available ID
    $sql = "SELECT MAX(id) as max_id FROM employees";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;

    $sql = "INSERT INTO employees (id, card_no, firstname, middlename, lastname, suffixname, fullname, fullname2, fullname3, 
                                  department_id, position_id, section_id, role_id, hired_date, is_inactive, created_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssssssiiisi",
        $next_id,
        $card_no,
        $firstname,
        $middlename,
        $lastname,
        $suffixname,
        $fullname,
        $fullname2,
        $fullname3,
        $department_id,
        $position_id,
        $section_id,
        $role_id,
        $hired_date
    );

    return $stmt->execute();
}

// Function to update an employee
function updateEmployee($id, $card_no, $firstname, $middlename, $lastname, $suffixname, $department_id, $position_id, $section_id, $role_id, $hired_date, $is_inactive)
{
    global $conn;

    // Generate fullname variations
    $fullname = trim($firstname . ' ' . $middlename . ' ' . $lastname . ' ' . $suffixname);
    $fullname2 = trim($lastname . ', ' . $firstname . ' ' . $middlename . ' ' . $suffixname);
    $fullname3 = trim($firstname . ' ' . $lastname);

    $sql = "UPDATE employees SET 
            card_no = ?, 
            firstname = ?, 
            middlename = ?, 
            lastname = ?, 
            suffixname = ?, 
            fullname = ?, 
            fullname2 = ?, 
            fullname3 = ?, 
            department_id = ?, 
            position_id = ?, 
            section_id = ?, 
            role_id = ?, 
            hired_date = ?, 
            is_inactive = ?, 
            updated_date = CURRENT_TIMESTAMP 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssiiiisii",
        $card_no,
        $firstname,
        $middlename,
        $lastname,
        $suffixname,
        $fullname,
        $fullname2,
        $fullname3,
        $department_id,
        $position_id,
        $section_id,
        $role_id,
        $hired_date,
        $is_inactive,
        $id
    );

    return $stmt->execute();
}

// Function to delete an employee (soft delete)
function deleteEmployee($id)
{
    global $conn;

    $sql = "UPDATE employees SET is_inactive = 1, updated_date = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    return $stmt->execute();
}

// Function to reset employee password
function resetEmployeePassword($id)
{
    global $conn;

    // Get employee card_no
    $sql = "SELECT card_no FROM employees WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $employee = $result->fetch_assoc();

        // Reset password to null (will force first-time login)
        $sql = "UPDATE employees SET password = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    return false;
}

// Function to get evaluations by department
function getEvaluationsByDepartment($department_id)
{
    global $conn;

    $sql = "SELECT ev.*, e.fullname, e.card_no, ed.name as department_name, ep.name as position_name
            FROM evaluations ev
            JOIN employees e ON ev.employee_id = e.id
            LEFT JOIN emp_department ed ON e.department_id = ed.id 
            LEFT JOIN emp_positions ep ON e.position_id = ep.id 
            WHERE e.department_id = ?
            ORDER BY ev.created_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to generate evaluation summary report
function generateEvaluationSummaryReport($department_id = null, $start_date = null, $end_date = null)
{
    global $conn;

    $params = [];
    $types = "";

    $sql = "SELECT ev.id, ev.evaluation_reason, ev.status, ev.created_date, ev.period_covered_from, ev.period_covered_to,
                   e.fullname, e.card_no, ed.name as department_name, ep.name as position_name,
                   (SELECT AVG(er.score) FROM evaluation_responses er WHERE er.evaluation_id = ev.id) as average_score
            FROM evaluations ev
            JOIN employees e ON ev.employee_id = e.id
            LEFT JOIN emp_department ed ON e.department_id = ed.id 
            LEFT JOIN emp_positions ep ON e.position_id = ep.id 
            WHERE 1=1";

    if ($department_id) {
        $sql .= " AND e.department_id = ?";
        $params[] = $department_id;
        $types .= "i";
    }

    if ($start_date) {
        $sql .= " AND ev.created_date >= ?";
        $params[] = $start_date;
        $types .= "s";
    }

    if ($end_date) {
        $sql .= " AND ev.created_date <= ?";
        $params[] = $end_date;
        $types .= "s";
    }

    $sql .= " ORDER BY ev.created_date DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to generate detailed evaluation report
function generateDetailedEvaluationReport($evaluation_id)
{
    global $conn;

    // Get evaluation details
    $evaluation = getEvaluationById($evaluation_id);

    // Get all responses
    $responses = getEvaluationResponses($evaluation_id);

    // Get workflow status
    $workflow = getEvaluationWorkflowStatus($evaluation_id);

    // Group responses by criteria
    $criteria_responses = [];
    foreach ($responses as $response) {
        $criteria_id = $response['criteria_id'];
        if (!isset($criteria_responses[$criteria_id])) {
            $criteria_responses[$criteria_id] = [
                'criteria_name' => $response['criteria_name'],
                'responses' => []
            ];
        }
        $criteria_responses[$criteria_id]['responses'][] = $response;
    }

    return [
        'evaluation' => $evaluation,
        'criteria_responses' => $criteria_responses,
        'workflow' => $workflow
    ];
}
