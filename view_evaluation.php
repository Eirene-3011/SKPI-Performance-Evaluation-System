<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';

requireLogin();

$evaluation_id = (int)$_GET['id'];
$evaluation = getEvaluationById($evaluation_id);
$user = getCurrentUser();

if (!$evaluation) {
    header("Location: dashboard.php?error=evaluation_not_found");
    exit();
}

// Strict access control for viewing evaluations
$can_view = false;

// 1. Employee being evaluated can always view their own evaluation
if ($evaluation['employee_id'] == $user['id']) {
    $can_view = true;
}

// 2. HR (role_id = 1) can view evaluations only for employees with matching designation_id
if ($user['role_id'] == 1) {
    // Get employee being evaluated to check designation_id
    $employee = getEmployeeById($evaluation['employee_id']);
    if ($employee && $employee['designation_id'] == $user['designation_id']) {
        $can_view = true;
    }
}

// 3. Current evaluator can view the evaluation
if ($evaluation['current_evaluator_role_id'] == $user['role_id'] && $evaluation['status'] == 'pending') {
    $can_view = true;
}

// 4. Evaluators who have already completed their part can view the evaluation
$sql_check_completed = "SELECT COUNT(*) as count FROM evaluation_workflow WHERE evaluation_id = ? AND evaluator_id = ? AND status = 'completed'";
$stmt_check_completed = $conn->prepare($sql_check_completed);
$stmt_check_completed->bind_param("ii", $evaluation_id, $user['id']);
$stmt_check_completed->execute();
$result_check_completed = $stmt_check_completed->get_result();
$row_check_completed = $result_check_completed->fetch_assoc();
if ($row_check_completed['count'] > 0) {
    $can_view = true;
}

// If none of the above conditions are met, deny access
if (!$can_view) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$criteria = getEvaluationCriteria();
$responses = getEvaluationResponses($evaluation_id);
$summary = getEvaluationSummary($evaluation_id);
$workflow_status = getEvaluationWorkflowStatus($evaluation_id);

