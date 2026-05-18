import './bootstrap';
import './charts.js';
import './quiz-engine.js';

// Wait for Livewire to initialize (which loads Alpine)
document.addEventListener('livewire:init', () => {
    const Alpine = window.Alpine;
    if (!Alpine) {
        console.error('Alpine not found');
        return;
    }

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

    // Dropdown store
    Alpine.store('dropdown', {
        active: null,
        pos: { top: 0, right: 0, maxH: 400 },
        open(id, btn) {
            if (this.active === id) { this.active = null; return }
            const r = btn.getBoundingClientRect()
            this.pos = {
                top: r.bottom + 4,
                right: window.innerWidth - r.right,
                maxH: Math.max(200, window.innerHeight - r.bottom - 20)
            }
            this.active = id
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

    console.log('Alpine stores and data registered');
});

// Close dropdown on outside click
document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-dd-trigger]') && !e.target.closest('[data-dd-panel]')) {
        window.Alpine?.store('dropdown')?.close();
    }
})

// Close dropdown on Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') window.Alpine?.store('dropdown')?.close();
})

// Expose notification helper globally
window.notify = (message, type = 'info', duration = 5000) => {
    window.Alpine?.store('notifications')?.add({ message, type, duration })
}
