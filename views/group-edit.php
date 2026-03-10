<?php
$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$group = null;
if ($groupId) {
    $st = $pdo->prepare('SELECT * FROM contact_groups WHERE id = ?');
    $st->execute([$groupId]);
    $group = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
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

    @media (max-width: 1023px) {
        .page-content {
            margin-top: 1.5rem;
        }
    }
</style>

<!-- Header Banner (Desktop) -->
<div class="page-banner bg-[#02396E] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-2">
                <a href="<?= url('groups') ?>" class="text-[#ff8904] hover:text-orange-600 text-sm font-bold flex items-center gap-1 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back
                </a>
            </div>
            <h1 class="text-[2.5rem] font-bold leading-tight"><?= $group ? 'Edit Group' : 'Add New Group' ?></h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">
                <?= $group ? 'Update group details for ' . h($group['name']) : 'Create a new contact group.' ?>
            </p>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#02396E] px-4 py-4 text-white">
    <h1 class="text-xl font-bold"><?= $group ? 'Edit Group' : 'Add Group' ?></h1>
    <p class="text-blue-100/80 text-xs"><?= $group ? 'Update group details' : 'Create new group' ?></p>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 relative page-content">
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase"><?= $group ? 'Edit' : 'Add' ?> group</h2></div>
    <form method="post" action="<?= url('groups') ?>">
        <input type="hidden" name="action" value="group-save">
        <?php if ($group): ?><input type="hidden" name="id" value="<?= (int)$group['id'] ?>"><?php endif; ?>
        <div class="p-6">
            <label class="block text-sm font-bold text-slate-700 mb-1">Group name *</label>
            <input type="text" name="name" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E]" value="<?= h($group['name'] ?? '') ?>" placeholder="e.g. Newsletter, VIP">
        </div>
        <div class="bg-slate-50 px-4 md:px-6 py-3 flex flex-col sm:flex-row gap-3">
            <button type="submit" class="px-6 py-3 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c] touch-manipulation w-full sm:w-auto">Save</button>
            <a href="<?= url('groups') ?>" class="px-6 py-3 bg-slate-200 text-slate-700 font-bold rounded-xl text-center touch-manipulation w-full sm:w-auto">Cancel</a>
        </div>
    </form>
</div>
