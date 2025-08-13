<?php
require_once 'config.php';

function importCSV($filename, $table_name, $conn, $column_map = [])
{
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Get CSV header

        // Clean and optionally map header
        $header = array_map(function ($col) use ($column_map) {
            $col = trim($col, '"');
            return $column_map[$col] ?? $col; // map if defined, else use as is
        }, $header);

        // Escape columns
        $escaped_columns = implode(',', array_map(fn($col) => "`$col`", $header));
        $placeholders = str_repeat('?,', count($header) - 1) . '?';

        $sql = "INSERT IGNORE INTO `$table_name` ($escaped_columns) VALUES ($placeholders)";
        echo "Preparing SQL: $sql<br>"; // Debug output
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("❌ SQL Prepare failed: " . $conn->error . "<br>SQL: $sql");
        }


        $row_count = 0;
        while (($data = fgetcsv($handle)) !== FALSE) {
            $data = array_map(function ($value) {
                $value = trim($value, '"');
                return ($value === '\\N' || $value === '') ? null : $value;
            }, $data);

            // Dynamically bind the parameters
            $types = str_repeat('s', count($data));  // Assuming all data are strings; you can adjust this to match types (e.g., 'i' for integers)
            $stmt->bind_param($types, ...$data);  // Bind data to statement

            $stmt->execute();  // Execute the prepared statement
            $row_count++;
        }

        fclose($handle);
        echo "Imported $row_count rows into $table_name<br>";
        return true;
    } else {
        echo "Error opening file: $filename<br>";
        return false;
    }
}


// CSV import list with optional column mapping
$csv_files = [
    'employees.csv' => [
        'table' => 'employees',
        'mapping' => []
    ],
    'emp_department.csv' => [
        'table' => 'emp_department',
        'mapping' => []
    ],
    'emp_positions.csv' => [
        'table' => 'emp_positions',
        'mapping' => []
    ],
    'emp_sections.csv' => [
        'table' => 'emp_sections',
        'mapping' => []
    ],
    'emp_roles.csv' => [
        'table' => 'emp_roles',
        'mapping' => []
    ],
    'emp_designation.csv' => [
        'table' => 'emp_designation',
        'mapping' => []
    ]
];


// Process each file
foreach ($csv_files as $file => $info) {
    if (file_exists($file)) {
        $table_name = $info['table'];
        $mapping = $info['mapping'] ?? [];

        echo "Importing $file into $table_name...<br>";
        importCSV($file, $table_name, $conn, $mapping);
    } else {
        echo "File not found: $file<br>";
    }
}

$conn->close();
echo "<br>CSV import completed!";