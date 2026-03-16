<?php
// Admin: read-only monitoring. Shows global stats across all users.
$adminSendersCount = (int) $pdo->query('SELECT COUNT(*) FROM sender_accounts')->fetchColumn();
$adminTemplatesCount = (int) $pdo->query('SELECT COUNT(*) FROM email_design_templates')->fetchColumn();
$adminApiKeysCount = (int) $pdo->query('SELECT COUNT(*) FROM api_keys')->fetchColumn();

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
<div class="admin-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-3 sm:px-4 md:px-6 lg:px-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Admin</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Monitor senders, design, API, and campaign activity.</p>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<div class="lg:hidden bg-[#141d2e] px-4 py-4 text-white mb-4">
    <h1 class="text-xl font-bold">Admin</h1>
    <p class="text-blue-100/80 text-xs mt-0.5">Monitor only</p>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow border border-slate-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-[#02396E]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">Senders</p>
                    <p class="text-2xl font-bold text-slate-900"><?= $adminSendersCount ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow border border-slate-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-[#02396E]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">Templates</p>
                    <p class="text-2xl font-bold text-slate-900"><?= $adminTemplatesCount ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow border border-slate-100 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-[#02396E]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">API keys</p>
                    <p class="text-2xl font-bold text-slate-900"><?= $adminApiKeysCount ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent campaigns (read-only) -->
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/10">
            <h2 class="text-lg md:text-2xl font-bold text-white">Recent campaigns (monitor only)</h2>
        </div>
        <div class="overflow-x-auto">
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
</div>
