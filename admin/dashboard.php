<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$name = htmlspecialchars($_SESSION['name'] ?? '');
$email = htmlspecialchars($_SESSION['email'] ?? '');
$userId = $_SESSION['user_id'] ?? 0;

// Fetch admin's last login and status
$stmt = $conn->prepare("SELECT last_login, message_status FROM users WHERE id = ?");
$stmt->execute([$userId]);
$adminInfo = $stmt->fetch(PDO::FETCH_ASSOC);
$lastLogin = $adminInfo['last_login'] ?? null;
$messageStatus = $adminInfo['message_status'] ?? 'offline';
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 24px;
            color: #333;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Welcome */
        .welcome {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .welcome h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .welcome p {
            color: #666;
            font-size: 14px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #007bff;
        }

        .stat-card.success {
            border-left-color: #28a745;
        }

        .stat-card.warning {
            border-left-color: #ffc107;
        }

        .stat-card.danger {
            border-left-color: #dc3545;
        }

        .stat-card.info {
            border-left-color: #17a2b8;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Card */
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .card-body {
            max-height: 300px;
            overflow-y: auto;
        }

        /* List Items */
        .list-item {
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-title {
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .item-meta {
            font-size: 12px;
            color: #666;
        }

        .item-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .admin-info {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .admin-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-indicator.online {
            background: #28a745;
        }

        .status-indicator.offline {
            background: #6c757d;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-primary {
            background: #cfe2ff;
            color: #084298;
        }

        /* Message Preview */
        .message-preview {
            font-size: 13px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #999;
        }

        /* Error */
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Scrollbar */
        .card-body::-webkit-scrollbar {
            width: 6px;
        }

        .card-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .card-body::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Admin Dashboard</h1>
            <div class="header-actions">
                <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-secondary">Manage Users</a>
                <a href="<?php echo url('chats/chats.php'); ?>" class="btn btn-primary">Messages</a>
                <a href="<?php echo url('auth/logout.php'); ?>" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <div id="error-message" class="error" style="display: none;"></div>

        <!-- Welcome -->
        <div class="welcome">
            <h2>Welcome, <?php echo $name; ?></h2>
            <p>Administrator Panel • <span id="current-date"><?php echo date('l, F j, Y'); ?></span></p>
            <div class="admin-info">
                <div class="admin-info-item">
                    <span
                        class="status-indicator <?php echo $messageStatus === 'active' ? 'online' : 'offline'; ?>"></span>
                    Status: <strong><?php echo $messageStatus === 'active' ? 'Online' : 'Offline'; ?></strong>
                </div>
                <div class="admin-info-item">
                    Last Login:
                    <strong><?php echo $lastLogin ? date('M j, Y g:i A', strtotime($lastLogin)) : 'First login'; ?></strong>
                </div>
            </div>
        </div>

        <!-- System Summary Widgets -->
        <div class="stats-grid" id="stats-container">
            <!-- Stats loaded via AJAX -->
        </div>

        <!-- Today's Stats -->
        <div class="stats-grid" id="today-stats-container"
            style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            <!-- Today's stats loaded via AJAX -->
        </div>

        <!-- Main Grid -->
        <div class="grid">
            <!-- Recent User Activity -->
            <div class="card">
                <div class="card-title">Recent User Activity</div>
                <div class="card-body" id="activity-container">
                    <div class="empty-state">Loading...</div>
                </div>
            </div>

            <!-- Notifications Panel -->
            <div class="card">
                <div class="card-title">Notifications</div>
                <div class="card-body" id="notifications-container">
                    <div class="empty-state">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Recent Chat Messages -->
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-title">Recent Chat Messages</div>
            <div class="card-body" id="messages-container">
                <div class="empty-state">Loading...</div>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const AJAX_URL = 'ajax_dashboard.php';

        // Load all dashboard sections
        function loadDashboard() {
            loadStats();
            loadActivity();
            loadNotifications();
            loadRecentMessages();
        }

        // Load statistics
        function loadStats() {
            fetch(`${AJAX_URL}?action=get_stats`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const stats = data.data;
                        let html = '';

                        const cards = [
                            { number: stats.totalUsers, label: 'Total Users', class: '' },
                            { number: stats.userStats.active, label: 'Active Users', class: 'success' },
                            { number: stats.userStats.inactive, label: 'Inactive Users', class: 'warning' },
                            { number: stats.totalClients, label: 'Total Clients', class: 'info' },
                            { number: stats.totalMessages, label: 'Total Messages', class: '' },
                            { number: stats.unreadMessages, label: 'Unread Messages', class: 'danger' }
                        ];

                        cards.forEach(card => {
                            html += `
                                <div class="stat-card ${card.class}">
                                    <div class="stat-number">${card.number}</div>
                                    <div class="stat-label">${card.label}</div>
                                </div>
                            `;
                        });

                        document.getElementById('stats-container').innerHTML = html;

                        // Today's stats
                        let todayHtml = '';
                        todayHtml += `
                            <div class="stat-card success">
                                <div class="stat-number">${stats.newUsersToday}</div>
                                <div class="stat-label">New Users Today</div>
                            </div>
                            <div class="stat-card info">
                                <div class="stat-number">${stats.messagesToday}</div>
                                <div class="stat-label">Messages Today</div>
                            </div>
                        `;
                        document.getElementById('today-stats-container').innerHTML = todayHtml;
                    } else {
                        showError('Failed to load statistics');
                    }
                })
                .catch(err => showError('Error loading statistics: ' + err.message));
        }

        // Load recent activity
        function loadActivity() {
            fetch(`${AJAX_URL}?action=get_activity`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const activities = data.data;
                        let html = '';

                        if (activities.length > 0) {
                            activities.forEach(user => {
                                const statusClass = user.status === 'active' ? 'success' :
                                    (user.status === 'pending' ? 'warning' : 'danger');
                                const lastActive = new Date(user.updated_at).toLocaleString();
                                html += `
                                    <div class="list-item">
                                        <div class="item-info">
                                            <div class="item-title">${htmlEscape(user.name)}</div>
                                            <div class="item-meta">
                                                ${htmlEscape(user.email)} • Last active: ${lastActive}
                                            </div>
                                        </div>
                                        <span class="item-badge badge-${statusClass}">
                                            ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                                        </span>
                                    </div>
                                `;
                            });
                        } else {
                            html = '<div class="empty-state">No user activity</div>';
                        }

                        document.getElementById('activity-container').innerHTML = html;
                    }
                })
                .catch(err => showError('Error loading activity'));
        }

        // Load notifications
        function loadNotifications() {
            fetch(`${AJAX_URL}?action=get_notifications`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const notifications = data.data;
                        let html = '';

                        if (notifications.length > 0) {
                            notifications.forEach(notif => {
                                html += `
                                    <div class="list-item">
                                        <div class="item-info">
                                            <div class="item-title">New messages from ${htmlEscape(notif.name)}</div>
                                            <div class="item-meta">
                                                ${notif.unread_count} unread message${notif.unread_count > 1 ? 's' : ''}
                                            </div>
                                        </div>
                                        <a href="${BASE_URL}chats/chats.php?user_id=${notif.id}" class="item-badge badge-primary">
                                            View Chat
                                        </a>
                                    </div>
                                `;
                            });
                        } else {
                            html = '<div class="empty-state">No new notifications</div>';
                        }

                        document.getElementById('notifications-container').innerHTML = html;
                    }
                })
                .catch(err => showError('Error loading notifications'));
        }

        // Load recent messages
        function loadRecentMessages() {
            fetch(`${AJAX_URL}?action=get_recent_messages`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const messages = data.data;
                        let html = '';

                        if (messages.length > 0) {
                            messages.forEach(msg => {
                                const badgeClass = msg.is_read ? 'success' : 'warning';
                                const badgeText = msg.is_read ? 'Read' : 'Unread';
                                const timestamp = new Date(msg.created_at).toLocaleString();
                                html += `
                                    <div class="list-item">
                                        <div class="item-info">
                                            <div class="item-title">
                                                ${htmlEscape(msg.sender_name)} → ${htmlEscape(msg.receiver_name)}
                                            </div>
                                            <div class="message-preview">
                                                ${htmlEscape(msg.message)}
                                            </div>
                                            <div class="item-meta">${timestamp}</div>
                                        </div>
                                        <span class="item-badge badge-${badgeClass}">${badgeText}</span>
                                    </div>
                                `;
                            });
                        } else {
                            html = '<div class="empty-state">No recent messages</div>';
                        }

                        document.getElementById('messages-container').innerHTML = html;
                    }
                })
                .catch(err => showError('Error loading messages'));
        }

        // Helper to escape HTML
        function htmlEscape(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Show error message
        function showError(message) {
            const errorDiv = document.getElementById('error-message');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        // Load dashboard on page load
        loadDashboard();

        // Refresh dashboard every 4 seconds
        setInterval(loadDashboard, 4000);

        // Update current date/time
        function updateDate() {
            const options = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };
            document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-US', options);
        }
        updateDate();
    </script>
</body>

</html>