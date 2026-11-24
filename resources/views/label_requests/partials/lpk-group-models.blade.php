@php($models = $items->pluck('model')->filter(fn ($model) => filled($model))->uniqueStrict())
@if($models->isEmpty())
    Sin modelo capturado
@else
    {{ $models->count() === 1 ? 'Modelo' : 'Modelos' }} {{ $models->implode(', ') }}
@endif
