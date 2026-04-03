@extends('layouts.app')

@section('title', 'Editar lista | SmartSuper')

@section('content')
    <section class="panel-card form-card">
        <div class="section-heading">
            <p class="eyebrow">Editar lista</p>
            <h1>Actualizar lista de compra</h1>
        </div>

        <form class="stack-form" action="{{ route('listas.update', $lista) }}" method="POST">
            @csrf
            @method('PUT')
            @include('listas.partials.form', ['lista' => $lista])
            <button class="button button--primary" type="submit">Guardar cambios</button>
        </form>
    </section>
@endsection
