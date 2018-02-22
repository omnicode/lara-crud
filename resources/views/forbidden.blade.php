@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-sm-offset-4 col-sm-4 text-center mt50">
            <h1>{{ $message }}</h1>
            @php
                $buttonConfig = config('lara_crud.forbidden.button');
            @endphp
            <h1>
                <a href="{{ route($buttonConfig['route']) }}" class="btn btn-primary">{{$buttonConfig['title']}}</a>
            </h1>
        </div>
    </div>
@endsection