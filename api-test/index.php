<?php
/**
 * Test site for the Email Marketing API.
 * Open in browser: http://localhost:8080/api-test/ (or your base URL + /api-test/)
 * Enter your API key, then load templates/senders and send a test campaign.
 */
// API base URL: same host as this page (API lives at /api/v1/... on same server)
$apiBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Marketing API – Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, sans-serif; }
        .result-box { white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow border border-slate-200 overflow-hidden">
            <div class="bg-[#02396E] px-6 py-4">
                <h1 class="text-lg font-bold text-white">Email Marketing API – Test site</h1>
                <p class="text-sm text-blue-100 mt-1">Test that the API is working from another page</p>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">API base URL</label>
                    <input type="text" id="base-url" value="<?= htmlspecialchars($apiBase) ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-mono"
                           placeholder="https://your-email-server.com">
                    <p class="text-xs text-slate-500 mt-1">Leave as-is if this test page is on the same server as the API.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">API key <span class="text-red-600">*</span></label>
                    <input type="password" id="api-key" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm"
                           placeholder="Paste your API key">
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="btn-load-templates" class="px-4 py-2.5 rounded-xl bg-slate-200 text-slate-800 font-medium text-sm hover:bg-slate-300">
                        Load templates
                    </button>
                    <button type="button" id="btn-load-senders" class="px-4 py-2.5 rounded-xl bg-slate-200 text-slate-800 font-medium text-sm hover:bg-slate-300">
                        Load senders
                    </button>
                </div>
                <hr class="border-slate-200">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Subject</label>
                    <input type="text" id="subject" value="API test – <?= date('Y-m-d H:i') ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Header & footer template</label>
                    <select id="template" class="w-full rounded-xl border border-slate-300 px-4 py-2.5">
                        <option value="">— No template (body only) —</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Click “Load templates” to fill this list.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Body (HTML)</label>
                    <textarea id="body" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-mono"><p>This is a test email sent from the API test page.</p><p>If you see this, the API is working.</p></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Recipients (one email per line)</label>
                    <textarea id="recipients" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-mono" placeholder="you@example.com"><?= htmlspecialchars($_GET['to'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Sender (optional)</label>
                    <select id="sender" class="w-full rounded-xl border border-slate-300 px-4 py-2.5">
                        <option value="">All active senders</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Click “Load senders” to fill this list.</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" id="btn-send" class="px-6 py-2.5 rounded-xl bg-[#02396E] text-white font-bold hover:bg-[#034a8c]">
                        Send test campaign
                    </button>
                </div>
                <div id="result" class="hidden rounded-xl border p-4 text-sm result-box"></div>
            </div>
        </div>
        <p class="mt-4 text-center text-slate-500 text-sm">
            <a href="<?= htmlspecialchars($apiBase) ?>" class="text-[#02396E] hover:underline">← Back to Email Marketing app</a>
        </p>
    </div>
    <script>
(function() {
    var baseUrlEl = document.getElementById('base-url');
    var apiKeyEl = document.getElementById('api-key');
    var templateEl = document.getElementById('template');
    var senderEl = document.getElementById('sender');
    var subjectEl = document.getElementById('subject');
    var bodyEl = document.getElementById('body');
    var recipientsEl = document.getElementById('recipients');
    var resultEl = document.getElementById('result');
    var btnLoadTemplates = document.getElementById('btn-load-templates');
    var btnLoadSenders = document.getElementById('btn-load-senders');
    var btnSend = document.getElementById('btn-send');

    function base() { return (baseUrlEl.value || '').replace(/\/$/, ''); }
    function key() { return (apiKeyEl.value || '').trim(); }
    function showResult(ok, msg) {
        resultEl.classList.remove('hidden');
        resultEl.className = 'rounded-xl border p-4 text-sm result-box ' + (ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800');
        resultEl.textContent = msg;
    }

    btnLoadTemplates.onclick = function() {
        var url = base() + '/api/v1/design/templates';
        resultEl.classList.add('hidden');
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var list = data.templates || [];
                templateEl.innerHTML = '<option value="">— No template (body only) —</option>';
                list.forEach(function(t) {
                    var opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.name;
                    opt.setAttribute('data-name', t.name || '');
                    templateEl.appendChild(opt);
                });
                showResult(true, 'Loaded ' + list.length + ' template(s).');
            })
            .catch(function(e) {
                showResult(false, 'Load templates failed: ' + (e.message || String(e)));
            });
    };

    btnLoadSenders.onclick = function() {
        if (!key()) { showResult(false, 'Enter your API key first.'); return; }
        var url = base() + '/api/v1/senders';
        resultEl.classList.add('hidden');
        fetch(url, { headers: { 'X-API-Key': key() } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var list = data.senders || [];
                senderEl.innerHTML = '<option value="">All active senders</option>';
                list.forEach(function(s) {
                    var opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name + ' (' + (s.email || '') + ')';
                    senderEl.appendChild(opt);
                });
                showResult(true, 'Loaded ' + list.length + ' sender(s).');
            })
            .catch(function(e) {
                showResult(false, 'Load senders failed: ' + (e.message || String(e)) + ' (check API key and base URL).');
            });
    };

    btnSend.onclick = function() {
        if (!key()) { showResult(false, 'Enter your API key.'); return; }
        var emails = (recipientsEl.value || '').split(/[\r\n,]+/).map(function(s) { return s.trim(); }).filter(Boolean);
        if (emails.length === 0) { showResult(false, 'Enter at least one recipient email.'); return; }
        var payload = {
            subject: (subjectEl.value || '').trim() || 'API test',
            body: (bodyEl.value || '').trim() || '<p>Test</p>',
            recipients: emails
        };
        var tid = (templateEl.value || '').trim();
        if (tid) {
            if (/^\d+$/.test(tid)) payload.template_id = parseInt(tid, 10);
            else payload.template_name = templateEl.options[templateEl.selectedIndex].getAttribute('data-name') || tid;
        }
        var sid = (senderEl.value || '').trim();
        if (sid) payload.sender_id = parseInt(sid, 10);
        resultEl.classList.add('hidden');
        fetch(base() + '/api/v1/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-API-Key': key() },
            body: JSON.stringify(payload)
        })
            .then(function(r) { return r.json().then(function(d) { return { status: r.status, data: d }; }); })
            .then(function(o) {
                if (o.status >= 200 && o.status < 300) {
                    showResult(true, 'Success: ' + JSON.stringify(o.data, null, 2));
                } else {
                    showResult(false, 'Error ' + o.status + ': ' + (o.data && o.data.error ? o.data.error : JSON.stringify(o.data)));
                }
            })
            .catch(function(e) {
                showResult(false, 'Request failed: ' + (e.message || String(e)) + ' (check base URL and CORS if different origin).');
            });
    };
})();
    </script>
</body>
</html>
