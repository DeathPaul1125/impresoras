<?php

namespace App\Livewire;

use App\Models\Configuracion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Configuración')]
class ConfiguracionPrecio extends Component
{
    public float $precio_unitario = 0.17;

    // Contrato con el proveedor.
    public string $contrato_numero = '';

    public int $contrato_bolson = 0;

    public string $contrato_inicio = '';

    public string $contrato_fin = '';

    public int $contrato_dia_corte = 25;

    /** Separados por coma. */
    public string $correos_reportes = '';

    // Cuenta que envía (SMTP).
    public string $smtp_host = '';

    public int $smtp_port = 587;

    public bool $smtp_ssl = false;

    public string $smtp_username = '';

    /** Vacío = conservar la guardada. Nunca se envía la actual al navegador. */
    public string $smtp_password = '';

    public bool $smtp_tiene_password = false;

    public string $smtp_from_address = '';

    public string $smtp_from_name = '';

    // Panel web (Command Center) de las impresoras: lectura de cuentas y respaldo de SNMP.
    public string $panel_usuario = 'Admin';

    /** Vacío = conservar la guardada. */
    public string $panel_password = '';

    public function mount(): void
    {
        $this->precio_unitario = Configuracion::precioUnitario();

        $c = Configuracion::contrato();
        $this->contrato_numero = (string) $c['numero'];
        $this->contrato_bolson = (int) $c['bolson'];
        $this->contrato_inicio = (string) $c['inicio'];
        $this->contrato_fin = (string) $c['fin'];
        $this->contrato_dia_corte = (int) $c['dia_corte'];
        $this->correos_reportes = implode(', ', Configuracion::correosReportes());

        $this->panel_usuario = Configuracion::panelImpresoras()['usuario'];

        $s = Configuracion::smtp();
        $this->smtp_host = (string) $s['host'];
        $this->smtp_port = (int) $s['port'];
        $this->smtp_ssl = (bool) $s['ssl'];
        $this->smtp_username = (string) $s['username'];
        $this->smtp_tiene_password = filled($s['password']);
        $this->smtp_from_address = (string) $s['from_address'];
        $this->smtp_from_name = (string) $s['from_name'];
    }

    public function guardar(): void
    {
        $this->validate([
            'precio_unitario' => 'required|numeric|min:0',
        ]);

        Configuracion::setPrecioUnitario($this->precio_unitario);

        session()->flash('ok', 'Precio unitario actualizado a Q'.number_format($this->precio_unitario, 2).'.');
    }

    public function guardarContrato(): void
    {
        $this->validate([
            'contrato_numero' => 'required|string|max:50',
            'contrato_bolson' => 'required|integer|min:1',
            'contrato_inicio' => 'required|date',
            'contrato_fin' => 'required|date|after:contrato_inicio',
            'contrato_dia_corte' => 'required|integer|between:1,28',
        ], [], [
            'contrato_numero' => 'número de contrato', 'contrato_bolson' => 'bolsón', 'contrato_inicio' => 'inicio',
            'contrato_fin' => 'fin', 'contrato_dia_corte' => 'día de corte',
        ]);

        Configuracion::setContrato([
            'numero' => trim($this->contrato_numero),
            'bolson' => $this->contrato_bolson,
            'inicio' => $this->contrato_inicio,
            'fin' => $this->contrato_fin,
            'dia_corte' => $this->contrato_dia_corte,
        ]);

        session()->flash('ok', 'Datos del contrato guardados.');
    }

    public function guardarCorreos(): void
    {
        $this->validate(['correos_reportes' => 'nullable|string|max:2000']);

        $correos = array_values(array_filter(array_map('trim', preg_split('/[,;\s]+/', $this->correos_reportes))));
        foreach ($correos as $c) {
            if (Validator::make(['c' => $c], ['c' => 'email'])->fails()) {
                $this->addError('correos_reportes', "Correo inválido: {$c}");

                return;
            }
        }

        Configuracion::setCorreosReportes($correos);
        $this->correos_reportes = implode(', ', $correos);

        session()->flash('ok', $correos ? 'Los reportes se enviarán a: '.$this->correos_reportes.'.' : 'Reportes desactivados (sin destinatarios).');
    }

    public function guardarPanel(): void
    {
        $this->validate(['panel_usuario' => 'required|string|max:64', 'panel_password' => 'nullable|string|max:64'], [], ['panel_usuario' => 'usuario']);

        Configuracion::setPanelImpresoras(trim($this->panel_usuario), $this->panel_password !== '' ? $this->panel_password : null);
        $this->panel_password = '';
        session()->flash('ok', 'Acceso al panel de las impresoras guardado.');
    }

    public function guardarSmtp(): void
    {
        $this->validate([
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|between:1,65535',
            'smtp_ssl' => 'boolean',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_from_address' => ($this->smtp_username && filter_var($this->smtp_username, FILTER_VALIDATE_EMAIL) ? 'nullable' : 'required').'|email|max:255',
            'smtp_from_name' => 'nullable|string|max:255',
        ], [], [
            'smtp_host' => 'servidor', 'smtp_port' => 'puerto', 'smtp_from_address' => 'correo remitente',
        ]);

        Configuracion::setSmtp([
            'host' => trim($this->smtp_host),
            'port' => $this->smtp_port,
            'ssl' => $this->smtp_ssl,
            'username' => trim($this->smtp_username) ?: null,
            'password' => $this->smtp_password !== '' ? $this->smtp_password : Configuracion::smtp()['password'],
            'from_address' => trim($this->smtp_from_address) ?: null,
            'from_name' => trim($this->smtp_from_name) ?: 'Impresoras SENABED',
        ]);

        $this->smtp_password = '';
        $this->smtp_tiene_password = filled(Configuracion::smtp()['password']);
        session()->flash('ok', 'Cuenta de envío guardada. Usa "Enviar prueba" para verificarla.');
    }

    /** Envía un correo de prueba con la cuenta guardada a los destinatarios (o al usuario actual). */
    public function probarCorreo(): void
    {
        if (! Configuracion::aplicarSmtp()) {
            session()->flash('error', 'Primero guarda el servidor SMTP.');

            return;
        }

        $para = Configuracion::correosReportes() ?: [auth()->user()->email];
        try {
            Mail::raw('Correo de prueba del sistema de impresoras SENABED. Si lo recibes, la cuenta de envío funciona.',
                fn ($m) => $m->to($para)->subject('Prueba de correo · Impresoras SENABED'));
            session()->flash('ok', 'Correo de prueba enviado a: '.implode(', ', $para).'.');
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'No se pudo enviar: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.configuracion-precio');
    }
}
