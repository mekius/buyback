@extends('layouts.app')

@section('content')
    <h1>{{ $user->name }} [ID: {{ $user->getProvider('evesso')->provider_user_id }}]</h1>
    <img src="{{ $user->avatar }}" alt="{{ $user->name }}"/>
    <div>
        @foreach($assetList as $locationId => $assets)
            <h3>{{ current($assets)['locationName'] }}</h3>
            @foreach($assets as $typeId => $asset)
                <pre>{{  json_encode($asset) . "\n" }}</pre>
            @endforeach
        @endforeach
    </div>
@endsection