<?php
$senderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$account = null;
if ($senderId) {
    $st = $pdo->prepare("SELECT * FROM sender_accounts WHERE id = ?");
    $st->execute([$senderId]);
    $account = $st->fetch(PDO::FETCH_ASSOC) ?: null;
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

    @media (max-width: 1023px) {
        .page-content {
            margin-top: 1.5rem;
        }
    }
</style>

<!-- Header Banner -->
<div class="page-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
    <div class="max-w-6xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-2">
                <a href="<?= url('senders') ?>" class="text-[#ff8904] hover:text-orange-600 text-sm font-bold flex items-center gap-1 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back
                </a>
            </div>
            <h1 class="text-[2.5rem] font-bold leading-tight"><?= $account ? 'Edit Sender' : 'Add New Sender' ?></h1>
            <p class="text-blue-100/80 mt-1 text-sm font-medium">
                <?= $account ? 'Update SMTP configuration for ' . h($account['email']) : 'Configure a new SMTP account for sending campaigns.' ?>
            </p>
        </div>
    </div>
    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#141d2e] px-4 py-4 text-white">
    <h1 class="text-xl font-bold"><?= $account ? 'Edit Sender' : 'Add Sender' ?></h1>
    <p class="text-blue-100/80 text-xs"><?= $account ? 'Update SMTP settings' : 'Configure new sender' ?></p>
</div>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-8 relative page-content">
    <!-- Background element starting from sidebar -->
    <div class="fixed inset-y-0 left-64 right-0 bg-slate-50 -z-10 border-l border-slate-200 hidden lg:block"></div>

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <div class="bg-[#02396E] px-4 md:px-8 py-6 border-b border-white/10 hidden lg:block">
            <h2 class="text-xl md:text-2xl font-bold text-white uppercase tracking-wider">SMTP Configuration</h2>
        </div>
        <form method="post" action="<?= url('senders') ?>">
            <input type="hidden" name="action" value="sender-save">
            <?php if ($account): ?><input type="hidden" name="id" value="<?= (int)$account['id'] ?>"><?php endif; ?>
            
            <div class="px-4 md:px-8 py-6 md:py-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Display Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required class="w-full rounded-xl border-2 border-slate-100 px-4 py-3 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E] transition-all" value="<?= h($account['name'] ?? '') ?>" placeholder="e.g. Gmail Primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required class="w-full rounded-xl border-2 border-slate-100 px-4 py-3 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E] transition-all" value="<?= h($account['email'] ?? '') ?>" placeholder="sender@example.com">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Password <span class="text-red-500">*</span> <?= $account ? '<span class="text-xs text-slate-400 font-normal ml-1">(leave blank to keep)</span>' : '' ?></label>
                        <input type="password" name="password" class="w-full rounded-xl border-2 border-slate-100 px-4 py-3 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E] transition-all" placeholder="App password" <?= $account ? '' : 'required' ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">SMTP Host</label>
                        <input type="text" name="host" required class="w-full rounded-xl border-2 border-slate-100 px-4 py-3 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E] transition-all" value="<?= h($account['host'] ?? 'smtp.gmail.com') ?>" placeholder="smtp.gmail.com">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Port</label>
                        <input type="number" name="port" min="1" max="65535" required class="w-full rounded-xl border-2 border-slate-100 px-4 py-3 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E] transition-all" value="<?= (int)($account['port'] ?? 587) ?>" placeholder="587">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Encryption</label>
                        <select name="encryption" class="w-full rounded-xl border-2 border-slate-100 px-4 py-3 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E] transition-all bg-white">
                            <option value="" <?= ($account['encryption'] ?? '') === '' ? 'selected' : '' ?>>None</option>
                            <option value="tls" <?= ($account['encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (Recommended for 587)</option>
                            <option value="ssl" <?= ($account['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (e.g. 465)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl border-2 border-slate-100">
                    <input type="checkbox" name="is_active" id="is_active" value="1" <?= ($account['is_active'] ?? 1) ? 'checked' : '' ?> class="w-5 h-5 rounded border-slate-300 text-[#02396E] focus:ring-[#02396E]">
                    <label for="is_active" class="text-sm font-bold text-slate-700 cursor-pointer">Active (Enable this sender for rotating delivery)</label>
                </div>
            </div>

            <div class="bg-slate-50 px-4 md:px-8 py-4 flex items-center justify-between border-t border-slate-100 gap-2">
                <p class="text-xs text-[#ff8904] font-medium hidden sm:block">* Required for sending</p>
                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="<?= url('senders') ?>" class="flex-1 sm:flex-none px-4 py-2 bg-white text-slate-600 font-bold rounded-lg border-2 border-slate-200 hover:bg-slate-50 transition-colors text-center text-sm">Cancel</a>
                    <button type="submit" class="flex-1 sm:flex-none px-4 py-2 bg-[#f54a00] text-white font-bold rounded-lg hover:bg-[#e04400]shadow-md transition-all text-sm">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
