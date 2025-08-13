<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';
require_once 'admin_functions.php';

// Check if user is logged in and is an admin
requireAdmin();

$user = getCurrentUser();

// Check if evaluation ID is provided
if (!isset($_GET['id'])) {
    header("Location: admin_summary_report.php");
    exit();
}

$evaluation_id = (int)$_GET['id'];

// Generate detailed report
$report = generateDetailedEvaluationReport($evaluation_id);

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
    <title>Detailed Evaluation Report - Admin - Performance Evaluation System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .report-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .report-section h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .employee-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #7f8c8d;
            display: block;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 16px;
        }

        .criteria-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .criteria-table th,
        .criteria-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .criteria-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .criteria-table tr:hover {
            background-color: #f5f5f5;
        }

        .workflow-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .workflow-table th,
        .workflow-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .workflow-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .workflow-table tr:hover {
            background-color: #f5f5f5;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #f39c12;
            color: white;
        }

        .status-completed {
            background-color: #2ecc71;
            color: white;
        }

        .score-display {
            font-weight: bold;
            font-size: 18px;
        }

        .print-buttons {
            margin-bottom: 20px;
            text-align: right;
        }

        @media print {

            .header,
            .print-buttons,
            .btn {
                display: none;
            }

            body {
                padding: 0;
                margin: 0;
            }

            .container {
                width: 100%;
                padding: 0;
                margin: 0;
            }

            .card {
                border: none;
                box-shadow: none;
            }
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
                    <button onclick="location.href='?logout=1'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Detailed Evaluation Report</h2>
            </div>

            <!-- Print/Export Buttons -->
            <div class="print-buttons">
                <button onclick="window.print()" class="btn btn-primary">Print Report</button>
                <button onclick="exportToPDF()" class="btn btn-secondary">Export to PDF</button>
            </div>

            <!-- Employee Information -->
            <div class="report-section">
                <h3>Employee Information</h3>
                <div class="employee-info">
                    <div class="info-item">
                        <span class="info-label">Employee Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['evaluation']['fullname']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Employee ID</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['evaluation']['card_no']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Department</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['evaluation']['department_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Position</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['evaluation']['position_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Section</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['evaluation']['section_name'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Hire Date</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($report['evaluation']['hired_date'])); ?></span>
                    </div>
                </div>

                <h3>Evaluation Details</h3>
                <div class="employee-info">
                    <div class="info-item">
                        <span class="info-label">Evaluation Reason</span>
                        <span class="info-value"><?php echo htmlspecialchars($report['evaluation']['evaluation_reason']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Evaluation Date</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($report['evaluation']['evaluation_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Period Covered</span>
                        <span class="info-value">
                            <?php echo date('M d, Y', strtotime($report['evaluation']['period_covered_from'])); ?> -
                            <?php echo date('M d, Y', strtotime($report['evaluation']['period_covered_to'])); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="status-badge status-<?php echo $report['evaluation']['status']; ?>">
                                <?php echo ucfirst($report['evaluation']['status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Created Date</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($report['evaluation']['created_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Updated</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($report['evaluation']['updated_date'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Evaluation Criteria and Responses -->
            <div class="report-section">
                <h3>Evaluation Results</h3>

                <?php if (empty($report['criteria_responses'])): ?>
                    <p>No evaluation responses have been submitted yet.</p>
                <?php else: ?>
                    <?php
                    $total_score = 0;
                    $response_count = 0;

                    foreach ($report['criteria_responses'] as $criteria_id => $criteria):
                        $criteria_scores = array_column($criteria['responses'], 'score');
                        $criteria_avg = !empty($criteria_scores) ? array_sum($criteria_scores) / count($criteria_scores) : 0;
                        $total_score += $criteria_avg;
                        $response_count++;
                    ?>
                        <div style="margin-bottom: 30px;">
                            <h4><?php echo htmlspecialchars($criteria['criteria_name']); ?></h4>

                            <table class="criteria-table">
                                <thead>
                                    <tr>
                                        <th>Evaluator</th>
                                        <th>Role</th>
                                        <th>Score</th>
                                        <th>Comments</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($criteria['responses'] as $response): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($response['evaluator_name']); ?></td>
                                            <td><?php echo htmlspecialchars($response['evaluator_role']); ?></td>
                                            <td>
                                                <span class="score-display">
                                                    <?php echo $response['score']; ?> / 5
                                                </span>
                                            </td>
                                            <td><?php echo nl2br(htmlspecialchars($response['comments'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($response['created_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2"><strong>Average Score:</strong></td>
                                        <td colspan="3">
                                            <span class="score-display">
                                                <?php echo number_format($criteria_avg, 2); ?> / 5.00
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endforeach; ?>

                    <div style="margin-top: 30px; padding: 15px; background-color: #e8f4f8; border-radius: 5px;">
                        <h3>Overall Evaluation Score</h3>
                        <div style="text-align: center; margin: 20px 0;">
                            <span style="font-size: 36px; font-weight: bold; color: #2980b9;">
                                <?php echo number_format($total_score / $response_count, 2); ?> / 5.00
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Workflow Status -->
            <div class="report-section">
                <h3>Evaluation Workflow Status</h3>

                <table class="workflow-table">
                    <thead>
                        <tr>
                            <th>Evaluator Role</th>
                            <th>Evaluator</th>
                            <th>Status</th>
                            <th>Completed Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['workflow'] as $workflow): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($workflow['role_name']); ?></td>
                                <td>
                                    <?php
                                    if ($workflow['evaluator_name']) {
                                        echo htmlspecialchars($workflow['evaluator_name']);
                                    } else {
                                        echo 'Not assigned yet';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $workflow['status']; ?>">
                                        <?php echo ucfirst($workflow['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    if ($workflow['completed_date']) {
                                        echo date('M d, Y H:i:s', strtotime($workflow['completed_date']));
                                    } else {
                                        echo 'Pending';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Back Buttons -->
            <div style="text-align: center; margin-top: 20px;">
                <a href="admin_summary_report.php" class="btn btn-secondary">Back to Summary Report</a>
                <a href="admin_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        // Export to PDF function
        function exportToPDF() {
            // In a real implementation, you would use a library like jsPDF
            // For this example, we'll just trigger the print dialog which can save as PDF
            window.print();
        }
    </script>
</body>

</html>


<?php
require_once 'auth.php';
require_once 'database_functions.php';

requireLogin();
if (!hasRole(0)) { // Admin role ID is 0
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$user = getCurrentUser();

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
    <title>Detailed Reports - Admin</title>
    <link rel="stylesheet" href="assets/style.css">
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
                <h2 class="card-title">Detailed Reports</h2>
                <a href="admin_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
            <p>This page will display detailed reports of evaluations.</p>
        </div>
    </div>
</body>

</html>