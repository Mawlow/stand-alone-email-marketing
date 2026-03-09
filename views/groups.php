<?php
$groups = $pdo->query('SELECT g.*, (SELECT COUNT(*) FROM contact_group_members WHERE group_id = g.id) as member_count FROM contact_groups g ORDER BY g.name')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm md:text-base font-semibold text-white uppercase">Contact groups</h2>
        <a href="<?= url('group-edit') ?>" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-[#ff8904] border-2 border-[#ff8904] hover:bg-[#f54a00] hover:text-white transition">Add group</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-blue-50">
                <tr>
                    <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Name</th>
                    <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Contacts</th>
                    <th class="px-4 md:px-8 py-3 text-right text-xs font-black text-slate-700 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groups as $g): ?>
                <tr class="border-t border-slate-100 hover:bg-slate-50">
                    <td class="px-4 md:px-8 py-4 font-semibold"><?= h($g['name']) ?></td>
                    <td class="px-4 md:px-8 py-4 text-slate-600"><?= (int)($g['member_count'] ?? 0) ?></td>
                    <td class="px-4 md:px-8 py-4 text-right">
                        <a href="<?= url('group-edit', ['id' => $g['id']]) ?>" class="p-2 text-[#02396E] hover:bg-blue-50 rounded">Edit</a>
                        <form method="post" action="<?= url('groups') ?>" class="inline" onsubmit="return confirm('Delete this group? Contacts will not be removed.');">
                            <input type="hidden" name="action" value="group-delete"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                            <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($groups)): ?>
                <tr><td colspan="3" class="px-4 md:px-8 py-12 text-center text-slate-500">No groups. <a href="<?= url('group-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add one</a>, then assign contacts to groups when adding or editing contacts.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
