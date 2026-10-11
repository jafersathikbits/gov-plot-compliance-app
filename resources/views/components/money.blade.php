@props(['value'])
<span {{ $attributes->merge(['class' => 'tabular-nums']) }}>{{ \App\Support\Format::inr($value) }}</span>
