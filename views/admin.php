<?php
// Admin: read-only monitoring. Shows global stats across all users.
$adminSendersCount = (int) $pdo->query('SELECT COUNT(*) FROM sender_accounts')->fetchColumn();
$adminTemplatesCount = (int) $pdo->query('SELECT COUNT(*) FROM email_design_templates')->fetchColumn();
$adminApiKeysCount = (int) $pdo->query('SELECT COUNT(*) FROM api_keys')->fetchColumn();
$pendingUsersStmt = $pdo->query('SELECT id, name, email, created_at FROM users WHERE COALESCE(is_admin, 0) = 0 AND COALESCE(is_approved, 0) = 0 ORDER BY created_at ASC');
$pendingUsers = $pendingUsersStmt->fetchAll(PDO::FETCH_ASSOC);
$allUsersStmt = $pdo->query('SELECT id, name, email, COALESCE(is_admin, 0) AS is_admin, COALESCE(is_approved, 0) AS is_approved, COALESCE(is_active, 1) AS is_active, created_at FROM users ORDER BY created_at DESC');
$allUsers = $allUsersStmt->fetchAll(PDO::FETCH_ASSOC);
$pendingUsersCount = count($pendingUsers);
$inactiveUsersCount = 0;
foreach ($allUsers as $uRow) {
    if (empty($uRow['is_admin']) && empty($uRow['is_active'])) {
        $inactiveUsersCount++;
    }
}
$adminNotificationCount = $pendingUsersCount + $inactiveUsersCount;

