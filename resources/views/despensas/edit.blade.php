@extends('layouts.app')

@section('title', __('despensas.edit.meta_title'))

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <section class="mx-auto max-w-3xl rounded-[20px] bg-white p-8 shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                <div class="mb-6 flex items-center gap-4">
                    <div class="flex h-[60px] w-[60px] items-center justify-center rounded-[10px] bg-brand-50 text-brand-500">
                        <x-ui.icon name="archive-box" class="h-8 w-8" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold uppercase text-brand-600">{{ __('despensas.edit.kicker') }}</p>
                        <h1 class="text-3xl font-semibold text-ink-900">{{ __('despensas.edit.title') }}</h1>
                    </div>
                </div>

                <form class="grid gap-5" action="{{ route('despensas.update', $despensa) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('despensas.partials.form', ['despensa' => $despensa])
                    <div class="flex flex-wrap gap-3">
                        <button class="ss-btn-green" type="submit">{{ __('common.save_changes') }}</button>
                        <a class="ss-btn-outline" href="{{ route('despensas.index') }}">{{ __('common.back') }}</a>
                    </div>
                </form>
            </section>
        </div>
    </section>
@endsection
