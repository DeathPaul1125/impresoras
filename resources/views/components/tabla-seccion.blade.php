@props(['titulo', 'export' => null, 'pdf' => null])

<div class="flex flex-col bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-8">
    <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-b border-gray-100">
        <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2">
            <span class="inline-block w-1.5 h-4 rounded-full bg-blue-500"></span>
            {{ $titulo }}
        </h2>
        <div class="flex gap-2">
            @if ($export)
                <a href="{{ $export }}"
                   class="py-1.5 px-3 inline-flex items-center gap-x-1.5 text-xs font-medium rounded-lg border border-transparent bg-teal-600 text-white hover:bg-teal-700 focus:outline-none">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            @endif
            @if ($pdf)
                <a href="{{ $pdf }}"
                   class="py-1.5 px-3 inline-flex items-center gap-x-1.5 text-xs font-medium rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-none">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            @endif
        </div>
    </div>
    <div class="overflow-x-auto">
        <div class="min-w-full inline-block align-middle">
            {{ $slot }}
        </div>
    </div>
</div>
