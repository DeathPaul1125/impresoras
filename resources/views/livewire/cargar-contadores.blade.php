<div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Carga de Contadores de Impresión</h1>

    @if (session('ok'))
        <div class="mb-4 rounded-lg bg-teal-50 border border-teal-200 p-3 text-teal-800 text-sm">
            {{ session('ok') }}
        </div>
    @endif

    @php $ctrl = 'py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500'; @endphp
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mb-8">
        {{-- Opciones + archivos --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Delimitador CSV</label>
                <select wire:model="delimitador" class="{{ $ctrl }}">
                    @foreach ($delimitadores as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Codificación</label>
                <select wire:model="codificacion" class="{{ $ctrl }}">
                    @foreach ($codificaciones as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="tieneEncabezado" class="shrink-0 mt-0.5 border-gray-200 rounded text-blue-600 focus:ring-blue-500">
                    El archivo contiene encabezados
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Archivos CSV</label>
            <input type="file" wire:model="files" multiple accept=".csv,.txt"
                   class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <div wire:loading wire:target="files" class="text-xs text-gray-500 mt-1">Subiendo...</div>
            @error('files') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            @error('files.*') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <x-primary-button wire:click="leerEncabezados" wire:loading.attr="disabled" type="button">
            Leer encabezados
        </x-primary-button>

        {{-- Mapeo de columnas --}}
        @if (! empty($headers))
            <div class="mt-6 border-t border-gray-100 pt-4">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Selecciona las columnas a importar</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ($campos as $campo => $etiqueta)
                        <div class="flex items-center gap-2">
                            <label class="w-40 text-sm text-gray-700">{{ $etiqueta }}</label>
                            <select wire:model="mapeo.{{ $campo }}" class="flex-1 py-2 px-3 border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">— No importar —</option>
                                @foreach ($headers as $i => $h)
                                    <option value="{{ $i }}">{{ $h !== '' ? $h : 'Columna '.($i + 1) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('mapeo.'.$campo) <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
                    @endforeach
                </div>

                <div class="mt-5 flex gap-2">
                    <x-primary-button wire:click="procesar" wire:loading.attr="disabled" type="button">
                        Procesar archivos
                    </x-primary-button>
                    <span wire:loading wire:target="procesar" class="text-sm text-gray-500 self-center">Procesando...</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Historial --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <h2 class="text-lg font-semibold text-gray-800 px-6 py-4 border-b border-gray-200">Historial de cargas</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Archivo</th>
                        <th class="px-4 py-2 text-right">Registros</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                        <th class="px-4 py-2 text-left">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($historial as $h)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $h->archivo }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($h->registros) }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-0.5 rounded text-xs {{ $h->estado === 'procesado' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ ucfirst($h->estado) }}
                                </span>
                            </td>
                            <td class="px-4 py-2">{{ $h->usuario?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">Aún no hay cargas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
