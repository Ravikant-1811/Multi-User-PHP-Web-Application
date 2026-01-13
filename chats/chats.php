<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? '';
$userName = htmlspecialchars($_SESSION['name'] ?? '');

// For client, chat partner is always admin (user with role 'admin')
// For admin, get chat partner from URL or show user list
$chatPartnerId = null;
$chatPartnerName = '';

if ($userRole === 'client') {
    // Client always chats with admin
    $stmt = $conn->prepare("SELECT id, name, message_status FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        $chatPartnerId = $admin['id'];
        $chatPartnerName = htmlspecialchars($admin['name']);
        $chatPartnerOnline = ($admin['message_status'] === 'active');
    }
} else {
    // Admin can select client
    if (isset($_GET['user_id'])) {
        $chatPartnerId = (int) $_GET['user_id'];
        $stmt = $conn->prepare("SELECT name, message_status FROM users WHERE id = :id AND role = 'client'");
        $stmt->execute(['id' => $chatPartnerId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $chatPartnerName = htmlspecialchars($user['name']);
            $chatPartnerOnline = ($user['message_status'] === 'active');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - MUPWA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background-color: #f5f5f5;
            height: 100vh;
            overflow: hidden;
        }

        .chat-container {
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 320px;
            background: white;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #007bff;
            color: white;
        }

        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 13px;
            opacity: 0.9;
        }

        .search-box {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .user-list {
            flex: 1;
            overflow-y: auto;
        }

        .user-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
        }

        .user-item:hover {
            background: #f8f9fa;
        }

        .user-item.active {
            background: #e3f2fd;
            border-left: 3px solid #007bff;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-weight: 500;
            font-size: 15px;
            margin-bottom: 3px;
        }

        .user-preview {
            font-size: 13px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
        }

        .last-message-time {
            font-size: 11px;
            color: #999;
        }

        .message-count {
            font-size: 11px;
            color: #007bff;
            font-weight: 500;
        }

        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        /* Chat Area */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
        }

        .chat-header-info {
            display: flex;
            align-items: center;
        }

        .chat-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
        }

        .chat-header-name {
            font-size: 18px;
            font-weight: 600;
        }

        .chat-header-status {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .status-online {
            background: #28a745;
        }

        .status-offline {
            background: #adb5bd;
        }

        .privacy-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
        }

        .back-btn {
            padding: 8px 16px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
        }

        .back-btn:hover {
            background: #e9ecef;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .message.sent {
            align-items: flex-end;
        }

        .message.received {
            align-items: flex-start;
        }

        .message-content {
            max-width: 60%;
            padding: 10px 15px;
            border-radius: 10px;
            word-wrap: break-word;
        }

        .message.sent .message-content {
            background: #007bff;
            color: white;
            border-bottom-right-radius: 2px;
        }

        .message.received .message-content {
            background: white;
            color: #333;
            border-bottom-left-radius: 2px;
            border: 1px solid #e0e0e0;
        }

        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
            padding: 0 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .read-status {
            display: inline-flex;
            align-items: center;
            font-size: 14px;
            margin-left: 3px;
        }

        .read-status.sent {
            color: #999;
        }

        .read-status.read {
            color: #4fc3f7;
        }

        .message.sent .message-time {
            justify-content: flex-end;
        }

        .message-input-area {
            padding: 15px 20px;
            border-top: 1px solid #e0e0e0;
            background: white;
        }

        .message-input-form {
            display: flex;
            gap: 10px;
        }

        .message-input-form input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
        }

        .message-input-form button {
            padding: 12px 25px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .message-input-form button:hover {
            background: #0056b3;
        }

        .message-input-form button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            text-align: center;
            padding: 40px;
        }

        .empty-state-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #666;
        }

        .empty-state p {
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: absolute;
                z-index: 10;
                display: none;
            }

            .sidebar.show {
                display: flex;
            }

            .chat-area {
                width: 100%;
            }

            .message-content {
                max-width: 85%;
            }
        }

        /* Scrollbar */
        .messages-container::-webkit-scrollbar,
        .user-list::-webkit-scrollbar {
            width: 6px;
        }

        .messages-container::-webkit-scrollbar-track,
        .user-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .messages-container::-webkit-scrollbar-thumb,
        .user-list::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        .messages-container::-webkit-scrollbar-thumb:hover,
        .user-list::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        .typing-indicator {
            display: none;
            padding: 10px;
            color: #999;
            font-size: 13px;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <!-- Sidebar -->
        <?php if ($userRole === 'admin'): ?>
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <h2>Messages</h2>
                    <p>Multiple Client Conversations</p>
                </div>

                <div class="search-box">
                    <input type="text" id="searchUsers" placeholder="Search clients...">
                </div>

                <div class="user-list" id="userList">
                    <!-- User list will be loaded via AJAX -->
                </div>
            </div>
        <?php endif; ?>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($chatPartnerId): ?>
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="chat-header-avatar">
                            <?php echo strtoupper(substr($chatPartnerName, 0, 1)); ?>
                        </div>
                        <div>
                            <div class="chat-header-name">
                                <?php echo $chatPartnerName; ?>
                                <span class="privacy-badge">
                                    🔒 Private Conversation
                                </span>
                            </div>
                            <div class="chat-header-status">
                                <?php echo $userRole === 'admin' ? 'Client User' : 'Administrator'; ?> • One-to-One Chat •
                                <span id="partnerStatusDot"
                                    class="status-dot <?php echo !empty($chatPartnerOnline) ? 'status-online' : 'status-offline'; ?>"></span>
                                <span
                                    id="partnerStatusText"><?php echo !empty($chatPartnerOnline) ? 'Online' : 'Offline'; ?></span>
                            </div>
                        </div>
                    </div>
                    <a href="<?php echo url($userRole === 'admin' ? 'admin/dashboard.php' : 'client/dashboard.php'); ?>"
                        class="back-btn">
                        Back to Dashboard
                    </a>
                </div>

                <div class="messages-container" id="messagesContainer">
                    <!-- Messages will be loaded via AJAX -->
                </div>

                <div class="typing-indicator" id="typingIndicator">
                    <?php echo $chatPartnerName; ?> is typing...
                </div>

                <div class="message-input-area">
                    <form class="message-input-form" id="messageForm">
                        <input type="text" id="messageInput" placeholder="Type a message..." autocomplete="off" required>
                        <button type="submit" id="sendBtn">Send</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💬</div>
                    <h3><?php echo $userRole === 'admin' ? 'Select a client to start chatting' : 'No admin available'; ?>
                    </h3>
                    <p><?php echo $userRole === 'admin' ? 'You can manage multiple one-to-one conversations. Choose a client from the sidebar.' : 'Please contact support'; ?>
                    </p>
                    <br>
                    <a href="<?php echo url($userRole === 'admin' ? 'admin/dashboard.php' : 'client/dashboard.php'); ?>"
                        class="back-btn">
                        Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const userId = <?php echo $userId; ?>;
        const userRole = '<?php echo $userRole; ?>';
        const chatPartnerId = <?php echo $chatPartnerId ?? 'null'; ?>;
        let lastMessageId = 0;
        let pollInterval;
        let messageElements = {}; // Track message DOM elements by ID

        // Load messages
        function loadMessages(loadAll = false) {
            if (!chatPartnerId) {
                console.log('No chat partner ID - skipping message load');
                return;
            }

            const lastIdParam = loadAll ? 0 : lastMessageId;
            const url = `${BASE_URL}chats/ajax_chat.php?action=get_messages&partner_id=${chatPartnerId}&last_id=${lastIdParam}`;
            console.log('Fetching messages from:', url);

            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Messages loaded:', data); // Debug log
                    if (data.success && data.messages && data.messages.length > 0) {
                        const container = document.getElementById('messagesContainer');
                        const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;

                        data.messages.forEach(msg => {
                            // Check if message already exists
                            if (messageElements[msg.id]) {
                                // Update existing message read status
                                updateMessageReadStatus(msg.id, msg.is_read);
                            } else {
                                // Create new message element
                                const messageDiv = document.createElement('div');
                                messageDiv.className = `message ${msg.sender_id == userId ? 'sent' : 'received'}`;
                                messageDiv.dataset.messageId = msg.id;

                                const contentDiv = document.createElement('div');
                                contentDiv.className = 'message-content';
                                contentDiv.textContent = msg.message;

                                const timeDiv = document.createElement('div');
                                timeDiv.className = 'message-time';

                                // Add read status icon for sent messages
                                if (msg.sender_id == userId) {
                                    const readStatus = document.createElement('span');
                                    readStatus.className = `read-status ${msg.is_read ? 'read' : 'sent'}`;
                                    readStatus.innerHTML = msg.is_read ? '✓✓' : '✓';
                                    readStatus.title = msg.is_read ? 'Read' : 'Sent';
                                    readStatus.dataset.readStatus = 'icon';
                                    timeDiv.appendChild(readStatus);
                                }

                                const timeText = document.createTextNode(msg.time);
                                timeDiv.appendChild(timeText);

                                messageDiv.appendChild(contentDiv);
                                messageDiv.appendChild(timeDiv);
                                container.appendChild(messageDiv);

                                // Store reference to message element
                                messageElements[msg.id] = messageDiv;
                            }

                            lastMessageId = Math.max(lastMessageId, msg.id);
                        });

                        if (isAtBottom) {
                            container.scrollTop = container.scrollHeight;
                        }

                        // Mark messages as read
                        markAsRead();
                    } else {
                        console.log('No messages or error:', data); // Debug log
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
        }

        // Update read status of existing message
        function updateMessageReadStatus(messageId, isRead) {
            const messageElement = messageElements[messageId];
            if (!messageElement) return;

            const readStatusIcon = messageElement.querySelector('[data-read-status="icon"]');
            if (!readStatusIcon) return;

            // Only update if status changed
            const currentIsRead = readStatusIcon.classList.contains('read');
            if (currentIsRead !== isRead) {
                if (isRead) {
                    readStatusIcon.classList.remove('sent');
                    readStatusIcon.classList.add('read');
                    readStatusIcon.innerHTML = '✓✓';
                    readStatusIcon.title = 'Read';
                } else {
                    readStatusIcon.classList.remove('read');
                    readStatusIcon.classList.add('sent');
                    readStatusIcon.innerHTML = '✓';
                    readStatusIcon.title = 'Sent';
                }
            }
        }

        // Check read status of recent messages
        function checkReadStatus() {
            if (!chatPartnerId || lastMessageId === 0) return;

            // Only check unread sent messages for efficiency
            const unreadMessages = Object.values(messageElements).filter(el => {
                const readIcon = el.querySelector('[data-read-status="icon"]');
                return el.classList.contains('sent') && readIcon && readIcon.classList.contains('sent');
            });

            if (unreadMessages.length === 0) return; // Nothing to check

            // Get only the unread message IDs
            const msgIds = unreadMessages.map(el => parseInt(el.dataset.messageId));
            const minId = Math.min(...msgIds);

            fetch(`${BASE_URL}chats/ajax_chat.php?action=get_messages&partner_id=${chatPartnerId}&last_id=${minId - 1}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            if (messageElements[msg.id] && msg.sender_id == userId) {
                                updateMessageReadStatus(msg.id, msg.is_read);
                            }
                        });
                    }
                })
                .catch(error => console.error('Error checking read status:', error));
        }

        // Send message
        document.getElementById('messageForm')?.addEventListener('submit', function (e) {
            e.preventDefault();

            const input = document.getElementById('messageInput');
            const message = input.value.trim();

            if (!message || !chatPartnerId) return;

            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', chatPartnerId);
            formData.append('message', message);

            fetch(`${BASE_URL}chats/ajax_chat.php`, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        input.value = '';
                        loadMessages();
                        // Refresh lists and unread counters immediately
                        updateUnreadCount();
                        if (userRole === 'admin') {
                            loadUserList();
                        }
                    } else {
                        alert('Failed to send message: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    alert('Failed to send message');
                })
                .finally(() => {
                    sendBtn.disabled = false;
                    input.focus();
                });
        });

        // Mark messages as read
        function markAsRead() {
            if (!chatPartnerId) return;

            fetch(`${BASE_URL}chats/ajax_chat.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=mark_read&partner_id=${chatPartnerId}`
            })
                .then(() => {
                    // Keep unread counters and admin list in sync
                    updateUnreadCount();
                    if (userRole === 'admin') {
                        loadUserList();
                    }
                })
                .catch(error => console.error('Error marking as read:', error));
        }

        // Load user list (Admin only)
        function loadUserList() {
            if (userRole !== 'admin') return;

            const searchQuery = document.getElementById('searchUsers')?.value || '';
            const url = `${BASE_URL}chats/ajax_chat.php?action=get_users&search=${encodeURIComponent(searchQuery)}`;
            console.log('Loading users from:', url, 'Search:', searchQuery);

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log('Users loaded:', data);
                    if (data.success) {
                        const userList = document.getElementById('userList');
                        userList.innerHTML = '';

                        if (!data.users || data.users.length === 0) {
                            userList.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">No clients found</div>';
                            return;
                        }

                        data.users.forEach(user => {
                            const userItem = document.createElement('a');
                            userItem.className = `user-item ${user.id == chatPartnerId ? 'active' : ''}`;
                            userItem.href = `${BASE_URL}chats/chats.php?user_id=${user.id}`;

                            // Determine online status
                            const isOnline = user.is_online === true || user.is_online === 'active';
                            const statusDot = isOnline ?
                                '<span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #28a745; margin-right: 4px;"></span>' :
                                '<span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #adb5bd; margin-right: 4px;"></span>';

                            userItem.innerHTML = `
                                <div class="user-avatar">${user.name.charAt(0).toUpperCase()}</div>
                                <div class="user-info">
                                    <div class="user-name">
                                        ${statusDot}
                                        ${user.name}
                                    </div>
                                    <div class="user-preview">${user.last_message || 'No messages yet'}</div>
                                    <div class="user-meta">
                                        ${user.last_message_time ? `<span class="last-message-time">${user.last_message_time}</span>` : ''}
                                    </div>
                                </div>
                                ${user.unread_count > 0 ? `<div class="unread-badge">${user.unread_count}</div>` : ''}
                            `;

                            userList.appendChild(userItem);
                        });
                    } else {
                        console.error('Failed to load users:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                });
        }

        // Update unread count in title
        function updateUnreadCount() {
            fetch(`${BASE_URL}chats/ajax_chat.php?action=get_unread_count`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.count > 0) {
                        document.title = `(${data.count}) Chat - MUPWA`;
                    } else {
                        document.title = 'Chat - MUPWA';
                    }
                })
                .catch(error => console.error('Error getting unread count:', error));
        }

        // Search users
        document.getElementById('searchUsers')?.addEventListener('input', function () {
            loadUserList();
        });

        // Initialize
        if (chatPartnerId) {
            loadMessages(true); // Load all messages initially
            markAsRead();
            // Load partner status initially and keep it fresh
            loadPartnerStatus();
            // Poll for new messages every 500ms (very fast updates)
            pollInterval = setInterval(() => {
                loadMessages();
                updateUnreadCount();
            }, 500);
            // Check read status updates every 300ms for near-instant read receipts
            setInterval(checkReadStatus, 300);
            // Refresh partner status every 5 seconds
            setInterval(loadPartnerStatus, 5000);
        }

        if (userRole === 'admin') {
            loadUserList();
            // Update user list every 1 second
            setInterval(loadUserList, 1000);
        }

        // Update unread count periodically (every 500ms)
        setInterval(updateUnreadCount, 500);

        // Load partner online/offline status
        function loadPartnerStatus() {
            if (!chatPartnerId) return;
            fetch(`${BASE_URL}chats/ajax_chat.php?action=get_user_status&user_id=${chatPartnerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const dot = document.getElementById('partnerStatusDot');
                        const text = document.getElementById('partnerStatusText');
                        if (dot && text) {
                            dot.classList.toggle('status-online', !!data.online);
                            dot.classList.toggle('status-offline', !data.online);
                            text.textContent = data.online ? 'Online' : 'Offline';
                        }
                    }
                })
                .catch(() => { });
        }

        // Keep current user status active
        function keepAlive() {
            fetch(`${BASE_URL}auth/keep_alive.php`)
                .catch(() => { });
        }

        // Keep alive every 30 seconds
        if (chatPartnerId) {
            keepAlive();
            setInterval(keepAlive, 30000);
        }
    </script>
</body>

</html>