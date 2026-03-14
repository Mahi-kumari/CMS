/* ============================================
   ICSS CRM — Dashboard Logic
   ============================================ */

const Dashboard = (() => {
    'use strict';

    // Mock data
    const metrics = {
        totalLeads: { value: 1247, trend: '+12.5%', direction: 'up' },
        newLeadsToday: { value: 23, trend: '+8.3%', direction: 'up' },
        admissionsToday: { value: 5, trend: '+2.1%', direction: 'up' },
        pendingFollowups: { value: 47, trend: '-3.2%', direction: 'down' },
        totalRevenue: { value: 2450000, trend: '+15.7%', direction: 'up' },
        pendingPayments: { value: 12, trend: '+4.0%', direction: 'up' }
    };

    const recentLeads = [
        { name: 'Arun Kumar', phone: '+91 98765 43210', course: 'Cyber Security', assignedUser: 'Rajesh M.', status: 'New', statusColor: 'blue', date: '14 Mar 2026' },
        { name: 'Priya Sharma', phone: '+91 87654 32109', course: 'SOC Analyst', assignedUser: 'Suman P.', status: 'Contacted', statusColor: 'yellow', date: '14 Mar 2026' },
        { name: 'Rahul Das', phone: '+91 76543 21098', course: 'Advance AWS', assignedUser: 'Amit K.', status: 'Qualified', statusColor: 'green', date: '13 Mar 2026' },
        { name: 'Sneha Gupta', phone: '+91 65432 10987', course: 'Ethical Hacking', assignedUser: 'Rajesh M.', status: 'Pending', statusColor: 'gray', date: '13 Mar 2026' },
        { name: 'Vikram Singh', phone: '+91 54321 09876', course: 'Cyber Security', assignedUser: 'Suman P.', status: 'Admitted', statusColor: 'green', date: '12 Mar 2026' }
    ];

    const upcomingFollowups = [
        { name: 'Meera Patel', phone: '+91 98111 22233', date: '15 Mar 2026', assignedUser: 'Amit K.', status: 'Positive', statusColor: 'green' },
        { name: 'Sanjay Roy', phone: '+91 97222 33344', date: '15 Mar 2026', assignedUser: 'Rajesh M.', status: 'Maybe Positive', statusColor: 'yellow' },
        { name: 'Kavita Nair', phone: '+91 96333 44455', date: '16 Mar 2026', assignedUser: 'Suman P.', status: 'Positive', statusColor: 'green' },
        { name: 'Deepak Jha', phone: '+91 95444 55566', date: '16 Mar 2026', assignedUser: 'Amit K.', status: 'Not Positive', statusColor: 'red' },
        { name: 'Anita Mishra', phone: '+91 94555 66677', date: '17 Mar 2026', assignedUser: 'Rajesh M.', status: 'Positive', statusColor: 'green' }
    ];

    function renderMetrics() {
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

    function renderRecentLeadsTable() {
        const tbody = document.getElementById('recentLeadsTbody');
        if (!tbody) return;

        tbody.innerHTML = recentLeads.map(lead => `
      <tr>
        <td>
          <div class="user-cell">
            <div class="user-cell-avatar">${lead.name.split(' ').map(n => n[0]).join('')}</div>
            <span>${lead.name}</span>
          </div>
        </td>
        <td>${lead.phone}</td>
        <td><span class="badge badge-primary">${lead.course}</span></td>
        <td>${lead.assignedUser}</td>
        <td>
          <span class="status-cell">
            <span class="status-dot ${lead.statusColor}"></span>
            ${lead.status}
          </span>
        </td>
        <td class="text-muted">${lead.date}</td>
      </tr>
    `).join('');
    }

    function renderFollowupsTable() {
        const tbody = document.getElementById('followupsTbody');
        if (!tbody) return;

        tbody.innerHTML = upcomingFollowups.map(f => `
      <tr>
        <td>
          <div class="user-cell">
            <div class="user-cell-avatar">${f.name.split(' ').map(n => n[0]).join('')}</div>
            <span>${f.name}</span>
          </div>
        </td>
        <td>${f.phone}</td>
        <td>${f.date}</td>
        <td>${f.assignedUser}</td>
        <td>
          <span class="status-cell">
            <span class="status-dot ${f.statusColor}"></span>
            ${f.status}
          </span>
        </td>
      </tr>
    `).join('');
    }

    function init() {
        renderMetrics();
        renderRecentLeadsTable();
        renderFollowupsTable();
    }

    return { init };
})();

document.addEventListener('DOMContentLoaded', Dashboard.init);