$adminCampaignsStmt = $pdo->query('SELECT id, subject, status, total_recipients, sent_count, failed_count, created_at FROM email_campaigns ORDER BY id DESC LIMIT 15');
$adminCampaigns = $adminCampaignsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    main > div > div.mb-4:first-child { display: none; }
    main > div.max-w-6xl { max-width: none !important; padding: 0 !important; margin: 0 !important; }
    main > div.max-w-6xl > div.mb-4 {
        max-width: 72rem;
        margin-left: auto;
        margin-right: auto;
        margin-top: 1.5rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    @media (min-width: 640px) { main > div.max-w-6xl > div.mb-4 { padding-left: 1.5rem; padding-right: 1.5rem; } }
    @media (min-width: 1024px) { main > div.max-w-6xl > div.mb-4 { padding-left: 2rem; padding-right: 2rem; } }
    .admin-banner { margin-bottom: 2rem; }
</style>

<!-- Admin Banner (Desktop) -->
<div class="admin-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-visible hidden lg:block">
    <div class="max-w-6xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Admin</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Monitor senders, design, API, and campaign activity.</p>
        </div>
        <div class="relative z-10 self-start md:self-auto">
            <button id="adminNotifBtn" type="button"
                class="relative z-30 inline-flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 transition-colors">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9">
                    </path>
                </svg>
                <?php if ($adminNotificationCount > 0): ?>
                <span
                    class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold leading-[18px] text-center"><?= (int)$adminNotificationCount ?></span>
                <?php endif; ?>
            </button>

            <div id="adminNotifMenu"
                class="hidden absolute right-0 mt-2 w-80 z-40 bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                    <p class="text-sm font-bold text-slate-800">Notifications</p>
                </div>
                <div class="divide-y divide-slate-100">
                    <a href="<?= url('registrations') ?>" class="block px-4 py-3 hover:bg-slate-50">
                        <p class="text-sm font-semibold text-slate-800">New registrations</p>
                        <p class="text-xs text-slate-500 mt-0.5"><?= (int)$pendingUsersCount ?> pending approval</p>
                    </a>
                    <a href="<?= url('users') ?>" class="block px-4 py-3 hover:bg-slate-50">
                        <p class="text-sm font-semibold text-slate-800">Inactive accounts</p>
                        <p class="text-xs text-slate-500 mt-0.5"><?= (int)$inactiveUsersCount ?> inactive users</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<div class="lg:hidden bg-[#141d2e] px-4 py-3 text-white mb-2">
    <h1 class="text-xl font-bold">Admin</h1>
    <p class="text-blue-100/80 text-xs mt-0.5">Monitor only</p>
</div>

<script>
(function() {
    const btn = document.getElementById('adminNotifBtn');
    const menu = document.getElementById('adminNotifMenu');
    if (!btn || !menu) return;

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.classList.toggle('hidden');
    });

    document.addEventListener('click', function(e) {
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
})();
</script>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
    <!-- Stats -->
    <div class="grid grid-cols-3 sm:grid-cols-3 gap-2 sm:gap-4 mb-6 sm:mb-8">
        <div class="bg-white rounded-xl shadow border border-slate-100 p-3 sm:p-5">
            <div class="flex flex-col items-center text-center gap-1 sm:flex-row sm:items-center sm:text-left sm:gap-4">
                <div class="w-9 h-9 sm:w-12 sm:h-12 bg-slate-100 rounded-xl flex items-center justify-center text-[#02396E] shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase leading-tight">Senders</p>
                    <p class="text-base sm:text-2xl font-black text-slate-900 leading-none mt-0.5"><?= $adminSendersCount ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow border border-slate-100 p-3 sm:p-5">
            <div class="flex flex-col items-center text-center gap-1 sm:flex-row sm:items-center sm:text-left sm:gap-4">
                <div class="w-9 h-9 sm:w-12 sm:h-12 bg-slate-100 rounded-xl flex items-center justify-center text-[#02396E] shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase leading-tight">Templates</p>
                    <p class="text-base sm:text-2xl font-black text-slate-900 leading-none mt-0.5"><?= $adminTemplatesCount ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow border border-slate-100 p-3 sm:p-5">
            <div class="flex flex-col items-center text-center gap-1 sm:flex-row sm:items-center sm:text-left sm:gap-4">
                <div class="w-9 h-9 sm:w-12 sm:h-12 bg-slate-100 rounded-xl flex items-center justify-center text-[#02396E] shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase leading-tight">API keys</p>
                    <p class="text-base sm:text-2xl font-black text-slate-900 leading-none mt-0.5"><?= $adminApiKeysCount ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent campaigns (read-only) -->
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/10">
            <h2 class="text-lg md:text-2xl font-bold text-white">Recent campaigns (monitor only)</h2>
        </div>
        <!-- Mobile cards -->
        <div class="md:hidden divide-y divide-slate-100">
            <?php if (empty($adminCampaigns)): ?>
                <div class="px-4 py-10 text-slate-500 text-center">No campaigns yet.</div>
            <?php else: ?>
                <?php foreach ($adminCampaigns as $c): ?>
                    <?php
                        $status = (string)($c['status'] ?? '');
                        $statusLower = strtolower($status);
                        $statusClass = $statusLower === 'completed'
                            ? 'bg-emerald-100 text-emerald-800'
                            : ($statusLower === 'sending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700');
                        $created = $c['created_at'] ? date('M j, Y', strtotime($c['created_at'])) : '—';
                    ?>
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-black text-slate-900 leading-snug line-clamp-2"><?= h($c['subject']) ?></div>
                                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Created: <?= h($created) ?></div>
                            </div>
                            <span class="shrink-0 px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest <?= $statusClass ?>"><?= h($status) ?></span>
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 text-center">
                                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Recipients</div>
                                <div class="text-sm font-black text-slate-900 mt-0.5"><?= (int)$c['total_recipients'] ?></div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 text-center">
                                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Sent</div>
                                <div class="text-sm font-black text-emerald-700 mt-0.5"><?= (int)$c['sent_count'] ?></div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 text-center">
                                <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Failed</div>
                                <div class="text-sm font-black text-red-600 mt-0.5"><?= (int)$c['failed_count'] ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Desktop/tablet table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 font-bold text-slate-600">Subject</th>
                        <th class="px-4 py-3 font-bold text-slate-600">Status</th>
                        <th class="px-4 py-3 font-bold text-slate-600">Recipients</th>
                        <th class="px-4 py-3 font-bold text-slate-600">Sent</th>
                        <th class="px-4 py-3 font-bold text-slate-600">Failed</th>
                        <th class="px-4 py-3 font-bold text-slate-600">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($adminCampaigns)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-slate-500 text-center">No campaigns yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($adminCampaigns as $c): ?>
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3 font-medium text-slate-900"><?= h($c['subject']) ?></td>
                        <td class="px-4 py-3"><span class="px-2 py-1 rounded-lg text-xs font-bold <?= $c['status'] === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($c['status'] === 'sending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') ?>"><?= h($c['status']) ?></span></td>
                        <td class="px-4 py-3 text-slate-600"><?= (int) $c['total_recipients'] ?></td>
                        <td class="px-4 py-3 text-slate-600"><?= (int) $c['sent_count'] ?></td>
                        <td class="px-4 py-3 text-slate-600"><?= (int) $c['failed_count'] ?></td>
                        <td class="px-4 py-3 text-slate-500"><?= $c['created_at'] ? date('M j, Y', strtotime($c['created_at'])) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden mt-6">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/10">
            <h2 class="text-lg md:text-2xl font-bold text-white">Pending registrations</h2>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($pendingUsers)): ?>
                <div class="px-4 py-8 text-slate-500 text-center">No pending accounts.</div>
            <?php else: ?>
                <?php foreach ($pendingUsers as $u): ?>
                <div class="px-4 md:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-slate-900"><?= h($u['name'] ?: 'No name') ?></div>
                        <div class="text-xs text-slate-600 break-all"><?= h($u['email']) ?></div>
                        <div class="text-[11px] text-slate-500 mt-1">Registered: <?= h($u['created_at'] ? date('M j, Y g:i A', strtotime($u['created_at'])) : '—') ?></div>
                    </div>
                    <form method="post" action="<?= url('admin') ?>" class="shrink-0">
                        <input type="hidden" name="action" value="admin-approve-user">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-lg hover:bg-emerald-700 transition-colors">Approve</button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden mt-6">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/10">
            <h2 class="text-lg md:text-2xl font-bold text-white">All users</h2>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($allUsers)): ?>
                <div class="px-4 py-8 text-slate-500 text-center">No users found.</div>
            <?php else: ?>
                <?php foreach ($allUsers as $u): ?>
                <?php
                    $isAdminUser = !empty($u['is_admin']);
                    $isApproved = !empty($u['is_approved']);
                    $isActive = !empty($u['is_active']);
                    $nextActive = $isActive ? 0 : 1;
                ?>
                <div class="px-4 md:px-8 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
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
                    <div class="flex items-center gap-2">
                        <?php if (!$isAdminUser && !$isApproved): ?>
                        <form method="post" action="<?= url('admin') ?>">
                            <input type="hidden" name="action" value="admin-approve-user">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" class="px-3 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 transition-colors">Approve</button>
                        </form>
                        <?php endif; ?>
                        <?php if (!$isAdminUser): ?>
                        <form method="post" action="<?= url('admin') ?>">
                            <input type="hidden" name="action" value="admin-user-toggle-active">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="set_active" value="<?= $nextActive ?>">
                            <button type="submit" class="px-3 py-2 text-white text-xs font-bold rounded-lg transition-colors <?= $isActive ? 'bg-red-600 hover:bg-red-700' : 'bg-slate-700 hover:bg-slate-800' ?>"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
