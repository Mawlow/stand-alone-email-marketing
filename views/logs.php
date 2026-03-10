<?php
$statusFilter = $_GET['status'] ?? '';
$campaignFilter = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : 0;
$openedFilter = $_GET['opened'] ?? '';
$q = 'SELECT l.*, c.subject as campaign_subject FROM email_logs l LEFT JOIN email_campaigns c ON c.id = l.email_campaign_id WHERE 1=1';
$params = [];
if ($statusFilter !== '') { $q .= ' AND l.status = ?'; $params[] = $statusFilter; }
if ($campaignFilter > 0) { $q .= ' AND l.email_campaign_id = ?'; $params[] = $campaignFilter; }
if ($openedFilter === '1') { $q .= ' AND l.opened_at IS NOT NULL'; }
if ($openedFilter === '0') { $q .= ' AND l.opened_at IS NULL'; }
$q .= ' ORDER BY l.id DESC LIMIT 100';
$stmt = $pdo->prepare($q);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$campaigns = $pdo->query('SELECT id, subject FROM email_campaigns ORDER BY id DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
$totalLogs = count($logs);
$sentLogs = count(array_filter($logs, fn($l) => $l['status'] === 'sent'));
$failedLogs = count(array_filter($logs, fn($l) => $l['status'] === 'failed'));
?>
<style>
    /* Hide the default title area from index.php when on the logs page */
    main > div > div.mb-4:first-child {
        display: none;
    }

    /* Force the parent container to be full width and remove padding */
    main > div.max-w-6xl {
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* Style flash messages to stay centered */
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

    .page-banner {
        margin-bottom: 2rem;
    }
</style>

<!-- Logs Banner (Desktop) -->
<div class="page-banner bg-[#02396E] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Email Logs</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Track delivery status and recipient engagement.</p>
        </div>
        <div class="flex gap-8">
            <div class="text-right">
                <p class="text-3xl font-bold text-white"><?= $sentLogs ?></p>
                <p class="text-blue-100/80 text-xs font-medium">sent</p>
            </div>
            <div class="text-right">
                <p class="text-3xl font-bold text-red-400"><?= $failedLogs ?></p>
                <p class="text-blue-100/80 text-xs font-medium">failed</p>
            </div>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#02396E] px-4 py-4 text-white">
    <h1 class="text-xl font-bold">Email Logs</h1>
    <p class="text-blue-100/80 text-xs">Track deliveries</p>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 relative mt-4 lg:mt-0">
    <!-- Background element starting from sidebar -->
    <div class="fixed inset-y-0 left-64 right-0 bg-slate-50 -z-10 border-l border-slate-200 hidden lg:block"></div>

    <!-- Desktop Filter Row -->
<form method="get" action="<?= url('logs') ?>" class="hidden lg:flex flex-wrap gap-2 mb-4">
    <input type="hidden" name="page" value="logs">
    <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm flex-1 min-w-[120px]">
        <option value="">All statuses</option>
        <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Sent</option>
        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
    </select>
    <select name="campaign_id" class="rounded-xl border border-slate-200 px-3 py-2 text-sm flex-1 min-w-[120px]">
        <option value="">All campaigns</option>
        <?php foreach ($campaigns as $co): ?><option value="<?= (int)$co['id'] ?>" <?= $campaignFilter === (int)$co['id'] ? 'selected' : '' ?>><?= h(mb_substr($co['subject'], 0, 30)) ?></option><?php endforeach; ?>
    </select>
    <select name="opened" class="rounded-xl border border-slate-200 px-3 py-2 text-sm flex-1 min-w-[100px]">
        <option value="">All</option>
        <option value="1" <?= $openedFilter === '1' ? 'selected' : '' ?>>Opened</option>
        <option value="0" <?= $openedFilter === '0' ? 'selected' : '' ?>>Not opened</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-[#02396E] text-white text-sm font-bold rounded-xl touch-manipulation">Filter</button>
</form>

<!-- Mobile Filter - Collapsible -->
<div class="lg:hidden mb-3">
    <button type="button" id="mobile-filter-toggle" class="w-full flex items-center justify-between px-4 py-3 bg-white rounded-xl shadow border border-slate-200 text-sm font-medium text-slate-700 touch-manipulation">
        <span class="flex items-center gap-2">
            <svg class="w-4 h-4 text-[#02396E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
            Filter
        </span>
        <svg id="filter-chevron" class="w-5 h-5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </button>
    
    <form method="get" action="<?= url('logs') ?>" id="mobile-filter-form" class="hidden mt-2 bg-white rounded-xl shadow border border-slate-200 p-4">
        <input type="hidden" name="page" value="logs">
        <div class="space-y-3">
            <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm bg-white">
                <option value="">All statuses</option>
                <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Sent</option>
                <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            </select>
            
            <select name="campaign_id" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm bg-white">
                <option value="">All campaigns</option>
                <?php foreach ($campaigns as $co): ?><option value="<?= (int)$co['id'] ?>" <?= $campaignFilter === (int)$co['id'] ? 'selected' : '' ?>><?= h(mb_substr($co['subject'], 0, 30)) ?></option><?php endforeach; ?>
            </select>
            
            <select name="opened" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm bg-white">
                <option value="">All</option>
                <option value="1" <?= $openedFilter === '1' ? 'selected' : '' ?>>Opened</option>
                <option value="0" <?= $openedFilter === '0' ? 'selected' : '' ?>>Not opened</option>
            </select>
        </div>
        
        <button type="submit" class="mt-3 w-full py-2.5 bg-[#02396E] text-white text-sm font-bold rounded-lg touch-manipulation">
            Apply
        </button>
    </form>
</div>

<script>
document.getElementById('mobile-filter-toggle').addEventListener('click', function() {
    var form = document.getElementById('mobile-filter-form');
    var chevron = document.getElementById('filter-chevron');
    form.classList.toggle('hidden');
    chevron.classList.toggle('rotate-180');
});
</script>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">Logs (<?= count($logs) ?>)</h2></div>
    
    <!-- Mobile Card View -->
    <div class="md:hidden divide-y divide-slate-100">
        <?php foreach ($logs as $log): ?>
        <div class="p-4 hover:bg-slate-50">
            <div class="flex items-start justify-between gap-2 mb-2">
                <h3 class="font-medium text-slate-900 text-sm flex-1 truncate"><?= h($log['recipient_email']) ?></h3>
                <span class="shrink-0 px-2 py-0.5 rounded text-xs font-semibold <?= $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : ($log['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') ?>"><?= h($log['status']) ?></span>
            </div>
            <p class="text-xs text-slate-600 mb-2"><?= h(mb_substr($log['campaign_subject'] ?? '—', 0, 40)) ?></p>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                <span>Sent: <?= h($log['sent_at'] ?? '—') ?></span>
                <?php if (!empty($log['opened_at'])): ?>
                <span class="text-green-600">Opened <?= h($log['opened_at']) ?></span>
                <?php else: ?>
                <span class="text-slate-400">Not opened</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?>
        <div class="p-8 text-center text-slate-500 text-sm">No logs.</div>
        <?php endif; ?>
    </div>
    
    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-600 uppercase">Recipient</th>
                    <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-600 uppercase">Campaign</th>
                    <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-600 uppercase">Status</th>
                    <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-600 uppercase">Sent at</th>
                    <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-600 uppercase">Opened</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr class="border-t border-slate-100 hover:bg-slate-50">
                    <td class="px-4 md:px-6 py-3 font-medium text-slate-900"><?= h($log['recipient_email']) ?></td>
                    <td class="px-4 md:px-6 py-3 text-slate-600 text-sm"><?= h(mb_substr($log['campaign_subject'] ?? '—', 0, 25)) ?></td>
                    <td class="px-4 md:px-6 py-3"><span class="px-2 py-0.5 rounded text-xs font-semibold <?= $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : ($log['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') ?>"><?= h($log['status']) ?></span></td>
                    <td class="px-4 md:px-6 py-3 text-slate-500 text-sm"><?= h($log['sent_at'] ?? '—') ?></td>
                    <td class="px-4 md:px-6 py-3">
                        <?php if (!empty($log['opened_at'])): ?>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Opened</span>
                            <span class="text-slate-500 text-xs block mt-0.5"><?= h($log['opened_at']) ?></span>
                        <?php else: ?>
                            <span class="text-slate-400 text-sm">—</span>
                            <?php if (!empty($log['open_tracking_token']) && $log['status'] === 'sent'): ?>
                                <a href="<?= h(trackingBaseUrl() . '/track/email-open/' . $log['open_tracking_token']) ?>" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:underline block mt-0.5">Test open</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="5" class="px-4 md:px-6 py-8 text-center text-slate-500">No logs.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
