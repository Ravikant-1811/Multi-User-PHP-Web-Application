<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/config.php';
require_role('client');

// Fetch complete user profile from database
$userId = $_SESSION['user_id'] ?? 0;
$userProfile = null;

try {
    $stmt = $conn->prepare("SELECT `id`, `name`, `email`, `role`, `status`, `created_at`, `updated_at` FROM `users` WHERE `id` = :id");
    $stmt->execute(['id' => $userId]);
    $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching profile: " . $e->getMessage();
}

$name = htmlspecialchars($userProfile['name'] ?? $_SESSION['name'] ?? '');
$email = htmlspecialchars($userProfile['email'] ?? $_SESSION['email'] ?? '');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
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
            max-width: 1200px;
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
            padding: 30px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .welcome h2 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #666;
            font-size: 16px;
        }

        .welcome-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        /* Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Card */
        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-size: 14px;
        }

        .info-value {
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        /* Stats */
        .stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
            margin-top: 15px;
        }

        .stats-number {
            font-size: 32px;
            font-weight: 600;
            color: #007bff;
        }

        .stats-label {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        /* Message Badge */
        .unread-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        /* Message List */
        .message-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-content {
            flex: 1;
        }

        .message-sender {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .message-text {
            color: #666;
            font-size: 13px;
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .message-time {
            font-size: 12px;
            color: #999;
        }

        .message-unread {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #007bff;
            margin-left: 10px;
            margin-top: 2px;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: #999;
            font-size: 14px;
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
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->


        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Welcome -->

        <div class="welcome">
            <div>
                <h2>Welcome,
                    <span id="welcomeName"><?php echo $name; ?></span>
                    <span class="status-badge status-inactive" id="welcomeStatus">Offline</span>
                </h2>
                <p>Your last login was on
                    <b id="welcomeLastLogin">Loading...</b>.
                </p>
            </div>
            <div class="welcome-actions">
                <a href="<?php echo url('chats/chats.php'); ?>" class="btn btn-primary">Messages</a>
                <a href="<?php echo url('auth/logout.php'); ?>" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <!-- Grid -->
        <div class="grid">
            <!-- Profile Summary -->
            <div class="card">
                <div class="card-title">Profile Summary</div>
                <div class="info-row">
                    <span class="info-label">Name</span>
                    <span class="info-value" id="profileName"><?php echo $name; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value" id="profileEmail"><?php echo $email; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role</span>
                    <span class="info-value" id="profileRole">Client</span>
                </div>
                <div class="info-row">
                    <span class="info-label">User ID</span>
                    <span class="info-value" id="profileId">#<?php echo $userId; ?></span>
                </div>
            </div>

            <!-- Account Status -->
            <div class="card">
                <div class="card-title">Account Status</div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <span class="status-badge status-active" id="statusBadge">Active</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Created</span>
                    <span class="info-value" id="accountCreated">Loading...</span>
                </div>
                <div class="stats">
                    <div class="stats-number" id="daysActive">0</div>
                    <div class="stats-label">DAYS ACTIVE</div>
                </div>
            </div>

            <!-- Login Activity -->
            <div class="card">
                <div class="card-title">Login Activity</div>
                <div class="info-row">
                    <span class="info-label">Last Activity</span>
                    <span class="info-value" id="lastLogin">Loading...</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <span class="status-badge status-inactive" id="onlineStatus">Offline</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Session Status</span>
                    <span class="info-value" style="color: #28a745; font-weight: 600;">Active</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Session Started</span>
                    <span class="info-value" id="sessionStarted"><?php echo date('g:i A'); ?></span>
                </div>
            </div>
        </div>

        <!-- Messages and Notifications Grid -->
        <div class="grid">
            <!-- Recent Messages Section -->
            <div class="card">
                <div class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Recent Messages from Admin</span>
                    <span class="unread-badge" id="unreadBadge" style="display:none;">0</span>
                </div>
                <div id="recentMessages"></div>
                <div id="messagesFooter"
                    style="text-align: center; padding-top: 15px; border-top: 1px solid #eee; display:none;">
                    <a href="<?php echo url('chats/chats.php'); ?>" class="btn btn-primary">View All Messages</a>
                </div>
                <div class="empty-message" id="recentEmpty" style="display:none;">No messages from admin yet</div>
            </div>

            <!-- Notifications Section -->
            <div class="card">
                <div class="card-title">Notifications</div>
                <div class="info-row">
                    <span class="info-label">Unread Messages</span>
                    <span class="info-value" id="notifUnread" style="color: #007bff; font-weight: 600;">0 unread</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Account Status</span>
                    <span class="info-value">
                        <span class="status-badge status-active" id="notifStatus">Active</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Login</span>
                    <span class="info-value" id="notifLastLogin">Loading...</span>
                </div>
                <div class="info-row" id="notifAlert"
                    style="display:none; background: #f0f7ff; padding: 12px; margin: 12px -25px -25px -25px; border-radius: 0 0 8px 8px; border-top: 1px solid #e0e0e0;">
                    <span class="info-label">Alert</span>
                    <span class="info-value" style="color: #dc3545;" id="notifAlertText"></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const welcomeName = document.getElementById('welcomeName');
        const profileName = document.getElementById('profileName');
        const profileEmail = document.getElementById('profileEmail');
        const profileRole = document.getElementById('profileRole');
        const profileId = document.getElementById('profileId');
        const statusBadge = document.getElementById('statusBadge');
        const accountCreated = document.getElementById('accountCreated');
        const daysActive = document.getElementById('daysActive');
        const lastLogin = document.getElementById('lastLogin');
        const onlineStatus = document.getElementById('onlineStatus');
        const unreadBadge = document.getElementById('unreadBadge');
        const recentMessages = document.getElementById('recentMessages');
        const recentEmpty = document.getElementById('recentEmpty');
        const messagesFooter = document.getElementById('messagesFooter');
        const notifUnread = document.getElementById('notifUnread');
        const notifStatus = document.getElementById('notifStatus');
        const notifLastLogin = document.getElementById('notifLastLogin');
        const notifAlert = document.getElementById('notifAlert');
        const notifAlertText = document.getElementById('notifAlertText');

        function badgeClass(status) {
            if (status === 'active') return 'status-active';
            if (status === 'pending') return 'status-pending';
            return 'status-inactive';
        }

        function renderMessages(list, unreadCount) {
            recentMessages.innerHTML = '';
            if (!list || list.length === 0) {
                recentEmpty.style.display = 'block';
                messagesFooter.style.display = 'none';
                unreadBadge.style.display = 'none';
                return;
            }
            recentEmpty.style.display = 'none';
            messagesFooter.style.display = 'block';
            unreadBadge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
            unreadBadge.textContent = unreadCount;

            list.forEach(msg => {
                const wrapper = document.createElement('div');
                wrapper.className = 'message-item';

                const content = document.createElement('div');
                content.className = 'message-content';

                const sender = document.createElement('div');
                sender.className = 'message-sender';
                sender.textContent = msg.sender_name;

                const text = document.createElement('div');
                text.className = 'message-text';
                const body = msg.message || '';
                text.textContent = body.length > 150 ? body.slice(0, 150) + '...' : body;

                const time = document.createElement('div');
                time.className = 'message-time';
                time.textContent = msg.created_at;

                content.appendChild(sender);
                content.appendChild(text);
                content.appendChild(time);
                wrapper.appendChild(content);

                if (!msg.is_read) {
                    const dot = document.createElement('div');
                    dot.className = 'message-unread';
                    wrapper.appendChild(dot);
                }
                recentMessages.appendChild(wrapper);
            });
        }

        function applyStatus(el, status) {
            el.classList.remove('status-active', 'status-inactive', 'status-pending');
            el.classList.add(badgeClass(status));
            el.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        }

        function loadDashboard() {
            fetch(`${BASE_URL}client/ajax_dashboard.php?action=get_data`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.error || 'Failed to load dashboard');
                    const d = data.data;
                    const p = d.profile;

                    // Profile
                    profileName.textContent = p.name;
                    profileEmail.textContent = p.email;
                    profileRole.textContent = p.role ? p.role.charAt(0).toUpperCase() + p.role.slice(1) : 'Client';
                    profileId.textContent = '#' + p.id;
                    if (welcomeName) welcomeName.textContent = p.name;

                    // Welcome section status and last login
                    const welcomeStatusEl = document.getElementById('welcomeStatus');
                    const welcomeLastLoginEl = document.getElementById('welcomeLastLogin');
                    if (welcomeStatusEl) {
                        applyStatus(welcomeStatusEl, d.online_status === 'active' ? 'active' : 'inactive');
                    }
                    if (welcomeLastLoginEl) {
                        welcomeLastLoginEl.textContent = d.last_login;
                    }

                    // Status
                    applyStatus(statusBadge, p.status);
                    accountCreated.textContent = p.created_at ? new Date(p.created_at).toLocaleDateString() : 'N/A';
                    daysActive.textContent = p.days_active;

                    // Login activity
                    lastLogin.textContent = d.last_login;
                    applyStatus(onlineStatus, d.online_status === 'active' ? 'active' : 'inactive');

                    // Notifications
                    notifUnread.textContent = `${d.unread_count} unread`;
                    applyStatus(notifStatus, p.status);
                    notifLastLogin.textContent = d.last_login;
                    if (d.unread_count > 0) {
                        notifAlert.style.display = 'flex';
                        notifAlertText.textContent = `You have ${d.unread_count} unread message${d.unread_count > 1 ? 's' : ''}`;
                    } else {
                        notifAlert.style.display = 'none';
                    }

                    // Messages
                    renderMessages(d.recent_messages, d.unread_count);
                })
                .catch(err => {
                    console.error(err);
                    recentEmpty.style.display = 'block';
                    recentEmpty.textContent = 'Failed to load dashboard';
                });
        }

        loadDashboard();
        // Refresh lightweight data periodically
        setInterval(loadDashboard, 5000);
    </script>
</body>

</html>