/* ============================================
   ICSS CRM — View Leads Table Management
   ============================================ */

const ViewLeads = (() => {
    'use strict';

    const ITEMS_PER_PAGE = 10;
    let currentPage = 1;
    let filteredLeads = [];

    // Mock leads data
    const allLeads = [
        { id: 1, name: 'Arun Kumar', phone: '+91 98765 43210', email: 'arun@email.com', course: 'Cyber Security', source: 'Google Search', assignedUser: 'Rajesh M.', status: 'New', nextFollowup: '15 Mar 2026', createdDate: '14 Mar 2026' },
        { id: 2, name: 'Priya Sharma', phone: '+91 87654 32109', email: 'priya@email.com', course: 'SOC Analyst', source: 'Facebook', assignedUser: 'Suman P.', status: 'Contacted', nextFollowup: '16 Mar 2026', createdDate: '14 Mar 2026' },
        { id: 3, name: 'Rahul Das', phone: '+91 76543 21098', email: 'rahul@email.com', course: 'Advance AWS', source: 'Whatsapp', assignedUser: 'Amit K.', status: 'Qualified', nextFollowup: '15 Mar 2026', createdDate: '13 Mar 2026' },
        { id: 4, name: 'Sneha Gupta', phone: '+91 65432 10987', email: 'sneha@email.com', course: 'Ethical Hacking', source: 'Website', assignedUser: 'Rajesh M.', status: 'Pending', nextFollowup: '17 Mar 2026', createdDate: '13 Mar 2026' },
        { id: 5, name: 'Vikram Singh', phone: '+91 54321 09876', email: 'vikram@email.com', course: 'Cyber Security', source: 'Referred from Friend', assignedUser: 'Suman P.', status: 'Admitted', nextFollowup: '-', createdDate: '12 Mar 2026' },
        { id: 6, name: 'Meera Patel', phone: '+91 98111 22233', email: 'meera@email.com', course: 'SOC Analyst', source: 'Google Search', assignedUser: 'Amit K.', status: 'New', nextFollowup: '15 Mar 2026', createdDate: '12 Mar 2026' },
        { id: 7, name: 'Sanjay Roy', phone: '+91 97222 33344', email: 'sanjay@email.com', course: 'Advance AWS', source: 'Direct Phone', assignedUser: 'Rajesh M.', status: 'Contacted', nextFollowup: '18 Mar 2026', createdDate: '11 Mar 2026' },
        { id: 8, name: 'Kavita Nair', phone: '+91 96333 44455', email: 'kavita@email.com', course: 'Cyber Security', source: 'Workshop', assignedUser: 'Suman P.', status: 'Qualified', nextFollowup: '16 Mar 2026', createdDate: '11 Mar 2026' },
        { id: 9, name: 'Deepak Jha', phone: '+91 95444 55566', email: 'deepak@email.com', course: 'Ethical Hacking', source: 'Facebook', assignedUser: 'Amit K.', status: 'Pending', nextFollowup: '19 Mar 2026', createdDate: '10 Mar 2026' },
        { id: 10, name: 'Anita Mishra', phone: '+91 94555 66677', email: 'anita@email.com', course: 'SOC Analyst', source: 'Whatsapp', assignedUser: 'Rajesh M.', status: 'New', nextFollowup: '15 Mar 2026', createdDate: '10 Mar 2026' },
        { id: 11, name: 'Rohit Verma', phone: '+91 93666 77788', email: 'rohit@email.com', course: 'Advance AWS', source: 'Google Search', assignedUser: 'Suman P.', status: 'Contacted', nextFollowup: '20 Mar 2026', createdDate: '09 Mar 2026' },
        { id: 12, name: 'Pooja Sinha', phone: '+91 92777 88899', email: 'pooja@email.com', course: 'Cyber Security', source: 'Website', assignedUser: 'Amit K.', status: 'Admitted', nextFollowup: '-', createdDate: '09 Mar 2026' },
        { id: 13, name: 'Nikhil Sen', phone: '+91 91888 99900', email: 'nikhil@email.com', course: 'SOC Analyst', source: 'Direct Email', assignedUser: 'Rajesh M.', status: 'New', nextFollowup: '16 Mar 2026', createdDate: '08 Mar 2026' },
        { id: 14, name: 'Swati Ghosh', phone: '+91 90999 00011', email: 'swati@email.com', course: 'Ethical Hacking', source: 'Facebook', assignedUser: 'Suman P.', status: 'Qualified', nextFollowup: '17 Mar 2026', createdDate: '08 Mar 2026' },
        { id: 15, name: 'Arjun Chatterjee', phone: '+91 89100 11122', email: 'arjun@email.com', course: 'Advance AWS', source: 'Whatsapp Ads', assignedUser: 'Amit K.', status: 'Pending', nextFollowup: '18 Mar 2026', createdDate: '07 Mar 2026' }
    ];

    const statusStyles = {
        'New': 'badge-info badge-dot',
        'Contacted': 'badge-warning badge-dot',
        'Qualified': 'badge-success badge-dot',
        'Pending': 'badge-neutral badge-dot',
        'Admitted': 'badge-primary badge-dot',
        'Rejected': 'badge-danger badge-dot'
    };

    // ─── Filter & Search ───
    function applyFilters() {
        const searchTerm = (document.getElementById('leadsSearch')?.value || '').toLowerCase();
        const courseFilter = document.getElementById('filterCourse')?.value || '';
        const sourceFilter = document.getElementById('filterSource')?.value || '';
        const statusFilter = document.getElementById('filterStatus')?.value || '';

        filteredLeads = allLeads.filter(lead => {
            const matchSearch = !searchTerm ||
                lead.name.toLowerCase().includes(searchTerm) ||
                lead.phone.includes(searchTerm) ||
                lead.email.toLowerCase().includes(searchTerm);
            const matchCourse = !courseFilter || lead.course === courseFilter;
            const matchSource = !sourceFilter || lead.source === sourceFilter;
            const matchStatus = !statusFilter || lead.status === statusFilter;

            return matchSearch && matchCourse && matchSource && matchStatus;
        });

        currentPage = 1;
        renderTable();
        renderPagination();
        updateLeadsCount();
    }

    // ─── Render Table ───
    function renderTable() {
        const tbody = document.getElementById('leadsTbody');
        if (!tbody) return;

        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end = start + ITEMS_PER_PAGE;
        const pageLeads = filteredLeads.slice(start, end);

        if (pageLeads.length === 0) {
            tbody.innerHTML = `
        <tr>
          <td colspan="10">
            <div class="empty-state">
              <div class="empty-state-icon">📋</div>
              <h4>No leads found</h4>
              <p>Try adjusting your search or filter criteria.</p>
              <button class="btn btn-secondary btn-sm" onclick="ViewLeads.clearFilters()">Clear Filters</button>
            </div>
          </td>
        </tr>
      `;
            return;
        }

        tbody.innerHTML = pageLeads.map(lead => `
      <tr data-lead-id="${lead.id}">
        <td>
          <div class="user-cell">
            <div class="user-cell-avatar">${lead.name.split(' ').map(n => n[0]).join('')}</div>
            <span>${lead.name}</span>
          </div>
        </td>
        <td>${lead.phone}</td>
        <td class="hide-mobile">${lead.email}</td>
        <td><span class="badge badge-primary">${lead.course}</span></td>
        <td class="hide-mobile">${lead.source}</td>
        <td class="hide-mobile">${lead.assignedUser}</td>
        <td><span class="badge ${statusStyles[lead.status] || 'badge-neutral'}">${lead.status}</span></td>
        <td class="hide-mobile">${lead.nextFollowup}</td>
        <td class="hide-mobile text-muted">${lead.createdDate}</td>
        <td>
          <div class="row-actions">
            <button class="row-action-btn view" data-tooltip="View" onclick="ViewLeads.viewLead(${lead.id})">👁</button>
            <button class="row-action-btn edit" data-tooltip="Edit" onclick="ViewLeads.editLead(${lead.id})">✏️</button>
            <button class="row-action-btn delete" data-tooltip="Delete" onclick="ViewLeads.deleteLead(${lead.id})">🗑</button>
          </div>
        </td>
      </tr>
    `).join('');
    }

    // ─── Pagination ───
    function renderPagination() {
        const paginationEl = document.getElementById('pagination');
        if (!paginationEl) return;

        const totalPages = Math.ceil(filteredLeads.length / ITEMS_PER_PAGE);

        if (totalPages <= 1) {
            paginationEl.innerHTML = '';
            return;
        }

        let html = `<button class="pagination-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="ViewLeads.goToPage(${currentPage - 1})">‹</button>`;

        for (let i = 1; i <= totalPages; i++) {
            if (totalPages > 7) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="ViewLeads.goToPage(${i})">${i}</button>`;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    html += `<span class="pagination-btn" style="pointer-events:none;">…</span>`;
                }
            } else {
                html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="ViewLeads.goToPage(${i})">${i}</button>`;
            }
        }

        html += `<button class="pagination-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="ViewLeads.goToPage(${currentPage + 1})">›</button>`;

        paginationEl.innerHTML = html;
    }

    function goToPage(page) {
        const totalPages = Math.ceil(filteredLeads.length / ITEMS_PER_PAGE);
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        renderTable();
        renderPagination();
        updateLeadsCount();
    }

    function updateLeadsCount() {
        const countEl = document.getElementById('leadsCount');
        if (!countEl) return;

        const start = (currentPage - 1) * ITEMS_PER_PAGE + 1;
        const end = Math.min(currentPage * ITEMS_PER_PAGE, filteredLeads.length);

        countEl.innerHTML = `Showing <strong>${filteredLeads.length > 0 ? start : 0}-${end}</strong> of <strong>${filteredLeads.length}</strong> leads`;
    }

    // ─── Actions ───
    function viewLead(id) {
        const lead = allLeads.find(l => l.id === id);
        if (!lead) return;

        const detailGrid = document.getElementById('leadDetailGrid');
        if (detailGrid) {
            detailGrid.innerHTML = Object.entries(lead).filter(([k]) => k !== 'id').map(([key, value]) => `
        <div class="detail-item">
          <div class="detail-label">${key.replace(/([A-Z])/g, ' $1').trim()}</div>
          <div class="detail-value">${value}</div>
        </div>
      `).join('');
        }

        const modalTitle = document.querySelector('#viewLeadModal .modal-header h3');
        if (modalTitle) modalTitle.textContent = `Lead: ${lead.name}`;

        App.openModal('viewLeadModal');
    }

    function editLead(id) {
        App.showToast('info', 'Edit Lead', `Redirecting to edit lead #${id}...`);
        // In a real app, redirect to create-lead.html with lead data
        setTimeout(() => {
            window.location.href = `create-lead.html?edit=${id}`;
        }, 800);
    }

    function deleteLead(id) {
        const lead = allLeads.find(l => l.id === id);
        if (!lead) return;

        const modalBody = document.querySelector('#deleteModal .modal-body p');
        if (modalBody) modalBody.textContent = `Are you sure you want to delete "${lead.name}"? This action cannot be undone.`;

        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            confirmBtn.onclick = () => {
                confirmBtn.classList.add('loading');
                setTimeout(() => {
                    const index = allLeads.findIndex(l => l.id === id);
                    if (index !== -1) allLeads.splice(index, 1);
                    App.closeModal('deleteModal');
                    confirmBtn.classList.remove('loading');
                    App.showToast('success', 'Lead Deleted', `"${lead.name}" has been removed.`);
                    applyFilters();
                }, 1000);
            };
        }

        App.openModal('deleteModal');
    }

    function clearFilters() {
        const search = document.getElementById('leadsSearch');
        const course = document.getElementById('filterCourse');
        const source = document.getElementById('filterSource');
        const status = document.getElementById('filterStatus');

        if (search) search.value = '';
        if (course) course.value = '';
        if (source) source.value = '';
        if (status) status.value = '';

        applyFilters();
    }

    // ─── Init ───
    function init() {
        filteredLeads = [...allLeads];
        renderTable();
        renderPagination();
        updateLeadsCount();

        // Event listeners
        const searchInput = document.getElementById('leadsSearch');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(applyFilters, 300);
            });
        }

        const filterCourse = document.getElementById('filterCourse');
        const filterSource = document.getElementById('filterSource');
        const filterStatus = document.getElementById('filterStatus');

        if (filterCourse) filterCourse.addEventListener('change', applyFilters);
        if (filterSource) filterSource.addEventListener('change', applyFilters);
        if (filterStatus) filterStatus.addEventListener('change', applyFilters);
    }

    return { init, goToPage, viewLead, editLead, deleteLead, clearFilters };
})();

document.addEventListener('DOMContentLoaded', ViewLeads.init);
