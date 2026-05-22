{{-- Dark Theme Footer --}}
<footer class="border-t border-dark-line px-8 py-4">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-dark-tx-3">
        <div>
            &copy; {{ date('Y') }} {{ config('app.name', 'td Academy') }}
        </div>
        <div class="flex items-center gap-4">
            <a href="#" class="hover:text-dark-tuerkis transition-colors">Impressum</a>
            <a href="#" class="hover:text-dark-tuerkis transition-colors">Datenschutz</a>
            <a href="#" class="hover:text-dark-tuerkis transition-colors">Hilfe</a>
        </div>
    </div>
</footer>
