<?php
// FIX: Check if a session is already active before starting a new one.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Function to hash password
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

// Function to authenticate user
function authenticateUser($card_no, $password)
{
    global $conn;

    $sql = "SELECT e.*, 
                   er.name AS role_name, 
                   ed.name AS department_name, 
                   ep.name AS position_name,
                   es.name AS section_name
            FROM employees e 
            LEFT JOIN emp_roles er ON e.role_id = er.id 
            LEFT JOIN emp_department ed ON e.department_id = ed.id 
            LEFT JOIN emp_positions ep ON e.position_id = ep.id 
            LEFT JOIN emp_sections es ON e.section_id = es.id
            WHERE e.card_no = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $card_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (empty($user['password'])) {
            return ['status' => 'first_login', 'user' => $user];
        } else {
            if (verifyPassword($password, $user['password'])) {
                return ['status' => 'success', 'user' => $user];
            } else {
                return ['status' => 'invalid_password'];
            }
        }
    } else {
        return ['status' => 'user_not_found'];
    }
}



// Function to set first-time password
function setFirstTimePassword($card_no, $password)
{
    global $conn;

    $hashed_password = hashPassword($password);
    $sql = "UPDATE employees SET password = ? WHERE card_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $hashed_password, $card_no);

    return $stmt->execute();
}

// Function to check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Function to get current user
function getCurrentUser()
{
    global $conn;

    if (!isLoggedIn()) {
        return null;
    }

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT e.*, 
                   er.name AS role_name, 
                   ed.name AS department_name, 
                   ep.name AS position_name,
                   es.name AS section_name,
                   edsg.name AS designation_name
            FROM employees e 
            LEFT JOIN emp_roles er ON e.role_id = er.id 
            LEFT JOIN emp_department ed ON e.department_id = ed.id 
            LEFT JOIN emp_positions ep ON e.position_id = ep.id 
            LEFT JOIN emp_sections es ON e.section_id = es.id
            LEFT JOIN emp_designation edsg ON e.designation_id = edsg.id
            WHERE e.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// Function to logout user
function logoutUser()
{
    session_destroy();
    header("Location: login.php");
    exit();
}

// Function to check user role
function hasRole($required_role)
{
    $user = getCurrentUser();
    if ($user) {
        return $user['role_id'] == $required_role;
    }
    return false;
}

// Function to check if user is admin
function isAdmin()
{
    $user = getCurrentUser();
    if ($user) {
        return $user['role_id'] == 0; // Admin role ID is 0
    }
    return false;
}

// Function to check if user can evaluate (HR, Shift Leader, Supervisor, Manager)
function canEvaluate()
{
    $user = getCurrentUser();
    if ($user) {
        return in_array($user['role_id'], [1, 2, 3, 4]); // HR, Manager, Supervisor, Shift Leader
    }
    return false;
}

// Function to get evaluator role permissions
function getEvaluatorPermissions($role_id)
{
    $permissions = [
        1 => ["criteria" => [4], "name" => "HR"], // HR can only evaluate Attendance and Punctuality
        2 => ["criteria" => [1, 2, 3, 5], "name" => "Manager"], // Manager can evaluate Work Quality, Quantity, Habits, Personality
        3 => ["criteria" => [1, 2, 3, 5], "name" => "Supervisor"], // Supervisor can evaluate Work Quality, Quantity, Habits, Personality
        4 => ["criteria" => [1, 2, 3, 5], "name" => "Shift Leader"] // Shift Leader can evaluate Work Quality, Quantity, Habits, Personality
    ];

    return isset($permissions[$role_id]) ? $permissions[$role_id] : null;
}

// Function to require login
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Function to require specific role
function requireRole($required_role)
{
    requireLogin();
    if (!hasRole($required_role)) {
        header("Location: dashboard.php?error=access_denied");
        exit();
    }
}

// Function to require admin role
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php?error=access_denied");
        exit();
    }
}

// Function to check if evaluator can evaluate specific employee based on department and section
function canEvaluateEmployee($evaluator_user, $employee_id)
{
    global $conn;

    // Admin role can evaluate anyone
    if ($evaluator_user['role_id'] == 0) {
        return true;
    }

    // HR role can only evaluate employees with matching designation_id
    if ($evaluator_user['role_id'] == 1) {
        $sql = "SELECT designation_id FROM employees WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $employee = $result->fetch_assoc();
            return ($evaluator_user['designation_id'] == $employee['designation_id']);
        }
        return false;
    }

    // For other evaluator roles (Shift Leader, Supervisor, Manager), check department and section match
    if (in_array($evaluator_user['role_id'], [2, 3, 4])) {
        $sql = "SELECT department_id, section_id FROM employees WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $employee = $result->fetch_assoc();
            return ($evaluator_user['department_id'] == $employee['department_id']) && 
                   ($evaluator_user['section_id'] == $employee['section_id']);
        }
    }

    return false;
}

// Function to get employees that current user can evaluate (department and section-based filtering)
function getEvaluableEmployees($user)
{
    global $conn;

    // Admin can see all employees
    if ($user['role_id'] == 0) {
        $sql = "SELECT e.*, er.name as role_name, ed.name as department_name, ep.name as position_name, es.name as section_name 
                FROM employees e 
                LEFT JOIN emp_roles er ON e.role_id = er.id 
                LEFT JOIN emp_department ed ON e.department_id = ed.id 
                LEFT JOIN emp_positions ep ON e.position_id = ep.id 
                LEFT JOIN emp_sections es ON e.section_id = es.id 
                WHERE e.is_inactive = 0 
                ORDER BY e.fullname";

        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // HR can only see employees with matching designation_id
    if ($user['role_id'] == 1) {
        $sql = "SELECT e.*, er.name as role_name, ed.name as department_name, ep.name as position_name, es.name as section_name 
                FROM employees e 
                LEFT JOIN emp_roles er ON e.role_id = er.id 
                LEFT JOIN emp_department ed ON e.department_id = ed.id 
                LEFT JOIN emp_positions ep ON e.position_id = ep.id 
                LEFT JOIN emp_sections es ON e.section_id = es.id 
                WHERE e.is_inactive = 0 AND e.designation_id = ? 
                ORDER BY e.fullname";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user['designation_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // For other evaluator roles, only show employees from same department and section
    if (in_array($user['role_id'], [2, 3, 4])) {
        $sql = "SELECT e.*, er.name as role_name, ed.name as department_name, ep.name as position_name, es.name as section_name 
                FROM employees e 
                LEFT JOIN emp_roles er ON e.role_id = er.id 
                LEFT JOIN emp_department ed ON e.department_id = ed.id 
                LEFT JOIN emp_positions ep ON e.position_id = ep.id 
                LEFT JOIN emp_sections es ON e.section_id = es.id 
                WHERE e.is_inactive = 0 AND e.department_id = ? AND e.section_id = ? 
                ORDER BY e.fullname";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user['department_id'], $user['section_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return [];
}

// Function to check if user should see department filter
function shouldShowDepartmentFilter($user)
{
    // Only HR role (role_id = 1) should see department filter
    return $user['role_id'] == 1;
}
