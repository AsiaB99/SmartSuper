<h2 class="text-2xl font-semibold text-ink-900">{{ __('common.summary') }}</h2>
<p class="mt-3 text-sm text-ink-600">{{ __('listas.products.summary.count') }}</p>
<p class="mt-3 text-3xl font-bold text-ink-900 text-center">{{ $lista->productos->count() }}</p>
@if ($puedeEditar && (auth()->user()?->latitud === null || auth()->user()?->longitud === null))
    <div class="mt-8 rounded-[12px] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
        {{ __('listas.products.location_notice') }}
        <form id="form-ubicacion-usuario" class="mt-3" action="{{ route('profile.location.update') }}" method="POST">
            @csrf
            <input type="hidden" name="latitud" id="ubicacion-latitud">
            <input type="hidden" name="longitud" id="ubicacion-longitud">
            <button type="button" id="btn-usar-ubicacion" class="ss-btn-outline w-full justify-center" data-loading-text="{{ __('js.location.loading') }}" data-error-text="{{ __('js.location.error') }}">
                {{ __('listas.products.use_location') }}
            </button>
        </form>
    </div>
@endif
<a class="ss-btn-green mt-8 w-full text-center {{ ! $puedeEditar ? 'pointer-events-none opacity-60' : '' }}"
   href="{{ $puedeEditar ? route('listas.recomendacion', $lista) : '#' }}">
    {{ __('listas.products.recommendation') }}
</a>
