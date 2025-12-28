CRM System for Technical Sales - Architecture & Implementation Plan

🎯 System Overview

A robust CRM designed specifically for technical sales teams with integrated communication, funnel tracking, and performance analytics.

📋 Core Modules

1. Customer Management Module

2. Sales Funnel Management System

Four Funnel Categories with Automated Rules:

3. Lead Qualification Engine

4. Intelligent Email System with Templates

5. Dashboard & Analytics Module

🚀 Enhanced Features for Quota Achievement

6. Smart Follow-up & Reminder System

🏗️ Technical Architecture

Database Schema Highlights:

📊 Integration Points

8. Third-Party Integrations

🎨 User Interface Components

9. Dashboard Widgets

🔄 Workflow Automation

10. Automated Workflows

📈 Reporting Engine

11. Advanced Reporting

🚀 Implementation Roadmap

Phase 1 (Weeks 1-4): Foundation

Customer management core
Basic deal tracking
Simple email templates
Basic dashboard
Phase 2 (Weeks 5-8): Automation

Funnel management
Email automation
Follow-up system
Calendar integration
Phase 3 (Weeks 9-12): Intelligence

Lead scoring
Predictive analytics
Advanced reporting
AI recommendations
Phase 4 (Weeks 13-16): Optimization

Performance tuning
Mobile responsiveness
Advanced integrations
Team collaboration features

💡 Best Practices Implementation

API-First Design: RESTful APIs for all core functions
Progressive Web App: Offline capability for field sales
Real-time Updates: WebSocket for live dashboard updates
Data Encryption: End-to-end encryption for sensitive data
Audit Logging: Comprehensive activity tracking
Backup Strategy: Automated backups with point-in-time recovery
Scalability: Microservices-ready architecture
Testing Suite: Unit, integration, and E2E testing
🛠️ Technology Stack Recommendation

Backend: PHP 8.2+ with Laravel/Symfony
Frontend: Vue.js 3 or React with TypeScript
Database: PostgreSQL with JSON support
Cache: Redis for sessions and caching
Queue: Redis or RabbitMQ for background jobs
Search: ElasticSearch for customer/lead search
Storage: AWS S3 or similar for documents
Real-time: Laravel Echo with Pusher/Socket.io

🔒 Compliance Considerations

GDPR: Data portability, right to be forgotten
CAN-SPAM: Email compliance for templates
CCPA: California consumer privacy act
Data Retention: Automated data purge policies
Consent Management: Track marketing consent

This architecture provides a comprehensive, scalable solution specifically tailored for technical sales teams. The system balances automation with flexibility, provides deep insights while remaining user-friendly, and includes all necessary features to help sales teams achieve and exceed their quotas.

📁 Project Structure

technical_crm/
├── app/
│   ├── controllers/
│   ├── models/
│   ├── views/
│   └── core/
├── config/
├── public/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── uploads/
│   ├── templates/
│   └── documents/
└── database/
Step 1: Database Setup (database/crm.sql)

sql
-- Create Database
CREATE DATABASE IF NOT EXISTS technical_crm;
USE technical_crm;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('sales_rep', 'sales_manager', 'admin') DEFAULT 'sales_rep',
    avatar VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customers Table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    industry VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100),
    
    -- Technical Fields
    tech_stack TEXT,
    current_solutions TEXT,
    pain_points TEXT,
    budget_range VARCHAR(50),
    
    -- Tracking
    lead_source VARCHAR(100),
    customer_status ENUM('prospect', 'qualified', 'active', 'inactive', 'vip') DEFAULT 'prospect',
    assigned_to INT,
    notes TEXT,
    
    -- Dates
    last_contact DATE,
    next_followup DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Sales Funnel/Deals Table
