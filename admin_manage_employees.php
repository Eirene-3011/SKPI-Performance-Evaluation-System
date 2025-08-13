<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';
require_once 'admin_functions.php';

// Check if user is logged in and is an admin
requireAdmin();

$user = getCurrentUser();
$employees = getAllEmployees();
$departments = getAllDepartments();
$positions = getAllPositions();
$sections = getAllSections();
$roles = getAllRoles();

$success_message = '';
$error_message = '';

// Handle employee actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $employee_id = (int)$_POST['employee_id'];

        if ($_POST['action'] == 'delete') {
            // Delete employee
            if (deleteEmployee($employee_id)) {
                $success_message = "Employee deleted successfully.";
                // Refresh employee list
                $employees = getAllEmployees();
            } else {
                $error_message = "Error deleting employee.";
            }
        } else if ($_POST['action'] == 'reset_password') {
            // Reset employee password
            if (resetEmployeePassword($employee_id)) {
                $success_message = "Employee password reset successfully.";
            } else {
                $error_message = "Error resetting employee password.";
            }
        }
    } else if (isset($_POST['add_employee'])) {
        // Add new employee
        $card_no = trim($_POST['card_no']);
        $firstname = trim($_POST['firstname']);
        $middlename = trim($_POST['middlename'] ?? '');
        $lastname = trim($_POST['lastname']);
        $suffixname = trim($_POST['suffixname'] ?? '');
        $department_id = (int)$_POST['department_id'];
        $position_id = (int)$_POST['position_id'];
        $section_id = (int)$_POST['section_id'];
        $role_id = (int)$_POST['role_id'];
        $hired_date = $_POST['hired_date'];

        // Validate inputs
        if (empty($card_no) || empty($firstname) || empty($lastname) || empty($hired_date)) {
            $error_message = "Required fields cannot be empty.";
        } else {
            // Add employee
            if (addEmployee($card_no, $firstname, $middlename, $lastname, $suffixname, $department_id, $position_id, $section_id, $role_id, $hired_date)) {
                $success_message = "Employee added successfully.";
                // Refresh employee list
                $employees = getAllEmployees();
            } else {
                $error_message = "Error adding employee.";
            }
        }
    } else if (isset($_POST['edit_employee'])) {
        // Edit employee
        $id = (int)$_POST['id'];
        $card_no = trim($_POST['card_no']);
        $firstname = trim($_POST['firstname']);
        $middlename = trim($_POST['middlename'] ?? '');
        $lastname = trim($_POST['lastname']);
        $suffixname = trim($_POST['suffixname'] ?? '');
        $department_id = (int)$_POST['department_id'];
        $position_id = (int)$_POST['position_id'];
        $section_id = (int)$_POST['section_id'];
        $role_id = (int)$_POST['role_id'];
        $hired_date = $_POST['hired_date'];
        $is_inactive = isset($_POST['is_inactive']) ? 1 : 0;

        // Validate inputs
        if (empty($card_no) || empty($firstname) || empty($lastname) || empty($hired_date)) {
            $error_message = "Required fields cannot be empty.";
        } else {
            // Update employee
            if (updateEmployee($id, $card_no, $firstname, $middlename, $lastname, $suffixname, $department_id, $position_id, $section_id, $role_id, $hired_date, $is_inactive)) {
                $success_message = "Employee updated successfully.";
                // Refresh employee list
                $employees = getAllEmployees();
            } else {
                $error_message = "Error updating employee.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Admin - Performance Evaluation System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .employee-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .employee-table th,
        .employee-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .employee-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .employee-table tr:hover {
            background-color: #f5f5f5;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-box {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
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
                <h2 class="card-title">Manage Employees</h2>
                <button onclick="openAddModal()" class="btn btn-primary">Add New Employee</button>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="search-box">
                <input type="text" id="employeeSearch" class="search-input" placeholder="Search employees by name, ID, department, or position...">
            </div>

            <table class="employee-table" id="employeeTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Role</th>
                        <th>Hire Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['card_no']); ?></td>
                            <td><?php echo htmlspecialchars($employee['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($employee['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['position_name']); ?></td>
                            <td><?php echo htmlspecialchars($employee['role_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($employee['hired_date'])); ?></td>
                            <td><?php echo $employee['is_inactive'] ? 'Inactive' : 'Active'; ?></td>
                            <td class="action-buttons">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($employee)); ?>)" class="btn btn-small btn-secondary">Edit</button>

                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to reset this employee\'s password?');" style="display: inline;">
                                    <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                    <input type="hidden" name="action" value="reset_password">
                                    <button type="submit" class="btn btn-small btn-warning">Reset Password</button>
                                </form>

                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this employee?');" style="display: inline;">
                                    <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Employee Modal -->
        <div id="addEmployeeModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddModal()">&times;</span>
                <h2>Add New Employee</h2>
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="card_no" class="form-label">Employee ID *</label>
                            <input type="text" name="card_no" id="card_no" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="hired_date" class="form-label">Hire Date *</label>
                            <input type="date" name="hired_date" id="hired_date" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="firstname" class="form-label">First Name *</label>
                            <input type="text" name="firstname" id="firstname" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="middlename" class="form-label">Middle Name</label>
                            <input type="text" name="middlename" id="middlename" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="lastname" class="form-label">Last Name *</label>
                            <input type="text" name="lastname" id="lastname" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="suffixname" class="form-label">Suffix</label>
                            <input type="text" name="suffixname" id="suffixname" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="department_id" class="form-label">Department *</label>
                            <select name="department_id" id="department_id" class="form-control" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department['id']; ?>">
                                        <?php echo htmlspecialchars($department['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="position_id" class="form-label">Position *</label>
                            <select name="position_id" id="position_id" class="form-control" required>
                                <option value="">-- Select Position --</option>
                                <?php foreach ($positions as $position): ?>
                                    <option value="<?php echo $position['id']; ?>">
                                        <?php echo htmlspecialchars($position['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="section_id" class="form-label">Section</label>
                            <select name="section_id" id="section_id" class="form-control">
                                <option value="">-- Select Section --</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo $section['id']; ?>">
                                        <?php echo htmlspecialchars($section['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="role_id" class="form-label">Role *</label>
                            <select name="role_id" id="role_id" class="form-control" required>
                                <option value="">-- Select Role --</option>
                                <option value="0">Admin</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>">
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <input type="hidden" name="add_employee" value="1">
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                        <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Employee Modal -->
        <div id="editEmployeeModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h2>Edit Employee</h2>
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_card_no" class="form-label">Employee ID *</label>
                            <input type="text" name="card_no" id="edit_card_no" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_hired_date" class="form-label">Hire Date *</label>
                            <input type="date" name="hired_date" id="edit_hired_date" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_firstname" class="form-label">First Name *</label>
                            <input type="text" name="firstname" id="edit_firstname" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_middlename" class="form-label">Middle Name</label>
                            <input type="text" name="middlename" id="edit_middlename" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="edit_lastname" class="form-label">Last Name *</label>
                            <input type="text" name="lastname" id="edit_lastname" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_suffixname" class="form-label">Suffix</label>
                            <input type="text" name="suffixname" id="edit_suffixname" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="edit_department_id" class="form-label">Department *</label>
                            <select name="department_id" id="edit_department_id" class="form-control" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department['id']; ?>">
                                        <?php echo htmlspecialchars($department['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_position_id" class="form-label">Position *</label>
                            <select name="position_id" id="edit_position_id" class="form-control" required>
                                <option value="">-- Select Position --</option>
                                <?php foreach ($positions as $position): ?>
                                    <option value="<?php echo $position['id']; ?>">
                                        <?php echo htmlspecialchars($position['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_section_id" class="form-label">Section</label>
                            <select name="section_id" id="edit_section_id" class="form-control">
                                <option value="">-- Select Section --</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo $section['id']; ?>">
                                        <?php echo htmlspecialchars($section['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_role_id" class="form-label">Role *</label>
                            <select name="role_id" id="edit_role_id" class="form-control" required>
                                <option value="">-- Select Role --</option>
                                <option value="0">Admin</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>">
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label class="form-checkbox">
                            <input type="checkbox" name="is_inactive" id="edit_is_inactive">
                            Mark as Inactive
                        </label>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="edit_employee" value="1">
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('addEmployeeModal').style.display = 'block';
            document.getElementById('hired_date').valueAsDate = new Date();
        }

        function closeAddModal() {
            document.getElementById('addEmployeeModal').style.display = 'none';
        }

        function openEditModal(employee) {
            document.getElementById('edit_id').value = employee.id;
            document.getElementById('edit_card_no').value = employee.card_no;
            document.getElementById('edit_firstname').value = employee.firstname;
            document.getElementById('edit_middlename').value = employee.middlename;
            document.getElementById('edit_lastname').value = employee.lastname;
            document.getElementById('edit_suffixname').value = employee.suffixname;
            document.getElementById('edit_department_id').value = employee.department_id;
            document.getElementById('edit_position_id').value = employee.position_id;
            document.getElementById('edit_section_id').value = employee.section_id;
            document.getElementById('edit_role_id').value = employee.role_id;
            document.getElementById('edit_hired_date').value = employee.hired_date.split(' ')[0]; // Format date
            document.getElementById('edit_is_inactive').checked = employee.is_inactive == 1;

            document.getElementById('editEmployeeModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editEmployeeModal').style.display = 'none';
        }

        // Search functionality
        document.getElementById('employeeSearch').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('employeeTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) { // Start from 1 to skip header row
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length - 1; j++) { // Skip the Actions column
                    const cellText = cells[j].textContent.toLowerCase();
                    if (cellText.includes(searchValue)) {
                        found = true;
                        break;
                    }
                }

                row.style.display = found ? '' : 'none';
            }
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addEmployeeModal');
            const editModal = document.getElementById('editEmployeeModal');

            if (event.target == addModal) {
                addModal.style.display = 'none';
            } else if (event.target == editModal) {
                editModal.style.display = 'none';
            }
        }
    </script>
</body>

</html>