<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';

requireLogin();
if (!hasRole(0)) { // Admin role ID is 0
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$user = getCurrentUser();

// Get view mode (list or summary)
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'list';

// Get department filter from GET parameter
$department_filter = (isset($_GET['department']) && $_GET['department'] !== '') ? intval($_GET['department']) : null;

// Check if user has HR role (role_id = 1) for department filter visibility
$show_department_filter = shouldShowDepartmentFilter($user);

// Get all evaluations with optional department filter and user restrictions
if ($view_mode === 'summary') {
    $all_evaluations = getCompletedEvaluationsSummary($department_filter, $user);
} else {
    $all_evaluations = getAllEvaluations($department_filter, $user);
}

// Get all departments for filter dropdown
$departments = getAllDepartments();
$user = getCurrentUser(); // Or however you get the logged-in user
$departments = getAllDepartments(); // List of departments
$view_mode = $_GET['view'] ?? 'list';
$department_filter = $_GET['department'] ?? '';
$show_department_filter = isAdmin() || hasRole(2); // Example: only admins and HR

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel' && $view_mode === 'summary') {
    exportEvaluationsSummaryToExcel($all_evaluations, $department_filter);
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}

// Function to get completed evaluations summary with general scores
function getCompletedEvaluationsSummary($department_filter = null, $user = null)
{
    global $conn;

    $sql = "SELECT ev.id, e.fullname, ed.name as department_name, 
                   AVG(er.score) as general_score
            FROM evaluations ev
            JOIN employees e ON ev.employee_id = e.id
            LEFT JOIN emp_department ed ON e.department_id = ed.id
            LEFT JOIN evaluation_responses er ON ev.id = er.evaluation_id
            WHERE ev.status = 'completed'";

    $where_conditions = [];
    $params = [];
    $param_types = "";

    // Apply department filter if specified
    if ($department_filter !== null && $department_filter !== '') {
        $where_conditions[] = "e.department_id = ?";
        $params[] = $department_filter;
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
        $sql .= " AND " . implode(" AND ", $where_conditions);
    }

    $sql .= " GROUP BY ev.id, e.fullname, ed.name
              ORDER BY e.fullname";

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

// Function to export evaluations summary to Excel
// Function to export evaluations summary to Excel
function exportEvaluationsSummaryToExcel($evaluations, $department_filter)
{
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="evaluations_summary_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    // Start output buffering
    ob_start();

    // Output UTF-8 BOM
    echo "\xEF\xBB\xBF"; // <-- Add this line

    echo '<table border="1">';
    echo '<tr>';
    echo '<th>Employee Name</th>';
    echo '<th>Department</th>';
    echo '<th>General Score</th>';
    echo '<th>Percentage</th>';
    echo '</tr>';

    foreach ($evaluations as $evaluation) {
        $percentage_score = ($evaluation['general_score'] / 5) * 100;
        list($final_rating, $percentage_desc) = getRatingAndPercentage($percentage_score);
        echo '<tr>';
        echo '<td>' . htmlspecialchars($evaluation['fullname']) . '</td>';
        echo '<td>' . htmlspecialchars($evaluation['department_name']) . '</td>';
        echo '<td>' . number_format($evaluation['general_score'], 2) . '</td>';
        echo '<td>' . $percentage_desc . ' (Final Rating: ' . $final_rating . ')</td>';
        echo '</tr>';
    }

    echo '</table>';

    $content = ob_get_clean();
    echo $content;
}

// Helper function to convert general score to rating and percentage description
function getRatingAndPercentage($score)
{
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($view_mode === 'summary') ? 'Evaluations Summary' : 'View All Evaluations'; ?> - Admin</title>
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
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding-top: 10px;">

                <!-- LEFT SIDE: Department Filter -->
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <?php if ($show_department_filter): ?>
                        <form method="GET" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                            <label for="department" style="font-weight: bold;">Filter by Department:</label>
                            <select name="department" id="department" class="form-control" style="width: auto;" onchange="this.form.submit()">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($department_filter == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- RIGHT SIDE: View/Action Buttons -->
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <?php if ($view_mode !== 'list'): ?>
                        <a href="?view=list<?php echo $department_filter ? '&department=' . $department_filter : ''; ?>"
                            class="btn btn-primary-admin">
                            View All Evaluations
                        </a>
                    <?php endif; ?>

                    <?php if ($view_mode !== 'summary'): ?>
                        <a href="?view=summary<?php echo $department_filter ? '&department=' . $department_filter : ''; ?>"
                            class="btn btn-primary-admin">
                            View Evaluations Summary
                        </a>
                    <?php endif; ?>

                    <?php if ($view_mode === 'summary'): ?>
                        <a href="?view=summary&export=excel<?php echo $department_filter ? '&department=' . $department_filter : ''; ?>"
                            class="btn btn-primary-admin"
                            style="
                    background: linear-gradient(to right, #1e7e34, #5cd168);
                    border: none;
                    color: white;
                    padding: 10px 24px;
                    font-size: 15px;
                    font-weight: bold;
                    border-radius: 8px;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                "
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 18px rgba(92, 209, 104, 0.4)';"
                            onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                            Convert to Excel File
                        </a>


                    <?php endif; ?>

                    <a href="admin_dashboard.php" class="btn btn-primary-admin">Back to Dashboard</a>
                </div>
            </div>

            <?php if ($view_mode === 'summary'): ?>
                <!-- Summary View Table -->
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>General Score</th>
                            <th>Percentage</th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_evaluations)): ?>
                            <?php foreach ($all_evaluations as $evaluation):
                                $percentage_score = ($evaluation['general_score'] / 5) * 100;
                                list($final_rating, $percentage_desc) = getRatingAndPercentage($percentage_score);

                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($evaluation['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($evaluation['department_name']); ?></td>
                                    <td><?php echo number_format($evaluation['general_score'], 2); ?></td>
                                    <td><?php echo $percentage_desc . " (Final Rating: $final_rating)"; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No completed evaluations found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Regular List View Table -->
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Evaluation Reason</th>
                            <th>Status</th>
                            <th>Evaluation Date</th>
                            <th>Period Covered</th>
                            <th>Current Step</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_evaluations)): ?>
                            <?php foreach ($all_evaluations as $evaluation):
                                $current_step = getCurrentEvaluationStep($evaluation['id']);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($evaluation['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($evaluation['evaluation_reason']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $evaluation['status']; ?>">
                                            <?php echo ucfirst($evaluation['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($evaluation['evaluation_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($evaluation['period_covered_from'])) . ' - ' . date('M d, Y', strtotime($evaluation['period_covered_to'])); ?></td>
                                    <td><?php echo htmlspecialchars($current_step); ?></td>
                                    <td>
                                        <a href="admin_view_evaluation.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-primary">View</a>
                                        <a href="admin_delete_evaluation.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this evaluation and all related data?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No evaluations found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>