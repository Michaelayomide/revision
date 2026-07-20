Premium Custom PHP & MySQL Admin Dashboard & Client Portal

A modern, high-performance, and secure dual-portal application built using native PHP and MySQL. Designed as a lightweight, framework-free alternative to bloated SaaS solutions, this system provides a seamless environment for business administration and client management.

Key Features

-Dual-Portal Architecture:** Features independent dashboards for Admin Operations (user/inventory management) and a secure Customer Portal (metrics, transactions, records).
-Core Session Security: Robust authentication flow utilizing secure registration, login handling, password hashing, and role-based access control.
-Dynamic UI/UX: Sleek, high-polish dashboard theme with interactive metrics cards, live progress indicators, and searchable, filterable data tables.
-Automated Email Notifications: Integrates PHPMailer to handle reliable transactional communication and notifications.
-Zero Framework Bloat: Written in optimised, pure PHP for rapid, lightning-fast execution and easy deployment on standard web hosting setups (like cPanel).
-Fully Responsive Layout: Optimised to look and function perfectly across desktops, tablets, and smartphones.

  Tech Stack & Dependencies

- Backend: PHP (Native/Procedural)
- Database: MySQL
- Frontend Framework: TailwindCSS / Bootstrap (Clean, structured HTML5/CSS3)
- Email Delivery: PHPMailer

 Installation & Setup

Follow these steps to deploy and run the application locally or on your production server:

 1. Prerequisites
- PHP 8.x or higher installed.
- MySQL / MariaDB server.
- Composer (required to install the PHPMailer dependency).

 2. Clone the Repository
bash
https://github.com/Michaelayomide/revision.git
cd php-mysql-dashboard

bash
composer install

<?php
// Database Setup
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'company_dashboard');

// PHPMailer SMTP Configuration
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@example.com');
define('SMTP_PASS', 'your_email_password');

php -S localhost:8000
