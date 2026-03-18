<?php
$campaignsStmt = $pdo->prepare('SELECT * FROM email_campaigns WHERE user_id = ? ORDER BY id DESC LIMIT 10');
$campaignsStmt->execute([$userId]);
$campaigns = $campaignsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    /* Hide the default title area from index.php when on the dashboard */
    main > div > div.mb-6:first-child {
        display: none;
    }

    /* Force the parent container to be full width and remove padding for the dashboard */
    main > div.max-w-6xl {
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* Style flash messages to stay centered since we expanded the parent */
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

    .dashboard-banner {
        margin-bottom: 2rem;
    }
</style>

<!-- Dashboard Banner (Desktop) -->
<div class="dashboard-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="max-w-6xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <h1 class="text-[2.5rem] font-bold leading-tight">Email Marketing</h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">Reach inboxes. Build connections. Grow your business.</p>
        </div>
        <!-- Search Bar -->
        <div class="relative z-10 w-full md:w-[400px]">
            <div class="relative group">
                <input type="text" id="dashboardSearch" placeholder="Search campaigns..." class="w-full bg-white rounded-xl py-3 pl-12 pr-20 text-slate-900 text-base placeholder-slate-400 focus:outline-none transition-all shadow-inner">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                
                <!-- Clear Button (X) -->
                <button id="clearSearch" class="absolute right-12 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hidden" title="Clear search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <!-- Search Arrow Button -->
                <button class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 bg-[#02396E] text-white rounded-full shadow-md" title="Search now">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </button>
            </div>
        </div>
    </div>
    <!-- Decorative element -->
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#141d2e] px-4 py-4 text-white">
    <div class="flex flex-col gap-1">
        <div class="flex items-center justify-start">
            <h1 class="text-xl font-bold">Email Marketing</h1>
        </div>
        <p class="text-blue-100/80 text-xs">Reach inboxes. Build connections.</p>
        <div class="relative mt-3">
            <input type="text" id="dashboardSearch" placeholder="Search campaigns..." class="w-full bg-white rounded-lg py-2 pl-10 pr-16 text-slate-900 text-sm placeholder-slate-400 focus:outline-none">
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('dashboardSearch');
    const clearBtn = document.getElementById('clearSearch');

    if (searchInput) {
        const tableRows = document.querySelectorAll('#campaignTable tbody tr');
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
    }
});
</script>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
    <!-- Mobile Stats Cards -->
    <div class="lg:hidden grid grid-cols-2 gap-3 mt-4 mb-4">
        <a href="<?= url('groups') ?>" class="bg-white rounded-xl shadow border border-gray-200 p-3 flex items-center gap-3 active:bg-slate-50 transition-colors">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-[#02396E] shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold text-slate-500 uppercase truncate">Groups</p>
                    <p class="text-xl font-bold text-slate-900 leading-none truncate"><?= $groupsCount ?></p>
                </div>
            </div>
        </a>
        <a href="<?= url('contacts') ?>" class="bg-white rounded-xl shadow border border-gray-200 p-3 flex items-center gap-3 active:bg-slate-50 transition-colors">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-[#02396E] shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold text-slate-500 uppercase truncate">Contacts</p>
                    <p class="text-xl font-bold text-slate-900 leading-none truncate"><?= $contactsCount ?></p>
                </div>
            </div>
        </a>
    </div>

    <!-- Desktop Stats Cards -->
    <div class="hidden lg:grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <a href="<?= url('groups') ?>" class="bg-white rounded-xl shadow-md border border-gray-200 p-4 md:p-6 flex items-center gap-6 hover:bg-slate-50 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#ff8904]/40 rounded-xl">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-[#02396E] shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div class="flex-1 flex justify-between items-end">
                <div>
                    <p class="text-lg font-bold text-black uppercase tracking-wide mb-1">Groups</p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-slate-500 text-sm font-medium">Organize contacts for targeted campaigns</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-bold text-slate-900 leading-none"><?= $groupsCount ?></p>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-1">Total</p>
                </div>
            </div>
        </a>
        <a href="<?= url('contacts') ?>" class="bg-white rounded-xl shadow-md border border-gray-200 p-4 md:p-6 flex items-center gap-6 hover:bg-slate-50 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#ff8904]/40 rounded-xl">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-[#02396E] shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div class="flex-1 flex justify-between items-end">
                <div>
                    <p class="text-lg font-bold text-black uppercase tracking-wide mb-1">Marketing list</p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-slate-500 text-sm font-medium">Smart lists, smarter campaigns</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-bold text-slate-900 leading-none"><?= $contactsCount ?></p>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-1">contacts</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Campaigns Section -->
    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/10 flex flex-col sm:flex-row justify-between items-center gap-3">
            <h2 class="text-lg md:text-2xl font-bold text-white">Campaigns</h2>
            <a href="<?= url('compose') ?>" class="inline-flex items-center px-3.5 py-1.5 text-white text-sm font-bold bg-[#f54a00] rounded-xl hover:bg-[#e04400] hover:text-white transition-colors w-full sm:w-auto justify-center">Add Campaigns</a>
        </div>
        
        <!-- Mobile Campaigns Card View (Redesigned) -->
        <div class="lg:hidden p-4 space-y-3">
            <?php foreach ($campaigns as $c): ?>
            <?php
                $total = max(0, (int)($c['total_recipients'] ?? 0));
                $sent = max(0, (int)($c['sent_count'] ?? 0));
                $failed = max(0, (int)($c['failed_count'] ?? 0));
                $done = min($total, $sent + $failed);
                $pct = $total > 0 ? (int) round(($done / $total) * 100) : 0;

                $status = (string)($c['status'] ?? '');
                $statusLower = strtolower($status);
                $statusClass = $statusLower === 'completed'
                    ? 'bg-emerald-100 text-emerald-800'
                    : ($statusLower === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800');
            ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-black text-slate-900 leading-snug line-clamp-2"><?= h($c['subject']) ?></h3>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1"><?= h(date('M d, Y', strtotime($c['created_at']))) ?></div>
                    </div>
                    <span class="shrink-0 px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest <?= $statusClass ?>"><?= h($status) ?></span>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-2">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 text-center">
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Recipients</div>
                        <div class="text-sm font-black text-slate-900 mt-0.5"><?= $total ?></div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 text-center">
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Sent</div>
                        <div class="text-sm font-black text-emerald-700 mt-0.5"><?= $sent ?></div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 text-center">
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Failed</div>
                        <div class="text-sm font-black text-red-600 mt-0.5"><?= $failed ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($campaigns)): ?>
            <div class="pt-6 pb-14 px-4 text-center">
                <div class="flex flex-col items-center justify-center space-y-4">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xl md:text-2xl font-bold text-slate-400 uppercase tracking-wider">No Campaigns Yet</p>
                        <p class="text-slate-400 mt-1 text-sm font-medium">Start engaging your audience with your first email campaign</p>
                    </div>
                    <a href="<?= url('compose') ?>" class="inline-flex items-center px-8 py-3 bg-[#ff8904] text-white text-sm font-bold rounded-xl hover:bg-orange-600 transition-all shadow-md hover:shadow-lg">
                        Compose your first one
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Subject</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Recipients</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Status</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Sent / Failed</th>
                        <th class="px-4 md:px-8 py-3 text-xs font-black text-slate-700 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $c): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 md:px-8 py-4 font-semibold text-slate-900"><?= h(mb_substr($c['subject'], 0, 50)) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600"><?= h($c['total_recipients']) ?></td>
                        <td class="px-4 md:px-8 py-4"><span class="px-2 py-1 rounded text-xs font-bold <?= $c['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>"><?= h($c['status']) ?></span></td>
                        <td class="px-4 md:px-8 py-4 text-slate-600"><?= h($c['sent_count']) ?> / <?= h($c['failed_count']) ?></td>
                        <td class="px-4 md:px-8 py-4 text-slate-500 text-sm"><?= h($c['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($campaigns)): ?>
                    <tr>
                        <td colspan="5" class="px-4 md:px-8 pt-8 pb-20 text-center">
                            <div class="flex flex-col items-center justify-center space-y-4">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-xl md:text-2xl font-bold text-slate-400 uppercase tracking-wider">No Campaigns Yet</p>
                                    <p class="text-slate-400 mt-1 text-sm font-medium">Start engaging your audience with your first email campaign</p>
                                </div>
                                <a href="<?= url('compose') ?>" class="inline-flex items-center px-6 py-2.5 bg-[#ff8904] text-white text-sm font-bold rounded-xl hover:bg-orange-600 transition-all shadow-md hover:shadow-lg">
                                    Compose your first one
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>    
        </div>
    </div>
</div>
