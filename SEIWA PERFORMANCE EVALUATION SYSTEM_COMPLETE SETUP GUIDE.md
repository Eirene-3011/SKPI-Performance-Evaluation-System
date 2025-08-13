# COMPLETE SETUP GUIDE - Enhanced Performance Evaluation System

## 🚀 QUICK START CHECKLIST

**✅ MUST DO - File Renaming Required:**
- [ ] Install XAMPP
- [ ] Create database
- [ ] Run SQL migration script
- [ ] **RENAME 4 enhanced files** (critical step)
- [ ] Test the system

---

## 📋 STEP-BY-STEP COMPLETE SETUP

### STEP 1: Install XAMPP

1. **Download XAMPP**
   - Go to: https://www.apachefriends.org/download.html
   - Download PHP 8.1 version for Windows
   - Install to `C:\xampp` (default)

2. **Start Services**
   - Open XAMPP Control Panel as Administrator
   - Start **Apache** service
   - Start **MySQL** service
   - Both should show green status

### STEP 2: Create Project Directory

```cmd
# Open Command Prompt
cd C:\xampp\htdocs
mkdir performance_evaluation_system
cd performance_evaluation_system
```

### STEP 3: Extract Files
- Extract ALL files from the enhanced ZIP to: `C:\xampp\htdocs\performance_evaluation_system\`

### STEP 4: Database Setup

#### 4.1 Set MySQL Password
```cmd
# Open Command Prompt as Administrator
cd C:\xampp\mysql\bin
mysql -u root
```

```sql
-- In MySQL prompt, run these commands:
ALTER USER 'root'@'localhost' IDENTIFIED BY 'password123';
FLUSH PRIVILEGES;
EXIT;
```

#### 4.2 Update Config File
Edit `C:\xampp\htdocs\performance_evaluation_system\config.php`:
```php
$password = "password123";  // Make sure this matches your MySQL password
```

#### 4.3 Create Database and Tables
1. Open browser: `http://localhost/performance_evaluation_system/create_tables.php`
2. You should see success messages

#### 4.4 Import CSV Data
1. Open browser: `http://localhost/performance_evaluation_system/import_csv.php`
2. You should see success messages

### STEP 5: 🔥 CRITICAL - Run Database Migration (Enhanced Features)

**Option A: Using phpMyAdmin (Recommended)**
1. Open: `http://localhost/phpmyadmin/`
2. Click on `performance_evaluation_db` database
3. Click "SQL" tab
4. Copy and paste the ENTIRE contents of `database_migration.sql` file
5. Click "Go"
6. You should see multiple success messages

**Option B: Using Command Line**
```cmd
cd C:\xampp\mysql\bin
mysql -u root -p performance_evaluation_db < "C:\xampp\htdocs\performance_evaluation_system\database_migration.sql"
```

### STEP 6: 🔥 CRITICAL - File Renaming (Enhanced Features)

**YOU MUST RENAME THESE 4 FILES:**

```cmd
# Navigate to project directory
cd C:\xampp\htdocs\performance_evaluation_system

# BACKUP original files first
copy admin_create_evaluation.php admin_create_evaluation_original.php
copy database_functions.php database_functions_original.php
copy evaluate.php evaluate_original.php
copy admin_view_evaluation.php admin_view_evaluation_original.php

# REPLACE with enhanced versions
copy admin_create_evaluation_enhanced.php admin_create_evaluation.php
copy database_functions_enhanced.php database_functions.php
copy evaluate_enhanced.php evaluate.php
copy admin_view_evaluation_enhanced.php admin_view_evaluation.php
```