CREATE TABLE deals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deal_code VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    deal_name VARCHAR(200) NOT NULL,
    description TEXT,
    deal_value DECIMAL(15,2) DEFAULT 0.00,
    
    -- Funnel Categories
    funnel_category ENUM('yellow', 'pink', 'green', 'blue') DEFAULT 'pink',
    
    -- Deal Status
    deal_status ENUM('new', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost') DEFAULT 'new',
    
    -- Probability
    probability INT DEFAULT 0, -- Percentage
    
    -- Dates
    expected_close DATE,
    quote_date DATE,
    closed_date DATE,
    
    -- Technical Details
    deal_type ENUM('product', 'project', 'service', 'support') DEFAULT 'product',
    requirements TEXT,
    competitors TEXT,
    
    -- Assigned To
    owner_id INT NOT NULL,
    
    -- Tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Leads Table
CREATE TABLE leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_code VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    company VARCHAR(200),
    email VARCHAR(100),
    phone VARCHAR(20),
    
    -- Qualification
    lead_score INT DEFAULT 0,
    lead_status ENUM('new', 'contacted', 'qualified', 'unqualified', 'converted') DEFAULT 'new',
    lead_source ENUM('website', 'referral', 'social', 'event', 'cold_call', 'email') DEFAULT 'website',
    
    -- Technical Assessment
    budget ENUM('low', 'medium', 'high', 'unknown') DEFAULT 'unknown',
    timeline ENUM('urgent', '1-3_months', '3-6_months', '6+_months', 'unknown') DEFAULT 'unknown',
    authority ENUM('decision_maker', 'influencer', 'end_user', 'unknown') DEFAULT 'unknown',
    need_level ENUM('high', 'medium', 'low', 'unknown') DEFAULT 'unknown',
    
    -- Notes & Assignment
    notes TEXT,
    assigned_to INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Email Templates Table
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL,
    template_slug VARCHAR(100) UNIQUE NOT NULL,
    subject VARCHAR(200) NOT NULL,
    content LONGTEXT NOT NULL,
    
    -- Categories
    category ENUM('proposal', 'followup', 'demo', 'quote', 'meeting', 'general') DEFAULT 'general',
    
    -- Variables placeholder
    variables TEXT, -- JSON format for available variables
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Activities Table
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_type ENUM('email', 'call', 'meeting', 'demo', 'proposal', 'followup', 'other') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT,
    
    -- Related Entities
    related_to ENUM('customer', 'deal', 'lead'),
    related_id INT,
    
    -- Dates
    activity_date DATETIME NOT NULL,
    reminder_date DATETIME,
    completed_date DATETIME,
    
    -- Status
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    outcome TEXT,
    
    -- Assigned
    assigned_to INT,
    created_by INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Email Logs Table
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT,
    recipient_email VARCHAR(100) NOT NULL,
    recipient_name VARCHAR(100),
    subject VARCHAR(200) NOT NULL,
    content LONGTEXT,
    
    -- Tracking
    sent_by INT,
    related_to ENUM('customer', 'deal', 'lead'),
    related_id INT,
    
    -- Email Status
    status ENUM('sent', 'delivered', 'opened', 'clicked', 'failed') DEFAULT 'sent',
    opened_at DATETIME,
    clicked_at DATETIME,
    
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Notes Table
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    
    -- Related Entities
    related_to ENUM('customer', 'deal', 'lead'),
    related_id INT,
    
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Follow-up Reminders Table
CREATE TABLE followups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deal_id INT NOT NULL,
    reminder_type ENUM('email', 'call', 'meeting', 'task') DEFAULT 'email',
    reminder_date DATE NOT NULL,
    reminder_time TIME,
    notes TEXT,
    
    -- Status
    status ENUM('pending', 'completed', 'snoozed', 'cancelled') DEFAULT 'pending',
    completed_at DATETIME,
    
    -- Assigned
    assigned_to INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Default Admin User
INSERT INTO users (username, email, password, first_name, last_name, role) 
VALUES ('admin', 'admin@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin');
-- Password: password

-- Insert Sample Email Templates
INSERT INTO email_templates (template_name, template_slug, subject, content, category, variables) VALUES
('Initial Follow-up', 'initial-followup', 'Following up on our conversation', '<p>Dear {customer_name},</p><p>I hope this email finds you well.</p><p>I wanted to follow up on our recent conversation about {deal_name}. I believe our solution could help you with {pain_points}.</p><p>Would you be available for a quick call next week?</p><p>Best regards,<br>{sales_rep_name}</p>', 'followup', '["customer_name", "deal_name", "pain_points", "sales_rep_name"]'),

('Technical Proposal', 'technical-proposal', 'Technical Proposal for {company_name}', '<p>Dear {contact_person},</p><p>As per our discussion, I''ve prepared a technical proposal for {project_name}.</p><p><strong>Key Features:</strong></p><ul><li>Feature 1</li><li>Feature 2</li></ul><p><strong>Technical Specifications:</strong></p><ul><li>Spec 1</li><li>Spec 2</li></ul><p>Please let me know if you have any questions.</p><p>Best regards,<br>{sales_rep_name}</p>', 'proposal', '["contact_person", "company_name", "project_name", "sales_rep_name"]'),

('Demo Invitation', 'demo-invitation', 'Invitation: Product Demo', '<p>Hello {customer_name},</p><p>I would like to invite you to a personalized demo of our solution.</p><p><strong>Date:</strong> {demo_date}<br><strong>Time:</strong> {demo_time}<br><strong>Duration:</strong> 30 minutes</p><p>Please confirm your availability.</p><p>Best regards,<br>{sales_rep_name}</p>', 'demo', '["customer_name", "demo_date", "demo_time", "sales_rep_name"]');
Step 2: Configuration File (config/database.php)

php
<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'technical_crm';
    private $username = 'root';
    private $password = 'root'; // Default MAMP password
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
Step 3: Core Classes (app/core/)

Auth Class (app/core/Auth.php)

php
<?php
// app/core/Auth.php
session_start();

class Auth {
    public static function login($username, $password) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT * FROM users WHERE username = :username OR email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
                return true;
            }
        }
        return false;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function logout() {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    public static function requireLogin() {
        if(!self::isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }
    
    public static function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] == $role;
    }
    
    public static function getUserID() {
        return $_SESSION['user_id'] ?? null;
    }
}
?>
Helper Functions (app/core/Helpers.php)

