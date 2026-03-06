import './bootstrap';
import Alpine from 'alpinejs';
import './charts.js';

// #region agent log
fetch('http://localhost:7254/ingest/98a3519f-245d-4a9b-8e89-73886663337e',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'3d8121'},body:JSON.stringify({sessionId:'3d8121',location:'app.js:imports',message:'All imports OK',data:{alpineExists:!!Alpine},timestamp:Date.now(),runId:'run1',hypothesisId:'A'})}).catch(()=>{});
// #endregion

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

// Initialize Alpine
window.Alpine = Alpine
Alpine.start()

// #region agent log
fetch('http://localhost:7254/ingest/98a3519f-245d-4a9b-8e89-73886663337e',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'3d8121'},body:JSON.stringify({sessionId:'3d8121',location:'app.js:started',message:'Alpine.start() called',data:{version:Alpine.version||'n/a'},timestamp:Date.now(),runId:'run1',hypothesisId:'A'})}).catch(()=>{});
// #endregion

// Expose notification helper globally
window.notify = (message, type = 'info', duration = 5000) => {
  Alpine.store('notifications').add({ message, type, duration })
}
