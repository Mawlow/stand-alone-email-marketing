<?php
$campaigns = $pdo->query('SELECT * FROM email_campaigns ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
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

<!-- Dashboard Banner -->
<div class="dashboard-banner bg-[#02396E] py-6 md:py-8 text-white shadow-lg relative overflow-hidden">
    <div class="px-8 sm:px-24 flex flex-col md:flex-row md:items-end justify-between gap-6">
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('dashboardSearch');
    const clearBtn = document.getElementById('clearSearch');

    searchInput.addEventListener('input', () => {
        if (searchInput.value.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
    });

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        clearBtn.classList.add('hidden');
        searchInput.focus();
    });
});
</script>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-md border-2 border-blue-200 p-4 md:p-6 flex items-center gap-6 hover:bg-slate-50 transition-colors">
            <img src="public/images/sender-icon.png" alt="" class="w-20 h-20 opacity-100">
            <div class="flex-1 flex justify-between items-end">
                <div>
                    <p class="text-lg font-bold text-black uppercase tracking-wide mb-1">Sender Accounts</p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-slate-500 text-sm font-medium">Power your campaigns with trusted senders.</p>
                        <a href="<?= url('senders') ?>" class="inline-flex items-center justify-center p-1 border-2 border-[#ff8904] text-[#ff8904] rounded-full hover:bg-orange-50 transition-colors shrink-0" title="View senders">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                        </a>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-bold text-slate-900 leading-none"><?= $activeSendersCount ?></p>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-1">active</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border-2 border-blue-200 p-4 md:p-6 flex items-center gap-6 hover:bg-slate-50 transition-colors">
            <img src="public/images/list.png" alt="" class="w-20 h-20 opacity-100">
            <div class="flex-1 flex justify-between items-end">
                <div>
                    <p class="text-lg font-bold text-black uppercase tracking-wide mb-1">Marketing list</p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-slate-500 text-sm font-medium">Smart lists, smarter campaigns.</p>
                        <a href="<?= url('contacts') ?>" class="inline-flex items-center justify-center p-1 border-2 border-[#ff8904] text-[#ff8904] rounded-full hover:bg-orange-50 transition-colors shrink-0" title="View contacts">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                        </a>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-bold text-slate-900 leading-none"><?= $contactsCount ?></p>
                    <p class="text-[10px] font-bold text-slate-500 uppercase mt-1">contacts</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-6 border-b border-white/10 flex justify-between items-center">
            <h2 class="text-xl md:text-2xl font-bold text-white">List of Campaigns</h2>
            <a href="<?= url('compose') ?>" class="inline-flex items-center px-3.5 py-1.5 bg-white text-[#02396E] text-sm font-bold rounded-xl hover:bg-[#ff8904] hover:text-white transition-colors">+ Add Campaigns</a>
        </div>
        <div class="overflow-x-auto">
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
                    <tr><td colspan="5" class="px-4 md:px-8 py-12 text-center text-slate-500">No campaigns yet. <a href="<?= url('compose') ?>" class="text-[#ff8904] font-bold hover:underline">Compose one</a>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>    
        </div>
    </div>
</div>
                    