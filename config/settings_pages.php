<?php

/*
 * Paginas de configuracion simples (se guardan en app_settings por empresa).
 * Tipos de campo: text, password, number, toggle, select, textarea, readonly.
 * Las claves secretas reales van en .env; aqui solo quedan los valores de prueba.
 */
return [
    'contrato' => [
        'title' => 'Contrato y licencia',
        'notice' => 'Este ERP es software propio de la empresa: no requiere contrato ni suscripción con un proveedor tecnológico. Aquí se registra la información del servicio de hosting y soporte.',
        'sections' => [
            ['title' => 'Licencia', 'fields' => [
                ['key' => 'software', 'label' => 'Software', 'type' => 'readonly', 'default' => 'OYARCEGROUP INC'],
                ['key' => 'dian_mode', 'label' => 'Modalidad DIAN', 'type' => 'readonly', 'default' => 'Software propio'],
                ['key' => 'server', 'label' => 'Servidor', 'type' => 'readonly', 'default' => parse_url((string) env('APP_URL'), PHP_URL_HOST) ?: 'localhost'],
            ]],
            ['title' => 'Soporte', 'fields' => [
                ['key' => 'support_name', 'label' => 'Responsable de soporte', 'type' => 'text'],
                ['key' => 'support_email', 'label' => 'Correo de soporte', 'type' => 'text'],
                ['key' => 'renewal_date', 'label' => 'Renovación de hosting / dominio', 'type' => 'date'],
            ]],
        ],
    ],
    'cobranza' => [
        'title' => 'Tareas de seguimiento de cobranza',
        'test' => 'Generar tareas de hoy',
        'notice' => 'Define cuándo se generan tareas de cobro para las facturas vencidas y quién las atiende.',
        'sections' => [
            ['title' => 'Generación de tareas', 'fields' => [
                ['key' => 'enabled', 'label' => 'Generar tareas de cobranza automáticamente', 'type' => 'toggle', 'default' => false],
                ['key' => 'first_days', 'label' => 'Primera gestión (días después del vencimiento)', 'type' => 'number', 'default' => 1],
                ['key' => 'second_days', 'label' => 'Segunda gestión (días después del vencimiento)', 'type' => 'number', 'default' => 15],
                ['key' => 'third_days', 'label' => 'Tercera gestión (días después del vencimiento)', 'type' => 'number', 'default' => 30],
                ['key' => 'channel', 'label' => 'Canal', 'type' => 'select', 'options' => ['Llamada', 'Correo electrónico', 'WhatsApp', 'Visita'], 'default' => 'Llamada'],
                ['key' => 'responsible', 'label' => 'Responsable por defecto', 'type' => 'text', 'help' => 'Si el cliente tiene cobrador asignado, la tarea se asigna a él.'],
                ['key' => 'min_balance', 'label' => 'Saldo mínimo para generar tarea', 'type' => 'number', 'default' => 0],
            ]],
        ],
    ],
    'correos' => [
        'title' => 'Desbloqueo de correos para envío de emails y notificaciones',
        'notice' => 'Servidor de correo con el que se envían facturas, recibos y notificaciones. Si un destinatario rebota, aparece aquí para desbloquearlo.',
        'test' => 'Enviar correo de prueba',
        'sections' => [
            ['title' => 'Servidor SMTP', 'fields' => [
                ['key' => 'host', 'label' => 'Servidor', 'type' => 'text', 'default' => 'smtp.gmail.com'],
                ['key' => 'port', 'label' => 'Puerto', 'type' => 'number', 'default' => 587],
                ['key' => 'encryption', 'label' => 'Cifrado', 'type' => 'select', 'options' => ['tls', 'ssl', 'ninguno'], 'default' => 'tls'],
                ['key' => 'username', 'label' => 'Usuario', 'type' => 'text'],
                ['key' => 'password', 'label' => 'Contraseña', 'type' => 'password', 'help' => 'Se guarda cifrada. Si dejas el servidor vacío se usa el correo configurado en el archivo .env.'],
                ['key' => 'from_name', 'label' => 'Nombre del remitente', 'type' => 'text'],
                ['key' => 'from_email', 'label' => 'Correo del remitente', 'type' => 'text'],
            ]],
            ['title' => 'Buzón de recepción de facturas de proveedores (IMAP)', 'fields' => [
                ['key' => 'imap_host', 'label' => 'Servidor IMAP', 'type' => 'text', 'help' => 'Correo de recepción registrado ante la DIAN. La Bandeja DIAN lo lee al pulsar Sincronizar.'],
                ['key' => 'imap_port', 'label' => 'Puerto', 'type' => 'number', 'default' => 993],
                ['key' => 'imap_encryption', 'label' => 'Cifrado', 'type' => 'select', 'options' => ['ssl', 'tls', 'ninguno'], 'default' => 'ssl'],
                ['key' => 'imap_username', 'label' => 'Usuario', 'type' => 'text'],
                ['key' => 'imap_password', 'label' => 'Contraseña', 'type' => 'password', 'help' => 'Se guarda cifrada.'],
                ['key' => 'imap_folder', 'label' => 'Carpeta', 'type' => 'text', 'default' => 'INBOX'],
            ]],
            ['title' => 'Correos bloqueados', 'fields' => [
                ['key' => 'blocked', 'label' => 'Direcciones bloqueadas (una por línea). Borra una línea para desbloquearla.', 'type' => 'textarea'],
            ]],
        ],
    ],
    'recordatorios' => [
        'title' => 'Configuración de recordatorios automáticos',
        'test' => 'Enviarme un recordatorio de prueba',
        'notice' => 'Correos automáticos a los clientes con facturas próximas a vencer o vencidas.',
        'sections' => [
            ['title' => 'Recordatorios de pago', 'fields' => [
                ['key' => 'enabled', 'label' => 'Enviar recordatorios automáticos', 'type' => 'toggle', 'default' => false],
                ['key' => 'before_days', 'label' => 'Días antes del vencimiento', 'type' => 'number', 'default' => 3],
                ['key' => 'on_due', 'label' => 'Enviar el día del vencimiento', 'type' => 'toggle', 'default' => true],
                ['key' => 'after_days', 'label' => 'Días después del vencimiento', 'type' => 'number', 'default' => 5],
                ['key' => 'repeat_days', 'label' => 'Repetir cada (días) mientras siga vencida', 'type' => 'number', 'default' => 15],
                ['key' => 'max_days', 'label' => 'Dejar de recordar después de (días vencida, 0 = sin límite)', 'type' => 'number', 'default' => 90],
                ['key' => 'attach_pdf', 'label' => 'Adjuntar PDF de la factura', 'type' => 'toggle', 'default' => true],
                ['key' => 'message', 'label' => 'Mensaje', 'type' => 'textarea', 'default' => 'Hola {cliente}, te recordamos que la factura {factura} por {saldo} vence el {vencimiento}.'],
            ]],
        ],
    ],
    'ia' => [
        'title' => 'Asistente de inteligencia artificial',
        'notice' => 'La IA solo se usa en la Bandeja DIAN para vincular los productos de las facturas recibidas con los productos creados en el ERP. Funciona con cualquier servicio compatible con la API de OpenAI (OpenCode Zen, OpenRouter, Ollama, LM Studio...). Los precios siempre se toman de la factura, nunca de la IA.',
        'test' => 'Probar conexión',
        'sections' => [
            ['title' => 'Conexión', 'fields' => [
                ['key' => 'enabled', 'label' => 'Usar IA para vincular productos de las facturas recibidas', 'type' => 'toggle', 'default' => false],
                ['key' => 'url', 'label' => 'URL de la API', 'type' => 'text', 'default' => 'https://opencode.ai/zen/v1', 'help' => 'URL base (termina en /v1) o completa hasta /chat/completions.'],
                ['key' => 'token', 'label' => 'Token', 'type' => 'password', 'help' => 'Se guarda cifrado. Se envía como "Authorization: Bearer <token>".'],
                ['key' => 'model', 'label' => 'Modelo', 'type' => 'text', 'default' => 'glm-4.7-flash'],
                ['key' => 'timeout', 'label' => 'Tiempo máximo de respuesta (segundos)', 'type' => 'number', 'default' => 90],
                ['key' => 'min_confidence', 'label' => 'Confianza mínima para vincular (0 a 100)', 'type' => 'number', 'default' => 70, 'help' => 'Por debajo de este valor la línea queda sin producto para que la elijas a mano.'],
            ]],
        ],
    ],
    'mandato' => [
        'title' => 'Cuentas de mandato',
        'notice' => 'Estas cuentas se usan cuando una factura o una nota crédito trae líneas del mandante, un ajuste sin impuesto o un contado ya pagado. No se reemplazan por las cuentas de venta. Si falta alguna y el documento la necesita, no se guarda ni se envía a la DIAN.',
        'sections' => [
            ['title' => 'Cuentas de mandato', 'fields' => [
                ['key' => 'bridge', 'label' => 'Puente de recaudo', 'type' => 'text', 'help' => 'Código PUC. Débito del total cuando la forma de pago es contado ya pagado.'],
                ['key' => 'liability', 'label' => 'Pasivo de mandato', 'type' => 'text', 'help' => 'Código PUC. Crédito del valor de las líneas del mandante, sin IVA.'],
                ['key' => 'vat', 'label' => 'IVA de mandato', 'type' => 'text', 'help' => 'Código PUC. Distinta de la cuenta de IVA propio.'],
                ['key' => 'adjustment', 'label' => 'Ajuste sin impuesto', 'type' => 'text', 'help' => 'Código PUC de ingreso o gasto. Recibe el valor de la línea ZZ, también si es negativo.'],
            ]],
        ],
    ],
    'api' => [
        'title' => 'Credenciales de integración a plataformas digitales (API)',
        'notice' => 'Credenciales para que otras plataformas (tienda en línea, punto de venta, CRM) consulten y creen información en el ERP: terceros, productos, existencias, facturas electrónicas, notas, compras, recibos, comprobantes, reportes y webhooks. Envía la llave en la cabecera "Authorization: Bearer <llave>" a /api/v1; la llave actúa con los permisos del usuario que la genera. Deja las IPs vacías para permitir cualquier origen.',
        'test' => 'Generar nueva llave',
        'docs' => '/api/v1/docs',
        'sections' => [
            ['title' => 'Acceso API', 'fields' => [
                ['key' => 'enabled', 'label' => 'Habilitar API', 'type' => 'toggle', 'default' => false],
                ['key' => 'client_id', 'label' => 'Usuario API', 'type' => 'readonly', 'default' => 'erp-api'],
                ['key' => 'token_hint', 'label' => 'Llave de acceso', 'type' => 'readonly', 'default' => 'Sin generar'],
                ['key' => 'allowed_ips', 'label' => 'IPs permitidas (separadas por coma)', 'type' => 'text', 'default' => ''],
                ['key' => 'scopes', 'label' => 'Permisos', 'type' => 'select', 'options' => ['Solo lectura', 'Lectura y escritura'], 'default' => 'Solo lectura'],
            ]],
        ],
    ],
];
