@php
    use App\Support\FormSignatures;
    $reviewingOn = filter_var(old('reviewing_officer_enabled', $data['reviewing_officer_enabled'] ?? false), FILTER_VALIDATE_BOOLEAN);
    $attachedSheetsCount = FormSignatures::ATTACHED_SHEETS_COUNT;

    $optometristOn = filter_var(old('optometrist_enabled', $data['optometrist_enabled'] ?? false), FILTER_VALIDATE_BOOLEAN);
    $entOn = filter_var(old('ent_enabled', $data['ent_enabled'] ?? false), FILTER_VALIDATE_BOOLEAN);
    $npOn = filter_var(old('np_enabled', $data['np_enabled'] ?? false), FILTER_VALIDATE_BOOLEAN);
    $dentistOn = filter_var(old('dentist_enabled', $data['dentist_enabled'] ?? false), FILTER_VALIDATE_BOOLEAN);
    $ecgOn = filter_var(old('ecg_enabled', $data['ecg_enabled'] ?? false), FILTER_VALIDATE_BOOLEAN);
@endphp
<div style="border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px; background: rgba(255, 255, 255, 0.4);" id="signatures-section">
    <h3 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 4px;">79–82. Certification &amp; Signatures</h3>
    <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 16px;">
        Assign specialist names for each completed assessment.
    </p>

    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
        {{-- Physician / Main doctor --}}
        <div style="border: 1px solid var(--border-color); border-radius: var(--radius); padding: 12px; background: rgba(255, 255, 255, 0.5);">
            <label style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 4px; color: #475569;">79–80. Main physician / doctor</label>
            <input type="text" name="physician_name" value="{{ old('physician_name', $data['physician_name'] ?? '') }}"
                placeholder="Doctor's name / signature line"
                style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 0.875rem; color: var(--text-main); background: rgba(255, 255, 255, 0.4);">
        </div>

        @foreach([
            ['key' => 'optometrist', 'label' => 'Optometrist (fields 58–69)', 'on' => $optometristOn, 'name' => old('optometrist_name', $data['optometrist_name'] ?? '')],
            ['key' => 'ent', 'label' => 'ENT doctor (fields 70–71)', 'on' => $entOn, 'name' => old('ent_name', $data['ent_name'] ?? '')],
            ['key' => 'np', 'label' => 'NP doctor / neuropsychologist (field 72)', 'on' => $npOn, 'name' => old('np_name', $data['np_name'] ?? '')],
            ['key' => 'ecg', 'label' => 'ECG technician (fields 57–58)', 'on' => $ecgOn, 'name' => old('ecg_name', $data['ecg_name'] ?? '')],
            ['key' => 'dentist', 'label' => 'Dentist (field 44)', 'on' => $dentistOn, 'name' => old('dentist_name', $data['dentist_name'] ?? '')],
        ] as $slot)
            <div class="specialty-slot" data-role="{{ $slot['key'] }}" style="border: 1px solid var(--border-color); border-radius: var(--radius); padding: 12px; background: rgba(255, 255, 255, 0.5);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 0.75rem; font-weight: 600; color: #475569;">{{ $slot['label'] }}</span>
                    <button type="button" class="btn-toggle-specialty" data-role="{{ $slot['key'] }}"
                        style="font-size: 0.75rem; font-weight: 600; color: #0284c7; background: none; border: none; cursor: pointer; text-decoration: underline;">
                        {{ $slot['on'] ? 'Remove' : 'Assign' }}
                    </button>
                </div>
                <input type="hidden" name="{{ $slot['key'] }}_enabled" class="specialty-enabled-input" value="{{ $slot['on'] ? '1' : '0' }}">
                <div class="specialty-select-wrap {{ $slot['on'] ? '' : 'hidden' }}">
                    <input type="text" name="{{ $slot['key'] }}_name" value="{{ $slot['name'] }}"
                        placeholder="Specialist name / signature line"
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 0.875rem; color: var(--text-main); background: rgba(255, 255, 255, 0.4);">
                </div>
            </div>
        @endforeach
    </div>

    {{-- Reviewing officer --}}
    <input type="hidden" name="reviewing_officer_enabled" id="reviewing_officer_enabled" value="{{ $reviewingOn ? '1' : '0' }}">
    <div id="reviewing-block" style="border: 1px solid var(--border-color); border-radius: var(--radius); padding: 12px; background: rgba(255, 255, 255, 0.5); {{ $reviewingOn ? '' : 'display: none;' }}">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
            <span style="font-size: 0.75rem; font-weight: 600; color: #475569;">82. Reviewing officer / approving authority</span>
            <button type="button" id="btn-toggle-reviewing"
                style="font-size: 0.75rem; font-weight: 600; color: #0284c7; background: none; border: none; cursor: pointer; text-decoration: underline;">
                Remove
            </button>
        </div>
        <input type="text" name="reviewing_officer_name" value="{{ old('reviewing_officer_name', $data['reviewing_officer_name'] ?? '') }}"
            placeholder="Reviewing officer name"
            style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 0.875rem; color: var(--text-main); background: rgba(255, 255, 255, 0.4);">
    </div>
    @unless($reviewingOn)
        <button type="button" id="btn-add-reviewing"
            style="margin-top: 8px; font-size: 0.75rem; font-weight: 600; color: #0284c7; background: none; border: none; cursor: pointer; text-decoration: underline;">
            + Add reviewing officer
        </button>
    @endunless
</div>

<script>
(function () {
    document.querySelectorAll('.btn-toggle-specialty').forEach((btn) => {
        btn.addEventListener('click', () => {
            const slot = btn.closest('.specialty-slot');
            const input = slot.querySelector('.specialty-enabled-input');
            const wrap = slot.querySelector('.specialty-select-wrap');
            const on = input.value !== '1';
            input.value = on ? '1' : '0';
            wrap.classList.toggle('hidden', !on);
            btn.textContent = on ? 'Remove' : 'Assign';
            if (!on) {
                wrap.querySelector('input[type=text]').value = '';
            }
        });
    });

    const reviewingInput = document.getElementById('reviewing_officer_enabled');
    const reviewingBlock = document.getElementById('reviewing-block');
    const toggleReviewing = () => {
        const on = reviewingInput.value !== '1';
        reviewingInput.value = on ? '1' : '0';
        reviewingBlock.style.display = on ? 'block' : 'none';
        if (!on) {
            reviewingBlock.querySelector('input[type=text]').value = '';
        }
    };
    document.getElementById('btn-toggle-reviewing')?.addEventListener('click', toggleReviewing);
    document.getElementById('btn-add-reviewing')?.addEventListener('click', () => {
        reviewingBlock.style.display = 'block';
        reviewingInput.value = '1';
        document.getElementById('btn-add-reviewing')?.remove();
    });
})();
</script>
