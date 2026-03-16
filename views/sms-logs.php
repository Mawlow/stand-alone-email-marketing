<?php
$statusFilter = $_GET['status'] ?? '';
$groupFilter = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$searchQuery = $_GET['search'] ?? '';

$q = 'SELECT l.* FROM sms_logs l INNER JOIN sms_groups g ON g.id = l.group_id AND g.user_id = ? WHERE 1=1';
$params = [$userId];
if ($statusFilter !== '') {
    $q .= ' AND l.status = ?';
    $params[] = $statusFilter;
}
if ($groupFilter > 0) {
    $q .= ' AND l.group_id = ?';
    $params[] = $groupFilter;
}
if ($searchQuery !== '') {
    $q .= ' AND (l.recipient_name LIKE ? OR l.phone_number LIKE ? OR l.message LIKE ? OR l.group_name LIKE ?)';
    $like = "%$searchQuery%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$q .= ' ORDER BY l.id DESC LIMIT 200';
$stmt = $pdo->prepare($q);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$groupsForFilterStmt = $pdo->prepare('SELECT id, name FROM sms_groups WHERE user_id = ? ORDER BY name');
$groupsForFilterStmt->execute([$userId]);
$groupsForFilter = $groupsForFilterStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    main > div.max-w-6xl { max-width: none !important; padding: 0 !important; margin: 0 !important; }
    main > div.max-w-6xl > div.mb-4 { max-width: 72rem; margin-left: auto; margin-right: auto; margin-top: 1rem; padding-left: 1rem; padding-right: 1rem; }
    .sms-logs-banner { margin-bottom: 1.5rem; }
</style>

<!-- Banner (Desktop) -->
<div class="sms-logs-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 relative z-10">
        <h1 class="text-[2.5rem] font-bold leading-tight">SMS Logs</h1>
        <p class="text-blue-100/80 mt-1 text-sm font-medium">View delivery history for all SMS messages sent via Semaphore.</p>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#141d2e] px-4 py-4 text-white mb-2">
    <h1 class="text-xl font-bold">SMS Logs</h1>
    <p class="text-blue-100/80 text-xs mt-0.5">View SMS delivery history.</p>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 border-b border-white/10 flex flex-col lg:flex-row justify-between lg:items-center gap-4">
            <h2 class="text-xl font-bold text-white shrink-0">SMS Activity</h2>
            <form method="get" action="<?= url('sms-logs') ?>" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="page" value="sms-logs">
                <input type="text" name="search" value="<?= h($searchQuery) ?>" placeholder="Search..." class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-40 lg:w-48">
                <select name="group_id" class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900">
                    <option value="">All groups</option>
                    <?php foreach ($groupsForFilter as $gf): ?>
                    <option value="<?= (int)$gf['id'] ?>" <?= $groupFilter === (int)$gf['id'] ? 'selected' : '' ?>><?= h($gf['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900">
                    <option value="">All status</option>
                    <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="queued" <?= $statusFilter === 'queued' ? 'selected' : '' ?>>Queued</option>
                    <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                    <option value="unknown" <?= $statusFilter === 'unknown' ? 'selected' : '' ?>>Unknown</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-[#f54a00] text-white text-sm font-bold rounded-lg hover:bg-[#e04400]">Filter</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-700 uppercase">Sent at</th>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-700 uppercase">Group</th>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-700 uppercase">Recipient</th>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-700 uppercase">Phone</th>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-700 uppercase">Message</th>
                        <th class="px-4 md:px-6 py-3 text-xs font-bold text-slate-700 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-6 py-3 text-slate-600 text-sm whitespace-nowrap"><?= h($log['sent_at']) ?></td>
                        <td class="px-4 md:px-6 py-3 font-medium text-slate-900"><?= h($log['group_name']) ?></td>
                        <td class="px-4 md:px-6 py-3 text-slate-900"><?= h($log['recipient_name']) ?></td>
                        <td class="px-4 md:px-6 py-3 text-slate-700"><?= h($log['phone_number']) ?></td>
                        <td class="px-4 md:px-6 py-3 text-slate-600 max-w-xs truncate" title="<?= h($log['message']) ?>"><?= h(mb_substr($log['message'], 0, 50)) ?><?= mb_strlen($log['message']) > 50 ? '…' : '' ?></td>
                        <td class="px-4 md:px-6 py-3">
                            <?php
                            $status = strtolower((string)$log['status']);
                            $displayStatus = ($status === 'pending' || $status === 'queued') ? 'Sent' : $log['status'];
                            $statusClass = ($status === 'sent' || $status === 'pending' || $status === 'queued') ? 'bg-emerald-100 text-emerald-800' : ($status === 'failed' ? 'bg-red-100 text-red-800' : ($status === 'unknown' ? 'bg-slate-200 text-slate-700' : 'bg-amber-100 text-amber-800'));
                            ?>
                            <span class="px-2 py-0.5 rounded text-xs font-bold <?= $statusClass ?>">
                                <?= h($displayStatus) ?>
                            </span>
                            <?php if (!empty($log['error_message'])): ?>
                            <span class="block text-xs text-red-600 mt-1 max-w-xs truncate" title="<?= h($log['error_message']) ?>"><?= h(mb_substr($log['error_message'], 0, 30)) ?>…</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="px-4 md:px-6 py-12 text-center text-slate-500">No SMS logs yet. Send an SMS from the SMS page to see entries here.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
