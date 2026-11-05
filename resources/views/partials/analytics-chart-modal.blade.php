<link rel="stylesheet" href="{{ asset('css/analytics-chart-modal.css') }}">

<div id="globalChartModal" data-batches-url="{{ route('api.analytics.batches') }}" data-chart-url="{{ route('api.analytics.chart') }}">
    <div class="chart-modal-content">
        <div class="chart-modal-header">
            <h3 id="chartModalTitle">System Analytics Overview</h3>
            <span class="chart-close">&times;</span>
        </div>
        
        <div style="margin-bottom: 20px; display: flex; justify-content: flex-end; align-items: center; gap: 10px;">
            <label for="chartStageSelect" style="font-weight: 600; font-size: 0.9rem; color: #475569;">Stage:</label>
            <select id="chartStageSelect" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; font-family: inherit; font-size: 0.9rem;">
                <option value="station">Station Breakdown</option>
                <option value="overall">Overall Status</option>
            </select>

            <label for="chartBatchSelect" style="font-weight: 600; font-size: 0.9rem; color: #475569; margin-left: 15px;">Filter Batch:</label>
            <select id="chartBatchSelect" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; font-family: inherit; font-size: 0.9rem;">
                <option value="All">All Batches</option>
            </select>
        </div>

        <div class="chart-container">
            <canvas id="globalChartCanvas"></canvas>
        </div>
        
        <div style="margin-top: 20px; text-align: right;">
            <button type="button" class="btn btn-secondary" id="closeAnalyticsChartBtn" style="padding: 8px 24px; border-radius: 8px; font-weight: 600; background: #64748b; color: white; border: none; cursor: pointer;">Close</button>
        </div>
    </div>
</div>

<!-- Load Chart.js and Datalabels plugin -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="{{ asset('js/analytics-chart-modal.js') }}" defer></script>
