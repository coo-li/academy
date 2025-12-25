@props(['name' => 'modal', 'title' => '', 'maxWidth' => 'lg'])

@php
$maxWidthClass = match($maxWidth) {
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    default => 'max-w-lg'
};
@endphp

<div 
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
    {{ $attributes }}
>
    <!-- Trigger -->
    @isset($trigger)
    <div @click="open = true">
        {{ $trigger }}
    </div>
    @endisset
    
    <!-- Modal -->
    <template x-teleport="body">
        <div x-show="open" class="relative z-50">
            <!-- Backdrop -->
            <div 
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="open = false"
                class="modal-backdrop"
            ></div>
            
            <!-- Modal Content -->
            <div 
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="modal {{ $maxWidthClass }}"
            >
                <!-- Header -->
                <div class="modal-header">
                    <h3 class="modal-title">{{ $title }}</h3>
                    <button @click="open = false" class="btn-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="modal-body">
                    {{ $slot }}
                </div>
                
                <!-- Footer -->
                @isset($footer)
                <div class="modal-footer">
                    {{ $footer }}
                </div>
                @endisset
            </div>
        </div>
    </template>
</div>
