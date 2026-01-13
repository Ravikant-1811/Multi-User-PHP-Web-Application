# MUPWA - Multi-User PHP Web Application

A real-time messaging and user management system built with PHP, MySQL, Ajax. MUPWA enables seamless communication between clients and administrators with live status tracking and instant notifications.

## Features

### Real-Time Messaging

- **Instant messaging** between clients and administrators
- **Live online/offline status** with automatic detection
- **Read receipts** with visual indicators (✓/✓✓)
- **Smart timestamps** (Today, Yesterday, or full date)
- **Unread message badges** with live count updates
- **Auto-refresh** every 500ms for near real-time experience

### User Management

- **AJAX-powered interface** - no page reloads needed
- **Role-based access control** (Admin/Client)
- **Live search** by name or email with 300ms debounce
- **Advanced filtering** by role and account status
- **Toast notifications** for all actions (no intrusive alerts)
- **Secure authentication** with password hashing

### Password Reset

- **Email-based password reset** with secure tokens
- **One-hour token expiry** for security
- **CSRF protection** on reset forms

### Admin Dashboard

- **User overview** with pagination
- **Client message history** with status tracking
- **Real-time client list** with last message preview
- **Activity monitoring** with last login timestamps

## 📁 Project Structure

```
MUPWA/
├── admin/              # Admin dashboard and user management
│   ├── dashboard.php   # Admin main dashboard
│   ├── users.php       # User management interface
│   └── ajax_users.php  # AJAX API for user operations
├── auth/               # Authentication system
│   ├── auth.php        # Auth helper functions
│   ├── login.php       # Login page
│   ├── register.php    # Registration page
│   ├── forgot_password.php  # Password reset request
│   └── reset_password.php   # Password reset form
├── chats/              # Messaging system
│   ├── chats.php       # Chat interface
│   ├── ajax_chat.php   # AJAX API for messaging
│   └── ajax_dashboard.php   # Dashboard data
├── client/             # Client dashboard
│   └── dashboard.php   # Client main dashboard
├── config/             # Configuration
│   └── config.php      # Database connection
├── assets/             # Static resources
│   ├── style.css       # Main stylesheet
│   ├── script.js       # Main JavaScript
│   └── images/         # Image assets
├── database_schema.sql # Database structure
├── index.php           # Landing page
└── README.md           # This file
```

### Polling Intervals

Adjust real-time update speeds in the JavaScript files:

- Messages: 500ms (chats.php)
- Read status: 300ms (chats.php)
- User list: 1000ms (chats.php)
- Keep-alive: 30000ms (keep_alive.php)

## Usage

### For Clients

1. **Register** an account at `/auth/register.php`
2. **Login** to access your dashboard
3. **Start chatting** with administrators from the chat interface
4. **View profile** and message history on dashboard

### For Administrators

1. **Login** with admin role credentials
2. **Manage users** from `/admin/users.php`
   - Add, edit, or delete users
   - Update user status (active/inactive)
   - Search and filter users
3. **View messages** from all clients
4. **Monitor activity** with online status indicators

### Password Reset

1. Click **"Forgot Password?"** on login page
2. Enter your **email address**
3. Check your **email** for reset link
4. Click link and **enter new password**
5. Token expires in **1 hour**
