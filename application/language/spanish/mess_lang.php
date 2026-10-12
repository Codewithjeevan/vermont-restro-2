<?php
/*
 | Mess module (monthly tiffin subscriptions).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 */

// module, menu and role screen
$lang['mess'] = 'Comedor';
$lang['mess_dashboard'] = 'Comedor hoy';
$lang['mess_today'] = 'Hoy';
$lang['mess_plans'] = 'Planes de comedor';
$lang['mess_plan'] = 'Plan';
$lang['mess_members'] = 'Miembros del comedor';
$lang['mess_member'] = 'Miembro';
$lang['mess_subscriptions'] = 'Suscripciones';
$lang['mess_subscription'] = 'Suscripción';
$lang['mess_payments'] = 'Pagos del comedor';
$lang['mess_payment'] = 'Pago del comedor';
$lang['mess_daily_menu'] = 'Menú del día';
$lang['mess_meal_register'] = 'Registro de comidas';
$lang['mess_extras'] = 'Extras';
$lang['mess_extra'] = 'Extra';
$lang['mess_daily_sheet'] = 'Hoja diaria';
$lang['mess_reports'] = 'Informes del comedor';
$lang['mess_settings'] = 'Ajustes del comedor';
$lang['mess_collection'] = 'Cobro del comedor';
$lang['mess_dues'] = 'Pendientes de pago';
$lang['mess_expiring'] = 'Por vencer / Renovar';
$lang['reopen'] = 'Reabrir';
$lang['mess_tab_members'] = 'Miembros';
$lang['mess_tab_renewals'] = 'Renovaciones';
$lang['mess_tab_payments'] = 'Pagos';
$lang['mess_tab_plans'] = 'Planes';
$lang['mess_tab_reports'] = 'Informes';
$lang['mess_tab_settings'] = 'Ajustes';
$lang['mess_tab_group_daily'] = 'Diario';
$lang['mess_tab_group_accounts'] = 'Miembros y cuentas';
$lang['mess_tab_group_setup'] = 'Configuración inicial';

// slots, diet, mode
$lang['mess_slot'] = 'Comida';
$lang['mess_slots'] = 'Turnos de comida';
$lang['mess_breakfast'] = 'Desayuno';
$lang['mess_lunch'] = 'Almuerzo';
$lang['mess_dinner'] = 'Cena';
$lang['mess_diet'] = 'Dieta';
$lang['mess_veg'] = 'Vegetariana';
$lang['mess_nonveg'] = 'No vegetariana';
$lang['mess_both'] = 'Ambas';
$lang['mess_mode'] = 'Modo';
$lang['mess_pickup'] = 'Recoger';
$lang['mess_delivery'] = 'Entrega';
$lang['mess_persons'] = 'Personas';
$lang['mess_meals'] = 'Comidas';
$lang['mess_meals_persons'] = 'Comidas x personas';
$lang['mess_meals_persons_hint'] = 'Personas por comida. "Almuerzo + 2 Cena" = Almuerzo 1, Cena 2.';

// status
$lang['mess_status_active'] = 'Activa';
$lang['mess_status_expiring'] = 'Por vencer';
$lang['mess_status_expired'] = 'Vencida';
$lang['mess_status_upcoming'] = 'Próxima';
$lang['mess_status_cancelled'] = 'Cancelada';
$lang['mess_status_paused'] = 'En pausa hoy';
$lang['mess_status_inactive'] = 'Inactivo';
$lang['mess_current'] = 'Actuales';
$lang['mess_renewed'] = 'Renovada';

// plans
$lang['mess_add_plan'] = 'Añadir plan';
$lang['mess_edit_plan'] = 'Editar plan';
$lang['mess_plan_name'] = 'Nombre del plan';
$lang['mess_price_per_person'] = 'Precio por persona / ciclo';
$lang['mess_per_meal_rate'] = 'Por comida (prorrata)';
$lang['mess_cycle'] = 'Ciclo';
$lang['mess_cycle_days'] = 'Días del ciclo';
$lang['mess_included_meals'] = 'Comidas incluidas';
$lang['mess_plan_hint'] = 'Un plan es una tarifa, no un contrato. El personal puede escribir un precio acordado en cada suscripción. Precio por comida = precio / (días del ciclo x comidas al día), se usa para ciclos cortos.';
$lang['mess_plan_need_slot'] = 'Marque al menos una comida.';
$lang['mess_plan_in_use'] = 'Este plan lo usan suscripciones, se desactivó en lugar de borrarse.';

