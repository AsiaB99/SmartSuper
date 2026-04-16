@extends('layouts.app')

@section('title', 'Editar despensa | SmartSuper')

@section('content')
    <section class="panel-card form-card">
        <div class="section-heading">
            <p class="eyebrow">Editar despensa</p>
            <h1>Actualizar despensa</h1>
        </div>

        <form class="stack-form" action="{{ route('despensas.update', $despensa) }}" method="POST">
            @csrf
            @method('PUT')
            @include('despensas.partials.form', ['despensa' => $despensa])
            <button class="button button--primary" type="submit">Guardar cambios</button>
        </form>
    </section>
@endsection
