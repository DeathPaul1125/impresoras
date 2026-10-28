<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

@php
    // Renglón del oficio: sin caja, solo la línea de tinta donde se escribe.
    $renglon = 'block w-full h-12 px-0 bg-transparent border-0 border-b-2 border-tinta/25 text-[18px] text-tinta placeholder:text-tinta-3
                focus:ring-0 focus:border-marino-600 transition-colors duration-150';
@endphp

<div class="relative">
    <h1 class="font-oficio text-[30px] sm:text-[40px] font-bold tracking-[-0.015em] leading-[1.1] text-marino">Acceso al sistema de contadores</h1>
    <p class="mt-2 text-[15px] text-tinta-2">Consumo de copias e impresiones de las multifuncionales.</p>

    <x-auth-session-status class="mt-5" :status="session('status')" />

    @error('form.email')
        {{-- La clave cambia en cada render: así la hoja se sacude en cada intento fallido. --}}
        <p wire:key="error-{{ microtime(true) }}" x-init="$dispatch('oficio-error')" role="alert"
           class="mt-6 flex items-start gap-2 text-sm text-red-700">
            <i class="fas fa-circle-exclamation mt-0.5"></i> {{ $message }}
        </p>
    @enderror

    <form wire:submit="login" class="mt-8 space-y-7">
        <div>
            <label for="email" class="block text-[11px] font-semibold uppercase tracking-[0.14em] text-tinta-3">Correo institucional</label>
            <input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username"
                   placeholder="usuario@senabed.gob.gt" class="{{ $renglon }} @error('form.email') !border-red-600/70 @enderror">
        </div>

        <div x-data="{ ver: false }">
            <label for="password" class="block text-[11px] font-semibold uppercase tracking-[0.14em] text-tinta-3">Contraseña</label>
            <div class="relative">
                <input wire:model="form.password" id="password" :type="ver ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password"
                       class="{{ $renglon }} pe-10 @error('form.email') !border-red-600/70 @enderror">
                <button type="button" @click="ver = !ver" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'" :aria-pressed="ver"
                        class="absolute end-0 bottom-0 h-12 w-10 inline-flex items-center justify-center rounded text-tinta-3 hover:text-marino focus:outline-none focus-visible:ring-2 focus-visible:ring-cian">
                    <i class="fas" :class="ver ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            @error('form.password') <p class="text-xs text-red-700 mt-1.5">{{ $message }}</p> @enderror
        </div>

        <div class="relative flex flex-wrap items-center justify-between gap-4 pt-2">
            <label for="remember" class="flex items-center gap-2.5 text-sm text-tinta-2 cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox" name="remember"
                       class="size-4 rounded-[3px] border-tinta/30 text-marino focus:ring-cian focus:ring-offset-bond">
                Mantener la sesión
            </label>

            <button type="submit" wire:loading.attr="disabled" wire:target="login"
                    class="w-full sm:w-auto justify-center h-12 px-6 inline-flex items-center gap-2.5 rounded-[4px] bg-marino text-white text-[15px] font-semibold
                           shadow-[0_6px_14px_-6px_rgba(13,33,73,.6)] hover:bg-marino-700 active:scale-[0.97]
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-cian focus-visible:ring-offset-2 focus-visible:ring-offset-bond
                           disabled:opacity-70 transition-[background-color,transform] duration-150">
                <i class="fas fa-pen-nib" wire:loading.remove wire:target="login"></i>
                <i class="fas fa-spinner fa-spin" wire:loading wire:target="login"></i>
                <span wire:loading.remove wire:target="login">Firmar y entrar</span>
                <span wire:loading wire:target="login">Verificando…</span>
            </button>

            {{-- Sello de hule «RECIBIDO»: baja sobre la fila de firma mientras se verifica (cierto aunque el acceso falle: la solicitud sí se recibió). --}}
            <svg wire:loading.flex wire:target="login" aria-hidden="true" viewBox="0 0 200 200"
                 class="sello-baja pointer-events-none absolute right-2 sm:right-44 -top-16 sm:-top-14 size-32 sm:size-36 text-marino-600 mix-blend-multiply">
                <defs><path id="anillo" d="M100,100 m-74,0 a74,74 0 1,1 148,0 a74,74 0 1,1 -148,0"/></defs>
                <circle cx="100" cy="100" r="94" fill="none" stroke="currentColor" stroke-width="4"/>
                <circle cx="100" cy="100" r="86" fill="none" stroke="currentColor" stroke-width="1.5"/>
                <circle cx="100" cy="100" r="58" fill="none" stroke="currentColor" stroke-width="1.5"/>
                {{-- textLength = circunferencia del anillo (2π·74 ≈ 465): el texto cierra el círculo sin encimarse. --}}
                <text font-size="11.5" font-weight="700" fill="currentColor">
                    <textPath href="#anillo" startOffset="0" textLength="458" lengthAdjust="spacing">DIRECCIÓN DE INFORMÁTICA Y ESTADÍSTICA · SENABED ·</textPath>
                </text>
                <text x="100" y="96" text-anchor="middle" font-size="20" font-weight="800" letter-spacing="1.5" fill="currentColor">RECIBIDO</text>
                <text x="100" y="118" text-anchor="middle" font-size="12" font-weight="700" letter-spacing="1.5" fill="currentColor">{{ mb_strtoupper(now()->locale('es')->translatedFormat('j M Y')) }}</text>
            </svg>
        </div>
    </form>

    <div class="mt-10 pt-5 border-t border-tinta/10 flex flex-wrap items-center justify-between gap-3 text-[13px] text-tinta-3">
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" wire:navigate class="font-medium text-marino-600 underline decoration-cian/60 underline-offset-4 hover:decoration-marino-600">¿Olvidaste tu contraseña?</a>
        @endif
        <span>¿Sin acceso? Solicítalo a Informática.</span>
    </div>

</div>
