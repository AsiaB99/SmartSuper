@extends('layouts.app')

@section('title', 'Crear lista | SmartSuper')

@section('content')
    <section class="panel-card form-card">
        <div class="section-heading">
            <p class="eyebrow">Nueva lista</p>
            <h1>Crear lista de compra</h1>
        </div>

        <form class="stack-form" action="{{ route('listas.store') }}" method="POST">
            @csrf
            @include('listas.partials.form', ['lista' => null])
            <button class="button button--primary" type="submit">Guardar lista</button>
        </form>
    </section>
@endsection
