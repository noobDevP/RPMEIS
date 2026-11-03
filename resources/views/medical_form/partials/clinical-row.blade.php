@php
    use App\Support\FormFieldRegistry;
    $selected = old($field, $data[$field] ?? '');
    $clinicalRequired = $num >= 18 && $num <= 42;
@endphp
<tr style="border-bottom: 1px solid #f1f5f9;">
    <td style="padding: 8px 16px; font-size: 0.75rem; font-weight: 500; color: #334155; width: 40px;">{{ $num }}@if($clinicalRequired)<span style="color: #dc2626;">*</span>@endif</td>
    <td style="padding: 8px 16px; font-size: 0.875rem; color: var(--text-main);">{{ $label }}</td>
    <td style="padding: 8px 16px; text-align: center;">
        <input type="radio" name="{{ $field }}" value="normal" @checked($selected === 'normal')
            @if($clinicalRequired) required @endif
            style="border-color: var(--border-color);">
    </td>
    <td style="padding: 8px 16px; text-align: center;">
        <input type="radio" name="{{ $field }}" value="abnormal" @checked($selected === 'abnormal')
            style="border-color: var(--border-color);">
    </td>
    <td style="padding: 8px 16px; text-align: center;">
        <input type="radio" name="{{ $field }}" value="ne" @checked($selected === 'ne')
            style="border-color: var(--border-color);">
    </td>
</tr>
