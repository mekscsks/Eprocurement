<?php
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Support Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--navy:#0D2B55;--navy-mid:#1A4080;--sun:#F5A623;--bg:#F4F7FC;--border:#D6E1EF;--sidebar-w:260px;}
*{box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);margin:0;overflow:hidden;}
.sa-wrap{display:flex;height:100vh;margin-left:var(--sidebar-w);}
.panel-left{width:320px;flex-shrink:0;background:#fff;border-right:1px solid var(--border);display:flex;flex-direction:column;}
.panel-right{flex:1;display:flex;flex-direction:column;min-width:0;}
.pl-head{background:linear-gradient(135deg,var(--navy),var(--navy-mid));padding:1rem;border-bottom:3px solid var(--sun);}
.pl-head h6{color:#fff;margin:0;font-size:.95rem;font-weight:600;}
.search-box{padding:.75rem;border-bottom:1px solid var(--border);}
.search-box input{width:100%;border:1px solid var(--border);border-radius:10px;padding:.5rem .85rem;font-size:.85rem;outline:none;font-family:inherit;}
.search-box input:focus{border-color:var(--navy-mid);}
.thread-list{flex:1;overflow-y:auto;}
.thread-item{padding:.85rem 1rem;border-bottom:1px solid var(--border);cursor:pointer;transition:background .15s;display:flex;gap:.75rem;align-items:flex-start;}
.thread-item:hover{background:#f0f4fb;}
.thread-item.active{background:#e8eef9;border-left:3px solid var(--navy-mid);}
.thread-avatar{width:38px;height:38px;border-radius:50%;background:var(--navy-mid);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;flex-shrink:0;}
.thread-info{flex:1;min-width:0;}
.thread-name{font-size:.88rem;font-weight:600;color:#1a2b40;display:flex;justify-content:space-between;}
.thread-preview{font-size:.78rem;color:#8a9ab5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:.15rem;}
.thread-time{font-size:.7rem;color:#b0bec5;white-space:nowrap;}
.unread-badge{background:var(--navy-mid);color:#fff;font-size:.65rem;font-weight:700;border-radius:999px;padding:.15rem .45rem;min-width:18px;text-align:center;}
.status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:.3rem;}
.dot-open{background:#22c55e;}
.dot-closed{background:#ef4444;}
.rp-empty{flex:1;display:flex;align-items:center;justify-content:center;color:#b0bec5;flex-direction:column;gap:.5rem;}
.chat-header{background:linear-gradient(135deg,var(--navy),var(--navy-mid));padding:.9rem 1.25rem;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid var(--sun);}
.chat-header h6{color:#fff;margin:0;font-size:.95rem;font-weight:600;}
.chat-body{flex:1;overflow-y:auto;padding:1.25rem;display:flex;flex-direction:column;gap:.6rem;}
.chat-footer{padding:.85rem 1rem;border-top:1px solid var(--border);background:#fafbfd;}
.bubble-wrap{display:flex;flex-direction:column;max-width:72%;}
.bubble-wrap.me{align-self:flex-end;align-items:flex-end;}
.bubble-wrap.them{align-self:flex-start;align-items:flex-start;}
.bubble{padding:.6rem .9rem;border-radius:18px;font-size:.88rem;line-height:1.5;word-break:break-word;}
.bubble.me{background:var(--navy-mid);color:#fff;border-bottom-right-radius:4px;}
.bubble.them{background:#e9eef6;color:#1a2b40;border-bottom-left-radius:4px;}
.bubble-meta{font-size:.7rem;color:#8a9ab5;margin-top:.2rem;}
.input-row{display:flex;gap:.5rem;align-items:flex-end;}
.input-row textarea{flex:1;resize:none;border-radius:12px;border:1px solid var(--border);padding:.6rem .85rem;font-size:.88rem;font-family:inherit;outline:none;transition:border .2s;}
.input-row textarea:focus{border-color:var(--navy-mid);}
.btn-send{background:var(--navy-mid);color:#fff;border:none;border-radius:12px;padding:.6rem 1.1rem;cursor:pointer;}
.btn-send:hover{background:var(--navy);}
.btn-send:disabled{opacity:.5;cursor:not-allowed;}
.attach-label{cursor:pointer;color:#8a9ab5;font-size:1.1rem;padding:.5rem;}
.attach-label:hover{color:var(--navy-mid);}
#typingIndicator{font-size:.78rem;color:#8a9ab5;min-height:1.2rem;padding:0 .25rem;}
#attachPreview{font-size:.78rem;color:#5a6a80;margin-top:.3rem;}
.t-badge{font-size:.7rem;padding:.2rem .55rem;border-radius:999px;font-weight:700;text-transform:uppercase;}
.t-badge.open{background:rgba(34,197,94,.15);color:#15803d;}
.t-badge.closed{background:#fee2e2;color:#b91c1c;}
@media(max-width:768px){.sa-wrap{margin-left:0;}.panel-left{width:100%;position:absolute;z-index:10;height:100%;transform:translateX(-100%);transition:transform .3s;}.panel-left.show{transform:translateX(0);}}
</style>
</head>
<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="sa-wrap">
    <div class="panel-left" id="panelLeft">
        <div class="pl-head">
            <h6><i class="bi bi-headset me-2"></i>Support Threads</h6>
        </div>
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Search by name or message…">
        </div>
        <div class="thread-list" id="threadList">
            <div class="text-center text-muted py-4">
                <div class="spinner-border spinner-border-sm"></div>
            </div>
        </div>
    </div>

    <div class="panel-right" id="panelRight">
        <div class="rp-empty" id="emptyState">
            <i class="bi bi-chat-dots" style="font-size:3rem;"></i>
            <span>Select a conversation</span>
        </div>

        <div id="chatArea" style="display:none;flex:1;flex-direction:column;overflow:hidden;">
            <div class="chat-header">
                <div>
                    <h6 id="chatTitle">—</h6>
                    <small id="chatSub" style="color:rgba(255,255,255,.55);font-size:.75rem;"></small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="chatStatusBadge" class="t-badge open">Open</span>
                    <button id="toggleStatusBtn" class="btn btn-sm btn-outline-light" onclick="toggleThreadStatus()">Close Thread</button>
                </div>
            </div>

            <div class="chat-body" id="chatBody"></div>

            <div class="chat-footer">
                <div id="typingIndicator"></div>
                <form id="chatForm" enctype="multipart/form-data">
                    <input type="hidden" id="threadId" name="thread_id" value="">
                    <div class="input-row">
                        <label class="attach-label" title="Attach file">
                            <i class="bi bi-paperclip"></i>
                            <input type="file" name="attachment" id="attachInput" accept=".jpg,.jpeg,.png,.pdf" hidden>
                        </label>
                        <textarea name="message" id="msgInput" rows="1" placeholder="Type a reply…" maxlength="2000"></textarea>
                        <button type="submit" class="btn-send" id="sendBtn">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                    <div id="attachPreview"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const ME_ID = <?= (int)$_SESSION['auth_user']['account_id'] ?>;
const API   = 'support';
const UPLOAD_BASE = '../support/uploads';
let activeThread = null, lastId = 0, threadStatus = 'open', typingTimer;

async function loadThreads(search = '') {
    const r = await fetch(`${API}/fetch_threads.php?search=${encodeURIComponent(search)}`).then(x=>x.json()).catch(()=>null);
    if (!r) return;
    const list = document.getElementById('threadList');
    if (!r.threads.length) { list.innerHTML = '<div class="text-center text-muted py-4" style="font-size:.85rem;">No threads found</div>'; return; }
    list.innerHTML = r.threads.map(t => {
        const initials = t.account_name.charAt(0).toUpperCase();
        const preview  = t.last_message ? escHtml(t.last_message).substring(0,50) : '<em>No messages</em>';
        const unread   = parseInt(t.unread_count) > 0 ? `<span class="unread-badge">${t.unread_count}</span>` : '';
        const dot      = t.status === 'open' ? 'dot-open' : 'dot-closed';
        const time     = t.last_at ? fmtTime(t.last_at) : '';
        return `<div class="thread-item ${activeThread?.id == t.id ? 'active' : ''}" onclick="openThread(${JSON.stringify(t).replace(/"/g,'&quot;')})">
            <div class="thread-avatar">${initials}</div>
            <div class="thread-info">
                <div class="thread-name"><span><span class="status-dot ${dot}"></span>${escHtml(t.account_name)}</span><span class="thread-time">${time}</span></div>
                <div class="thread-preview d-flex justify-content-between"><span>${preview}</span>${unread}</div>
            </div>
        </div>`;
    }).join('');
}

function openThread(t) {
    activeThread = t; lastId = 0; threadStatus = t.status;
    document.getElementById('emptyState').style.display = 'none';
    const ca = document.getElementById('chatArea'); ca.style.display = 'flex';
    document.getElementById('chatTitle').textContent = t.account_name;
    document.getElementById('chatSub').textContent   = t.account_role.toUpperCase() + ' · Thread #' + t.id;
    document.getElementById('threadId').value        = t.id;
    document.getElementById('chatBody').innerHTML    = '';
    updateStatusUI();
    loadThreads(document.getElementById('searchInput').value);
    pollMessages();
}

async function pollMessages() {
    if (!activeThread) return;
    const tid = activeThread.id;
    const r = await fetch(`${API}/fetch_messages.php?thread_id=${tid}&after_id=${lastId}`).then(x=>x.json()).catch(()=>null);
    if (!r || activeThread?.id !== tid) return;
    threadStatus = r.thread_status || 'open';
    updateStatusUI();
    if (r.messages && r.messages.length) {
        r.messages.forEach(appendBubble);
        lastId = r.messages[r.messages.length - 1].id;
        scrollBottom();
        markRead(tid);
        loadThreads(document.getElementById('searchInput').value);
    }
}

function appendBubble(m) {
    const isMe = parseInt(m.sender_id) === ME_ID;
    const wrap = document.createElement('div');
    wrap.className = 'bubble-wrap ' + (isMe ? 'me' : 'them');
    wrap.dataset.id = m.id;
    let content = m.message ? escHtml(m.message) : '';
    if (m.attachment) {
        const ext = m.attachment.split('.').pop().toLowerCase();
        const url = `${UPLOAD_BASE}/${m.attachment}`;
        content += ['jpg','jpeg','png'].includes(ext)
            ? `<br><a href="${url}" target="_blank"><img src="${url}" style="max-width:180px;border-radius:8px;margin-top:.4rem;"></a>`
            : `<br><a href="${url}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> ${escHtml(m.attachment)}</a>`;
    }
    wrap.innerHTML = `<div class="bubble ${isMe?'me':'them'}">${content}</div><div class="bubble-meta">${isMe?'You':escHtml(m.sender_name)} · ${fmtTime(m.created_at)}</div>`;
    document.getElementById('chatBody').appendChild(wrap);
}

document.getElementById('chatForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (threadStatus === 'closed') return;
    const msg  = document.getElementById('msgInput').value.trim();
    const file = document.getElementById('attachInput').files[0];
    if (!msg && !file) return;
    const fd = new FormData(this);
    document.getElementById('sendBtn').disabled = true;
    const r = await fetch(`${API}/send_message.php`, {method:'POST', body:fd}).then(x=>x.json()).catch(()=>null);
    document.getElementById('sendBtn').disabled = false;
    if (r && r.success) {
        document.getElementById('msgInput').value = '';
        document.getElementById('attachInput').value = '';
        document.getElementById('attachPreview').textContent = '';
        await pollMessages();
    } else { alert(r?.error || 'Failed to send'); }
});

async function toggleThreadStatus() {
    if (!activeThread) return;
    const action = threadStatus === 'open' ? 'close' : 'reopen';
    const fd = new FormData(); fd.append('thread_id', activeThread.id); fd.append('action', action);
    const r = await fetch(`${API}/close_thread.php`, {method:'POST', body:fd}).then(x=>x.json()).catch(()=>null);
    if (r && r.success) { threadStatus = r.status; activeThread.status = r.status; updateStatusUI(); loadThreads(document.getElementById('searchInput').value); }
}

function updateStatusUI() {
    const isClosed = threadStatus === 'closed';
    document.getElementById('chatStatusBadge').textContent = isClosed ? 'Closed' : 'Open';
    document.getElementById('chatStatusBadge').className   = 't-badge ' + threadStatus;
    document.getElementById('toggleStatusBtn').textContent = isClosed ? 'Reopen' : 'Close Thread';
    document.getElementById('msgInput').disabled  = isClosed;
    document.getElementById('sendBtn').disabled   = isClosed;
    document.getElementById('chatForm').style.opacity = isClosed ? '.45' : '1';
}

document.getElementById('msgInput').addEventListener('input', function() {
    clearTimeout(typingTimer); sendTyping(1);
    typingTimer = setTimeout(()=>sendTyping(0), 3000);
    this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});
document.getElementById('msgInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); document.getElementById('chatForm').dispatchEvent(new Event('submit')); }
});
function sendTyping(val) {
    if (!activeThread) return;
    const fd = new FormData(); fd.append('thread_id', activeThread.id); fd.append('is_typing', val);
    fetch(`${API}/typing.php`, {method:'POST', body:fd});
}
async function pollTyping() {
    if (!activeThread) return;
    const r = await fetch(`${API}/typing.php?thread_id=${activeThread.id}`).then(x=>x.json()).catch(()=>null);
    document.getElementById('typingIndicator').textContent = r?.typing?.length ? r.typing.join(', ') + ' is typing…' : '';
}
document.getElementById('attachInput').addEventListener('change', function() {
    document.getElementById('attachPreview').textContent = this.files[0]?.name || '';
});

let searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer); searchTimer = setTimeout(()=>loadThreads(this.value), 300);
});

function scrollBottom() { const cb = document.getElementById('chatBody'); cb.scrollTop = cb.scrollHeight; }
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>'); }
function fmtTime(ts) {
    const d = new Date(ts.replace(' ','T')), now = new Date();
    return d.toDateString() === now.toDateString()
        ? d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})
        : d.toLocaleDateString([], {month:'short',day:'numeric'});
}
function markRead(tid) { const fd = new FormData(); fd.append('thread_id', tid); fetch(`${API}/mark_as_read.php`, {method:'POST', body:fd}); }

loadThreads();
setInterval(()=>loadThreads(document.getElementById('searchInput').value), 5000);
setInterval(pollMessages, 2500);
setInterval(pollTyping, 2000);
</script>
</body>
</html>
