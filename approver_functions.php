<?php
require_once 'config.php';

/**
 * Load approvers from CSV file
 */
function loadApproverFromCSV() {
    $approvers = [];
    $csvFile = 'approvers.csv';
    
    if (!file_exists($csvFile)) {
        return $approvers;
    }
    
    $handle = fopen($csvFile, 'r');
    if ($handle !== FALSE) {
        // Skip header row
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $employee_id = (int)$data[0];
            $approver1_id = !empty($data[1]) && $data[1] !== 'NULL' ? (int)$data[1] : null;
            $approver2_id = !empty($data[2]) && $data[2] !== 'NULL' ? (int)$data[2] : null;
            $approver3_id = !empty($data[3]) && $data[3] !== 'NULL' ? (int)$data[3] : null;
            
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

/**
 * Get evaluators for a specific employee based on approvers.csv
 * Now follows specific HR routing rules.
 */
function getEvaluatorsForEmployee($employee_id) {
    global $conn;
    
    $approvers = loadApproverFromCSV();
    $evaluators = [];
    
    // Get employee details
    $employee = getEmployeeById($employee_id);
    if (!$employee) {
        return $evaluators;
    }

    // --- HR SELECTION BASED ON ROUTING RULES ---
    $hr_sql = "";
    $params = [];
    $types = "";

    if ($employee['designation_id'] == 1) {
        if ($employee['department_id'] == 3) {
            // Rule: HR with designation_id=1 and department_id=3
            $hr_sql = "SELECT * FROM employees 
                       WHERE role_id = 1 AND designation_id = 1 AND department_id = 3 AND is_inactive = 0 
                       LIMIT 1";
        } elseif (in_array($employee['department_id'], [1, 2, 4, 5, 6, 7, 8])) {
            // Rule: HR with designation_id=1 and department_id=7
            $hr_sql = "SELECT * FROM employees 
                       WHERE role_id = 1 AND designation_id = 1 AND department_id = 7 AND is_inactive = 0 
                       LIMIT 1";
        }
    } elseif ($employee['designation_id'] == 2) {
        // Rule: HR with designation_id=2 (any department)
        $hr_sql = "SELECT * FROM employees 
                   WHERE role_id = 1 AND designation_id = 2 AND is_inactive = 0 
                   LIMIT 1";
    } elseif ($employee['designation_id'] == 3) {
        // Rule: HR with designation_id=3 (any department)
        $hr_sql = "SELECT * FROM employees 
                   WHERE role_id = 1 AND designation_id = 3 AND is_inactive = 0 
                   LIMIT 1";
    }

    if ($hr_sql) {
        $hr_stmt = $conn->prepare($hr_sql);
        $hr_stmt->execute();
        $hr_result = $hr_stmt->get_result();
        $hr_evaluator = $hr_result->fetch_assoc();
        $evaluators['hr'] = $hr_evaluator;
    } else {
        // No matching HR found based on rules
        $evaluators['hr'] = null;
    }
    
    // --- APPROVERS FROM CSV ---
    if (isset($approvers[$employee_id])) {
        $approver_data = $approvers[$employee_id];
        
        // Evaluator 1 (Shift Leader)
        if (!empty($approver_data['approver1_id'])) {
            $evaluators['evaluator1'] = getEmployeeById($approver_data['approver1_id']);
        }
        
        // Evaluator 2 (Supervisor)
        if (!empty($approver_data['approver2_id'])) {
            $evaluators['evaluator2'] = getEmployeeById($approver_data['approver2_id']);
        }
        
        // Evaluator 3 (Manager)
        if (!empty($approver_data['approver3_id'])) {
            $evaluators['evaluator3'] = getEmployeeById($approver_data['approver3_id']);
        }
    }
    
    return $evaluators;
}

/**
 * Get potential replacements for an evaluator based on role and department
 */
function getPotentialReplacements($role_id, $department_id) {
    global $conn;
    
    $sql = "SELECT * FROM employees WHERE role_id = ? AND department_id = ? AND is_inactive = 0 ORDER BY fullname";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $role_id, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Update approver in CSV file
 */
function updateApproverInCSV($employee_id, $approver_position, $new_approver_id) {
    $approvers = loadApproverFromCSV();
    
    // Initialize if employee doesn't exist
    if (!isset($approvers[$employee_id])) {
        $approvers[$employee_id] = [
            'approver1_id' => null,
            'approver2_id' => null,
            'approver3_id' => null
        ];
    }
    
    // Update the specific approver
    $approvers[$employee_id][$approver_position] = $new_approver_id;
    
    // Write back to CSV
    $csvFile = 'approvers.csv';
    $handle = fopen($csvFile, 'w');
    
    if ($handle !== FALSE) {
        // Write header
        fputcsv($handle, ['employee_id', 'approver1_id', 'approver2_id', 'approver3_id']);
        
        // Write data
        foreach ($approvers as $emp_id => $approver_data) {
            $row = [
                $emp_id,
                $approver_data['approver1_id'] ?: 'NULL',
                $approver_data['approver2_id'] ?: 'NULL',
                $approver_data['approver3_id'] ?: 'NULL'
            ];
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        return true;
    }
    
    return false;
}

/**
 * Get evaluator role based on employee role and evaluator position
 */
function getEvaluatorRole($employee_role_id, $evaluator_position) {
    $role_mappings = [
        5 => [ // Staff
            'evaluator1' => 4, // Shift Leader
            'evaluator2' => 3, // Supervisor
            'evaluator3' => 2  // Manager
        ],
        4 => [ // Shift Leader
            'evaluator1' => 3, // Supervisor
            'evaluator2' => 2  // Manager
        ],
        3 => [ // Supervisor
            'evaluator1' => 2  // Manager
        ]
    ];
    
    return $role_mappings[$employee_role_id][$evaluator_position] ?? null;
}
?>
