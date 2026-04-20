<?php
$waGroupsStmt = $pdo->prepare('SELECT g.*, (SELECT COUNT(*) FROM whatsapp_recipients WHERE group_id = g.id) as recipient_count FROM whatsapp_groups g WHERE g.user_id = ? ORDER BY g.name');
$waGroupsStmt->execute([$userId]);
$waGroups = $waGroupsStmt->fetchAll(PDO::FETCH_ASSOC);
$waRecipientsStmt = $pdo->prepare('SELECT r.*, g.name as group_name FROM whatsapp_recipients r JOIN whatsapp_groups g ON g.id = r.group_id AND g.user_id = ? ORDER BY g.name, r.name');
$waRecipientsStmt->execute([$userId]);
$waRecipients = $waRecipientsStmt->fetchAll(PDO::FETCH_ASSOC);
$recipientsByGroup = [];
foreach ($waRecipients as $r) {
    $recipientsByGroup[(int)$r['group_id']][] = ['id' => (int)$r['id'], 'name' => $r['name'], 'phone_number' => $r['phone_number']];
}
$waProvider = strtolower(trim((string) ($config['whatsapp_provider'] ?? 'twilio')));
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
    .wa-banner { margin-bottom: 1rem; }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .modal-animate { animation: modalFadeIn 0.2s ease-out forwards; }
    .wa-alert { margin-bottom: 1rem; }
    .send-wa-btn-loading { pointer-events: none; opacity: 0.8; }
</style>

<div class="wa-banner bg-[#075e54] text-white shadow-lg relative overflow-hidden hidden lg:block min-h-[97px] box-border border-b border-emerald-950/40 flex items-center">
    <div class="max-w-6xl mx-auto w-full px-3 sm:px-4 md:px-6 lg:px-8 relative z-10 flex flex-row flex-wrap items-center justify-between gap-4 py-3">
        <div class="min-w-0 pt-2 md:pt-3">
            <h1 class="text-xl font-bold leading-tight md:text-2xl">WhatsApp</h1>
            <p class="text-emerald-100/90 mt-0.5 text-xs font-medium md:text-sm leading-snug"><?= $waProvider === 'twilio' ? 'Groups and recipients — send via Twilio WhatsApp.' : 'Groups and recipients — send via Meta WhatsApp Cloud API.' ?></p>
        </div>
        <button type="button" class="send-wa-open-btn inline-flex items-center gap-2 px-5 py-2.5 bg-[#25D366] text-white text-sm font-bold rounded-xl hover:bg-[#20bd5a] transition-colors shadow-md shrink-0">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            <span>Send WhatsApp</span>
        </button>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
</div>

<div class="lg:hidden bg-[#075e54] px-6 py-6 text-white mb-2 border-b border-emerald-950/40 box-border min-h-[97px] flex flex-col justify-center">
    <div class="flex items-center justify-between gap-3 pt-2 md:pt-0">
        <div class="min-w-0">
            <h1 class="text-xl font-bold leading-tight">WhatsApp</h1>
            <p class="text-emerald-100/90 text-xs mt-1 leading-snug">Manage groups and send messages.</p>
        </div>
        <button type="button" class="send-wa-open-btn inline-flex items-center gap-1.5 px-3 py-2 bg-[#25D366] text-white text-xs font-bold rounded-lg hover:bg-[#20bd5a] transition-colors flex-shrink-0">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            <span>Send</span>
        </button>
    </div>
</div>

