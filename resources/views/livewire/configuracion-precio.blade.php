<div class="py-8 max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Configuración</h1>

    @if (session('ok'))
        <div class="mb-4 rounded-lg bg-teal-50 border border-teal-200 p-3 text-teal-800 text-sm">
            {{ session('ok') }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
        <label class="block text-sm font-medium text-gray-700">Precio unitario (Q por impresión + copia)</label>
        <p class="text-xs text-gray-500 mb-2">El costo se calcula como (impresiones + copias) × precio. Los escaneos no tienen costo.</p>
        <div class="flex items-center gap-2">
            <span class="text-gray-500">Q</span>
            <input type="number" step="0.01" min="0" wire:model="precio_unitario"
                   class="py-2 px-3 block w-40 border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        @error('precio_unitario') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror

        <div class="mt-5">
            <x-primary-button wire:click="guardar" type="button">Guardar</x-primary-button>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mt-6">
        <label class="block text-sm font-medium text-gray-700">Correos para reportes</label>
        <p class="text-xs text-gray-500 mb-2">Separados por coma. Reciben el consumo por impresora: diario (6 pm), los viernes (lunes a viernes) y el último día del mes.</p>
        <textarea wire:model="correos_reportes" rows="2" placeholder="informatica@senabed.gob.gt, ..."
                  class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        @error('correos_reportes') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror

        <div class="mt-5">
            <x-primary-button wire:click="guardarCorreos" type="button">Guardar correos</x-primary-button>
        </div>
    </div>
</div>
