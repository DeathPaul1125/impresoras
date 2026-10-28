<button {{ $attributes->merge(['type' => 'submit', 'class' => 'h-9 px-3.5 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg bg-marino text-white hover:bg-marino-700 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-marino-600 focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none transition-[background-color,transform] duration-150']) }}>
    {{ $slot }}
</button>
