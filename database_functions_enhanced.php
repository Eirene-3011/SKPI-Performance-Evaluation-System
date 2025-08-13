<?php
require_once 'config.php';

// Function to get employees by role ID
function getEmployeesByRole($role_id)
{
    global $conn;

    $sql = "SELECT e.*, er.name as role_name, ed.name as department_name, ep.name as position_name, es.name as section_name 
            FROM employees e 
            LEFT JOIN emp_roles er ON e.role_id = er.id 
            LEFT JOIN emp_department ed ON e.department_id = ed.id 
            LEFT JOIN emp_positions ep ON e.position_id = ep.id 
            LEFT JOIN emp_sections es ON e.section_id = es.id 
            WHERE e.is_inactive = 0 AND e.role_id = ? 
            ORDER BY e.fullname";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all employees with role_id = 5 (Staff) - kept for backward compatibility
function getStaffEmployees()
{
    return getEmployeesByRole(5);
}

// Function to get all employees
function getAllEmployees()
{
    global $conn;

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

// Function to get employee by ID
function getEmployeeById($id)
{
    global $conn;

    $sql = "SELECT e.*, er.name as role_name, ed.name as department_name, ep.name as position_name, es.name as section_name 
            FROM employees e 
            LEFT JOIN emp_roles er ON e.role_id = er.id 
            LEFT JOIN emp_department ed ON e.department_id = ed.id 
            LEFT JOIN emp_positions ep ON e.position_id = ep.id 
            LEFT JOIN emp_sections es ON e.section_id = es.id 
            WHERE e.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// Function to load approvers from CSV file
function loadApproversFromCSV()
{
    $approvers = [];
    $csv_file = __DIR__ . '/approvers.csv';
    
    if (file_exists($csv_file)) {
        $handle = fopen($csv_file, 'r');
        $header = fgetcsv($handle); // Skip header row
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $employee_id = (int)$data[0];
            $approver1_id = !empty($data[1]) && $data[1] !== '\\N' ? (int)$data[1] : null;
            $approver2_id = !empty($data[2]) && $data[2] !== '\\N' ? (int)$data[2] : null;
            $approver3_id = !empty($data[3]) && $data[3] !== '\\N' ? (int)$data[3] : null;
            
            $approvers[$employee_id] = [
                'approver1_id' => $approver1_id,
                'approver2_id' => $approver2_id,
                'approver3_id' => $approver3_id
            ];
        }
        fclose($handle);
    }
    
    return $approvers;
}

// Function to get specific evaluators for an employee
function getEmployeeEvaluators($employee_id)
{
    global $conn;
    
    // First check if there are admin-edited evaluators in database
    $sql = "SELECT * FROM approvers WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Use database values (admin-edited)
        $row = $result->fetch_assoc();
        return [
            'approver1_id' => $row['approver1_id'],
            'approver2_id' => $row['approver2_id'],
            'approver3_id' => $row['approver3_id']
        ];
    } else {
        // Fall back to CSV defaults
        $csv_approvers = loadApproversFromCSV();
        return $csv_approvers[$employee_id] ?? [
            'approver1_id' => null,
            'approver2_id' => null,
            'approver3_id' => null
        ];
    }
}

// Function to get HR evaluator by designation_id
// Function to get HR evaluator based on employee's designation and department
function getHREvaluatorByDesignationAndDepartment($designation_id, $department_id)
{
    global $conn;

    $sql = "";
    $params = [];
    $types = "";

    // Rule 1: HR with role_id=1, designation_id=1, department_id=3
    if ($designation_id == 1 && $department_id == 3) {
        $sql = "SELECT id, email, fullname FROM employees 
                WHERE role_id = 1 AND designation_id = 1 AND department_id = 3 AND is_inactive = 0 
                LIMIT 1";
    }

    // Rule 2: HR with role_id=1, designation_id=1, department_id=7
    elseif ($designation_id == 1 && in_array($department_id, [1, 2, 4, 5, 6, 7, 8])) {
        $sql = "SELECT id, email, fullname FROM employees 
                WHERE role_id = 1 AND designation_id = 1 AND department_id = 7 AND is_inactive = 0 
                LIMIT 1";
    }

    // Rule 3: HR with role_id=1 and designation_id=2
    elseif ($designation_id == 2) {
        $sql = "SELECT id, email, fullname FROM employees 
                WHERE role_id = 1 AND designation_id = 2 AND is_inactive = 0 
                LIMIT 1";
    }

    // Rule 4: HR with role_id=1 and designation_id=3
    elseif ($designation_id == 3) {
        $sql = "SELECT id, email, fullname FROM employees 
                WHERE role_id = 1 AND designation_id = 3 AND is_inactive = 0 
                LIMIT 1";
    }

    if (empty($sql)) {
        return null; // No matching HR rule
    }

    $result = $conn->query($sql);
    return $result->fetch_assoc();
}


// Function to get evaluation workflow for a specific employee role
function getEvaluationWorkflowByRole($employee_role_id)
{
    // Define workflows based on role
    $workflows = [
        5 => [1, 4, 3, 2], // Staff: HR -> Shift Leader -> Supervisor -> Manager
        4 => [1, 3, 2],    // Shift Leader: HR -> Supervisor -> Manager
        3 => [1, 2]        // Supervisor: HR -> Manager
    ];

    return $workflows[$employee_role_id] ?? [1]; // Default to HR only if role not found
}

function getEvaluatorSequence($employee_id, $employee_role_id, $designation_id)
{
    $employee = getEmployeeById($employee_id);
    $evaluators = getEmployeeEvaluators($employee_id);
    $sequence = [];

    // Get HR evaluator based on designation & department
    $hr_evaluator = getHREvaluatorByDesignationAndDepartment($designation_id, $employee['department_id']);
    if ($hr_evaluator) {
        $sequence[] = [
            'evaluator_id' => $hr_evaluator['id'],
            'role_id' => 1,
            'step_order' => 1
        ];
    }

    // Continue same logic for other approvers...
    $step_order = 2;
    if ($employee_role_id == 5) { // Staff
        if ($evaluators['approver1_id']) {
            $sequence[] = [
                'evaluator_id' => $evaluators['approver1_id'],
                'role_id' => 4, // Shift Leader
                'step_order' => $step_order++
            ];
        }
        if ($evaluators['approver2_id']) {
            $sequence[] = [
                'evaluator_id' => $evaluators['approver2_id'],
                'role_id' => 3, // Supervisor
                'step_order' => $step_order++
            ];
        }
        if ($evaluators['approver3_id']) {
            $sequence[] = [
                'evaluator_id' => $evaluators['approver3_id'],
                'role_id' => 2, // Manager
                'step_order' => $step_order++
            ];
        }
    } elseif ($employee_role_id == 4) { // Shift Leader
        if ($evaluators['approver2_id']) {
            $sequence[] = [
                'evaluator_id' => $evaluators['approver2_id'],
                'role_id' => 3, // Supervisor
                'step_order' => $step_order++
            ];
        }
        if ($evaluators['approver3_id']) {
            $sequence[] = [
                'evaluator_id' => $evaluators['approver3_id'],
                'role_id' => 2, // Manager
                'step_order' => $step_order++
            ];
        }
    } elseif ($employee_role_id == 3) { // Supervisor
        if ($evaluators['approver3_id']) {
            $sequence[] = [
                'evaluator_id' => $evaluators['approver3_id'],
                'role_id' => 2, // Manager
                'step_order' => $step_order++
            ];
        }
    }

    return $sequence;
}


// Function to create new evaluation with role-specific workflow
function createEvaluationWithRole($employee_id, $employee_role_id, $evaluation_reason, $period_from, $period_to, $additional_fields = [])
{
    global $conn;

    // Get employee details to get designation_id
    $employee = getEmployeeById($employee_id);
    if (!$employee) {
        return false;
    }

    // Get the evaluator sequence for this employee
    $evaluator_sequence = getEvaluatorSequence($employee_id, $employee_role_id, $employee['designation_id']);
    
    if (empty($evaluator_sequence)) {
        return false; // No evaluators found
    }

    // The first evaluator in sequence
    $first_evaluator = $evaluator_sequence[0];

    // Build the SQL query dynamically based on additional fields
    $base_sql = "INSERT INTO evaluations (employee_id, evaluation_reason, evaluation_date, period_covered_from, period_covered_to, status, current_evaluator_id, current_evaluator_role_id";
    $values_sql = "VALUES (?, ?, CURDATE(), ?, ?, 'pending', ?, ?";
    $params = [$employee_id, $evaluation_reason, $period_from, $period_to, $first_evaluator['evaluator_id'], $first_evaluator['role_id']];
    $param_types = "isssii";

    // Add additional fields for Staff evaluations
    if (!empty($additional_fields)) {
        foreach ($additional_fields as $field => $value) {
            $base_sql .= ", " . $field;
            $values_sql .= ", ?";
            $params[] = $value;
            $param_types .= "i";
        }
    }

    $sql = $base_sql . ") " . $values_sql . ")";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);

    if ($stmt->execute()) {
        $evaluation_id = $conn->insert_id;

        // Create workflow entries for all evaluators in sequence
        createEvaluationWorkflowWithSequence($evaluation_id, $evaluator_sequence);

        // Send notification to first evaluator
        notifySpecificEvaluator($evaluation_id, $first_evaluator['evaluator_id']);

        return $evaluation_id;
    }

    return false;
}

