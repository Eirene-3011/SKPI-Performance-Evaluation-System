<?php
// Make sure you have installed PHPMailer via Composer
// composer require phpmailer/phpmailer
require_once 'database_functions_enhanced.php';
require 'vendor/autoload.php';  // Adjust path if needed

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Replace these with your actual Gmail credentials
define('GMAIL_USERNAME', 'main.raileyandrei.acosta@cvsu.edu.ph');         // Your Gmail address
define('GMAIL_APP_PASSWORD', 'odsu lpri ulks jgmo');  // Gmail app password, NOT your normal password

// Database connection assumed globally available as $conn
// Example: $conn = new mysqli('localhost', 'user', 'password', 'database');

function sendEmail($to, $subject, $message, $from = 'hr@seiwakaiun.com.ph') {
    $mail = new PHPMailer(true);
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'main.raileyandrei.acosta@cvsu.edu.ph';
        $mail->Password   = 'odsu lpri ulks jgmo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS encryption
        $mail->Port       = 587;

        // Email headers and body
        $mail->setFrom($from, 'Performance Evaluation System');
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
        return false;
    }
}

function send_email_notification($evaluation_id) {
    global $conn;

    $evaluation = getEvaluationById($evaluation_id);
    if (!$evaluation) return false;

    // Get employee details to find designation_id
    $employee = getEmployeeById($evaluation['employee_id']);
    if (!$employee) return false;

    // Get specific HR evaluator by designation_id
    $hr_evaluator = getHREvaluatorByDesignation($employee['designation_id']);
    if (!$hr_evaluator || empty($hr_evaluator['email'])) {
        return false;
    }

    $subject = "New Performance Evaluation Created - " . $evaluation['fullname'];
    $message = "Dear " . $hr_evaluator['fullname'] . ",\n\n";
    $message .= "A new performance evaluation has been created and requires your attention.\n\n";
    $message .= "Employee: " . $evaluation['fullname'] . "\n";
    $message .= "Employee ID: " . $evaluation['card_no'] . "\n";
    $message .= "Department: " . $evaluation['department_name'] . "\n";
    $message .= "Position: " . $evaluation['position_name'] . "\n";
    $message .= "Evaluation Reason: " . $evaluation['evaluation_reason'] . "\n";
    $message .= "Period Covered: " . date('M d, Y', strtotime($evaluation['period_covered_from'])) . " to " . date('M d, Y', strtotime($evaluation['period_covered_to'])) . "\n\n";
    $message .= "Please log in to the Performance Evaluation System to begin the evaluation process.\n\n";
    $message .= "Thank you,\nPerformance Evaluation System";

    return sendEmail($hr_evaluator['email'], $subject, $message);
}

// Example usage:
// Initialize your $conn connection here before using
// $conn = new mysqli('localhost', 'username', 'password', 'database');
// Check connection...

// Call to send notification for a specific evaluation id
// $success = send_email_notification(123);
// echo $success ? "Emails sent." : "Failed to send emails.";