// members
$lang['mess_add_member'] = 'Añadir miembro';
$lang['mess_edit_member'] = 'Editar miembro';
$lang['mess_member_code'] = 'Código de miembro';
$lang['mess_phone2'] = 'Teléfono 2';
$lang['mess_building_room'] = 'Edificio / habitación';
$lang['mess_default_diet'] = 'Dieta preferida';
$lang['mess_default_mode'] = 'Recoger o entrega';
$lang['mess_member_hint'] = 'Un miembro es una persona o una habitación, se crea una vez. Cada mes es una nueva suscripción del mismo miembro.';
$lang['mess_phone_used_by'] = 'Este teléfono ya lo usa';
$lang['mess_member_has_records'] = 'Este miembro tiene suscripciones o pagos. Se marcó inactivo en lugar de borrarse.';
$lang['mess_last_cycle_end'] = 'Fin del último ciclo';
$lang['mess_last_payment'] = 'Último pago';
$lang['mess_no_subscription'] = 'Sin suscripción';
$lang['mess_member_profile'] = 'Perfil del miembro';
$lang['mess_ledger'] = 'Estado de cuenta';
$lang['mess_charge'] = 'Cargo';
$lang['mess_paid'] = 'Pagado';
$lang['mess_balance_due'] = 'Saldo pendiente';
$lang['mess_advance_credit'] = 'Saldo a favor';
$lang['mess_print_statement'] = 'Imprimir estado de cuenta';
$lang['mess_statement'] = 'Estado de cuenta del miembro';
$lang['mess_whatsapp_reminder'] = 'Recordatorio WhatsApp';
$lang['mess_whatsapp'] = 'WhatsApp';
$lang['mess_no_entries'] = 'Sin movimientos';
$lang['mess_active_members'] = 'Miembros activos';

// subscriptions
$lang['mess_new_subscription'] = 'Nueva suscripción';
$lang['mess_edit_subscription'] = 'Editar suscripción';
$lang['mess_renew'] = 'Renovar';
$lang['mess_renew_cycle'] = 'Renovar ciclo';
$lang['mess_renewing_from'] = 'Renovando el ciclo que termina el';
$lang['mess_suggested_price'] = 'Precio sugerido';
$lang['mess_agreed_price'] = 'Precio acordado';
$lang['mess_price_note'] = 'Nota del precio';
$lang['mess_price_note_ph'] = 'p. ej. cliente antiguo, tarifa de conductor';
$lang['mess_delivery_address'] = 'Dirección de entrega';
$lang['mess_end_date_auto'] = 'La fecha de fin = fecha de inicio + días del ciclo';
$lang['mess_advance_now'] = 'Cobrar ahora';
$lang['mess_advance_now_hint'] = 'Opcional. Va a la caja de hoy como cualquier pago del comedor.';
$lang['mess_save_subscription'] = 'Guardar suscripción';
$lang['mess_overlap_warning'] = 'Aviso: este miembro ya tiene un ciclo en estas fechas';
$lang['mess_meals_required'] = 'Indique personas para al menos una comida.';
$lang['mess_dates_invalid'] = 'La fecha de fin debe ser el día de inicio o posterior.';
$lang['mess_agreed_price_required'] = 'El precio acordado es obligatorio.';
$lang['mess_cancel_subscription'] = 'Cancelar suscripción';
$lang['mess_cancel_confirm'] = '¿Cancelar esta suscripción? Su cargo se quita del estado de cuenta y ya no se esperan comidas.';
$lang['mess_sub_has_payments'] = 'Esta suscripción tiene pagos. Se canceló en lugar de borrarse; los pagos quedan en el estado de cuenta como saldo a favor.';
$lang['mess_pause'] = 'Pausa';
$lang['mess_pauses'] = 'Pausas (ausencia)';
$lang['mess_add_pause'] = 'Añadir pausa';
$lang['mess_pause_hint'] = 'Miembro de viaje: la fecha de fin se mueve los mismos días y no se esperan comidas esos días.';
$lang['mess_pause_dates_invalid'] = 'El fin de la pausa debe ser el día de su inicio o posterior.';
$lang['mess_pause_outside_cycle'] = 'La pausa debe empezar dentro de las fechas de la suscripción.';
$lang['mess_pause_overlaps'] = 'Esta pausa se solapa con otra pausa.';
$lang['mess_pause_added'] = 'Pausa guardada, fecha de fin movida.';
$lang['mess_paused_days'] = 'Días de pausa';
$lang['mess_days_left'] = 'Días restantes';
$lang['mess_ends_today'] = 'termina hoy';
$lang['mess_ends_in'] = 'termina en';
$lang['mess_ended'] = 'terminó hace';
$lang['mess_expiring_list'] = 'Por vencer pronto';
$lang['mess_expired_not_renewed'] = 'Vencidas, no renovadas';
$lang['mess_expired_not_renewed_hint'] = 'Ciclos terminados en los últimos 45 días sin un ciclo más nuevo para el miembro.';
$lang['mess_within_days'] = 'en (días)';