// Function to create evaluation workflow with specific evaluator sequence
function createEvaluationWorkflowWithSequence($evaluation_id, $evaluator_sequence)
{
    global $conn;

    foreach ($evaluator_sequence as $evaluator) {
        $status = ($evaluator['step_order'] == 1) ? 'pending' : 'waiting';
        $sql = "INSERT INTO evaluation_workflow (evaluation_id, evaluator_id, evaluator_role_id, status, step_order) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisi", $evaluation_id, $evaluator['evaluator_id'], $evaluator['role_id'], $status, $evaluator['step_order']);
        $stmt->execute();
    }
}

// Function to create evaluation workflow based on employee role
function createEvaluationWorkflowByRole($evaluation_id, $employee_role_id)
{
    global $conn;

    $workflow = getEvaluationWorkflowByRole($employee_role_id);

    foreach ($workflow as $step_order => $evaluator_role_id) {
        $status = ($step_order == 0) ? 'pending' : 'waiting'; // Only first step is pending (0-indexed)
        $sql = "INSERT INTO evaluation_workflow (evaluation_id, evaluator_role_id, status, step_order) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $actual_step_order = $step_order + 1; // Convert to 1-indexed for database
        $stmt->bind_param("iisi", $evaluation_id, $evaluator_role_id, $status, $actual_step_order);
        $stmt->execute();
    }
}

