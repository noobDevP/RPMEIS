let globalChartInstance = null;
let chartBatchesLoaded = false;
let currentChartData = null;

async function loadChartBatches() {
    if (chartBatchesLoaded) return;
    try {
        const modal = document.getElementById('globalChartModal');
        const batchesUrl = modal?.dataset?.batchesUrl || '/analytics/batches';
        const response = await fetch(batchesUrl);
        const batches = await response.json();
        const select = document.getElementById('chartBatchSelect');
        if (!select) return;
        
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
    
    const stageSelect = document.getElementById('chartStageSelect');
    const stage = stageSelect ? stageSelect.value : 'station';
    const canvas = document.getElementById('globalChartCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
        
    if (globalChartInstance) {
        globalChartInstance.destroy();
    }

    if (window.ChartDataLabels && window.Chart) {
        Chart.register(ChartDataLabels);
    }

    let chartConfig = {};

    if (stage === 'station') {
        const sectionData = currentChartData.station || {};
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
        const overallData = currentChartData.overall || {};
        chartConfig = {
            type: 'bar',
            data: {
                labels: ['Overall Status'],
                datasets: [
                    {
                        label: 'CLEARED / PASSED',
                        data: [overallData.CLEARED || 0],
                        backgroundColor: 'rgba(34, 197, 94, 0.85)',
                        borderColor: 'rgb(22, 163, 74)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'DISQUALIFIED / FAILED',
                        data: [overallData.DISQUALIFIED || 0],
                        backgroundColor: 'rgba(239, 68, 68, 0.85)',
                        borderColor: 'rgb(220, 38, 38)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'COMPLETED / DONE',
                        data: [overallData.DONE || 0],
                        backgroundColor: 'rgba(59, 130, 246, 0.85)',
                        borderColor: 'rgb(37, 99, 235)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'ON GOING',
                        data: [overallData["ON GOING"] || 0],
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
    if (!modal) return;
    modal.style.display = 'block';
    
    if (batchSelect && batchSelect.value !== batch) {
        batchSelect.value = batch;
    }
    
    if (title) {
        title.innerHTML = batch === 'All' || !batch ? 'System Analytics Overview (All Batches)' : `System Analytics Overview - Batch: ${batch}`;
    }
    
    try {
        const chartUrl = modal.dataset?.chartUrl || '/analytics/chart';
        const response = await fetch(`${chartUrl}?batch=${encodeURIComponent(batch)}`);
        currentChartData = await response.json();
        
        renderAnalyticsChart();
    } catch (error) {
        console.error('Error fetching analytics chart data:', error);
        alert('Failed to load chart data.');
    }
}

function closeAnalyticsChart() {
    const modal = document.getElementById('globalChartModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const stageSelect = document.getElementById('chartStageSelect');
    if (stageSelect) {
        stageSelect.addEventListener('change', renderAnalyticsChart);
    }

    const batchSelect = document.getElementById('chartBatchSelect');
    if (batchSelect) {
        batchSelect.addEventListener('change', function() {
            openAnalyticsChart(this.value);
        });
    }

    const closeBtns = document.querySelectorAll('.chart-close, #closeAnalyticsChartBtn');
    closeBtns.forEach(btn => btn.addEventListener('click', closeAnalyticsChart));

    const modal = document.getElementById('globalChartModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAnalyticsChart();
            }
        });
    }

    // Global delegation for opening analytics
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('#showAnalyticsBtn, [data-action="show-analytics"]');
        if (trigger) {
            e.preventDefault();
            const legacyModal = document.getElementById('analyticsModal');
            if (legacyModal) {
                legacyModal.style.display = 'block';
                const catSelect = document.getElementById('analyticsCategorySelect');
                const batchSelect = document.getElementById('analyticsBatchSelect');
                if (catSelect && (!catSelect.value || catSelect.value === '')) {
                    catSelect.value = 'All';
                }
                if (batchSelect && (!batchSelect.value || batchSelect.value === '')) {
                    batchSelect.value = 'All';
                }
                const filterBtn = document.getElementById('filterAnalyticsBtn');
                if (filterBtn) {
                    filterBtn.click();
                }
            } else if (typeof openAnalyticsChart === 'function') {
                openAnalyticsChart('All');
            }
        }
    });
});

window.openAnalyticsChart = openAnalyticsChart;
window.closeAnalyticsChart = closeAnalyticsChart;
window.renderAnalyticsChart = renderAnalyticsChart;
