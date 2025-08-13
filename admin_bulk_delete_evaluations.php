<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'database_functions_enhanced.php';

requireLogin();
if (!hasRole(0)) { // Admin role ID is 0
    header("Location: dashboard.php?error=access_denied");
    exit();
}

// Handle bulk delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
    // Start a transaction
    $conn->begin_transaction();

    try {
        // Delete all evaluation responses
        $conn->query("DELETE FROM evaluation_responses");

        // Delete all evaluation workflow records
        $conn->query("DELETE FROM evaluation_workflow");

        // Delete all evaluations
        $conn->query("DELETE FROM evaluations");

        // Commit the transaction
        $conn->commit();

        $success_message = "All evaluations and related data have been successfully deleted.";
    } catch (mysqli_sql_exception $exception) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error deleting evaluations: " . $exception->getMessage();
    }
}

// Handle individual employee evaluations delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employee_evaluations'])) {
    $employee_id = $_POST['employee_id'];

    if (!empty($employee_id) && is_numeric($employee_id)) {
        // Start a transaction
        $conn->begin_transaction();

        try {
            // Get all evaluation IDs for this employee
            $stmt = $conn->prepare("SELECT id FROM evaluations WHERE employee_id = ?");
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $evaluation_ids = [];

            while ($row = $result->fetch_assoc()) {
                $evaluation_ids[] = $row['id'];
            }

            if (!empty($evaluation_ids)) {
                $ids_placeholder = str_repeat('?,', count($evaluation_ids) - 1) . '?';

                // Delete related records from evaluation_responses
                $stmt_responses = $conn->prepare("DELETE FROM evaluation_responses WHERE evaluation_id IN ($ids_placeholder)");
                $stmt_responses->bind_param(str_repeat('i', count($evaluation_ids)), ...$evaluation_ids);
                $stmt_responses->execute();

                // Delete related records from evaluation_workflow
                $stmt_workflow = $conn->prepare("DELETE FROM evaluation_workflow WHERE evaluation_id IN ($ids_placeholder)");
                $stmt_workflow->bind_param(str_repeat('i', count($evaluation_ids)), ...$evaluation_ids);
                $stmt_workflow->execute();

                // Delete the evaluation records
                $stmt_evaluation = $conn->prepare("DELETE FROM evaluations WHERE employee_id = ?");
                $stmt_evaluation->bind_param("i", $employee_id);
                $stmt_evaluation->execute();
            }

            // Commit the transaction
            $conn->commit();

            $success_message = "All evaluations for the selected employee have been successfully deleted.";
        } catch (mysqli_sql_exception $exception) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Error deleting employee evaluations: " . $exception->getMessage();
        }
    } else {
        $error_message = "Please select a valid employee.";
    }
}

$user = getCurrentUser();
$all_employees = getAllEmployees();

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Delete Evaluations - Admin</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .danger-zone {
            background-color: #fff5f5;
            border: 2px solid #fed7d7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .danger-zone h3 {
            color: #c53030;
            margin-top: 0;
        }

        .warning-text {
            color: #d69e2e;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .btn-danger {
            background-color: #e53e3e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-danger:hover {
            background-color: #c53030;
        }

        .success-message {
            background-color: #f0fff4;
            border: 1px solid #9ae6b4;
            color: #2f855a;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .error-message {
            background-color: #fed7d7;
            border: 1px solid #feb2b2;
            color: #c53030;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
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
                    <button onclick="location.href='profile.php'" class="btn btn-secondary">My Profile</button>
                    <button onclick="location.href='?logout=1'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Bulk Delete Evaluations</h2>
                <a href="admin_dashboard.php" class="btn btn-primary-admin">Back to Dashboard</a>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Delete All Evaluations -->
            <div class="danger-zone">
                <h3>⚠️ Delete All Evaluations</h3>
                <p class="warning-text">WARNING: This action will permanently delete ALL evaluations and related data from the system. This action cannot be undone!</p>
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete ALL evaluations? This action cannot be undone!');">
                    <button type="submit" name="delete_all" class="btn-danger">Delete All Evaluations</button>
                </form>
            </div>

            <!-- Delete Evaluations by Employee -->
            <div class="danger-zone">
                <h3>⚠️ Delete All Evaluations for Specific Employee</h3>
                <p class="warning-text">WARNING: This action will permanently delete ALL evaluations for the selected employee. This action cannot be undone!</p>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete all evaluations for the selected employee? This action cannot be undone!');">
                    <div class="form-group">
                        <label for="employee_id">Select Employee:</label>
                        <select name="employee_id" id="employee_id" required>
                            <option value="">-- Select Employee --</option>
                            <?php foreach ($all_employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['fullname']) . ' (' . htmlspecialchars($employee['card_no']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="delete_employee_evaluations" class="btn-danger">Delete Employee's Evaluations</button>
                </form>
            </div>

            <div style="margin-top: 30px;">
                <a href="admin_view_evaluations.php" class="btn btn-primary">View All Evaluations</a>
            </div>
        </div>
    </div>
</body>

</html>