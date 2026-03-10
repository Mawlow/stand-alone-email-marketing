<?php
$contacts = $pdo->query("SELECT c.*, (SELECT GROUP_CONCAT(g.name) FROM contact_group_members m JOIN contact_groups g ON g.id = m.group_id WHERE m.contact_id = c.id) as group_names FROM marketing_contacts c ORDER BY c.email")->fetchAll(PDO::FETCH_ASSOC);
$totalContacts = count($contacts);
$groupsCount = (int) $pdo->query('SELECT COUNT(*) FROM contact_groups')->fetchColumn();
?>
<style>
    /* Hide the default title area from index.php when on the contacts page */
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

<!-- Contacts Banner (Desktop) -->
<div class="page-banner bg-[#02396E] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Marketing Contacts</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Manage your audience and group them for targeted campaigns.</p>
        </div>
        <!-- Search Bar -->
        <div class="relative z-10 w-full md:w-[400px]">
            <div class="relative group">
                <input type="text" id="contactSearch" placeholder="Search contacts..." class="w-full bg-white rounded-xl py-3 pl-12 pr-20 text-slate-900 text-base placeholder-slate-400 focus:outline-none transition-all shadow-inner">
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
            <h1 class="text-xl font-bold">Marketing Contacts</h1>
        </div>
        <p class="text-blue-100/80 text-xs">Manage your audience</p>
        <div class="relative">
            <input type="text" id="contactSearch" placeholder="Search contacts..." class="w-full bg-white rounded-lg py-2 pl-10 pr-16 text-slate-900 text-sm placeholder-slate-400 focus:outline-none">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <button id="clearSearch" class="absolute right-10 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600 hidden">
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
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900"><?= $totalContacts ?></p>
                <p class="text-[10px] font-bold text-slate-500 uppercase">contacts</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border-2 border-blue-200 p-4 flex flex-col items-center gap-2">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center text-[#ff8904]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-900"><?= $groupsCount ?></p>
                <p class="text-[10px] font-bold text-slate-500 uppercase">groups</p>
            </div>
        </div>
    </div>

    <!-- Desktop Stats Cards -->
    <div class="hidden lg:grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-md border-2 border-blue-200 p-4 md:p-6 flex items-center gap-6 hover:bg-slate-50 transition-colors">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-[#02396E] shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div class="flex-1 flex justify-between items-end">
                <div>
                    <p class="text-lg font-bold text-black uppercase tracking-wide mb-1">Total Contacts</p>
                    <p class="text-slate-500 text-sm font-medium">Verified emails in your database.</p>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-bold text-slate-900 leading-none"><?= $totalContacts ?></p>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-1">contacts</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border-2 border-blue-200 p-4 md:p-6 flex items-center gap-6 hover:bg-slate-50 transition-colors">
            <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center text-[#ff8904] shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <div class="flex-1 flex justify-between items-end">
                <div>
                    <p class="text-lg font-bold text-black uppercase tracking-wide mb-1">Active Groups</p>
                    <p class="text-slate-500 text-sm font-medium">Segments for targeted delivery.</p>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-bold text-slate-900 leading-none"><?= $groupsCount ?></p>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-1">groups</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/10 flex flex-col sm:flex-row justify-between items-center gap-3">
            <h2 class="text-lg md:text-2xl font-bold text-white">Contacts</h2>
            <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                <a href="<?= url('contact-edit') ?>" class="flex-1 sm:flex-none inline-flex items-center justify-center px-3.5 py-1.5 bg-white text-[#02396E] text-sm font-bold rounded-xl hover:bg-[#ff8904] hover:text-white transition-colors">+ Add</a>
                <a href="<?= url('contacts-import') ?>" class="flex-1 sm:flex-none inline-flex items-center justify-center px-3.5 py-1.5 bg-[#ff8904] text-white text-sm font-bold rounded-xl hover:bg-[#f54a00] transition-colors">Import</a>
                <a href="<?= url('groups') ?>" class="flex-1 sm:flex-none inline-flex items-center justify-center px-3.5 py-1.5 border-2 border-white/20 text-white text-sm font-bold rounded-xl hover:bg-white/10 transition-colors">Groups</a>
            </div>
        </div>
        
        <!-- Mobile Card View -->
        <div class="lg:hidden divide-y divide-slate-100">
            <?php foreach ($contacts as $c): ?>
            <div class="p-4 hover:bg-slate-50">
                <h3 class="font-semibold text-slate-900 text-sm mb-1"><?= h($c['email']) ?></h3>
                <?php if (!empty($c['company_name'])): ?>
                <p class="text-xs text-slate-600 mb-1"><?= h($c['company_name']) ?></p>
                <?php endif; ?>
                <?php if (!empty($c['group_names'])): ?>
                <p class="text-xs text-blue-600 mb-2"><?= h($c['group_names']) ?></p>
                <?php endif; ?>
                <?php if (!empty($c['notes'])): ?>
                <p class="text-xs text-slate-500 mb-3 line-clamp-2"><?= h($c['notes']) ?></p>
                <?php endif; ?>
                <div class="flex gap-2">
                    <a href="<?= url('contact-edit', ['id' => $c['id']]) ?>" class="flex-1 text-center px-3 py-2 text-xs text-[#02396E] border border-[#02396E] rounded-lg touch-manipulation">Edit</a>
                    <form method="post" action="<?= url('contacts') ?>" class="flex-1" onsubmit="return confirm('Remove this contact?');">
                        <input type="hidden" name="action" value="contact-delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <button type="submit" class="w-full px-3 py-2 text-xs text-red-600 border border-red-600 rounded-lg touch-manipulation">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($contacts)): ?>
            <div class="p-8 text-center text-slate-500 text-sm">No contacts. <a href="<?= url('contact-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add one</a> or <a href="<?= url('contacts-import') ?>" class="text-[#ff8904] font-bold hover:underline">import CSV</a>.</div>
            <?php endif; ?>
        </div>
        
        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left" id="contactTable">
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
                    <tr class="border-t border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="px-4 md:px-8 py-4 font-semibold text-slate-900"><?= h($c['email']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600 font-medium"><?= h($c['company_name'] ?? '—') ?></td>
                        <td class="px-4 md:px-8 py-4">
                            <div class="flex flex-wrap gap-1">
                                <?php if ($c['group_names']): ?>
                                    <?php foreach (explode(',', $c['group_names']) as $gn): ?>
                                        <span class="px-2 py-0.5 bg-blue-50 text-[#02396E] text-[10px] font-bold rounded uppercase tracking-wider"><?= h(trim($gn)) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 md:px-8 py-4 text-slate-500 text-sm max-w-xs truncate"><?= h($c['notes'] ?? '—') ?></td>
                        <td class="px-4 md:px-8 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="<?= url('contact-edit', ['id' => $c['id']]) ?>" class="p-2 text-slate-400 hover:text-[#02396E] hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <form method="post" action="<?= url('contacts') ?>" class="inline" onsubmit="return confirm('Remove this contact?');">
                                    <input type="hidden" name="action" value="contact-delete">
                                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                    <button type="submit" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($contacts)): ?>
                    <tr><td colspan="5" class="px-4 md:px-8 py-12 text-center text-slate-500 font-medium">No contacts found. <a href="<?= url('contact-edit') ?>" class="text-[#ff8904] font-bold hover:underline">Add your first one</a>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('contactSearch');
    const clearBtn = document.getElementById('clearSearch');
    const tableRows = document.querySelectorAll('#contactTable tbody tr');
    const mobileCards = document.querySelectorAll('.lg\\:hidden.divide-y > div');

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        
        if (query.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }

        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });

        mobileCards.forEach(card => {
            const text = card.innerText.toLowerCase();
            card.style.display = text.includes(query) ? '' : 'none';
        });
    });

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        clearBtn.classList.add('hidden');
        tableRows.forEach(row => row.style.display = '');
        mobileCards.forEach(card => card.style.display = '');
        searchInput.focus();
    });
});
</script>
