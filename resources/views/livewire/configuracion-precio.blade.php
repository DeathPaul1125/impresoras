@php
    $ctrl = 'block w-full py-2 px-3 rounded-lg border-0 bg-papel ring-1 ring-inset ring-linea text-sm text-tinta placeholder:text-tinta-4 focus:ring-2 focus:ring-marino-600 cifra';
    $seccion = 'grid grid-cols-1 lg:grid-cols-3 gap-x-10 gap-y-4 py-8 border-t border-linea first:border-t-0 first:pt-2';
@endphp

<div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-2">
        <h1 class="text-2xl font-semibold tracking-tight text-tinta">Configuración</h1>
        <p class="text-sm text-tinta-3">Contrato con el proveedor, costo por página, destinatarios de los reportes y la cuenta que los envía.</p>
    </div>

    @if (session('ok'))
        <div class="mt-4 rounded-lg bg-saldo-50 ring-1 ring-inset ring-saldo/20 px-4 py-3 text-sm text-saldo flex gap-2"><i class="fas fa-circle-check mt-0.5"></i>{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-lg bg-red-50 ring-1 ring-inset ring-red-200 px-4 py-3 text-sm text-red-700 flex gap-2"><i class="fas fa-circle-exclamation mt-0.5"></i>{{ session('error') }}</div>
    @endif

    <div class="mt-6">
        {{-- Contrato --}}
        <div class="{{ $seccion }}">
            <div>
                <h2 class="text-sm font-semibold text-tinta">Contrato</h2>
                <p class="text-sm text-tinta-3 mt-1">El bolsón es el total de páginas B/N contratadas. El corte mensual se registra solo el día indicado a las 00:00 y con él se calcula lo producido de cada mes.</p>
            </div>
            <x-panel class="lg:col-span-2">
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                    <x-campo label="Número de contrato" nombre="contrato_numero" class="md:col-span-2">
                        <input type="text" wire:model="contrato_numero" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Bolsón B/N (páginas)" nombre="contrato_bolson" class="md:col-span-2">
                        <input type="number" min="1" wire:model="contrato_bolson" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Día de corte" nombre="contrato_dia_corte" ayuda="1 a 28" class="md:col-span-2">
                        <input type="number" min="1" max="28" wire:model="contrato_dia_corte" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Inicio de vigencia" nombre="contrato_inicio" ayuda="Fecha del contador inicial" class="md:col-span-3">
                        <input type="date" wire:model="contrato_inicio" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Fin de vigencia" nombre="contrato_fin" class="md:col-span-3">
                        <input type="date" wire:model="contrato_fin" class="{{ $ctrl }}">
                    </x-campo>
                </div>
                <div class="mt-5 flex justify-end">
                    <x-primary-button wire:click="guardarContrato" type="button">Guardar contrato</x-primary-button>
                </div>
            </x-panel>
        </div>

        {{-- Costo --}}
        <div class="{{ $seccion }}">
            <div>
                <h2 class="text-sm font-semibold text-tinta">Costo por página</h2>
                <p class="text-sm text-tinta-3 mt-1">Costo = (impresiones + copias) × precio unitario. Los escaneos no tienen costo.</p>
            </div>
            <x-panel class="lg:col-span-2">
                <div class="flex flex-wrap items-end gap-4">
                    <x-campo label="Precio unitario" nombre="precio_unitario" class="w-48">
                        <div class="relative">
                            <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-sm text-tinta-3">Q</span>
                            <input type="number" step="0.01" min="0" wire:model="precio_unitario" class="{{ $ctrl }} ps-7">
                        </div>
                    </x-campo>
                    <p class="text-xs text-tinta-3 pb-2">Ejemplo: 1,000 páginas = <b class="text-tinta cifra">Q{{ number_format(1000 * $precio_unitario, 2) }}</b></p>
                    <x-primary-button wire:click="guardar" type="button" class="ms-auto">Guardar precio</x-primary-button>
                </div>
            </x-panel>
        </div>

        {{-- Destinatarios --}}
        <div class="{{ $seccion }}">
            <div>
                <h2 class="text-sm font-semibold text-tinta">Reportes por correo</h2>
                <p class="text-sm text-tinta-3 mt-1">Quiénes reciben los reportes automáticos.</p>
                <ul class="mt-3 space-y-1.5 text-xs text-tinta-2">
                    <li><i class="fas fa-sun w-4 text-tinta-4"></i> Diario · 6:15 pm</li>
                    <li><i class="fas fa-calendar-week w-4 text-tinta-4"></i> Viernes · 6:15 pm (lunes a viernes)</li>
                    <li><i class="fas fa-scissors w-4 text-tinta-4"></i> Corte · día {{ $contrato_dia_corte }}, 00:10 (con Excel y PDF)</li>
                </ul>
            </div>
            <x-panel class="lg:col-span-2">
                <x-campo label="Destinatarios" nombre="correos_reportes" ayuda="Separados por coma. Si queda vacío no se envía ningún reporte.">
                    <textarea wire:model="correos_reportes" rows="3" placeholder="informatica@senabed.gob.gt, direccion@senabed.gob.gt" class="{{ $ctrl }}"></textarea>
                </x-campo>
                <div class="mt-5 flex justify-end">
                    <x-primary-button wire:click="guardarCorreos" type="button">Guardar destinatarios</x-primary-button>
                </div>
            </x-panel>
        </div>

        {{-- SNMP --}}
        <div class="{{ $seccion }}">
            <div>
                <h2 class="text-sm font-semibold text-tinta">SNMP de las impresoras</h2>
                <p class="text-sm text-tinta-3 mt-1">Con SNMP v2c se leen los contadores (6 pm), el estado y tóner (cada 15 min) y el registro de trabajos (cada minuto). La comunidad debe coincidir con la configurada en las impresoras (de fábrica: <span class="cifra">public</span>). Aquí solo se configura cómo las consulta el sistema; no se cambia nada en las impresoras.</p>
                @unless (function_exists('snmp2_get'))
                    <p class="mt-3 text-xs text-red-700"><i class="fas fa-triangle-exclamation me-1"></i>Este servidor no tiene la extensión php-snmp: las lecturas usan el panel web como respaldo y no hay estado, tóner ni trabajos.</p>
                @endunless
            </div>
            <x-panel class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-campo label="Comunidad (lectura)" nombre="snmp_comunidad">
                        <input type="text" wire:model="snmp_comunidad" autocomplete="off" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Tiempo de espera (segundos)" nombre="snmp_timeout" ayuda="Por consulta. Súbelo si la red es lenta.">
                        <input type="number" step="0.5" min="0.5" max="10" wire:model="snmp_timeout" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Reintentos" nombre="snmp_reintentos">
                        <input type="number" min="0" max="5" wire:model="snmp_reintentos" class="{{ $ctrl }}">
                    </x-campo>
                </div>
                @if ($snmp_prueba)
                    <ul class="mt-4 space-y-1 text-xs">
                        @foreach ($snmp_prueba as $equipo => $res)
                            <li class="{{ str_starts_with($res, 'Responde') ? 'text-saldo' : 'text-red-700' }}"><b>{{ $equipo }}:</b> {{ $res }}</li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-5 flex justify-end gap-2">
                    <x-secondary-button wire:click="probarSnmp" wire:loading.attr="disabled" wire:target="probarSnmp" type="button">
                        <i class="fas fa-satellite-dish me-2" wire:loading.class="fa-beat" wire:target="probarSnmp"></i> Probar con las impresoras
                    </x-secondary-button>
                    <x-primary-button wire:click="guardarSnmp" type="button">Guardar SNMP</x-primary-button>
                </div>
            </x-panel>
        </div>

        {{-- Panel web de las impresoras --}}
        <div class="{{ $seccion }}">
            <div>
                <h2 class="text-sm font-semibold text-tinta">Panel web de las impresoras</h2>
                <p class="text-sm text-tinta-3 mt-1">Usuario administrador del Command Center de las Kyocera. Se usa para leer el consumo por clave (contabilidad de trabajos) y como respaldo cuando SNMP no responde. Solo lee: no cambia nada en las impresoras.</p>
            </div>
            <x-panel class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-campo label="Usuario" nombre="panel_usuario">
                        <input type="text" wire:model="panel_usuario" autocomplete="off" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Contraseña" nombre="panel_password" ayuda="Vacía = se conserva la guardada (por defecto, la de fábrica).">
                        <input type="password" wire:model="panel_password" autocomplete="new-password" placeholder="••••••••" class="{{ $ctrl }}">
                    </x-campo>
                </div>
                <div class="mt-5 flex justify-end">
                    <x-primary-button wire:click="guardarPanel" type="button">Guardar acceso</x-primary-button>
                </div>
            </x-panel>
        </div>

        {{-- Cuenta que envía --}}
        <div class="{{ $seccion }}">
            <div>
                <h2 class="text-sm font-semibold text-tinta">Cuenta que envía los correos</h2>
                <p class="text-sm text-tinta-3 mt-1">Servidor SMTP del correo institucional. La contraseña se guarda cifrada y no se vuelve a mostrar.</p>
                <dl class="mt-3 space-y-1 text-xs text-tinta-2">
                    <div><dt class="inline font-medium">Office 365:</dt> <dd class="inline">smtp.office365.com · 587</dd></div>
                    <div><dt class="inline font-medium">Gmail:</dt> <dd class="inline">smtp.gmail.com · 587 (contraseña de aplicación)</dd></div>
                </dl>
            </div>
            <x-panel class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <x-campo label="Servidor" nombre="smtp_host" class="md:col-span-4">
                        <input type="text" wire:model="smtp_host" placeholder="smtp.office365.com" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Puerto" nombre="smtp_port" class="md:col-span-2">
                        <input type="number" wire:model="smtp_port" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Usuario" nombre="smtp_username" class="md:col-span-3">
                        <input type="text" wire:model="smtp_username" autocomplete="off" placeholder="informatica@senabed.gob.gt" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Contraseña" nombre="smtp_password" :ayuda="$smtp_tiene_password ? 'Hay una guardada. Déjala vacía para no cambiarla.' : null" class="md:col-span-3">
                        <input type="password" wire:model="smtp_password" autocomplete="new-password" placeholder="{{ $smtp_tiene_password ? '••••••••' : '' }}" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Correo remitente" nombre="smtp_from_address" ayuda="Vacío = el mismo usuario" class="md:col-span-3">
                        <input type="email" wire:model="smtp_from_address" class="{{ $ctrl }}">
                    </x-campo>
                    <x-campo label="Nombre remitente" nombre="smtp_from_name" class="md:col-span-3">
                        <input type="text" wire:model="smtp_from_name" placeholder="Impresoras SENABED" class="{{ $ctrl }}">
                    </x-campo>
                    <label class="md:col-span-6 flex items-center gap-2 text-sm text-tinta-2">
                        <input type="checkbox" wire:model="smtp_ssl" class="size-4 rounded border-gray-300 text-marino-600 focus:ring-marino-600">
                        Usar SSL (puerto 465). Desmarcado usa STARTTLS (587) si el servidor lo ofrece.
                    </label>
                </div>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <x-secondary-button wire:click="probarCorreo" wire:loading.attr="disabled" wire:target="probarCorreo" type="button">
                        <i class="fas fa-paper-plane me-2" wire:loading.class="fa-beat" wire:target="probarCorreo"></i> Enviar prueba
                    </x-secondary-button>
                    <x-primary-button wire:click="guardarSmtp" type="button">Guardar cuenta</x-primary-button>
                </div>
            </x-panel>
        </div>
    </div>
</div>