// payments
$lang['mess_receive_payment'] = 'Recibir pago';
$lang['mess_payment_type'] = 'Tipo de movimiento';
$lang['mess_type_payment'] = 'Pago (entra dinero)';
$lang['mess_type_refund'] = 'Reembolso (sale dinero)';
$lang['mess_type_adjustment'] = 'Ajuste (sin dinero)';
$lang['mess_adjustment'] = 'Ajuste';
$lang['mess_adjust_credit'] = 'Abono: reducir lo pendiente (descuento, condonación)';
$lang['mess_adjust_debit'] = 'Cargo: añadir un importe';
$lang['mess_against_subscription'] = 'Para la suscripción';
$lang['mess_general_advance'] = 'General (sin suscripción)';
$lang['mess_amount_positive'] = 'El importe debe ser mayor que cero.';
$lang['mess_payment_method_required'] = 'El método de pago es obligatorio para un pago o un reembolso.';
$lang['mess_payment_saved'] = 'Pago guardado.';
$lang['mess_print_receipt'] = 'Imprimir recibo';
$lang['mess_receipt'] = 'Recibo del comedor';
$lang['mess_receipt_title'] = 'RECIBO DE PAGO DEL COMEDOR';
$lang['mess_received_from'] = 'Recibido de';
$lang['mess_received_by'] = 'Recibido por';
$lang['mess_balance_after'] = 'Saldo tras este pago';
$lang['mess_thank_you'] = 'Gracias';
$lang['mess_payment_closed_register'] = 'Este pago pertenece a una caja ya cerrada. Solo el administrador puede borrarlo.';
$lang['mess_register_needed'] = 'Los pagos del comedor necesitan una caja abierta (Ajustes del comedor). Abra primero la caja.';
$lang['mess_refund_adjust_admin'] = 'Reembolso y ajuste requieren administrador o el permiso de borrar pagos del comedor.';
$lang['mess_collection_today'] = 'Cobrado hoy';
$lang['mess_total_dues'] = 'Total pendiente';
$lang['mess_members_with_due'] = 'miembros con pendiente';
$lang['mess_payments_count'] = 'pagos';

// register card
$lang['mess_collection_plus'] = 'Cobro del comedor (+)';
$lang['mess_collection_register_hint'] = 'Pagos del comedor tomados en esta caja, incluidos en el efectivo en mano';

// daily menu
$lang['mess_menu_for'] = 'Menú del';
$lang['mess_dishes'] = 'Platos';
$lang['mess_dish'] = 'Plato';
$lang['mess_add_dish'] = 'Añadir plato';
$lang['mess_portion_per_person'] = 'Ración / persona';
$lang['mess_buffer'] = 'Margen';
$lang['mess_expected_persons'] = 'Personas esperadas';
$lang['mess_planned_portions'] = 'Raciones previstas';
$lang['mess_copy_yesterday'] = 'Copiar ayer';
$lang['mess_copy_last_week'] = 'Copiar el mismo día de la semana pasada';
$lang['mess_copy_from'] = 'Copiar desde fecha';
$lang['mess_copy_done'] = 'platos copiados';
$lang['mess_copy_confirm'] = '¿Reemplazar el menú de este día por la copia?';
$lang['mess_menu_text'] = 'Texto del menú (WhatsApp)';
$lang['mess_menu_missing'] = 'falta el menú';
$lang['mess_menu_saved'] = 'Menú guardado';
$lang['mess_no_recipe'] = 'sin receta';
$lang['mess_no_recipe_hint'] = 'Este plato no tiene receta en el menú, el stock no se moverá por él.';
$lang['mess_free_text_dish'] = 'o escriba un nombre de plato';
$lang['mess_pick_food_menu'] = '-- Plato del menú (con receta) --';
$lang['mess_expected_hint'] = 'Las personas esperadas salen en vivo de las suscripciones del día menos las ausencias.';
$lang['mess_copied_to_clipboard'] = 'Copiado';
$lang['mess_copy_text'] = 'Copiar texto';

