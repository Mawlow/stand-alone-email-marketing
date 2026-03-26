<?php
?>
<style>
    /* Hide the default title area from index.php */
    main > div > div.mb-6:first-child {
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
        margin-bottom: 1rem;
    }
</style>

<!-- Header Banner -->
<div class="page-banner bg-[#141d2e] text-white shadow-lg relative overflow-hidden hidden lg:block min-h-[97px] box-border border-b border-slate-700/50 flex items-center">
    <div class="max-w-6xl mx-auto w-full px-3 sm:px-4 md:px-6 lg:px-8 py-3">
        <div class="relative z-10 min-w-0 pt-2 md:pt-3">
            <div class="flex items-center gap-2 mb-1">
                <a href="<?= url('contacts') ?>" class="text-[#ff8904] hover:text-orange-600 text-sm font-bold flex items-center gap-1 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back
                </a>
            </div>
            <h1 class="text-xl font-bold leading-tight md:text-2xl">Contacts Import</h1>
            <p class="text-blue-100/80 mt-0.5 text-xs font-medium md:text-sm leading-snug">Bulk upload your audience from CSV files.</p>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#141d2e] px-6 py-6 text-white border-b border-slate-700/50 mb-2 flex flex-col justify-center min-h-[97px] box-border">
    <div class="hidden flex items-center gap-2 mb-2">
        <a href="<?= url('contacts') ?>" class="text-[#ff8904] hover:text-orange-300 text-sm font-bold flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back
        </a>
    </div>
    <div class="flex items-center justify-start pt-2 md:pt-0">
        <h1 class="text-xl font-bold leading-tight">Contacts Import</h1>
    </div>
    <p class="text-blue-100/80 text-xs mt-1 leading-snug">Bulk upload your audience from CSV files.</p>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 relative pt-6 md:pt-0">
    <!-- Background element starting from sidebar -->
    <div class="fixed inset-y-0 left-64 right-0 bg-slate-50 -z-10 border-l border-slate-200 hidden lg:block"></div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#141d2e] px-4 md:px-8 py-6 border-b border-slate-700/50">
            <h2 class="text-xl md:text-2xl font-bold text-white uppercase tracking-wider">Import CSV</h2>
        </div>
        <form method="post" enctype="multipart/form-data" action="<?= url('contacts-import') ?>">
            <input type="hidden" name="action" value="contacts-import-csv">
            <div class="px-4 md:px-8 py-6 md:py-8 space-y-6">
                <div class="bg-slate-50 p-6 rounded-xl border-2 border-slate-100">
                    <p class="text-slate-600 mb-4 font-medium">Upload a CSV with columns: <span class="text-[#02396E] font-bold">email</span> (required), and optional <span class="text-[#02396E] font-bold">company</span> or <span class="text-[#02396E] font-bold">company_name</span>.</p>
                    <div class="relative group">
                        <input type="file" name="file" accept=".csv,.txt" required class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-8 text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-[#02396E] file:text-white hover:file:bg-[#034a8c] cursor-pointer transition-all">
                    </div>
                    <p class="mt-4 text-xs text-slate-400 italic">Tip: The first row can be a header. Any duplicate emails will be skipped.</p>
                </div>
            </div>
            <div class="bg-slate-50 px-4 md:px-8 py-4 flex flex-col sm:flex-row items-center justify-between border-t border-slate-100 gap-3">
                <p class="text-xs text-[#ff8904]/70 font-medium hidden sm:block">* CSV file required</p>
                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="<?= url('contacts') ?>" class="flex-1 sm:flex-none px-6 py-3 bg-white text-slate-600 font-bold rounded-xl border-2 border-slate-200 hover:bg-slate-50 transition-colors text-center text-sm touch-manipulation">Cancel</a>
                    <button type="submit" class="flex-1 sm:flex-none px-8 py-3 bg-[#f54a00] text-white font-bold rounded-xl hover:bg-[#e04400] shadow-md transition-all text-sm touch-manipulation"><span class="sm:hidden">Import</span><span class="hidden sm:inline">Import Contacts</span></button>
                </div>
            </div>
        </form>
    </div>
</div>
