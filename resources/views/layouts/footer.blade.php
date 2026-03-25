<!-- Footer -->
<footer class="bg-white/50 border-t border-surface-100 px-8 py-5">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-surface-400">
        <div>
            &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}
        </div>
        <div class="flex items-center gap-5">
            <a href="#" class="hover:text-brand-primary transition-colors duration-200">Impressum</a>
            <a href="#" class="hover:text-brand-primary transition-colors duration-200">Datenschutz</a>
            <a href="#" class="hover:text-brand-primary transition-colors duration-200">Hilfe</a>
        </div>
    </div>
</footer>

