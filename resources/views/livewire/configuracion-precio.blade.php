<div class="py-8 max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Configuración</h1>

    @if (session('ok'))
        <div class="mb-4 rounded-lg bg-teal-50 border border-teal-200 p-3 text-teal-800 text-sm">
            {{ session('ok') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-red-800 text-sm">{{ session('error') }}</div>
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

    {{-- Cuenta que envía --}}
    @php $ctrl = 'py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500'; @endphp
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mt-6">
        <h2 class="block text-sm font-medium text-gray-700">Cuenta que envía los correos (SMTP)</h2>
        <p class="text-xs text-gray-500 mb-4">Datos del servidor de correo institucional. Office 365: smtp.office365.com, puerto 587. Gmail: smtp.gmail.com, puerto 587 con contraseña de aplicación.</p>

        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
                <label class="block text-xs text-gray-600 mb-1">Servidor *</label>
                <input type="text" wire:model="smtp_host" placeholder="smtp.office365.com" class="{{ $ctrl }}">
                @error('smtp_host') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Puerto *</label>
                <input type="number" wire:model="smtp_port" class="{{ $ctrl }}">
                @error('smtp_port') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model="smtp_ssl" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            Usar SSL (puerto 465). Desmarcado usa STARTTLS (587) si el servidor lo ofrece.
        </label>

        <div class="grid grid-cols-2 gap-3 mt-3">
            <div>
                <label class="block text-xs text-gray-600 mb-1">Usuario</label>
                <input type="text" wire:model="smtp_username" autocomplete="off" placeholder="informatica@senabed.gob.gt" class="{{ $ctrl }}">
                @error('smtp_username') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Contraseña</label>
                <input type="password" wire:model="smtp_password" autocomplete="new-password"
                       placeholder="{{ $smtp_tiene_password ? '•••••••• (guardada, dejar vacío para no cambiar)' : '' }}" class="{{ $ctrl }}">
                @error('smtp_password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Correo remitente</label>
                <input type="email" wire:model="smtp_from_address" placeholder="Igual que el usuario" class="{{ $ctrl }}">
                @error('smtp_from_address') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">Nombre remitente</label>
                <input type="text" wire:model="smtp_from_name" placeholder="Impresoras SENABED" class="{{ $ctrl }}">
            </div>
        </div>

        <div class="mt-5 flex gap-2">
            <x-primary-button wire:click="guardarSmtp" type="button">Guardar cuenta</x-primary-button>
            <x-secondary-button wire:click="probarCorreo" wire:loading.attr="disabled" wire:target="probarCorreo" type="button">
                <i class="fas fa-paper-plane me-2" wire:loading.class="fa-beat" wire:target="probarCorreo"></i> Enviar prueba
            </x-secondary-button>
        </div>
    </div>
</div>
