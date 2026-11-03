@php
    $isEditMode = isset($form) && $form->isEditInProgress();
@endphp

<div style="margin-top: 24px; display: flex; flex-wrap: wrap; gap: 12px; border-top: 1px solid var(--border-color); padding-top: 20px;">
    <a href="{{ route('doctor.show', ['station' => 'gpe']) }}"
        style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; border: 1px solid var(--border-color); border-radius: var(--radius); font-weight: 500; font-size: 0.85rem; cursor: pointer; background: rgba(255, 255, 255, 0.4); color: var(--text-muted); text-decoration: none; transition: all 0.2s;">
        &larr; Cancel
    </a>
    @if($step === 1)
        <button type="submit" name="direction" value="next"
            style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 24px; border: none; border-radius: var(--radius); font-weight: 500; font-size: 0.9rem; cursor: pointer; background: var(--primary-color); color: white; transition: background 0.2s;">
            Next &rarr;
        </button>
        @if($isEditMode)
            <button type="submit" name="direction" value="submit"
                style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 24px; border: none; border-radius: var(--radius); font-weight: 500; font-size: 0.9rem; cursor: pointer; background: #10b981; color: white; transition: background 0.2s;">
                Submit Form
            </button>
        @endif
    @elseif($step === 2)
        <button type="submit" name="direction" value="previous"
            style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 24px; border: 1px solid var(--border-color); border-radius: var(--radius); font-weight: 500; font-size: 0.9rem; cursor: pointer; background: rgba(255, 255, 255, 0.4); color: var(--text-main); transition: all 0.2s;">
            &larr; Previous
        </button>
        <button type="submit" name="direction" value="next"
            style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 24px; border: none; border-radius: var(--radius); font-weight: 500; font-size: 0.9rem; cursor: pointer; background: var(--primary-color); color: white; transition: background 0.2s;">
            Next &rarr;
        </button>
        @if($isEditMode)
            <button type="submit" name="direction" value="submit"
                style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 24px; border: none; border-radius: var(--radius); font-weight: 500; font-size: 0.9rem; cursor: pointer; background: #10b981; color: white; transition: background 0.2s;">
                Submit Form
            </button>
        @endif
    @elseif($step === 3)
        <button type="submit" formaction="{{ route('medical-form.step.save', ['medical_form_id' => $form->examination_id, 'step' => 3]) }}" name="direction" value="previous"
            style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 24px; border: 1px solid var(--border-color); border-radius: var(--radius); font-weight: 500; font-size: 0.9rem; cursor: pointer; background: rgba(255, 255, 255, 0.4); color: var(--text-main); transition: all 0.2s;">
            &larr; Previous
        </button>
        <button type="submit" name="direction" value="submit"
            style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 24px; border: none; border-radius: var(--radius); font-weight: 500; font-size: 0.9rem; cursor: pointer; background: #10b981; color: white; transition: background 0.2s;">
            Submit Form
        </button>
    @endif
</div>

