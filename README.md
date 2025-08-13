# Performance Evaluation System for Seiwa Kaiun Philippines Inc.

## Overview

A comprehensive web-based performance evaluation system designed to automate the multi-level employee evaluation process at Seiwa Kaiun Philippines Inc. (SKPI). The system implements a structured workflow with role-based access control and automated notifications.

## Features

### Core Functionality
- **Multi-Level Evaluation Workflow**: Sequential evaluation by HR, Shift Leader, Supervisor, and Manager
- **Role-Based Access Control**: Secure access based on user roles and permissions
- **Automated Employee Data**: Auto-populated employee information from company database
- **Comprehensive Reporting**: Detailed evaluation reports with scoring and comments
- **Email Notifications**: Automated workflow notifications for evaluators and employees
- **Responsive Design**: Mobile-friendly interface for all devices

### Evaluation Criteria
1. **Work Quality** - Standard and accuracy of work performed
2. **Work Quantity** - Amount of work completed within timeframes
3. **Work Habits** - Work behavior, organization, and professional conduct
4. **Attendance and Punctuality** - Attendance record and punctuality (HR only)
5. **Personality/Attitude** - Attitude toward work, colleagues, and company values

### User Roles
- **HR Supervisor**: Create evaluations, evaluate attendance criteria, full system access
- **Manager**: Evaluate work quality, quantity, habits, and personality criteria
- **Supervisor**: Evaluate work quality, quantity, habits, and personality criteria
- **Shift Leader**: Evaluate work quality, quantity, habits, and personality criteria
- **Staff**: View own evaluation results and reports

## Technical Specifications

### Requirements
- **Web Server**: Apache 2.4+
- **PHP**: 8.1+
- **Database**: MySQL 8.0+
- **Operating System**: Windows (XAMPP recommended)
- **Browser**: Chrome, Firefox, Safari, Edge (latest versions)

### Technology Stack
- **Backend**: PHP with MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Database**: MySQL with prepared statements
- **Security**: Password hashing, session management, SQL injection prevention
- **Email**: PHP mail system with SMTP support

## Installation

### Quick Start (Windows)
1. Download and install XAMPP
2. Extract application files to `C:\xampp\htdocs\performance_evaluation_system\`
3. Start Apache and MySQL services
4. Configure MySQL password
5. Run database setup: `http://localhost/performance_evaluation_system/create_tables.php`
6. Import data: `http://localhost/performance_evaluation_system/import_csv_fixed.php`
7. Access system: `http://localhost/performance_evaluation_system/`

### Detailed Installation
See `Complete_Deployment_Guide.md` for comprehensive installation instructions.

## File Structure

```
performance_evaluation_system/
├── index.php                    # Main entry point
├── config.php                   # Database configuration
├── auth.php                     # Authentication system
├── database_functions.php       # Database operations
├── email_functions.php          # Email notifications
├── login.php                    # Login interface
├── dashboard.php                # Main dashboard
├── create_evaluation.php        # Evaluation creation
├── evaluate.php                 # Evaluation form
├── view_evaluation.php          # Results viewer
├── create_tables.php            # Database setup
├── import_csv_fixed.php         # Data import
├── assets/
│   ├── style.css               # Main stylesheet
│   └── seiwa.logo.png          # Company logo
├── *.csv                       # Employee data files
└── documentation/
    ├── Complete_Deployment_Guide.md
    ├── User_Manual.md
    ├── Testing_Documentation.md
    └── Windows_Setup_Instructions.md
```

## Database Schema

### Core Tables
- **employees**: Employee information and credentials
- **emp_department**: Department master data
- **emp_positions**: Position master data
- **emp_sections**: Section master data
- **emp_roles**: User role definitions
- **evaluations**: Evaluation records
- **evaluation_criteria**: Evaluation criteria definitions
- **evaluation_responses**: Individual evaluator responses
- **evaluation_workflow**: Workflow status tracking

## Usage

### For HR Users
1. Login with admin credentials
2. Create new evaluations for employees
3. Complete attendance and punctuality evaluation
4. Monitor evaluation progress
5. Generate reports

### For Evaluators (Managers, Supervisors, Shift Leaders)
1. Login with employee credentials
2. Access pending evaluations
3. Complete assigned evaluation criteria
4. Submit evaluations to continue workflow

### For Staff
1. Login with employee credentials
2. View evaluation results
3. Download evaluation reports
4. Track evaluation history

## Default Test Accounts

| Employee ID | Role | Initial Password | Permissions |
|-------------|------|------------------|-------------|
| admin | HR Supervisor | admin | Full system access |
| 2021-001 | Staff | 2021-001 | View own evaluations |
| 2021-003 | Manager | 2021-003 | Evaluate employees |
| 2021-004 | Manager | 2021-004 | Evaluate employees |

## Security Features

- **Password Hashing**: Secure password storage using PHP password_hash()
- **Session Management**: Secure session handling with timeout
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Input sanitization and output encoding
- **Role-Based Access**: Strict permission checking for all functions
- **CSRF Protection**: Form token validation (recommended for production)

## Email Notifications

The system includes automated email notifications for:
- New evaluation assignments
- Evaluation completion alerts
- Workflow progression updates
- Final evaluation completion

**Note**: Email system is configured for development (file logging). Production deployment requires SMTP configuration.

## Documentation

### Available Documentation
- **Complete_Deployment_Guide.md**: Comprehensive installation and setup guide
- **User_Manual.md**: End-user instructions and best practices
- **Testing_Documentation.md**: System testing results and validation
- **Windows_Setup_Instructions.md**: Windows-specific setup instructions

## Support and Maintenance

### Regular Maintenance
- Database backups (recommended daily)
- System updates and security patches
- User account management
- Performance monitoring

### Troubleshooting
Common issues and solutions are documented in the deployment guide. For additional support:
- Check documentation files
- Review error logs
- Contact system administrator

## Development Notes

### Code Standards
- PHP 8.1+ compatibility
- Prepared statements for database queries
- Responsive CSS design
- Clean, documented code structure

### Security Considerations
- Input validation on all forms
- Output encoding for XSS prevention
- Secure session configuration
- Regular security updates

### Performance Optimization
- Efficient database queries with proper indexing
- Optimized CSS and JavaScript
- Image optimization
- Caching strategies for production

## License and Copyright

This system is developed specifically for Seiwa Kaiun Philippines Inc. All rights reserved.

## Version Information

- **Version**: 1.0.0
- **Release Date**: July 16, 2025
- **PHP Version**: 8.1+
- **MySQL Version**: 8.0+
- **Last Updated**: July 16, 2025

## Contact Information

For technical support or questions:
- **HR Department**: hr@seiwakaiun.com.ph
- **System Administrator**: Contact your IT department

---

**Thank you for using the Performance Evaluation System!**

