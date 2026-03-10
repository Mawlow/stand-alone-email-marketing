<?php
$statusFilter = $_GET['status'] ?? '';
$campaignFilter = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : 0;
$openedFilter = $_GET['opened'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$q = 'SELECT l.*, c.subject as campaign_subject FROM email_logs l LEFT JOIN email_campaigns c ON c.id = l.email_campaign_id WHERE 1=1';
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
    /* Hide the default title area from index.php */
    main > div > div.mb-6:first-child {
        display: none;
    }

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
<div class="logs-banner bg-[#02396E] py-6 md:py-8 text-white shadow-lg relative overflow-hidden">
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('logsSearch');
    const clearBtn = document.getElementById('clearLogsSearch');

    searchInput.addEventListener('input', () => {
        if (searchInput.value.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
    });

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        clearBtn.classList.add('hidden');
        searchInput.closest('form').submit();
    });
});
</script>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 text-left">
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 border-b border-white/10 flex flex-col lg:flex-row justify-between lg:items-center gap-4">
            <h2 class="text-xl font-bold text-white shrink-0">Activities</h2>
            
            <form method="get" action="<?= url('logs') ?>" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="page" value="logs">
                <?php if ($searchQuery): ?><input type="hidden" name="search" value="<?= h($searchQuery) ?>"><?php endif; ?>
                
                <select name="status" class="bg-white/10 border border-white/20 rounded-lg px-3 py-1.5 text-xs font-bold text-white focus:ring-2 focus:ring-[#ff8904] focus:outline-none cursor-pointer hover:bg-white/20 transition-colors">
                    <option value="" class="text-slate-900">All Status</option>
                    <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?> class="text-slate-900">Sent</option>
                    <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?> class="text-slate-900">Failed</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?> class="text-slate-900">Pending</option>
                </select>

                <select name="campaign_id" class="bg-white/10 border border-white/20 rounded-lg px-3 py-1.5 text-xs font-bold text-white focus:ring-2 focus:ring-[#ff8904] focus:outline-none cursor-pointer hover:bg-white/20 transition-colors max-w-[150px]">
                    <option value="" class="text-slate-900">All Campaigns</option>
                    <?php foreach ($campaigns as $co): ?>
                        <option value="<?= (int)$co['id'] ?>" <?= $campaignFilter === (int)$co['id'] ? 'selected' : '' ?> class="text-slate-900">
                            <?= h(mb_substr($co['subject'], 0, 30)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="opened" class="bg-white/10 border border-white/20 rounded-lg px-3 py-1.5 text-xs font-bold text-white focus:ring-2 focus:ring-[#ff8904] focus:outline-none cursor-pointer hover:bg-white/20 transition-colors">
                    <option value="" class="text-slate-900">All Open Status</option>
                    <option value="1" <?= $openedFilter === '1' ? 'selected' : '' ?> class="text-slate-900">Opened</option>
                    <option value="0" <?= $openedFilter === '0' ? 'selected' : '' ?> class="text-slate-900">Not opened</option>
                </select>

                <button type="submit" class="px-4 py-1.5 bg-[#ff8904] text-white text-xs font-bold rounded-lg hover:bg-orange-600 transition-colors shadow-sm">Filter</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Recipient</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Campaign</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Status</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Sent at</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Opened</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-4 font-semibold text-slate-900"><?= h($log['recipient_email']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600 text-sm font-medium"><?= h(mb_substr($log['campaign_subject'] ?? '—', 0, 40)) ?></td>
                        <td class="px-4 md:px-8 py-4">
                            <span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?= $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : ($log['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') ?>">
                                <?= h($log['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 md:px-8 py-4 text-slate-500 text-sm font-medium"><?= h($log['sent_at'] ?? '—') ?></td>
                        <td class="px-4 md:px-8 py-4">
                            <?php if (!empty($log['opened_at'])): ?>
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 italic">Opened</span>
                                <span class="text-slate-400 text-[10px] block mt-1 font-bold"><?= h($log['opened_at']) ?></span>
                            <?php else: ?>
                                <span class="text-slate-300 text-sm">—</span>
                                <?php if (!empty($log['open_tracking_token']) && $log['status'] === 'sent'): ?>
                                    <a href="<?= h(trackingBaseUrl() . '/track/email-open/' . $log['open_tracking_token']) ?>" target="_blank" rel="noopener" class="text-[10px] font-bold text-blue-600 hover:underline block mt-1">Test tracking</a>
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
