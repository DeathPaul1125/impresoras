{{-- Chart.js genérico. Params: $id, $config (type/data/options de Chart.js), $alto (px, 260).
     Opciones extra en $config['options']['plugins']:
       centro: ['valor' => '569,439', 'texto' => 'PÁGINAS'] → texto al centro de una dona
       etiquetas: true → muestra el valor sobre cada punto/barra --}}
@once
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
        <script>
            window.senabedChartPlugins = [{
                id: 'centro',
                afterDraw(chart, args, o) {
                    if (!o || !o.valor) return;
                    const { ctx, chartArea: a } = chart, x = (a.left + a.right) / 2, y = (a.top + a.bottom) / 2;
                    ctx.save(); ctx.textAlign = 'center'; ctx.fillStyle = '#0f172a';
                    ctx.font = '700 22px Figtree, sans-serif'; ctx.fillText(o.valor, x, y + 2);
                    ctx.font = '600 10px Figtree, sans-serif'; ctx.fillStyle = '#475569';
                    (o.texto || '').split('\n').forEach((t, i) => ctx.fillText(t, x, y + 18 + i * 12));
                    ctx.restore();
                },
            }, {
                id: 'etiquetas',
                afterDatasetsDraw(chart, args, o) {
                    if (!o) return;
                    const { ctx } = chart;
                    ctx.save(); ctx.font = '700 11px Figtree, sans-serif'; ctx.textAlign = 'center';
                    chart.data.datasets.forEach((ds, i) => chart.getDatasetMeta(i).data.forEach((el, j) => {
                        const v = ds.data[j];
                        if (v === null || v === undefined) return;
                        if (chart.config.type === 'doughnut') {
                            const total = ds.data.reduce((s, n) => s + (n || 0), 0), pct = total ? v / total * 100 : 0;
                            if (pct < 3) return;
                            const p = el.tooltipPosition(); ctx.fillStyle = '#fff'; ctx.fillText(pct.toFixed(1) + '%', p.x, p.y + 4);
                        } else {
                            ctx.fillStyle = ds.borderColor || '#1e3a8a'; ctx.fillText(Number(v).toLocaleString('es-GT'), el.x, el.y - 10);
                        }
                    }));
                    ctx.restore();
                },
            }];
        </script>
    @endpush
@endonce

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
