@extends('layouts.app')

@section('title', 'Crear despensa | SmartSuper')

@section('content')
    <section class="panel-card form-card">
        <div class="section-heading">
            <p class="eyebrow">Nueva despensa</p>
            <h1>Crear despensa</h1>
        </div>

        <form class="stack-form" action="{{ route('despensas.store') }}" method="POST">
            @csrf
            @include('despensas.partials.form', ['despensa' => null])
            <button class="button button--primary" type="submit">Guardar despensa</button>
        </form>
    </section>
@endsection
