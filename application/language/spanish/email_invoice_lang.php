<?php
/*
 | Email Invoice module (docs/email_invoice.md). Same key order as english/email_invoice_lang.php.
 | The bill email itself is always English (Invoice_mailer::t), so its keys are not repeated here.
 */

// module, menu and role screen
$lang['email_invoice'] = 'Factura por correo';
$lang['ei_send_email'] = 'Enviar / reenviar factura por correo';
$lang['ei_email_settings'] = 'Ajustes de correo';
$lang['ei_email_log'] = 'Registro de correos';

// settings page
$lang['ei_on'] = 'ACTIVADO';
$lang['ei_off'] = 'DESACTIVADO';
$lang['ei_settings_hint'] = 'Conecte la cuenta de correo desde la que se envían las facturas. Guarde y envíe un correo de prueba para comprobarlo.';
$lang['ei_enable'] = 'Enviar facturas por correo';
$lang['ei_enable_help'] = 'Al activarlo, la pantalla de pago del POS muestra la casilla "Factura por correo".';
$lang['ei_email_account'] = 'Cuenta de correo';
$lang['ei_provider'] = 'Proveedor de correo';
$lang['ei_username'] = 'Correo electrónico (usuario)';
$lang['ei_username_help'] = 'La cuenta de correo que envía las facturas.';
$lang['ei_password'] = 'Contraseña / contraseña de aplicación';
$lang['ei_password_help'] = 'Para Gmail use una contraseña de aplicación, no su contraseña normal.';
$lang['ei_password_saved'] = 'Guardada - deje vacío para mantenerla';
$lang['ei_password_keep'] = 'Hay una contraseña guardada. Deje vacío para mantenerla o escriba una nueva.';
$lang['ei_help_gmail'] = 'Gmail: active la verificación en dos pasos en su cuenta de Google, luego Cuenta de Google > Seguridad > Contraseñas de aplicaciones, cree una y pegue las 16 letras aquí. Su contraseña normal de Gmail no funcionará.';
$lang['ei_help_outlook'] = 'Outlook / Office 365: use el correo completo y su contraseña. En cuentas de empresa el administrador debe permitir "Authenticated SMTP" para este buzón.';
$lang['ei_help_zoho'] = 'Zoho: use el correo completo y su contraseña, o una contraseña de aplicación si tiene la verificación en dos pasos.';
$lang['ei_help_hostinger'] = 'Hostinger: use una cuenta de correo creada en hPanel > Emails y su contraseña.';
$lang['ei_help_custom'] = 'Pida a su proveedor de hosting el servidor SMTP, el puerto y la seguridad (cPanel: Email Accounts > Connect Devices) y complételos en Avanzado.';
$lang['ei_advanced'] = 'Avanzado (servidor, puerto, nombre del remitente)';
$lang['ei_host'] = 'Servidor SMTP';
$lang['ei_port'] = 'Puerto';
$lang['ei_encryption'] = 'Seguridad';
$lang['ei_none'] = 'Ninguna';
$lang['ei_from_email'] = 'Enviar desde';
$lang['ei_from_email_help'] = 'Vacío = el correo de usuario. La mayoría de proveedores solo permiten ese.';
$lang['ei_from_name'] = 'Nombre del remitente';
$lang['ei_from_name_help'] = 'Vacío = el nombre del local.';
$lang['ei_reply_to'] = 'Las respuestas van a';
$lang['ei_reply_to_help'] = 'Vacío = el correo del local.';
$lang['ei_bcc_email'] = 'Enviarme una copia (CCO)';
$lang['ei_bcc_help'] = 'Opcional: cada factura también se envía a esta dirección.';
$lang['ei_pos_section'] = 'POS';
$lang['ei_pos_auto_tick'] = 'Marcar "Factura por correo" automáticamente si el cliente tiene correo';
$lang['ei_pos_auto_tick_help'] = 'El cajero puede desmarcarla o escribir otra dirección.';
$lang['ei_test_section'] = 'Prueba';
$lang['ei_test_to'] = 'Enviar prueba a (vacío = su correo de usuario)';
$lang['ei_send_test'] = 'Enviar correo de prueba';
$lang['ei_test_help'] = 'Usa los valores de esta página, incluso antes de guardar. La prueba incluye su última factura como ejemplo.';
$lang['ei_save'] = 'Guardar';
$lang['ei_saved'] = 'Ajustes de correo guardados.';
$lang['ei_save_error'] = 'No se pudieron guardar los ajustes. Inténtelo de nuevo.';
$lang['ei_last_test_ok'] = 'Última prueba: enviada';
$lang['ei_last_test_failed'] = 'Última prueba: fallida';
$lang['ei_pending_count'] = '%d correo(s) esperando envío';
$lang['ei_sending'] = 'Enviando...';
$lang['ei_network_error'] = 'El servidor no responde. Compruebe la conexión a internet e inténtelo de nuevo.';
$lang['ei_test_ok'] = 'Correo de prueba enviado a %s. Revise la bandeja de entrada (y el spam).';
$lang['ei_test_subject'] = 'Correo de prueba';
$lang['ei_test_body'] = 'This is a test from your POS. If you can read this, bill emails will work. Below is a sample of how your customers will see their bill.';
$lang['ei_err_host'] = 'Escriba un servidor SMTP válido.';
$lang['ei_err_port'] = 'Escriba un puerto entre 1 y 65535.';
$lang['ei_err_encryption'] = 'Elija una opción de seguridad.';
$lang['ei_err_username'] = 'Escriba el correo electrónico (usuario).';
$lang['ei_err_password'] = 'Escriba la contraseña.';
$lang['ei_err_email_field'] = '%s no es un correo válido.';
$lang['ei_hint_gmail'] = 'Gmail rechazó el acceso. Use una contraseña de aplicación (Cuenta de Google > Seguridad > Contraseñas de aplicaciones).';
$lang['ei_hint_auth'] = 'El servidor rechazó el acceso. Revise el correo y la contraseña. Algunos proveedores requieren contraseña de aplicación o activar SMTP.';
$lang['ei_hint_connect'] = 'No se pudo conectar con el servidor de correo. Revise el servidor y el puerto. Muchos hostings bloquean los puertos 25 y 587; pruebe SSL con el puerto 465.';
$lang['ei_hint_tls'] = 'Falló la conexión segura. Pruebe la otra opción de seguridad (TLS con 587 o SSL con 465).';

