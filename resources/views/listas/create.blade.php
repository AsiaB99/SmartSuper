@extends('layouts.app')

@section('title', __('listas.create.meta_title'))

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <section class="mx-auto max-w-3xl rounded-[20px] bg-white p-8 shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                <div class="mb-6 flex items-center gap-4">
                    <div class="flex h-[60px] w-[60px] items-center justify-center rounded-[10px] bg-brand-50 text-brand-500">
                        <x-ui.icon name="list-bullet" class="h-8 w-8" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold uppercase text-brand-600">{{ __('listas.create.kicker') }}</p>
                        <h1 class="text-3xl font-semibold text-ink-900">{{ __('listas.create.title') }}</h1>
                    </div>
                </div>

                <form class="grid gap-5" action="{{ route('listas.store') }}" method="POST">
                    @csrf
                    @include('listas.partials.form', ['lista' => null, 'mostrarFechaCreacion' => false])
                    <div class="flex flex-wrap gap-3">
                        <button class="ss-btn-green" type="submit">{{ __('listas.create.submit') }}</button>
                        <a class="ss-btn-outline" href="{{ route('listas.index') }}">{{ __('common.back') }}</a>
                    </div>
                </form>
            </section>
        </div>
    </section>
@endsection
