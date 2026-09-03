/**
 * Dashboard snapy: wykresy jak w Prompt Engine + animacja „generuj payroll”.
 */
(function initDashboardSnaps() {
    const PALETTE = [
        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
        '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#14b8a6',
        '#a855f7', '#64748b',
    ];
    const STATUS_COLORS = {
        completed: '#10b981', pending: '#8b5cf6',
        in_progress: '#f59e0b', cancelled: '#ef4444',
    };
    const STATUS_LABELS = {
        completed: 'Zakończone', pending: 'Oczekujące',
        in_progress: 'W trakcie', cancelled: 'Anulowane',
    };
    const CURRENCY_ACCENT = { EUR: '#3b82f6', PLN: '#10b981', USD: '#f59e0b' };

    function loadChartJs() {
        return new Promise((resolve) => {
            if (window.Chart) {
                resolve();
                return;
            }
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
            s.onload = resolve;
            s.onerror = resolve;
            document.head.appendChild(s);
        });
    }

    function readJson(id) {
        const el = document.getElementById(id);
        if (!el) {
            return null;
        }
        try {
            return JSON.parse(el.textContent);
        } catch (_) {
            return null;
        }
    }

    function card(inner) {
        return `<div class="rounded-2 p-3" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1)">${inner}</div>`;
    }

    function sectionHead(label) {
        return `<div class="small fw-semibold text-uppercase mb-2" style="letter-spacing:.06em;font-size:10px;color:rgba(255,255,255,0.5)">${label}</div>`;
    }

    function fmtMoney(v) {
        const n = Number(v) || 0;
        return n.toLocaleString('pl-PL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function renderTasks(container, json) {
        const tasks = json.tasks || [];
        const byStatus = { completed: 0, pending: 0, in_progress: 0, cancelled: 0 };
        const byAssignee = {};
        tasks.forEach((t) => {
            const s = t.status || 'pending';
            if (s in byStatus) {
                byStatus[s] += 1;
            }
            const n = t.assigned_to?.name || '—';
            byAssignee[n] = (byAssignee[n] || 0) + 1;
        });
        const total = tasks.length;
        const kpis = [
            { label: 'Wszystkich', v: total, color: '#3b82f6' },
            { label: 'Zakończone', v: byStatus.completed, color: STATUS_COLORS.completed },
            { label: 'W trakcie', v: byStatus.in_progress, color: STATUS_COLORS.in_progress },
            { label: 'Oczekujące', v: byStatus.pending, color: STATUS_COLORS.pending },
        ];
        const id = 'dash-task-donut';
        container.innerHTML = `
            <div class="d-flex flex-wrap gap-2 mb-3">
                ${kpis.map((k) => `
                    <div class="flex-grow-1 rounded-2 p-3" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);min-width:72px">
                        <div style="font-size:22px;font-weight:700;color:${k.color};line-height:1.1">${k.v}</div>
                        <div style="font-size:10px;color:rgba(255,255,255,0.45);margin-top:4px;text-transform:uppercase;letter-spacing:.04em">${k.label}</div>
                    </div>`).join('')}
            </div>
            ${card(`${sectionHead('Rozkład statusów')}<div style="height:180px;position:relative"><canvas id="${id}"></canvas></div>`)}
        `;
        if (!window.Chart || total === 0) {
            return;
        }
        window.Chart.defaults.color = 'rgba(255,255,255,0.45)';
        window.Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
        new window.Chart(document.getElementById(id), {
            type: 'doughnut',
            data: {
                labels: Object.values(STATUS_LABELS),
                datasets: [{
                    data: Object.keys(STATUS_COLORS).map((s) => byStatus[s]),
                    backgroundColor: Object.values(STATUS_COLORS).map((c) => c + '30'),
                    borderColor: Object.values(STATUS_COLORS),
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
            },
        });
    }

    function renderCosts(container, json) {
        const byCur = json.summary?.by_currency_and_type || {};
        const currencies = Object.keys(byCur).sort();
        if (currencies.length === 0) {
            container.innerHTML = '<div class="text-muted small">Brak kosztów.</div>';
            return;
        }
        const keys = ['fixed', 'variable', 'transport', 'accommodation', 'labor', 'vehicle_repairs'];
        const labels = ['Stałe', 'Zmienne', 'Transport', 'Najem', 'Praca', 'Naprawy'];
        let html = '';
        currencies.forEach((cur) => {
            const totals = byCur[cur] || {};
            const accent = CURRENCY_ACCENT[cur] || PALETTE[0];
            html += `
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-2 p-3"
                         style="background:${accent}15;border:1px solid ${accent};min-width:64px;font-size:16px;font-weight:800;color:${accent}">${cur}</div>
                    <div class="flex-grow-1 rounded-2 p-3" style="background:rgba(255,255,255,0.05);border:1px solid ${accent}60;min-width:90px">
                        <div style="font-size:20px;font-weight:700;color:${accent};line-height:1.1">${fmtMoney(totals.total)}</div>
                        <div style="font-size:10px;color:rgba(255,255,255,0.45);margin-top:4px;text-transform:uppercase">Razem koszty</div>
                    </div>
                </div>
                ${card(`${sectionHead('Struktura · '+cur)}<div style="height:170px;position:relative"><canvas id="dash-cost-donut-${cur}"></canvas></div>`)}
            `;
        });
        container.innerHTML = html;
        if (!window.Chart) {
            return;
        }
        currencies.forEach((cur) => {
            const totals = byCur[cur] || {};
            const canvas = document.getElementById(`dash-cost-donut-${cur}`);
            if (!canvas) {
                return;
            }
            new window.Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: keys.map((k) => Number(totals[k]) || 0),
                        backgroundColor: PALETTE.map((c) => c + '30'),
                        borderColor: PALETTE,
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 8, font: { size: 9 } } } },
                },
            });
        });
    }

    const boot = () => {
        const tasksEl = document.getElementById('dash-task-charts');
        const costsEl = document.getElementById('dash-cost-charts');
        if (!tasksEl && !costsEl) {
            return;
        }
        loadChartJs().then(() => {
            const tasks = readJson('dash-tasks-json');
            const costs = readJson('dash-costs-json');
            if (tasksEl && tasks) {
                renderTasks(tasksEl, tasks);
            }
            if (costsEl && costs) {
                renderCosts(costsEl, costs);
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
