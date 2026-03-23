<?php
$smsGroupsStmt = $pdo->prepare('SELECT g.*, (SELECT COUNT(*) FROM sms_recipients WHERE group_id = g.id) as recipient_count FROM sms_groups g WHERE g.user_id = ? ORDER BY g.name');
$smsGroupsStmt->execute([$userId]);
$smsGroups = $smsGroupsStmt->fetchAll(PDO::FETCH_ASSOC);
$smsRecipientsStmt = $pdo->prepare('SELECT r.*, g.name as group_name FROM sms_recipients r JOIN sms_groups g ON g.id = r.group_id AND g.user_id = ? ORDER BY g.name, r.name');
$smsRecipientsStmt->execute([$userId]);
$smsRecipients = $smsRecipientsStmt->fetchAll(PDO::FETCH_ASSOC);
$recipientsByGroup = [];
foreach ($smsRecipients as $r) {
    $recipientsByGroup[(int)$r['group_id']][] = ['id' => (int)$r['id'], 'name' => $r['name'], 'phone_number' => $r['phone_number']];
}
?>
<style>
    main > div.max-w-6xl {
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    main > div.max-w-6xl > div.mb-4 {
        max-width: 72rem;
        margin-left: auto;
        margin-right: auto;
        margin-top: 1rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    .sms-banner { margin-bottom: 1.5rem; }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .modal-animate { animation: modalFadeIn 0.2s ease-out forwards; }
    .sms-alert { margin-bottom: 1rem; }
    .send-sms-btn-loading { pointer-events: none; opacity: 0.8; }
</style>

<!-- Banner (Desktop) -->
<div class="sms-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="max-w-6xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
            <h1 class="text-[2.5rem] font-bold leading-tight">SMS Notifications</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Manage groups and recipients, then send SMS messages via Semaphore.</p>
        </div>
        <button type="button" class="send-sms-open-btn inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-500 transition-colors shadow-md md:flex-shrink-0">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            <span>Send SMS</span>
        </button>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#141d2e] px-4 py-4 text-white mb-2">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold">SMS Notifications</h1>
            <p class="text-blue-100/80 text-xs mt-0.5">Manage groups and send SMS to recipients.</p>
        </div>
        <button type="button" class="send-sms-open-btn inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-500 transition-colors flex-shrink-0">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            <span>Send SMS</span>
        </button>
    </div>
</div>

<?php $smsFlashSuccess = $flashSuccess ?? null; $smsFlashError = $flashError ?? null; ?>
<?php if ($smsFlashSuccess || $smsFlashError): ?>
<div id="sms-alerts" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-4 space-y-2">
    <?php if ($smsFlashSuccess): ?>
    <div class="sms-alert sms-alert-success flex items-center justify-between gap-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 font-medium">
        <span><?= h($smsFlashSuccess) ?></span>
        <button type="button" class="sms-alert-close p-1 rounded-lg hover:bg-emerald-100 transition-colors" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    <?php endif; ?>
    <?php if ($smsFlashError): ?>
    <div class="sms-alert sms-alert-error flex items-center justify-between gap-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 font-medium">
        <span><?= h($smsFlashError) ?></span>
        <button type="button" class="sms-alert-close p-1 rounded-lg hover:bg-red-100 transition-colors" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 space-y-8">
    <!-- 1. Create Group & Add Recipient (one form) -->
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 border-b border-white/10">
            <h2 class="text-lg md:text-xl font-bold text-white">Create Group & Add Recipient</h2>
        </div>
        <div class="p-4 md:p-6">
            <form method="post" action="<?= url('sms') ?>" id="sms-group-recipient-form" class="space-y-4">
                <input type="hidden" name="action" value="sms-recipient-add">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="recipient_group_id" class="block text-sm font-medium text-slate-700 mb-1">Group</label>
                        <select name="group_id" id="recipient_group_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                            <option value="">Select group</option>
                            <option value="new">— Create new group —</option>
                            <?php foreach ($smsGroups as $g): ?>
                            <option value="<?= (int)$g['id'] ?>"><?= h($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2"></div>
                </div>
                <div id="recipient-rows" class="space-y-3">
                    <div class="recipient-row grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Recipient Name</label>
                            <input type="text" name="recipient_name[]" placeholder="Juan Dela Cruz" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Phone Number</label>
                            <input type="text" name="phone_number[]" placeholder="09XXXXXXXXX or +639XXXXXXXXX" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:gap-3 sm:w-1/2 sm:justify-start items-center">
                    <button type="button" id="add-recipient-row" class="w-full sm:w-auto px-3 py-2 border border-slate-300 text-slate-700 font-medium rounded-xl hover:bg-slate-50 transition-colors">
                        <span class="sm:hidden">Add another</span>
                        <span class="hidden sm:inline">Add another recipient</span>
                    </button>
                    <button type="submit" class="w-full sm:w-auto px-3 py-2.5 bg-[#f54a00] text-white font-bold rounded-xl hover:bg-[#e04400] transition-colors">
                        <span class="sm:hidden">Add</span>
                        <span class="hidden sm:inline">Add recipient</span>
                    </button>
                </div>
                <div id="new-group-wrap" class="hidden">
                    <label for="new_group_name" class="block text-sm font-medium text-slate-700 mb-1">New group name</label>
                    <input type="text" name="new_group_name" id="new_group_name" placeholder="e.g. Marketing Team" class="w-full max-w-md rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Existing Groups -->
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 border-b border-white/10">
            <h2 class="text-lg md:text-xl font-bold text-white">SMS Groups</h2>
        </div>

        <!-- Mobile Card View (like Email Activities) -->
        <div class="lg:hidden divide-y divide-slate-100">
            <?php foreach ($smsGroups as $g): ?>
            <div class="p-4 hover:bg-slate-50">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-900 text-sm truncate"><?= h($g['name']) ?></div>
                        <div class="mt-1 text-xs text-slate-500 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 0 016 0z"></path></svg>
                            <span class="font-bold text-slate-700"><?= (int)($g['recipient_count'] ?? 0) ?></span>
                            <span class="text-slate-400">recipients</span>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <form method="post" action="<?= url('sms') ?>" class="inline sms-delete-group-form">
                            <input type="hidden" name="action" value="sms-group-delete">
                            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                            <button type="button" class="p-2 text-red-500 hover:bg-red-50 rounded-lg sms-delete-group-btn" data-name="<?= h($g['name']) ?>" title="Delete group" aria-label="Delete group <?= h($g['name']) ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($smsGroups)): ?>
            <div class="p-4 text-center text-slate-500">No SMS groups yet. Use the form above to create a group and add a recipient.</div>
            <?php endif; ?>
        </div>

        <!-- Desktop Table -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-bold text-slate-700 uppercase">Name</th>
                        <th class="px-4 md:px-8 py-3 text-center text-xs font-bold text-slate-700 uppercase">Recipients</th>
                        <th class="px-4 md:px-8 py-3 text-right text-xs font-bold text-slate-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($smsGroups as $g): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-3 font-medium text-slate-900"><?= h($g['name']) ?></td>
                        <td class="px-4 md:px-8 py-3 text-center text-slate-600"><?= (int)($g['recipient_count'] ?? 0) ?></td>
                        <td class="px-4 md:px-8 py-3 text-right">
                            <form method="post" action="<?= url('sms') ?>" class="inline sms-delete-group-form">
                                <input type="hidden" name="action" value="sms-group-delete">
                                <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                                <button type="button" class="p-2 text-red-500 hover:bg-red-50 rounded-lg sms-delete-group-btn" data-name="<?= h($g['name']) ?>" title="Delete group" aria-label="Delete group <?= h($g['name']) ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($smsGroups)): ?>
                    <tr>
                        <td colspan="3" class="px-4 md:px-8 py-8 text-center text-slate-500">No SMS groups yet. Use the form above to create a group and add a recipient.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Recipients -->
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 border-b border-white/10">
            <h2 class="text-lg md:text-xl font-bold text-white">All Recipients</h2>
        </div>

        <!-- Mobile Card View (no horizontal scroll) -->
        <div class="lg:hidden divide-y divide-slate-100">
            <?php foreach ($smsRecipients as $r): ?>
            <div class="p-4 hover:bg-slate-50">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-900 text-sm truncate"><?= h($r['name']) ?></div>
                        <div class="mt-1 text-xs text-slate-500 truncate"><?= h($r['phone_number']) ?></div>
                        <div class="mt-2 inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 0 016 0z"></path></svg>
                            <span class="truncate"><?= h($r['group_name']) ?></span>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <form method="post" action="<?= url('sms') ?>" class="inline sms-delete-recipient-form">
                            <input type="hidden" name="action" value="sms-recipient-delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="button" class="p-2 text-red-500 hover:bg-red-50 rounded-lg sms-delete-recipient-btn" data-name="<?= h($r['name']) ?>" title="Delete recipient" aria-label="Delete recipient <?= h($r['name']) ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($smsRecipients)): ?>
            <div class="p-4 text-center text-slate-500">No recipients yet. Add your first recipient above.</div>
            <?php endif; ?>
        </div>

        <!-- Desktop Table -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-bold text-slate-700 uppercase">Name</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-bold text-slate-700 uppercase">Phone</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-bold text-slate-700 uppercase">Group</th>
                        <th class="px-4 md:px-8 py-3 text-right text-xs font-bold text-slate-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($smsRecipients as $r): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-3 font-medium text-slate-900"><?= h($r['name']) ?></td>
                        <td class="px-4 md:px-8 py-3 text-slate-700"><?= h($r['phone_number']) ?></td>
                        <td class="px-4 md:px-8 py-3 text-slate-600"><?= h($r['group_name']) ?></td>
                        <td class="px-4 md:px-8 py-3 text-right">
                            <form method="post" action="<?= url('sms') ?>" class="inline sms-delete-recipient-form">
                                <input type="hidden" name="action" value="sms-recipient-delete">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="button" class="p-2 text-red-500 hover:bg-red-50 rounded-lg sms-delete-recipient-btn" data-name="<?= h($r['name']) ?>" title="Delete recipient" aria-label="Delete recipient <?= h($r['name']) ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($smsRecipients)): ?>
                    <tr>
                        <td colspan="4" class="px-4 md:px-8 py-8 text-center text-slate-500">No recipients yet. Use the form above to add a recipient to a group.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Send SMS Modal -->
<div id="sendSmsModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-slate-900/50 md:backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden modal-animate">
        <div class="bg-[#02396E] px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-white">Send SMS</h3>
            <button type="button" class="send-sms-close-btn p-2 text-white/80 hover:text-white rounded-lg transition-colors" aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="<?= url('sms') ?>" id="send-sms-form">
                <input type="hidden" name="action" value="sms-send">
                <div class="space-y-4">
                    <div>
                        <label for="send_group_id" class="block text-sm font-medium text-slate-700 mb-1">Group</label>
                        <select name="group_id" id="send_group_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                            <option value="">Select a group</option>
                            <?php foreach ($smsGroups as $g): ?>
                            <option value="<?= (int)$g['id'] ?>"><?= h($g['name']) ?> (<?= (int)($g['recipient_count'] ?? 0) ?> recipients)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="send-recipient-wrap">
                        <label for="send_recipient_id" class="block text-sm font-medium text-slate-700 mb-1">Send to (optional)</label>
                        <select name="recipient_id" id="send_recipient_id" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                            <option value="">All in group</option>
                        </select>
                    </div>
                    <div>
                        <label for="send_sms_message" class="block text-sm font-medium text-slate-700 mb-1">Message</label>
                        <textarea name="message" id="send_sms_message" required rows="4" placeholder="Type your SMS message here..." class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" class="send-sms-close-btn px-4 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                        <button type="submit" id="send-sms-submit-btn" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition-colors">
                            <span class="send-sms-btn-text">Send SMS</span>
                            <svg id="send-sms-spinner" class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Recipient Modal -->
<div id="smsDeleteRecipientModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-slate-900/50 md:backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden modal-animate">
        <div class="p-6 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-500 mb-4 mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Delete Recipient?</h3>
            <p class="text-slate-600">Are you sure you want to remove <span id="smsDeleteRecipientName" class="font-bold text-slate-900"></span> from the group?</p>
        </div>
        <div class="bg-slate-50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-3">
            <button id="smsConfirmDeleteRecipientBtn" type="button" class="w-full sm:w-auto px-6 py-2.5 bg-red-500 text-white font-bold rounded-xl hover:bg-red-700 transition-colors">Delete</button>
            <button id="smsCancelDeleteRecipientBtn" type="button" class="w-full sm:w-auto px-6 py-2.5 bg-white text-slate-600 font-bold rounded-xl border border-slate-200 hover:bg-slate-100 transition-colors">Cancel</button>
        </div>
    </div>
</div>

<!-- Delete Group Modal -->
<div id="smsDeleteModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/50 md:backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden modal-animate">
        <div class="p-6 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-500 mb-4 mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Delete SMS Group?</h3>
            <p class="text-slate-600">Are you sure you want to delete <span id="smsDeleteGroupName" class="font-bold text-slate-900"></span>? All recipients in this group will be removed.</p>
        </div>
        <div class="bg-slate-50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-3">
            <button id="smsConfirmDeleteBtn" type="button" class="w-full sm:w-auto px-6 py-2.5 bg-red-500 text-white font-bold rounded-xl hover:bg-red-700 transition-colors">Delete Group</button>
            <button id="smsCancelDeleteBtn" type="button" class="w-full sm:w-auto px-6 py-2.5 bg-white text-slate-600 font-bold rounded-xl border border-slate-200 hover:bg-slate-100 transition-colors">Cancel</button>
        </div>
    </div>
</div>

<script>
window.smsRecipientsByGroup = <?= json_encode($recipientsByGroup) ?>;
document.addEventListener('DOMContentLoaded', function() {
    var groupSelect = document.getElementById('recipient_group_id');
    var newGroupWrap = document.getElementById('new-group-wrap');
    var newGroupInput = document.getElementById('new_group_name');
    function toggleNewGroup() {
        if (groupSelect && groupSelect.value === 'new') {
            newGroupWrap.classList.remove('hidden');
            newGroupInput.setAttribute('required', 'required');
        } else {
            newGroupWrap.classList.add('hidden');
            newGroupInput.removeAttribute('required');
            newGroupInput.value = '';
        }
    }
    if (groupSelect) {
        groupSelect.addEventListener('change', toggleNewGroup);
        toggleNewGroup();
    }

    var sendGroupSelect = document.getElementById('send_group_id');
    var sendRecipientSelect = document.getElementById('send_recipient_id');
    if (sendGroupSelect && sendRecipientSelect && window.smsRecipientsByGroup) {
        function updateSendRecipientOptions() {
            var gid = sendGroupSelect.value ? parseInt(sendGroupSelect.value, 10) : 0;
            var list = window.smsRecipientsByGroup[gid] || [];
            sendRecipientSelect.innerHTML = '<option value="">All in group</option>';
            list.forEach(function(r) {
                var opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name + ' (' + r.phone_number + ')';
                sendRecipientSelect.appendChild(opt);
            });
        }
        sendGroupSelect.addEventListener('change', updateSendRecipientOptions);
        updateSendRecipientOptions();
    }

    var addRowBtn = document.getElementById('add-recipient-row');
    var recipientRows = document.getElementById('recipient-rows');
    if (addRowBtn && recipientRows) {
        addRowBtn.addEventListener('click', function() {
            var firstRow = recipientRows.querySelector('.recipient-row');
            if (!firstRow) return;
            var clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input').forEach(function(inp) { inp.value = ''; });
            recipientRows.appendChild(clone);
        });
    }

    var modal = document.getElementById('smsDeleteModal');
    var nameEl = document.getElementById('smsDeleteGroupName');
    var confirmBtn = document.getElementById('smsConfirmDeleteBtn');
    var cancelBtn = document.getElementById('smsCancelDeleteBtn');
    var formToSubmit = null;

    document.querySelectorAll('.sms-delete-group-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            formToSubmit = btn.closest('.sms-delete-group-form');
            nameEl.textContent = btn.getAttribute('data-name');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        formToSubmit = null;
    }
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
    confirmBtn.addEventListener('click', function() {
        if (formToSubmit) formToSubmit.submit();
    });

    var sendSmsModal = document.getElementById('sendSmsModal');
    var sendSmsOpenBtns = document.querySelectorAll('.send-sms-open-btn');
    var sendSmsCloseBtns = document.querySelectorAll('.send-sms-close-btn');
    function openSendSmsModal() {
        if (sendSmsModal) {
            sendSmsModal.classList.remove('hidden');
            sendSmsModal.classList.add('flex');
            if (sendGroupSelect) sendGroupSelect.dispatchEvent(new Event('change'));
        }
    }
    function closeSendSmsModal() {
        if (sendSmsModal) {
            sendSmsModal.classList.add('hidden');
            sendSmsModal.classList.remove('flex');
        }
    }
    sendSmsOpenBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) { e.preventDefault(); openSendSmsModal(); });
    });
    sendSmsCloseBtns.forEach(function(btn) {
        btn.addEventListener('click', closeSendSmsModal);
    });
    if (sendSmsModal) {
        sendSmsModal.addEventListener('click', function(e) {
            if (e.target === sendSmsModal) closeSendSmsModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sendSmsModal && !sendSmsModal.classList.contains('hidden')) closeSendSmsModal();
    });

    var recipientModal = document.getElementById('smsDeleteRecipientModal');
    var recipientNameEl = document.getElementById('smsDeleteRecipientName');
    var confirmRecipientBtn = document.getElementById('smsConfirmDeleteRecipientBtn');
    var cancelRecipientBtn = document.getElementById('smsCancelDeleteRecipientBtn');
    var recipientFormToSubmit = null;
    document.querySelectorAll('.sms-delete-recipient-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            recipientFormToSubmit = btn.closest('.sms-delete-recipient-form');
            if (recipientNameEl) recipientNameEl.textContent = btn.getAttribute('data-name') || 'this recipient';
            if (recipientModal) { recipientModal.classList.remove('hidden'); recipientModal.classList.add('flex'); }
        });
    });
    function closeRecipientModal() {
        if (recipientModal) { recipientModal.classList.add('hidden'); recipientModal.classList.remove('flex'); }
        recipientFormToSubmit = null;
    }
    if (cancelRecipientBtn) cancelRecipientBtn.addEventListener('click', closeRecipientModal);
    if (recipientModal) recipientModal.addEventListener('click', function(e) { if (e.target === recipientModal) closeRecipientModal(); });
    if (confirmRecipientBtn) confirmRecipientBtn.addEventListener('click', function() { if (recipientFormToSubmit) recipientFormToSubmit.submit(); });

    document.querySelectorAll('.sms-alert-close').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var alert = btn.closest('.sms-alert');
            if (alert) alert.remove();
            var container = document.getElementById('sms-alerts');
            if (container && container.querySelectorAll('.sms-alert').length === 0) container.remove();
        });
    });
    var alertContainer = document.getElementById('sms-alerts');
    if (alertContainer) {
        setTimeout(function() {
            alertContainer.querySelectorAll('.sms-alert').forEach(function(el) {
                el.style.transition = 'opacity 0.4s';
                el.style.opacity = '0';
                setTimeout(function() { el.remove(); if (alertContainer.querySelectorAll('.sms-alert').length === 0) alertContainer.remove(); }, 400);
            });
        }, 6000);
    }

    var sendSmsForm = document.getElementById('send-sms-form');
    var sendSmsSubmitBtn = document.getElementById('send-sms-submit-btn');
    var sendSmsBtnText = document.querySelector('.send-sms-btn-text');
    var sendSmsSpinner = document.getElementById('send-sms-spinner');
    if (sendSmsForm && sendSmsSubmitBtn) {
        sendSmsForm.addEventListener('submit', function() {
            sendSmsSubmitBtn.classList.add('send-sms-btn-loading');
            sendSmsSubmitBtn.disabled = true;
            if (sendSmsBtnText) sendSmsBtnText.textContent = 'Sending...';
            if (sendSmsSpinner) sendSmsSpinner.classList.remove('hidden');
        });
    }
});
</script>
