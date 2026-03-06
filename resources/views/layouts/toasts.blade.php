<!-- Toast Container -->
<div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2" x-data>
    <template x-for="notification in $store.notifications.items" :key="notification.id">
        <div x-show="true" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-4"
             :class="{
                 'toast-success': notification.type === 'success',
                 'toast-warning': notification.type === 'warning',
                 'toast-error': notification.type === 'error',
                 'toast-info': notification.type === 'info'
             }"
             class="toast max-w-sm">
            
            <!-- Icon -->
            <template x-if="notification.type === 'success'">
                <svg class="w-5 h-5 text-ui-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </template>
            <template x-if="notification.type === 'error'">
                <svg class="w-5 h-5 text-ui-error flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </template>
            <template x-if="notification.type === 'warning'">
                <svg class="w-5 h-5 text-ui-warning flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </template>
            <template x-if="notification.type === 'info'">
                <svg class="w-5 h-5 text-ui-info flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </template>
            
            <!-- Message -->
            <span class="flex-1 text-sm" x-text="notification.message"></span>
            
            <!-- Close Button -->
            <button @click="$store.notifications.remove(notification.id)" 
                    class="flex-shrink-0 text-surface-400 hover:text-surface-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </template>
</div>

<!-- Session Flash Messages -->
@if(session('success'))
<script>
    document.addEventListener('alpine:init', () => {
        setTimeout(() => notify('{{ session('success') }}', 'success'), 100);
    });
</script>
@endif

@if(session('error'))
<script>
    document.addEventListener('alpine:init', () => {
        setTimeout(() => notify('{{ session('error') }}', 'error'), 100);
    });
</script>
@endif

@if(session('warning'))
<script>
    document.addEventListener('alpine:init', () => {
        setTimeout(() => notify('{{ session('warning') }}', 'warning'), 100);
    });
</script>
@endif

@if(session('info'))
<script>
    document.addEventListener('alpine:init', () => {
        setTimeout(() => notify('{{ session('info') }}', 'info'), 100);
    });
</script>
@endif

