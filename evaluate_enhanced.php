<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';
require_once 'email_functions.php';

requireLogin();

$evaluation_id = (int)$_GET['id'];
$evaluation = getEvaluationById($evaluation_id);
$user = getCurrentUser();

if (!$evaluation) {
    header("Location: dashboard.php?error=evaluation_not_found");
    exit();
}

// Strict sequential access control: Only the current evaluator can access this page
if ($evaluation['current_evaluator_role_id'] != $user['role_id']) {
    header("Location: dashboard.php?error=not_your_turn");
    exit();
}

// Department-based access control: Check if evaluator can evaluate this employee
if (!canEvaluateEmployee($user, $evaluation['employee_id'])) {
    header("Location: dashboard.php?error=department_access_denied");
    exit();
}

$criteria = getEvaluationCriteria();

// Remove duplicates by criteria_name
$unique = [];
foreach ($criteria as $criterion) {
    $key = strtolower(trim($criterion['criteria_name']));
    if (!isset($unique[$key])) {
        $unique[$key] = $criterion;
    }
}
$criteria = array_values($unique);

// Get criteria permissions for current user role
$criteria_permissions = getCriteriaPermissions($user['role_id']);
$success_message = '';
$error_message = '';

// Get existing responses for this evaluator
$existing_responses = [];
$sql = "SELECT * FROM evaluation_responses WHERE evaluation_id = ? AND evaluator_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $evaluation_id, $user['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $existing_responses[$row['criteria_id']] = $row;
}

