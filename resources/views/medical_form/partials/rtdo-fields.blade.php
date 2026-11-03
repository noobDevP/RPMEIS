<div style="border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px; background: rgba(255, 255, 255, 0.4);">
    <h3 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 12px;">78. R / T / D / O</h3>
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; max-width: 280px;">
        @foreach(['r' => 'R', 't' => 'T', 'd' => 'D', 'o' => 'O'] as $letter => $label)
            @php $name = 'rtdo_'.$letter; @endphp
            <div>
                <label for="{{ $name }}" style="display: block; font-size: 0.75rem; font-weight: bold; text-align: center; margin-bottom: 4px; color: #475569;">{{ $label }}</label>
                <input type="text" id="{{ $name }}" name="{{ $name }}"
                    value="{{ old($name, $data[$name] ?? '') }}"
                    style="width: 100%; padding: 6px 4px; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 0.875rem; text-align: center; font-family: monospace; background: rgba(255, 255, 255, 0.4);">
            </div>
        @endforeach
    </div>
</div>
