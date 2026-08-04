@props(['condition'])

@php
    use App\Enums\ProductCondition;

    $enum = $condition instanceof ProductCondition
        ? $condition
        : ProductCondition::tryFrom((string) $condition);
@endphp

@if ($enum)
    <span {{ $attributes->class([
        $enum->badgeClasses(),
        'product-condition-badge inline-flex items-center text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded shadow-sm tracking-wide !text-cream',
    ]) }}>
        {{ $enum->label() }}
    </span>
@endif
