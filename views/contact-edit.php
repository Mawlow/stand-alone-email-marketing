<?php
$contactId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$contact = null;
$contactGroupIds = [];
if ($contactId) {
    $st = $pdo->prepare('SELECT * FROM marketing_contacts WHERE id=? AND user_id=?');
    $st->execute([$contactId, $userId]);
    $contact = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($contact) {
        $st2 = $pdo->prepare('SELECT group_id FROM contact_group_members WHERE contact_id=?');
        $st2->execute([$contactId]);
        $contactGroupIds = array_column($st2->fetchAll(PDO::FETCH_ASSOC), 'group_id');
    }
}
$allGroupsStmt = $pdo->prepare('SELECT id, name FROM contact_groups WHERE user_id = ? ORDER BY name');
$allGroupsStmt->execute([$userId]);
$allGroups = $allGroupsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    /* Hide the default title area from index.php */
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
        max-width: 72rem; /* 6xl */
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

    @media (max-width: 1023px) {
        .page-content {
            margin-top: 1.5rem;
        }
    }
</style>

<!-- Header Banner (Desktop) -->
<div class="page-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-2">
                <a href="<?= url('contacts') ?>" class="text-[#ff8904] hover:text-orange-600 text-sm font-bold flex items-center gap-1 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back
                </a>
            </div>
            <h1 class="text-[2.5rem] font-bold leading-tight"><?= $contact ? 'Edit Contact' : 'Add New Contact' ?></h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">
                <?= $contact ? 'Update details for ' . h($contact['email']) : 'Create a new profile for your marketing list.' ?>
            </p>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#141d2e] px-4 py-4 text-white">
    <h1 class="text-xl font-bold"><?= $contact ? 'Edit Contact' : 'Add Contact' ?></h1>
    <p class="text-blue-100/80 text-xs"><?= $contact ? 'Update contact details' : 'Add new contact' ?></p>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 relative page-content">
    <!-- Background element starting from sidebar -->
    <div class="fixed inset-y-0 left-64 right-0 bg-slate-50 -z-10 border-l border-slate-200 hidden lg:block"></div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 border-b border-white/10 hidden lg:block">
            <h2 class="text-lg font-bold text-white uppercase tracking-wider">Contact Information</h2>
        </div>
        <form method="post" action="<?= url('contacts') ?>">
            <input type="hidden" name="action" value="contact-save">
            <?php if ($contact): ?><input type="hidden" name="id" value="<?= (int)$contact['id'] ?>"><?php endif; ?>
            
            <div class="px-4 md:px-8 py-6 md:py-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required class="w-full rounded-xl border-2 border-slate-100 px-4 py-3 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E] transition-all" value="<?= h($contact['email'] ?? '') ?>" placeholder="hr@company.com">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" class="w-full rounded-xl border-2 border-slate-100 px-4 py-3 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E] transition-all" value="<?= h($contact['company_name'] ?? '') ?>" placeholder="Acme Corp">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full rounded-xl border-2 border-slate-100 px-4 py-3 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E] transition-all" placeholder="Add any internal notes about this contact..."><?= h($contact['notes'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-3">Assign to Groups</label>
                    <div class="bg-slate-50 p-4 rounded-xl border-2 border-slate-100">
                        <div class="flex flex-wrap gap-3">
                            <?php foreach ($allGroups as $gr): ?>
                            <label class="inline-flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-slate-200 cursor-pointer hover:border-[#02396E] transition-colors group">
                                <input type="checkbox" name="group_ids[]" value="<?= (int)$gr['id'] ?>" class="w-4 h-4 rounded border-slate-300 text-[#02396E] focus:ring-[#02396E]" <?= in_array((int)$gr['id'], $contactGroupIds, true) ? 'checked' : '' ?>>
                                <span class="text-sm font-bold text-slate-700 group-hover:text-[#02396E]"><?= h($gr['name']) ?></span>
                            </label>
                            <?php endforeach; ?>
                            <?php if (empty($allGroups)): ?>
                                <div class="w-full text-center py-2">
                                    <span class="text-slate-500 text-sm italic">No groups found.</span>
                                    <a href="<?= url('group-edit') ?>" class="text-[#ff8904] font-bold hover:underline ml-1">Create your first group</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 px-4 md:px-8 py-4 flex items-center justify-between border-t border-slate-100 gap-2">
                <p class="text-xs text-[#ff8904]/70 font-medium hidden sm:block">* Required fields</p>
                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="<?= url('contacts') ?>" class="flex-1 sm:flex-none px-4 py-2 bg-white text-slate-600 font-bold rounded-lg border-2 border-slate-200 hover:bg-slate-50 transition-colors text-center text-sm">Cancel</a>
                    <button type="submit" class="flex-1 sm:flex-none px-4 py-2 bg-[#ff8904] text-white font-bold rounded-lg hover:bg-orange-600 shadow-md transition-all text-sm">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
