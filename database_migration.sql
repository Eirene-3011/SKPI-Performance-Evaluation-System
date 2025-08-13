-- Database Migration Script for Performance Evaluation System Enhancement
-- This script adds the necessary columns and tables to support the new features

-- 1. Add new columns to the 'evaluations' table for Staff-specific fields
ALTER TABLE evaluations
ADD COLUMN approved_leaves INT DEFAULT NULL COMMENT 'Number of approved leaves for Staff evaluations',
ADD COLUMN disapproved_leaves INT DEFAULT NULL COMMENT 'Number of disapproved leaves for Staff evaluations',
ADD COLUMN tardiness INT DEFAULT NULL COMMENT 'Number of tardiness instances for Staff evaluations',
ADD COLUMN late_undertime INT DEFAULT NULL COMMENT 'Number of late/undertime instances for Staff evaluations',
ADD COLUMN offense_1st INT DEFAULT NULL COMMENT 'Count of 1st offenses for Staff evaluations',
ADD COLUMN offense_2nd INT DEFAULT NULL COMMENT 'Count of 2nd offenses for Staff evaluations',
ADD COLUMN offense_3rd INT DEFAULT NULL COMMENT 'Count of 3rd offenses for Staff evaluations',
ADD COLUMN offense_4th INT DEFAULT NULL COMMENT 'Count of 4th offenses for Staff evaluations',
ADD COLUMN offense_5th INT DEFAULT NULL COMMENT 'Count of 5th offenses for Staff evaluations',
ADD COLUMN suspension_days INT DEFAULT NULL COMMENT 'Number of suspension days for Staff evaluations';

-- 2. Add new column to the 'evaluation_responses' table for Recommendation field
ALTER TABLE evaluation_responses
ADD COLUMN recommendation VARCHAR(50) DEFAULT NULL COMMENT 'Evaluator recommendation (For Probationary, For Continued Probation, For Regularization, Unsatisfactory)';

-- 3. Create the new 'evaluation_workflows_by_role' table (optional - for future use)
-- This table provides a flexible way to manage different evaluation workflows
CREATE TABLE IF NOT EXISTS evaluation_workflows_by_role (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_role_id INT NOT NULL COMMENT 'Foreign Key to emp_roles.id',
    evaluator_role_id INT NOT NULL COMMENT 'Foreign Key to emp_roles.id', 
    step_order INT NOT NULL COMMENT 'Order in which this evaluator participates',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Whether this workflow step is active',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_role_id) REFERENCES emp_roles(id),
    FOREIGN KEY (evaluator_role_id) REFERENCES emp_roles(id),
    UNIQUE KEY unique_workflow_step (employee_role_id, evaluator_role_id, step_order),
    INDEX idx_employee_role (employee_role_id),
    INDEX idx_evaluator_role (evaluator_role_id)
) COMMENT 'Defines evaluation workflows for different employee roles';

-- 4. Insert initial workflow data (if table was created)
-- Staff (role_id = 5): HR -> Shift Leader -> Supervisor -> Manager
INSERT IGNORE INTO evaluation_workflows_by_role (employee_role_id, evaluator_role_id, step_order) VALUES
(5, 1, 1), -- HR
(5, 4, 2), -- Shift Leader
(5, 3, 3), -- Supervisor
(5, 2, 4); -- Manager

-- Shift Leader (role_id = 4): HR -> Supervisor -> Manager
INSERT IGNORE INTO evaluation_workflows_by_role (employee_role_id, evaluator_role_id, step_order) VALUES
(4, 1, 1), -- HR
(4, 3, 2), -- Supervisor
(4, 2, 3); -- Manager

-- Supervisor (role_id = 3): HR -> Manager
INSERT IGNORE INTO evaluation_workflows_by_role (employee_role_id, evaluator_role_id, step_order) VALUES
(3, 1, 1), -- HR
(3, 2, 2); -- Manager

-- 5. Create the 'evaluation_reasons' table (optional - for better management)
CREATE TABLE IF NOT EXISTS evaluation_reasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reason_name VARCHAR(100) NOT NULL UNIQUE COMMENT 'Name of the evaluation reason',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Whether this reason is active',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT 'Manages evaluation reason options';

-- 6. Insert initial evaluation reasons
INSERT IGNORE INTO evaluation_reasons (reason_name) VALUES
('Semi-Annual'),
('For Promotion'),
('Regularization'),
('3- Month Evaluation');

-- 7. Add indexes for better performance on new columns
ALTER TABLE evaluations 
ADD INDEX idx_evaluations_employee_role (employee_id),
ADD INDEX idx_evaluations_status_role (status, current_evaluator_role_id);

ALTER TABLE evaluation_responses
ADD INDEX idx_responses_recommendation (recommendation);

-- 8. Update any existing evaluation_workflow_stages table if it exists
-- This ensures compatibility with the existing system
UPDATE evaluation_workflow_stages SET is_active = 1 WHERE is_active IS NULL;

-- Migration completed successfully
-- Note: This script uses IF NOT EXISTS and IGNORE to prevent errors if run multiple times

-- create the emp_designation table in your database first. -- 
CREATE TABLE `emp_designation` (
  `id` INT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `is_disabled` TINYINT(1) DEFAULT 0
);
