{{-- Chart.js genérico. Params: $id, $config (type/data/options de Chart.js), $alto (px, 260).
     Opciones extra en $config['options']['plugins']:
       centro: ['valor' => '569,439', 'texto' => 'PÁGINAS'] → texto al centro de una dona
       etiquetas: true → muestra el valor sobre cada punto/barra (un dataset con 'sinEtiquetas' => true se omite)
     $click (opcional): ['accion' => 'metodoLivewire', 'valores' => [...]] → al hacer clic en el elemento i llama metodo(valores[i]).
     El wire:key cambia con la configuración, así la gráfica se vuelve a dibujar cuando cambia la selección. --}}


<div wire:ignore wire:key="chart-{{ md5(json_encode([$config, $click ?? null])) }}" x-data="{
        chart: null,
        init() {
            const cfg = @js($config);
            cfg.plugins = window.senabedChartPlugins;
            cfg.options = Object.assign({ responsive: true, maintainAspectRatio: false }, cfg.options || {});
            const click = @js($click ?? null);
            if (click) {
                cfg.options.onClick = (e, els) => { if (els.length) $wire.call(click.accion, click.valores[els[0].index]); };
                cfg.options.onHover = (e, els) => { e.native.target.style.cursor = els.length ? 'pointer' : 'default'; };
            }
            Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';
            Chart.defaults.color = '#64748b';
            this.chart = new Chart(this.$refs.cv, cfg);
        },
        destroy() { this.chart?.destroy(); },
     }" class="relative" style="height: {{ $alto ?? 260 }}px;">
    <canvas x-ref="cv"></canvas>
</div>
