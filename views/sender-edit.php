<?php
$senderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$account = null;
if ($senderId) {
    $st = $pdo->prepare("SELECT * FROM sender_accounts WHERE id = ?");
    $st->execute([$senderId]);
    $account = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>
<div class="mb-4"><a href="<?= url('senders') ?>" class="text-[#02396E] hover:underline text-sm font-medium">← Sender accounts</a></div>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase"><?= $account ? 'Edit' : 'Add' ?> sender</h2></div>
    <form method="post" action="<?= url('senders') ?>">
        <input type="hidden" name="action" value="sender-save">
        <?php if ($account): ?><input type="hidden" name="id" value="<?= (int)$account['id'] ?>"><?php endif; ?>
        <div class="p-6 space-y-4">
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Name</label><input type="text" name="name" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E]" value="<?= h($account['name'] ?? '') ?>" placeholder="e.g. Gmail Primary"></div>
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Email</label><input type="email" name="email" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:ring-2 focus:ring-[#02396E]" value="<?= h($account['email'] ?? '') ?>"></div>
            <div><label class="block text-sm font-bold text-slate-700 mb-1">Password <?= $account ? '(leave blank to keep)' : '' ?></label><input type="password" name="password" class="w-full rounded-xl border border-slate-200 px-4 py-2.5" placeholder="App password" <?= $account ? '' : 'required' ?>></div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1" for="sender-host">Host</label>
                <input id="sender-host" type="text" name="host" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]" value="<?= h($account['host'] ?? 'smtp.gmail.com') ?>" placeholder="smtp.gmail.com">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1" for="sender-port">Port</label>
                <input id="sender-port" type="number" name="port" min="1" max="65535" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]" value="<?= (int)($account['port'] ?? 587) ?>" placeholder="587">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1" for="sender-encryption">Encryption</label>
                <select id="sender-encryption" name="encryption" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-[#02396E] focus:border-[#02396E]">
                    <option value="" <?= ($account['encryption'] ?? '') === '' ? 'selected' : '' ?>>None</option>
                    <option value="tls" <?= ($account['encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (recommended for port 587)</option>
                    <option value="ssl" <?= ($account['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (e.g. port 465)</option>
                </select>
            </div>
            <div class="flex items-center gap-2"><input type="checkbox" name="is_active" id="is_active" value="1" <?= ($account['is_active'] ?? 1) ? 'checked' : '' ?> class="rounded text-[#02396E]"><label for="is_active" class="text-sm font-medium text-slate-700">Active</label></div>
        </div>
        <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Save</button>
            <a href="<?= url('senders') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl">Cancel</a>
        </div>
    </form>
</div>
