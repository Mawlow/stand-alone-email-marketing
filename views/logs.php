<?php
$statusFilter = $_GET['status'] ?? '';
$campaignFilter = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : 0;
$openedFilter = $_GET['opened'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$q = 'SELECT l.*, c.subject as campaign_subject, c.sent_count as campaign_sent, c.failed_count as campaign_failed FROM email_logs l LEFT JOIN email_campaigns c ON c.id = l.email_campaign_id WHERE 1=1';
$params = [];

if ($statusFilter !== '') { $q .= ' AND l.status = ?'; $params[] = $statusFilter; }
if ($campaignFilter > 0) { $q .= ' AND l.email_campaign_id = ?'; $params[] = $campaignFilter; }
if ($openedFilter === '1') { $q .= ' AND l.opened_at IS NOT NULL'; }
if ($openedFilter === '0') { $q .= ' AND l.opened_at IS NULL'; }
if ($searchQuery !== '') {
    $q .= ' AND (l.recipient_email LIKE ? OR c.subject LIKE ?)';
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$q .= ' ORDER BY l.id DESC LIMIT 100';
$stmt = $pdo->prepare($q);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$campaigns = $pdo->query('SELECT id, subject FROM email_campaigns ORDER BY id DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Force the parent container to be full width */
    main > div.max-w-6xl {
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* Style flash messages */
    main > div.max-w-6xl > div.mb-4 {
        max-width: 72rem; /* 6xl */
        margin-left: auto;
        margin-right: auto;
        margin-top: 1.5rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    @media (min-width: 640px) { main > div.max-w-6xl > div.mb-4 { padding-left: 1.5rem; padding-right: 1.5rem; } }
    @media (min-width: 1024px) { main > div.max-w-6xl > div.mb-4 { padding-left: 2rem; padding-right: 2rem; } }

    .logs-banner {
        margin-bottom: 2rem;
    }
</style>

<!-- Logs Banner -->
<div class="logs-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Campaign Logs</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Track every delivery and engagement in real-time.</p>
        </div>
        <!-- Search Bar -->
        <form method="get" action="<?= url('logs') ?>" class="relative z-10 w-full md:w-[400px]">
            <input type="hidden" name="page" value="logs">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= h($statusFilter) ?>"><?php endif; ?>
            <?php if ($campaignFilter): ?><input type="hidden" name="campaign_id" value="<?= (int)$campaignFilter ?>"><?php endif; ?>
            <?php if ($openedFilter !== ''): ?><input type="hidden" name="opened" value="<?= h($openedFilter) ?>"><?php endif; ?>
            
            <div class="relative group">
                <input type="text" name="search" id="logsSearch" placeholder="Search recipients or campaigns..." value="<?= h($searchQuery) ?>" class="w-full bg-white rounded-xl py-3 pl-12 pr-20 text-slate-900 text-base placeholder-slate-400 focus:outline-none transition-all shadow-inner border-none">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                
                <!-- Clear Button (X) -->
                <button type="button" id="clearLogsSearch" class="absolute right-12 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 <?= empty($searchQuery) ? 'hidden' : '' ?>" title="Clear search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Search Arrow Button -->
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 bg-[#02396E] text-white rounded-full shadow-md" title="Search now">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </button>
            </div>
        </form>
    </div>
    <!-- Decorative element -->
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#141d2e] px-4 pt-4 pb-6 text-white mb-2">
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Campaign Logs</h1>
        </div>
        <p class="text-blue-100/80 text-xs">Track every delivery and engagement in real-time.</p>
        <form method="get" action="<?= url('logs') ?>" class="relative">
            <input type="hidden" name="page" value="logs">
            <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= h($statusFilter) ?>"><?php endif; ?>
            <?php if ($campaignFilter): ?><input type="hidden" name="campaign_id" value="<?= (int)$campaignFilter ?>"><?php endif; ?>
            <?php if ($openedFilter !== ''): ?><input type="hidden" name="opened" value="<?= h($openedFilter) ?>"><?php endif; ?>
            
            <input type="text" name="search" id="logsSearchMobile" placeholder="Search recipients or campaigns..." value="<?= h($searchQuery) ?>" class="w-full bg-white rounded-lg py-2 pl-10 pr-16 text-slate-900 text-sm placeholder-slate-400 focus:outline-none">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            
            <button type="button" id="clearLogsSearchMobile" class="absolute right-10 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600 <?= empty($searchQuery) ? 'hidden' : '' ?>" title="Clear search">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 bg-[#02396E] text-white rounded-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const setupSearch = (input, clear) => {
        if (!input || !clear) return;
        input.addEventListener('input', () => {
            if (input.value.length > 0) clear.classList.remove('hidden');
            else clear.classList.add('hidden');
        });
        clear.addEventListener('click', () => {
            input.value = '';
            clear.classList.add('hidden');
            input.closest('form').submit();
        });
    };
    setupSearch(document.getElementById('logsSearch'), document.getElementById('clearLogsSearch'));
    setupSearch(document.getElementById('logsSearchMobile'), document.getElementById('clearLogsSearchMobile'));
});
</script>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 text-left">
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 border-b border-white/10 flex flex-col lg:flex-row justify-between lg:items-center gap-4">
            <h2 class="text-xl font-bold text-white shrink-0">Activities</h2>
            
            <!-- Proper Mobile Grid Layout for Filters -->
            <form method="get" action="<?= url('logs') ?>" class="flex flex-col lg:flex-row gap-3 w-full lg:w-auto mt-4 lg:mt-0">
                <input type="hidden" name="page" value="logs">
                <?php if ($searchQuery): ?><input type="hidden" name="search" value="<?= h($searchQuery) ?>"><?php endif; ?>
                
                <div class="grid grid-cols-2 lg:flex lg:flex-row gap-3 lg:gap-2 w-full lg:w-auto">
                    <!-- Status & Opened -->
                    <select name="status" class="w-full lg:w-auto bg-white/10 border border-white/20 rounded-lg px-2 py-2.5 lg:py-1.5 text-sm lg:text-xs font-medium text-white focus:ring-2 focus:ring-[#ff8904] focus:outline-none cursor-pointer hover:bg-white/20 transition-colors">
                        <option value="" class="text-slate-900">All Status</option>
                        <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?> class="text-slate-900">Sent</option>
                        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?> class="text-slate-900">Failed</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?> class="text-slate-900">Pending</option>
                    </select>

                    <select name="opened" class="w-full lg:w-auto bg-white/10 border border-white/20 rounded-lg px-2 py-2.5 lg:py-1.5 text-sm lg:text-xs font-medium text-white focus:ring-2 focus:ring-[#ff8904] focus:outline-none cursor-pointer hover:bg-white/20 transition-colors">
                        <option value="" class="text-slate-900">Open Status</option>
                        <option value="1" <?= $openedFilter === '1' ? 'selected' : '' ?> class="text-slate-900">Opened</option>
                        <option value="0" <?= $openedFilter === '0' ? 'selected' : '' ?> class="text-slate-900">Unopened</option>
                    </select>

                    <!-- Campaign (Col-Span-2 on mobile) -->
                    <select name="campaign_id" class="col-span-2 lg:col-span-1 w-full lg:w-auto bg-white/10 border border-white/20 rounded-lg px-2 py-2.5 lg:py-1.5 text-sm lg:text-xs font-medium text-white focus:ring-2 focus:ring-[#ff8904] focus:outline-none cursor-pointer hover:bg-white/20 transition-colors lg:max-w-[220px]">
                        <option value="" class="text-slate-900">All Campaigns</option>
                        <?php foreach ($campaigns as $co): ?>
                            <option value="<?= (int)$co['id'] ?>" <?= $campaignFilter === (int)$co['id'] ? 'selected' : '' ?> class="text-slate-900">
                                <?= h(mb_substr($co['subject'], 0, 30)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Button -->
                <button type="submit" class="w-full lg:w-auto px-4 py-2.5 lg:py-1.5 bg-[#ff8904] text-white text-sm lg:text-xs font-bold rounded-lg hover:bg-orange-600 transition-colors shadow-sm">Filter</button>
            </form>
        </div>
        <!-- Mobile Card View -->
        <div class="lg:hidden divide-y divide-slate-100">
            <?php foreach ($logs as $log): ?>
            <div class="p-4 hover:bg-slate-50 transition-colors">
                <div class="flex justify-between items-start mb-2 gap-2">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-900 text-sm truncate"><?= h($log['recipient_email']) ?></h3>
                        <p class="text-xs text-slate-500 font-medium truncate"><?= h(mb_substr($log['campaign_subject'] ?? '—', 0, 40)) ?></p>
                    </div>
                    <span class="shrink-0 px-2 py-0.5 rounded text-xs font-bold <?= $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : ($log['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') ?>">
                        <?= h($log['status']) ?>
                    </span>
                </div>
                <div class="flex justify-between items-end mt-3">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Sent / Failed</p>
                        <div class="flex items-center gap-1 text-xs text-slate-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span><?= (int)$log['campaign_sent'] ?></span>
                            <span class="text-slate-300">/</span>
                            <span><?= (int)$log['campaign_failed'] ?></span>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1.5 font-medium"><?= h($log['sent_at'] ?? '') ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1.5">Engagement</p>
                        <?php if (!empty($log['opened_at'])): ?>
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 inline-block">Opened</span>
                            <span class="text-slate-400 text-xs block mt-1 font-medium"><?= h($log['opened_at']) ?></span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-500 inline-block">Unopened</span>
                            <?php if (!empty($log['open_tracking_token']) && $log['status'] === 'sent'): ?>
                                <a href="<?= h(trackingBaseUrl() . '/track/email-open/' . $log['open_tracking_token']) ?>" target="_blank" rel="noopener" class="text-[10px] font-bold text-blue-600 hover:underline block mt-1">Test tracking</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <div class="p-8 text-center text-slate-500 font-medium text-sm">No activity logs found matching your filters.</div>
            <?php endif; ?>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Recipient</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Campaign</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Status</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Sent / Failed</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Opened</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-4 align-top">
                            <div class="text-sm font-medium text-slate-900 mt-1"><?= h($log['recipient_email']) ?></div>
                        </td>
                        <td class="px-4 md:px-8 py-4 align-top">
                            <div class="text-sm font-medium text-slate-600 mt-1"><?= h(mb_substr($log['campaign_subject'] ?? '—', 0, 40)) ?></div>
                        </td>
                        <td class="px-4 md:px-8 py-4 align-top">
                            <span class="px-2 py-1 rounded text-xs font-bold <?= $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : ($log['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') ?>">
                                <?= h($log['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 md:px-8 py-4 align-top">
                            <div class="text-slate-600 mt-1">
                                <span><?= (int)$log['campaign_sent'] ?></span>
                                <span class="mx-1">/</span>
                                <span><?= (int)$log['campaign_failed'] ?></span>
                            </div>
                            <div class="text-[10px] text-slate-400 font-medium block mt-1"><?= h($log['sent_at'] ?? '') ?></div>
                        </td>
                        <td class="px-4 md:px-8 py-4 align-top">
                            <?php if (!empty($log['opened_at'])): ?>
                                <span class="px-2 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-800">Opened</span>
                                <div class="text-slate-400 text-xs font-medium block mt-1"><?= h($log['opened_at']) ?></div>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded text-xs font-bold bg-slate-100 text-slate-500">Not yet opened</span>
                                <?php if (!empty($log['open_tracking_token']) && $log['status'] === 'sent'): ?>
                                    <a href="<?= h(trackingBaseUrl() . '/track/email-open/' . $log['open_tracking_token']) ?>" target="_blank" rel="noopener" class="text-[10px] font-bold text-blue-600 hover:underline mt-1 block">Test tracking</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="px-4 md:px-8 py-12 text-center text-slate-500 font-bold">No activity logs found matching your filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
