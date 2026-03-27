/* ============================================
   ICSS CRM — Global App Logic
   ============================================ */

const App = (() => {
  'use strict';

  const roleParam = window.CRM_ROLE === 'admin' ? 'role=admin' : '';
  const getCrmBase = () => {
    const p = window.location.pathname || '';
    const lower = p.toLowerCase();
    const idx = lower.indexOf('/crm/');
    if (idx >= 0) return p.substring(0, idx) + '/crm';
    return '/CRM';
  };
  const crmBase = getCrmBase();
  window.CRM_BASE = crmBase;
  const apiBase = window.CRM_ROLE === 'admin' ? `${crmBase}/Dashboard/` : '';
  const withRole = (url) => {
    const baseUrl = url.startsWith('api/') ? `${apiBase}${url}` : url;
    return roleParam ? `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}${roleParam}` : baseUrl;
  };

  const userSettings = {
    email_notifications: 0,
    sound_alerts: 0
  };
  let audioCtx = null;
  let audioUnlocked = false;

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

    if (userSettings.sound_alerts) {
      playNotificationSound();
    }

    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => removeToast(toast));

    setTimeout(() => removeToast(toast), duration);
  }

  // ---------- Notifications ----------
  function initNotifications() {
    const btn = document.getElementById('notificationBtn');
    if (!btn) return;

    let panel = document.querySelector('.notification-panel');
    if (!panel) {
      panel = document.createElement('div');
      panel.className = 'notification-panel';
      panel.innerHTML = `
        <div class="notification-header">Notifications</div>
        <div class="notification-list"></div>
      `;
      (btn.closest('.navbar-actions') || document.body).appendChild(panel);
    }

    const list = panel.querySelector('.notification-list');
    let lastSignature = null;
    async function loadNotifications() {
      try {
        let res = await fetch(withRole('api/get_notifications.php'), { cache: 'no-store' });
        if (!res.ok) {
          const fallback = withRole(window.location.origin + `${crmBase}/Dashboard/api/get_notifications.php`);
          res = await fetch(fallback, { cache: 'no-store' });
        }
        if (!res.ok) throw new Error('Failed to load notifications');

        const data = await res.json();
        const items = Array.isArray(data?.items) ? data.items : [];
        if (list) {
          list.innerHTML = items.length ? items.map(item => `
            <div class="notification-item">
              <div class="notification-title">${item.title}</div>
              <div class="notification-text">${item.text}</div>
            </div>
          `).join('') : '<div class="notification-item"><div class="notification-text">No new notifications</div></div>';
        }
        const dot = btn.querySelector('.notification-dot');
        if (dot) dot.style.display = items.length ? '' : 'none';
        const signature = items.map(i => `${i.title}::${i.text}`).join('|');
        if (userSettings.sound_alerts && lastSignature !== null && signature !== lastSignature) {
          playNotificationSound();
        }
        lastSignature = signature;
      } catch (e) {
        if (list) {
          list.innerHTML = '<div class="notification-item"><div class="notification-text">No new notifications</div></div>';
        }
      }
    }

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      loadNotifications();
      panel.classList.toggle('open');
    });

    document.addEventListener('click', () => {
      panel.classList.remove('open');
    });

    // Background polling for new notifications
    loadNotifications();
    setInterval(loadNotifications, 15000);
  }

  function playNotificationSound() {
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      if (!audioCtx) audioCtx = new AudioContext();
      if (audioCtx.state === 'suspended') {
        audioCtx.resume().catch(() => {});
      }
      const gain = audioCtx.createGain();
      gain.gain.value = 0.06;
      gain.connect(audioCtx.destination);

      const playTone = (freq, start, duration) => {
        const osc = audioCtx.createOscillator();
        osc.type = 'sine';
        osc.frequency.value = freq;
        osc.connect(gain);
        osc.start(start);
        osc.stop(start + duration);
      };

      const now = audioCtx.currentTime;
      playTone(660, now, 0.09);
      playTone(880, now + 0.1, 0.09);
    } catch (e) {
      // ignore sound failures
    }
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

    let debounceTimer;
    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        const query = searchInput.value.trim();

        // If a page-level search exists (View Leads), forward the query.
        const leadsSearch = document.getElementById('leadsSearch');
        if (leadsSearch) {
          leadsSearch.value = query;
          leadsSearch.dispatchEvent(new Event('input', { bubbles: true }));
          return;
        }

        // Fallback: filter any data table rows by text.
        const tbody = document.querySelector('.data-table tbody');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        if (!query) {
          rows.forEach(r => { r.style.display = ''; });
          return;
        }
        const q = query.toLowerCase();
        rows.forEach(r => {
          const text = r.textContent.toLowerCase();
          r.style.display = text.includes(q) ? '' : 'none';
        });
      }, 200);
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

  // ---------- Settings Toggles ----------
  async function updateUserSetting(key, value) {
    try {
      const res = await fetch(withRole('api/update_user_settings.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key, value })
      });
      return res.ok;
    } catch (e) {
      return false;
    }
  }

  function initSettingsToggles() {
    const emailToggle = document.getElementById('emailNotif');
    const soundToggle = document.getElementById('soundAlert');

    if (emailToggle) {
      userSettings.email_notifications = emailToggle.checked ? 1 : 0;
      emailToggle.addEventListener('change', async () => {
        userSettings.email_notifications = emailToggle.checked ? 1 : 0;
        const ok = await updateUserSetting('email_notifications', userSettings.email_notifications);
        if (!ok) {
          emailToggle.checked = !emailToggle.checked;
          userSettings.email_notifications = emailToggle.checked ? 1 : 0;
        }
      });
    }

    if (soundToggle) {
      userSettings.sound_alerts = soundToggle.checked ? 1 : 0;
      soundToggle.addEventListener('change', async () => {
        userSettings.sound_alerts = soundToggle.checked ? 1 : 0;
        const ok = await updateUserSetting('sound_alerts', userSettings.sound_alerts);
        if (!ok) {
          soundToggle.checked = !soundToggle.checked;
          userSettings.sound_alerts = soundToggle.checked ? 1 : 0;
        } else if (userSettings.sound_alerts) {
          // Play a short test sound when enabling
          playNotificationSound();
        }
      });
    }
  }

  function initAudioUnlock() {
    const unlock = () => {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      if (!audioCtx) audioCtx = new AudioContext();
      if (audioCtx.state === 'suspended') {
        audioCtx.resume().catch(() => {});
      }
      audioUnlocked = true;
      window.removeEventListener('pointerdown', unlock);
      window.removeEventListener('keydown', unlock);
    };
    window.addEventListener('pointerdown', unlock, { once: true });
    window.addEventListener('keydown', unlock, { once: true });
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
    initNotifications();
    initSettingsToggles();
    initAudioUnlock();
    loadUserSettings();
  }

  async function loadUserSettings() {
    try {
      const res = await fetch(withRole('api/get_user_settings.php'), { cache: 'no-store' });
      if (!res.ok) return;
      const data = await res.json();
      userSettings.email_notifications = data.email_notifications ? 1 : 0;
      userSettings.sound_alerts = data.sound_alerts ? 1 : 0;

      const emailToggle = document.getElementById('emailNotif');
      if (emailToggle) emailToggle.checked = !!userSettings.email_notifications;
      const soundToggle = document.getElementById('soundAlert');
      if (soundToggle) soundToggle.checked = !!userSettings.sound_alerts;
    } catch (e) {
      // ignore
    }
  }

  // Public API
  return {
    init,
    showToast,
    openModal,
    closeModal,
    formatNumber,
    formatCurrency,
    playNotificationSound
  };
})();

window.App = App;
document.addEventListener('DOMContentLoaded', App.init);