// meal register
$lang['mess_expected'] = 'Esperadas';
$lang['mess_served'] = 'Servido';
$lang['mess_pending'] = 'Pendiente';
$lang['mess_not_taken'] = 'No recogido';
$lang['mess_skipped'] = 'Ausente';
$lang['mess_partial'] = 'Parcial';
$lang['mess_undo'] = 'Deshacer';
$lang['mess_mark_all_served'] = 'Marcar todo lo pendiente como Servido';
$lang['mess_mark_all_not_taken'] = 'Marcar todo lo pendiente como No recogido';
$lang['mess_bulk_confirm'] = '¿Marcar todas las filas pendientes?';
$lang['mess_search_member'] = 'Buscar nombre / teléfono / edificio';
$lang['mess_print_tick_sheet'] = 'Imprimir hoja de control';
$lang['mess_no_members_expected'] = 'Ningún miembro espera esta comida. Revise la fecha, la comida o las suscripciones.';
$lang['mess_skip_ahead'] = 'Ausencia avisada';
$lang['mess_skip_ahead_hint'] = '"Mañana no vengo": la comida sale del recuento y la cocina prepara menos.';
$lang['mess_skip_saved'] = 'Ausencia guardada';
$lang['mess_skip_not_expected'] = 'Este miembro no espera esa comida en esa fecha.';
$lang['mess_skip_already_marked'] = 'Esa comida ya está marcada y no puede marcarse ausente.';
$lang['mess_served_at'] = 'Servido';
$lang['mess_flip_diet'] = 'Cambiar Vegetariana / No vegetariana para hoy';
$lang['mess_attendance_last_30'] = 'Comidas de los últimos 30 días';
$lang['mess_future_only_skip'] = 'Una comida de fecha futura solo puede marcarse ausente.';
$lang['mess_invalid_action'] = 'Acción desconocida.';

// extras
$lang['mess_add_extra'] = 'Añadir extra';
$lang['mess_settle'] = 'Liquidación';
$lang['mess_pay_now'] = 'Pagar ahora';
$lang['mess_on_account'] = 'A cuenta';
$lang['mess_on_account_hint'] = 'Se suma al saldo del miembro y se cobra con la mensualidad.';
$lang['mess_pay_now_hint'] = 'Se crea ahora un pago del comedor que llega a la caja de esta noche.';
$lang['mess_custom_item'] = 'Artículo libre';
$lang['mess_item_name'] = 'Nombre del artículo';
$lang['mess_unit_price'] = 'Precio unitario';
$lang['mess_extra_saved'] = 'Extra guardado';
$lang['mess_mark_delivered'] = 'Marcar entregado';
$lang['mess_extra_status_requested'] = 'Pedido';
$lang['mess_extra_status_delivered'] = 'Entregado';
$lang['mess_extra_status_cancelled'] = 'Cancelado';
$lang['mess_cancel_extra'] = 'Cancelar extra';
$lang['mess_extras_today'] = 'Extras de hoy';
$lang['mess_extra_paid_cancel_note'] = 'Este extra se pagó al momento. Cancelarlo deja el pago como saldo a favor (reembólselo desde Recibir pago si hace falta).';

// daily sheet
$lang['mess_sheet_title'] = 'HOJA DIARIA DEL COMEDOR';
$lang['mess_headcount'] = 'Recuento';
$lang['mess_production_plan'] = 'Plan de producción';
$lang['mess_ingredient_need'] = 'Necesidad de ingredientes (receta x raciones)';
$lang['mess_stock'] = 'Stock';
$lang['mess_short'] = 'falta';
$lang['mess_ok'] = 'ok';
$lang['mess_member_checklist'] = 'Lista de miembros';
$lang['mess_sign'] = 'Firma';
$lang['mess_printed_by'] = 'impreso por';
$lang['mess_stock_shortage'] = 'Falta de stock';
$lang['mess_delivery_route'] = 'Lista de entregas';
$lang['mess_skipped_advance'] = 'Ausencias avisadas';
$lang['mess_portions'] = 'Raciones';

// today dashboard
$lang['mess_expected_today'] = 'esperadas';
$lang['mess_menu_today'] = 'Menú de hoy';
$lang['mess_needs_attention'] = 'Requiere atención';
$lang['mess_open_register'] = 'Abrir registro de comidas';
$lang['mess_quick_actions'] = 'Acciones rápidas';
$lang['mess_nothing_to_show'] = 'Nada que mostrar';

// reports
$lang['mess_attendance_matrix'] = 'Matriz de asistencia';
$lang['mess_attendance_matrix_hint'] = 'S servido, P parcial, N no recogido, K ausente, - pendiente, vacío sin suscripción';
$lang['mess_daily_served_summary'] = 'Resumen diario servido';
$lang['mess_collection_report'] = 'Informe de cobros';
$lang['mess_by_method'] = 'Por método de pago';
$lang['mess_member_x_day'] = 'Miembro x día';

