<div style="border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px; background: rgba(255, 255, 255, 0.4);">
    <h3 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 12px;">76. Physical Profile (P U L H E S)</h3>
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; max-width: 400px;">
        @foreach(['p' => 'P', 'u' => 'U', 'l' => 'L', 'h' => 'H', 'e' => 'E', 's' => 'S'] as $letter => $label)
            @php 
                $name = 'profile_'.$letter; 
                $isEditable = in_array($letter, ['u', 'l']);
            @endphp
            <div>
                <label for="{{ $name }}" style="display: block; font-size: 0.75rem; font-weight: bold; text-align: center; margin-bottom: 4px; color: #475569;">{{ $label }}</label>
                <input type="text" id="{{ $name }}" name="{{ $name }}"
                    value="{{ old($name, $data[$name] ?? '') }}"
                    {{ $isEditable ? '' : 'readonly' }}
                    style="width: 100%; padding: 6px 4px; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 0.875rem; text-align: center; font-family: monospace; {{ $isEditable ? '' : 'background: rgba(0,0,0,0.05); cursor: not-allowed;' }}" 
                    title="{{ $isEditable ? 'Editable' : 'Calculated from assessment stations' }}">
            </div>
        @endforeach
    </div>
</div>
