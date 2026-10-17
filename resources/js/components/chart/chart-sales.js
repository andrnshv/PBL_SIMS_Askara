export const initChartSales = () => {
    const chartElement = document.querySelector('#chartSales');
    if (!chartElement) return;

    // Data dikirim dari controller lewat atribut data-labels dan data-values
    const labels = JSON.parse(chartElement.dataset.labels || '[]');
    const values = JSON.parse(chartElement.dataset.values || '[]');
    const maxValue = Math.max(0, ...values);

    const options = {
        series: [{ name: 'penjualan', data: values }],
        chart: {
            type: 'area',
            height: 260,
            fontFamily: 'Outfit, sans-serif',
            toolbar: { show: false },
        },
        colors: ['#465FFF'],
        stroke: { curve: 'straight', width: 2 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0, stops: [0, 100] },
        },
        markers: { size: 4, strokeWidth: 0 },
        dataLabels: { enabled: false },
        xaxis: {
            categories: labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            min: 0,
            max: maxValue === 0 ? 5 : undefined,
            forceNiceScale: true,
            labels: { formatter: (value) => Math.round(value) },
        },
        tooltip: { y: { formatter: (value) => value + ' penjualan' } },
    };

    const chart = new ApexCharts(chartElement, options);
    chart.render();
    return chart;
};

export default initChartSales;
