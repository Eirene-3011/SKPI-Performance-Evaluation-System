# Performance Evaluation System - User Manual

## Table of Contents
1. [System Overview](#system-overview)
2. [Getting Started](#getting-started)
3. [User Roles and Permissions](#user-roles-and-permissions)
4. [Login Process](#login-process)
5. [Dashboard Navigation](#dashboard-navigation)
6. [Creating Evaluations (HR)](#creating-evaluations-hr)
7. [Conducting Evaluations](#conducting-evaluations)
8. [Viewing Evaluation Results](#viewing-evaluation-results)
9. [Troubleshooting](#troubleshooting)

## System Overview

The Performance Evaluation System is a web-based application designed to automate the multi-level employee evaluation process at Seiwa Kaiun Philippines Inc. (SKPI). The system ensures a structured evaluation workflow while maintaining transparency and security.

### Key Features
- **Automated Evaluation Workflow**: Sequential evaluation by HR, Shift Leader, Supervisor, and Manager
- **Role-Based Access Control**: Users can only access functions relevant to their role
- **Auto-filled Employee Data**: Employee information is automatically populated from the database
- **Comprehensive Reporting**: Detailed evaluation reports with scoring and comments
- **Email Notifications**: Automated notifications for evaluation steps
- **Secure Authentication**: Password-protected access with role verification

## Getting Started

### System Requirements
- **Web Browser**: Chrome, Firefox, Safari, or Edge (latest versions)
- **Internet Connection**: Required for accessing the system
- **Employee ID**: Your company-assigned employee identification number

### First-Time Access
1. Open your web browser
2. Navigate to the system URL provided by your IT department
3. Use your Employee ID for both username and password on first login
4. Set up a secure password when prompted

## User Roles and Permissions

### 1. HR Supervisor (Role ID: 1)
**Permissions:**
- Create new evaluations
- Evaluate Criteria IV (Attendance and Punctuality)
- View all employee data and evaluations
- Manage system settings
- Access administrative functions

### 2. Manager (Role ID: 2)
**Permissions:**
- Evaluate Criteria I, II, III, V (Work Quality, Quantity, Habits, Personality)
- View evaluations they participated in
- Access employee information for evaluation purposes

### 3. Supervisor (Role ID: 3)
**Permissions:**
- Evaluate Criteria I, II, III, V (Work Quality, Quantity, Habits, Personality)
- View evaluations they participated in
- Access employee information for evaluation purposes

### 4. Shift Leader (Role ID: 4)
**Permissions:**
- Evaluate Criteria I, II, III, V (Work Quality, Quantity, Habits, Personality)
- View evaluations they participated in
- Access employee information for evaluation purposes

### 5. Staff (Role ID: 5)
**Permissions:**
- View their own evaluation results
- Access personal profile information
- Download evaluation reports

## Login Process

### First-Time Login
1. **Enter Employee ID**: Use your company employee ID (e.g., "admin", "2021-001")
2. **Enter Password**: Use the same Employee ID as your initial password
3. **Set New Password**: The system will prompt you to create a secure password
4. **Password Requirements**: Minimum 6 characters, confirm password must match

### Subsequent Logins
1. **Enter Employee ID**: Your company employee identification
2. **Enter Password**: The password you set during first login
3. **Click Login**: Access your dashboard

### Forgot Password
Contact your HR department for password reset assistance.

## Dashboard Navigation

### Dashboard Elements

#### Header Section
- **Company Logo**: Seiwa Kaiun Philippines Inc. branding
- **User Information**: Your name, role, and department
- **Logout Button**: Securely exit the system

#### Statistics Cards
- **Total Employees**: (HR only) Number of employees in system
- **My Evaluations**: Number of evaluations for your record
- **Completed**: Number of completed evaluations
- **Pending Reviews**: (Evaluators only) Evaluations awaiting your input

#### Quick Actions
- **Create New Evaluation**: (HR only) Start a new evaluation process
- **Review Pending Evaluations**: (Evaluators) Access evaluations requiring your input
- **View My Evaluations**: See your evaluation history
- **My Profile**: View personal information

#### Recent Activity
- **Recent Evaluations**: List of your most recent evaluations
- **Pending Evaluations**: (Evaluators) Evaluations requiring your attention

## Creating Evaluations (HR)

### Step-by-Step Process

#### 1. Access Creation Form
- Click "Create New Evaluation" from dashboard
- Navigate to the evaluation creation page

#### 2. Select Employee
- Choose employee from dropdown list
- Employee information will display automatically
- Verify employee details are correct

#### 3. Set Evaluation Parameters
- **Evaluation Reason**: Select from dropdown
  - Semi-Annual
  - Promotion
  - Regularization
  - Annual
  - Special
- **Period Covered From**: Start date of evaluation period
- **Period Covered To**: End date of evaluation period

#### 4. Review and Submit
- Verify all information is correct
- Click "Create Evaluation"
- System will create evaluation and notify evaluators

### Employee Information Auto-Fill
The system automatically populates:
- Full Name
- Employee Number
- Date Hired
- Position
- Department
- Section

## Conducting Evaluations

### Evaluation Workflow
1. **HR Supervisor**: Evaluates Attendance and Punctuality (Criteria IV)
2. **Shift Leader**: Evaluates Work Quality, Quantity, Habits, Personality (Criteria I, II, III, V)
3. **Supervisor**: Evaluates Work Quality, Quantity, Habits, Personality (Criteria I, II, III, V)
4. **Manager**: Evaluates Work Quality, Quantity, Habits, Personality (Criteria I, II, III, V)

### Evaluation Process

#### 1. Access Evaluation
- Click "Evaluate" button from pending evaluations
- Review employee information displayed

#### 2. Understand Your Role
- View evaluation instructions
- Note which criteria you can evaluate
- Read-only criteria are shown for reference

#### 3. Rate Performance
- **Rating Scale**: 1-5 (1 = Poor, 5 = Excellent)
- Click on rating number to select
- Rating descriptions:
  - 1: Poor
  - 2: Fair
  - 3: Good
  - 4: Very Good
  - 5: Excellent

#### 4. Provide Comments
- Comments are required for all criteria you evaluate
- Be specific and constructive
- Focus on observable behaviors and results
- Provide examples when possible

#### 5. Save and Submit
- **Save Progress**: Save your work to continue later
- **Submit Evaluation**: Complete and submit your evaluation
- Confirmation required before final submission

### Evaluation Criteria

#### I. Work Quality
Evaluate the standard and accuracy of work performed

#### II. Work Quantity
Assess the amount of work completed within given timeframes

#### III. Work Habits
Review work behavior, organization, and professional conduct

#### IV. Attendance and Punctuality (HR Only)
Evaluate attendance record and punctuality

#### V. Personality / Attitude
Assess attitude toward work, colleagues, and company values

## Viewing Evaluation Results

### Accessing Your Evaluations
1. Click "View My Evaluations" from dashboard
2. Select specific evaluation to view details
3. Review comprehensive evaluation report

### Evaluation Report Contents

#### Employee Information Section
- Personal and employment details
- Evaluation parameters and dates
- Current status

#### Evaluation Summary (Completed Evaluations)
- Average scores per criteria
- Overall rating
- Performance level indicators

#### Detailed Results
- Individual evaluator scores
- Comments from each evaluator
- Criteria-by-criteria breakdown

#### Workflow Status
- Evaluation progress tracking
- Completed steps and dates
- Pending evaluator information

### Print and Download Options
- **Print Report**: Use browser print function
- **Download PDF**: (Feature available for completed evaluations)
- **Save for Records**: Keep personal copies as needed

## Troubleshooting

### Common Issues and Solutions

#### Login Problems
**Issue**: Cannot login with Employee ID
**Solution**: 
- Verify Employee ID is correct
- For first-time login, use Employee ID as password
- Contact HR if account is not found

**Issue**: Password not accepted
**Solution**:
- Ensure password was set correctly during first login
- Check for caps lock or typing errors
- Contact HR for password reset

#### Access Denied Errors
**Issue**: Cannot access certain features
**Solution**:
- Verify your role permissions
- Some features are role-specific
- Contact HR if you believe you should have access

#### Evaluation Issues
**Issue**: Cannot submit evaluation
**Solution**:
- Ensure all required fields are completed
- Check that comments are provided for all criteria
- Verify scores are within 1-5 range

**Issue**: Evaluation not appearing in pending list
**Solution**:
- Check if previous evaluator has completed their step
- Verify you are assigned to evaluate this employee
- Contact HR if evaluation should be available

#### Technical Issues
**Issue**: Page not loading properly
**Solution**:
- Refresh the browser page
- Clear browser cache and cookies
- Try a different browser
- Check internet connection

**Issue**: System running slowly
**Solution**:
- Close unnecessary browser tabs
- Check internet connection speed
- Try accessing during off-peak hours

### Getting Help

#### Contact Information
- **HR Department**: hr@seiwakaiun.com.ph
- **IT Support**: Contact your local IT administrator
- **System Administrator**: Available during business hours

#### Before Contacting Support
1. Note the exact error message (if any)
2. Record what you were trying to do when the issue occurred
3. Try the basic troubleshooting steps above
4. Have your Employee ID ready

### Best Practices

#### For All Users
- **Logout Properly**: Always use the logout button when finished
- **Keep Passwords Secure**: Don't share your login credentials
- **Regular Access**: Check the system regularly for pending tasks
- **Browser Updates**: Keep your browser updated for best performance

#### For Evaluators
- **Timely Completion**: Complete evaluations promptly to avoid delays
- **Constructive Feedback**: Provide helpful, specific comments
- **Fair Assessment**: Base ratings on actual performance and behavior
- **Documentation**: Keep notes during evaluation period for reference

#### For HR Users
- **Regular Monitoring**: Check evaluation progress regularly
- **Clear Communication**: Ensure employees understand the process
- **Data Accuracy**: Verify employee information is current
- **System Maintenance**: Coordinate with IT for system updates

## System Updates and Maintenance

### Scheduled Maintenance
- System maintenance is typically scheduled during off-hours
- Users will be notified in advance of any planned downtime
- Backup systems ensure data protection

### Feature Updates
- New features and improvements are added regularly
- Users will be notified of significant changes
- Training may be provided for major updates

### Data Backup
- Employee data and evaluations are backed up regularly
- Historical evaluation data is preserved
- Contact HR for data recovery requests if needed

---

**For additional support or questions about the Performance Evaluation System, please contact the HR Department at hr@seiwakaiun.com.ph**

