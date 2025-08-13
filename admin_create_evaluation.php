<?php
// REMOVED: The session_start() block was here. It's no longer needed as auth.php handles it.

// It's good practice to check for the existence of required files before including them
$required_files = [
    'auth.php',
    'database_functions_enhanced.php',
    'admin_functions.php',
    'email_functions.php',
    'approver_functions.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        // Stop execution with a clear error message if a file is missing
        die("<strong>Critical Error:</strong> Required file not found: <strong>{$file}</strong>. Please check the file path and ensure it is uploaded correctly.");
    }
    require_once $file;
}

// Check if user is logged in and is an admin
requireAdmin();

$user = getCurrentUser();
$success_message = '';
$error_message = '';

// --- Get every reason from the DB ---
$evaluation_reasons = [];
$sql  = "SELECT reason_name FROM evaluation_reasons ORDER BY reason_name";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$evaluation_reasons = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get available roles for evaluation (Staff, Shift Leader, Supervisor)
$available_roles = [
    ['id' => 5, 'name' => 'Staff'],
    ['id' => 4, 'name' => 'Shift Leader'],
    ['id' => 3, 'name' => 'Supervisor']
];

// Initialize variables
$selected_role_id = null;
$selected_department_id = null;
$selected_section_id = null;
$employees = [];
$departments = [];
$sections = [];

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    switch ($_GET['ajax']) {
        case 'get_departments':
            if (isset($_GET['role_id'])) {
                $role_id = (int)$_GET['role_id'];
                $departments = getDepartmentsByRole($role_id);
                echo json_encode($departments);
            }
            exit;

        case 'get_sections':
            if (isset($_GET['department_id']) && isset($_GET['role_id'])) {
                $department_id = (int)$_GET['department_id'];
                $role_id = (int)$_GET['role_id'];
                $sections = getSectionsByDepartmentAndRole($department_id, $role_id);
                echo json_encode($sections);
            }
            exit;

        case 'get_employees':
            if (isset($_GET['role_id'])) {
                $role_id = (int)$_GET['role_id'];
                $department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
                $section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : null;
                $employees = getFilteredEmployees($role_id, $department_id, $section_id);
                echo json_encode($employees);
            }
            exit;

        case 'get_bulk_employees':
            if (isset($_GET['role_id']) && isset($_GET['department_id']) && isset($_GET['section_id'])) {
                $role_id = (int)$_GET['role_id'];
                $department_id = (int)$_GET['department_id'];
                $section_id = (int)$_GET['section_id'];
                $employees = getFilteredEmployees($role_id, $department_id, $section_id);
                echo json_encode($employees);
            }
            exit;

        case 'get_evaluators':
            if (isset($_GET['employee_id'])) {
                $employee_id = (int)$_GET['employee_id'];
                try {
                    $evaluators = getEvaluatorsForEmployee($employee_id);
                    echo json_encode($evaluators);
                } catch (Exception $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['error' => 'Employee ID not provided']);
            }
            exit;

        case 'get_potential_replacements':
            if (isset($_GET['role_id']) && isset($_GET['department_id'])) {
                $role_id = (int)$_GET['role_id'];
                $department_id = (int)$_GET['department_id'];
                $replacements = getPotentialReplacements($role_id, $department_id);
                echo json_encode($replacements);
            }
            exit;

        case 'update_evaluator':
            if (isset($_POST['employee_id']) && isset($_POST['evaluator_position']) && isset($_POST['new_evaluator_id'])) {
                $employee_id = (int)$_POST['employee_id'];
                $evaluator_position = $_POST['evaluator_position'];
                $new_evaluator_id = (int)$_POST['new_evaluator_id'];
                
                $success = updateApproverInCSV($employee_id, $evaluator_position, $new_evaluator_id);
                echo json_encode(['success' => $success]);
            }
            exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $evaluation_reason = trim($_POST['evaluation_reason']);
    if ($evaluation_reason === 'Other') {
        $other_reason = trim($_POST['other_reason'] ?? '');
        if (!empty($other_reason)) {
            $evaluation_reason = $other_reason;
        } else {
            $error_message = "Please specify the reason for evaluation.";
        }
    }

    $period_from = $_POST['period_from'];
    $period_to = $_POST['period_to'];

    // Validate period dates
    if (strtotime($period_from) > strtotime($period_to)) {
        $error_message = "Period From date cannot be later than Period To date.";
    } else {
        // Check if this is bulk creation (Semi-Annual)
        if ($evaluation_reason === 'Semi-Annual' && isset($_POST['bulk_mode'])) {
            // Handle bulk creation
            $role_id = (int)$_POST['role_id'];
            $department_id = (int)$_POST['department_id'];
            $section_id = (int)$_POST['section_id'];

            // Get all employees for bulk creation
            $bulk_employees = getFilteredEmployees($role_id, $department_id, $section_id);

            $created_count = 0;
            $failed_count = 0;

            foreach ($bulk_employees as $employee) {
                $employee_id = $employee['id'];

                // Get additional fields for this specific employee
                $additional_fields = [];
                if ($role_id == 5) { // Staff role
                    $emp_prefix = "emp_" . $employee_id . "_";
                    $additional_fields = [
                        'approved_leaves' => (int)($_POST[$emp_prefix . 'approved_leaves'] ?? 0),
                        'disapproved_leaves' => (int)($_POST[$emp_prefix . 'disapproved_leaves'] ?? 0),
                        'tardiness' => (int)($_POST[$emp_prefix . 'tardiness'] ?? 0),
                        'late_undertime' => (int)($_POST[$emp_prefix . 'late_undertime'] ?? 0),
                        'offense_1st' => (int)($_POST[$emp_prefix . 'offense_1st'] ?? 0),
                        'offense_2nd' => (int)($_POST[$emp_prefix . 'offense_2nd'] ?? 0),
                        'offense_3rd' => (int)($_POST[$emp_prefix . 'offense_3rd'] ?? 0),
                        'offense_4th' => (int)($_POST[$emp_prefix . 'offense_4th'] ?? 0),
                        'offense_5th' => (int)($_POST[$emp_prefix . 'offense_5th'] ?? 0),
                        'suspension_days' => (int)($_POST[$emp_prefix . 'suspension_days'] ?? 0)
                    ];
                }

                // Create evaluation for this employee
                $evaluation_id = createEvaluationWithRole($employee_id, $role_id, $evaluation_reason, $period_from, $period_to, $additional_fields);

                if ($evaluation_id) {
                    $created_count++;
                } else {
                    $failed_count++;
                }
            }

            if ($created_count > 0) {
                // Notify HR for bulk creation
                $notification_sent = notifyHRBulk($created_count, $evaluation_reason, $period_from, $period_to);
                $success_message = "Bulk evaluation creation completed! {$created_count} evaluations created successfully.";
                if ($failed_count > 0) {
                    $success_message .= " {$failed_count} evaluations failed to create.";
                }
                if ($notification_sent) {
                    $success_message .= " HR has been notified.";
                }
            } else {
                $error_message = "Failed to create any evaluations. Please try again.";
            }
        } else {
            // Handle standard single evaluation creation
            $role_id = (int)$_POST['role_id'];
            $employee_id = (int)$_POST['employee_id'];

            // Additional fields for Staff evaluations only
            $additional_fields = [];
            if ($role_id == 5) { // Staff role
                $additional_fields = [
                    'approved_leaves' => (int)($_POST['approved_leaves'] ?? 0),
                    'disapproved_leaves' => (int)($_POST['disapproved_leaves'] ?? 0),
                    'tardiness' => (int)($_POST['tardiness'] ?? 0),
                    'late_undertime' => (int)($_POST['late_undertime'] ?? 0),
                    'offense_1st' => (int)($_POST['offense_1st'] ?? 0),
                    'offense_2nd' => (int)($_POST['offense_2nd'] ?? 0),
                    'offense_3rd' => (int)($_POST['offense_3rd'] ?? 0),
                    'offense_4th' => (int)($_POST['offense_4th'] ?? 0),
                    'offense_5th' => (int)($_POST['offense_5th'] ?? 0),
                    'suspension_days' => (int)($_POST['suspension_days'] ?? 0)
                ];
            }

            // Validate inputs
            if (!isset($_POST['role_id'], $_POST['employee_id'], $_POST['evaluation_reason']) || trim($evaluation_reason) === '') {
                $error_message = "All fields are required.";
            } else {
                // Create evaluation with role-specific workflow
                $evaluation_id = createEvaluationWithRole($employee_id, $role_id, $evaluation_reason, $period_from, $period_to, $additional_fields);

                if ($evaluation_id) {
                    // Notify first evaluator (HR)
                    $notification_sent = notifyHR($evaluation_id);
                    if ($notification_sent) {
                        $success_message = "Evaluation created successfully! HR has been notified.";
                    } else {
                        $error_message = "Evaluation created, but failed to send notification email. Please check the system's email configuration.";
                    }
                } else {
                    $error_message = "Error creating evaluation. Please try again.";
                }
            }
        }
    }

    // Set selected values for form persistence
    if (isset($_POST['role_id'])) $selected_role_id = (int)$_POST['role_id'];
    if (isset($_POST['department_id'])) $selected_department_id = (int)$_POST['department_id'];
    if (isset($_POST['section_id'])) $selected_section_id = (int)$_POST['section_id'];
}

