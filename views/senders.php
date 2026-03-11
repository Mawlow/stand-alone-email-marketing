<?php
$accounts = $pdo->query('SELECT * FROM sender_accounts ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$totalSenders = count($accounts);
$activeSenders = 0;
foreach ($accounts as $a) { if ($a['is_active']) $activeSenders++; }
?>
<style>
    /* Hide the default title area from index.php when on the senders page */
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
</style>

<!-- Senders Banner (Desktop) -->
<div class="page-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Sender Accounts</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Manage the identities powering your email delivery.</p>
        </div>
        <!-- Search Bar -->
        <div class="relative z-10 w-full md:w-[400px]">
            <div class="relative group">
                <input type="text" id="senderSearch" placeholder="Search senders..." class="w-full bg-white rounded-xl py-3 pl-12 pr-20 text-slate-900 text-base placeholder-slate-400 focus:outline-none transition-all shadow-inner">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                
                <button id="clearSearch" class="absolute right-12 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hidden" title="Clear search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <button class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 bg-[#02396E] text-white rounded-full shadow-md" title="Search now">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </button>
            </div>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#02396E] px-4 py-4 text-white">
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Sender Accounts</h1>
        </div>
        <p class="text-blue-100/80 text-xs">Manage your senders</p>
        <div class="relative">
            <input type="text" id="senderSearchMobile" placeholder="Search senders..." class="w-full bg-white rounded-lg py-2 pl-10 pr-16 text-slate-900 text-sm placeholder-slate-400 focus:outline-none">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <button id="clearSearchMobile" class="absolute right-10 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600 hidden">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <button class="absolute right-2 top-1/2 -translate-y-1/2 p-1 bg-[#02396E] text-white rounded-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
            </button>
        </div>
    </div>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 relative">
    <!-- Background element starting from sidebar -->
    <div class="fixed inset-y-0 left-64 right-0 bg-slate-50 -z-10 border-l border-slate-200 hidden lg:block"></div>

    <!-- Mobile Stats Cards -->
    <div class="lg:hidden grid grid-cols-2 gap-3 mt-4 mb-4">
        <div class="bg-white rounded-xl shadow-md border-2 border-blue-200 p-4 flex flex-col items-center gap-2">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-[#02396E]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900"><?= $activeSenders ?></p>
                <p class="text-[10px] font-bold text-slate-500 uppercase">active</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border-2 border-blue-200 p-4 flex flex-col items-center gap-2">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center text-[#ff8904]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900"><?= $totalSenders ?></p>
                <p class="text-[10px] font-bold text-slate-500 uppercase">total</p>
            </div>
        </div>
    </div>

    <!-- Desktop Stats Cards -->
    <div class="hidden lg:grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-md border-2 border-blue-200 p-4 md:p-6 flex items-center gap-6 hover:bg-slate-50 transition-colors">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-[#02396E] shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <div class="flex-1 flex justify-between items-end">
                <div>
                    <p class="text-lg font-bold text-black uppercase tracking-wide mb-1">Active Senders</p>
                    <p class="text-slate-500 text-sm font-medium">Currently enabled for sending.</p>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-bold text-slate-900 leading-none"><?= $activeSenders ?></p>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-1">active</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border-2 border-blue-200 p-4 md:p-6 flex items-center gap-6 hover:bg-slate-50 transition-colors">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-[#02396E] shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <div class="flex-1 flex justify-between items-end">
                <div>
                    <p class="text-lg font-bold text-black uppercase tracking-wide mb-1">Total Accounts</p>
                    <p class="text-slate-500 text-sm font-medium">Total SMTP profiles configured.</p>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-bold text-slate-900 leading-none"><?= $totalSenders ?></p>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-1">total</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/10 flex flex-col sm:flex-row justify-between items-center gap-3">
            <h2 class="text-lg md:text-2xl font-bold text-white">Senders</h2>
            <a href="<?= url('sender-edit') ?>" class="w-full sm:w-auto inline-flex items-center justify-center px-3.5 py-1.5 bg-[#ff8904] text-white text-sm font-bold rounded-xl hover:bg-orange-600 transition-colors">+ Add Sender</a>
        </div>
        
        <!-- Mobile Card View -->
        <div class="lg:hidden divide-y divide-slate-100">
            <?php foreach ($accounts as $a): ?>
            <div class="p-4 hover:bg-slate-50">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <div>
                        <h3 class="font-semibold text-slate-900"><?= h($a['name']) ?></h3>
                        <p class="text-sm text-slate-600"><?= h($a['email']) ?></p>
                    </div>
                    <span class="shrink-0 px-2 py-0.5 rounded text-xs font-bold <?= $a['is_active'] ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' ?>"><?= $a['is_active'] ? 'Active' : 'Inactive' ?></span>
                </div>
                <p class="text-xs text-slate-500 mb-3"><?= h($a['host']) ?>:<?= h($a['port']) ?></p>
                <div class="flex gap-2">
                    <a href="<?= url('sender-edit', ['id' => $a['id']]) ?>" class="flex-1 text-center px-3 py-2 text-sm text-[#02396E] border border-[#02396E] rounded-lg touch-manipulation">Edit</a>
                    <form method="post" action="<?= url('senders') ?>" class="flex-1" onsubmit="return confirm('Delete this sender?');">
                        <input type="hidden" name="action" value="sender-delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button type="submit" class="w-full px-3 py-2 text-sm text-red-600 border border-red-600 rounded-lg touch-manipulation">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($accounts)): ?>
            <div class="p-8 text-center text-slate-500 text-sm">No senders. <a href="<?= url('sender-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add one</a>.</div>
            <?php endif; ?>
        </div>
        
        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left" id="senderTable">
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
                    <tr class="border-t border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="px-4 md:px-8 py-4 font-semibold text-slate-900"><?= h($a['name']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600 font-medium"><?= h($a['email']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-500 text-sm"><?= h($a['host']) ?>:<?= h($a['port']) ?></td>
                        <td class="px-4 md:px-8 py-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $a['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                                <?= $a['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-4 md:px-8 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="<?= url('sender-edit', ['id' => $a['id']]) ?>" class="p-2 text-[#02396E] hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <form method="post" action="<?= url('senders') ?>" class="inline" onsubmit="return confirm('Delete this sender?');">
                                    <input type="hidden" name="action" value="sender-delete">
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button type="button" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($accounts)): ?>
                    <tr><td colspan="5" class="px-4 md:px-8 py-12 text-center text-slate-500 font-medium">No senders found. <a href="<?= url('sender-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add your first one</a>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('senderSearch');
    const searchInputMobile = document.getElementById('senderSearchMobile');
    const clearBtn = document.getElementById('clearSearch');
    const clearBtnMobile = document.getElementById('clearSearchMobile');
    const tableRows = document.querySelectorAll('#senderTable tbody tr');
    const mobileCards = document.querySelectorAll('.lg\\:hidden.divide-y > div');

    function filterSenders(query) {
        query = query.toLowerCase();
        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
        mobileCards.forEach(card => {
            const text = card.innerText.toLowerCase();
            card.style.display = text.includes(query) ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value;
            if (query.length > 0) clearBtn.classList.remove('hidden');
            else clearBtn.classList.add('hidden');
            filterSenders(query);
        });
    }

    if (searchInputMobile) {
        searchInputMobile.addEventListener('input', () => {
            const query = searchInputMobile.value;
            if (query.length > 0) clearBtnMobile.classList.remove('hidden');
            else clearBtnMobile.classList.add('hidden');
            filterSenders(query);
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            clearBtn.classList.add('hidden');
            filterSenders('');
            searchInput.focus();
        });
    }

    if (clearBtnMobile) {
        clearBtnMobile.addEventListener('click', () => {
            searchInputMobile.value = '';
            clearBtnMobile.classList.add('hidden');
            filterSenders('');
            searchInputMobile.focus();
        });
    }
});
</script>