// Organize responses by criteria and evaluator
$organized_responses = [];
foreach ($responses as $response) {
    $organized_responses[$response['criteria_id']][] = $response;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Evaluation - Performance Evaluation System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
            .workflow-step {
                display: flex;
                align-items: center;
                padding: 10px;
                margin: 5px 0;
                border-radius: 5px;
                border-left: 4px solid #ddd;
            }
            .workflow-step.completed {
                background-color: #d5f4e6;
                border-left-color: #27ae60;
            }
            .workflow-step.pending {
                background-color: #fff3cd;
                border-left-color: #ffc107;
            }
            .workflow-step.waiting {
                background-color: #f8f9fa;
                border-left-color: #6c757d;
            }
            .evaluation-details-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    margin-bottom: 20px;
            }
            .evaluation-details-grid strong {
                display: block;
                margin-bottom: 5px;
                color: #555;
            }
            .evaluation-details-grid p {
                margin: 0;
            }
            .workflow-status-item {
                margin-bottom: 10px;
                padding: 8px;
                border-left: 4px solid #5a8dee;
                background-color: #eef4ff;
            }
            .workflow-status-item.completed {
                border-left-color: #28a745;
                background-color: #e6ffe6;
                margin-top: 10px;
            }
            .workflow-status-item.pending {
                border-left-color: #ffc107;
                background-color: #fff8e6;
                margin-top: 10px;
            }
            .workflow-status-item strong {
                color: #2c3e50;
            }
            .workflow-status-item span {
                font-size: 0.9em;
                color: #666;
            }
            .staff-additional-info {
                background: #FEF9E1;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                padding: 30px;
                margin-bottom: 20px;
                transition: transform 0.3s, box-shadow 0.3s;
            }
            .staff-additional-info h3 {
                color: #4B3B2A;
                margin-top: 0;
                margin-bottom: 15px;
            }
            .info-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
            .info-section {
                background-color: #FEF9E1;
                padding: 15px;
                border-radius: 6px;
            }
            .info-section h4 {
                margin-top: 0;
                margin-bottom: 10px;
                color: #4B3B2A;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .info-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                padding: 4px 0;
                border-bottom: 1px solid #f8f9fa;
            }
            .info-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
            }
            .info-label {
                font-weight: 500;
                color: #4B3B2A;
            }
            .info-value {
                color: #4B3B2A;
                font-weight: 600;
            }
            .recommendations-section {
                background-color: #e3f2fd;
                border: 1px solid #2196f3;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
            }
            .recommendations-section h3 {
                color: #A31D1D;
                margin-top: 0;
            }
            .recommendation-item {
                background-color: #FEF9E1;
                padding: 10px 15px;
                margin: 10px 0;
                border-radius: 0px;
                border-bottom: 1px solid #e0e0e0;
            }
            .recommendation-evaluator {
                font-weight: bold;
                color: #A31D1D;
            }
            .recommendation-value {
                color: #424242;
                margin-top: 5px;
            }
            .info-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 12px;
                padding: 4px 0;
                border-bottom: 1px solid #e0e0e0;
            }
            .info-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
            }
            .info-label {
                font-weight: 470;
                color: #4B3B2A;
            }
            .info-value {
                color: #4B3B2A;
                font-weight: 350;
            }
             .info-item {
                border-bottom: 1px solid #ccc;
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
                    <p><?php echo htmlspecialchars($user['role_name']); ?></p>
                    <button onclick="location.href='dashboard.php'" class="btn btn-secondary">Back to Dashboard</button>
                    <button onclick="location.href='?logout=1'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <!-- Evaluation Header -->
        <div class="card">
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="assets/seiwa.logo.png" alt="Seiwa Kaiun Philippines Inc." style="height: 60px; margin-bottom: 15px;">
                <h1 style="color: #2c3e50; margin-bottom: 5px;">PERFORMANCE EVALUATION REPORT</h1>
                <p style="color: #7f8c8d;">Seiwa Kaiun Philippines Inc.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <div>
                    <strong>Employee Name:</strong><br>
                    <?php echo htmlspecialchars($evaluation['fullname']); ?>
                </div>
                <div>
                    <strong>Employee ID:</strong><br>
                    <?php echo htmlspecialchars($evaluation['card_no']); ?>
                </div>
                <div>
                    <strong>Position:</strong><br>
                    <?php echo htmlspecialchars($evaluation['position_name']); ?>
                </div>
                <div>
                    <strong>Department:</strong><br>
                    <?php echo htmlspecialchars($evaluation['department_name']); ?>
                </div>
                <div>
                    <strong>Section:</strong><br>
                    <?php echo htmlspecialchars($evaluation['section_name'] ?: 'N/A'); ?>
                </div>
                <div>
                    <strong>Date Hired:</strong><br>
                    <?php echo date('M d, Y', strtotime($evaluation['hired_date'])); ?>
                </div>
                <div>
                    <strong>Evaluation Reason:</strong><br>
                    <?php echo htmlspecialchars($evaluation['evaluation_reason']); ?>
                </div>
                <div>
                    <strong>Period Covered:</strong><br>
                    <?php echo date('M d, Y', strtotime($evaluation['period_covered_from'])); ?> - 
                    <?php echo date('M d, Y', strtotime($evaluation['period_covered_to'])); ?>
                </div>
                <div>
                    <strong>Evaluation Date:</strong><br>
                    <?php echo date('M d, Y', strtotime($evaluation['evaluation_date'])); ?>
                </div>
                <div>
                    <strong>Status:</strong><br>
                    <span class="badge badge-<?php echo $evaluation['status']; ?>">
                        <?php echo ucfirst($evaluation['status']); ?>
                    </span>
                </div>
            </div>
        </div>

                    <!-- Additional Information for Staff Evaluations -->
                <?php if (in_array($evaluation['employee_role_id'], [3, 4, 5])): ?>
                    <div class="staff-additional-info">
                        <h3 style="color: #A31D1D;">Additional Information</h3>
                        <div class="info-grid">
                            <div class="info-section">
                                <h4>Attendance & Punctuality</h4>
                                <div class="info-item">
                                    <span class="info-label">Approved Leaves:</span>
                                    <span class="info-value"><?php echo $evaluation['approved_leaves'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Disapproved Leaves:</span>
                                    <span class="info-value"><?php echo $evaluation['disapproved_leaves'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Tardiness:</span>
                                    <span class="info-value"><?php echo $evaluation['tardiness'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Late/Undertime:</span>
                                    <span class="info-value"><?php echo $evaluation['late_undertime'] ?? 0; ?></span>
                                </div>
                            </div>
                            
                            <div class="info-section">
                                <h4>Violations & Suspensions</h4>
                                <div class="info-item">
                                    <span class="info-label">1st Offense:</span>
                                    <span class="info-value"><?php echo $evaluation['offense_1st'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">2nd Offense:</span>
                                    <span class="info-value"><?php echo $evaluation['offense_2nd'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">3rd Offense:</span>
                                    <span class="info-value"><?php echo $evaluation['offense_3rd'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">4th Offense:</span>
                                    <span class="info-value"><?php echo $evaluation['offense_4th'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">5th Offense:</span>
                                    <span class="info-value"><?php echo $evaluation['offense_5th'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Suspension Days:</span>
                                    <span class="info-value"><?php echo $evaluation['suspension_days'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

        <!-- Evaluation Results -->
        <?php if (!empty($summary)): ?>
            <!-- Summary -->
            <div class="card">
                <div class="card-header">
                    <h3 style="color: #A31D1D;">Evaluation Summary</h3>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Criteria</th>
                            <th>Average Score</th>
                            <th>Individual Scores</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['criteria_name']); ?></td>
                                <td>
                                    <span class="badge badge-score">
                                        <?php echo number_format($item['average_score'], 2); ?>/5
                                    </span>
                                </td>
                                <td style="font-size: 12px;">
                                    <?php echo htmlspecialchars($item['individual_scores']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Detailed Responses -->
            <div class="card">
                <div class="card-header">
                    <h3 style="color: #A31D1D;">Detailed Evaluation Responses</h3>
                </div>
                <?php foreach ($criteria as $criterion): ?>
                    <?php if (isset($organized_responses[$criterion['id']])): ?>
                        <div style="margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
                            <h4 style="color: #2c3e50; margin-bottom: 15px;">
                                <?php echo $criterion['order_num']; ?>. <?php echo htmlspecialchars($criterion['criteria_name']); ?>
                            </h4>
                            <p style="color: #7f8c8d; margin-bottom: 20px; font-style: italic;">
                                <?php echo htmlspecialchars($criterion['criteria_description']); ?>
                            </p>
                            
                            <?php foreach ($organized_responses[$criterion['id']] as $response): ?>
                                <div style="background: #f8f9fa; padding: 15px; margin-bottom: 10px; border-radius: 5px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <strong style="color: #2c3e50;">
                                            <?php echo htmlspecialchars($response['evaluator_role']); ?> - 
                                            <?php echo htmlspecialchars($response['evaluator_name']); ?>
                                        </strong>
                                        <span class="badge badge-score">
                                            Score: <?php echo $response['score']; ?>/5
                                        </span>
                                    </div>
                                    <div style="color: #555;">
                                        <strong>Comments:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($response['comments'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <h3>Evaluation In Progress</h3>
                    <p>This evaluation is still being processed by the evaluators.</p>
                    <p>Results will be available once all evaluators have completed their assessments.</p>
                </div>
            </div>

            
        <?php endif; ?>
    </div>
    <button onclick="scrollToTop()" id="scrollTopBtn" title="Go to top">↑</button>
        <script>
            window.addEventListener('scroll', function() {
            const btn = document.getElementById('scrollTopBtn');
                if (window.scrollY > 300) {
                    btn.style.display = 'block';
                } else {
                    btn.style.display = 'none';
                }
            });

        function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        }
</script>
</body>
</html>