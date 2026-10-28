{{-- Chart.js genérico. Params: $id, $config (type/data/options de Chart.js), $alto (px, 260).
     Opciones extra en $config['options']['plugins']:
       centro: ['valor' => '569,439', 'texto' => 'PÁGINAS'] → texto al centro de una dona
       etiquetas: true → muestra el valor sobre cada punto/barra --}}


<div wire:ignore x-data="{
        chart: null,
        init() {
            const cfg = @js($config);
            cfg.plugins = window.senabedChartPlugins;
            cfg.options = Object.assign({ responsive: true, maintainAspectRatio: false }, cfg.options || {});
            Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';
            Chart.defaults.color = '#64748b';
            this.chart = new Chart(this.$refs.cv, cfg);
        },
        destroy() { this.chart?.destroy(); },
     }" class="relative" style="height: {{ $alto ?? 260 }}px;">
    <canvas x-ref="cv"></canvas>
</div>
