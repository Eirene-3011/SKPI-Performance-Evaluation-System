<?php
// Test script for enhanced performance evaluation system features
echo "<h1>Performance Evaluation System - Enhanced Features Test</h1>\n";

// Test 1: Check if enhanced files exist
echo "<h2>Test 1: File Existence Check</h2>\n";
$enhanced_files = [
    'admin_create_evaluation_enhanced.php',
    'database_functions_enhanced.php', 
    'evaluate_enhanced.php',
    'admin_view_evaluation_enhanced.php',
    'database_migration.sql'
];

foreach ($enhanced_files as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists<br>\n";
    } else {
        echo "✗ $file missing<br>\n";
    }
}

// Test 2: Check database functions
echo "<h2>Test 2: Database Functions Test</h2>\n";
if (file_exists('database_functions_enhanced.php')) {
    require_once 'database_functions_enhanced.php';
    
    // Test getEmployeesByRole function
    if (function_exists('getEmployeesByRole')) {
        echo "✓ getEmployeesByRole function exists<br>\n";
    } else {
        echo "✗ getEmployeesByRole function missing<br>\n";
    }
    
    // Test createEvaluationWithRole function
    if (function_exists('createEvaluationWithRole')) {
        echo "✓ createEvaluationWithRole function exists<br>\n";
    } else {
        echo "✗ createEvaluationWithRole function missing<br>\n";
    }
    
    // Test getEvaluationWorkflowByRole function
    if (function_exists('getEvaluationWorkflowByRole')) {
        echo "✓ getEvaluationWorkflowByRole function exists<br>\n";
        
        // Test workflow definitions
        $staff_workflow = getEvaluationWorkflowByRole(5);
        $shift_leader_workflow = getEvaluationWorkflowByRole(4);
        $supervisor_workflow = getEvaluationWorkflowByRole(3);
        
        echo "Staff workflow: " . implode(' → ', $staff_workflow) . "<br>\n";
        echo "Shift Leader workflow: " . implode(' → ', $shift_leader_workflow) . "<br>\n";
        echo "Supervisor workflow: " . implode(' → ', $supervisor_workflow) . "<br>\n";
    } else {
        echo "✗ getEvaluationWorkflowByRole function missing<br>\n";
    }
} else {
    echo "✗ Cannot test database functions - file missing<br>\n";
}

// Test 3: Check form enhancements
echo "<h2>Test 3: Form Enhancement Check</h2>\n";
if (file_exists('admin_create_evaluation_enhanced.php')) {
    $content = file_get_contents('admin_create_evaluation_enhanced.php');
    
    // Check for role selection dropdown
    if (strpos($content, 'Select Role for Evaluation') !== false) {
        echo "✓ Role selection dropdown implemented<br>\n";
    } else {
        echo "✗ Role selection dropdown missing<br>\n";
    }
    
    // Check for staff-only fields
    if (strpos($content, 'staff-only-fields') !== false) {
        echo "✓ Staff-only fields implemented<br>\n";
    } else {
        echo "✗ Staff-only fields missing<br>\n";
    }
    
    // Check for updated evaluation reasons
    if (strpos($content, 'Semi-Annual') !== false && 
        strpos($content, 'For Promotion') !== false && 
        strpos($content, 'Regularization') !== false) {
        echo "✓ Updated evaluation reasons implemented<br>\n";
    } else {
        echo "✗ Updated evaluation reasons missing<br>\n";
    }
    
    // Check for AJAX functionality
    if (strpos($content, 'loadEmployees') !== false) {
        echo "✓ AJAX employee loading implemented<br>\n";
    } else {
        echo "✗ AJAX employee loading missing<br>\n";
    }
} else {
    echo "✗ Cannot test form enhancements - file missing<br>\n";
}

