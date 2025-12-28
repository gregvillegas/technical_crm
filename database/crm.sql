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
    quota_profit DECIMAL(15,2) DEFAULT 250000.00,
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

-- System Notifications Table
CREATE TABLE system_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    icon VARCHAR(50) DEFAULT 'bell',
    category ENUM('system', 'deal', 'customer', 'lead', 'activity', 'email') DEFAULT 'system',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    related_type ENUM('customer', 'deal', 'lead', 'activity') NULL,
    related_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX idx_notifications_user ON system_notifications(user_id, is_read, created_at);
CREATE INDEX idx_notifications_read ON system_notifications(is_read, created_at);

-- Insert Default Admin User
INSERT INTO users (username, email, password, first_name, last_name, role) 
VALUES ('admin', 'admin@crm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin');
-- Password: password

-- Insert Sample Email Templates
INSERT INTO email_templates (template_name, template_slug, subject, content, category, variables) VALUES
('Initial Follow-up', 'initial-followup', 'Following up on our conversation', '<p>Dear {customer_name},</p><p>I hope this email finds you well.</p><p>I wanted to follow up on our recent conversation about {deal_name}. I believe our solution could help you with {pain_points}.</p><p>Would you be available for a quick call next week?</p><p>Best regards,<br>{sales_rep_name}</p>', 'followup', '["customer_name", "deal_name", "pain_points", "sales_rep_name"]'),

('Technical Proposal', 'technical-proposal', 'Technical Proposal for {company_name}', '<p>Dear {contact_person},</p><p>As per our discussion, I''ve prepared a technical proposal for {project_name}.</p><p><strong>Key Features:</strong></p><ul><li>Feature 1</li><li>Feature 2</li></ul><p><strong>Technical Specifications:</strong></p><ul><li>Spec 1</li><li>Spec 2</li></ul><p>Please let me know if you have any questions.</p><p>Best regards,<br>{sales_rep_name}</p>', 'proposal', '["contact_person", "company_name", "project_name", "sales_rep_name"]'),

('Demo Invitation', 'demo-invitation', 'Invitation: Product Demo', '<p>Hello {customer_name},</p><p>I would like to invite you to a personalized demo of our solution.</p><p><strong>Date:</strong> {demo_date}<br><strong>Time:</strong> {demo_time}<br><strong>Duration:</strong> 30 minutes</p><p>Please confirm your availability.</p><p>Best regards,<br>{sales_rep_name}</p>', 'demo', '["customer_name", "demo_date", "demo_time", "sales_rep_name"]');