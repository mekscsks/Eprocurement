// user/assets/js/purchase_request.js
document.querySelectorAll('.db-nav-item').forEach(i => i.classList.remove('active'));
const prLink = document.querySelector('.db-nav-item[href*="purchase_request"]');
if (prLink) prLink.classList.add('active');

const officeUnits = {
    'Office of the Schools Division Superintendent': [
        'OSDS Proper',
        'ASDS',
        'ICT Services',
        'Administrative Services - Cashier',
        'Administrative Services - Personnel',
        'Administrative Services - Records',
        'Administrative Services - Procurement',
        'Administrative Services - Supply and Property',
        'Legal Services',
        'Finance Services - Budget',
        'Finance Services - Accounting',
    ],
    'Curriculum Implementation Division': [
        'LR',
        'EPS',
        'PSDS',
        'ALS',
    ],
    'School Governance and Operations Division': [
        'Planning & Research',
        'HRD',
        'SMNE',
        'SMME',
        'HSNU',
        'Facilities',
        'Education Facilities Section',
        'YFD',
        'Private',
    ],
};

const officeSelect = document.getElementById('officeSelect');
const unitSelect   = document.getElementById('unitSelect');

officeSelect?.addEventListener('change', () => {
    const opts = officeUnits[officeSelect.value] || [];
    unitSelect.innerHTML = '<option value="">- Select Unit -</option>';
    opts.forEach(u => {
        const o = document.createElement('option');
        o.value = u; o.textContent = u;
        unitSelect.appendChild(o);
    });
    unitSelect.disabled = opts.length === 0;
    unitSelect.required = opts.length > 0;
});
/* -- Delivery Date Validation -- */
const deliveryDateInput = document.getElementById('expectedDeliveryDate');
const deliveryDateError = document.getElementById('deliveryDateError');

function validateDeliveryDate() {
    if (!deliveryDateInput?.value) return true;
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const selected = new Date(deliveryDateInput.value + 'T00:00:00');
    const valid = selected >= today;
    deliveryDateError.style.display = valid ? 'none' : 'block';
    return valid;
}

if (deliveryDateInput) {
    deliveryDateInput.min = new Date().toISOString().split('T')[0];
    deliveryDateInput.addEventListener('change', validateDeliveryDate);
}

/* -- Form submit guard -- */
document.getElementById('prForm')?.addEventListener('submit', function (e) {
    if (!validateDeliveryDate()) e.preventDefault();
});

/* -- Items table -- */
function calcTotal(row) {
    const qty   = parseFloat(row.querySelector('.qty').value)   || 0;
    const ucost = parseFloat(row.querySelector('.ucost').value) || 0;
    row.querySelector('.tcost').value = (qty * ucost).toFixed(2);
}

function attachRowEvents(row) {
    row.querySelector('.qty').addEventListener('input',   () => calcTotal(row));
    row.querySelector('.ucost').addEventListener('input', () => calcTotal(row));
    row.querySelector('.btn-remove-row').addEventListener('click', () => {
        if (document.querySelectorAll('#itemsBody tr').length > 1) row.remove();
    });
}

document.querySelectorAll('#itemsBody tr').forEach(attachRowEvents);

document.getElementById('addRowBtn')?.addEventListener('click', () => {
    const tbody  = document.getElementById('itemsBody');
    const newRow = tbody.rows[0].cloneNode(true);
    newRow.querySelectorAll('input').forEach(i => i.value = '');
    newRow.querySelectorAll('textarea').forEach(t => t.value = '');
    attachRowEvents(newRow);
    tbody.appendChild(newRow);
});
