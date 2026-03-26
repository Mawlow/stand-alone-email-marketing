<?php
$pendingUsersStmt = $pdo->query('SELECT id, name, email, created_at FROM users WHERE COALESCE(is_admin, 0) = 0 AND COALESCE(is_approved, 0) = 0 ORDER BY created_at ASC');
$pendingUsers = $pendingUsersStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    main > div > div.mb-4:first-child { display: none; }
    main > div.max-w-6xl { max-width: none !important; padding: 0 !important; margin: 0 !important; }
    .reg-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        border-radius: 1rem;
    }
    .reg-header {
        background: #141d2e;
    }
    .reg-row {
        transition: background-color .18s ease;
    }
    .reg-row:hover {
        background-color: #f8fafc;
    }
    .reg-avatar {
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
            <h1 class="text-xl font-bold leading-tight md:text-2xl">Registrations</h1>
            <p class="text-blue-100/80 mt-0.5 text-xs font-medium md:text-sm leading-snug">Approve and manage user accounts.</p>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<div class="lg:hidden bg-[#141d2e] px-6 py-6 text-white mb-2 border-b border-slate-700/50 flex flex-col justify-center min-h-[97px] box-border">
    <h1 class="text-xl font-bold leading-tight pt-2 md:pt-3">Registrations</h1>
    <p class="text-blue-100/80 text-xs mt-1 leading-snug">Approve and manage users</p>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
    <div class="reg-shell p-2 sm:p-3 shadow border border-slate-200/70">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="reg-header px-4 md:px-8 py-4 md:py-6 border-b border-slate-700/50">
            <h2 class="text-lg md:text-2xl font-bold text-white">Pending registrations</h2>
            <p class="text-xs md:text-sm text-blue-100/80 mt-1">Review and approve new accounts</p>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($pendingUsers)): ?>
                <div class="px-4 py-12 text-slate-500 text-center">
                    <p class="text-sm font-semibold">No pending accounts.</p>
                    <p class="text-xs mt-1">New registration requests will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pendingUsers as $u): ?>
                <div class="reg-row px-4 md:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0 flex items-start gap-3">
                        <div class="reg-avatar"><?= h(strtoupper(substr((string)($u['name'] ?: $u['email']), 0, 1))) ?></div>
                        <div class="min-w-0">
                        <div class="text-sm font-bold text-slate-900"><?= h($u['name'] ?: 'No name') ?></div>
                        <div class="text-xs text-slate-600 break-all"><?= h($u['email']) ?></div>
                        <div class="mt-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">Pending</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">Registered: <?= h($u['created_at'] ? date('M j, Y g:i A', strtotime($u['created_at'])) : '—') ?></div>
                        </div>
                    </div>
                    <form method="post" action="<?= url('registrations') ?>" class="shrink-0">
                        <input type="hidden" name="action" value="admin-approve-user">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="return_page" value="registrations">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-bold rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">Approve</button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    </div>

</div>