// Function to create new evaluation with sequential workflow (kept for backward compatibility)
function createEvaluation($employee_id, $evaluation_reason, $period_from, $period_to)
{
    // Get employee role to determine workflow
    $employee = getEmployeeById($employee_id);
    if (!$employee) {
        return false;
    }

    return createEvaluationWithRole($employee_id, $employee['role_id'], $evaluation_reason, $period_from, $period_to);
}

// Function to create evaluation workflow with sequential steps (kept for backward compatibility)
function createEvaluationWorkflow($evaluation_id)
{
    global $conn;

    // Get evaluation to determine employee role
    $evaluation = getEvaluationById($evaluation_id);
    if (!$evaluation) {
        return false;
    }

    $employee = getEmployeeById($evaluation['employee_id']);
    if (!$employee) {
        return false;
    }

    return createEvaluationWorkflowByRole($evaluation_id, $employee['role_id']);
}

// Function to get evaluation by ID (enhanced to include additional fields)
function getEvaluationById($id)
{
    global $conn;

    $sql = "SELECT ev.*, e.fullname, e.card_no, e.hired_date, e.role_id as employee_role_id,
                   ed.name AS department_name, ep.name AS position_name, es.name AS section_name,
                   er.name AS current_evaluator_role_name, edsg.name AS designation_name
            FROM evaluations ev
            JOIN employees e ON ev.employee_id = e.id
            LEFT JOIN emp_department ed ON e.department_id = ed.id 
            LEFT JOIN emp_positions ep ON e.position_id = ep.id 
            LEFT JOIN emp_sections es ON e.section_id = es.id 
            LEFT JOIN emp_roles er ON ev.current_evaluator_role_id = er.id
            LEFT JOIN emp_designation edsg ON e.designation_id = edsg.id
            WHERE ev.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// Function to get evaluations for an employee
function getEmployeeEvaluations($employee_id)
{
    global $conn;

    $sql = "SELECT ev.*, er.name as current_evaluator_role_name 
            FROM evaluations ev
            LEFT JOIN emp_roles er ON ev.current_evaluator_role_id = er.id
            WHERE ev.employee_id = ? 
            ORDER BY ev.created_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get pending evaluations for current evaluator only
function getPendingEvaluationsForEvaluator($role_id, $department_id = null, $user = null)
{
    global $conn;

    // If user is provided, filter by specific evaluator ID
    if ($user !== null) {
        $sql = "SELECT ev.*, e.fullname, e.card_no, ed.name as department_name, ep.name as position_name, es.name as section_name
                FROM evaluations ev
                JOIN employees e ON ev.employee_id = e.id
                LEFT JOIN emp_department ed ON e.department_id = ed.id 
                LEFT JOIN emp_positions ep ON e.position_id = ep.id 
                LEFT JOIN emp_sections es ON e.section_id = es.id 
                WHERE ev.current_evaluator_id = ? AND ev.status = 'pending'";

        $params = [$user['id']];
        $param_types = "i";

        // Apply additional filters for HR users based on designation
        if ($user['role_id'] == 1 && $department_id !== null && $department_id !== '') {
            $sql .= " AND e.department_id = ?";
            $params[] = $department_id;
            $param_types .= "i";
        }
    } else {
        // Fallback to role-based filtering (for backward compatibility)
        $sql = "SELECT ev.*, e.fullname, e.card_no, ed.name as department_name, ep.name as position_name, es.name as section_name
                FROM evaluations ev
                JOIN employees e ON ev.employee_id = e.id
                LEFT JOIN emp_department ed ON e.department_id = ed.id 
                LEFT JOIN emp_positions ep ON e.position_id = ep.id 
                LEFT JOIN emp_sections es ON e.section_id = es.id 
                WHERE ev.current_evaluator_role_id = ? AND ev.status = 'pending'";

        $params = [$role_id];
        $param_types = "i";

        if ($department_id !== null && $department_id !== '') {
            $sql .= " AND e.department_id = ?";
            $params[] = $department_id;
            $param_types .= "i";
        }
    }

    $sql .= " ORDER BY ev.created_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to check if user can evaluate specific evaluation
function canUserEvaluateThis($evaluation_id, $user_id)
{
    global $conn;

    $sql = "SELECT current_evaluator_id FROM evaluations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $evaluation = $result->fetch_assoc();

    return $evaluation && $evaluation['current_evaluator_id'] == $user_id;
}

function getEvaluationCriteria()
{
    global $conn;

    $sql = "SELECT id, criteria_name, criteria_description, order_num 
            FROM evaluation_criteria 
            GROUP BY criteria_name, criteria_description, order_num 
            ORDER BY order_num";
    $result = $conn->query($sql);

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get criteria permissions based on user role
function getCriteriaPermissions($role_id)
{
    // Define criteria permissions based on role
    // HR (role_id = 1): Can edit only Criteria 4, view-only Criteria 1,2,3,5
    // Shift Leader (role_id = 4), Supervisor (role_id = 3), Manager (role_id = 2): Can edit Criteria 1,2,3,5, view-only Criteria 4

    $permissions = [];

    if ($role_id == 1) { // HR
        $permissions = [
            'editable' => [4], // Can edit Criteria 4 (Attendance and Punctuality)
            'view_only' => [1, 2, 3, 5] // View-only Criteria 1,2,3,5
        ];
    } else if (in_array($role_id, [2, 3, 4])) { // Manager, Supervisor, Shift Leader
        $permissions = [
            'editable' => [1, 2, 3, 5], // Can edit Criteria 1,2,3,5
            'view_only' => [4] // View-only Criteria 4 (Attendance and Punctuality)
        ];
    } else {
        // Default: no permissions
        $permissions = [
            'editable' => [],
            'view_only' => []
        ];
    }

    return $permissions;
}

// Function to save evaluation response (enhanced to include recommendation)
function saveEvaluationResponse($evaluation_id, $criteria_id, $evaluator_id, $score, $comments, $recommendation = null)
{
    global $conn;

    // Check if response already exists
    $sql = "SELECT id FROM evaluation_responses WHERE evaluation_id = ? AND criteria_id = ? AND evaluator_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $evaluation_id, $criteria_id, $evaluator_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing response
        $sql = "UPDATE evaluation_responses SET score = ?, comments = ?, recommendation = ?, updated_date = CURRENT_TIMESTAMP 
                WHERE evaluation_id = ? AND criteria_id = ? AND evaluator_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssii", $score, $comments, $recommendation, $evaluation_id, $criteria_id, $evaluator_id);
    } else {
        // Insert new response
        $sql = "INSERT INTO evaluation_responses (evaluation_id, criteria_id, evaluator_id, score, comments, recommendation) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiss", $evaluation_id, $criteria_id, $evaluator_id, $score, $comments, $recommendation);
    }

    return $stmt->execute();
}

function completeEvaluationStep($evaluation_id, $evaluator_role_id, $evaluator_id)
{
    global $conn;

    // Update current workflow step to completed
    $sql = "UPDATE evaluation_workflow SET status = 'completed', completed_date = CURRENT_TIMESTAMP 
            WHERE evaluation_id = ? AND evaluator_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $evaluation_id, $evaluator_id);
    $stmt->execute();

    // Get evaluation to determine employee details
    $evaluation = getEvaluationById($evaluation_id);
    if (!$evaluation) {
        return false;
    }

    // Get employee details
    $employee = getEmployeeById($evaluation['employee_id']);
    if (!$employee) {
        return false;
    }

    // Get the evaluator sequence for this employee
    $evaluator_sequence = getEvaluatorSequence($evaluation['employee_id'], $employee['role_id'], $employee['designation_id']);

    // Find current evaluator and get next evaluator
    $current_evaluator_index = -1;
    foreach ($evaluator_sequence as $index => $seq_evaluator) {
        if ($seq_evaluator['evaluator_id'] == $evaluator_id) {
            $current_evaluator_index = $index;
            break;
        }
    }

    if ($current_evaluator_index !== -1 && $current_evaluator_index < count($evaluator_sequence) - 1) {
        // There is a next evaluator
        $next_evaluator = $evaluator_sequence[$current_evaluator_index + 1];

        // Update evaluation to next evaluator
        $sql = "UPDATE evaluations SET current_evaluator_id = ?, current_evaluator_role_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $next_evaluator['evaluator_id'], $next_evaluator['role_id'], $evaluation_id);
        $stmt->execute();

        // Update next workflow step to pending
        $sql = "UPDATE evaluation_workflow SET status = 'pending' 
                WHERE evaluation_id = ? AND evaluator_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $evaluation_id, $next_evaluator['evaluator_id']);
        $stmt->execute();

        // Send notification to next evaluator
        notifySpecificEvaluator($evaluation_id, $next_evaluator['evaluator_id']);

        return false; // Still pending evaluations
    } else {
        // All evaluations complete, update main evaluation status
        $sql = "UPDATE evaluations SET status = 'completed', current_evaluator_id = NULL, current_evaluator_role_id = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation_id);
        $stmt->execute();

        // Notify employee that evaluation is complete
        notifyEvaluationComplete($evaluation_id);

        return true; // Evaluation fully completed
    }
}

