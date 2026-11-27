# CommUnity — Unified Residential Community Platform

A complete, database-driven web application for residential community management with three role-based dashboards: **Administrator**, **Watchman**, and **Resident**.

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Backend | PHP 7.4+ |
| Database | MySQL 5.7+ |
| Local Server | XAMPP |
| Database Management | phpMyAdmin |

---

## Project Structure

```
community/
├── index.php              # Landing page
├── login.php              # Login page
├── signup.php             # Signup page
├── logout.php             # Logout handler
│
├── config/
│   ├── database.php       # Database configuration
│   └── constants.php      # Application constants
│
├── auth/
│   ├── authentication.php # Session & auth helpers
│   ├── login_process.php  # Login handler
│   └── signup_process.php # Signup handler
│
├── includes/
│   ├── functions.php      # Shared helper functions
│   ├── top.php            # Dashboard layout (header + sidebar)
│   └── bottom.php         # Dashboard layout (footer)
│
├── admin/
│   ├── dashboard.php      # Admin dashboard
│   ├── residents.php      # Resident management (CRUD)
│   ├── blocks.php         # Block management
│   ├── users.php          # User management
│   ├── visitors.php       # Visitors overview
│   ├── deliveries.php     # Deliveries overview
│   ├── reports.php        # Reports & analytics
│   ├── activity_logs.php  # Activity logs
│   └── settings.php       # System settings
│
├── watchman/
│   ├── dashboard.php      # Watchman dashboard
│   ├── residents.php      # Resident directory
│   ├── visitors.php       # Visitor management
│   ├── entry_exit.php     # Entry/Exit terminal
│   └── deliveries.php     # Delivery management
│
├── resident/
│   ├── dashboard.php      # Resident dashboard
│   ├── profile.php        # My profile
│   ├── visitors.php       # Visitor requests
│   ├── local_help.php     # Skill search
│   ├── help_requests.php  # Help requests
│   ├── resources.php      # Resource sharing
│   ├── my_skills.php      # My skills
│   ├── my_resources.php   # My resources
│   ├── notifications.php  # Notifications
│   └── my_activity.php    # My activity log
│
├── api/
│   ├── residents.php      # Resident search API
│   ├── visitors.php       # Visitor PIN & workflow API
│   ├── deliveries.php     # Delivery API
│   ├── notifications.php  # Notification API
│   ├── skills.php         # Skills search API
│   ├── help_requests.php  # Help request API
│   └── resources.php      # Resource API
│
├── assets/
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   ├── js/
│   │   └── app.js         # Main JavaScript
│   ├── images/            # Images
│   └── uploads/           # User uploads
│
└── database/
    └── community.sql      # Database schema + demo data
```

---

## Setup Instructions

### 1. Install XAMPP

Download and install XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)

### 2. Start XAMPP Services

Open XAMPP Control Panel and start:
- **Apache** (web server)
- **MySQL** (database)

### 3. Create Database

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **New** to create a new database
3. Name it `community`
4. Click **Import**
5. Choose `community/database/community.sql`
6. Click **Go**

### 4. Configure Database Connection

Edit `config/database.php` if needed:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'community');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP password is empty
```

### 5. Place Project in htdocs

Copy the `community` folder to `C:\xampp\htdocs\community`

### 6. Access the Application

Open your browser and go to:
```
http://localhost/community/
```

---

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@community.com | admin123 |
| Watchman | watchman@community.com | watchman123 |
| Resident (Arun) | arun@community.com | resident123 |
| Resident (Priya) | priya@community.com | resident123 |
| Resident (Vishwa) | vishwa@community.com | resident123 |
| Resident (Karthik) | karthik@community.com | resident123 |

---

## Core Workflows

### Visitor Entry Workflow
1. Resident creates a visitor request → 6-digit PIN generated
2. Watchman enters PIN at Entry/Exit terminal
3. Watchman clicks "Visitor Arrived"
4. Watchman clicks "Notify Resident"
5. Resident receives notification with Approve/Reject options
6. Resident approves → Watchman sees approval → Visitor allowed inside
7. Resident rejects → Watchman sees rejection → Visitor denied entry
8. Watchman records visitor exit

### Delivery Workflow
1. Watchman selects resident and enters delivery details
2. System checks resident availability
3. If Available → normal delivery handling
4. If Not Available → Watchman clicks "Notify Resident"
5. Resident receives notification
6. Resident marks delivery as collected

### Local Help Workflow
1. Resident adds a skill
2. Another resident searches for the skill
3. Resident sends help request
4. Skill provider receives notification
5. Provider accepts → Help completed → Rating/Feedback

### Resource Sharing Workflow
1. Resident adds a resource item
2. Another resident searches and requests the item
3. Owner receives notification
4. Owner accepts/rejects → Transaction completed → Status updated

---

## Security Features

- Password hashing with bcrypt (PHP `password_hash`)
- PHP session-based authentication
- Role-based access control (RBAC)
- PDO prepared statements (SQL injection prevention)
- Output escaping (XSS prevention)
- Secure file uploads (type & size validation)
- Visitor PIN expiration
- Protected dashboard pages

---

## Features Summary

### Administrator Dashboard
- Dashboard with statistics
- Resident management (Add, Edit, Delete, Verify)
- Block management
- User management (Watchmen & Admins)
- Visitor overview
- Delivery overview
- Reports & analytics
- Activity logs
- System settings & announcements

### Watchman Dashboard
- Dashboard with security statistics
- Resident directory with search & filters
- Visitor management with PIN-based entry/exit
- Delivery management with availability check
- Real-time notifications

### Resident Dashboard
- Personal dashboard
- Profile management with availability toggle
- Visitor request creation with PIN generation
- Local help skill search
- Help request management
- Resource sharing (add, search, request)
- My skills management
- My resources management
- Notification center
- Activity log

---

## License

This project is developed for educational purposes as a final-year college project.
