<!-- Footer -->
<footer class="bg-white border-t border-surface-200 px-6 py-4">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-surface-500">
        <div>
            &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. Alle Rechte vorbehalten.
        </div>
        <div class="flex items-center gap-4">
            <a href="#" class="hover:text-brand-primary transition-colors">Impressum</a>
            <a href="#" class="hover:text-brand-primary transition-colors">Datenschutz</a>
            <a href="#" class="hover:text-brand-primary transition-colors">Hilfe</a>
        </div>
    </div>
</footer>

