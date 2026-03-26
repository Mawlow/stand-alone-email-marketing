<?php
$allUsersStmt = $pdo->query('SELECT id, name, email, COALESCE(is_admin, 0) AS is_admin, COALESCE(is_approved, 0) AS is_approved, COALESCE(is_active, 1) AS is_active, created_at FROM users ORDER BY created_at DESC');
$allUsers = $allUsersStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    main > div > div.mb-4:first-child { display: none; }
    main > div.max-w-6xl { max-width: none !important; padding: 0 !important; margin: 0 !important; }
    .users-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        border-radius: 1rem;
    }
    .users-header {
        background: #141d2e;
    }
    .user-row {
        transition: background-color .18s ease, transform .18s ease;
    }
    .user-row:hover {
        background-color: #f8fafc;
    }
    .user-avatar {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 9999px;
        background: linear-gradient(135deg, #1d4ed8, #3b82f6);
        color: #fff;
        font-weight: 800;
        font-size: .75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
</style>

<div class="bg-[#141d2e] text-white shadow-lg relative overflow-hidden hidden lg:block mb-4 min-h-[97px] box-border border-b border-slate-700/50 flex items-center">
    <div class="max-w-6xl mx-auto w-full px-3 sm:px-4 md:px-6 lg:px-8 py-3">
        <div class="relative z-10 min-w-0 pt-2 md:pt-3">
            <h1 class="text-xl font-bold leading-tight md:text-2xl">Users</h1>
            <p class="text-blue-100/80 mt-0.5 text-xs font-medium md:text-sm leading-snug">Manage all user accounts.</p>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<div class="lg:hidden bg-[#141d2e] px-6 py-6 text-white mb-2 border-b border-slate-700/50 flex flex-col justify-center min-h-[97px] box-border">
    <h1 class="text-xl font-bold leading-tight pt-2 md:pt-3">Users</h1>
    <p class="text-blue-100/80 text-xs mt-1 leading-snug">Manage accounts</p>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
    <div class="users-shell p-2 sm:p-3 shadow border border-slate-200/70">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="users-header px-4 md:px-8 py-4 md:py-6 border-b border-slate-700/50">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h2 class="text-lg md:text-2xl font-bold text-white">All users</h2>
                    <p class="text-xs md:text-sm text-blue-100/80 mt-1">Manage approvals, activation state, and account lifecycle</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2">
                    <input id="usersFilterSearch" type="text" placeholder="Search name or email"
                        class="users-filter-input w-full sm:w-56 rounded-lg border border-slate-200 bg-white text-slate-900 placeholder-slate-400 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
                    <select id="usersFilterStatus"
                        class="users-filter-select w-full sm:w-44 rounded-lg border border-slate-200 bg-white text-slate-900 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
                        <option value="all" class="text-slate-900">All</option>
                        <option value="active" class="text-slate-900">Active</option>
                        <option value="inactive" class="text-slate-900">Inactive</option>
                        <option value="pending" class="text-slate-900">Pending</option>
                        <option value="approved" class="text-slate-900">Approved</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($allUsers)): ?>
                <div class="px-4 py-12 text-slate-500 text-center">
                    <p class="text-sm font-semibold">No users found.</p>
                    <p class="text-xs mt-1">New accounts will appear here when users register.</p>
                </div>
            <?php else: ?>
                <?php foreach ($allUsers as $u): ?>
                <?php
                    $isAdminUser = !empty($u['is_admin']);
                    $isApproved = !empty($u['is_approved']);
                    $isActive = !empty($u['is_active']);
                    $nextActive = $isActive ? 0 : 1;
                ?>
                <div class="user-row px-4 md:px-8 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3"
                    data-name="<?= h(strtolower((string)($u['name'] ?: ''))) ?>"
                    data-email="<?= h(strtolower((string)$u['email'])) ?>"
                    data-approved="<?= $isApproved ? '1' : '0' ?>"
                    data-active="<?= $isActive ? '1' : '0' ?>">
                    <div class="min-w-0 flex items-start gap-3">
                        <div class="user-avatar"><?= h(strtoupper(substr((string)($u['name'] ?: $u['email']), 0, 1))) ?></div>
                        <div class="min-w-0">
                        <div class="text-sm font-bold text-slate-900"><?= h($u['name'] ?: 'No name') ?></div>
                        <div class="text-xs text-slate-600 break-all"><?= h($u['email']) ?></div>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $isAdminUser ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-700' ?>"><?= $isAdminUser ? 'Admin' : 'User' ?></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $isApproved ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>"><?= $isApproved ? 'Approved' : 'Pending' ?></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">Registered: <?= h($u['created_at'] ? date('M j, Y g:i A', strtotime($u['created_at'])) : '—') ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <?php if (!$isAdminUser && !$isApproved): ?>
                        <form method="post" action="<?= url('users') ?>">
                            <input type="hidden" name="action" value="admin-approve-user">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="return_page" value="users">
                            <button type="submit" class="px-3 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">Approve</button>
                        </form>
                        <?php endif; ?>
                        <?php if (!$isAdminUser): ?>
                        <form method="post" action="<?= url('users') ?>">
                            <input type="hidden" name="action" value="admin-user-toggle-active">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="set_active" value="<?= $nextActive ?>">
                            <input type="hidden" name="return_page" value="users">
                            <button type="submit" class="px-3 py-2 text-white text-xs font-bold rounded-lg transition-colors shadow-sm <?= $isActive ? 'bg-red-600 hover:bg-red-700' : 'bg-slate-700 hover:bg-slate-800' ?>"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                        <form method="post" action="<?= url('users') ?>" class="delete-user-form">
                            <input type="hidden" name="action" value="admin-user-delete">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="return_page" value="users">
                            <button type="button" class="delete-user-btn px-3 py-2 bg-red-700 text-white text-xs font-bold rounded-lg hover:bg-red-800 transition-colors shadow-sm">Delete</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>

<div id="deleteUserModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-slate-900">Delete account</h3>
        <p class="text-sm text-slate-600 mt-2">Are you sure you want to delete this account? This action cannot be undone.</p>
        <div class="mt-5 flex items-center justify-end gap-2">
            <button type="button" id="deleteUserCancel" class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-200">Cancel</button>
            <button type="button" id="deleteUserConfirm" class="px-4 py-2 bg-red-700 text-white text-sm font-bold rounded-lg hover:bg-red-800">Delete</button>
        </div>
    </div>
</div>

<script>
(function() {
    const searchInput = document.getElementById('usersFilterSearch');
    const statusSelect = document.getElementById('usersFilterStatus');
    if (!searchInput || !statusSelect) return;

    const rows = Array.from(document.querySelectorAll('.user-row'));
    const applyFilter = () => {
        const q = (searchInput.value || '').trim().toLowerCase();
        const status = statusSelect.value;
        for (const row of rows) {
            const name = row.dataset.name || '';
            const email = row.dataset.email || '';
            const approved = row.dataset.approved === '1';
            const active = row.dataset.active === '1';
            const matchesText = q === '' || name.includes(q) || email.includes(q);
            let matchesStatus = true;
            if (status === 'active') matchesStatus = active;
            else if (status === 'inactive') matchesStatus = !active;
            else if (status === 'approved') matchesStatus = approved;
            else if (status === 'pending') matchesStatus = !approved;
            row.style.display = (matchesText && matchesStatus) ? '' : 'none';
        }
    };

    searchInput.addEventListener('input', applyFilter);
    statusSelect.addEventListener('change', applyFilter);

    const deleteModal = document.getElementById('deleteUserModal');
    const deleteCancelBtn = document.getElementById('deleteUserCancel');
    const deleteConfirmBtn = document.getElementById('deleteUserConfirm');
    let pendingDeleteForm = null;

    const closeDeleteModal = () => {
        if (!deleteModal) return;
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
        pendingDeleteForm = null;
    };

    const openDeleteModal = () => {
        if (!deleteModal) return;
        deleteModal.classList.remove('hidden');
        deleteModal.classList.add('flex');
    };

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-user-btn');
        if (!btn) return;
        const form = btn.closest('.delete-user-form');
        if (!form) return;
        pendingDeleteForm = form;
        openDeleteModal();
    });

    if (deleteCancelBtn) {
        deleteCancelBtn.addEventListener('click', closeDeleteModal);
    }
    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', function() {
            if (pendingDeleteForm) {
                pendingDeleteForm.submit();
            }
        });
    }
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) closeDeleteModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
})();
</script>