// Recommendation options
$recommendation_options = [
    'For Probationary' => 'For Probationary',
    'For Continued Probation' => 'For Continued Probation',
    'For Regularization' => 'For Regularization',
    'Unsatisfactory' => 'Unsatisfactory'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $all_saved = true;
    $evaluation_complete = true;
    $recommendation = trim($_POST['recommendation'] ?? '');

    // Validate recommendation
    if (empty($recommendation) || !array_key_exists($recommendation, $recommendation_options)) {
        $error_message = "Please select a valid recommendation.";
        $all_saved = false;
        $evaluation_complete = false;
    }

    foreach ($criteria as $criterion) {
        // Only process editable criteria for this user role
        if (in_array($criterion['order_num'], $criteria_permissions['editable'])) {
            $score = (int)$_POST['score_' . $criterion['id']];
            $comments = trim($_POST['comments_' . $criterion['id']]);

            if ($score < 1 || $score > 5) {
                $error_message = "Invalid score for " . $criterion['criteria_name'];
                $all_saved = false;
                break;
            }

            if (empty($comments)) {
                $error_message = "Comments are required for " . $criterion['criteria_name'];
                $all_saved = false;
                $evaluation_complete = false;
                break;
            }

            if (!saveEvaluationResponse($evaluation_id, $criterion['id'], $user['id'], $score, $comments, $recommendation)) {
                $error_message = "Error saving response for " . $criterion['criteria_name'];
                $all_saved = false;
                break;
            }
        }
    }

    if ($all_saved && $evaluation_complete) {
        // Mark this evaluation step as complete and advance to next evaluator
        $fully_complete = completeEvaluationStep($evaluation_id, $user['role_id'], $user['id']);

        if ($fully_complete) {
            $success_message = "Evaluation completed successfully! All evaluators have finished and the employee has been notified.";
        } else {
            $success_message = "Your evaluation has been submitted successfully! The next evaluator has been notified.";
        }
    } elseif ($all_saved) {
        $success_message = "Your responses have been saved. Please complete all required fields to submit.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate Employee - Performance Evaluation System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Container for criteria section */
        .criteria-section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
            background-color: #FEF9E1;
            /* warm light yellow-beige background */
            border-left: 4px solid transparent;
        }

        /* Readonly criteria section visually distinct */
        .criteria-section.readonly {
            background-color: #E5D0AC;
            /* muted beige */
            border-left: 4px solid #A31D1D;
            /* strong dark red left border */
            opacity: 0.85;
        }

        /* Criteria title styling */
        .criteria-section h3 {
            margin-top: 0;
            color: #A31D1D;
            /* dark red for headings */
        }

        /* Score section margin */
        .score-section {
            margin: 15px 0;
        }

        /* Score options container: horizontal flex with gap */
        .score-options {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }

        /* Individual score option */
        .score-option {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Radio buttons get pointer cursor */
        .score-option input[type="radio"] {
            cursor: pointer;
        }

        /* Comments section spacing */
        .comments-section {
            margin: 15px 0;
        }

        /* Comments textarea style */
        .comments-section textarea {
            width: 100%;
            min-height: 80px;
            padding: 8px;
            border: 1px solid #A31D1D;
            /* dark red border */
            border-radius: 6px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #FEF9E1;
            /* light yellow background */
            resize: vertical;
        }

        /* Readonly comments textarea style */
        .comments-section textarea[readonly] {
            background-color: #E5D0AC;
            color: #4B3B2A;
            cursor: not-allowed;
        }

        /* Recommendation section styling */
        .recommendation-section {
            margin: 30px 0;
            padding: 20px;
            border: 2px solid #A31D1D;
            /* dark red border */
            border-radius: 8px;
            background-color: #FEF9E1;
            /* light yellow background */
        }

        /* Recommendation section heading */
        .recommendation-section h3 {
            color: #A31D1D;
            margin-top: 0;
        }

        /* Recommendation select styling */
        .recommendation-select {
            width: 100%;
            padding: 8px;
            border: 1px solid #A31D1D;
            border-radius: 6px;
            font-size: 16px;
        }

        /* Employee info section */
        .employee-info {
            background-color: #FEF9E1;
            /* light yellow */
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            color: #4B3B2A;
        }

        /* Employee info headings */
        .employee-info h3,
        .employee-info h4 {
            color: #A31D1D;
            margin-top: 0;
        }

        /* Buttons container */
        .btn-container {
            text-align: center;
            margin-top: 30px;
        }

        /* Buttons (assuming your btn classes from external CSS, but add pointer for safety) */
        .btn,
        .btn-primary,
        .btn-secondary,
        .btn-info {
            cursor: pointer;
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
                    <p><strong><?php echo strtoupper($user['role_name']); ?></strong> - <?php echo htmlspecialchars($user['department_name']); ?></p>
                    <p>Employee ID: <?php echo htmlspecialchars($user['card_no']); ?></p>
                    <button onclick="location.href='dashboard.php'" class="btn btn-secondary">Back to Dashboard</button>
                    <button onclick="location.href='?logout=1'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Employee Performance Evaluation</h2>
            </div>

            <!-- Employee Information -->
            <div class="employee-info">
                <h3>Employee Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($evaluation['fullname']); ?></p>
                <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($evaluation['card_no']); ?></p>
                <p><strong>Position:</strong> <?php echo htmlspecialchars($evaluation['position_name']); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($evaluation['department_name']); ?></p>
                <p><strong>Evaluation Period:</strong> <?php echo date('M d, Y', strtotime($evaluation['period_covered_from'])); ?> - <?php echo date('M d, Y', strtotime($evaluation['period_covered_to'])); ?></p>
                <p><strong>Evaluation Reason:</strong> <?php echo htmlspecialchars($evaluation['evaluation_reason']); ?></p>

                <?php if ($evaluation['employee_role_id'] == 5): // Staff role 
                ?>
                    <h4>Additional Information (Staff Only)</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                        <div>
                            <p><strong>Approved Leaves:</strong> <?php echo $evaluation['approved_leaves'] ?? 0; ?></p>
                            <p><strong>Disapproved Leaves:</strong> <?php echo $evaluation['disapproved_leaves'] ?? 0; ?></p>
                            <p><strong>Tardiness:</strong> <?php echo $evaluation['tardiness'] ?? 0; ?></p>
                            <p><strong>Late/Undertime:</strong> <?php echo $evaluation['late_undertime'] ?? 0; ?></p>
                        </div>
                        <div>
                            <p><strong>1st Offense:</strong> <?php echo $evaluation['offense_1st'] ?? 0; ?></p>
                            <p><strong>2nd Offense:</strong> <?php echo $evaluation['offense_2nd'] ?? 0; ?></p>
                            <p><strong>3rd Offense:</strong> <?php echo $evaluation['offense_3rd'] ?? 0; ?></p>
                            <p><strong>4th Offense:</strong> <?php echo $evaluation['offense_4th'] ?? 0; ?></p>
                            <p><strong>5th Offense:</strong> <?php echo $evaluation['offense_5th'] ?? 0; ?></p>
                            <p><strong>Suspension Days:</strong> <?php echo $evaluation['suspension_days'] ?? 0; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
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
                    <a href="view_evaluation.php?id=<?php echo $evaluation_id; ?>" class="btn btn-info">See Evaluation</a>
                    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <?php foreach ($criteria as $criterion): ?>
                        <?php
                        $is_editable = in_array($criterion['order_num'], $criteria_permissions['editable']);
                        $existing_response = $existing_responses[$criterion['id']] ?? null;
                        ?>

                        <div class="criteria-section <?php echo $is_editable ? '' : 'readonly'; ?>">
                            <h3><?php echo htmlspecialchars($criterion['criteria_name']); ?></h3>
                            <p><?php echo htmlspecialchars($criterion['criteria_description']); ?></p>

                            <?php if ($is_editable): ?>
                                <div class="score-section">
                                    <label><strong>Score (1-5):</strong></label>
                                    <div class="score-options">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div class="score-option">
                                                <input type="radio"
                                                    name="score_<?php echo $criterion['id']; ?>"
                                                    value="<?php echo $i; ?>"
                                                    id="score_<?php echo $criterion['id']; ?>_<?php echo $i; ?>"
                                                    <?php echo ($existing_response && $existing_response['score'] == $i) ? 'checked' : ''; ?>
                                                    required>
                                                <label for="score_<?php echo $criterion['id']; ?>_<?php echo $i; ?>"><?php echo $i; ?></label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="comments-section">
                                    <label for="comments_<?php echo $criterion['id']; ?>"><strong>Comments:</strong></label>
                                    <textarea name="comments_<?php echo $criterion['id']; ?>"
                                        id="comments_<?php echo $criterion['id']; ?>"
                                        placeholder="Please provide detailed comments for this criterion..."
                                        required><?php echo $existing_response ? htmlspecialchars($existing_response['comments']) : ''; ?></textarea>
                                </div>
                            <?php else: ?>
                                <div class="score-section">
                                    <label><strong>Score:</strong></label>
                                    <p><?php echo $existing_response ? $existing_response['score'] : 'Not yet evaluated'; ?></p>
                                </div>

                                <div class="comments-section">
                                    <label><strong>Comments:</strong></label>
                                    <p><?php echo $existing_response ? htmlspecialchars($existing_response['comments']) : 'Not yet evaluated'; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Recommendation Section -->
                    <div class="recommendation-section">
                        <h3>Recommendation</h3>
                        <p>Based on your evaluation, please select your recommendation for this employee:</p>
                        <select name="recommendation" class="recommendation-select" required>
                            <option value="">-- Select Recommendation --</option>
                            <?php foreach ($recommendation_options as $value => $label): ?>
                                <?php
                                $selected = '';
                                if (!empty($existing_responses)) {
                                    $first_response = reset($existing_responses);
                                    if ($first_response && $first_response['recommendation'] == $value) {
                                        $selected = 'selected';
                                    }
                                }
                                ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">Submit Evaluation</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>