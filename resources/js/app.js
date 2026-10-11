import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Load Chart.js only on pages that actually render a chart, keeping the main bundle small.
const chartCanvases = document.querySelectorAll('canvas[data-chart]');
if (chartCanvases.length) {
    import('chart.js/auto').then(({ default: Chart }) => {
        chartCanvases.forEach((canvas) => {
            const config = JSON.parse(canvas.dataset.chart);
            new Chart(canvas, config);
        });
    });
}

// Ask for confirmation on destructive forms: <form data-confirm="Are you sure?">
document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
    }
    // Prevent accidental double submits.
    event.target.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
        button.disabled = true;
    });
});

// Re-enable buttons when the page is restored from the back/forward cache.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('button[disabled]').forEach((button) => {
        button.disabled = false;
    });
});