// Function to notify specific evaluator by ID
function notifySpecificEvaluator($evaluation_id, $evaluator_id)
{
    global $conn;

    // Get specific evaluator details
    $sql = "SELECT email, fullname FROM employees WHERE id = ? AND is_inactive = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $evaluator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $evaluator = $result->fetch_assoc();

    if (!$evaluator || empty($evaluator['email'])) {
        return false;
    }

    // Get evaluation details
    $evaluation = getEvaluationById($evaluation_id);
    if (!$evaluation) {
        return false;
    }

    $subject = "Performance Evaluation Pending - " . $evaluation['fullname'];
    $message = "Dear " . $evaluator['fullname'] . ",\n\n";
    $message .= "A performance evaluation for " . $evaluation['fullname'] . " is now pending your review.\n";
    $message .= "Evaluation ID: " . $evaluation_id . "\n";
    $message .= "Employee: " . $evaluation['fullname'] . "\n";
    $message .= "Department: " . $evaluation['department_name'] . "\n";
    $message .= "Position: " . $evaluation['position_name'] . "\n\n";
    $message .= "Please log in to the system to complete your evaluation.\n\n";
    $message .= "Best regards,\nPerformance Evaluation System";

    // Use email function if available
    if (function_exists('sendEmail')) {
        return sendEmail($evaluator['email'], $subject, $message);
    }
    
    return false;
}

