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
    $ctrl = 'block w-full h-11 px-3.5 rounded-lg border-0 bg-white ring-1 ring-inset ring-linea text-sm text-tinta placeholder:text-tinta-4 shadow-sm focus:ring-2 focus:ring-marino-600';
@endphp

<div>
    <h2 class="text-2xl font-semibold tracking-tight text-tinta">Iniciar sesión</h2>
    <p class="mt-1 text-sm text-tinta-3">Ingresa con tu correo institucional.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    @error('form.email')
        <div class="mt-6 rounded-lg bg-red-50 ring-1 ring-inset ring-red-200 px-3.5 py-2.5 text-sm text-red-700 flex gap-2">
            <i class="fas fa-circle-exclamation mt-0.5"></i> {{ $message }}
        </div>
    @enderror

    <form wire:submit="login" class="mt-6 space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium text-tinta-2 mb-1.5">Correo</label>
            <input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username"
                   placeholder="usuario@senabed.gob.gt" class="{{ $ctrl }}">
        </div>

        <div x-data="{ ver: false }">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-medium text-tinta-2">Contraseña</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate class="text-xs font-medium text-marino-600 hover:underline">¿La olvidaste?</a>
                @endif
            </div>
            <div class="relative">
                <input wire:model="form.password" id="password" :type="ver ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password"
                       class="{{ $ctrl }} pe-11">
                <button type="button" @click="ver = !ver" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        class="absolute inset-y-0 end-0 w-11 inline-flex items-center justify-center text-tinta-3 hover:text-tinta">
                    <i class="fas" :class="ver ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            @error('form.password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <label for="remember" class="flex items-center gap-2 text-sm text-tinta-2">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember" class="size-4 rounded border-gray-300 text-marino-600 focus:ring-marino-600">
            Mantener la sesión iniciada
        </label>

        <button type="submit" wire:loading.attr="disabled" wire:target="login"
                class="w-full h-11 inline-flex items-center justify-center gap-2 rounded-lg bg-marino text-white text-sm font-semibold hover:bg-marino-700 active:scale-[0.99] focus:outline-none focus-visible:ring-2 focus-visible:ring-marino-600 focus-visible:ring-offset-2 disabled:opacity-60 transition-[background-color,transform] duration-150">
            <span wire:loading.remove wire:target="login">Entrar</span>
            <span wire:loading wire:target="login"><i class="fas fa-spinner fa-spin me-1"></i> Verificando…</span>
        </button>
    </form>

    <p class="mt-8 text-xs text-tinta-3">¿No tienes acceso? Solicítalo a la Dirección de Informática y Estadística.</p>
</div>
