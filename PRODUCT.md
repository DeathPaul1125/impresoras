# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Informática y Estadística (admin):** el equipo que opera el sistema a diario: registra impresoras, revisa cortes, corrige contadores con el dato del proveedor y configura reportes.
- **Dirección y jefaturas (rol consulta):** revisan el consumo, el saldo del bolsón y los reportes para decidir sobre el contrato.

## Product Purpose

Controlar el consumo de copias e impresiones de las 7 multifuncionales Kyocera de SENABED contra el contrato con el proveedor. Lee los contadores de cada equipo por SNMP todos los días a las 6 pm, registra el corte mensual del contrato el día 25 a las 00:00, calcula lo producido por equipo y por mes, el saldo del bolsón B/N y su proyección, y envía reportes por correo (diario, semanal y de corte) con Excel y PDF. Éxito: saber en todo momento cuánto del bolsón queda y cuadrar cada corte con el proveedor sin hojas de cálculo manuales.

## Positioning

Es el registro propio de SENABED, independiente del proveedor: los contadores salen directamente de las impresoras y el histórico del contrato reproduce exactamente el control mensual que antes se llevaba en Excel.

## Operating Context

- Uso exclusivo en la red interna de SENABED (192.100.x); no se publica a internet.
- Ritmo del contrato: lecturas diarias 18:00, corte mensual día 25 00:00, reporte de corte 00:10.
- Equipos identificados por ubicación y nivel (piso): Lobby (N1), DAB 7o. (N7.1), URC 7o. (N7.2), DAF 8o. (N8.1), DCR 8o. (N8.2), SG 9o. (N9.1), DAJ 9o. (N9.2).

## Capabilities and Constraints

- Laravel 13 + Livewire 3 (Volt) + Tailwind v3; despliegue por git a un servidor con Docker (MatiPanel).
- Contador del contrato = impresiones + copias (no el total SNMP).
- Consumo por usuario/clave: pendiente (registro de trabajos por SNMP o contabilidad de trabajos del Command Center).
- Todo el texto en español (Guatemala).

## Brand Commitments

- Identidad institucional SENABED: usar el logotipo oficial de la Dirección de Informática y Estadística (`public/img/logo-senabed-die.png`: escudo de Guatemala + "Secretaría Nacional de Administración de Bienes en Extinción de Dominio · Dirección de Informática y Estadística").
- Debe sentirse institucional y propio; el usuario rechaza explícitamente lo que "parece plantilla".

## Evidence on Hand

- Contrato 4912, bolsón 781,200 páginas B/N (dato interno: no mostrar en pantallas públicas como el login).
- Excel del proveedor "Control Contadores mensuales" (ene–sep 2026) y dashboard de referencia del usuario (fuera del repositorio).

## Product Principles

1. Los números deben cuadrar con el proveedor; cualquier discrepancia se muestra, no se esconde.
2. Automático por defecto: lo que se puede leer de las impresoras no se teclea.
3. Institucional y sobrio, pero propio: nada de plantillas genéricas.
4. Datos internos no se exponen fuera de la sesión.