<?php $waFlashSuccess = $flashSuccess ?? null; $waFlashError = $flashError ?? null; ?>
<?php if ($waFlashSuccess || $waFlashError): ?>
<div id="wa-alerts" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-4 space-y-2">
    <?php if ($waFlashSuccess): ?>
    <div class="wa-alert wa-alert-success flex items-center justify-between gap-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 font-medium">
        <span><?= h($waFlashSuccess) ?></span>
        <button type="button" class="wa-alert-close p-1 rounded-lg hover:bg-emerald-100 transition-colors" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    <?php endif; ?>
    <?php if ($waFlashError): ?>
    <div class="wa-alert wa-alert-error flex items-center justify-between gap-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 font-medium">
        <span><?= h($waFlashError) ?></span>
        <button type="button" class="wa-alert-close p-1 rounded-lg hover:bg-red-100 transition-colors" aria-label="Dismiss">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 space-y-8">
    <div class="rounded-2xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-950">
        <?php if ($waProvider === 'twilio'): ?>
        <strong class="font-bold">Twilio:</strong> In <code class="text-xs bg-white/80 px-1 rounded">.env</code> set <code class="text-xs bg-white/80 px-1 rounded">TWILIO_ACCOUNT_SID</code>, <code class="text-xs bg-white/80 px-1 rounded">TWILIO_AUTH_TOKEN</code>, and <code class="text-xs bg-white/80 px-1 rounded">TWILIO_WHATSAPP_FROM</code> (sandbox: e.g. <code class="text-xs bg-white/80 px-1 rounded">whatsapp:+14155238886</code>). On your phone, <strong>join the Twilio sandbox</strong> with the code from the Console, then <strong>add that WhatsApp number</strong> as a recipient. Send a plain <strong>session message</strong> in the form. For <strong>production</strong>, replace <code class="text-xs bg-white/80 px-1 rounded">TWILIO_WHATSAPP_FROM</code> with your approved <code class="text-xs bg-white/80 px-1 rounded">whatsapp:+…</code> sender. Optional: <code class="text-xs bg-white/80 px-1 rounded">TWILIO_WHATSAPP_CONTENT_SID</code> (+ variables) for approved templates — fill “Template name” in the app to use it.
        <?php else: ?>
        <strong class="font-bold">Meta Cloud API (optional):</strong> You set <code class="text-xs bg-white/80 px-1 rounded">WHATSAPP_PROVIDER=meta</code>. Add <code class="text-xs bg-white/80 px-1 rounded">WHATSAPP_ACCESS_TOKEN</code> and <code class="text-xs bg-white/80 px-1 rounded">WHATSAPP_PHONE_NUMBER_ID</code> to <code class="text-xs bg-white/80 px-1 rounded">.env</code> (Meta for Developers → WhatsApp → API Setup). Plain text works inside the <strong>24-hour</strong> window; otherwise use an approved <strong>Meta template</strong> (Template name; message can be empty).
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#141d2e] px-4 md:px-8 py-4 border-b border-slate-700/50">
            <h2 class="text-lg md:text-xl font-bold text-white">Create Group & Add Recipient</h2>
        </div>
        <div class="p-4 md:p-6">
            <form method="post" action="<?= url('whatsapp') ?>" id="wa-group-recipient-form" class="space-y-4">
                <input type="hidden" name="action" value="wa-recipient-add">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="wa_recipient_group_id" class="block text-sm font-medium text-slate-700 mb-1">Group</label>
                        <select name="group_id" id="wa_recipient_group_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                            <option value="">Select group</option>
                            <option value="new">— Create new group —</option>
                            <?php foreach ($waGroups as $g): ?>
                            <option value="<?= (int)$g['id'] ?>"><?= h($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2"></div>
                </div>
                <div id="wa-recipient-rows" class="space-y-3">
                    <div class="wa-recipient-row grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Recipient name</label>
                            <input type="text" name="recipient_name[]" placeholder="Display name" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">WhatsApp number</label>
                            <input type="text" name="phone_number[]" placeholder="Country code + number, e.g. 639171234567" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:gap-3 sm:w-1/2 sm:justify-start items-center">
                    <button type="button" id="wa-add-recipient-row" class="w-full sm:w-auto px-3 py-2 border border-slate-300 text-slate-700 font-medium rounded-xl hover:bg-slate-50 transition-colors">
                        <span class="sm:hidden">Add another</span>
                        <span class="hidden sm:inline">Add another recipient</span>
                    </button>
                    <button type="submit" class="w-full sm:w-auto px-3 py-2.5 bg-[#f54a00] text-white font-bold rounded-xl hover:bg-[#e04400] transition-colors">
                        <span class="sm:hidden">Add</span>
                        <span class="hidden sm:inline">Add recipient</span>
                    </button>
                </div>
                <div id="wa-new-group-wrap" class="hidden">
                    <label for="wa_new_group_name" class="block text-sm font-medium text-slate-700 mb-1">New group name</label>
                    <input type="text" name="new_group_name" id="wa_new_group_name" placeholder="e.g. VIP customers" class="w-full max-w-md rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#141d2e] px-4 md:px-8 py-4 border-b border-slate-700/50">
            <h2 class="text-lg md:text-xl font-bold text-white">All recipients</h2>
        </div>

        <div class="lg:hidden divide-y divide-slate-100">
            <?php foreach ($waRecipients as $r): ?>
            <div class="p-4 hover:bg-slate-50">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-900 text-sm truncate"><?= h($r['name']) ?></div>
                        <div class="mt-1 text-xs text-slate-500 truncate"><?= h($r['phone_number']) ?></div>
                        <div class="mt-2 inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700">
                            <span class="truncate"><?= h($r['group_name']) ?></span>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <form method="post" action="<?= url('whatsapp') ?>" class="inline wa-delete-recipient-form">
                            <input type="hidden" name="action" value="wa-recipient-delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="button" class="p-2 text-red-500 hover:bg-red-50 rounded-lg wa-delete-recipient-btn" data-name="<?= h($r['name']) ?>" title="Delete recipient" aria-label="Delete <?= h($r['name']) ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($waRecipients)): ?>
            <div class="p-4 text-center text-slate-500">No recipients yet. Add your first recipient above.</div>
            <?php endif; ?>
        </div>

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
                    <?php foreach ($waRecipients as $r): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-3 font-medium text-slate-900"><?= h($r['name']) ?></td>
                        <td class="px-4 md:px-8 py-3 text-slate-700"><?= h($r['phone_number']) ?></td>
                        <td class="px-4 md:px-8 py-3 text-slate-600"><?= h($r['group_name']) ?></td>
                        <td class="px-4 md:px-8 py-3 text-right">
                            <form method="post" action="<?= url('whatsapp') ?>" class="inline wa-delete-recipient-form">
                                <input type="hidden" name="action" value="wa-recipient-delete">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="button" class="p-2 text-red-500 hover:bg-red-50 rounded-lg wa-delete-recipient-btn" data-name="<?= h($r['name']) ?>" title="Delete recipient" aria-label="Delete <?= h($r['name']) ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($waRecipients)): ?>
                    <tr>
                        <td colspan="4" class="px-4 md:px-8 py-8 text-center text-slate-500">No recipients yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="sendWaModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-slate-900/50 md:backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto modal-animate">
        <div class="bg-[#075e54] px-6 py-4 flex items-center justify-between sticky top-0">
            <h3 class="text-lg font-bold text-white">Send WhatsApp</h3>
            <button type="button" class="send-wa-close-btn p-2 text-white/80 hover:text-white rounded-lg transition-colors" aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="<?= url('whatsapp') ?>" id="send-wa-form">
                <input type="hidden" name="action" value="wa-send">
                <div class="space-y-4">
                    <div>
                        <label for="wa_send_group_id" class="block text-sm font-medium text-slate-700 mb-1">Group</label>
                        <select name="group_id" id="wa_send_group_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#075e54] focus:border-[#075e54]">
                            <option value="">Select a group</option>
                            <?php foreach ($waGroups as $g): ?>
                            <option value="<?= (int)$g['id'] ?>"><?= h($g['name']) ?> (<?= (int)($g['recipient_count'] ?? 0) ?> recipients)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="wa_send_recipient_id" class="block text-sm font-medium text-slate-700 mb-1">Send to (optional)</label>
                        <select name="recipient_id" id="wa_send_recipient_id" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#075e54] focus:border-[#075e54]">
                            <option value="">All in group</option>
                        </select>
                    </div>
                    <div>
                        <label for="wa_send_message" class="block text-sm font-medium text-slate-700 mb-1">Message (session / text)</label>
                        <textarea name="message" id="wa_send_message" rows="4" placeholder="<?= $waProvider === 'twilio' ? 'Sandbox: plain text after the recipient joined your Twilio sandbox.' : 'Plain text — only if user messaged you within 24 hours.' ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#075e54] focus:border-[#075e54]"></textarea>
                    </div>
                    <div class="border-t border-slate-100 pt-4 space-y-2">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide"><?= $waProvider === 'twilio' ? 'Or Twilio template (Content SID in .env)' : 'Or use Meta template' ?></p>
                        <div>
                            <label for="wa_template_name" class="block text-sm font-medium text-slate-700 mb-1"><?= $waProvider === 'twilio' ? 'Template name (any text — enables Content send if TWILIO_WHATSAPP_CONTENT_SID is set)' : 'Template name' ?></label>
                            <input type="text" name="template_name" id="wa_template_name" placeholder="<?= $waProvider === 'twilio' ? 'e.g. notify — requires TWILIO_WHATSAPP_CONTENT_SID' : 'e.g. hello_world' ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#075e54] focus:border-[#075e54]">
                        </div>
                        <div>
                            <label for="wa_template_language" class="block text-sm font-medium text-slate-700 mb-1">Template language code <?= $waProvider === 'twilio' ? '(ignored for Twilio)' : '' ?></label>
                            <input type="text" name="template_language" id="wa_template_language" value="en_US" placeholder="en_US" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:ring-2 focus:ring-[#075e54] focus:border-[#075e54]">
                        </div>
                        <?php if ($waProvider === 'twilio'): ?>
                        <p class="text-xs text-slate-500">Twilio ignores Meta language codes. Use <code class="bg-slate-100 px-1 rounded">TWILIO_WHATSAPP_CONTENT_VARIABLES</code> in <code class="bg-slate-100 px-1 rounded">.env</code> for JSON variables.</p>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" class="send-wa-close-btn px-4 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                        <button type="submit" id="send-wa-submit-btn" class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#25D366] text-white font-bold rounded-xl hover:bg-[#20bd5a] transition-colors">
                            <span class="send-wa-btn-text">Send</span>
                            <svg id="send-wa-spinner" class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
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

<div id="waDeleteRecipientModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-slate-900/50 md:backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden modal-animate">
        <div class="p-6 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-500 mb-4 mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-2">Delete recipient?</h3>
            <p class="text-slate-600">Remove <span id="waDeleteRecipientName" class="font-bold text-slate-900"></span> from the list?</p>
        </div>
        <div class="bg-slate-50 px-6 py-4 flex flex-col sm:flex-row-reverse gap-3">
            <button id="waConfirmDeleteRecipientBtn" type="button" class="w-full sm:w-auto px-6 py-2.5 bg-red-500 text-white font-bold rounded-xl hover:bg-red-700 transition-colors">Delete</button>
            <button id="waCancelDeleteRecipientBtn" type="button" class="w-full sm:w-auto px-6 py-2.5 bg-white text-slate-600 font-bold rounded-xl border border-slate-200 hover:bg-slate-100 transition-colors">Cancel</button>
        </div>
    </div>
</div>

<script>
window.waRecipientsByGroup = <?= json_encode($recipientsByGroup) ?>;
document.addEventListener('DOMContentLoaded', function() {
    var groupSelect = document.getElementById('wa_recipient_group_id');
    var newGroupWrap = document.getElementById('wa-new-group-wrap');
    var newGroupInput = document.getElementById('wa_new_group_name');
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

    var sendGroupSelect = document.getElementById('wa_send_group_id');
    var sendRecipientSelect = document.getElementById('wa_send_recipient_id');
    if (sendGroupSelect && sendRecipientSelect && window.waRecipientsByGroup) {
        function updateSendRecipientOptions() {
            var gid = sendGroupSelect.value ? parseInt(sendGroupSelect.value, 10) : 0;
            var list = window.waRecipientsByGroup[gid] || [];
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

    var addRowBtn = document.getElementById('wa-add-recipient-row');
    var recipientRows = document.getElementById('wa-recipient-rows');
    if (addRowBtn && recipientRows) {
        addRowBtn.addEventListener('click', function() {
            var firstRow = recipientRows.querySelector('.wa-recipient-row');
            if (!firstRow) return;
            var clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input').forEach(function(inp) { inp.value = ''; });
            recipientRows.appendChild(clone);
        });
    }

    var sendWaModal = document.getElementById('sendWaModal');
    var sendWaOpenBtns = document.querySelectorAll('.send-wa-open-btn');
    var sendWaCloseBtns = document.querySelectorAll('.send-wa-close-btn');
    function openSendWaModal() {
        if (sendWaModal) {
            sendWaModal.classList.remove('hidden');
            sendWaModal.classList.add('flex');
            if (sendGroupSelect) sendGroupSelect.dispatchEvent(new Event('change'));
        }
    }
    function closeSendWaModal() {
        if (sendWaModal) {
            sendWaModal.classList.add('hidden');
            sendWaModal.classList.remove('flex');
        }
    }
    sendWaOpenBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) { e.preventDefault(); openSendWaModal(); });
    });
    sendWaCloseBtns.forEach(function(btn) {
        btn.addEventListener('click', closeSendWaModal);
    });
    if (sendWaModal) {
        sendWaModal.addEventListener('click', function(e) {
            if (e.target === sendWaModal) closeSendWaModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sendWaModal && !sendWaModal.classList.contains('hidden')) closeSendWaModal();
    });

    var recipientModal = document.getElementById('waDeleteRecipientModal');
    var recipientNameEl = document.getElementById('waDeleteRecipientName');
    var confirmRecipientBtn = document.getElementById('waConfirmDeleteRecipientBtn');
    var cancelRecipientBtn = document.getElementById('waCancelDeleteRecipientBtn');
    var recipientFormToSubmit = null;
    document.querySelectorAll('.wa-delete-recipient-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            recipientFormToSubmit = btn.closest('.wa-delete-recipient-form');
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

    document.querySelectorAll('.wa-alert-close').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var alert = btn.closest('.wa-alert');
            if (alert) alert.remove();
            var container = document.getElementById('wa-alerts');
            if (container && container.querySelectorAll('.wa-alert').length === 0) container.remove();
        });
    });
    var alertContainer = document.getElementById('wa-alerts');
    if (alertContainer) {
        setTimeout(function() {
            alertContainer.querySelectorAll('.wa-alert').forEach(function(el) {
                el.style.transition = 'opacity 0.4s';
                el.style.opacity = '0';
                setTimeout(function() { el.remove(); if (alertContainer.querySelectorAll('.wa-alert').length === 0) alertContainer.remove(); }, 400);
            });
        }, 6000);
    }

    var sendWaForm = document.getElementById('send-wa-form');
    var sendWaSubmitBtn = document.getElementById('send-wa-submit-btn');
    var sendWaBtnText = document.querySelector('.send-wa-btn-text');
    var sendWaSpinner = document.getElementById('send-wa-spinner');
    if (sendWaForm && sendWaSubmitBtn) {
        sendWaForm.addEventListener('submit', function() {
            sendWaSubmitBtn.classList.add('send-wa-btn-loading');
            sendWaSubmitBtn.disabled = true;
            if (sendWaBtnText) sendWaBtnText.textContent = 'Sending...';
            if (sendWaSpinner) sendWaSpinner.classList.remove('hidden');
        });
    }
});
</script>
