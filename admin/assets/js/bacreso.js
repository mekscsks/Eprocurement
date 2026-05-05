// ── INIT ──────────────────────────────────────────────────────
const AJAX_URL = 'functions/bacfunction.php';

// ── PR MONITORING TABLE ───────────────────────────────────────
const PR_PER_PAGE = 10;
let prPage = 1, filteredPRData = [...allPRData];

const prStatusMap = {
  Pending:        { cls:'s-pending',   icon:'bi-clock' },
  Approved:       { cls:'s-approved',  icon:'bi-check-circle' },
  Rejected:       { cls:'s-rejected',  icon:'bi-x-circle' },
  'PO Generated': { cls:'s-completed', icon:'bi-archive' },
};

function renderPRTable() {
  const body  = document.getElementById('prTableBody');
  const start = (prPage - 1) * PR_PER_PAGE;
  const slice = filteredPRData.slice(start, start + PR_PER_PAGE);

  body.innerHTML = slice.length === 0
    ? `<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--muted)">
         <i class="bi bi-inbox" style="font-size:28px;display:block;margin-bottom:8px;"></i>No purchase requests found.
       </td></tr>`
    : slice.map(r => {
        const s    = prStatusMap[r.status] || { cls:'s-pending', icon:'bi-circle' };
        const date = new Date(r.created_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'});
        const canApprove = r.status !== 'Approved';
        return `<tr>
          <td class="td-id">${r.pr_number}</td>
          <td>${r.requested_by}</td>
          <td>${r.office}</td>
          <td>${r.fund_source || '—'}</td>
          <td class="td-id">&#8369;${r.total_amount.toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
          <td><span class="badge-status ${s.cls}"><i class="bi ${s.icon}"></i> ${r.status}</span></td>
          <td>${date}</td>
          <td>
            <div class="actions">
              <button class="btn-action" style="color:#0891b2;border-color:#bae6fd;" title="View" onclick="prViewRecord(${r.id})">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn-action" style="color:#16a34a;border-color:#d1fae5;" title="Approve"
                onclick="prApproveRecord(${r.id})" ${!canApprove?'disabled style="opacity:.4;cursor:not-allowed;"':''}>
                <i class="bi bi-check-circle"></i>
              </button>
              <button class="btn-action" style="color:#2563eb;border-color:#bfdbfe;" title="Edit" onclick="prEditRecord(${r.id})">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn-action btn-word" title="Generate BAC Reso" onclick="prGenerateWord(${r.id})">
                <i class="bi bi-file-earmark-word"></i>
              </button>
              <button class="btn-action btn-delete" title="Delete" onclick="prDeleteRecord(${r.id})">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
        </tr>`;
      }).join('');

  const total = Math.ceil(filteredPRData.length / PR_PER_PAGE);
  const pg = document.getElementById('prPagination');
  let html = `<button class="pg-btn" onclick="goPRPage(${prPage-1})" ${prPage===1?'disabled':''}><i class="bi bi-chevron-left"></i></button>`;
  let s2 = Math.max(1,prPage-2), e2 = Math.min(total,s2+4);
  if (e2-s2<4) s2=Math.max(1,e2-4);
  for (let p=s2;p<=e2;p++) html+=`<button class="pg-btn ${p===prPage?'active':''}" onclick="goPRPage(${p})">${p}</button>`;
  html+=`<button class="pg-btn" onclick="goPRPage(${prPage+1})" ${prPage===total||total===0?'disabled':''}><i class="bi bi-chevron-right"></i></button>`;
  pg.innerHTML = html;

  const end = Math.min(prPage*PR_PER_PAGE, filteredPRData.length);
  document.getElementById('prFooterInfo').innerHTML =
    filteredPRData.length ? `Showing <strong>${start+1}&ndash;${end}</strong> of <strong>${filteredPRData.length}</strong> records` : '';
}

function goPRPage(p) {
  const total = Math.ceil(filteredPRData.length / PR_PER_PAGE);
  if (p<1||p>total) return;
  prPage=p; renderPRTable();
}

function filterPRTable() {
  const q  = document.getElementById('prSearchInput').value.toLowerCase();
  const st = document.getElementById('prStatusFilter').value;
  filteredPRData = allPRData.filter(r =>
    (!q  || r.pr_number.toLowerCase().includes(q) || r.office.toLowerCase().includes(q) || r.requested_by.toLowerCase().includes(q)) &&
    (!st || r.status === st)
  );
  prPage=1; renderPRTable();
}

function prApproveRecord(id) {
  Swal.fire({
    title: 'Approve Purchase Request?', text: 'This will mark the PR as Approved.',
    icon: 'question', showCancelButton: true,
    confirmButtonText: 'Yes, Approve', confirmButtonColor: '#16a34a', cancelButtonColor: '#6b7280',
  }).then(result => {
    if (!result.isConfirmed) return;
    const fd = new FormData();
    fd.append('bacreso_action', 'approve_pr');
    fd.append('id', id);
    fetch(AJAX_URL, { method:'POST', body:fd })
      .then(r => r.json())
      .then(res => {
        if (res.ok) {
          Swal.fire({ icon:'success', title:'Approved!', timer:1500, showConfirmButton:false });
          setTimeout(() => location.reload(), 1500);
        } else {
          Swal.fire({ icon:'error', title:'Failed', text:'Could not approve.' });
        }
      });
  });
}

function prGenerateWord(prId) {
  // Fetch tool_sub options for the dropdown
  fetch(AJAX_URL, {
    method: 'POST',
    body: (() => { const fd = new FormData(); fd.append('bacreso_action','get_tool_sub'); return fd; })()
  })
  .then(r => r.json())
  .then(list => {
    const options = list.map(t =>
      `<option value="${t.id}">[${t.procurement_mode}] ${t.description} (${t.unit})</option>`
    ).join('');

    Swal.fire({
      title: 'Generate BAC Resolution',
      html: `
        <p style="margin-bottom:8px;font-size:13px;color:#64748b;">Select the PPMP/APP entry to link the procurement mode:</p>
        <select id="swal_tool_sub" class="swal2-input" style="width:100%;font-size:13px;">
          <option value="">-- No link (skip) --</option>
          ${options}
        </select>`,
      showCancelButton: true,
      confirmButtonText: '<i class="bi bi-file-earmark-word"></i> Generate',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#2563eb',
      cancelButtonColor: '#6b7280',
      preConfirm: () => document.getElementById('swal_tool_sub').value
    }).then(result => {
      if (!result.isConfirmed) return;
      const toolSubId = result.value;
      // Save the link first if a tool_sub was selected, then generate
      if (toolSubId) {
        const fd = new FormData();
        fd.append('bacreso_action', 'link_tool_sub');
        fd.append('pr_id', prId);
        fd.append('tool_sub_id', toolSubId);
        fetch(AJAX_URL, { method:'POST', body:fd })
          .then(r => r.json())
          .then(() => { window.location.href = 'functions/generate_bac_reso.php?id=' + prId; });
      } else {
        window.location.href = 'functions/generate_bac_reso.php?id=' + prId;
      }
    });
  })
  .catch(() => {
    // Fallback: generate without link
    window.location.href = 'functions/generate_bac_reso.php?id=' + prId;
  });
}

function prDeleteRecord(id) {
  Swal.fire({
    title: 'Delete this Purchase Request?', text: 'This action cannot be undone.',
    icon: 'warning', showCancelButton: true,
    confirmButtonColor: '#dc2626', confirmButtonText: 'Yes, delete it', cancelButtonColor: '#6b7280',
  }).then(result => {
    if (!result.isConfirmed) return;
    const fd = new FormData();
    fd.append('bacreso_action', 'delete_pr');
    fd.append('id', id);
    fetch(AJAX_URL, { method:'POST', body:fd })
      .then(r => r.json())
      .then(res => {
        if (res.ok) {
          Swal.fire({ icon:'success', title:'Deleted!', timer:1200, showConfirmButton:false });
          setTimeout(() => location.reload(), 1200);
        } else {
          Swal.fire({ icon:'error', title:'Failed', text:'Could not delete record.' });
        }
      });
  });
}

// ── VIEW PR ───────────────────────────────────────────────────
function prViewRecord(id) {
  const pr = allPRData.find(r => r.id === id);
  if (!pr) return;

  if (!document.getElementById('prViewModal')) {
    document.body.insertAdjacentHTML('beforeend', `
      <div id="prViewModal" class="modal-overlay">
        <div class="modal-box" style="max-width:520px;">
          <div class="modal-head">
            <h5><i class="bi bi-eye"></i> Purchase Request Details</h5>
            <button class="btn-close-modal" onclick="closePRViewModal()"><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
              <tbody id="prViewBody"></tbody>
            </table>
          </div>
          <div class="modal-foot">
            <button class="btn-cancel" onclick="closePRViewModal()">Close</button>
          </div>
        </div>
      </div>`);
  }

  const s = prStatusMap[pr.status] || { cls:'s-pending', icon:'bi-circle' };
  const date = new Date(pr.created_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'});
  const rows = [
    ['PR Number',     pr.pr_number],
    ['Requested By',  pr.requested_by],
    ['Office',        pr.office],
    ['Fund Source',   pr.fund_source || '—'],
    ['Total Amount',  '&#8369;' + pr.total_amount.toLocaleString('en-PH',{minimumFractionDigits:2})],
    ['Status',        `<span class="badge-status ${s.cls}"><i class="bi ${s.icon}"></i> ${pr.status}</span>`],
    ['Date',          date],
  ];

  document.getElementById('prViewBody').innerHTML = rows.map(([label, val]) =>
    `<tr>
      <td style="padding:8px 10px;font-weight:600;color:var(--muted);width:40%;border-bottom:1px solid #f1f5f9;">${label}</td>
      <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;">${val}</td>
    </tr>`
  ).join('');

  document.getElementById('prViewModal').classList.add('show');
}

function closePRViewModal() {
  document.getElementById('prViewModal').classList.remove('show');
}

// ── EDIT PR ───────────────────────────────────────────────────
function prEditRecord(id) {
  const pr = allPRData.find(r => r.id === id);
  if (!pr) return;

  if (!document.getElementById('prEditModal')) {
    document.body.insertAdjacentHTML('beforeend', `
      <div id="prEditModal" class="modal-overlay">
        <div class="modal-box" style="max-width:600px;">
          <div class="modal-head">
            <h5><i class="bi bi-pencil-square"></i> Edit Purchase Request</h5>
            <button class="btn-close-modal" onclick="closePREditModal()"><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="editPrId">
            <div class="form-row">
              <div class="form-group">
                <label>PR Number</label>
                <input id="editPrNumber" type="text" class="form-input" placeholder="e.g. PR-2025-0001">
              </div>
              <div class="form-group">
                <label>Status</label>
                <select id="editStatus" class="form-input">
                  <option>Pending</option>
                  <option>Approved</option>
                  <option>Rejected</option>
                  <option>PO Generated</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Requested By</label>
                <input id="editRequestedBy" type="text" class="form-input" placeholder="Name of requester">
              </div>
              <div class="form-group">
                <label>Office</label>
                <input id="editOffice" type="text" class="form-input" placeholder="Office / Department">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Fund Source</label>
                <input id="editFundSource" type="text" class="form-input" placeholder="e.g. MOOE, SEF">
              </div>
              <div class="form-group">
                <label>Total Amount (PHP)</label>
                <input id="editTotalAmount" type="number" class="form-input" step="0.01" min="0" placeholder="0.00">
              </div>
            </div>
          </div>
          <div class="modal-foot">
            <button class="btn-cancel" onclick="closePREditModal()">Cancel</button>
            <button class="btn-save" onclick="savePREdit()"><i class="bi bi-floppy"></i> Save Changes</button>
          </div>
        </div>
      </div>`);
  }

  document.getElementById('editPrId').value        = pr.id;
  document.getElementById('editPrNumber').value    = pr.pr_number;
  document.getElementById('editRequestedBy').value = pr.requested_by;
  document.getElementById('editOffice').value      = pr.office;
  document.getElementById('editFundSource').value  = pr.fund_source || '';
  document.getElementById('editTotalAmount').value = pr.total_amount;
  document.getElementById('editStatus').value      = pr.status;

  document.getElementById('prEditModal').classList.add('show');
}

function closePREditModal() {
  document.getElementById('prEditModal').classList.remove('show');
}

function savePREdit() {
  const id          = document.getElementById('editPrId').value;
  const pr_number   = document.getElementById('editPrNumber').value.trim();
  const requested_by= document.getElementById('editRequestedBy').value.trim();
  const office      = document.getElementById('editOffice').value.trim();
  const fund_source = document.getElementById('editFundSource').value.trim();
  const total_amount= document.getElementById('editTotalAmount').value;
  const status      = document.getElementById('editStatus').value;

  if (!pr_number || !office) {
    Swal.fire({ icon:'warning', title:'Required', text:'PR Number and Office are required.' });
    return;
  }

  const fd = new FormData();
  fd.append('bacreso_action', 'update_pr');
  fd.append('id',           id);
  fd.append('pr_number',    pr_number);
  fd.append('requested_by', requested_by);
  fd.append('office',       office);
  fd.append('fund_source',  fund_source);
  fd.append('total_amount', total_amount);
  fd.append('status',       status);

  fetch(AJAX_URL, { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (res.ok) {
        closePREditModal();
        Swal.fire({ icon:'success', title:'Saved!', timer:1200, showConfirmButton:false });
        setTimeout(() => location.reload(), 1200);
      } else {
        Swal.fire({ icon:'error', title:'Failed', text: res.msg || 'Could not save changes.' });
      }
    });
}

// Close modal on backdrop click
document.addEventListener('click', e => {
  const editModal = document.getElementById('prEditModal');
  if (editModal && e.target === editModal) closePREditModal();
  const viewModal = document.getElementById('prViewModal');
  if (viewModal && e.target === viewModal) closePRViewModal();
});

document.head.insertAdjacentHTML('beforeend',`<style>.btn-delete:hover{background:#fee2e2;border-color:#fca5a5;color:#dc2626;}</style>`);

renderPRTable();
