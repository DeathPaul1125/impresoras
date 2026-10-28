---
version: 1
slug: "views-livewire-pages-auth-login-blade-php-4f41abf9"
primary_target: "resources/views/livewire/pages/auth/login.blade.php"
related_targets: ["resources/views/layouts/guest.blade.php"]
---

# Login · Contadores SENABED

Scope: pantalla de inicio de sesión (y el layout guest que comparten las demás pantallas de acceso). Mode: Operate.
Audiencia: Informática (admin, diario) y Dirección/jefaturas (consulta). Solo red interna. Tarea: entrar con correo y contraseña; recuperar contraseña si se olvidó.
Constraints: logo oficial DIE obligatorio; nada que "parezca plantilla"; sin datos internos (contrato, contadores); todo en español; Livewire Volt login() intacto.

## Direction contract

THESIS: Entrar es presentar un oficio. El acceso es un documento institucional de la Dirección de Informática y Estadística, no un formulario genérico sobre una tarjeta; rechaza el split-screen con panel de color y viñetas.

OWN-WORLD: Mesa marino (#0d2149) con trama sutil de pauta; hoja bond cálida (#fbfbf8) con membrete oficial, filete cian institucional (#29a9e0) bajo el logo, referencia y fecha en tipografía de oficio; campos como renglones a llenar (subrayado tinta, sin cajas); botón tinta marino; sello de hule azul en tinta doble.

STORY: El usuario reconoce de inmediato que es un sistema de SENABED, llena dos renglones y "firma"; el sello confirma que el acceso fue recibido.

FIRST VIEWPORT: Hoja centrada (≈640px, proporción carta recortada) sobre la mesa marino, ligeramente elevada. Arriba el membrete; debajo, línea "Ref.: DIE · Sistema de Contadores" a la izquierda y la fecha del día a la derecha; título "Acceso al sistema de contadores"; dos renglones; "Mantener la sesión" y "Firmar y entrar" al pie; pie con "¿Olvidaste tu contraseña?" y la nota de red interna. Firma de movimiento: al enviar, el sello «RECIBIDO · fecha» se estampa (escala 1.15→1 con opacidad) antes de redirigir; en error, la hoja se sacude levemente y el renglón marca en rojo.

FORM: Oficio institucional, posición 4 de mi lista (repartida por la tirada); seed key 54e2f6eb.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
