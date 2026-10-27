import 'preline';

// Re-inicializa los componentes de Preline tras navegaciones/actualizaciones de Livewire.
document.addEventListener('livewire:navigated', () => window.HSStaticMethods?.autoInit());
