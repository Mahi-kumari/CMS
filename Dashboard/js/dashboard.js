/* ============================================
   ICSS CRM — Dashboard Logic
   ============================================ */

const Dashboard = (() => {
    'use strict';

    // Metrics (fallbacks if server data is missing)
    const metrics = {
        totalLeads: { value: 0, trend: '—', direction: 'up' },
        newLeadsToday: { value: 0, trend: '—', direction: 'up' },
        admissionsToday: { value: 0, trend: '—', direction: 'up' },
        pendingFollowups: { value: 0, trend: '—', direction: 'up' },
        totalRevenue: { value: 0, trend: '—', direction: 'up' },
        pendingPayments: { value: 0, trend: '—', direction: 'up' }
    };


    function renderMetrics() {
        if (window.dashboardMetrics) {
            Object.keys(metrics).forEach(key => {
                if (window.dashboardMetrics[key] !== undefined) {
                    metrics[key].value = window.dashboardMetrics[key];
                }
            });
        }

        const cards = document.querySelectorAll('.metric-card');
        cards.forEach(card => {
            const key = card.dataset.metric;
            if (!key || !metrics[key]) return;

            const m = metrics[key];
            const valueEl = card.querySelector('.metric-value');
            const trendEl = card.querySelector('.metric-trend');

            if (valueEl) {
                if (key === 'totalRevenue') {
                    valueEl.textContent = App.formatCurrency(m.value);
                } else {
                    animateCounter(valueEl, m.value);
                }
            }

            if (trendEl) {
                trendEl.textContent = `${m.direction === 'up' ? '↑' : '↓'} ${m.trend}`;
                trendEl.className = `metric-trend ${m.direction}`;
            }
        });
    }

    function animateCounter(el, target) {
        let current = 0;
        const duration = 1200;
        const step = target / (duration / 16);
        const isLarge = target >= 1000;

        function update() {
            current += step;
            if (current >= target) {
                el.textContent = isLarge ? App.formatNumber(target) : target;
                return;
            }
            el.textContent = isLarge ? App.formatNumber(Math.floor(current)) : Math.floor(current);
            requestAnimationFrame(update);
        }

        requestAnimationFrame(update);
    }

    function init() {
        renderMetrics();
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', Dashboard.init);
