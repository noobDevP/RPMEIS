@php
    $furthestStep = $furthestStep ?? $step;
    $totalSteps = $totalSteps ?? 3;
    $routeName = 'medical-form.step';
    $progressPercent = ($step / $totalSteps) * 100;
@endphp
<style>
    @keyframes step-progress-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    @keyframes step-progress-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.35); }
        50% { box-shadow: 0 0 12px 2px rgba(34, 197, 94, 0.45); }
    }
    .step-progress-fill {
        background: linear-gradient(
            90deg,
            #14b8a6 0%,
            #22c55e 25%,
            #4ade80 50%,
            #22c55e 75%,
            #14b8a6 100%
        );
        background-size: 200% 100%;
        animation: step-progress-shimmer 2.5s linear infinite, step-progress-pulse 2s ease-in-out infinite;
        transition: width 0.65s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .step-progress-track {
        background: linear-gradient(90deg, #e2e8f0 0%, #f1f5f9 50%, #e2e8f0 100%);
        background-size: 200% 100%;
        animation: step-progress-shimmer 4s linear infinite;
    }
</style>

<nav style="margin-bottom: 24px;" aria-label="Form progress">
    <ol style="list-style: none; padding: 0; margin: 0 0 12px 0; display: flex; justify-content: space-between; gap: 4px;">
        @for($i = 1; $i <= $totalSteps; $i++)
            @php
                $isCurrent = $i === $step;
                $isReachable = $i <= $furthestStep;
                $isCompleted = $i < $furthestStep && ! $isCurrent;
            @endphp
            <li style="flex: 1; text-align: center; min-width: 0;">
                @if($isReachable && ! $isCurrent)
                    <button type="submit" formaction="{{ route('medical-form.step.save', ['medical_form_id' => $form->examination_id, 'step' => $step]) }}" name="target_step" value="{{ $i }}"
                       style="display: inline-flex; flex-direction: column; align-items: center; gap: 4px; width: 100%; border-radius: 8px; padding: 6px 4px; font-size: 12px; font-weight: 600; color: #92400e; text-decoration: none; border: none; background: none; cursor: pointer;"
                       title="Go to step {{ $i }}"
                       onmouseover="this.style.backgroundColor='#fffbeb'"
                       onmouseout="this.style.backgroundColor=''">
                        <span style="display: flex; height: 28px; width: 28px; align-items: center; justify-content: center; border-radius: 50%; border: 2px solid #92400e; background-color: #fffbeb; color: #92400e;">
                            @if($isCompleted)
                                <svg style="height: 14px; width: 14px;" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                {{ $i }}
                            @endif
                        </span>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%;">Step {{ $i }}</span>
                    </button>
                @elseif($isCurrent)
                    <span style="display: inline-flex; flex-direction: column; align-items: center; gap: 4px; width: 100%; padding: 6px 4px; font-size: 12px; font-weight: 700; color: #78350f;"
                          aria-current="step">
                        <span style="display: flex; height: 28px; width: 28px; align-items: center; justify-content: center; border-radius: 50%; border: 2px solid #b45309; background-color: #b45309; color: white; box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.3); transform: scale(1.1);">
                            {{ $i }}
                        </span>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%;">Step {{ $i }}</span>
                    </span>
                @else
                    <span style="display: inline-flex; flex-direction: column; align-items: center; gap: 4px; width: 100%; padding: 6px 4px; font-size: 12px; font-weight: 500; cursor: not-allowed; user-select: none; color: #94a3b8;"
                          title="Complete earlier steps first">
                        <span style="display: flex; height: 28px; width: 28px; align-items: center; justify-content: center; border-radius: 50%; border: 2px solid #e2e8f0; background-color: #f8fafc; color: #94a3b8;">
                            {{ $i }}
                        </span>
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%;">Step {{ $i }}</span>
                    </span>
                @endif
            </li>
        @endfor
    </ol>
    <div class="step-progress-track" style="height: 10px; border-radius: 999px; overflow: hidden;">
        <div class="step-progress-fill" style="height: 100%; border-radius: 999px; width: {{ $progressPercent }}%"></div>
    </div>
    <p style="margin-top: 8px; text-align: center; font-size: 11px; color: var(--text-muted);">
        @if($furthestStep < $totalSteps)
            Complete each step with <strong>Next</strong> to unlock later steps.
        @else
            You can click any step above to review or edit.
        @endif
    </p>
</nav>

