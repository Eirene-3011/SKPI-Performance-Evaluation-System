<?php
require_once 'config.php';
require_once 'database_functions_enhanced.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $evaluation_id = $_GET['id'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Delete related records from evaluation_responses
        $stmt_responses = $conn->prepare("DELETE FROM evaluation_responses WHERE evaluation_id = ?");
        $stmt_responses->bind_param("i", $evaluation_id);
        $stmt_responses->execute();

        // Delete related records from evaluation_workflow
        $stmt_workflow = $conn->prepare("DELETE FROM evaluation_workflow WHERE evaluation_id = ?");
        $stmt_workflow->bind_param("i", $evaluation_id);
        $stmt_workflow->execute();

        // Delete the evaluation record
        $stmt_evaluation = $conn->prepare("DELETE FROM evaluations WHERE id = ?");
        $stmt_evaluation->bind_param("i", $evaluation_id);
        $stmt_evaluation->execute();

        // Commit the transaction
        $conn->commit();

        echo "Evaluation and related data deleted successfully.";
    } catch (mysqli_sql_exception $exception) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Error deleting evaluation: " . $exception->getMessage();
    }

    $conn->close();
    header('Location: admin_view_evaluations.php'); // Redirect back to the evaluations list
    exit();
} else {
    echo "Invalid evaluation ID.";
}
