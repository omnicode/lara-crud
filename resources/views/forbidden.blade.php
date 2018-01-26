@extends('lara-view::layouts.app')

@section('content')
    <div class="row">
        <div class="col-sm-offset-4 col-sm-4 text-center mt50">
            <h1>{{ $message }}</h1>
            @php
                $buttonConfig = config('lara_crud.forbidden.button');
            @endphp
            <h1>
                {!! LaraLink::link($buttonConfig['title'], ['route' => $buttonConfig['route'], 'btn' => true]) !!}
            </h1>
        </div>
    </div>



@endsection