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
<style>
    /* Hide the default title area from index.php */
    main > div > div.mb-4:first-child { display: none; }
    main > div.max-w-6xl { max-width: none !important; padding: 0 !important; margin: 0 !important; }
    
    main > div.max-w-6xl > div.mb-4 {
        max-width: 72rem;
        margin-left: auto;
        margin-right: auto;
        margin-top: 1rem;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    @media (min-width: 640px) { main > div.max-w-6xl > div.mb-4 { padding-left: 1.5rem; padding-right: 1.5rem; } }
    @media (min-width: 1024px) { main > div.max-w-6xl > div.mb-4 { padding-left: 2rem; padding-right: 2rem; } }
.design-banner { margin-bottom: 2rem; }

/* Content Wrapper matching compose.php */
.design-content-wrapper {
    max-width: 72rem; /* 6xl */
    margin: 0 auto;
    padding: 0 1rem 2rem 1rem;
}
@media (max-width: 1023px) { .design-content-wrapper { margin-top: 1.5rem; } }
@media (min-width: 640px) { .design-content-wrapper { padding: 0 1.5rem 2rem 1.5rem; } }
@media (min-width: 1024px) { .design-content-wrapper { padding: 0 2rem 2rem 2rem; } }
</style>

<!-- Banner (Matches Compose Campaign design exactly) -->
<div class="design-banner bg-[#141d2e] py-6 md:py-8 text-white shadow-lg relative overflow-hidden hidden lg:block">
<div class="max-w-6xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
    <div class="relative z-10">
        <h1 class="text-[2.5rem] font-bold leading-tight">Design</h1>
        <p class="text-blue-100/80 mt-1 text-sm font-medium">Customize and manage email templates</p>
    </div>
</div>
<div class="absolute top-0 right-0 -mt-4 -mr-4 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
</div>

<!-- Mobile Header -->
<div class="lg:hidden bg-[#141d2e] px-4 py-4 text-white pb-6">
<h1 class="text-xl font-bold">Design</h1>
<p class="text-blue-100/80 text-xs">Customize templates</p>
</div>

<div class="design-content-wrapper mt-6 lg:mt-0">

    <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
        <!-- Header -->
        <div class="bg-[#02396E] px-4 md:px-8 py-4 md:py-6 border-b border-white/10 flex flex-col sm:flex-row justify-between items-center gap-3">
            <h2 class="text-lg md:text-2xl font-bold text-white">Email Design</h2>
        </div>


        <form method="post" action="<?= url('design') ?>" enctype="multipart/form-data" id="design-form" onsubmit="return validateDesignForm()">
            <input type="hidden" name="action" value="save-design">
            <input type="hidden" name="template_edit_id" value="<?= (int)$editingTemplateId ?>">
            <input type="hidden" name="header_logo_url" value="<?= h($designHeaderLogo) ?>">
            <input type="hidden" name="footer_logo_url" value="<?= h($designFooterLogo) ?>">
            <input type="hidden" name="header_mode" value="text_only">
            <input type="hidden" name="footer_mode" value="text_only">
            <input type="hidden" name="footer_bg_color" value="<?= h($designFooterBg) ?>">
            <input type="hidden" name="block_text_color" value="<?= h($designTextColor) ?>">
            <input type="hidden" name="body_outline_color" value="<?= h($designBodyOutline) ?>">
            
            <div class="p-4 md:p-6 space-y-6">
                <?php if ($editingTemplateId > 0): ?>
                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-[#02396E] animate-pulse"></div>
                    <span class="text-xs font-bold text-[#02396E]">Currently editing: <span class="uppercase tracking-wide ml-1"><?= h($editingTemplateName) ?></span></span>
                </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <div>
                        <label class="block text-lg font-bold text-slate-800 mb-1">Template Label <span class="text-red-600 font-black">*</span></label>
                        <input type="text" name="template_name" id="template_name" maxlength="255" placeholder="e.g. Monthly Newsletter" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-900 focus:ring-2 focus:ring-[#02396E] outline-none transition-all" value="<?= h($editingTemplateName) ?>">
                        <p id="template-name-error" class="hidden text-red-600 text-[10px] font-black uppercase mt-1 tracking-widest">Template name is required.</p>
                    </div>

                    <div>
                        <label class="block text-lg font-bold text-slate-800 mb-1">HTML Architecture <span class="text-red-600 font-black">*</span></label>

                        <p class="text-slate-500 text-[10px] mb-2 font-medium uppercase tracking-tighter">Use <code class="bg-slate-100 px-1.5 py-0.5 rounded font-bold text-[#02396E]">&lt;!-- FOOTER --&gt;</code> to separate blocks.</p>
                        <textarea name="header_footer_html" rows="15" class="w-full rounded-xl border border-slate-200 px-4 py-4 font-mono text-xs text-slate-800 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#02396E] outline-none transition-all" placeholder="&lt;!-- Your HTML Here --&gt;"><?= h($designHeaderFooterCombined) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Redundant "Deploy Design" button removed -->
            <!-- Bottom Action Bar -->
            <div class="bg-slate-50 px-4 md:px-6 py-4 flex items-center justify-start gap-3 border-t border-slate-100 flex-wrap">
                <button type="submit" class="flex-1 sm:flex-none px-7 sm:px-10 py-3 sm:py-3 bg-[#ff8904] text-white font-black rounded-xl hover:bg-[#f54a00] transition-all shadow-lg uppercase tracking-widest text-sm sm:text-sm text-center"><span class="sm:hidden">Save</span><span class="hidden sm:inline">Save Design</span></button>
                <a href="<?= url('compose') ?>" class="flex-1 sm:flex-none px-5 sm:px-6 py-3 sm:py-3 bg-slate-200 text-slate-700 text-sm sm:text-sm font-bold rounded-xl hover:bg-slate-300 transition-colors text-center"><span class="sm:hidden">To Compose</span><span class="hidden sm:inline">Go to Compose</span></a>
            </div>
        </form>
    </div>
</div>

<script>
function validateDesignForm() {
    const nameInput = document.getElementById('template_name');
    const errorMsg = document.getElementById('template-name-error');
    if (!nameInput.value.trim()) {
        nameInput.classList.add('border-red-600', 'ring-2', 'ring-red-100');
        errorMsg.classList.remove('hidden');
        nameInput.focus();
        return false;
    }
    return true;
}
document.getElementById('template_name').addEventListener('input', function() {
    this.classList.remove('border-red-600', 'ring-2', 'ring-red-100');
    document.getElementById('template-name-error').classList.add('hidden');
});
</script>
