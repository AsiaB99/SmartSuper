<footer class="ss-footer w-full overflow-x-hidden">
    <div class="ss-container py-10">
        <div class="grid gap-12 md:grid-cols-3">
            <div class="flex flex-col items-center text-center">
                <h2 class="inline-flex items-center gap-2 text-xl font-semibold text-brand-500">
                    <x-ui.icon name="shopping-cart" class="h-5 w-5" />
                    <span>SmartSuper</span>
                </h2>
                <p class="mt-3 text-sm leading-7 text-white/85">Tu asistente de compra inteligente para combatir la inflación.</p>
            </div>

            <div class="flex flex-col items-center text-center">
                <h3 class="font-semibold text-brand-500">Enlaces</h3>
                <ul class="mt-3 space-y-2 text-sm text-white/85">
                    <li><a href="{{ route('aviso-legal') }}" class="transition hover:text-brand-400 hover:underline">Aviso Legal</a></li>
                    <li><a href="{{ route('privacidad') }}" class="transition hover:text-brand-400 hover:underline">Privacidad</a></li>
                    <li><a href="{{ route('contacto') }}" class="transition hover:text-brand-400 hover:underline">Contacto</a></li>
                </ul>
            </div>

            <div class="flex flex-col items-center text-center">
                <h3 class="font-semibold text-brand-500">Síguenos</h3>
                <div class="mt-4 flex justify-center gap-4 text-white/85">
                    <a href="https://www.instagram.com/smartsuper.web/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/25 transition hover:border-brand-400 hover:text-brand-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5a4.25 4.25 0 0 0 4.25 4.25h8.5a4.25 4.25 0 0 0 4.25-4.25v-8.5a4.25 4.25 0 0 0-4.25-4.25h-8.5Zm8.75 1.75a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/>
                        </svg>
                    </a>
                    <a href="https://x.com/smartsuper_" target="_blank" rel="noopener noreferrer" aria-label="X / Twitter" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/25 transition hover:border-brand-400 hover:text-brand-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M18.901 2H22l-6.767 7.733L23.2 22h-6.236l-4.887-6.783L6.14 22H3.04l7.237-8.27L.8 2h6.395l4.417 6.176L18.9 2Zm-1.09 18.1h1.717L6.266 3.803H4.422L17.81 20.1Z"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="Facebook" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/25 transition hover:border-brand-400 hover:text-brand-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.7-1.6h1.5V4.8c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4V11H8v3h2.5v8h3Z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full bg-[var(--color-footer-oscuro)] py-5 text-center text-sm text-white/85">
        &copy; {{ now()->year }} SmartSuper. Desarrollado por Asia Bosch Dwiyanti.
    </div>
</footer>
