<?php
$groups = $pdo->query('SELECT g.*, (SELECT COUNT(*) FROM contact_group_members WHERE group_id = g.id) as member_count FROM contact_groups g ORDER BY g.name')->fetchAll(PDO::FETCH_ASSOC);
$totalGroups = count($groups);
?>
<style>
    /* Hide the default title area from index.php when on the groups page */
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
        max-width: 72rem;
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
</style>

<!-- Groups Banner (Desktop) -->
<div class="page-banner bg-[#02396E] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Contact Groups</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Organize contacts into segments for targeted campaigns.</p>
        </div>
        <div class="text-right">
            <p class="text-4xl font-bold text-white"><?= $totalGroups ?></p>
            <p class="text-blue-100/80 text-sm font-medium">groups</p>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#02396E] px-4 py-4 text-white">
    <h1 class="text-xl font-bold">Contact Groups</h1>
    <p class="text-blue-100/80 text-xs">Organize contacts</p>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 relative mt-4 lg:mt-0">
    <!-- Background element starting from sidebar -->
    <div class="fixed inset-y-0 left-64 right-0 bg-slate-50 -z-10 border-l border-slate-200 hidden lg:block"></div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h2 class="text-sm md:text-base font-semibold text-white uppercase">Groups</h2>
        <a href="<?= url('group-edit') ?>" class="self-start md:self-auto inline-flex items-center gap-1 px-2 py-1.5 md:px-3 md:py-2 text-xs md:text-sm font-bold rounded-lg text-[#ff8904] border-2 border-[#ff8904] hover:bg-[#f54a00] hover:text-white transition touch-manipulation">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add group
        </a>
    </div>
    
    <!-- Mobile Card View -->
    <div class="md:hidden divide-y divide-slate-100">
        <?php foreach ($groups as $g): ?>
        <div class="p-4 hover:bg-slate-50">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-slate-900"><?= h($g['name']) ?></h3>
                <span class="text-sm text-slate-600"><?= (int)($g['member_count'] ?? 0) ?> contacts</span>
            </div>
            <div class="flex gap-2">
                <a href="<?= url('group-edit', ['id' => $g['id']]) ?>" class="flex-1 text-center px-3 py-2 text-xs text-[#02396E] border border-[#02396E] rounded-lg touch-manipulation">Edit</a>
                <form method="post" action="<?= url('groups') ?>" class="flex-1" onsubmit="return confirm('Delete this group? Contacts will not be removed.');">
                    <input type="hidden" name="action" value="group-delete"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                    <button type="submit" class="w-full px-3 py-2 text-xs text-red-600 border border-red-600 rounded-lg touch-manipulation">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($groups)): ?>
        <div class="p-8 text-center text-slate-500 text-sm">No groups. <a href="<?= url('group-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add one</a>, then assign contacts to groups when adding or editing contacts.</div>
        <?php endif; ?>
    </div>
    
    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
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
                    <td class="px-4 md:px-8 py-3 md:py-4 font-semibold"><?= h($g['name']) ?></td>
                    <td class="px-4 md:px-8 py-3 md:py-4 text-slate-600"><?= (int)($g['member_count'] ?? 0) ?></td>
                    <td class="px-4 md:px-8 py-3 md:py-4 text-right">
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
</div>
