-- Database schema updates for evaluation flow fixes

-- Add current_evaluator_id column to evaluations table if it doesn't exist
ALTER TABLE evaluations 
ADD COLUMN IF NOT EXISTS current_evaluator_id INT NULL,
ADD FOREIGN KEY (current_evaluator_id) REFERENCES employees(id);

-- Add evaluator_id column to evaluation_workflow table if it doesn't exist
ALTER TABLE evaluation_workflow 
ADD COLUMN IF NOT EXISTS evaluator_id INT NULL,
ADD FOREIGN KEY (evaluator_id) REFERENCES employees(id);

-- Create approvers table for storing admin-edited evaluators
CREATE TABLE IF NOT EXISTS approvers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    approver1_id INT NULL,
    approver2_id INT NULL,
    approver3_id INT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_employee (employee_id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (approver1_id) REFERENCES employees(id),
    FOREIGN KEY (approver2_id) REFERENCES employees(id),
    FOREIGN KEY (approver3_id) REFERENCES employees(id)
);

-- Add designation_id column to employees table if it doesn't exist
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS designation_id INT NULL DEFAULT 1;