// Helper functions for enhanced filtering
function getDepartmentsByRole($role_id)
{
    global $conn;
    $sql = "SELECT DISTINCT ed.id, ed.name 
            FROM emp_department ed 
            INNER JOIN employees e ON ed.id = e.department_id 
            WHERE e.role_id = ? AND e.is_inactive = 0 
            ORDER BY ed.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getSectionsByDepartmentAndRole($department_id, $role_id)
{
    global $conn;
    $sql = "SELECT DISTINCT es.id, es.name 
            FROM emp_sections es 
            INNER JOIN employees e ON es.id = e.section_id 
            WHERE e.department_id = ? AND e.role_id = ? AND e.is_inactive = 0 
            ORDER BY es.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $department_id, $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getFilteredEmployees($role_id, $department_id = null, $section_id = null)
{
    global $conn;

    $sql = "SELECT e.*, er.name as role_name, ed.name as department_name, ep.name as position_name, es.name as section_name 
            FROM employees e 
            LEFT JOIN emp_roles er ON e.role_id = er.id 
            LEFT JOIN emp_department ed ON e.department_id = ed.id 
            LEFT JOIN emp_positions ep ON e.position_id = ep.id 
            LEFT JOIN emp_sections es ON e.section_id = es.id 
            WHERE e.is_inactive = 0 AND e.role_id = ?";

    $params = [$role_id];
    $types = "i";

    if ($department_id) {
        $sql .= " AND e.department_id = ?";
        $params[] = $department_id;
        $types .= "i";
    }

    if ($section_id) {
        $sql .= " AND e.section_id = ?";
        $params[] = $section_id;
        $types .= "i";
    }

    $sql .= " ORDER BY e.fullname";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to notify HR for bulk creation
function notifyHRBulk($count, $evaluation_reason, $period_from, $period_to)
{
    if (!function_exists('send_email_notification')) {
        return false;
    }

    global $conn;

    // Get HR users (role_id = 1)
    $sql = "SELECT * FROM employees WHERE role_id = 1 AND is_inactive = 0";
    $result = $conn->query($sql);
    $hr_users = $result->fetch_all(MYSQLI_ASSOC);

    // Notify each HR user
    foreach ($hr_users as $hr_user) {
        $subject = "Bulk Evaluations Created - Action Required";
        $message = "Dear " . $hr_user['fullname'] . ",\n\n";
        $message .= "{$count} new evaluations have been created for {$evaluation_reason}.\n";
        $message .= "Period Covered: " . date('M d, Y', strtotime($period_from)) . " - " . date('M d, Y', strtotime($period_to)) . "\n\n";
        $message .= "Please log in to the Performance Evaluation System to complete your evaluations.\n\n";
        $message .= "Thank you,\nPerformance Evaluation System";

        send_email_notification($hr_user['email'] ?? 'hr@example.com', $subject, $message);
    }
    return true;
}

// Function to notify HR (existing function)
function notifyHR($evaluation_id)
{
    if (!function_exists('send_email_notification')) {
        return false;
    }

    global $conn;

    // Get HR users (role_id = 1)
    $sql = "SELECT * FROM employees WHERE role_id = 1 AND is_inactive = 0";
    $result = $conn->query($sql);
    $hr_users = $result->fetch_all(MYSQLI_ASSOC);

    // Get evaluation details
    $evaluation = getEvaluationById($evaluation_id);

    // Notify each HR user
    foreach ($hr_users as $hr_user) {
        $subject = "New Evaluation Created - Action Required";
        $message = "Dear " . $hr_user['fullname'] . ",\n\n";
        $message .= "A new evaluation has been created for " . $evaluation['fullname'] . " (" . $evaluation['card_no'] . ").\n";
        $message .= "Evaluation Reason: " . $evaluation['evaluation_reason'] . "\n";
        $message .= "Period Covered: " . date('M d, Y', strtotime($evaluation['period_covered_from'])) . " - " . date('M d, Y', strtotime($evaluation['period_covered_to'])) . "\n\n";
        $message .= "Please log in to the Performance Evaluation System to complete your evaluation.\n\n";
        $message .= "Thank you,\nPerformance Evaluation System";

        send_email_notification($hr_user['email'] ?? 'hr@example.com', $subject, $message);
    }
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Evaluation - Admin - Performance Evaluation System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .form-control:focus {
            border-color: #e74c3c;
            outline: none;
            box-shadow: 0 0 5px #e74c3c;
        }

        select.form-control {
            height: 42px;
        }

        .btn-container {
            margin-top: 30px;
            text-align: center;
        }

        .staff-only-fields {
            display: none;
            border: 2px solid #e74c3c;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            background-color: #fdf2f2;
        }

        .staff-only-fields.show {
            display: block;
        }

        .staff-only-fields h3 {
            color: #e74c3c;
            margin-top: 0;
            margin-bottom: 15px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        /* Bulk creation styles */
        .bulk-mode {
            display: none;
        }

        .bulk-mode.show {
            display: block;
        }

        .standard-mode {
            display: block;
        }

        .standard-mode.hide {
            display: none;
        }

        .employee-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .employee-table th,
        .employee-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .employee-table input[type="number"] {
            width: 80px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            min-width: 300px;
            max-width: 500px;
        }

        .modal-buttons {
            margin-top: 15px;
            text-align: right;
        }

        .modal-buttons button {
            margin-left: 10px;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .modal-buttons button:first-child {
            background-color: #e74c3c;
            color: white;
        }

        .modal-buttons button:last-child {
            background-color: #95a5a6;
            color: white;
        }

        /* Edit button styles */
        .btn-edit {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 8px;
            vertical-align: middle;
            display: inline-block;
        }

        .btn-edit:hover {
            background-color: #c84132ff;
        }

        /* Evaluator table specific styles */
        .evaluator-table td {
            vertical-align: middle;
            padding: 8px 12px;
            position: relative;
        }

        .evaluator-table .evaluator-cell {
            position: relative;
            padding-right: 50px;
        }

        .evaluator-table .evaluator-name {
            display: inline-block;
            width: calc(100% - 50px);
        }

        .mode-indicator {
            background-color: #d4edda;
            /* Light green background */
            border: 1px solid #c3e6cb;
            /* Green border */
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 20px;
            color: #155724;
            /* Dark green text */
        }


        .mode-indicator.bulk {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="logo-section">
                    <img src="assets/seiwa.logo.png" alt="Seiwa Kaiun Philippines Inc." class="logo">
                    <div class="company-info">
                        <h1>Performance Evaluation System</h1>
                        <p>Seiwa Kaiun Philippines Inc.</p>
                    </div>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($user['fullname']); ?></h3>
                    <p><strong>ADMIN</strong> - <?php echo htmlspecialchars($user['department_name']); ?></p>
                    <p>Employee ID: <?php echo htmlspecialchars($user['card_no']); ?></p>
                    <button onclick="location.href='admin_dashboard.php'" class="btn btn-secondary">Back to Dashboard</button>
                    <button onclick="location.href='admin_dashboard.php'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Create New Evaluation</h2>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                    <br><br>
                    <a href="admin_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                </div>
            <?php else: ?>
                <!-- Mode Indicator -->
                <div id="mode-indicator" class="mode-indicator">
                    <strong>Standard Mode:</strong> Create evaluation for a single employee
                </div>

                <form method="POST" action="" id="evaluation-form">
                    <!-- Evaluation Reason - First Field -->
                    <div class="form-group">
                        <label for="evaluation_reason" class="form-label">Reason for Evaluation *</label>
                        <select name="evaluation_reason" id="evaluation_reason" class="form-control" required>
                            <option value="">-- Select Reason --</option>
                            <?php foreach ($evaluation_reasons as $reason): ?>
                                <?php
                                $value = $reason['reason_name'];
                                $selected = (isset($_POST['evaluation_reason']) && $_POST['evaluation_reason'] === $value) ? 'selected' : '';
                                ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($value); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="Other" <?php echo (isset($_POST['evaluation_reason']) && $_POST['evaluation_reason'] === 'Other') ? 'selected' : ''; ?>>
                                Others, Please specify:
                            </option>
                        </select>

                        <input type="text"
                            id="other_reason"
                            name="other_reason"
                            class="form-control mt-2"
                            placeholder="Please specify"
                            style="display: <?php echo (isset($_POST['evaluation_reason']) && $_POST['evaluation_reason'] === 'Other') ? 'block' : 'none'; ?>;"
                            value="<?php echo isset($_POST['other_reason']) ? htmlspecialchars($_POST['other_reason']) : ''; ?>" />
                    </div>

                    <!-- Standard Mode Fields -->
                    <div id="standard-mode" class="standard-mode">
                        <div class="form-group">
                            <label for="role_id" class="form-label">Select Role for Evaluation *</label>
                            <select name="role_id" id="role_id" class="form-control" required>
                                <option value="">-- Select Role --</option>
                                <?php foreach ($available_roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>" <?php echo ($selected_role_id == $role['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="department_id" class="form-label">Select Department *</label>
                            <select name="department_id" id="department_id" class="form-control" required>
                                <option value="">-- Select Department --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="section_id" class="form-label">Select Section *</label>
                            <select name="section_id" id="section_id" class="form-control" required>
                                <option value="">-- Select Section --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="employee_id" class="form-label">Select Employee *</label>
                            <select name="employee_id" id="employee_id" class="form-control" required>
                                <option value="">-- Select Employee --</option>
                            </select>
                        </div>

                        <!-- Evaluator Table for Standard Mode -->
                        <div id="evaluator-table-container" style="display: none;">
                            <h4 style="color: #A31D1D">Assigned Evaluators</h4>
                            <table class="employee-table evaluator-table" id="evaluator-table">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>HR</th>
                                        <th>Evaluator 1</th>
                                        <th>Evaluator 2</th>
                                        <th>Evaluator 3</th>
                                    </tr>
                                </thead>
                                <tbody id="evaluator-table-body">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bulk Mode Fields -->
                    <div id="bulk-mode" class="bulk-mode">
                        <input type="hidden" name="bulk_mode" value="1">

                        <div class="form-group">
                            <label for="bulk_role_id" class="form-label">Select Role for Bulk Evaluation *</label>
                            <select name="role_id" id="bulk_role_id" class="form-control" required>
                                <option value="">-- Select Role --</option>
                                <?php foreach ($available_roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>">
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="bulk_department_id" class="form-label">Select Department *</label>
                            <select name="department_id" id="bulk_department_id" class="form-control" required>
                                <option value="">-- Select Department --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="bulk_section_id" class="form-label">Select Section *</label>
                            <select name="section_id" id="bulk_section_id" class="form-control" required>
                                <option value="">-- Select Section --</option>
                            </select>
                        </div>

                        <div id="bulk-employee-list" style="display: none;">
                            <h4 style="color: #A31D1D">Employees for Bulk Evaluation</h4>
                            <div id="employee-table-container"></div>
                            
                            <!-- Evaluator Table for Bulk Mode -->
                            <div id="bulk-evaluator-table-container" style="display: none;">
                                <h4 style="color: #A31D1D">Assigned Evaluators</h4>
                                <table class="employee-table evaluator-table" id="bulk-evaluator-table">
                                    <thead>
                                        <tr>
                                            <th>Employee Name</th>
                                            <th>HR</th>
                                            <th>Evaluator 1</th>
                                            <th>Evaluator 2</th>
                                            <th>Evaluator 3</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulk-evaluator-table-body">
                                    </tbody>
                                </table>
                            </div>
                            
                            <div id="bulk-additional-info" style="display: none;">
                                <h4 style="color: #A31D1D">Additional Information</h4>
                                <div id="additional-info-table-container"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Period Fields (Common to both modes) -->
                    <div class="form-group">
                        <label for="period_from" class="form-label">Period Covered From *</label>
                        <input type="date" name="period_from" id="period_from" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="period_to" class="form-label">Period Covered To *</label>
                        <input type="date" name="period_to" id="period_to" class="form-control" required>
                    </div>

                    <!-- Additional Fields for Staff Evaluations Only (Standard Mode) -->
                    <div id="staff-only-fields" class="staff-only-fields">
                        <h3>Additional Information for Staff Evaluation</h3>

                        <h4>Attendance & Punctuality</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="approved_leaves" class="form-label">Number of Approved Leaves</label>
                                <input type="number" name="approved_leaves" id="approved_leaves" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="disapproved_leaves" class="form-label">Number of Disapproved Leaves</label>
                                <input type="number" name="disapproved_leaves" id="disapproved_leaves" class="form-control" min="0" value="0">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="tardiness" class="form-label">Number of Tardiness</label>
                                <input type="number" name="tardiness" id="tardiness" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="late_undertime" class="form-label">Number of Late/Undertime</label>
                                <input type="number" name="late_undertime" id="late_undertime" class="form-control" min="0" value="0">
                            </div>
                        </div>

                        <h4>Number of Violations</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="offense_1st" class="form-label">1st Offense</label>
                                <input type="number" name="offense_1st" id="offense_1st" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="offense_2nd" class="form-label">2nd Offense</label>
                                <input type="number" name="offense_2nd" id="offense_2nd" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="offense_3rd" class="form-label">3rd Offense</label>
                                <input type="number" name="offense_3rd" id="offense_3rd" class="form-control" min="0" value="0">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="offense_4th" class="form-label">4th Offense</label>
                                <input type="number" name="offense_4th" id="offense_4th" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label for="offense_5th" class="form-label">5th Offense</label>
                                <input type="number" name="offense_5th" id="offense_5th" class="form-control" min="0" value="0">
                            </div>
                        </div>

                        <h4>Suspensions</h4>
                        <div class="form-group">
                            <label for="suspension_days" class="form-label">Number of Suspension Days</label>
                            <input type="number" name="suspension_days" id="suspension_days" class="form-control" min="0" value="0">
                        </div>
                    </div>

                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary" id="submit-btn">Create Evaluation</button>
                        <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Global variables
        let currentMode = 'standard';
        let bulkEmployees = [];

        // Initialize form on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set default dates
            const oneYearAgo = new Date();
            oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);
            document.getElementById('period_from').valueAsDate = oneYearAgo;

            const today = new Date();
            document.getElementById('period_to').valueAsDate = today;

            // Initialize event listeners
            initializeEventListeners();

            // Initialize form state
            handleEvaluationReasonChange();
        });

        function initializeEventListeners() {
            // Evaluation reason change
            document.getElementById('evaluation_reason').addEventListener('change', handleEvaluationReasonChange);

            // Standard mode events
            document.getElementById('role_id').addEventListener('change', handleStandardRoleChange);
            document.getElementById('department_id').addEventListener('change', handleStandardDepartmentChange);
            document.getElementById('section_id').addEventListener('change', handleStandardSectionChange);
            document.getElementById('employee_id').addEventListener('change', handleStandardEmployeeChange);

            // Bulk mode events
            document.getElementById('bulk_role_id').addEventListener('change', handleBulkRoleChange);
            document.getElementById('bulk_department_id').addEventListener('change', handleBulkDepartmentChange);
            document.getElementById('bulk_section_id').addEventListener('change', handleBulkSectionChange);
        }

        function handleEvaluationReasonChange() {
            const reasonSelect = document.getElementById('evaluation_reason');
            const otherInput = document.getElementById('other_reason');
            const modeIndicator = document.getElementById('mode-indicator');
            const standardMode = document.getElementById('standard-mode');
            const bulkMode = document.getElementById('bulk-mode');
            const submitBtn = document.getElementById('submit-btn');

            // Handle "Other" option
            if (reasonSelect.value === 'Other') {
                otherInput.style.display = 'block';
                otherInput.required = true;
            } else {
                otherInput.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }

            // Handle mode switching
            if (reasonSelect.value === 'Semi-Annual') {
                // Switch to bulk mode
                currentMode = 'bulk';
                standardMode.classList.add('hide');
                bulkMode.classList.add('show');
                modeIndicator.innerHTML = '<strong>Bulk Mode:</strong> Create evaluations for multiple employees at once';
                modeIndicator.classList.add('bulk');
                submitBtn.textContent = 'Create Bulk Evaluations';

                // Clear standard mode requirements
                clearStandardModeRequirements();
            } else {
                // Switch to standard mode
                currentMode = 'standard';
                standardMode.classList.remove('hide');
                bulkMode.classList.remove('show');
                modeIndicator.innerHTML = '<strong>Standard Mode:</strong> Create evaluation for a single employee';
                modeIndicator.classList.remove('bulk');
                submitBtn.textContent = 'Create Evaluation';

                // Clear bulk mode requirements
                clearBulkModeRequirements();
            }

            // Update staff fields visibility
            toggleStaffFields();
        }

        function clearStandardModeRequirements() {
            const fields = ['role_id', 'department_id', 'section_id', 'employee_id'];
            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.required = false;
                    field.value = '';
                }
            });
        }

        function clearBulkModeRequirements() {
            const fields = ['bulk_role_id', 'bulk_department_id', 'bulk_section_id'];
            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.required = false;
                    field.value = '';
                }
            });

            // Hide bulk employee list
            document.getElementById('bulk-employee-list').style.display = 'none';
        }

        // Standard mode handlers
        function handleStandardRoleChange() {
            const roleId = document.getElementById('role_id').value;
            const departmentSelect = document.getElementById('department_id');

            // Clear dependent dropdowns
            clearSelect(departmentSelect);
            clearSelect(document.getElementById('section_id'));
            clearSelect(document.getElementById('employee_id'));

            if (roleId) {
                loadDepartments(roleId, departmentSelect);
            }

            toggleStaffFields();
        }

        function handleStandardDepartmentChange() {
            const roleId = document.getElementById('role_id').value;
            const departmentId = document.getElementById('department_id').value;
            const sectionSelect = document.getElementById('section_id');

            // Clear dependent dropdowns
            clearSelect(sectionSelect);
            clearSelect(document.getElementById('employee_id'));

            if (roleId && departmentId) {
                loadSections(departmentId, roleId, sectionSelect);
            }
        }

        function handleStandardSectionChange() {
            const roleId = document.getElementById('role_id').value;
            const departmentId = document.getElementById('department_id').value;
            const sectionId = document.getElementById('section_id').value;
            const employeeSelect = document.getElementById('employee_id');

            // Clear employee dropdown
            clearSelect(employeeSelect);

            if (roleId && departmentId && sectionId) {
                loadEmployees(roleId, departmentId, sectionId, employeeSelect);
            }
        }

        function handleStandardEmployeeChange() {
            const employeeId = document.getElementById('employee_id').value;
            
            if (employeeId) {
                loadEvaluatorsForEmployee(employeeId);
            } else {
                document.getElementById('evaluator-table-container').style.display = 'none';
            }
        }

        // Add fallback function to show evaluator table even if AJAX fails
        function showFallbackEvaluatorTable(employeeId) {
            const container = document.getElementById('evaluator-table-container');
            const tbody = document.getElementById('evaluator-table-body');
            
            // Get employee name
            const employeeSelect = document.getElementById('employee_id');
            const employeeName = employeeSelect.options[employeeSelect.selectedIndex].text.split(' (')[0];
            
            let tableHTML = `
                <tr>
                    <td>${employeeName}</td>
                    <td>HR Person (Loading...)</td>
                    <td>
                        <div class="evaluator-cell">
                            <span class="evaluator-name">Evaluator 1 (Loading...)</span>
                            <button type="button" class="btn-edit" onclick="editEvaluator(${employeeId}, 'approver1_id', 4)">Edit</button>
                        </div>
                    </td>
                    <td>
                        <div class="evaluator-cell">
                            <span class="evaluator-name">Evaluator 2 (Loading...)</span>
                            <button type="button" class="btn-edit" onclick="editEvaluator(${employeeId}, 'approver2_id', 3)">Edit</button>
                        </div>
                    </td>
                    <td>
                        <div class="evaluator-cell">
                            <span class="evaluator-name">Evaluator 3 (Loading...)</span>
                            <button type="button" class="btn-edit" onclick="editEvaluator(${employeeId}, 'approver3_id', 2)">Edit</button>
                        </div>
                    </td>
                </tr>
            `;
            
            tbody.innerHTML = tableHTML;
            container.style.display = 'block';
        }

        // Bulk mode handlers
        function handleBulkRoleChange() {
            const roleId = document.getElementById('bulk_role_id').value;
            const departmentSelect = document.getElementById('bulk_department_id');

            // Clear dependent dropdowns
            clearSelect(departmentSelect);
            clearSelect(document.getElementById('bulk_section_id'));

            // Hide employee list
            document.getElementById('bulk-employee-list').style.display = 'none';

            if (roleId) {
                loadDepartments(roleId, departmentSelect);
            }
        }

        function handleBulkDepartmentChange() {
            const roleId = document.getElementById('bulk_role_id').value;
            const departmentId = document.getElementById('bulk_department_id').value;
            const sectionSelect = document.getElementById('bulk_section_id');

            // Clear section dropdown
            clearSelect(sectionSelect);

            // Hide employee list
            document.getElementById('bulk-employee-list').style.display = 'none';

            if (roleId && departmentId) {
                loadSections(departmentId, roleId, sectionSelect);
            }
        }

        function handleBulkSectionChange() {
            const roleId = document.getElementById('bulk_role_id').value;
            const departmentId = document.getElementById('bulk_department_id').value;
            const sectionId = document.getElementById('bulk_section_id').value;

            if (roleId && departmentId && sectionId) {
                loadBulkEmployees(roleId, departmentId, sectionId);
            } else {
                document.getElementById('bulk-employee-list').style.display = 'none';
            }
        }

        // Helper functions
        function clearSelect(selectElement) {
            selectElement.innerHTML = '<option value="">-- Select --</option>';
        }

        function loadDepartments(roleId, targetSelect) {
            targetSelect.innerHTML = '<option value="">-- Loading... --</option>';

            fetch(`?ajax=get_departments&role_id=${roleId}`)
                .then(response => response.json())
                .then(departments => {
                    targetSelect.innerHTML = '<option value="">-- Select Department --</option>';
                    departments.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.name;
                        targetSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading departments:', error);
                    targetSelect.innerHTML = '<option value="">-- Error loading departments --</option>';
                });
        }

        function loadSections(departmentId, roleId, targetSelect) {
            targetSelect.innerHTML = '<option value="">-- Loading... --</option>';

            fetch(`?ajax=get_sections&department_id=${departmentId}&role_id=${roleId}`)
                .then(response => response.json())
                .then(sections => {
                    targetSelect.innerHTML = '<option value="">-- Select Section --</option>';
                    sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.id;
                        option.textContent = section.name;
                        targetSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    targetSelect.innerHTML = '<option value="">-- Error loading sections --</option>';
                });
        }

        function loadEmployees(roleId, departmentId, sectionId, targetSelect) {
            targetSelect.innerHTML = '<option value="">-- Loading... --</option>';

            fetch(`?ajax=get_employees&role_id=${roleId}&department_id=${departmentId}&section_id=${sectionId}`)
                .then(response => response.json())
                .then(employees => {
                    targetSelect.innerHTML = '<option value="">-- Select Employee --</option>';
                    employees.forEach(employee => {
                        const option = document.createElement('option');
                        option.value = employee.id;
                        option.textContent = `${employee.fullname} (${employee.card_no}) - ${employee.position_name}`;
                        targetSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error loading employees:', error);
                    targetSelect.innerHTML = '<option value="">-- Error loading employees --</option>';
                });
        }

        function loadBulkEmployees(roleId, departmentId, sectionId) {
            fetch(`?ajax=get_bulk_employees&role_id=${roleId}&department_id=${departmentId}&section_id=${sectionId}`)
                .then(response => response.json())
                .then(employees => {
                    bulkEmployees = employees;
                    displayBulkEmployees(employees, roleId);
                })
                .catch(error => {
                    console.error('Error loading bulk employees:', error);
                });
        }

        function displayBulkEmployees(employees, roleId) {
            const container = document.getElementById('employee-table-container');
            const bulkList = document.getElementById('bulk-employee-list');
            const additionalInfo = document.getElementById('bulk-additional-info');
            const additionalInfoContainer = document.getElementById('additional-info-table-container');

            if (employees.length === 0) {
                container.innerHTML = '<p>No employees found for the selected criteria.</p>';
                bulkList.style.display = 'block';
                additionalInfo.style.display = 'none';
                return;
            }

            // Create employee list table
            let tableHTML = `
                <table class="employee-table">
                    <thead>
                        <tr">
                            <th>Employee Name</th>
                            <th>Card No</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Section</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            employees.forEach(employee => {
                tableHTML += `
                    <tr>
                        <td>${employee.fullname}</td>
                        <td>${employee.card_no}</td>
                        <td>${employee.position_name || 'N/A'}</td>
                        <td>${employee.department_name || 'N/A'}</td>
                        <td>${employee.section_name || 'N/A'}</td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            container.innerHTML = tableHTML;

            // Load and display evaluators for bulk mode
            loadBulkEvaluators(employees);

            // Show additional information table for Staff role
            if (roleId == '5') {
                let additionalHTML = `
                    <table class="employee-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Approved Leaves</th>
                                <th>Disapproved Leaves</th>
                                <th>Tardiness</th>
                                <th>Late/<br>Undertime</th>
                                <th>1st Offense</th>
                                <th>2nd Offense</th>
                                <th>3rd Offense</th>
                                <th>4th Offense</th>
                                <th>5th Offense</th>
                                <th>Suspension Days</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                employees.forEach(employee => {
                    const prefix = `emp_${employee.id}_`;
                    additionalHTML += `
                        <tr>
                            <td>${employee.fullname}</td>
                            <td><input type="number" name="${prefix}approved_leaves" min="0" value="0"></td>
                            <td><input type="number" name="${prefix}disapproved_leaves" min="0" value="0"></td>
                            <td><input type="number" name="${prefix}tardiness" min="0" value="0"></td>
                            <td><input type="number" name="${prefix}late_undertime" min="0" value="0"></td>
                            <td><input type="number" name="${prefix}offense_1st" min="0" value="0"></td>
                            <td><input type="number" name="${prefix}offense_2nd" min="0" value="0"></td>
                            <td><input type="number" name="${prefix}offense_3rd" min="0" value="0"></td>
                            <td><input type="number" name="${prefix}offense_4th" min="0" value="0"></td>
                            <td><input type="number" name="${prefix}offense_5th" min="0" value="0"></td>
                            <td><input type="number" name="${prefix}suspension_days" min="0" value="0"></td>
                        </tr>
                    `;
                });

                additionalHTML += '</tbody></table>';
                additionalInfoContainer.innerHTML = additionalHTML;
                additionalInfo.style.display = 'block';
            } else {
                additionalInfo.style.display = 'none';
            }

            bulkList.style.display = 'block';
        }

        function toggleStaffFields() {
            const roleId = currentMode === 'standard' ?
                document.getElementById('role_id').value :
                document.getElementById('bulk_role_id').value;
            const evaluationReason = document.getElementById('evaluation_reason').value;
            const staffFields = document.getElementById('staff-only-fields');

            // Show only if role is Staff (5) and reason is selected and in standard mode
            if (roleId == '5' && evaluationReason !== '' && currentMode === 'standard') {
                staffFields.classList.add('show');
            } else {
                staffFields.classList.remove('show');
            }
        }

        // Evaluator management functions
        function loadEvaluatorsForEmployee(employeeId) {
            console.log('Loading evaluators for employee:', employeeId);
            fetch(`?ajax=get_evaluators&employee_id=${employeeId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(evaluators => {
                    console.log('Evaluators received:', evaluators);
                    if (evaluators.error) {
                        console.error('Error from server:', evaluators.error);
                        // Show fallback table instead of alert
                        showFallbackEvaluatorTable(employeeId);
                    } else {
                        displayEvaluatorTable(employeeId, evaluators);
                    }
                })
                .catch(error => {
                    console.error('Error loading evaluators:', error);
                    // Show fallback table instead of alert
                    showFallbackEvaluatorTable(employeeId);
                });
        }

        function displayEvaluatorTable(employeeId, evaluators) {
            const container = document.getElementById('evaluator-table-container');
            const tbody = document.getElementById('evaluator-table-body');
            
            // Get employee name
            const employeeSelect = document.getElementById('employee_id');
            const employeeName = employeeSelect.options[employeeSelect.selectedIndex].text.split(' (')[0];
            
            let tableHTML = `
                <tr>
                    <td>${employeeName}</td>
                    <td>${evaluators.hr ? evaluators.hr.fullname : 'Not Assigned'}</td>
                    <td>
                        <div class="evaluator-cell">
                            <span class="evaluator-name">${evaluators.evaluator1 ? evaluators.evaluator1.fullname : 'Not Assigned'}</span>
                            <button type="button" class="btn-edit" onclick="editEvaluator(${employeeId}, 'approver1_id', 4)">Edit</button>
                        </div>
                    </td>
                    <td>
                        <div class="evaluator-cell">
                            <span class="evaluator-name">${evaluators.evaluator2 ? evaluators.evaluator2.fullname : 'Not Assigned'}</span>
                            <button type="button" class="btn-edit" onclick="editEvaluator(${employeeId}, 'approver2_id', 3)">Edit</button>
                        </div>
                    </td>
                    <td>
                        <div class="evaluator-cell">
                            <span class="evaluator-name">${evaluators.evaluator3 ? evaluators.evaluator3.fullname : 'Not Assigned'}</span>
                            <button type="button" class="btn-edit" onclick="editEvaluator(${employeeId}, 'approver3_id', 2)">Edit</button>
                        </div>
                    </td>
                </tr>
            `;
            
            tbody.innerHTML = tableHTML;
            container.style.display = 'block';
        }

        function loadBulkEvaluators(employees) {
            console.log('Loading bulk evaluators for employees:', employees);
            const container = document.getElementById('bulk-evaluator-table-container');
            const tbody = document.getElementById('bulk-evaluator-table-body');
            
            // Clear existing content
            tbody.innerHTML = '';
            
            // Show fallback table immediately
            let fallbackHTML = '';
            employees.forEach(employee => {
                fallbackHTML += `
                    <tr>
                        <td>${employee.fullname}</td>
                        <td>HR Person (Loading...)</td>
                        <td>
                            <div class="evaluator-cell">
                                <span class="evaluator-name">Evaluator 1 (Loading...)</span>
                                <button type="button" class="btn-edit" onclick="editEvaluator(${employee.id}, 'approver1_id', 4)">Edit</button>
                            </div>
                        </td>
                        <td>
                            <div class="evaluator-cell">
                                <span class="evaluator-name">Evaluator 2 (Loading...)</span>
                                <button type="button" class="btn-edit" onclick="editEvaluator(${employee.id}, 'approver2_id', 3)">Edit</button>
                            </div>
                        </td>
                        <td>
                            <div class="evaluator-cell">
                                <span class="evaluator-name">Evaluator 3 (Loading...)</span>
                                <button type="button" class="btn-edit" onclick="editEvaluator(${employee.id}, 'approver3_id', 2)">Edit</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = fallbackHTML;
            container.style.display = 'block';
            
            // Try to load actual evaluator data
            const evaluatorPromises = employees.map(employee => 
                fetch(`?ajax=get_evaluators&employee_id=${employee.id}`)
                    .then(response => {
                        console.log(`Response for employee ${employee.id}:`, response.status);
                        return response.json();
                    })
                    .then(evaluators => {
                        console.log(`Evaluators for employee ${employee.id}:`, evaluators);
                        return {
                            employee: employee,
                            evaluators: evaluators
                        };
                    })
                    .catch(error => {
                        console.error('Error loading evaluators for employee:', employee.id, error);
                        return {
                            employee: employee,
                            evaluators: {}
                        };
                    })
            );
            
            // Wait for all requests to complete and update table if successful
            Promise.all(evaluatorPromises).then(results => {
                console.log('All evaluator requests completed:', results);
                let tableHTML = '';
                
                results.forEach(result => {
                    const { employee, evaluators } = result;
                    // Only update if we have valid evaluator data
                    if (evaluators && !evaluators.error) {
                        tableHTML += `
                            <tr>
                                <td>${employee.fullname}</td>
                                <td>${evaluators.hr ? evaluators.hr.fullname : 'Not Assigned'}</td>
                                <td>
                                    <div class="evaluator-cell">
                                        <span class="evaluator-name">${evaluators.evaluator1 ? evaluators.evaluator1.fullname : 'Not Assigned'}</span>
                                        <button type="button" class="btn-edit" onclick="editEvaluator(${employee.id}, 'approver1_id', 4)">Edit</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="evaluator-cell">
                                        <span class="evaluator-name">${evaluators.evaluator2 ? evaluators.evaluator2.fullname : 'Not Assigned'}</span>
                                        <button type="button" class="btn-edit" onclick="editEvaluator(${employee.id}, 'approver2_id', 3)">Edit</button>
                                    </div>
                                </td>
                                <td>
                                    <div class="evaluator-cell">
                                        <span class="evaluator-name">${evaluators.evaluator3 ? evaluators.evaluator3.fullname : 'Not Assigned'}</span>
                                        <button type="button" class="btn-edit" onclick="editEvaluator(${employee.id}, 'approver3_id', 2)">Edit</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }
                });
                
                // Only update if we have actual data
                if (tableHTML) {
                    console.log('Updating table with actual data');
                    tbody.innerHTML = tableHTML;
                }
            }).catch(error => {
                console.error('Error in Promise.all:', error);
                // Keep the fallback table visible
            });
        }

        function editEvaluator(employeeId, evaluatorPosition, roleId) {
            // Get employee department
            const employee = bulkEmployees ? bulkEmployees.find(emp => emp.id == employeeId) : null;
            const departmentId = employee ? employee.department_id : document.getElementById('department_id').value;
            
            if (!departmentId) {
                alert('Department information not available');
                return;
            }
            
            // Load potential replacements
            fetch(`?ajax=get_potential_replacements&role_id=${roleId}&department_id=${departmentId}`)
                .then(response => response.json())
                .then(replacements => {
                    showEvaluatorModal(employeeId, evaluatorPosition, replacements);
                })
                .catch(error => {
                    console.error('Error loading potential replacements:', error);
                    alert('Error loading potential replacements');
                });
        }

        function showEvaluatorModal(employeeId, evaluatorPosition, replacements) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-content">
                    <h3>Select New Evaluator</h3>
                    <select id="replacement-select" class="form-control">
                        <option value="">-- Select Evaluator --</option>
                        ${replacements.map(emp => `<option value="${emp.id}">${emp.fullname} (${emp.card_no})</option>`).join('')}
                    </select>
                    <div class="modal-buttons">
                        <button type="button" onclick="updateEvaluator(${employeeId}, '${evaluatorPosition}', document.getElementById('replacement-select').value); closeModal();">Update</button>
                        <button type="button" onclick="closeModal();">Cancel</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }

        function updateEvaluator(employeeId, evaluatorPosition, newEvaluatorId) {
            if (!newEvaluatorId) {
                alert('Please select an evaluator');
                return;
            }
            
            const formData = new FormData();
            formData.append('employee_id', employeeId);
            formData.append('evaluator_position', evaluatorPosition);
            formData.append('new_evaluator_id', newEvaluatorId);
            
            fetch('?ajax=update_evaluator', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Reload evaluator tables
                    if (currentMode === 'standard') {
                        const employeeId = document.getElementById('employee_id').value;
                        if (employeeId) {
                            loadEvaluatorsForEmployee(employeeId);
                        }
                    } else {
                        // Reload bulk evaluators
                        if (bulkEmployees) {
                            loadBulkEvaluators(bulkEmployees);
                        }
                    }
                    alert('Evaluator updated successfully');
                } else {
                    alert('Failed to update evaluator');
                }
            })
            .catch(error => {
                console.error('Error updating evaluator:', error);
                alert('Error updating evaluator');
            });
        }

        function closeModal() {
            const modal = document.querySelector('.modal-overlay');
            if (modal) {
                modal.remove();
            }
        }
    </script>
</body>

</html>