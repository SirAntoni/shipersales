@props(['width' => 'w-auto', 'height' => 'h-auto', 'classReport' => null])

<div class="{{ $width }} {{ $height }}">
    <x-base.chart
        class="{{ $classReport }}"
        {{ $attributes->merge($attributes->whereDoesntStartWith('class')->getAttributes()) }}
    >
    </x-base.chart>
</div>

@pushOnce('vendors')
    @vite('resources/js/vendors/lodash.js')
@endPushOnce

@pushOnce('scripts')
    @vite('resources/js/utils/colors.js')
    @vite('resources/js/components/report-bar-chart-5.js')
@endPushOnce
