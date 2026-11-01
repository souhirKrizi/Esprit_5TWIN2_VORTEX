@props(['compte'])
<span {{ $attributes->merge(['class' => 'ha-avatar']) }} aria-hidden="true">
    @if($compte?->photo)
        <img src="{{ $compte->photoUrl() }}" alt="">
    @else
        <x-space-icon name="user" />
    @endif
</span>
