/* ============================================
   ICSS CRM — Global App Logic
   ============================================ */

const App = (() => {
  'use strict';

  // ─── Sidebar ───
  function initSidebar() {
    const body = document.body;
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const hamburger = document.getElementById('hamburgerBtn');
    const overlay = document.getElementById('sidebarOverlay');

    // Collapse / expand (desktop)
    if (toggle) {
      toggle.addEventListener('click', () => {
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
      });
    }

    // Restore state
    if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 768) {
      body.classList.add('sidebar-collapsed');
    }

    // Hamburger (mobile)
    if (hamburger) {
      hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
      });
    }

    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
      });
    }
  }

  // ─── Profile Dropdown ───
  function initProfileDropdown() {
    const wrapper = document.querySelector('.profile-wrapper');
    if (!wrapper) return;

    const trigger = wrapper.querySelector('.profile-trigger');

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      wrapper.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
      if (!wrapper.contains(e.target)) {
        wrapper.classList.remove('open');
      }
    });
  }

  // ─── Active Sidebar Link ───
  function setActiveSidebarLink() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const links = document.querySelectorAll('.sidebar-link');

    links.forEach(link => {
      link.classList.remove('active');
      const href = link.getAttribute('href');
      if (href === currentPage || (currentPage === '' && href === 'index.html')) {
        link.classList.add('active');
      }
    });
  }

  // ─── Toast Notifications ───
  function showToast(type, title, message, duration = 4000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const icons = {
      success: '✓',
      error: '✕',
      warning: '⚠',
      info: 'ℹ'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <span class="toast-icon">${icons[type] || 'ℹ'}</span>
      <div class="toast-body">
        <div class="toast-title">${title}</div>
        <div class="toast-message">${message}</div>
      </div>
      <span class="toast-close">&times;</span>
    `;

    container.appendChild(toast);

    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => removeToast(toast));

    setTimeout(() => removeToast(toast), duration);
  }

  function removeToast(toast) {
    if (!toast || toast.classList.contains('removing')) return;
    toast.classList.add('removing');
    setTimeout(() => toast.remove(), 300);
  }

  // ─── Modal ───
  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  function initModals() {
    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
          overlay.classList.remove('active');
          document.body.style.overflow = '';
        }
      });
    });

    // Close buttons
    document.querySelectorAll('.modal-close').forEach(btn => {
      btn.addEventListener('click', () => {
        const modal = btn.closest('.modal-overlay');
        if (modal) {
          modal.classList.remove('active');
          document.body.style.overflow = '';
        }
      });
    });

    // Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
          modal.classList.remove('active');
        });
        document.body.style.overflow = '';
      }
    });
  }

  // ─── Search shortcut ───
  function initSearchShortcut() {
    const searchInput = document.getElementById('globalSearch');
    if (!searchInput) return;

    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        searchInput.focus();
      }
    });
  }

  // ─── Tabs ───
  function initTabs() {
    document.querySelectorAll('.tabs').forEach(tabGroup => {
      const items = tabGroup.querySelectorAll('.tab-item');
      const containerId = tabGroup.dataset.tabGroup;

      items.forEach(item => {
        item.addEventListener('click', () => {
          items.forEach(i => i.classList.remove('active'));
          item.classList.add('active');

          const target = item.dataset.tab;
          if (containerId) {
            document.querySelectorAll(`[data-tab-content="${containerId}"] .tab-content`).forEach(c => {
              c.classList.remove('active');
            });
            const targetContent = document.getElementById(target);
            if (targetContent) targetContent.classList.add('active');
          }
        });
      });
    });
  }

  // ─── Format numbers ───
  function formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
  }

  function formatCurrency(num) {
    return '₹' + num.toLocaleString('en-IN');
  }

  // ─── Initialize ───
  function init() {
    initSidebar();
    initProfileDropdown();
    setActiveSidebarLink();
    initModals();
    initSearchShortcut();
    initTabs();
  }

  // Public API
  return {
    init,
    showToast,
    openModal,
    closeModal,
    formatNumber,
    formatCurrency
  };
})();

document.addEventListener('DOMContentLoaded', App.init);
