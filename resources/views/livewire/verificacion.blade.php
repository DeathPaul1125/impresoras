@php $q = fn ($v) => 'Q'.number_format((float) $v, 2); @endphp

<div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Verificación de Importación</h1>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-8">
        <h2 class="text-lg font-semibold text-gray-800 px-6 py-4 border-b border-gray-200">Lotes importados</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Archivo</th>
                        <th class="px-4 py-2 text-right">Registros</th>
                        <th class="px-4 py-2 text-right">Costo Total</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($importaciones as $imp)
                        <tr class="hover:bg-gray-50 {{ $importacionId === $imp->id ? 'bg-indigo-50' : '' }}">
                            <td class="px-4 py-2">{{ \Illuminate\Support\Carbon::parse($imp->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $imp->archivo }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($imp->registros) }}</td>
                            <td class="px-4 py-2 text-right font-semibold">{{ $q($imp->costo_total) }}</td>
                            <td class="px-4 py-2 text-right">
                                <button wire:click="seleccionar({{ $imp->id }})" class="text-indigo-600 hover:underline text-xs">
                                    {{ $importacionId === $imp->id ? 'Ocultar' : 'Ver por depto' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">Aún no hay importaciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($importacionId && $desglose->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <h2 class="text-lg font-semibold text-gray-800 px-6 py-4 border-b border-gray-200">Desglose por departamento</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Depto</th>
                            <th class="px-4 py-2 text-right">Registros</th>
                            <th class="px-4 py-2 text-right">Costo Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($desglose as $d)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $d->depto ?? '—' }} @if($d->depto_nombre) — {{ $d->depto_nombre }} @endif</td>
                                <td class="px-4 py-2 text-right">{{ number_format($d->registros) }}</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ $q($d->costo_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
