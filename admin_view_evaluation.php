<?php
    require_once 'auth.php';
    require_once 'database_functions_enhanced.php';
    require_once 'admin_functions.php'; // Include admin_functions for generateDetailedEvaluationReport

    requireLogin();
    if (!isAdmin()) { // Ensure only admins can access
        header("Location: dashboard.php?error=access_denied");
        exit();
    }

    $user = getCurrentUser();
    $evaluation_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

    $report_data = generateDetailedEvaluationReport($evaluation_id);

    if (!$report_data || !$report_data['evaluation']) {
        echo "Evaluation not found.";
        exit();
    }

    $evaluation = $report_data['evaluation'];
    $criteria_responses = $report_data['criteria_responses'];
    $workflow = $report_data['workflow'];
    $workflow_status = getEvaluationWorkflowStatus($evaluation_id);
    $summary = getEvaluationSummary($evaluation_id);

    // Get evaluation responses with recommendations
    $evaluation_responses = getEvaluationResponses($evaluation_id);

    // Group responses by evaluator
    $responses_by_evaluator = [];
    foreach ($evaluation_responses as $response) {
        $evaluator_key = $response['evaluator_name'] . ' (' . $response['evaluator_role'] . ')';
        $responses_by_evaluator[$evaluator_key][] = $response;
    }

    // Function to get evaluator information for print section
    function getEvaluatorInformation($evaluation_id) {
        global $conn;
        
        $sql = "SELECT DISTINCT 
                    e.fullname as evaluator_name,
                    er.name as role_name,
                    ed.name as department_name,
                    ew.evaluator_role_id
                FROM evaluation_workflow ew
                JOIN emp_roles er ON ew.evaluator_role_id = er.id
                LEFT JOIN employees e ON ew.evaluator_id = e.id
                LEFT JOIN emp_department ed ON e.department_id = ed.id
                WHERE ew.evaluation_id = ? AND ew.status = 'completed'
                ORDER BY ew.step_order";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    function getRatingAndPercentage($score) {
    if ($score >= 95) {
        return ['A', '95-100% Excellent'];
    } elseif ($score >= 85) {
        return ['B', '85-94% Meets and Exceeds'];
    } elseif ($score >= 70) {
        return ['C', '70-84% Satisfactory'];
    } elseif ($score >= 55) {
        return ['D', '55-69% Below Expectation'];
    } else {
        return ['E', '≤54% Unsatisfactory/Poor'];
    }
}

    
    $evaluators_info = [];
    foreach ($responses_by_evaluator as $evaluator_key => $responses) {
        // Extract role and name from the evaluator_key
        preg_match("/(.*) \((.*)\)/", $evaluator_key, $matches);
        $evaluator_name = $matches[1] ?? 'Unknown';
        $evaluator_role = $matches[2] ?? 'Unknown';

        // Assuming department can be derived from the first response for that evaluator
        // This might need adjustment if evaluators can have different departments for different responses
        $department_name = $responses[0]['evaluator_department'] ?? 'N/A';

        $evaluators_info[] = [
            'role_name' => $evaluator_role,
            'evaluator_name' => $evaluator_name,
            'department_name' => $department_name
        ];
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>View Evaluation - Admin</title>
        <link rel="stylesheet" href="assets/style.css">
        <style>
            /* --- Screen Styles --- */
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
            border-radius: 0;
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
        @media screen {
            .only-print {
                display: none;
            }
        }

        /* --- Print Styles --- */
        @media print {
            .header,
            .btn,
            .back-button-container,
            .no-print,
            .workflow-status-item,
            .recommendations-section {
                display: none !important;
            }
            #scrollTopBtn {
        display: none !important;
    }
            .only-print {
                display: block !important;
            }

            body {
                background: white !important;
                font-size: 12px;
                color: #000;
                zoom: 88%; /* Add this */
            }

            .card,
            .staff-additional-info,
            .info-section {
                background: none !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 0 20px 0 !important;
            }

            .card-header,
            .card-title {
                border: none !important;
                background: none !important;
                padding: 0 !important;
                margin: 0 0 10px 0 !important;
            }

            .info-label,
            .info-value {
                color: #000 !important;
            }

            .info-item {
                border-bottom: 1px solid #ccc;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
            }

            .table th,
            .table td {
                border: 1px solid #000;
                padding: 5px;
                text-align: left;
            }
            .badge-score {
                color: #000 !important;
                font-weight: bold !important;
                background-color: transparent !important;
            }
        }

        /* Evaluator Information Section for Print */
        .evaluator-info-section {
            margin: 30px 0;
            padding: 20px 0;
            border-top: 2px solid #000;
        }
        
        .evaluator-info-section h3 {
            color: #000;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .evaluator-item {
            margin-bottom: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #ccc;
        }
        
        .evaluator-item:last-child {
            border-bottom: none;
        }
        
        .evaluator-role {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .evaluator-name {
            margin-bottom: 5px;
        }
        
        .signature-line {
            margin-top: 10px;
            border-bottom: 1px solid #000;
            width: 300px;
            height: 20px;
        }
        @media print {
            .evaluator-table td {
                vertical-align: top;
            }

            .signature-line {
                height: 20px;
                display: block;
            }
            .print-hide-title {
                display: none !important;
            }
        }

            .recommendations-table-wrapper {
                overflow-x: auto;
            }

            .recommendations-table {
                width: 100%;
                border-collapse: collapse;
                background-color: #FEF9E1;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                overflow: hidden;
                font-family: inherit;
            }

            .recommendations-table thead {
                background-color: #FEF9E1;
            }

            .recommendations-table th,
            .recommendations-table td {
                text-align: left;
                padding: 12px 16px;
                color: #4B3B2A;
                border-bottom: 1px solid #e0e0e0;
                font-size: 14px;
            }

            .recommendations-table th {
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .recommendations-table tbody tr:last-child td {
                border-bottom: none;
            }

            .violations-grid {
                display: grid;
                grid-template-columns: 1fr 1fr; /* Two equal columns */
                gap: 10px 20px; /* Space between rows and columns */
            }

            .violations-grid .info-item {
                display: flex;
                justify-content: space-between;
            }
            .employee-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

@media print {
    .employee-info-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 10px !important;
        margin-bottom: 25px !important;
    }

    .employee-info-grid > div {
        font-size: 14px !important;
        break-inside: avoid !important;
        padding: 4px 0;
    }

    .employee-info-grid strong {
        color: #000 !important;
        font-weight: bold;
    }

    .badge {
        background: none !important;
        color: #000 !important;
        font-weight: bold !important;
        border: none !important;
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
                        <h3><?php echo htmlspecialchars($user["fullname"]); ?></h3>
                        <p><strong>ADMIN</strong> - <?php echo htmlspecialchars($user["department_name"]); ?></p>
                        <p>Employee ID: <?php echo htmlspecialchars($user["card_no"]); ?></p>
                        <button onclick="location.href='admin_dashboard.php'" class="btn btn-secondary">Back to Dashboard</button>
                        <button onclick="location.href='admin_dashboard.php'" class="logout-btn">Logout</button>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="card no-print-header">
                <div class="card-body">
                    <div class="print-hide-title">
                        <h2 class="card-title">Evaluation Report - <?php echo htmlspecialchars($evaluation["fullname"]); ?></h2>
                    </div>
                    <a href="#" class="btn btn-primary" onclick="window.print(); return false;">Print Report</a>
                    <a href="admin_view_evaluations.php" class="btn btn-secondary">Back to Evaluations</a>
                </div>
            </div>

                <!-- Employee Information -->
<div class="card">
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="assets/seiwa.logo.png" alt="Seiwa Kaiun Philippines Inc." style="height: 60px; margin-bottom: 15px;">
        <h1 style="color: #2c3e50; margin-bottom: 5px;">PERFORMANCE EVALUATION REPORT</h1>
        <p style="color: #7f8c8d;">Seiwa Kaiun Philippines Inc.</p>
    </div>

    <div class="employee-info-grid">
        <div>
            <strong>Employee Name:</strong><br>
            <?php echo htmlspecialchars($evaluation['fullname']); ?>
        </div>
        <div>
            <strong>Employee ID:</strong><br>
            <?php echo htmlspecialchars($evaluation['card_no']); ?>
        </div>
        <div>
            <strong>Designation:</strong><br>
            <?php echo htmlspecialchars($evaluation['designation_name'] ?? 'N/A'); ?>
        </div>
        <div>
            <strong>Position:</strong><br>
            <?php echo htmlspecialchars($evaluation['position_name'] ?? 'N/A'); ?>
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
                                <div class="violations-grid">
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
                    </div>
                <?php endif; ?>

        
       <!-- Summary -->
<div class="card">
    <div class="card-header">
        <h3 style="color: #A31D1D;">Performance Evaluation Summary</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Criteria</th>
                <th>Average Score</th>
                <th>Evaluator Comments</th> <!-- ✅ Updated Column Title -->
            </tr>
        </thead>
        <tbody>
            <?php foreach ($summary as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['criteria_name']); ?></td>
                    <td>
                        <span class="badge badge-score">
                            <?php echo number_format($item['average_score'], 2); ?>
                        </span>
                    </td>
                    <td style="font-size: 12px;">
                        <?php
                        $comments = [];

                        foreach ($criteria_responses as $evaluator_response) {
                            foreach ($evaluator_response['responses'] as $response) {
                                if (
                                    $response['criteria_name'] === $item['criteria_name']
                                    && !empty($response['comments'])
                                ) {
                                    $comments[] = "<strong>" . htmlspecialchars($response['evaluator_name']) . ":</strong> " . htmlspecialchars($response['comments']);
                                }
                            }
                        }

                        echo !empty($comments) ? implode("<br><br>", $comments) : 'No comment';
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php
                // General score calculation
                $total_score = 0;
                $total_count = 0;
                foreach ($criteria_responses as $data) {
                    foreach ($data['responses'] as $response) {
                        $total_score += $response['score'];
                        $total_count++;
                    }
                }
                $general_average = $total_count > 0 ? round($total_score / $total_count, 2) : 0;
                $percentage_score = ($general_average / 5) * 100;
                list($final_rating, $percentage_desc) = getRatingAndPercentage($percentage_score);
            ?>

            <tr>
                <td><strong>General Score</strong></td>
                <td>
                    <span class="badge badge-score" style="background-color: #2e7d32; color: white;">
                        <?php echo number_format($general_average, 2); ?>
                    </span>
                </td>
                <td style="font-size: 12px;">
                    <span class="badge badge-score" style="background-color: #2e7d32; color: white;">
                        <?php echo number_format($percentage_score, 1); ?>%
                        (<?php echo $percentage_desc; ?>, Final Rating: <?php echo $final_rating; ?>)
                    </span>
                </td>
            </tr>

        </tbody>
    </table>
</div>


        <!-- Evaluation Criteria and Responses -->
        <?php if (!empty($criteria_responses)): ?>
            <div class="no-print">
                <div class="staff-additional-info">
                    <h3 style="color: #A31D1D;">Evaluation Criteria and Responses</h3>
                    <?php foreach ($criteria_responses as $criteria_id => $data): ?>
                        <?php
                            $criteria_total = 0;
                            $criteria_count = 0;
                            foreach ($data['responses'] as $response) {
                                $criteria_total += $response['score'];
                                $criteria_count++;
                            }
                            $criteria_average = $criteria_count > 0 ? round($criteria_total / $criteria_count, 2) : 0;
                            // No need to accumulate total_score and total_count here anymore
                        ?>
                        <div class="info-section" style="margin-bottom: 20px;">
                            <h4><?php echo htmlspecialchars($data['criteria_name']); ?></h4>
                            <?php foreach ($data['responses'] as $response): ?>
                                <div class="info-item">
                                    <span class="info-label">Evaluator:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($response['evaluator_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Score:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($response['score']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Comments:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($response['comments']); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div style="margin-top: 10px; display: flex; justify-content: flex-end;">
                            <div class="badge badge-score" style="background-color: #2e7d32; color: white; font-size: 12px; padding: 6px 12px;">    
                                <span class="info-label" style="color: inherit;"><strong>Average Score:</strong></span>
                                <span class="info-value" style="font-weight: bold; color: inherit;"><?php echo $criteria_average; ?></span>
                            </div>
    </div>
</div>
                    <?php endforeach; ?>

                    <!-- Removed general average block from here -->

                </div>
            </div>
        <?php else: ?>
            <p>No criteria responses found for this evaluation.</p>
        <?php endif; ?>


                    
        <!-- Recommendations Section -->
        <?php if (!empty($responses_by_evaluator)): ?>
            <div class="staff-additional-info no-print">
                <h3 style="color: #A31D1D;">Evaluator Recommendations</h3>
                <div class="recommendations-table-wrapper">
                    <table class="recommendations-table">
                        <thead>
                            <tr>
                                <th>Evaluator</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responses_by_evaluator as $evaluator => $responses): ?>
                                <?php 
                                    $recommendation = $responses[0]['recommendation'] ?? 'Not specified';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($evaluator); ?></td>
                                    <td><?php echo htmlspecialchars($recommendation); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>


        <!-- Page 2: Evaluator Info + Signatories -->
            <div class="only-print evaluator-info-section" style="page-break-before: always;">
                <h3>Evaluated by:</h3>
                <?php if (!empty($evaluators_info)): ?>
                    <table class="table evaluator-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Role</th>
                                <th style="width: 30%;">Name</th>
                                <th style="width: 20%;">Signature</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluators_info as $evaluator): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($evaluator['role_name']); ?></td>
                                    <td><?php echo htmlspecialchars($evaluator['evaluator_name'] ?: 'Not assigned'); ?></td>
                                    <td>
                                        <div class="signature-line" style="border-bottom: 1px solid #000; width: 100%; height: 20px;"></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No evaluator information available.</p>
                <?php endif; ?>

                <!-- Signatories Section (now below evaluator table) -->
                <div class="card" style="margin-top: 30px;">
                    <p style="margin-top: 40px; margin-bottom: 20px;">
                        This is to acknowledge that this performance appraisal result was shown to and have been fully discussed with me by my superior.
                    </p>

                    <p style="text-align: center; font-size: 14px; font-weight: bold; margin-top: 20px;">Conforme:</p>
                    <br><br>

                    <div style="margin-top: 30px; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 20px; font-size: 12px;">
                        <div style="flex: 1; min-width: 200px;">
                            <p style="text-align: center; font-weight: bold; margin-bottom: 0;">
                                <?php echo htmlspecialchars($evaluation['fullname']); ?>
                            </p>
                            <hr style="margin: 5px 0 0 0;">
                            <p style="text-align: center; margin-bottom: 30px;">
                                Signature of Employee Over Printed Name
                            </p>
                        </div>
                    </div>
                </div>


                    <!-- Reviewed by -->
                    <div style="margin-top: 30px; margin-bottom: 30px; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 20px; font-size: 12px;">
                        <div style="flex: 1; min-width: 200px;">
                            <strong style="display: inline-block; margin-bottom: 50px;">Reviewed by:</strong><br>
                            <hr style="margin: 0;">
                            <p style="text-align: center; margin-bottom: 20px;">
                                Signature over printed name / date<br>
                                <strong>HR SUPERVISOR</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Approved and Noted -->
                    <div style="margin-top: 30px; margin-bottom: 30px; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 20px; font-size: 12px;">
                        <div style="flex: 1; min-width: 200px;">
                            <strong style="display: inline-block; margin-bottom: 50px;">Approved by:</strong><br>
                            <hr style="margin: 0;">
                            <p style="text-align: center; margin-bottom: 30px;">
                                Signature over printed name / date<br>
                                <strong>HR/GA/FINANCE/MIS/OOS/OJS/ Safety Manager</strong>
                            </p>
                        </div>

                        <div style="flex: 1; min-width: 200px;">
                            <strong style="display: inline-block; margin-bottom: 50px;">Noted by:</strong><br>
                            <hr style="margin: 0;">
                            <p style="text-align: center; margin-bottom: 30px;">
                                Signature over printed name / date<br>
                                <strong>HR Manager</strong>
                            </p>
                        </div>
                </div>
            </div>
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