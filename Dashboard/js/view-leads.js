/* ============================================
   ICSS CRM — View Leads Table Management
   ============================================ */

const ViewLeads = (() => {
    'use strict';

    const ITEMS_PER_PAGE = 5;
    let currentPage = 1;
    let filteredLeads = [];
    let allLeads = [];
    const roleParam = window.CRM_ROLE === 'admin' ? 'role=admin' : '';
    const crmBase = window.CRM_BASE || '/CRM';
    const apiBase = window.CRM_ROLE === 'admin' ? `${crmBase}/Dashboard/` : '';
    const withRole = (url) => {
        const baseUrl = url.startsWith('api/') ? `${apiBase}${url}` : url;
        return roleParam ? `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}${roleParam}` : baseUrl;
    };

    const statusStyles = {
        'Positive': 'badge-success badge-dot',
        'Considering': 'badge-warning badge-dot',
        'Negative': 'badge-danger badge-dot',
        'May Enroll Later': 'badge-neutral badge-dot'
    };
    const isAdmin = window.CRM_ROLE === 'admin';
    const selectedIds = new Set();

    function formatDate(value) {
        if (!value) return '-';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function normalizeLead(raw) {
        return {
            id: raw.id,
            name: raw.name || '-',
            phone: raw.phone || '-',
            email: raw.email || '-',
            course: raw.course || '-',
            source: raw.source || '-',
            assignedUser: raw.assignedUser || '-',
            status: raw.status || 'Considering',
            nextFollowup: formatDate(raw.nextFollowup),
            nextFollowupRaw: raw.nextFollowup || '',
            createdDate: formatDate(raw.createdDate),
            createdDateRaw: raw.createdDate || ''
        };
    }

    async function loadLeads() {
        try {
            let res = await fetch(withRole('api/get_leads.php'), { cache: 'no-store' });
            if (!res.ok) {
                const fallback = withRole(window.location.origin + `${crmBase}/Dashboard/api/get_leads.php`);
                res = await fetch(fallback, { cache: 'no-store' });
            }
            if (!res.ok) throw new Error('Failed to load leads');

            const data = await res.json();
            const rows = Array.isArray(data?.leads) ? data.leads : [];
            allLeads = rows.map(normalizeLead);
            applyFilters();
        } catch (e) {
            allLeads = [];
            filteredLeads = [];
            renderTable();
            renderPagination();
            updateLeadsCount();
        }
    }

    // ─── Filter & Search ───
    function applyFilters() {
        const searchTerm = (document.getElementById('leadsSearch')?.value || '').toLowerCase();
        const courseFilter = document.getElementById('filterCourse')?.value || '';
        const sourceFilter = document.getElementById('filterSource')?.value || '';
        filteredLeads = allLeads.filter(lead => {
            const matchSearch = !searchTerm ||
                lead.name.toLowerCase().includes(searchTerm) ||
                lead.phone.includes(searchTerm) ||
                lead.email.toLowerCase().includes(searchTerm);
            const matchCourse = !courseFilter || lead.course === courseFilter;
            const matchSource = !sourceFilter || lead.source === sourceFilter;
            return matchSearch && matchCourse && matchSource;
        });

        currentPage = 1;
        renderTable();
        renderPagination();
        updateLeadsCount();
    }

    async function loadLookups() {
        try {
            let res = await fetch(withRole('api/lookups.php'), { cache: 'no-store' });
            if (!res.ok) {
                const fallback = withRole(window.location.origin + `${crmBase}/Dashboard/api/lookups.php`);
                res = await fetch(fallback, { cache: 'no-store' });
            }
            if (!res.ok) return;
            const data = await res.json();
            populateSelect('filterCourse', data.courses, 'All Courses');
            populateSelect('filterSource', data.sources, 'All Sources');
        } catch (e) {
            // ignore lookup failures
        }
    }

    function populateSelect(id, items, placeholder) {
        const select = document.getElementById(id);
        if (!select) return;
        select.innerHTML = '';
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = placeholder;
        select.appendChild(defaultOpt);

        const list = Array.isArray(items) ? [...items] : [];
        if (id === 'filterSource' && !list.includes('Other')) {
            list.push('Other');
        }
        list.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            select.appendChild(opt);
        });
    }

    // ─── Render Table ───
    function renderTable() {
        const tbody = document.getElementById('leadsTbody');
        if (!tbody) return;

        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end = start + ITEMS_PER_PAGE;
        const pageLeads = filteredLeads.slice(start, end);
        const colCount = isAdmin ? 11 : 10;

        if (pageLeads.length === 0) {
            tbody.innerHTML = `
        <tr>
          <td colspan="${colCount}">
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
        ${isAdmin ? `<td class="col-select"><input type="checkbox" class="lead-select" value="${lead.id}" ${selectedIds.has(lead.id) ? 'checked' : ''}></td>` : ''}
        <td>
          <div class="user-cell">
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
            </div>
        </td>
      </tr>
    `).join('');

        if (isAdmin) {
            bindRowSelection();
        }
    }

    function bindRowSelection() {
        const selectAll = document.getElementById('selectAllLeads');
        const rowChecks = Array.from(document.querySelectorAll('.lead-select'));
        rowChecks.forEach(chk => {
            chk.addEventListener('change', () => {
                const id = parseInt(chk.value, 10);
                if (Number.isFinite(id)) {
                    if (chk.checked) selectedIds.add(id);
                    else selectedIds.delete(id);
                }
                syncSelectAll();
            });
        });

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                if (selectAll.checked) {
                    filteredLeads.forEach(l => selectedIds.add(l.id));
                } else {
                    selectedIds.clear();
                }
                renderTable();
            });
            syncSelectAll();
        }
    }

    function syncSelectAll() {
        const selectAll = document.getElementById('selectAllLeads');
        if (!selectAll) return;
        if (filteredLeads.length === 0) {
            selectAll.checked = false;
            return;
        }
        const allSelected = filteredLeads.every(l => selectedIds.has(l.id));
        selectAll.checked = allSelected;
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

    async function applyBulkUpdate() {
        const selected = Array.from(document.querySelectorAll('.lead-select:checked'))
            .map(el => parseInt(el.value, 10))
            .filter(v => Number.isFinite(v));
        if (selected.length === 0) {
            App.showToast('warning', 'Select Leads', 'Please select at least one lead.');
            return;
        }
        const status = document.getElementById('bulkStatus')?.value || '';
        const counselor = document.getElementById('bulkCounselor')?.value.trim() || '';
        const followup = document.getElementById('bulkFollowup')?.value || '';
        if (!status && !counselor && !followup) {
            App.showToast('warning', 'No Changes', 'Set status, counselor, or follow-up date.');
            return;
        }
        const payload = new URLSearchParams({
            lead_ids: selected.join(','),
            counseling_status: status,
            counselor_name: counselor,
            follow_up_date: followup
        });
        try {
            const res = await fetch(withRole('api/admin_bulk_update_leads.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: payload
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                App.showToast('error', 'Update Failed', data.message || 'Could not update leads.');
                return;
            }
            // update local cache
            allLeads.forEach(l => {
                if (selected.includes(l.id)) {
                    if (status) l.status = status;
                    if (counselor) l.assignedUser = counselor;
                    if (followup) {
                        l.nextFollowupRaw = followup;
                        l.nextFollowup = formatDate(followup);
                    }
                }
            });
            App.showToast('success', 'Bulk Updated', data.message || 'Leads updated.');
            renderTable();
            updateLeadsCount();
        } catch (e) {
            App.showToast('error', 'Network Error', 'Please try again.');
        }
    }

    // ─── Actions ───
    function viewLead(id) {
        const lead = allLeads.find(l => l.id === id);
        if (!lead) return;

        const detailGrid = document.getElementById('leadDetailGrid');
        if (detailGrid) {
            const fields = [
                { key: 'name', label: 'Name', editable: true, field: 'full_name' },
                { key: 'phone', label: 'Phone', editable: true, field: 'phone_number' },
                { key: 'email', label: 'Email', editable: true, field: 'email_address' },
                { key: 'course', label: 'Course', editable: true, field: 'course_applied' },
                { key: 'source', label: 'Source', editable: true, field: 'source_of_lead' },
                { key: 'assignedUser', label: 'Assigned User', editable: true, field: 'counselor_name' },
                { key: 'status', label: 'Status', editable: true, field: 'counseling_status' },
                { key: 'nextFollowup', label: 'Next Follow-up', editable: true, field: 'follow_up_date' },
                { key: 'createdDate', label: 'Created Date', editable: false }
            ];

            detailGrid.innerHTML = fields.map(field => `
        <div class="detail-item" data-field="${field.field || ''}" data-editable="${field.editable ? '1' : '0'}">
          <div class="detail-label">${field.label}</div>
          <div class="detail-value">${lead[field.key] || '-'}</div>
        </div>
      `).join('');
        }

        const modalTitle = document.querySelector('#viewLeadModal .modal-header h3');
        if (modalTitle) modalTitle.textContent = `Lead: ${lead.name}`;

        const editBtn = document.getElementById('editLeadBtn');
        if (editBtn) {
            editBtn.onclick = () => toggleInlineEdit(detailGrid, lead);
        }

        App.openModal('viewLeadModal');
    }

    function editLead(id) {
        App.showToast('info', 'Edit Lead', `Edit mode is available in the modal.`);
    }

    async function toggleInlineEdit(detailGrid, lead) {
        if (!detailGrid) return;
        const isEditing = detailGrid.dataset.editing === 'true';
        if (!isEditing) {
            detailGrid.dataset.editing = 'true';
            detailGrid.querySelectorAll('.detail-item').forEach(item => {
                const editable = item.dataset.editable === '1';
                if (!editable) return;
                const label = item.querySelector('.detail-label')?.textContent?.trim() || '';
                const field = item.dataset.field || '';
                const valueEl = item.querySelector('.detail-value');
                if (!valueEl) return;
                const currentValue = valueEl.textContent.trim();
                const input = document.createElement('input');
                if (field === 'follow_up_date') {
                    input.type = 'date';
                    input.value = lead.nextFollowupRaw ? String(lead.nextFollowupRaw).slice(0, 10) : '';
                } else {
                    input.type = 'text';
                    input.value = currentValue === '-' ? '' : currentValue;
                }
                input.className = 'form-control';
                input.dataset.fieldLabel = label;
                input.dataset.fieldKey = field;
                valueEl.replaceWith(input);
            });
            const editBtn = document.getElementById('editLeadBtn');
            if (editBtn) editBtn.textContent = 'Save';
        } else {
            const updated = {};
            detailGrid.querySelectorAll('input.form-control').forEach(input => {
                const label = input.dataset.fieldLabel || '';
                const key = input.dataset.fieldKey || '';
                const value = input.value.trim() || '-';
                if (key) updated[key] = value;
            });

            const res = await saveLeadEdits(lead.id, updated);
            if (!res) {
                App.showToast('error', 'Update Failed', 'Could not save lead. Please try again.');
                return;
            }

            // update local lead object
            if (updated.full_name !== undefined) lead.name = updated.full_name || '-';
            if (updated.phone_number !== undefined) lead.phone = updated.phone_number || '-';
            if (updated.email_address !== undefined) lead.email = updated.email_address || '-';
            if (updated.course_applied !== undefined) lead.course = updated.course_applied || '-';
            if (updated.source_of_lead !== undefined) lead.source = updated.source_of_lead || '-';
            if (updated.counselor_name !== undefined) lead.assignedUser = updated.counselor_name || '-';
            if (updated.counseling_status !== undefined) lead.status = updated.counseling_status || '-';
            if (updated.follow_up_date !== undefined) {
                lead.nextFollowupRaw = updated.follow_up_date === '-' ? '' : updated.follow_up_date;
                lead.nextFollowup = formatDate(lead.nextFollowupRaw);
            }

            detailGrid.dataset.editing = 'false';
            detailGrid.querySelectorAll('.detail-item').forEach(item => {
                const valueEl = item.querySelector('input.form-control');
                if (!valueEl) return;
                const key = valueEl.dataset.fieldKey || '';
                let displayValue = valueEl.value.trim() || '-';
                if (key === 'follow_up_date') {
                    displayValue = formatDate(valueEl.value.trim());
                }
                const div = document.createElement('div');
                div.className = 'detail-value';
                div.textContent = displayValue || '-';
                valueEl.replaceWith(div);
            });

            const editBtn = document.getElementById('editLeadBtn');
            if (editBtn) editBtn.textContent = 'Edit Lead';

            renderTable();
            renderPagination();
            updateLeadsCount();
            App.showToast('success', 'Lead Updated', 'Changes saved to the database.');
        }
    }

    async function saveLeadEdits(id, updates) {
        try {
            const res = await fetch(withRole('api/update_lead.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, updates })
            });
            const data = await res.json().catch(() => ({}));
            return res.ok && data.success;
        } catch (e) {
            return false;
        }
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
        loadLookups();
        loadLeads();

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
        if (filterCourse) filterCourse.addEventListener('change', applyFilters);
        if (filterSource) filterSource.addEventListener('change', applyFilters);
        // Sync global search (navbar) with leads search
        const globalSearch = document.getElementById('globalSearch');
        if (globalSearch) {
            globalSearch.addEventListener('input', () => {
                const query = globalSearch.value;
                if (searchInput) {
                    searchInput.value = query;
                }
                applyFilters();
            });
        }

        const bulkApplyBtn = document.getElementById('bulkApplyBtn');
        const bulkClearBtn = document.getElementById('bulkClearBtn');
        if (bulkApplyBtn) bulkApplyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            applyBulkUpdate();
        });
        if (bulkClearBtn) bulkClearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const bs = document.getElementById('bulkStatus');
            const bc = document.getElementById('bulkCounselor');
            const bf = document.getElementById('bulkFollowup');
            if (bs) bs.value = '';
            if (bc) bc.value = '';
            if (bf) bf.value = '';
            selectedIds.clear();
            renderTable();
        });
    }

    return { init, goToPage, viewLead, editLead, deleteLead, clearFilters, getSelectedIds: () => Array.from(selectedIds) };
})();

document.addEventListener('DOMContentLoaded', ViewLeads.init);





