<?php
$contacts = $pdo->query("SELECT c.*, (SELECT GROUP_CONCAT(g.name) FROM contact_group_members m JOIN contact_groups g ON g.id = m.group_id WHERE m.contact_id = c.id) as group_names FROM marketing_contacts c ORDER BY c.email")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm md:text-base font-semibold text-white uppercase">Contacts (<?= count($contacts) ?>)</h2>
        <div class="flex gap-2">
            <a href="<?= url('contact-edit') ?>" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-[#ff8904] border-2 border-[#ff8904] hover:bg-[#f54a00] hover:text-white transition">Add contact</a>
            <a href="<?= url('contacts-import') ?>" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-[#ff8904] border-2 border-[#ff8904] hover:bg-[#f54a00] hover:text-white transition">Import CSV</a>
            <a href="<?= url('groups') ?>" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-white border-2 border-white/50 hover:bg-white/10 transition">Groups</a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-blue-50">
                <tr>
                    <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Email</th>
                    <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Company</th>
                    <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Groups</th>
                    <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Notes</th>
                    <th class="px-4 md:px-8 py-3 text-right text-xs font-black text-slate-700 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $c): ?>
                <tr class="border-t border-slate-100 hover:bg-slate-50">
                    <td class="px-4 md:px-8 py-4 font-semibold"><?= h($c['email']) ?></td>
                    <td class="px-4 md:px-8 py-4 text-slate-600"><?= h($c['company_name'] ?? '—') ?></td>
                    <td class="px-4 md:px-8 py-4 text-slate-600 text-sm"><?= h($c['group_names'] ?? '—') ?></td>
                    <td class="px-4 md:px-8 py-4 text-slate-600 text-sm max-w-xs truncate"><?= h($c['notes'] ?? '—') ?></td>
                    <td class="px-4 md:px-8 py-4 text-right">
                        <a href="<?= url('contact-edit', ['id' => $c['id']]) ?>" class="p-2 text-[#02396E] hover:bg-blue-50 rounded">Edit</a>
                        <form method="post" action="<?= url('contacts') ?>" class="inline" onsubmit="return confirm('Remove this contact?');">
                            <input type="hidden" name="action" value="contact-delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($contacts)): ?>
                <tr><td colspan="5" class="px-4 md:px-8 py-12 text-center text-slate-500">No contacts. <a href="<?= url('contact-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add one</a> or <a href="<?= url('contacts-import') ?>" class="text-[#ff8904] font-bold hover:underline">import CSV</a>.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
