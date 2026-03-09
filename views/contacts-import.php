<?php
?>
<div class="mb-4"><a href="<?= url('contacts') ?>" class="text-[#02396E] hover:underline text-sm font-medium">← Contacts</a></div>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">Import CSV</h2></div>
    <form method="post" enctype="multipart/form-data" action="<?= url('contacts-import') ?>">
        <input type="hidden" name="action" value="contacts-import-csv">
        <div class="p-6">
            <p class="text-slate-600 mb-4">Upload a CSV with columns: <strong>email</strong> (required), optional <strong>company</strong> or <strong>company_name</strong>. First row can be header.</p>
            <input type="file" name="file" accept=".csv,.txt" required class="w-full rounded-xl border border-slate-200 px-4 py-2">
        </div>
        <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl">Import</button>
            <a href="<?= url('contacts') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl">Cancel</a>
        </div>
    </form>
</div>