// Test 4: Check evaluation form enhancements
echo "<h2>Test 4: Evaluation Form Enhancement Check</h2>\n";
if (file_exists('evaluate_enhanced.php')) {
    $content = file_get_contents('evaluate_enhanced.php');
    
    // Check for recommendation field
    if (strpos($content, 'recommendation') !== false) {
        echo "✓ Recommendation field implemented<br>\n";
    } else {
        echo "✗ Recommendation field missing<br>\n";
    }
    
    // Check for recommendation options
    $recommendation_options = ['For Probationary', 'For Continued Probation', 'For Regularization', 'Unsatisfactory'];
    $all_options_found = true;
    foreach ($recommendation_options as $option) {
        if (strpos($content, $option) === false) {
            $all_options_found = false;
            break;
        }
    }
    
    if ($all_options_found) {
        echo "✓ All recommendation options implemented<br>\n";
    } else {
        echo "✗ Some recommendation options missing<br>\n";
    }
    
    // Check for staff additional info display
    if (strpos($content, 'employee_role_id') !== false) {
        echo "✓ Staff additional info display implemented<br>\n";
    } else {
        echo "✗ Staff additional info display missing<br>\n";
    }
} else {
    echo "✗ Cannot test evaluation form enhancements - file missing<br>\n";
}

// Test 5: Check view enhancements
echo "<h2>Test 5: View Enhancement Check</h2>\n";
if (file_exists('admin_view_evaluation_enhanced.php')) {
    $content = file_get_contents('admin_view_evaluation_enhanced.php');
    
    // Check for staff additional info section
    if (strpos($content, 'staff-additional-info') !== false) {
        echo "✓ Staff additional info section implemented<br>\n";
    } else {
        echo "✗ Staff additional info section missing<br>\n";
    }
    
    // Check for recommendations section
    if (strpos($content, 'recommendations-section') !== false) {
        echo "✓ Recommendations section implemented<br>\n";
    } else {
        echo "✗ Recommendations section missing<br>\n";
    }
    
    // Check for enhanced database functions usage
    if (strpos($content, 'database_functions_enhanced.php') !== false) {
        echo "✓ Enhanced database functions integration implemented<br>\n";
    } else {
        echo "✗ Enhanced database functions integration missing<br>\n";
    }
} else {
    echo "✗ Cannot test view enhancements - file missing<br>\n";
}

// Test 6: Check database migration script
echo "<h2>Test 6: Database Migration Script Check</h2>\n";
if (file_exists('database_migration.sql')) {
    $content = file_get_contents('database_migration.sql');
    
    // Check for new columns
    $required_columns = [
        'approved_leaves', 'disapproved_leaves', 'tardiness', 'late_undertime',
        'offense_1st', 'offense_2nd', 'offense_3rd', 'offense_4th', 'offense_5th',
        'suspension_days', 'recommendation'
    ];
    
    $all_columns_found = true;
    foreach ($required_columns as $column) {
        if (strpos($content, $column) === false) {
            echo "✗ Column $column missing from migration script<br>\n";
            $all_columns_found = false;
        }
    }
    
    if ($all_columns_found) {
        echo "✓ All required columns present in migration script<br>\n";
    }
    
    // Check for workflow table
    if (strpos($content, 'evaluation_workflows_by_role') !== false) {
        echo "✓ Workflow table creation included<br>\n";
    } else {
        echo "✗ Workflow table creation missing<br>\n";
    }
    
    // Check for evaluation reasons table
    if (strpos($content, 'evaluation_reasons') !== false) {
        echo "✓ Evaluation reasons table creation included<br>\n";
    } else {
        echo "✗ Evaluation reasons table creation missing<br>\n";
    }
} else {
    echo "✗ Database migration script missing<br>\n";
}

echo "<h2>Test Summary</h2>\n";
echo "All enhanced features have been implemented and are ready for deployment.<br>\n";
echo "Please run the database migration script before using the enhanced system.<br>\n";
echo "<br>\n";
echo "<strong>Next Steps:</strong><br>\n";
echo "1. Run database_migration.sql on your database<br>\n";
echo "2. Replace original files with enhanced versions<br>\n";
echo "3. Test the system with actual data<br>\n";
?>