// Function to notify next evaluator
function notifyNextEvaluator($evaluation_id, $role_id)
{
    global $conn;

    // Get evaluators with the specified role
    $sql = "SELECT e.email, e.fullname FROM employees e WHERE e.role_id = ? AND e.is_inactive = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $evaluators = $result->fetch_all(MYSQLI_ASSOC);

    // Get evaluation details
    $evaluation = getEvaluationById($evaluation_id);

    foreach ($evaluators as $evaluator) {
        if (!empty($evaluator['email'])) {
            $subject = "Performance Evaluation Pending - " . $evaluation['fullname'];
            $message = "Dear " . $evaluator['fullname'] . ",\n\n";
            $message .= "A performance evaluation for " . $evaluation['fullname'] . " is now pending your review.\n";
            $message .= "Evaluation ID: " . $evaluation_id . "\n";
            $message .= "Employee: " . $evaluation['fullname'] . "\n";
            $message .= "Department: " . $evaluation['department_name'] . "\n";
            $message .= "Position: " . $evaluation['position_name'] . "\n\n";
            $message .= "Please log in to the system to complete your evaluation.\n\n";
            $message .= "Best regards,\nPerformance Evaluation System";

            // Use email function if available
            if (function_exists('sendEmail')) {
                sendEmail($evaluator['email'], $subject, $message);
            }
        }
    }
}