**Manual Method (if command line doesn't work):**
1. Rename `admin_create_evaluation.php` to `admin_create_evaluation_original.php`
2. Rename `admin_create_evaluation_enhanced.php` to `admin_create_evaluation.php`
3. Rename `database_functions.php` to `database_functions_original.php`
4. Rename `database_functions_enhanced.php` to `database_functions.php`
5. Rename `evaluate.php` to `evaluate_original.php`
6. Rename `evaluate_enhanced.php` to `evaluate.php`
7. Rename `admin_view_evaluation.php` to `admin_view_evaluation_original.php`
8. Rename `admin_view_evaluation_enhanced.php` to `admin_view_evaluation.php`

### STEP 7: Verify Installation

Open browser: `http://localhost/performance_evaluation_system/test_enhanced_features_no_db.php`

**You should see ALL GREEN CHECKMARKS (✓)**
- If you see RED X marks (✗), something went wrong

---

## 📊 COMPLETE SQL MIGRATION SCRIPT

**Copy and paste this ENTIRE script into phpMyAdmin SQL tab:**

```sql
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
('3-Month Evaluation');

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
```

---

## 🧪 TESTING THE ENHANCED SYSTEM

### Test 1: Access the System
1. Open browser: `http://localhost/performance_evaluation_system/`
2. Login with:
   - Username: `admin`
   - Password: `admin`

### Test 2: Test Role Selection (NEW FEATURE)
1. Go to "Create New Evaluation"
2. **NEW**: You should see "Select Role for Evaluation" dropdown
3. Select different roles and watch employee list change

### Test 3: Test Staff Additional Fields (NEW FEATURE)
1. Select "Staff" as role
2. Choose any staff member
3. **NEW**: Additional fields should appear:
   - Attendance & Punctuality section
   - Number of Violations section
   - Suspensions section

### Test 4: Test Updated Evaluation Reasons (NEW FEATURE)
**NEW**: "Reason for Evaluation" should show:
- Semi-Annual
- For Promotion
- Regularization

### Test 5: Test Recommendation Field (NEW FEATURE)
1. Create an evaluation
2. Login as evaluator
3. **NEW**: "Recommendation" section should appear with 4 options

---

## 🔧 VERIFICATION COMMANDS

### Check Database Migration Success:
```sql
-- Open phpMyAdmin and run these queries:

-- Check if new columns exist in evaluations table
DESCRIBE evaluations;
-- Should show: approved_leaves, disapproved_leaves, tardiness, etc.

-- Check if recommendation column exists
DESCRIBE evaluation_responses;
-- Should show: recommendation VARCHAR(50)

-- Check if new tables exist
SHOW TABLES LIKE 'evaluation_workflows_by_role';
SHOW TABLES LIKE 'evaluation_reasons';

-- Check workflow data
SELECT * FROM evaluation_workflows_by_role;
```

---

## ❌ TROUBLESHOOTING

### Problem: "Role selection dropdown not working"
**Solution:**
```cmd
# Check if enhanced files were renamed correctly
cd C:\xampp\htdocs\performance_evaluation_system
# Verify admin_create_evaluation.php is the enhanced version
```

### Problem: "Additional fields not appearing for Staff"
**Solution:**
```sql
-- Check if database migration completed
DESCRIBE evaluations;
-- Should show new columns like approved_leaves, etc.
```

### Problem: "Recommendation field not saving"
**Solution:**
```sql
-- Check if recommendation column exists
DESCRIBE evaluation_responses;
-- Should show: recommendation VARCHAR(50)
```

### Problem: "Database migration failed"
**Solution:**
```sql
-- Run migration commands one by one in phpMyAdmin
-- Check for error messages
-- Ensure database name is correct
```

---

## 🔄 ROLLBACK PROCEDURE (If Something Goes Wrong)

### Rollback Database:
```sql
-- Remove added columns (if needed)
ALTER TABLE evaluations 
DROP COLUMN approved_leaves,
DROP COLUMN disapproved_leaves,
DROP COLUMN tardiness,
DROP COLUMN late_undertime,
DROP COLUMN offense_1st,
DROP COLUMN offense_2nd,
DROP COLUMN offense_3rd,
DROP COLUMN offense_4th,
DROP COLUMN offense_5th,
DROP COLUMN suspension_days;

ALTER TABLE evaluation_responses 
DROP COLUMN recommendation;

-- Drop new tables (if needed)
DROP TABLE IF EXISTS evaluation_workflows_by_role;
DROP TABLE IF EXISTS evaluation_reasons;
```

---
STEP 8: Add Admin Accounts (HR and MIS)
Paste the following INSERT queries into phpMyAdmin or run from your SQL client:

-- HR-ADMIN ACCOUNT
```sql
INSERT INTO employees (
   id, card_no, firstname, middlename, lastname, suffixname,
   fullname, fullname2, fullname3,
   department_id, position_id, designation_id, section_id, shift_id, role_id,
   birthdate, birthplace, gender, civil_status, with_child,
   profile_img, contact, address, email, work_email, hired_date, agency, employment_status,
   gov_sss, gov_pagibig, gov_philhealth, gov_tin,
   incase_name, incase_relationship, incase_contact, incase_address,
   resign_flag, resign_date, resign_reason, is_inactive,
   created_date, updated_date
) VALUES (
   671, 'HR-ADMIN', 'Analyn', 'Agomez', 'Hagos', NULL,
   'Hagos, Analyn Agomez', 'Hagos, Analyn', 'Analyn Hagos',
   3, 18, 1, NULL, NULL, 0,
   NULL, NULL, NULL, NULL, 0,
   '/uploads/profile-img/client-102.png', NULL, 'B25 L12 Don Bosco Executive Village Brgy Cabuco Trece Martires Cavite',
   NULL, NULL, '2021-11-11', 1, 3,
   NULL, NULL, NULL, NULL,
   NULL, NULL, NULL, NULL,
   0, NULL, NULL, 0,
   '2023-05-20 16:52:00', '2025-07-16 15:43:00'
   );

   UPDATE employees
   SET profile_img = 'http://192.168.112.248:8083/uploads/profile-img/2024/09/hagos.jpg'
   WHERE card_no = 'HR-ADMIN';

```

-- MIS-ADMIN ACCOUNT
```sql
INSERT INTO employees (
   id, card_no, firstname, middlename, lastname, suffixname,
   fullname, fullname2, fullname3,
   department_id, position_id, designation_id, section_id, shift_id, role_id,
   birthdate, birthplace, gender, civil_status, with_child,
   profile_img, contact, address, email, work_email, hired_date, agency, employment_status,
   gov_sss, gov_pagibig, gov_philhealth, gov_tin,
   incase_name, incase_relationship, incase_contact, incase_address,
   resign_flag, resign_date, resign_reason, is_inactive,
   created_date, updated_date
) VALUES (
   672, 'MIS-ADMIN', 'Rodante', 'Regis', 'Reyes', NULL,
   'Reyes, Rodante Regis', 'Reyes, Rodante', 'Rodante Reyes',
   4, 26, 1, NULL, NULL, 0,
   NULL, NULL, NULL, NULL, 0,
   '/uploads/profile-img/client-58.png', NULL, 'Blk 30 Lot 8 Beverly Homes Subdivision Hugo Perez Trece Martires Cavite',
   NULL, 'dan.reyes@seiwakaiun.com.ph', '2021-08-01', 1, 3,
   NULL, NULL, NULL, NULL,
   NULL, NULL, NULL, NULL,
   0, NULL, NULL, 0,
   '2023-05-20 16:52:00', '2025-07-16 15:43:00'
);

UPDATE employees
SET profile_img = 'http://192.168.112.248:8083/uploads/profile-img/2023/09/client-58.png'
WHERE card_no = 'MIS-ADMIN';

```
 -- Default login credentials: -- 

HR-ADMIN
Username: HR-ADMIN
Password: hradmin1234

MIS-ADMIN
Username: MIS-ADMIN
Password: misadmin1234

Let me know if you'd like this exported as a .txt file.
## 📁 FINAL FILE STRUCTURE

After successful setup, your directory should look like this:

```
C:\xampp\htdocs\performance_evaluation_system\
├── admin_create_evaluation.php (ENHANCED VERSION)
├── admin_create_evaluation_enhanced.php (source)
├── admin_create_evaluation_original.php (backup)
├── database_functions.php (ENHANCED VERSION)
├── database_functions_enhanced.php (source)
├── database_functions_original.php (backup)
├── evaluate.php (ENHANCED VERSION)
├── evaluate_enhanced.php (source)
├── evaluate_original.php (backup)
├── admin_view_evaluation.php (ENHANCED VERSION)
├── admin_view_evaluation_enhanced.php (source)
├── admin_view_evaluation_original.php (backup)
├── database_migration.sql
├── test_enhanced_features_no_db.php
├── [all other original files...]
```

---

## ✅ SUCCESS INDICATORS

**You know the setup worked when:**
1. ✅ `test_enhanced_features_no_db.php` shows all green checkmarks
2. ✅ Role selection dropdown appears in "Create New Evaluation"
3. ✅ Staff additional fields appear when Staff role is selected
4. ✅ Recommendation field appears in evaluation forms
5. ✅ Updated evaluation reasons show in dropdown

---

## 🆘 NEED HELP?

**If you're stuck:**
1. Run the test script: `http://localhost/performance_evaluation_system/test_enhanced_features_no_db.php`
2. Check which step shows red X marks
3. Review the corresponding section in this guide
4. Check XAMPP error logs in `C:\xampp\apache\logs\`

**This guide contains EVERYTHING you need to set up the enhanced system successfully!**

