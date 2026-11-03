<style>
    /* Chart modal specific styles */
    #globalChartModal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.75); /* Modern dark overlay */
        backdrop-filter: blur(4px);
        z-index: 9999;
    }
    
    .chart-modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #ffffff;
        border-radius: 16px;
        padding: 25px 30px;
        width: 90%;
        max-width: 850px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    
    .chart-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 15px;
    }

    .chart-modal-header h3 {
        margin: 0;
        color: #1e293b;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .chart-close {
        color: #94a3b8;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.2s;
        line-height: 1;
    }

    .chart-close:hover {
        color: #ef4444;
    }

    .chart-container {
        position: relative;
        height: 400px;
        width: 100%;
    }
</style>

<div id="globalChartModal">
    <div class="chart-modal-content">
        <div class="chart-modal-header">
            <h3 id="chartModalTitle" style="margin: 0; color: #1e293b; font-size: 1.25rem; font-weight: 700;">System Analytics Overview</h3>
            <span class="chart-close" onclick="closeAnalyticsChart()">&times;</span>
        </div>
        
        <div style="margin-bottom: 20px; display: flex; justify-content: flex-end; align-items: center; gap: 10px;">
            <label for="chartStageSelect" style="font-weight: 600; font-size: 0.9rem; color: #475569;">Stage:</label>
            <select id="chartStageSelect" onchange="renderAnalyticsChart()" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; font-family: inherit; font-size: 0.9rem;">
                <option value="station">Station Breakdown</option>
                <option value="overall">Overall Status</option>
            </select>

            <label for="chartBatchSelect" style="font-weight: 600; font-size: 0.9rem; color: #475569; margin-left: 15px;">Filter Batch:</label>
            <select id="chartBatchSelect" onchange="openAnalyticsChart(this.value)" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; font-family: inherit; font-size: 0.9rem;">
                <option value="All">All Batches</option>
            </select>
        </div>

        <div class="chart-container">
            <canvas id="globalChartCanvas"></canvas>
        </div>
        
        <div style="margin-top: 20px; text-align: right;">
            <button type="button" class="btn btn-secondary" onclick="closeAnalyticsChart()" style="padding: 8px 24px; border-radius: 8px; font-weight: 600; background: #64748b; color: white; border: none; cursor: pointer;">Close</button>
        </div>
    </div>
</div>

<!-- Load Chart.js and Datalabels plugin -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
    let globalChartInstance = null;
    let chartBatchesLoaded = false;
    let currentChartData = null;

    async function loadChartBatches() {
        if (chartBatchesLoaded) return;
        try {
            const response = await fetch('{{ route("api.analytics.batches") }}');
            const batches = await response.json();
            const select = document.getElementById('chartBatchSelect');
            
            batches.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b;
                opt.textContent = b;
                select.appendChild(opt);
            });
            chartBatchesLoaded = true;
        } catch (e) {
            console.error('Error loading chart batches:', e);
        }
    }

    function renderAnalyticsChart() {
        if (!currentChartData) return;
        
        const stage = document.getElementById('chartStageSelect').value;
        const ctx = document.getElementById('globalChartCanvas').getContext('2d');
            
        if (globalChartInstance) {
            globalChartInstance.destroy();
        }

        Chart.register(ChartDataLabels);

        let chartConfig = {};

        if (stage === 'station') {
            const sectionData = currentChartData.station;
            const labels = Object.keys(sectionData).map(k => k.toUpperCase());
            const complianceData = Object.values(sectionData).map(d => d["COMPLIANCE"] || 0);
            const noGoData = Object.values(sectionData).map(d => d["NO GO"] || 0);
            const goData = Object.values(sectionData).map(d => d["GO"] || 0);
            
            chartConfig = {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'DQ / NO GO',
                            data: noGoData,
                            backgroundColor: 'rgba(239, 68, 68, 0.85)',
                            borderColor: 'rgb(220, 38, 38)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'GO',
                            data: goData,
                            backgroundColor: 'rgba(34, 197, 94, 0.85)',
                            borderColor: 'rgb(22, 163, 74)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'COMPLIANCE',
                            data: complianceData,
                            backgroundColor: 'rgba(234, 179, 8, 0.85)',
                            borderColor: 'rgb(202, 138, 4)',
                            borderWidth: 1,
                            borderRadius: 4
                        }
                    ]
                }
            };
        } else {
            const overallData = currentChartData.overall;
            chartConfig = {
                type: 'bar',
                data: {
                    labels: ['Overall Status'],
                    datasets: [
                        {
                            label: 'CLEARED / PASSED',
                            data: [overallData.CLEARED],
                            backgroundColor: 'rgba(34, 197, 94, 0.85)',
                            borderColor: 'rgb(22, 163, 74)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'DISQUALIFIED / FAILED',
                            data: [overallData.DISQUALIFIED],
                            backgroundColor: 'rgba(239, 68, 68, 0.85)',
                            borderColor: 'rgb(220, 38, 38)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'COMPLETED / DONE',
                            data: [overallData.DONE],
                            backgroundColor: 'rgba(59, 130, 246, 0.85)', // Blue for done
                            borderColor: 'rgb(37, 99, 235)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'ON GOING',
                            data: [overallData["ON GOING"]],
                            backgroundColor: 'rgba(234, 179, 8, 0.85)',
                            borderColor: 'rgb(202, 138, 4)',
                            borderWidth: 1,
                            borderRadius: 4
                        }
                    ]
                }
            };
        }

        chartConfig.options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'top',
                    labels: {
                        font: { family: "'Inter', sans-serif", weight: '600' },
                        usePointStyle: true,
                        padding: 20
                    }
                },
                datalabels: {
                    color: '#ffffff',
                    font: { weight: 'bold', size: 13, family: "'Inter', sans-serif" },
                    formatter: value => value === 0 ? '' : value,
                    anchor: 'center',
                    align: 'center'
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Inter', sans-serif", weight: '500' } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { stepSize: 1, font: { family: "'Inter', sans-serif" } }
                }
            },
            interaction: {
                mode: 'index',
                intersect: false,
            }
        };

        globalChartInstance = new Chart(ctx, chartConfig);
    }

    async function openAnalyticsChart(batch = 'All') {
        await loadChartBatches();
        
        const modal = document.getElementById('globalChartModal');
        const title = document.getElementById('chartModalTitle');
        const batchSelect = document.getElementById('chartBatchSelect');
        modal.style.display = 'block';
        
        if (batchSelect.value !== batch) {
            batchSelect.value = batch;
        }
        
        title.innerHTML = batch === 'All' || !batch ? 'System Analytics Overview (All Batches)' : `System Analytics Overview - Batch: ${batch}`;
        
        try {
            const response = await fetch(`{{ route('api.analytics.chart') }}?batch=${encodeURIComponent(batch)}`);
            currentChartData = await response.json();
            
            renderAnalyticsChart();
        } catch (error) {
            console.error('Error fetching analytics chart data:', error);
            alert('Failed to load chart data.');
        }
    }

    function closeAnalyticsChart() {
        document.getElementById('globalChartModal').style.display = 'none';
    }

    // Close on backdrop click
    document.getElementById('globalChartModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAnalyticsChart();
        }
    });
</script>
