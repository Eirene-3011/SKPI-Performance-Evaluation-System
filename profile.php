<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';

requireLogin();

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
    <title>My Profile - Performance Evaluation System</title>
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
                    <p><?php echo htmlspecialchars($user['role_name']); ?> - <?php echo htmlspecialchars($user['department_name']); ?></p>
                    <p>Employee ID: <?php echo htmlspecialchars($user['card_no']); ?></p>
                    <button onclick="location.href='dashboard.php'" class="btn btn-secondary">Back to Dashboard</button>
                    <button onclick="location.href='?logout=1'" class="logout-btn">Logout</button>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">My Profile</h2>
            </div>
            <div class="profile-details">

                <!-- Profile Image -->
                <div class="profile-picture" style="text-align: center; margin-bottom: 30px;">
                    <img src="<?php echo htmlspecialchars($user['profile_img'] ?? 'assets/default-profile.png'); ?>"
                        alt="Profile Picture"
                        style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover; color: #a31d1d;">
                    <p style="margin-top: 10px; font-weight: bold;"><?php echo htmlspecialchars($user['fullname']); ?></p>
                </div>

                <div class="profile-section">
                    <h3>Personal Information</h3>
                    <div class="profile-grid">
                        <div class="profile-item">
                            <strong>Full Name:</strong>
                            <p><?php echo htmlspecialchars($user['fullname']); ?></p>
                        </div>
                        <div class="profile-item">
                            <strong>Employee ID:</strong>
                            <p><?php echo htmlspecialchars($user['card_no']); ?></p>
                        </div>
                        <div class="profile-item">
                            <strong>Date Hired:</strong>
                            <p><?php echo date('M d, Y', strtotime($user['hired_date'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <h3>Position Information</h3>
                    <div class="profile-grid">
                        <div class="profile-item">
                            <strong>Role:</strong>
                            <p><?php echo htmlspecialchars($user['role_name']); ?></p>
                        </div>
                        <div class="profile-item">
                            <strong>Department:</strong>
                            <p><?php echo htmlspecialchars($user['department_name']); ?></p>
                        </div>
                        <div class="profile-item">
                            <strong>Position:</strong>
                            <p><?php echo htmlspecialchars($user['position_name']); ?></p>
                        </div>
                        <div class="profile-item">
                            <strong>Designation:</strong>
                            <p><?php echo htmlspecialchars($user['designation_name'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="profile-item">
                            <strong>Section:</strong>
                            <p><?php echo htmlspecialchars($user["section_name"] ?? "N/A"); ?></p>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <h3>Account Information</h3>
                    <div class="profile-grid">
                        <div class="profile-item">
                            <strong>Password:</strong>
                            <p>********</p>
                        </div>
                        <div class="profile-item">
                            <strong>Last Login:</strong>
                            <p id="last-login"></p>
                        </div>

                        <script>
                            function updateDateTime() {
                                const now = new Date();
                                const options = {
                                    month: 'short',
                                    day: '2-digit',
                                    year: 'numeric'
                                };
                                const dateStr = now.toLocaleDateString('en-US', options);
                                const timeStr = now.toLocaleTimeString('en-US', {
                                    hour12: false
                                });
                                document.getElementById('last-login').textContent = `${dateStr} ${timeStr}`;
                            }

                            updateDateTime();
                            setInterval(updateDateTime, 1000);
                        </script>

                    </div>
                </div>
            </div>
        </div>


        <!-- Back Button -->
        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>

    <style>
        .profile-details {
            padding: 20px;
        }

        .profile-section {
            margin-bottom: 30px;
        }

        .profile-section h3 {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
            color: #2c3e50;
            position: relative;
            transition: all 0.3s ease;
        }

        .profile-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -1px;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #8A0000, #ff5e57);
            transition: width 0.3s ease;
        }

        .profile-section h3:hover::after {
            width: 100%;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .profile-item strong {
            display: block;
            margin-bottom: 5px;
            color: #7f8c8d;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-item p {
            font-size: 16px;
            margin: 0;
            color: #2c3e50;
        }

        .profile-picture {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-picture img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #C83F12;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
        }

        .profile-picture img:hover {
            transform: scale(1.1) rotate(1deg);
            box-shadow: 0 8px 20px rgba(138, 0, 0, 0.25);
        }

        .profile-picture p {
            font-weight: bold;
            margin-top: 12px;
            font-size: 18px;
            color: #34495e;
        }

        .btn-primary {
            background: linear-gradient(to right, #8A0000, #ff5e57);
            color: #fff;
            padding: 10px 24px;
            font-size: 15px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(255, 94, 87, 0.4);
        }

        .back-btn {
            text-align: center;
            margin-top: 30px;
        }
    </style>

</body>

</html>