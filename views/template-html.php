<?php
$templateRows = $pdo->query('SELECT id, name, header_html, footer_html FROM email_design_templates ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#141d2e] px-4 md:px-6 py-3 border-b border-slate-700/50"><h2 class="text-sm md:text-base font-semibold text-white uppercase">Template HTML &mdash; ready to paste in Design</h2></div>
    <p class="p-4 text-slate-600 text-sm border-b border-slate-100">Copy the HTML below for any template and paste it into the Design page (Header &amp; Footer field). <a href="<?= url('design') ?>" class="text-[#02396E] font-semibold hover:underline">&larr; Back to Design</a></p>
    <?php if (empty($templateRows)): ?>
    <div class="p-6 text-slate-500">No saved templates yet. Save a design with a template name on the <a href="<?= url('design') ?>" class="text-[#02396E] hover:underline">Design</a> page first.</div>
    <?php else: ?>
    <div class="p-6 space-y-8">
        <?php foreach ($templateRows as $t):
            $h = (string)($t['header_html'] ?? '');
            $f = (string)($t['footer_html'] ?? '');
            $combined = ($h === $f) ? $h : $h . "\n<!-- FOOTER -->\n" . $f;
            $id = 'tpl-html-' . (int)$t['id'];
        ?>
        <div class="rounded-xl border-2 border-slate-200 bg-slate-50/50 p-5">
            <div class="flex items-center justify-between gap-2 mb-2">
                <h3 class="text-sm font-bold text-slate-800"><?= h($t['name']) ?></h3>
                <button type="button" data-copy-for="<?= $id ?>" class="template-html-copy px-3 py-1.5 bg-[#02396E] text-white text-sm font-bold rounded-lg hover:bg-[#034a8c]">Copy</button>
            </div>
            <textarea id="<?= $id ?>" rows="14" readonly class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-mono bg-white"><?= h($combined) ?></textarea>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script>
(function() {
    document.querySelectorAll('.template-html-copy').forEach(function(btn) {
        btn.onclick = function() {
            var id = btn.getAttribute('data-copy-for');
            var el = id ? document.getElementById(id) : null;
            var text = el && el.value !== undefined ? el.value : (el ? el.textContent : '');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() { btn.textContent = 'Copied!'; setTimeout(function() { btn.textContent = 'Copy'; }, 1500); });
            } else {
                el.select();
                document.execCommand('copy');
                btn.textContent = 'Copied!';
                setTimeout(function() { btn.textContent = 'Copy'; }, 1500);
            }
        };
    });
})();
</script>
