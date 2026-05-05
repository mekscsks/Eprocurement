// admin/assets/js/planning.js
document.addEventListener('DOMContentLoaded', () => {
    const officeUnits = window.planningOfficeUnits || {};

    const populateUnitSelect = (officeSelect) => {
        const targetId = officeSelect.dataset.unitTarget;
        const unitSelect = targetId ? document.getElementById(targetId) : null;

        if (!unitSelect) return;

        const officeName = officeSelect.value;
        const units = officeUnits[officeName] || [];

        unitSelect.innerHTML = '';

        if (!units.length) {
            unitSelect.disabled = true;
            unitSelect.append(new Option('Select office first', '', true, true));
            return;
        }

        unitSelect.disabled = false;
        unitSelect.append(new Option('Select unit', '', true, true));

        units.forEach(unit => {
            unitSelect.append(new Option(unit, unit));
        });
    };

    const setOfficeUnitSelection = (officeSelect, officeValue, unitValue) => {
        if (!officeSelect) return;
        if (officeValue && !Array.from(officeSelect.options).some(option => option.value === officeValue)) {
            officeSelect.append(new Option(officeValue, officeValue));
        }
        officeSelect.value = officeValue || '';
        populateUnitSelect(officeSelect);

        const targetId = officeSelect.dataset.unitTarget;
        const unitSelect = targetId ? document.getElementById(targetId) : null;
        if (unitSelect && unitValue) {
            if (!Array.from(unitSelect.options).some(option => option.value === unitValue)) {
                unitSelect.append(new Option(unitValue, unitValue));
            }
            unitSelect.value = unitValue;
        }
    };

    document.querySelectorAll('.planning-office-select').forEach(select => {
        select.addEventListener('change', () => populateUnitSelect(select));
        populateUnitSelect(select);
    });

    // Tab switch
    document.querySelectorAll('.planning-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.planning-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.planning-tab-pane').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });

    // Excel preview
    document.querySelectorAll('.btn-preview-excel').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            fetch('functions/preview_excel.php?id=' + id)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('excelPreviewContent').innerHTML = html;
                    const downloadBtn = document.getElementById('downloadExcelBtn');
                    if (downloadBtn) downloadBtn.href = 'functions/download_excel.php?id=' + encodeURIComponent(id);
                    new bootstrap.Modal(document.getElementById('excelPreviewModal')).show();
                });
        });
    });

    // Reupload PPMP
    document.querySelectorAll('.btn-reupload-ppmp').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.getElementById('reuploadPpmpId');
            if (input) input.value = this.dataset.id;
        });
    });

    document.querySelectorAll('.btn-edit-ppmp').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('editPpmpId').value = this.dataset.id;
            const officeSelect = document.getElementById('ep-office');
            setOfficeUnitSelection(officeSelect, this.dataset.office || '', this.dataset.unit || '');

            [
                'ppmp_type', 'description', 'project_type', 'quantity', 'procurement_mode',
                'preproc', 'start_date', 'end_date', 'delivery_period', 'source_funds',
                'budget', 'remarks'
            ].forEach(f => {
                const el = document.getElementById('ep-' + f);
                if (el) el.value = this.dataset[f] || '';
            });
        });
    });

    // Edit APP
    document.querySelectorAll('.btn-edit-app').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('editAppId').value = this.dataset.id;
            ['ppmp_type', 'description', 'procurement_mode', 'preproc', 'project_type',
             'start_date', 'end_date', 'source_funds', 'budget', 'delivery_period', 'remarks',
             'general_requirements', 'miscellaneous_items', 'cse_ps_dbm'
            ].forEach(f => {
                const el = document.getElementById('ea-' + f);
                if (el) el.value = this.dataset[f] || '';
            });
        });
    });

    // View details
    document.querySelectorAll('.btn-view-details').forEach(btn => {
        btn.addEventListener('click', function () {
            ['office', 'unit', 'description', 'project_type', 'quantity', 'procurement_mode',
             'preproc', 'start_date', 'end_date', 'delivery_period', 'source_funds', 'budget', 'remarks'
            ].forEach(f => {
                const el = document.getElementById('vd-' + f);
                if (el) el.textContent = this.dataset[f] || '';
            });
            new bootstrap.Modal(document.getElementById('viewDetailsModal')).show();
        });
    });

    const ppmpRemoveAction = (btn) => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            Swal.fire({
                title: 'Are you sure?',
                text: 'This record will be removed from the table.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, remove it',
            }).then(result => {
                if (!result.isConfirmed) return;
                const fd = new FormData();
                fd.append('action', 'remove_ppmp');
                fd.append('ppmp_id', id);
                fetch('code.php', { method: 'POST', body: fd })
                    .then(async r => {
                        const text = await r.text();
                        try {
                            return JSON.parse(text);
                        } catch {
                            throw new Error(text || 'Invalid server response.');
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Record removed.', showConfirmButton: false, timer: 2500, timerProgressBar: true });
                            setTimeout(() => location.reload(), 1200);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Action failed.' });
                        }
                    })
                    .catch(error => Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Request failed.' }));
            });
        });
    };

    document.querySelectorAll('.btn-remove-ppmp').forEach(btn => ppmpRemoveAction(btn));

    document.querySelectorAll('.btn-toggle-user-update').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            const currentAllow = this.dataset.allow === '1';
            const nextAllow = currentAllow ? 0 : 1;

            Swal.fire({
                title: currentAllow ? 'Disable user update?' : 'Enable user update?',
                text: currentAllow
                    ? 'The user will no longer be able to revise this PPMP.'
                    : 'The user will be allowed to revise this PPMP from their submissions page.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: currentAllow ? 'Disable' : 'Enable',
            }).then(result => {
                if (!result.isConfirmed) return;

                const fd = new FormData();
                fd.append('action', 'toggle_user_ppmp_update');
                fd.append('ppmp_id', id);
                fd.append('allow', String(nextAllow));

                fetch('code.php', { method: 'POST', body: fd })
                    .then(async r => {
                        const text = await r.text();
                        try {
                            return JSON.parse(text);
                        } catch {
                            throw new Error(text || 'Invalid server response.');
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: data.message || 'Updated.', showConfirmButton: false, timer: 2500, timerProgressBar: true });
                            setTimeout(() => location.reload(), 900);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Action failed.' });
                        }
                    })
                    .catch(error => Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Request failed.' }));
            });
        });
    });
});
