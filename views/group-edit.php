<?php
$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$group = null;
if ($groupId) {
    $st = $pdo->prepare('SELECT * FROM contact_groups WHERE id = ?');
    $st->execute([$groupId]);
    $group = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>
<div class="mb-4"><a href="<?= url('groups') ?>" class="text-[#02396E] hover:underline text-sm font-medium">← Contact groups</a></div>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase"><?= $group ? 'Edit' : 'Add' ?> group</h2></div>
    <form method="post" action="<?= url('groups') ?>">
        <input type="hidden" name="action" value="group-save">
        <?php if ($group): ?><input type="hidden" name="id" value="<?= (int)$group['id'] ?>"><?php endif; ?>
        <div class="p-6">
            <label class="block text-sm font-bold text-slate-700 mb-1">Group name *</label>
            <input type="text" name="name" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E]" value="<?= h($group['name'] ?? '') ?>" placeholder="e.g. Newsletter, VIP">
        </div>
        <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Save</button>
            <a href="<?= url('groups') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl">Cancel</a>
        </div>
    </form>
</div>
