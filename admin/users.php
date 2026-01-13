<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$name = htmlspecialchars($_SESSION['name'] ?? '');
$adminId = $_SESSION['user_id'] ?? 0;

// Users will be fetched via AJAX
$users = [];
$totalUsers = 0;
$totalPages = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
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
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Filters */
        .filters {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .filter-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-group input[type="text"] {
            flex: 1;
            min-width: 200px;
        }

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
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

        .badge-primary {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
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

            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group input[type="text"] {
                min-width: 100%;
            }

            table {
                font-size: 12px;
            }

            th,
            td {
                padding: 10px 8px;
            }
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
        }

        .pagination {
            display: flex;
            gap: 5px;
            list-style: none;
        }

        .pagination li {
            margin: 0;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
            font-size: 14px;
        }

        .pagination a:hover {
            background: #f0f0f0;
        }

        .pagination .active span {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination .disabled span {
            color: #999;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .pagination-info {
            color: #666;
            font-size: 14px;
        }

        /* Message Container */
        .message-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            max-width: 400px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease-in-out;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        .message.hide {
            animation: slideOut 0.3s ease-in-out forwards;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Message Container -->
        <div id="messageContainer" class="message-container"></div>

        <!-- Header -->
        <div class="header">
            <h1>User Management</h1>
            <div class="header-actions">
                <a href="<?php echo url('admin/dashboard.php'); ?>" class="btn btn-secondary">Dashboard</a>
                <button class="btn btn-success" onclick="openAddModal()">Add User</button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <input type="text" id="searchInput" placeholder="Search by name or email..." value="">

                <select id="roleFilter">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="client">Client</option>
                </select>

                <select id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                </select>

                <button type="button" class="btn btn-secondary" onclick="clearFilters()">Clear</button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <div id="usersTableContainer" style="min-height: 300px;">
                <div style="text-align: center; padding: 40px; color: #999;">Loading users...</div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container" id="paginationContainer" style="display: none;">
            <div class="pagination-info" id="paginationInfo"></div>
            <ul class="pagination" id="paginationList"></ul>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">Add New User</div>
            <form id="addUserForm">
                <input type="hidden" name="action" value="add_user">

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="add_name" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="add_email" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="add_password" required minlength="8">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="add_role" required>
                        <option value="client">Client</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="add_status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Create User</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">Edit User</div>
            <form id="editUserForm">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" required>
                        <option value="client">Client</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Modal -->
    <div class="modal" id="statusModal">
        <div class="modal-content">
            <div class="modal-header">Change User Status</div>
            <form id="statusForm">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="user_id" id="status_user_id">

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status_value" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Status</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('editModal').classList.add('active');
        }

        function openStatusModal(userId, currentStatus) {
            document.getElementById('status_user_id').value = userId;
            document.getElementById('status_value').value = currentStatus;
            document.getElementById('statusModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Show message on page
        function showMessage(message, type = 'success', duration = 3000) {
            const container = document.getElementById('messageContainer');
            const messageEl = document.createElement('div');
            messageEl.className = `message ${type}`;
            messageEl.textContent = message;

            container.appendChild(messageEl);

            // Auto-remove after duration
            if (duration > 0) {
                setTimeout(() => {
                    messageEl.classList.add('hide');
                    setTimeout(() => messageEl.remove(), 300);
                }, duration);
            }

            return messageEl;
        }

        // AJAX submit for status change
        document.getElementById('statusForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch(`${BASE_URL}admin/ajax_users.php`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('Status updated successfully');
                    closeModal('statusModal');
                    loadUsers(1);
                } else {
                    showMessage(data.error || 'Failed to update status', 'error');
                }
            } catch (err) {
                console.error(err);
                showMessage('Network error', 'error');
            }
        });

        // AJAX submit for add user
        document.getElementById('addUserForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch(`${BASE_URL}admin/ajax_users.php`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('User created successfully');
                    this.reset();
                    closeModal('addModal');
                    loadUsers(1);
                } else {
                    showMessage(data.error || 'Failed to create user', 'error');
                }
            } catch (err) {
                console.error(err);
                showMessage('Network error', 'error');
            }
        });

        // AJAX submit for edit user
        document.getElementById('editUserForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch(`${BASE_URL}admin/ajax_users.php`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('User updated successfully');
                    closeModal('editModal');
                    loadUsers(1);
                } else {
                    showMessage(data.error || 'Failed to update user', 'error');
                }
            } catch (err) {
                console.error(err);
                showMessage('Network error', 'error');
            }
        });

        // Live search with debouncing
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                console.log('Search input changed:', this.value);
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => loadUsers(1), 300);
            });
        }

        // Filter change listeners
        if (roleFilter) {
            roleFilter.addEventListener('change', () => {
                console.log('Role filter changed');
                loadUsers(1);
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                console.log('Status filter changed');
                loadUsers(1);
            });
        }

        // Load users via AJAX
        function loadUsers(page = 1) {
            const search = document.getElementById('searchInput').value;
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;

            console.log('Loading users - Search:', search, 'Role:', role, 'Status:', status, 'Page:', page);

            const params = new URLSearchParams({
                action: 'get_users',
                search: search,
                role: role,
                status: status,
                page: page
            });

            console.log('URL:', `${BASE_URL}admin/ajax_users.php?${params}`);

            fetch(`${BASE_URL}admin/ajax_users.php?${params}`)
                .then(r => r.json())
                .then(data => {
                    console.log('Response:', data);
                    if (!data.success) throw new Error(data.error || 'Failed to load users');
                    renderUsers(data.data.users, data.data.pagination);
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('usersTableContainer').innerHTML = `
                        <div class="empty-state"><p>Failed to load users</p></div>
                    `;
                });
        }

        // Render users table
        function renderUsers(users, pagination) {
            const container = document.getElementById('usersTableContainer');
            const paginationContainer = document.getElementById('paginationContainer');
            const paginationInfo = document.getElementById('paginationInfo');
            const paginationList = document.getElementById('paginationList');

            if (!users || users.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>No users found</p></div>';
                paginationContainer.style.display = 'none';
                return;
            }

            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Online Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            users.forEach(user => {
                const roleClass = user.role === 'admin' ? 'danger' : 'primary';
                const statusClass = user.status === 'active' ? 'success' : (user.status === 'pending' ? 'warning' : 'danger');
                const onlineClass = user.message_status === 'active' ? 'success' : 'secondary';
                const onlineText = user.message_status === 'active' ? 'Online' : 'Offline';

                html += `
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.name}</td>
                        <td>${user.email}</td>
                        <td><span class="badge badge-${roleClass}">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span></td>
                        <td><span class="badge badge-${statusClass}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</span></td>
                        <td><span class="badge badge-${onlineClass}">${onlineText}</span></td>
                        <td>${user.last_login}</td>
                        <td>${user.created_at}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick='openEditModal(${JSON.stringify(user)})'>Edit</button>
                            <button class="btn btn-secondary btn-sm" onclick="openStatusModal(${user.id}, '${user.status}')">Status</button>
                            ${user.id !== <?php echo $adminId; ?> ? `<button class="btn btn-danger btn-sm" onclick="confirmDelete(${user.id}, '${user.name}')">Delete</button>` : ''}
                        </td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            container.innerHTML = html;

            // Render pagination
            if (pagination.total_users > 0) {
                paginationInfo.textContent = `Showing ${pagination.offset + 1} - ${Math.min(pagination.offset + pagination.per_page, pagination.total_users)} of ${pagination.total_users} users`;

                let paginationHtml = '';

                // First button
                if (pagination.current_page > 1) {
                    paginationHtml += `<li><a href="javascript:loadUsers(1)">First</a></li>`;
                    paginationHtml += `<li><a href="javascript:loadUsers(${pagination.current_page - 1})">Previous</a></li>`;
                } else {
                    paginationHtml += `<li class="disabled"><span>First</span></li>`;
                    paginationHtml += `<li class="disabled"><span>Previous</span></li>`;
                }

                // Page numbers
                const startPage = Math.max(1, pagination.current_page - 2);
                const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

                if (startPage > 1) {
                    paginationHtml += `<li><a href="javascript:loadUsers(1)">1</a></li>`;
                    if (startPage > 2) paginationHtml += `<li><span>...</span></li>`;
                }

                for (let i = startPage; i <= endPage; i++) {
                    if (i === pagination.current_page) {
                        paginationHtml += `<li class="active"><span>${i}</span></li>`;
                    } else {
                        paginationHtml += `<li><a href="javascript:loadUsers(${i})">${i}</a></li>`;
                    }
                }

                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) paginationHtml += `<li><span>...</span></li>`;
                    paginationHtml += `<li><a href="javascript:loadUsers(${pagination.total_pages})">${pagination.total_pages}</a></li>`;
                }

                // Next button
                if (pagination.current_page < pagination.total_pages) {
                    paginationHtml += `<li><a href="javascript:loadUsers(${pagination.current_page + 1})">Next</a></li>`;
                    paginationHtml += `<li><a href="javascript:loadUsers(${pagination.total_pages})">Last</a></li>`;
                } else {
                    paginationHtml += `<li class="disabled"><span>Next</span></li>`;
                    paginationHtml += `<li class="disabled"><span>Last</span></li>`;
                }

                paginationList.innerHTML = paginationHtml;
                paginationContainer.style.display = 'flex';
            } else {
                paginationContainer.style.display = 'none';
            }
        }

        // Clear filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('roleFilter').value = '';
            document.getElementById('statusFilter').value = '';
            loadUsers(1);
        }

        function confirmDelete(userId, userName) {
            if (confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
                deleteUser(userId);
            }
        }

        function deleteUser(userId) {
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', userId);

            fetch(`${BASE_URL}admin/ajax_users.php`, {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showMessage('User deleted successfully');
                        loadUsers(1);
                    } else {
                        showMessage(data.error || 'Failed to delete user', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showMessage('Network error', 'error');
                });
        }

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Load users on page load
        loadUsers(1);

        // Auto-refresh users every 5 seconds
        setInterval(() => loadUsers(1), 5000);
    </script>
</body>

</html>