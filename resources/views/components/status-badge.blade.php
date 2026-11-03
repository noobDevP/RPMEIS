@props(['status' => null, 'label' => null])

@php
    $display = $label ?? ($status ?: 'NOT YET STARTED');
    $class = \App\Enums\ApprovalStatus::cssClassFor($status);
@endphp

<span {{ $attributes->merge(['class' => "status-badge {$class}"]) }}
      style="display:inline-block;padding:4px 10px;border-radius:4px;font-size:0.85rem;font-weight:600;">
    {{ $display }}
</span>
