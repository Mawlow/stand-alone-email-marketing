<?php
$campaigns = $pdo->query('SELECT * FROM email_campaigns ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow border-2 border-slate-200 p-4 md:p-6">
        <p class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-1">Sender Accounts</p>
        <p class="text-2xl font-bold text-slate-900"><?= $sendersCount ?> total, <?= $activeSendersCount ?> active</p>
        <a href="<?= url('senders') ?>" class="text-[#ff8904] font-semibold text-sm mt-2 inline-block hover:underline">Manage →</a>
    </div>
    <div class="bg-white rounded-xl shadow border-2 border-slate-200 p-4 md:p-6">
        <p class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-1">Marketing list</p>
        <p class="text-2xl font-bold text-slate-900"><?= $contactsCount ?> contact(s)</p>
        <a href="<?= url('contacts') ?>" class="text-[#ff8904] font-semibold text-sm mt-2 inline-block hover:underline">Manage →</a>
    </div>
</div>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10">
        <h2 class="text-sm md:text-base font-semibold text-white uppercase">Recent Campaigns</h2>
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
<p class="mt-4"><a href="<?= url('compose') ?>" class="inline-flex items-center px-4 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Compose campaign</a></p>
