// admin/assets/js/dashboard.js
document.addEventListener('DOMContentLoaded', () => {
    const el      = document.getElementById('dashboardData');
    const data    = JSON.parse(el.dataset.dashboard);
    const gridColor = 'rgba(0,0,0,0.05)';
    const tickColor = '#9ca3af';

    // Trend Line Chart
    const trendEl = document.getElementById('trendChart');
    if (trendEl) {
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: data.trendLabels,
                datasets: [{
                    label: 'Submissions',
                    data: data.trendValues,
                    borderColor: '#1d4ed8',
                    backgroundColor: 'rgba(29,78,216,0.07)',
                    fill: true, tension: 0.4,
                    pointRadius: 4, pointHoverRadius: 5,
                    pointBackgroundColor: '#1d4ed8', borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, color: tickColor, font: { size: 11 } }, grid: { color: gridColor }, border: { display: false } },
                    x: { ticks: { color: tickColor, font: { size: 11 } }, grid: { display: false }, border: { display: false } }
                }
            }
        });
    }

    // Status Doughnut
    const pieEl = document.getElementById('statusPie');
    if (pieEl) {
        new Chart(pieEl, {
            type: 'doughnut',
            data: {
                labels: ['Open', 'Closed', 'Rejected'],
                datasets: [{ data: [data.pendingPPMP, data.approvedPPMP, data.rejectedPPMP], backgroundColor: ['#3b82f6', '#22c55e', '#ef4444'], borderWidth: 0, hoverOffset: 4 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => { const pct = data.totalPPMP > 0 ? Math.round(ctx.parsed / data.totalPPMP * 100) : 0; return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`; } } }
                }
            }
        });
    }

    // PR Status Doughnut
    const bottleneckEl = document.getElementById('bottleneckPie');
    if (bottleneckEl) {
        new Chart(bottleneckEl, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Approved', 'Rejected', 'PO Generated'],
                datasets: [{ data: [data.prPending, data.prApproved, data.prRejected, data.prPOGenerated], backgroundColor: ['#f59e0b', '#22c55e', '#ef4444', '#3b82f6'], borderWidth: 0, hoverOffset: 4 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => { const total = ctx.dataset.data.reduce((a, b) => a + b, 0); const pct = total > 0 ? Math.round(ctx.parsed / total * 100) : 0; return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`; } } }
                }
            }
        });
    }

    // PPMP Mode Bar Chart
    const barEl = document.getElementById('modeBar');
    if (barEl) {
        new Chart(barEl, {
            type: 'bar',
            data: {
                labels: data.modeLabels.length ? data.modeLabels : ['No data'],
                datasets: [{ label: 'Total', data: data.modeValues.length ? data.modeValues : [0], backgroundColor: 'rgba(29,78,216,0.72)', hoverBackgroundColor: '#1d4ed8', borderRadius: 5 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, color: tickColor, font: { size: 11 } }, grid: { color: gridColor }, border: { display: false } },
                    x: { ticks: { color: tickColor, font: { size: 11 }, maxRotation: 0, minRotation: 0 }, grid: { display: false }, border: { display: false } }
                }
            }
        });
    }

    // PO by Mode of Procurement - Bar Chart
    const poModeColors = ['#1d4ed8','#16a34a','#b45309','#7c3aed','#0891b2','#be185d','#ea580c','#4b5563'];
    const poModeBarEl = document.getElementById('poModeBar');
    if (poModeBarEl && data.poModeLabels && data.poModeLabels.length) {
        new Chart(poModeBarEl, {
            type: 'bar',
            data: {
                labels: data.poModeLabels,
                datasets: [{
                    label: 'Purchase Orders',
                    data: data.poModeValues,
                    backgroundColor: data.poModeLabels.map((_, i) => poModeColors[i % poModeColors.length] + 'b8'),
                    hoverBackgroundColor: data.poModeLabels.map((_, i) => poModeColors[i % poModeColors.length]),
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0, color: tickColor, font: { size: 11 } }, grid: { color: gridColor }, border: { display: false } },
                    y: { ticks: { color: tickColor, font: { size: 11 } }, grid: { display: false }, border: { display: false } }
                }
            }
        });
    }

    // PO by Mode of Procurement - Pie Chart
    const poModePieEl = document.getElementById('poModePie');
    if (poModePieEl && data.poModeLabels && data.poModeLabels.length) {
        new Chart(poModePieEl, {
            type: 'doughnut',
            data: {
                labels: data.poModeLabels,
                datasets: [{
                    data: data.poModeValues,
                    backgroundColor: data.poModeLabels.map((_, i) => poModeColors[i % poModeColors.length]),
                    borderWidth: 0, hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '60%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => { const total = ctx.dataset.data.reduce((a, b) => a + b, 0); const pct = total > 0 ? Math.round(ctx.parsed / total * 100) : 0; return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`; } } }
                }
            }
        });

        // Build legend for PO mode pie
        const legendEl = document.getElementById('poModeLegend');
        if (legendEl) {
            legendEl.innerHTML = data.poModeLabels.map((label, i) =>
                `<span><span class="legend-dot" style="background:${poModeColors[i % poModeColors.length]}"></span>${label} ${data.poModeValues[i]}</span>`
            ).join('');
        }
    }
});
