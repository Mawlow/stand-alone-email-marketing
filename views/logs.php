<?php
$logType = $_GET['type'] ?? 'email';
$statusFilter = $_GET['status'] ?? '';
$campaignFilter = isset($_GET['campaign_id']) ? (int) $_GET['campaign_id'] : 0;
$openedFilter = $_GET['opened'] ?? '';
$groupFilter = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

$logs = [];
if ($logType === 'email') {
    $q = 'SELECT l.*, c.subject as campaign_subject, c.sent_count as campaign_sent, c.failed_count as campaign_failed FROM email_logs l LEFT JOIN email_campaigns c ON c.id = l.email_campaign_id WHERE l.email_campaign_id IN (SELECT id FROM email_campaigns WHERE user_id = ?)';
    $params = [$userId];

    if ($statusFilter !== '') { $q .= ' AND l.status = ?'; $params[] = $statusFilter; }
    if ($campaignFilter > 0) { $q .= ' AND l.email_campaign_id = ?'; $params[] = $campaignFilter; }
    if ($openedFilter === '1') { $q .= ' AND l.opened_at IS NOT NULL'; }
    if ($openedFilter === '0') { $q .= ' AND l.opened_at IS NULL'; }

    $q .= ' ORDER BY l.id DESC LIMIT 100';
    $stmt = $pdo->prepare($q);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $campaignsStmt = $pdo->prepare('SELECT id, subject FROM email_campaigns WHERE user_id = ? ORDER BY id DESC LIMIT 50');
    $campaignsStmt->execute([$userId]);
    $campaigns = $campaignsStmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($logType === 'sms') {
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
    $q .= ' ORDER BY l.id DESC LIMIT 200';
    $stmt = $pdo->prepare($q);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $groupsForFilterStmt = $pdo->prepare('SELECT id, name FROM sms_groups WHERE user_id = ? ORDER BY name');
    $groupsForFilterStmt->execute([$userId]);
    $smsGroups = $groupsForFilterStmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($logType === 'whatsapp') {
    $logs = []; // WhatsApp logs not yet implemented in database
}
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

    /* Content Wrapper matching api.php */
    .logs-content-wrapper {
        max-width: 72rem; /* 6xl */
        margin: 0 auto;
        padding: 0 1rem 2rem 1rem;
    }
    @media (max-width: 1023px) { .logs-content-wrapper { margin-top: 1.5rem; } }
    @media (min-width: 640px) { .logs-content-wrapper { padding: 0 1.5rem 2rem 1.5rem; } }
    @media (min-width: 1024px) { .logs-content-wrapper { padding: 0 2rem 2rem 2rem; } }
</style>

<!-- Logs Banner -->
<div class="logs-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="max-w-6xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Activity Logs</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Track every delivery and engagement in real-time.</p>
        </div>
        <!-- Filter Buttons (Separated) -->
        <div class="relative z-10 flex gap-2">
            <a href="<?= url('logs', ['type' => 'email']) ?>" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all border <?= $logType === 'email' ? 'bg-[#f54a00] text-white border-[#f54a00] shadow-lg' : 'bg-white/10 text-white/70 border-white/30 hover:text-white hover:bg-white/20' ?>">Email</a>
            <a href="<?= url('logs', ['type' => 'sms']) ?>" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all border <?= $logType === 'sms' ? 'bg-[#f54a00] text-white border-[#f54a00] shadow-lg' : 'bg-white/10 text-white/70 border-white/30 hover:text-white hover:bg-white/20' ?>">SMS</a>
            <a href="<?= url('logs', ['type' => 'whatsapp']) ?>" class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all border <?= $logType === 'whatsapp' ? 'bg-[#f54a00] text-white border-[#f54a00] shadow-lg' : 'bg-white/10 text-white/70 border-white/30 hover:text-white hover:bg-white/20' ?>">WhatsApp</a>
        </div>
    </div>
    <!-- Decorative element -->
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#141d2e] px-4 pt-4 pb-6 text-white mb-2">
    <div class="flex items-center gap-3">
        <h1 class="text-base font-black text-white shrink-0">Logs</h1>
        <div class="flex flex-1 gap-2 justify-end">
            <a href="<?= url('logs', ['type' => 'email']) ?>" class="text-center px-3 py-2 min-h-[34px] rounded-lg text-[11px] font-bold transition-all border whitespace-nowrap <?= $logType === 'email' ? 'bg-[#f54a00] text-white border-[#f54a00] shadow-sm' : 'bg-white/10 text-white/70 border-white/30 hover:text-white' ?>">Email</a>
            <a href="<?= url('logs', ['type' => 'sms']) ?>" class="text-center px-3 py-2 min-h-[34px] rounded-lg text-[11px] font-bold transition-all border whitespace-nowrap <?= $logType === 'sms' ? 'bg-[#f54a00] text-white border-[#f54a00] shadow-sm' : 'bg-white/10 text-white/70 border-white/30 hover:text-white' ?>">SMS</a>
            <a href="<?= url('logs', ['type' => 'whatsapp']) ?>" class="text-center px-3 py-2 min-h-[34px] rounded-lg text-[11px] font-bold transition-all border whitespace-nowrap <?= $logType === 'whatsapp' ? 'bg-[#f54a00] text-white border-[#f54a00] shadow-sm' : 'bg-white/10 text-white/70 border-white/30 hover:text-white' ?>">WhatsApp</a>
        </div>
    </div>
</div>

<div class="logs-content-wrapper">
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/30 flex flex-col lg:flex-row justify-between lg:items-center gap-4">
            <h2 class="text-lg md:text-2xl font-bold text-white shrink-0"><?= ucfirst($logType) ?> Activities</h2>
            
            <form method="get" action="<?= url('logs') ?>" class="flex flex-col lg:flex-row gap-3 w-full lg:w-auto mt-2 lg:mt-0">
                <input type="hidden" name="page" value="logs">
                <input type="hidden" name="type" value="<?= h($logType) ?>">
                
                <div class="grid grid-cols-2 lg:flex lg:flex-row gap-3 lg:gap-2 w-full lg:w-auto">
                    <select name="status" onchange="this.form.submit()" class="w-full lg:w-auto bg-white/10 border border-white/20 rounded-lg px-2 py-2.5 lg:py-1.5 text-sm lg:text-xs font-medium text-white focus:ring-2 focus:ring-[#f54a00] focus:outline-none cursor-pointer hover:bg-white/20 transition-colors">
                        <option value="" class="text-slate-900">All Status</option>
                        <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?> class="text-slate-900">Sent</option>
                        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?> class="text-slate-900">Failed</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?> class="text-slate-900">Pending</option>
                        <?php if ($logType === 'sms'): ?>
                        <option value="queued" <?= $statusFilter === 'queued' ? 'selected' : '' ?> class="text-slate-900">Queued</option>
                        <option value="unknown" <?= $statusFilter === 'unknown' ? 'selected' : '' ?> class="text-slate-900">Unknown</option>
                        <?php endif; ?>
                    </select>

                    <?php if ($logType === 'email'): ?>
                    <select name="opened" onchange="this.form.submit()" class="w-full lg:w-auto bg-white/10 border border-white/20 rounded-lg px-2 py-2.5 lg:py-1.5 text-sm lg:text-xs font-medium text-white focus:ring-2 focus:ring-[#f54a00] focus:outline-none cursor-pointer hover:bg-white/20 transition-colors">
                        <option value="" class="text-slate-900">Open Status</option>
                        <option value="1" <?= $openedFilter === '1' ? 'selected' : '' ?> class="text-slate-900">Opened</option>
                        <option value="0" <?= $openedFilter === '0' ? 'selected' : '' ?> class="text-slate-900">Unopened</option>
                    </select>

                    <select name="campaign_id" onchange="this.form.submit()" class="col-span-2 lg:col-span-1 w-full lg:w-auto bg-white/10 border border-white/20 rounded-lg px-2 py-2.5 lg:py-1.5 text-sm lg:text-xs font-medium text-white focus:ring-2 focus:ring-[#f54a00] focus:outline-none cursor-pointer hover:bg-white/20 transition-colors lg:max-w-[220px]">
                        <option value="" class="text-slate-900">All Campaigns</option>
                        <?php foreach ($campaigns as $co): ?>
                            <option value="<?= (int)$co['id'] ?>" <?= $campaignFilter === (int)$co['id'] ? 'selected' : '' ?> class="text-slate-900">
                                <?= h(mb_substr($co['subject'], 0, 30)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>

                    <?php if ($logType === 'sms'): ?>
                    <select name="group_id" onchange="this.form.submit()" class="col-span-2 lg:col-span-1 w-full lg:w-auto bg-white/10 border border-white/20 rounded-lg px-2 py-2.5 lg:py-1.5 text-sm lg:text-xs font-medium text-white focus:ring-2 focus:ring-[#f54a00] focus:outline-none cursor-pointer hover:bg-white/20 transition-colors lg:max-w-[220px]">
                        <option value="" class="text-slate-900">All Groups</option>
                        <?php foreach ($smsGroups as $sg): ?>
                            <option value="<?= (int)$sg['id'] ?>" <?= $groupFilter === (int)$sg['id'] ? 'selected' : '' ?> class="text-slate-900"><?= h($sg['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($logType === 'email'): ?>
        <!-- Desktop Email Table -->
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
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($logType === 'sms' || $logType === 'whatsapp'): ?>
        <!-- Desktop SMS/WhatsApp Table -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Sent at</th>
                        <?php if ($logType === 'sms'): ?>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Group</th>
                        <?php endif; ?>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Recipient</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Message</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-4 text-sm text-slate-600"><?= h($log['sent_at']) ?></td>
                        <?php if ($logType === 'sms'): ?>
                        <td class="px-4 md:px-8 py-4 text-sm font-medium text-slate-900"><?= h($log['group_name'] ?? '—') ?></td>
                        <?php endif; ?>
                        <td class="px-4 md:px-8 py-4">
                            <div class="text-sm font-bold text-slate-900"><?= h($log['recipient_name']) ?></div>
                            <div class="text-xs text-slate-500"><?= h($log['phone_number']) ?></div>
                        </td>
                        <td class="px-4 md:px-8 py-4 text-sm text-slate-600 max-w-xs truncate" title="<?= h($log['message']) ?>"><?= h($log['message']) ?></td>
                        <td class="px-4 md:px-8 py-4">
                            <?php
                            $st = strtolower($log['status']);
                            $cls = ($st === 'sent' || $st === 'pending' || $st === 'queued') ? 'bg-emerald-100 text-emerald-800' : ($st === 'failed' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-600');
                            ?>
                            <span class="px-2 py-1 rounded text-xs font-bold <?= $cls ?>"><?= h($log['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Mobile View -->
        <?php if ($logType === 'email'): ?>
        <div class="lg:hidden divide-y divide-slate-100">
            <?php foreach ($logs as $log): ?>
            <div class="p-4 hover:bg-slate-50">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-900 text-sm truncate"><?= h($log['recipient_email'] ?? '—') ?></div>
                        <div class="text-xs text-slate-500 mt-0.5 truncate"><?= h($log['campaign_subject'] ?? '—') ?></div>
                    </div>
                    <span class="shrink-0 px-2 py-0.5 rounded text-xs font-bold <?= $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : ($log['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') ?>">
                        <?= h($log['status']) ?>
                    </span>
                </div>
                <div class="text-[10px] text-slate-400">
                    <?= h($log['sent_at'] ?? '') ?>
                    <?php if (!empty($log['opened_at'])): ?>
                        <span class="mx-2">•</span>
                        <span class="text-emerald-600 font-bold">Opened</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="lg:hidden divide-y divide-slate-100">
            <?php foreach ($logs as $log): ?>
            <?php
                $st = strtolower($log['status'] ?? '');
                $cls = ($st === 'sent' || $st === 'pending' || $st === 'queued') ? 'bg-emerald-100 text-emerald-800' : ($st === 'failed' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-600');
            ?>
            <div class="p-4 hover:bg-slate-50">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-900 text-sm truncate"><?= h($log['recipient_name'] ?? '—') ?></div>
                        <div class="text-xs text-slate-500 mt-0.5 truncate"><?= h($log['phone_number'] ?? '') ?></div>
                    </div>
                    <span class="shrink-0 px-2 py-0.5 rounded text-xs font-bold <?= $cls ?>">
                        <?= h($log['status'] ?? '') ?>
                    </span>
                </div>

                <?php if ($logType === 'sms'): ?>
                <div class="text-xs text-slate-600 mb-2">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Group:</span>
                    <span class="font-bold"><?= h($log['group_name'] ?? '—') ?></span>
                </div>
                <?php endif; ?>

                <div class="text-xs text-slate-600 mb-2"><?= h(mb_substr((string)($log['message'] ?? ''), 0, 160)) ?></div>
                <div class="text-[10px] text-slate-400"><?= h($log['sent_at'] ?? '') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($logs)): ?>
        <div class="p-12 text-center text-slate-500 font-bold">No logs found.</div>
        <?php endif; ?>
    </div>
</div>
