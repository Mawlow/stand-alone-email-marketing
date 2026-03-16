<?php
$type = $_GET['type'] ?? 'email';
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// For Email Logs
$campaignFilter = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : 0;
$openedFilter = $_GET['opened'] ?? '';

// For SMS Logs
$groupFilter = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

$logs = [];
$params = [];

if ($type === 'email') {
    $q = 'SELECT l.*, c.subject as campaign_subject, c.sent_count as campaign_sent, c.failed_count as campaign_failed FROM email_logs l LEFT JOIN email_campaigns c ON c.id = l.email_campaign_id WHERE 1=1';
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
} elseif ($type === 'sms') {
    $q = 'SELECT * FROM sms_logs WHERE 1=1';
    if ($statusFilter !== '') { $q .= ' AND status = ?'; $params[] = $statusFilter; }
    if ($groupFilter > 0) { $q .= ' AND group_id = ?'; $params[] = $groupFilter; }
    if ($searchQuery !== '') {
        $q .= ' AND (recipient_name LIKE ? OR phone_number LIKE ? OR message LIKE ? OR group_name LIKE ?)';
        $like = "%$searchQuery%";
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }
    $q .= ' ORDER BY id DESC LIMIT 100';
    $stmt = $pdo->prepare($q);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $groupsForFilter = $pdo->query('SELECT id, name FROM sms_groups ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
} elseif ($type === 'whatsapp') {
    // WhatsApp logs table is not created yet
    $logs = [];
}
?>

<style>
    main > div.max-w-6xl { max-width: none !important; padding: 0 !important; margin: 0 !important; }
    main > div.max-w-6xl > div.mb-4 { max-width: 72rem; margin-left: auto; margin-right: auto; margin-top: 1.5rem; padding-left: 1rem; padding-right: 1rem; }
    .logs-banner { margin-bottom: 2rem; }
    .logs-content-wrapper { max-width: 72rem; margin: 0 auto; padding: 0 1rem 2rem 1rem; }
    @media (max-width: 1023px) { .logs-content-wrapper { margin-top: 1.5rem; } }
    @media (min-width: 640px) { .logs-content-wrapper { padding: 0 1.5rem 2rem 1.5rem; } }
    @media (min-width: 1024px) { .logs-content-wrapper { padding: 0 2rem 2rem 2rem; } }
</style>

<!-- Logs Banner -->
<div class="logs-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Logs</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Track every delivery and engagement in real-time.</p>
        </div>
        
        <!-- Type Selector Tabs -->
        <div class="relative z-10 flex items-center gap-1 bg-white/10 p-1 rounded-xl w-fit">
            <a href="<?= url('logs', ['type' => 'email']) ?>" class="px-6 py-2 rounded-lg text-sm font-bold transition-all <?= $type === 'email' ? 'bg-[#f54a00] text-white shadow-sm' : 'text-slate-300 hover:text-white' ?>">Email Logs</a>
            <a href="<?= url('logs', ['type' => 'sms']) ?>" class="px-6 py-2 rounded-lg text-sm font-bold transition-all <?= $type === 'sms' ? 'bg-[#f54a00] text-white shadow-sm' : 'text-slate-300 hover:text-white' ?>">SMS Logs</a>
            <a href="<?= url('logs', ['type' => 'whatsapp']) ?>" class="px-6 py-2 rounded-lg text-sm font-bold transition-all <?= $type === 'whatsapp' ? 'bg-[#f54a00] text-white shadow-sm' : 'text-slate-300 hover:text-white' ?>">WhatsApp Logs</a>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<div class="logs-content-wrapper">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-6 border-b border-slate-200 flex flex-col lg:flex-row justify-between lg:items-center gap-4">
            <h2 class="text-xl md:text-2xl font-bold text-white">Activity History</h2>
            
            <form method="get" action="<?= url('logs') ?>" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="page" value="logs">
                <input type="hidden" name="type" value="<?= h($type) ?>">
                
                <?php if ($type === 'email'): ?>
                    <select name="campaign_id" onchange="this.form.submit()" class="rounded-lg border border-white/30 bg-white/10 px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-white/20 hover:bg-white/20 transition-all cursor-pointer">
                        <option value="" class="text-slate-900">All Campaigns</option>
                        <?php foreach ($campaigns as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $campaignFilter === (int)$c['id'] ? 'selected' : '' ?> class="text-slate-900"><?= h($c['subject']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'sms'): ?>
                    <select name="group_id" onchange="this.form.submit()" class="rounded-lg border border-white/30 bg-white/10 px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-white/20 hover:bg-white/20 transition-all cursor-pointer">
                        <option value="" class="text-slate-900">All Groups</option>
                        <?php foreach ($groupsForFilter as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= $groupFilter === (int)$g['id'] ? 'selected' : '' ?> class="text-slate-900"><?= h($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <select name="status" onchange="this.form.submit()" class="rounded-lg border border-white/30 bg-white/10 px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-white/20 hover:bg-white/20 transition-all cursor-pointer">
                    <option value="" class="text-slate-900">All Status</option>
                    <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?> class="text-slate-900">Sent</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?> class="text-slate-900">Pending</option>
                    <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?> class="text-slate-900">Failed</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <?php if ($type === 'email'): ?>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Recipient</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Campaign</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Sent At</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Opened At</th>
                        <?php elseif ($type === 'sms' || $type === 'whatsapp'): ?>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Recipient</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Phone</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Message</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Sent At</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <?php if ($type === 'email'): ?>
                                <td class="px-8 py-4">
                                    <div class="text-sm font-medium text-slate-900"><?= h($log['recipient_email']) ?></div>
                                </td>
                                <td class="px-8 py-4">
                                    <div class="text-sm text-slate-600"><?= h($log['campaign_subject'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-8 py-4">
                                    <?php
                                    $s = strtolower($log['status']);
                                    $class = $s === 'sent' ? 'bg-emerald-100 text-emerald-700' : ($s === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $class ?>"><?= ucfirst($s) ?></span>
                                </td>
                                <td class="px-8 py-4 text-sm text-slate-500"><?= $log['sent_at'] ? date('M j, Y H:i', strtotime($log['sent_at'])) : '—' ?></td>
                                <td class="px-8 py-4 text-sm text-slate-500"><?= $log['opened_at'] ? date('M j, Y H:i', strtotime($log['opened_at'])) : 'Not opened' ?></td>
                            <?php elseif ($type === 'sms' || $type === 'whatsapp'): ?>
                                <td class="px-8 py-4">
                                    <div class="text-sm font-medium text-slate-900"><?= h($log['recipient_name']) ?></div>
                                    <?php if ($type === 'sms' && !empty($log['group_name'])): ?>
                                        <div class="text-xs text-slate-500 mt-0.5"><?= h($log['group_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-8 py-4 text-sm text-slate-600"><?= h($log['phone_number']) ?></td>
                                <td class="px-8 py-4">
                                    <div class="text-sm text-slate-600 max-w-xs truncate" title="<?= h($log['message']) ?>"><?= h($log['message']) ?></div>
                                </td>
                                <td class="px-8 py-4">
                                    <?php
                                    $s = strtolower($log['status']);
                                    $class = $s === 'sent' ? 'bg-emerald-100 text-emerald-700' : ($s === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700');
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $class ?>"><?= ucfirst($s) ?></span>
                                </td>
                                <td class="px-8 py-4 text-sm text-slate-500"><?= date('M j, Y H:i', strtotime($log['sent_at'])) ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="px-8 py-12 text-center text-slate-500 font-medium">No logs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>