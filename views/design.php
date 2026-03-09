<?php
$designRow = $pdo->query('SELECT header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color FROM email_design WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
$editingTemplateId = (int)($_GET['edit_template'] ?? 0);
$editingTemplateName = '';
if ($editingTemplateId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, header_html, footer_html, footer_bg_color, block_text_color, header_logo_url, header_mode, footer_logo_url, footer_mode, body_outline_color FROM email_design_templates WHERE id = ?');
    $stmt->execute([$editingTemplateId]);
    $templateRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($templateRow) {
        $editingTemplateName = (string)($templateRow['name'] ?? '');
        $designRow = $templateRow;
    } else {
        $editingTemplateId = 0;
    }
}
$designHeader = $designRow ? $designRow['header_html'] : '';
$designFooter = $designRow ? $designRow['footer_html'] : '';
$designHeaderFooterCombined = ($designHeader === $designFooter)
    ? $designHeader
    : $designHeader . "\n<!-- FOOTER -->\n" . $designFooter;
$designFooterBg = $designRow && $designRow['footer_bg_color'] !== '' ? $designRow['footer_bg_color'] : '#f1f5f9';
$designTextColor = $designRow && !empty($designRow['block_text_color']) ? $designRow['block_text_color'] : '#1e293b';
$designHeaderLogo = $designRow && isset($designRow['header_logo_url']) ? $designRow['header_logo_url'] : '';
$designFooterLogo = $designRow && isset($designRow['footer_logo_url']) ? $designRow['footer_logo_url'] : '';
$designBodyOutline = $designRow && isset($designRow['body_outline_color']) ? $designRow['body_outline_color'] : '';
$designHeaderMode = $designRow && in_array($designRow['header_mode'] ?? '', ['logo_only', 'text_only', 'logo_and_text'], true) ? $designRow['header_mode'] : 'text_only';
$designFooterMode = $designRow && in_array($designRow['footer_mode'] ?? '', ['logo_only', 'text_only', 'logo_and_text'], true) ? $designRow['footer_mode'] : 'text_only';
?>
<div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
    <div class="bg-[#02396E] px-4 md:px-6 py-3 border-b border-white/10"><h2 class="text-sm md:text-base font-semibold text-white uppercase">Email design (header &amp; footer)</h2></div>
    <p class="p-4 text-slate-600 text-sm border-b border-slate-100">Set header and footer using HTML code. Content is centered in the email.</p>
    <form method="post" action="<?= url('design') ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save-design">
        <input type="hidden" name="template_edit_id" value="<?= (int)$editingTemplateId ?>">
        <input type="hidden" name="header_logo_url" value="<?= h($designHeaderLogo) ?>">
        <input type="hidden" name="footer_logo_url" value="<?= h($designFooterLogo) ?>">
        <input type="hidden" name="header_mode" value="text_only">
        <input type="hidden" name="footer_mode" value="text_only">
        <input type="hidden" name="footer_bg_color" value="<?= h($designFooterBg) ?>">
        <input type="hidden" name="block_text_color" value="<?= h($designTextColor) ?>">
        <input type="hidden" name="body_outline_color" value="<?= h($designBodyOutline) ?>">
        <div class="p-6 space-y-8">
            <?php if ($editingTemplateId > 0): ?>
            <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                Editing template <strong><?= h($editingTemplateName) ?></strong>.
            </div>
            <?php endif; ?>
            <div class="rounded-xl border-2 border-slate-200 bg-slate-50/50 p-5">
                <label class="block text-sm font-bold text-slate-700 mb-1">Template name</label>
                <p class="text-slate-500 text-xs mb-2">Give this design a name to show in the Compose &quot;Load template&quot; dropdown. Leave empty to only update the current design.</p>
                <input type="text" name="template_name" maxlength="255" placeholder="e.g. Newsletter, Welcome email" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#02396E]" value="<?= h($editingTemplateName) ?>">
            </div>
            <!-- Header & Footer (single code block, split by delimiter) -->
            <div class="rounded-xl border-2 border-slate-200 bg-slate-50/50 p-5">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-3">Header &amp; Footer</h3>
                <label class="block text-sm font-bold text-slate-700 mb-1">Header &amp; Footer (HTML code)</label>
                <p class="text-slate-500 text-xs mb-2">Paste your HTML in one block. To use different content for header and footer, put <code class="bg-slate-100 px-1 rounded">&lt;!-- FOOTER --&gt;</code> on its own line: everything above = header, everything below = footer. If you don’t use it, the same code is used for both. Use email-safe HTML with inline styles.</p>
                <textarea name="header_footer_html" rows="14" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-[#02396E]" placeholder="Header HTML here...&#10;&#10;&lt;!-- FOOTER --&gt;&#10;&#10;Footer HTML here..."><?= h($designHeaderFooterCombined) ?></textarea>
            </div>
        </div>
        <div class="bg-slate-50 px-4 md:px-6 py-3 flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-[#02396E] text-white font-bold rounded-xl hover:bg-[#034a8c]">Save design</button>
            <a href="<?= url('compose') ?>" class="px-6 py-2.5 bg-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-300">Compose</a>
        </div>
    </form>
</div>