// settings
$lang['mess_settings_saved'] = 'Ajustes del comedor guardados';
$lang['mess_slot_on'] = 'Activo';
$lang['mess_from_time'] = 'Desde';
$lang['mess_to_time'] = 'Hasta';
$lang['mess_default_cycle_days'] = 'Días de ciclo por defecto';
$lang['mess_expiring_days'] = 'Aviso de vencimiento (días antes del fin)';
$lang['mess_skip_credit_policy'] = 'Política de abono por ausencia';
$lang['mess_skip_credit_none'] = 'Sin abono, las ausencias solo reducen la cocina';
$lang['mess_skip_credit_informed'] = 'Abono si avisa antes de la comida (ajuste manual)';
$lang['mess_skip_credit_always'] = 'Siempre abono (ajuste manual)';
$lang['mess_require_open_register'] = 'Exigir caja abierta para los pagos del comedor';
$lang['mess_require_open_register_hint'] = 'Recomendado. El dinero cobrado sin caja abierta nunca llega a un cierre, así que el efectivo en mano no cuadrará.';
$lang['mess_member_code_prefix'] = 'Prefijo del código de miembro';
$lang['mess_whatsapp_country_code'] = 'Código de país WhatsApp';
$lang['mess_whatsapp_templates'] = 'Plantillas de texto WhatsApp';
$lang['mess_template_menu'] = 'Menú del día';
$lang['mess_template_due'] = 'Recordatorio de pendiente';
$lang['mess_template_expiry'] = 'Recordatorio de vencimiento';
$lang['mess_template_vars'] = 'Variables: {name} {amount} {currency} {end_date} {slot} {date} {menu}';
$lang['mess_roles_hint'] = 'Dé a cada rol solo lo que necesita en Ajustes > Roles: Caja = Registro de comidas, Extras, Pagos, Hoy. Cocina = Hoy, Menú del día, Hoja diaria.';

// day close (stock)
$lang['mess_day_close'] = 'Cierre de comida';
$lang['mess_cooked'] = 'Cocinado';
$lang['mess_leftover'] = 'Sobrante';
$lang['mess_leftover_goes_to'] = 'El sobrante va a';
$lang['mess_waste'] = 'Merma';
$lang['mess_carry'] = 'Pasar a la siguiente comida';
$lang['mess_staff_meal'] = 'Comida del personal';
$lang['mess_food_cost'] = 'Coste de comida';
$lang['mess_waste_cost'] = 'Coste de merma';
$lang['mess_cost_per_meal'] = 'Coste por comida servida';
$lang['mess_served_portions'] = 'Servido (est.)';
$lang['mess_close_and_post'] = 'Cerrar y pasar al stock';
$lang['mess_close_confirm'] = '¿Cerrar esta comida? Las raciones cocinadas x receta salen del stock y el sobrante tirado se registra como merma.';
$lang['mess_close_hint'] = 'Confirme lo que la cocina cocinó realmente y lo que sobró. El cierre pasa el consumo de ingredientes (cocinado menos tirado, x receta) al stock y el sobrante tirado como merma, así las pantallas de stock se mueven exactamente por lo cocinado. El sobrante pasado a la siguiente comida o al personal cuenta como usado. Los platos sin receta no mueven stock.';
$lang['mess_already_closed'] = 'Esta comida ya está cerrada. Reábrala primero.';
$lang['mess_close_failed'] = 'No se pudo guardar el cierre, no se registró nada. Inténtelo de nuevo.';
$lang['mess_close_future'] = 'Una comida se cierra el mismo día o después.';
$lang['mess_closed_ok'] = 'Comida cerrada, stock actualizado.';
$lang['mess_reopened_ok'] = 'Comida reabierta, se revirtió el movimiento de stock.';
$lang['mess_reopen_confirm'] = '¿Reabrir esta comida? Se revertirán el consumo de stock y la merma registrados.';
$lang['mess_closed_by'] = 'por';
$lang['mess_pending_warning'] = 'Aún hay miembros pendientes en el registro de comidas. Márquelos primero para que las cifras sean correctas.';
$lang['mess_open_waste'] = 'Abrir la merma';
$lang['mess_status_closed'] = 'Cerrada';
$lang['mess_status_open'] = 'Abierta';
$lang['mess_day_close_report'] = 'Cierres y coste de comida';
$lang['mess_meals_served'] = 'Comidas servidas';
$lang['mess_not_closed'] = 'Sin cerrar';
