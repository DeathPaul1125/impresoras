<?php

namespace App\Support;

/**
 * Traduce las alertas de la Printer-MIB (RFC 3805, prtAlertTable) a texto claro.
 * Las Kyocera no llenan la descripción de la alerta: solo dan código, grupo y a qué bandeja/suministro se refiere.
 */
class AlertasImpresora
{
    /** código => [qué pasa, qué hacer] */
    public const CODIGOS = [
        3 => ['Tapa abierta', 'Cerrar la tapa que indica la pantalla.'],
        5 => ['Puerta o seguro abierto', 'Cerrar bien la puerta hasta que haga clic.'],
        7 => ['Cambió la configuración de la impresora', null],
        8 => ['Atasco de papel', 'Retirar el papel atascado siguiendo las indicaciones de la pantalla.'],
        9 => ['Falta una pieza o accesorio', 'Revisar que esté bien colocada; si falta, avisar al proveedor.'],
        10 => ['Pieza cerca del fin de su vida útil', 'Avisar al proveedor para programar el cambio.'],
        11 => ['Pieza al final de su vida útil', 'Solicitar al proveedor el reemplazo.'],
        12 => ['Casi vacío', 'Preparar la reposición.'],
        13 => ['Vacío', 'Reponer.'],
        14 => ['Casi lleno', 'Vaciar pronto.'],
        15 => ['Lleno', 'Vaciar.'],
        16 => ['Cerca del límite', null],
        17 => ['En el límite', 'Revisar en la pantalla de la impresora.'],
        18 => ['Abierto', 'Cerrar.'],
        22 => ['Fuera de línea', 'Revisar la pantalla; apagar y encender si no se recupera.'],
        23 => ['En ahorro de energía', null],
        24 => ['Calentando', null],
        29 => ['Falla recuperable', 'Apagar y encender la impresora; si se repite, avisar al proveedor.'],
        30 => ['Falla que requiere servicio técnico', 'Llamar al proveedor y darle el código que muestra la pantalla.'],
        31 => ['Error de almacenamiento (recuperable)', 'Apagar y encender; si se repite, avisar al proveedor.'],
        32 => ['Error de almacenamiento (requiere servicio)', 'Llamar al proveedor.'],
        33 => ['Falla de motor', 'Llamar al proveedor.'],
        34 => ['Memoria llena', 'Dividir el trabajo en partes o imprimirlo con menor resolución.'],
        35 => ['Temperatura por debajo de lo normal', null],
        36 => ['Sobrecalentamiento', 'Apagar, dejar enfriar y encender; si se repite, avisar al proveedor.'],
        37 => ['Falla de sincronización', 'Llamar al proveedor.'],
        38 => ['Falla del sensor de temperatura', 'Llamar al proveedor.'],
        501 => ['Puerta abierta', 'Cerrar la puerta.'],
        503 => ['La impresora se encendió', null],
        504 => ['La impresora se apagó', null],
        505 => ['Reiniciada por la red', null],
        506 => ['Reiniciada manualmente', null],
        507 => ['Lista para imprimir', null],
        801 => ['Bandeja de papel no colocada', 'Insertar la bandeja hasta el fondo.'],
        802 => ['Cambió el tamaño de papel de la bandeja', 'Verificar que el tamaño configurado coincida con el papel cargado.'],
        803 => ['Cambió el gramaje del papel', null],
        804 => ['Cambió el tipo de papel', null],
        805 => ['Cambió el color del papel', null],
        807 => ['Poco papel', 'Cargar papel pronto.'],
        808 => ['Sin papel', 'Cargar papel en la bandeja.'],
        809 => ['Pide cargar papel a mano', 'Colocar papel en la bandeja multipropósito y confirmar en la pantalla.'],
        811 => ['Bandeja de papel retirada', 'Volver a insertar la bandeja.'],
        901 => ['Falta la bandeja de salida', 'Colocar la bandeja de salida.'],
        902 => ['Bandeja de salida casi llena', 'Retirar las hojas impresas.'],
        903 => ['Bandeja de salida llena', 'Retirar las hojas impresas para que siga imprimiendo.'],
        1001 => ['Fusor frío (en reposo)', 'Normal mientras la impresora está en reposo o ahorro de energía: se calienta sola al imprimir.'],
        1002 => ['Fusor sobrecalentado', 'Apagar, dejar enfriar y encender; si se repite, avisar al proveedor.'],
        1003 => ['Falla del fusor', 'Llamar al proveedor.'],
        1004 => ['Falla del sensor del fusor', 'Llamar al proveedor.'],
        1005 => ['Ajustando la calidad de impresión', null],
        1101 => ['Tóner agotado', 'Cambiar el cartucho de tóner.'],
        1104 => ['Tóner por agotarse', 'Tener listo un cartucho nuevo (pedirlo al proveedor).'],
        1107 => ['Depósito de tóner residual casi lleno', 'Pedir al proveedor la caja de tóner residual.'],
        1109 => ['Depósito de tóner residual lleno', 'Cambiar la caja de tóner residual; la impresora no imprime hasta hacerlo.'],
        1111 => ['Tambor cerca del fin de su vida útil', 'Avisar al proveedor para programar el cambio.'],
        1112 => ['Tambor al final de su vida útil', 'Solicitar al proveedor el cambio del tambor.'],
        1113 => ['Revelador casi agotado', 'Avisar al proveedor.'],
        1114 => ['Revelador agotado', 'Solicitar al proveedor el cambio.'],
        1115 => ['Falta el cartucho de tóner', 'Instalar el cartucho de tóner.'],
        1301 => ['Falta una bandeja en el recorrido del papel', 'Revisar que todas las bandejas estén colocadas.'],
        1302 => ['Bandeja del recorrido casi llena', null],
        1303 => ['Bandeja del recorrido llena', 'Retirar las hojas.'],
        1304 => ['No puede imprimir a doble cara en el papel elegido', 'Usar otro tamaño o tipo de papel, o imprimir a una cara.'],
        1507 => ['Falta un recurso del trabajo (fuente o formulario)', 'Revisar el documento y volver a enviarlo.'],
        1509 => ['Página demasiado compleja', 'La página puede salir incompleta: simplificar el documento o imprimir con menor resolución.'],
    ];

