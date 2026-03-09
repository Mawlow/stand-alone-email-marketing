<?php
$contactId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$contact = null;
$contactGroupIds = [];
if ($contactId) {
    $st = $pdo->prepare('SELECT * FROM marketing_contacts WHERE id=?');
    $st->execute([$contactId]);
    $contact = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($contact) {
        $st2 = $pdo->prepare('SELECT group_id FROM contact_group_members WHERE contact_id=?');
        $st2->execute([$contactId]);
        $contactGroupIds = array_column($st2->fetchAll(PDO::FETCH_ASSOC), 'group_id');
    }
}
$allGroups = $pdo->query('SELECT id, name FROM contact_groups ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="mb-4"><a href="<?= url('contacts') ?>" class="text-[#02396E] hover:underline text-sm font-medium">← Contacts</a></div>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase"><?= $contact ? 'Edit' : 'Add' ?> contact</h2></div>
    <form method="post" action="<?= url('contacts') ?>">
        <input type="hidden" name="action" value="contact-save">
        <?php if ($contact): ?><input type="hidden" name="id" value="<?= (int)$contact['id'] ?>"><?php endif; ?>
        <div class="p-6 space-y-4">
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Email *</label><input type="email" name="email" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E]" value="<?= h($contact['email'] ?? '') ?>" placeholder="hr@company.com"></div>
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Company name</label><input type="text" name="company_name" class="w-full rounded-xl border border-slate-200 px-4 py-2.5" value="<?= h($contact['company_name'] ?? '') ?>" placeholder="Acme Corp"></div>
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Notes</label><textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-200 px-4 py-2.5"><?= h($contact['notes'] ?? '') ?></textarea></div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Groups</label>
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($allGroups as $gr): ?>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="group_ids[]" value="<?= (int)$gr['id'] ?>" class="rounded border-slate-300 text-[#02396E]" <?= in_array((int)$gr['id'], $contactGroupIds, true) ? 'checked' : '' ?>>
                        <span class="text-sm text-slate-700"><?= h($gr['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php if (empty($allGroups)): ?><span class="text-slate-500 text-sm">No groups yet. <a href="<?= url('group-edit') ?>" class="text-[#02396E] hover:underline">Create one</a></span><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Save</button>
            <a href="<?= url('contacts') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl">Cancel</a>
        </div>
    </form>
</div>