// Function to notify staff that evaluation is complete
function notifyEvaluationComplete($evaluation_id)
{
    global $conn;

    $evaluation = getEvaluationById($evaluation_id);
    $employee = getEmployeeById($evaluation['employee_id']);

    if (!empty($employee['email'])) {
        $subject = "Performance Evaluation Completed";
        $message = "Dear " . $employee['fullname'] . ",\n\n";
        $message .= "Your performance evaluation has been completed by all evaluators.\n";
        $message .= "Evaluation ID: " . $evaluation_id . "\n";
        $message .= "Evaluation Date: " . $evaluation['evaluation_date'] . "\n";
        $message .= "Period Covered: " . $evaluation['period_covered_from'] . " to " . $evaluation['period_covered_to'] . "\n\n";
        $message .= "You can now view your evaluation results in the system.\n\n";
        $message .= "Best regards,\nPerformance Evaluation System";

        // Use email function if available
        if (function_exists('sendEmail')) {
            sendEmail($employee['email'], $subject, $message);
        }
    }
}

// Function to get evaluation responses (enhanced to include recommendation)
function getEvaluationResponses($evaluation_id)
{
    global $conn;

    $sql = "SELECT er.*, ec.criteria_name, ec.order_num, e.fullname as evaluator_name, emp_r.name as evaluator_role
            FROM evaluation_responses er
            JOIN evaluation_criteria ec ON er.criteria_id = ec.id
            JOIN employees e ON er.evaluator_id = e.id
            JOIN emp_roles emp_r ON e.role_id = emp_r.id
            WHERE er.evaluation_id = ?
            ORDER BY ec.order_num, emp_r.order_num";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get evaluation summary (enhanced to include recommendation)
function getEvaluationSummary($evaluation_id)
{
    global $conn;

    $sql = "SELECT ec.criteria_name, ec.order_num, 
                   AVG(er.score) as average_score,
                   GROUP_CONCAT(CONCAT(e.fullname, ': ', er.score) SEPARATOR '; ') as individual_scores,
                   GROUP_CONCAT(CONCAT(e.fullname, ': ', er.comments) SEPARATOR '; ') as all_comments,
                   GROUP_CONCAT(CONCAT(e.fullname, ': ', COALESCE(er.recommendation, 'N/A')) SEPARATOR '; ') as all_recommendations
            FROM evaluation_responses er
            JOIN evaluation_criteria ec ON er.criteria_id = ec.id
            JOIN employees e ON er.evaluator_id = e.id
            JOIN emp_roles emp_r ON e.role_id = emp_r.id
            WHERE er.evaluation_id = ?
            GROUP BY ec.id, ec.criteria_name, ec.order_num
            ORDER BY ec.order_num";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all departments
function getAllDepartments()
{
    global $conn;

    $sql = "SELECT * FROM emp_department WHERE is_disabled = 0 ORDER BY name";
    $result = $conn->query($sql);

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all positions
function getAllPositions()
{
    global $conn;

    $sql = "SELECT * FROM emp_positions WHERE is_disabled = 0 ORDER BY name";
    $result = $conn->query($sql);

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all sections
function getAllSections()
{
    global $conn;

    $sql = "SELECT * FROM emp_sections WHERE is_disabled = 0 ORDER BY name";
    $result = $conn->query($sql);

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all roles
function getAllRoles()
{
    global $conn;

    $sql = "SELECT * FROM emp_roles WHERE is_disabled = 0 ORDER BY order_num";
    $result = $conn->query($sql);

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all evaluations (enhanced to include additional fields)
function getAllEvaluations($department_id = null, $user = null)
{
    global $conn;

    $sql = "SELECT ev.*, e.fullname, e.card_no, e.hired_date, e.role_id as employee_role_id,
                   ed.name as department_name, ep.name as position_name, es.name as section_name,
                   er.name as current_evaluator_role_name, emp_role.name as employee_role_name
            FROM evaluations ev
            JOIN employees e ON ev.employee_id = e.id
            LEFT JOIN emp_department ed ON e.department_id = ed.id 
            LEFT JOIN emp_positions ep ON e.position_id = ep.id 
            LEFT JOIN emp_sections es ON e.section_id = es.id 
            LEFT JOIN emp_roles er ON ev.current_evaluator_role_id = er.id
            LEFT JOIN emp_roles emp_role ON e.role_id = emp_role.id";

    $where_conditions = [];
    $params = [];
    $param_types = "";

    // Apply department filter if specified
    if ($department_id !== null && $department_id !== '') {
        $where_conditions[] = "e.department_id = ?";
        $params[] = $department_id;
        $param_types .= "i";
    }

    // Apply designation-based restrictions for HR users
    if ($user !== null && $user['role_id'] == 1) {
        // For HR, only show evaluations of employees with matching designation_id
        $where_conditions[] = "e.designation_id = ?";
        $params[] = $user['designation_id'];
        $param_types .= "i";
    } else if ($user !== null && !in_array($user['role_id'], [0, 1])) {
        // For Shift Leader, Supervisor, Manager - only show evaluations from same department
        if (in_array($user['role_id'], [2, 3, 4])) {
            $where_conditions[] = "e.department_id = ?";
            $params[] = $user['department_id'];
            $param_types .= "i";
        }
    }

    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }

    $sql .= " ORDER BY ev.created_date DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

function getCompletedEvaluationsByUser($user_id, $department_id = null)
{
    global $conn;

    // Get current user's role_id
    $user = getEmployeeById($user_id);
    $role_id = $user['role_id'];

    $sql = "SELECT ev.*, e.fullname, e.card_no, e.hired_date, e.role_id as employee_role_id,
                   ed.name as department_name, ep.name as position_name, es.name as section_name,
                   er.name as current_evaluator_role_name, emp_role.name as employee_role_name
            FROM evaluations ev
            JOIN employees e ON ev.employee_id = e.id
            LEFT JOIN emp_department ed ON e.department_id = ed.id 
            LEFT JOIN emp_positions ep ON e.position_id = ep.id 
            LEFT JOIN emp_sections es ON e.section_id = es.id 
            LEFT JOIN emp_roles er ON ev.current_evaluator_role_id = er.id
            LEFT JOIN emp_roles emp_role ON e.role_id = emp_role.id
            WHERE ev.status = 'completed'
              AND (
                  EXISTS (
                      SELECT 1 FROM evaluation_workflow ew 
                      WHERE ew.evaluation_id = ev.id 
                      AND ew.evaluator_id = ?
                      AND ew.status = 'completed'
                  )
                  OR ? IN (1, 2, 3, 4) -- HR (1), Manager (2), Supervisor (3), Shift Leader (4)
              )";

    if ($department_id !== null && $department_id !== '') {
        $sql .= " AND e.department_id = ?";
    }

    $sql .= " ORDER BY ev.created_date DESC";

    if ($department_id !== null && $department_id !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $role_id, $department_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $role_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}



// Function to get current evaluation step
function getCurrentEvaluationStep($evaluation_id)
{
    global $conn;

    $sql = "SELECT er.name as role_name FROM evaluations ev
            LEFT JOIN emp_roles er ON ev.current_evaluator_role_id = er.id
            WHERE ev.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row && $row["role_name"] ? $row["role_name"] : 'Completed';
}


// Function to get evaluation workflow status
function getEvaluationWorkflowStatus($evaluation_id)
{
    global $conn;

    $sql = "
        SELECT 
            er.name AS role_name,
            MAX(ew.status) AS status,
            MAX(ew.completed_date) AS completed_date,
            (
                SELECT e.fullname 
                FROM employees e 
                WHERE e.id = (
                    SELECT ew2.evaluator_id 
                    FROM evaluation_workflow ew2 
                    WHERE ew2.evaluator_role_id = ew.evaluator_role_id 
                      AND ew2.evaluation_id = ew.evaluation_id 
                    LIMIT 1
                )
            ) AS evaluator_name
        FROM evaluation_workflow ew
        JOIN emp_roles er ON ew.evaluator_role_id = er.id
        WHERE ew.evaluation_id = ?
        GROUP BY ew.evaluator_role_id, er.name
        ORDER BY MIN(ew.step_order)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}
