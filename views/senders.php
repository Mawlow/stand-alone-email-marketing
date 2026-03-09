<?php
$accounts = $pdo->query('SELECT * FROM sender_accounts ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm md:text-base font-semibold text-white uppercase">Sender accounts</h2>
        <a href="<?= url('sender-edit') ?>" class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-lg text-[#ff8904] border-2 border-[#ff8904] hover:bg-[#f54a00] hover:text-white transition">Add sender</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-blue-50">
                <tr>
                    <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Name</th>
                    <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Email</th>
                    <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Host:Port</th>
                    <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Status</th>
                    <th class="px-4 md:px-8 py-3 text-right text-xs font-black text-slate-700 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $a): ?>
                <tr class="border-t border-slate-100 hover:bg-slate-50">
                    <td class="px-4 md:px-8 py-4 font-semibold"><?= h($a['name']) ?></td>
                    <td class="px-4 md:px-8 py-4 text-slate-600"><?= h($a['email']) ?></td>
                    <td class="px-4 md:px-8 py-4 text-slate-600"><?= h($a['host']) ?>:<?= h($a['port']) ?></td>
                    <td class="px-4 md:px-8 py-4"><span class="px-2 py-1 rounded text-xs font-bold <?= $a['is_active'] ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' ?>"><?= $a['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td class="px-4 md:px-8 py-4 text-right">
                        <a href="<?= url('sender-edit', ['id' => $a['id']]) ?>" class="p-2 text-[#02396E] hover:bg-blue-50 rounded" title="Edit">Edit</a>
                        <form method="post" action="<?= url('senders') ?>" class="inline" onsubmit="return confirm('Delete this sender?');">
                            <input type="hidden" name="action" value="sender-delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                            <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($accounts)): ?>
                <tr><td colspan="5" class="px-4 md:px-8 py-12 text-center text-slate-500">No senders. <a href="<?= url('sender-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add one</a>.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
