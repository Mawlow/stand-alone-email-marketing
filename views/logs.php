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
?>
<form method="get" action="<?= url('logs') ?>" class="flex flex-wrap gap-2 mb-4">
    <input type="hidden" name="page" value="logs">
    <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <option value="">All statuses</option>
        <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Sent</option>
        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
    </select>
    <select name="campaign_id" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <option value="">All campaigns</option>
        <?php foreach ($campaigns as $co): ?><option value="<?= (int)$co['id'] ?>" <?= $campaignFilter === (int)$co['id'] ? 'selected' : '' ?>><?= h(mb_substr($co['subject'], 0, 30)) ?></option><?php endforeach; ?>
    </select>
    <select name="opened" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
        <option value="">All</option>
        <option value="1" <?= $openedFilter === '1' ? 'selected' : '' ?>>Opened</option>
        <option value="0" <?= $openedFilter === '0' ? 'selected' : '' ?>>Not opened</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-[#02396E] text-white text-sm font-bold rounded-xl">Filter</button>
</form>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">Logs (<?= count($logs) ?>)</h2></div>
    <div class="overflow-x-auto">
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
