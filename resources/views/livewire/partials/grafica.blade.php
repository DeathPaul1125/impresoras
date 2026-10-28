{{-- Gráfica Chart.js reutilizable. Params: $key, $tipo, $labels, $datos, $label, $firma.
     Opcionales: $series = [['label' => .., 'data' => [..]], ...] (barras apiladas, una por serie);
     $moneda = false para mostrar cantidades en vez de Q.
     La clave wire:key incluye $firma para recrear el canvas cuando cambian los filtros. --}}
@php
    $series ??= null;
    $moneda ??= true;
    $datos ??= [];
@endphp
@once
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    @endpush
@endonce

<div wire:key="grafica-{{ $key }}-{{ $firma }}"
     x-data="{
        chart: null,
        paleta: ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16','#f97316','#14b8a6','#3b82f6','#a855f7'],
        init() {
            const tipo = @js($tipo);
            const series = @js($series);
            const moneda = @js($moneda);
            const horizontal = tipo === 'bar' && !series;
            const fmt = (v) => (moneda ? 'Q' : '') + Number(v).toLocaleString('es-GT', moneda ? { minimumFractionDigits: 2 } : {});
            Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';
            Chart.defaults.color = '#64748b';
            this.chart = new Chart(this.$refs.cv, {
                type: tipo,
                data: {
                    labels: @js($labels),
                    datasets: series ? series.map((s, i) => ({
                        label: s.label,
                        data: s.data,
                        backgroundColor: this.paleta[i % this.paleta.length],
                        borderRadius: 3,
                        maxBarThickness: 28,
                    })) : [{
                        label: @js($label),
                        data: @js($datos),
                        backgroundColor: tipo === 'line'
                            ? 'rgba(99,102,241,0.15)'
                            : (tipo === 'bar' ? '#6366f1' : this.paleta),
                        borderColor: tipo === 'line' ? '#6366f1' : (tipo === 'bar' ? '#6366f1' : '#fff'),
                        borderWidth: tipo === 'doughnut' ? 2 : (tipo === 'line' ? 2.5 : 0),
                        borderRadius: tipo === 'bar' ? 6 : 0,
                        maxBarThickness: 22,
                        fill: tipo === 'line',
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#6366f1',
                    }],
                },
                options: {
                    indexAxis: horizontal ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: 4 },
                    plugins: {
                        legend: {
                            display: tipo === 'doughnut' || !!series,
                            position: series ? 'bottom' : 'right',
                            labels: { boxWidth: 12, boxHeight: 12, padding: 10, font: { size: 11 } },
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (c) => ' ' + (series ? c.dataset.label + ': ' : '') + fmt(c.parsed[horizontal ? 'x' : 'y'] ?? c.parsed),
                            },
                        },
                    },
                    scales: tipo === 'doughnut' ? {} : {
                        x: { stacked: !!series, grid: { display: horizontal, color: '#f1f5f9' }, ticks: { font: { size: 10 } }, border: { display: false } },
                        y: { stacked: !!series, grid: { display: !horizontal, color: '#f1f5f9' }, ticks: { font: { size: 10 }, autoSkip: horizontal ? false : true }, border: { display: false } },
                    },
                    cutout: tipo === 'doughnut' ? '62%' : undefined,
                },
            });
        },
        destroy() { this.chart?.destroy(); },
     }"
     class="relative" style="height: 300px;">
    <canvas x-ref="cv"></canvas>
</div>
