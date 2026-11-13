<button type="button"
    class="filter-toggle-btn"
    data-filter-toggle
    data-filter-target="{{ $targetId }}"
    data-filter-storage="{{ $storageKey ?? $targetId }}"
    aria-controls="{{ $targetId }}"
    aria-expanded="false">
    <i class="fas fa-sliders-h"></i>
    <span data-filter-toggle-label>Filters</span>
    <span class="filter-toggle-badge" data-filter-count>0</span>
    <i class="fas fa-chevron-down filter-toggle-chevron"></i>
</button>
