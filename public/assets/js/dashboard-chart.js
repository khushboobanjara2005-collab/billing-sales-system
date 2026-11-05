const chart = document.getElementById('sales-chart');
if (chart && Array.isArray(window.dashboardSales)) {
    const values = window.dashboardSales.map((item) => Number(item.sales));
    const labels = window.dashboardSales.map((item) => item.label);
    const drawChart = () => {
        const context = chart.getContext('2d');
        const width = chart.clientWidth;
        const height = 250;
        const ratio = window.devicePixelRatio || 1;
        chart.width = width * ratio;
        chart.height = height * ratio;
        context.scale(ratio, ratio);
        const margin = { top: 16, right: 12, bottom: 42, left: 58 };
        const plotWidth = width - margin.left - margin.right;
        const plotHeight = height - margin.top - margin.bottom;
        const maximum = Math.max(...values, 1);
        const slot = plotWidth / values.length;
        context.font = '12px Segoe UI, Arial, sans-serif';
        context.textAlign = 'right';
        context.textBaseline = 'middle';
        context.fillStyle = '#526575';
        for (let tick = 0; tick <= 4; tick += 1) {
            const value = maximum * tick / 4;
            const y = margin.top + plotHeight - plotHeight * tick / 4;
            context.strokeStyle = '#dce5eb';
            context.beginPath();
            context.moveTo(margin.left, y);
            context.lineTo(width - margin.right, y);
            context.stroke();
            context.fillText(`₹${value.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`, margin.left - 8, y);
        }
        values.forEach((value, index) => {
            const barHeight = plotHeight * value / maximum;
            const barWidth = slot * 0.58;
            const x = margin.left + index * slot + (slot - barWidth) / 2;
            const y = margin.top + plotHeight - barHeight;
            context.fillStyle = '#08796b';
            context.fillRect(x, y, barWidth, barHeight);
            context.fillStyle = '#344c5c';
            context.textAlign = 'center';
            context.textBaseline = 'top';
            context.fillText(labels[index], x + barWidth / 2, height - margin.bottom + 10);
        });
    };
    drawChart();
    window.addEventListener('resize', drawChart);
}
