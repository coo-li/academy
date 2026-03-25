import './bootstrap';
import Alpine from 'alpinejs';
import './charts.js';

// Alpine.js Global Stores
Alpine.store('notifications', {
  items: [],
  add(notification) {
    const id = Date.now()
    this.items.push({ id, ...notification })
    if (notification.autoClose !== false) {
      setTimeout(() => this.remove(id), notification.duration || 5000)
    }
  },
  remove(id) {
    this.items = this.items.filter(item => item.id !== id)
  }
})

// Sidebar toggle store
Alpine.store('sidebar', {
  open: true,
  toggle() {
    this.open = !this.open
  }
})

// Dropdown store — guarantees only ONE dropdown open at a time across the entire page
Alpine.store('dropdown', {
  active: null,
  pos: { top: 0, right: 0, maxH: 400 },
  open(id, btn) {
    // #region agent log
    fetch('http://localhost:7833/ingest/1c51a961-1c92-4f0c-ab60-60f1218d2d67',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'999a08'},body:JSON.stringify({sessionId:'999a08',location:'app.js:open',message:'open() called',data:{requestedId:id,currentActive:this.active,btnTag:btn?.tagName,btnText:btn?.textContent?.trim()?.substring(0,30)},timestamp:Date.now(),hypothesisId:'B,C'})}).catch(()=>{});
    // #endregion
    if (this.active === id) { this.active = null; return }
    const r = btn.getBoundingClientRect()
    this.pos = {
      top: r.bottom + 4,
      right: window.innerWidth - r.right,
      maxH: Math.max(200, window.innerHeight - r.bottom - 20)
    }
    this.active = id
    // #region agent log
    fetch('http://localhost:7833/ingest/1c51a961-1c92-4f0c-ab60-60f1218d2d67',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'999a08'},body:JSON.stringify({sessionId:'999a08',location:'app.js:open-after',message:'active set',data:{newActive:this.active,pos:this.pos},timestamp:Date.now(),hypothesisId:'B,C'})}).catch(()=>{});
    // #endregion
    // #region agent log
    var _activeId = id; setTimeout(function(){var panels=document.querySelectorAll('[data-dd-panel]');var vis=[];panels.forEach(function(p,i){var s=window.getComputedStyle(p);if(s.display!=='none'){var hdr=p.querySelector('.truncate');vis.push({idx:i,display:s.display,opacity:s.opacity,pointerEvents:s.pointerEvents,text:hdr?hdr.textContent.trim():'?'});}});fetch('http://localhost:7833/ingest/1c51a961-1c92-4f0c-ab60-60f1218d2d67',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'999a08'},body:JSON.stringify({sessionId:'999a08',location:'app.js:panel-check',message:'visible panels after open',data:{activeId:_activeId,totalPanels:panels.length,visibleCount:vis.length,visiblePanels:vis},timestamp:Date.now(),hypothesisId:'A,E'})}).catch(function(){});},150);
    // #endregion
  },
  close() { this.active = null },
  isOpen(id) { return this.active === id }
})

// Global Search component
Alpine.data('globalSearch', () => ({
  query: '',
  results: {},
  showResults: false,
  loading: false,
  selectedIndex: -1,
  abortController: null,

  async search() {
    if (this.query.length < 2) {
      this.results = {}
      this.showResults = false
      return
    }

    if (this.abortController) this.abortController.abort()
    this.abortController = new AbortController()

    this.loading = true
    this.showResults = true

    try {
      const res = await fetch(`/search?q=${encodeURIComponent(this.query)}`, {
        signal: this.abortController.signal,
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      })
      this.results = await res.json()
      this.selectedIndex = -1
    } catch (e) {
      if (e.name !== 'AbortError') this.results = {}
    } finally {
      this.loading = false
    }
  },

  get modules() {
    return this.results.modules || []
  },

  moveDown() {
    const total = this.modules.length
    if (total === 0) return
    this.selectedIndex = (this.selectedIndex + 1) % total
  },

  moveUp() {
    const total = this.modules.length
    if (total === 0) return
    this.selectedIndex = this.selectedIndex <= 0 ? total - 1 : this.selectedIndex - 1
  },

  goToSelected() {
    if (this.selectedIndex >= 0 && this.selectedIndex < this.modules.length) {
      window.location.href = this.modules[this.selectedIndex].url
    }
  }
}))

// Notification Bell component
Alpine.data('notificationBell', () => ({
  open: false,
  items: [],
  unreadCount: 0,
  pollInterval: null,

  async load() {
    try {
      const res = await fetch('/notifications', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      })
      const data = await res.json()
      this.items = data.notifications
      this.unreadCount = data.unread_count
    } catch (e) {
      // silently fail
    }

    if (this.pollInterval) clearInterval(this.pollInterval)
    this.pollInterval = setInterval(() => this.load(), 60000)
  },

  toggle() {
    this.open = !this.open
    if (this.open) this.load()
  },

  async markRead(item) {
    if (item.read) return
    try {
      await fetch(`/notifications/${item.id}/read`, {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      item.read = true
      this.unreadCount = Math.max(0, this.unreadCount - 1)
    } catch (e) {
      // silently fail
    }
  },

  async markAllRead() {
    try {
      await fetch('/notifications/read-all', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      this.items.forEach(i => i.read = true)
      this.unreadCount = 0
    } catch (e) {
      // silently fail
    }
  }
}))

// Initialize Alpine
window.Alpine = Alpine
Alpine.start()

// Close dropdown on outside click
document.addEventListener('click', (e) => {
  if (!e.target.closest('[data-dd-trigger]') && !e.target.closest('[data-dd-panel]')) {
    Alpine.store('dropdown').active = null
  }
})

// Close dropdown on Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') Alpine.store('dropdown').active = null
})

// Expose notification helper globally
window.notify = (message, type = 'info', duration = 5000) => {
  Alpine.store('notifications').add({ message, type, duration })
}