    /** Códigos que no son un problema (avisos de rutina): se muestran en gris y no cuentan como alerta. */
    public const INFORMATIVAS = [7, 23, 24, 503, 504, 505, 506, 507, 803, 804, 805, 1001, 1005];

    /** prtAlertGroup: de qué parte de la impresora es la alerta. */
    private const GRUPOS = [5 => 'Impresora', 6 => 'Tapa', 8 => 'Bandeja de papel', 9 => 'Bandeja de salida', 10 => 'Unidad de impresión',
        11 => 'Suministro', 13 => 'Recorrido del papel', 14 => 'Conexión', 15 => 'Intérprete', 16 => 'Pantalla', 30 => 'Finalizador'];

    /** prtAlertTrainingLevel: quién puede resolverla. */
    private const QUIEN = [3 => 'Cualquier usuario', 4 => 'Personal capacitado', 5 => 'Técnico del proveedor', 6 => 'Administrador'];

    private const PIEZAS = ['MP Tray' => 'Bandeja multipropósito', 'Inner Tray' => 'Bandeja interna', 'Rear Tray' => 'Bandeja trasera'];

    /**
     * @param  array{severidad:int,nivel_usuario:int,grupo:int,indice:int,codigo:int}  $a  columnas crudas de prtAlertTable
     * @param  array<string,string>  $nombres  [grupo.índice => nombre] de bandejas, salidas y suministros
     */
    public static function describir(array $a, array $nombres = []): array
    {
        [$texto, $accion] = self::CODIGOS[$a['codigo']] ?? ["Alerta código {$a['codigo']}", 'Revisar el mensaje en la pantalla de la impresora.'];
        $pieza = $nombres["{$a['grupo']}.{$a['indice']}"] ?? null;
        $pieza = $pieza ? (self::PIEZAS[$pieza] ?? $pieza) : null;
        // Los códigos genéricos (9-38: "vacío", "lleno"...) necesitan decir de qué parte se trata.
        $donde = $pieza ?? ($a['codigo'] < 500 ? (self::GRUPOS[$a['grupo']] ?? null) : null);

        return [
            'codigo' => $a['codigo'],
            'nivel' => in_array($a['codigo'], self::INFORMATIVAS, true) ? 'info' : ($a['severidad'] === 3 ? 'critica' : 'advertencia'),
            'texto' => $donde ? "{$donde}: ".mb_strtolower(mb_substr($texto, 0, 1)).mb_substr($texto, 1) : $texto,
            'accion' => $accion,
            'quien' => self::QUIEN[$a['nivel_usuario']] ?? null,
        ];
    }

    /** Alertas que sí requieren atención (sin las informativas). Acepta el formato anterior (solo código). */
    public static function activas(?array $alertas): array
    {
        return array_values(array_filter($alertas ?? [], fn ($a) => ($a['nivel'] ?? null) !== 'info' && ! in_array($a['codigo'] ?? 0, self::INFORMATIVAS, true)));
    }

    /** Texto de una alerta guardada (las del formato anterior solo traían el código). */
    public static function texto(array $a): string
    {
        return $a['texto'] ?? (self::CODIGOS[$a['codigo'] ?? 0][0] ?? 'Alerta código '.($a['codigo'] ?? '?'));
    }
}