php
<?php
// app/core/Helpers.php
class Helpers {
    
    public static function escape($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    public static function generateCode($prefix) {
        return $prefix . strtoupper(uniqid());
    }
    
    public static function formatDate($date, $format = 'Y-m-d') {
        if(empty($date)) return '';
        return date($format, strtotime($date));
    }
    
    public static function formatCurrency($amount) {
        return '$' . number_format($amount, 2);
    }
    
    public static function getFunnelColor($category) {
        $colors = [
            'yellow' => 'warning',
            'pink' => 'danger',
            'green' => 'success',
            'blue' => 'primary'
        ];
        return $colors[$category] ?? 'secondary';
    }
    
    public static function getFunnelName($category) {
        $names = [
            'yellow' => 'Closable this month',
            'pink' => 'Newly quoted',
            'green' => 'Project based',
            'blue' => 'Services offered'
        ];
        return $names[$category] ?? 'Unknown';
    }
    
    public static function calculateLeadScore($lead) {
        $score = 0;
        
        // Budget
        if($lead['budget'] == 'high') $score += 30;
        elseif($lead['budget'] == 'medium') $score += 20;
        elseif($lead['budget'] == 'low') $score += 10;
        
        // Timeline
        if($lead['timeline'] == 'urgent') $score += 25;
        elseif($lead['timeline'] == '1-3_months') $score += 20;
        elseif($lead['timeline'] == '3-6_months') $score += 10;
        
        // Authority
        if($lead['authority'] == 'decision_maker') $score += 25;
        elseif($lead['authority'] == 'influencer') $score += 15;
        
        // Need
        if($lead['need_level'] == 'high') $score += 20;
        elseif($lead['need_level'] == 'medium') $score += 10;
        
        return $score;
    }
}
?>
Step 4: Models (app/models/)

Customer Model (app/models/Customer.php)

Step 6: Dashboard (dashboard.php)

Step 11: Reports Page (reports.php)

Step 12: Logout (logout.php)

Step 14: Installation Instructions

Setup MAMP:

Download and install MAMP from https://www.mamp.info/
Start MAMP servers (Apache & MySQL)
Create Database:

Open phpMyAdmin (usually http://localhost/phpmyadmin)
Create new database: technical_crm
Import database/crm.sql file
Configure Project:

Place all files in MAMP's htdocs folder: Applications/MAMP/htdocs/technical_crm/
Update database credentials in config/database.php if needed
Set Permissions:

bash
chmod 755 uploads/
chmod 644 config/
Access the System:

Open browser and go to: http://localhost:8888/technical_crm/
Login with: admin / password
Features Included:

✅ Complete CRM System with all requested features
✅ Customer Management with technical details
✅ 4-Color Sales Funnel with drag & drop
✅ Lead Qualification with scoring system
✅ Dashboard with analytics and KPIs
✅ Email Templates with WYSIWYG editor
✅ Follow-up & Reminder System
✅ Activity Tracking
✅ Reports & Analytics
✅ Responsive Design
✅ User Authentication
✅ Data Export (CSV, PDF)
✅ Security Features

Step 15: Sidebar Component (sidebar.php)
Step 17: Update Existing Files to Include Sidebar & Footer

Now you need to update the existing files to properly include the sidebar and footer:

Update dashboard.php, customers.php, deals.php, email.php, reports.php:

Replace the entire HTML structure in these files (from <!DOCTYPE html> to closing </html>) with:
Recommended Folder Structure:
technical_crm/
├── app/
│   ├── controllers/         # All your page files go here
│   │   ├── add_customer.php
│   │   ├── customers.php
│   │   ├── dashboard.php
│   │   ├── deals.php
│   │   ├── email.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── reports.php
│   ├── core/               # Core classes
│   │   ├── Auth.php
│   │   └── Helpers.php
│   ├── models/            # Model classes
│   │   ├── Customer.php
│   │   └── Deal.php
│   └── views/             # View templates
│       ├── sidebar.php
│       ├── footer.php
│       ├── header.php
│       └── notifications.php
├── config/
│   └── database.php
├── public/
│   └── index.php          # Entry point
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── uploads/
│   ├── templates/
│   └── documents/
└── database/
    └── crm.sql

Step 1: Move Sidebar and Footer to views folder

Save both files to app/views/:

app/views/sidebar.php

Step 2: Update ALL Controller Files to Use Correct Paths

Since your controllers are in a different folder now, you need to update the include paths. Here's how to update each controller:

Update app/controllers/dashboard.php:
Step 4: Update .htaccess for Clean URLs

Create .htaccess in the root folder:

text
RewriteEngine On

# Redirect all requests to public folder
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ public/$1 [L,QSA]

# Prevent directory listing
Options -Indexes

# Security headers
Header set X-Content-Type-Options nosniff
Header set X-Frame-Options DENY
Header set X-XSS-Protection "1; mode=block"
Step 5: Update MAMP Configuration

Move your files to the correct structure as shown above
Point MAMP to the public folder as the document root:

Open MAMP
Go to Preferences → Web Server
Set Document Root to: /Applications/MAMP/htdocs/technical_crm/public
Step 6: Create Missing Files

app/views/header.php (optional but useful):

php
<?php
// app/views/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Technical CRM'; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #1abc9c;
            --accent-color: #3498db;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #1a252f;
            border-color: #1a252f;
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
    </style>
</head>
<body>
Update your controllers to use header.php:

php
<?php
// In any controller file
$page_title = 'Dashboard'; // Set page title

// Include header
include __DIR__ . '/../views/header.php';

// Then include sidebar
include __DIR__ . '/../views/sidebar.php';

// Your content...

// Include footer
include __DIR__ . '/../views/footer.php';
Step 7: Quick Fix for Immediate Testing

If you want to test quickly without moving everything, just save sidebar.php and footer.php in your current folder and update the includes:

In dashboard.php, customers.php, etc.:

php
<?php
// At the beginning
require_once 'core/Auth.php';
require_once 'core/Helpers.php';
require_once 'models/Customer.php';
require_once '../config/database.php'; // Adjust path

// Your logic...

// Include sidebar and footer from current directory
include 'sidebar.php';  // If saved in same folder
// OR
include 'views/sidebar.php'; // If saved in views folder

// Your HTML...

include 'footer.php';
Summary:

Yes, save sidebar.php and footer.php to app/views/ folder - that's the correct MVC architecture. This gives you:

Clean separation: Views in app/views/, controllers in app/controllers/
Better organization: Easy to find and maintain
Professional structure: Follows MVC pattern
Scalability: Easy to add more views or templates
Quick setup for testing:

Create app/views/ folder
Save sidebar.php and footer.php there
Update all controller files with correct include paths
Make sure your MAMP document root points to the project folder
The system will work much better with this organized structure!
can you include the notification.php also?
Yes! Here's the complete notification.php file for the views folder:

app/views/notifications.php

Add JavaScript to Load Notifications in Dropdown


Features Included:

✅ Complete CRM System with all requested features
✅ Customer Management with technical details
✅ 4-Color Sales Funnel with drag & drop
✅ Lead Qualification with scoring system
✅ Dashboard with analytics and KPIs
✅ Email Templates with WYSIWYG editor
✅ Follow-up & Reminder System
✅ Activity Tracking
✅ Reports & Analytics
✅ Responsive Design
✅ User Authentication
✅ Data Export (CSV, PDF)
✅ Security Featurestechnical_crm/

STRUCTURE
├── app/
│   ├── controllers/         # All your page files go here
│   │   ├── add_customer.php
│   │   ├── customers.php
│   │   ├── dashboard.php
│   │   ├── deals.php
│   │   ├── email.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── reports.php
│   ├── core/               # Core classes
│   │   ├── Auth.php
│   │   └── Helpers.php
│   ├── models/            # Model classes
│   │   ├── Customer.php
│   │   └── Deal.php
│   └── views/             # View templates
│       ├── sidebar.php
│       ├── footer.php
│       ├── header.php
│       └── notifications.php
├── config/
│   └── database.php
├── public/
│   └── index.php          # Entry point
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── uploads/
│   ├── templates/
│   └── documents/
└── database/
    └── crm.sql