// email log
$lang['ei_log_hint'] = 'Últimos 200 correos de facturas. Un envío fallido se reintenta 3 veces y luego queda como Fallido.';
$lang['ei_log_empty'] = 'Todavía no hay correos de facturas.';
$lang['ei_send_now'] = 'Enviar ahora';
$lang['ei_outlet'] = 'Local';
$lang['ei_to'] = 'Para';
$lang['ei_status'] = 'Estado';
$lang['ei_attempts'] = 'Intentos';
$lang['ei_details'] = 'Detalles';
$lang['ei_resend'] = 'reenviado';
$lang['ei_retry'] = 'Reintentar';
$lang['ei_retry_queued'] = 'El correo se enviará de nuevo.';
$lang['ei_retry_error'] = 'Este correo no se puede reintentar.';
$lang['ei_next_try'] = 'Próximo intento: %s';
$lang['ei_status_pending'] = 'En espera';
$lang['ei_status_sending'] = 'Enviando';
$lang['ei_status_sent'] = 'Enviado';
$lang['ei_status_failed'] = 'Fallido';

// POS and sales list
$lang['ei_email_bill'] = 'Factura por correo';
$lang['ei_email_placeholder'] = 'cliente@email.com';
$lang['ei_email_invoice'] = 'Enviar factura por correo';
$lang['ei_send'] = 'Enviar';
$lang['ei_customer_email'] = 'Correo del cliente';
$lang['ei_last_email'] = 'Último correo';
$lang['ei_pos_invalid'] = 'El correo de la factura no es válido. Corríjalo o desmarque "Factura por correo".';

// messages
$lang['ei_reload'] = 'Su sesión cambió. Recargue la página.';
$lang['ei_not_ready'] = 'La factura por correo aún no está instalada: ejecute Update/email_invoice_migration.sql en la base de datos.';
$lang['ei_not_enabled'] = 'El envío de facturas por correo está desactivado. Actívelo en Ajustes > Ajustes de correo.';
$lang['ei_invalid_email'] = 'Escriba un correo válido.';
$lang['ei_sale_not_found'] = 'Factura no encontrada.';
$lang['ei_already_queued'] = 'Esta factura ya se está enviando a esta dirección.';
$lang['ei_too_many'] = 'Esta factura se ha enviado demasiadas veces.';
$lang['ei_queue_error'] = 'No se pudo poner el correo en cola.';
$lang['ei_queued'] = 'La factura se enviará en un momento.';
$lang['ei_sent_to'] = 'Factura enviada a %s.';
$lang['ei_will_retry'] = 'Aún no enviado, se reintentará automáticamente.';
$lang['ei_password_unreadable'] = 'No se puede leer la contraseña guardada. Escríbala de nuevo en Ajustes de correo.';
$lang['ei_from_invalid'] = 'La dirección del remitente no es válida.';
