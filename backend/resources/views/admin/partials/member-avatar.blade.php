@php $size = $size ?? 38; @endphp

@if ($user->photo)
    <img src="{{ $user->photo_url }}" alt="{{ $user->name }}" class="avatar"
         style="width: {{ $size }}px; height: {{ $size }}px;">
@else
    <div class="avatar"
         style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.42) }}px;">
        {{ strtoupper(substr($user->name, 0, 1)) }}
    </div>
@endif