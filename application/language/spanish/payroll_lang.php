<?php
/*
 | Payroll module (monthly salary from attendance).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 */

// module, menu and role screen
$lang['payroll'] = 'Nómina';
$lang['payroll_runs'] = 'Nómina mensual';
$lang['payroll_salary_setup'] = 'Configuración de salarios';
$lang['payroll_adjustments'] = 'Anticipos & ajustes';
$lang['payroll_leaves'] = 'Permisos';
$lang['payroll_holidays'] = 'Festivos';
$lang['payroll_reports'] = 'Informes de nómina';
$lang['payroll_settings'] = 'Ajustes de nómina';
$lang['payroll_generate'] = 'Generar nómina';
$lang['approve'] = 'Aprobar';
$lang['pay'] = 'Pagar';
$lang['payroll_tab_reports'] = 'Informes';
$lang['payroll_tab_setup'] = 'Configuración';
$lang['payroll_attendance_sheet'] = 'Hoja de asistencia';
$lang['payroll_salary_history'] = 'Historial salarial';
$lang['payroll_advance_balances'] = 'Saldos de anticipos';

// common words
$lang['payroll_attendance'] = 'Asistencia';
$lang['payroll_earnings'] = 'Devengos';
$lang['payroll_deductions'] = 'Deducciones';
$lang['payroll_net_salary'] = 'Salario neto';
$lang['payroll_net_pay'] = 'Neto a pagar';
$lang['payroll_net_payable'] = 'Total neto a pagar';
$lang['payroll_balance'] = 'Saldo';
$lang['payroll_basic'] = 'Básico';
$lang['payroll_allowances'] = 'Asignaciones';
$lang['payroll_bonus'] = 'Bonificación';
$lang['payroll_deduction'] = 'Deducción';
$lang['payroll_absence'] = 'Ausencia';
$lang['payroll_advance'] = 'Anticipo';
$lang['payroll_advances'] = 'Anticipos';
$lang['payroll_other'] = 'Otro';
$lang['payroll_overtime'] = 'Horas extra';
$lang['payroll_ot'] = 'HE';
$lang['payroll_ot_offday'] = 'HE / día libre';
$lang['payroll_offday_work'] = 'Trabajo en día libre / festivo';
$lang['payroll_hrs'] = 'h';
$lang['payroll_days_short'] = 'días';
$lang['payroll_total_earnings'] = 'Total devengado';
$lang['payroll_total_deductions'] = 'Total deducciones';
$lang['payroll_partial'] = 'Pagado en parte';
$lang['payroll_unpaid'] = 'No pagado';
$lang['payroll_status_draft'] = 'Borrador';
$lang['payroll_status_approved'] = 'Aprobada';
$lang['payroll_status_active'] = 'Activo';
$lang['payroll_status_inactive'] = 'Inactivo';
$lang['payroll_type_monthly'] = 'Mensual';
$lang['payroll_type_daily'] = 'Jornal diario';
$lang['payroll_type_hourly'] = 'Por hora';
$lang['payroll_per_monthly'] = 'mes';
$lang['payroll_per_daily'] = 'día';
$lang['payroll_per_hourly'] = 'hora';
$lang['payroll_mode_cash'] = 'Efectivo';
$lang['payroll_mode_bank'] = 'Transferencia bancaria';
$lang['payroll_auto'] = 'Auto';
$lang['payroll_off'] = 'desactivado';
$lang['payroll_open'] = 'Abrir';
$lang['payroll_day'] = 'Día';
$lang['payroll_from_date'] = 'Desde';
$lang['payroll_to_date'] = 'Hasta';
$lang['payroll_reason'] = 'Motivo';
$lang['payroll_signature'] = 'Firma';
$lang['payroll_not_set'] = 'Sin configurar';
$lang['payroll_set_up'] = 'Configurar';
$lang['payroll_posted'] = 'En gastos';
$lang['payroll_expense'] = 'Gasto';
$lang['payroll_all_employees'] = 'Todos los empleados';
$lang['payroll_all_months'] = 'Todos los meses';
$lang['payroll_all_types'] = 'Todos los tipos';
$lang['payroll_choose_employee'] = 'Elija un empleado para ver el salario de cada mes, los pagos y los anticipos.';

// day codes (attendance sheet, payslip)
$lang['payroll_code_P'] = 'Presente';
$lang['payroll_code_M'] = 'Presente, sin marcar salida';
$lang['payroll_code_H'] = 'Medio día';
$lang['payroll_code_A'] = 'Ausente';
$lang['payroll_code_L'] = 'Permiso retribuido';
$lang['payroll_code_U'] = 'Permiso no retribuido';
$lang['payroll_code_O'] = 'Descanso semanal';
$lang['payroll_code_HO'] = 'Festivo';
$lang['payroll_code_W'] = 'Trabajó en día libre / festivo';
$lang['payroll_code_X'] = 'Aún no contratado / de baja';
$lang['payroll_code_F'] = 'Día por venir';
$lang['payroll_present_short'] = 'P';
$lang['payroll_half_short'] = 'medio';
$lang['payroll_absent_short'] = 'A';
$lang['payroll_leave_short'] = 'L';
$lang['payroll_off_short'] = 'Libre';
$lang['payroll_holiday_short'] = 'Fest.';
$lang['payroll_offday_worked_short'] = 'W';
$lang['payroll_not_employed_short'] = 'sin contrato';

// attendance figures
$lang['payroll_days_in_month'] = 'Días del mes';
$lang['payroll_working_days'] = 'Días laborables';
$lang['payroll_present_days'] = 'Días de presencia';
$lang['payroll_half_days'] = 'Medios días';
$lang['payroll_absent_days'] = 'Días de ausencia';
$lang['payroll_leave_paid_unpaid'] = 'Permisos (retribuidos + no retribuidos)';
$lang['payroll_off_holidays'] = 'Libres / festivos';
$lang['payroll_offday_worked'] = 'Días libres trabajados';
$lang['payroll_worked_hours'] = 'Horas trabajadas';
$lang['payroll_overtime_hours'] = 'Horas extra';
$lang['payroll_missing_checkout'] = 'Días sin marcar salida';
$lang['payroll_not_employed'] = 'Días sin contrato';
$lang['payroll_per_day_rate'] = 'Tarifa por día';
$lang['payroll_calendar_days'] = 'Días';
$lang['payroll_full_day'] = 'Día completo';
$lang['payroll_half_day'] = 'Medio día';

// monthly payroll list
$lang['payroll_runs_hint'] = 'Una nómina por mes, calculada a partir de la asistencia y la configuración de salarios. Genere, revise, apruebe y luego pague.';
$lang['payroll_employees_on_payroll'] = 'Empleados en nómina';
$lang['payroll_employees_on_payroll_hint'] = 'con una configuración salarial activa';
$lang['payroll_monthly_gross'] = 'Bruto mensual (fijo)';
$lang['payroll_monthly_gross_hint'] = 'básico + asignaciones del personal mensual';
$lang['payroll_not_generated'] = 'No generada';
$lang['payroll_last_month'] = 'mes pasado';
$lang['payroll_balance_to_pay'] = 'pendiente de pago';
$lang['payroll_advances_open'] = 'Anticipos por recuperar';
$lang['payroll_advances_open_hint'] = 'entregados, aún no descontados';
$lang['payroll_no_setup_yet'] = 'Ningún empleado tiene salario todavía. Empiece por la configuración salarial de cada empleado.';
$lang['payroll_generate_title'] = 'Generar nómina';
$lang['payroll_generate_hint'] = 'Una nómina nueva empieza como borrador: vuelve a leer la asistencia cada vez que la abre, así puede corregir la asistencia y ver el resultado al instante. Apruébela cuando esté correcta.';
$lang['payroll_running'] = 'en curso';
$lang['payroll_generated_by'] = 'Generada por';
$lang['payroll_approved_by'] = 'Aprobada por';

// payroll sheet
$lang['payroll_print_sheet'] = 'Imprimir hoja';
$lang['payroll_all_payslips'] = 'Todos los recibos';
$lang['payroll_recalculate'] = 'Recalcular';
$lang['payroll_pay_all'] = 'Pagar todo';
$lang['payroll_approve_confirm'] = '¿Aprobar esta nómina? Queda bloqueada tras la aprobación y ya se pueden pagar los salarios.';
$lang['payroll_draft_info'] = 'Borrador: las cifras se calculan de nuevo a partir de la asistencia cada vez que se abre esta página. Use el lápiz para corregir una línea (días de ausencia, horas extra, bonificación, deducción) y luego apruebe.';
$lang['payroll_approved_info'] = 'Aprobada: esta nómina está bloqueada. Pague los salarios línea por línea o todos a la vez. Para cambiarla, borre sus pagos y reábrala.';
$lang['payroll_month_running_warn'] = 'Este mes aún está en curso: %d día(s) por venir todavía no son ausencias (al personal mensual se le pagan, al personal diario y por hora no). Apruebe cuando termine el mes.';
$lang['payroll_missing_checkout_warn'] = '%d día(s) tienen entrada pero no salida. Cuentan como %s sin horas extra (Ajustes de nómina). Corrija la hora de salida en Asistencia si hace falta.';
$lang['payroll_negative_warn'] = '%d empleado(s) tienen un salario neto negativo. Reduzca las deducciones o reparta el anticipo en más meses.';
$lang['payroll_missing_setup_warn'] = 'No incluidos en esta nómina (sin configuración salarial):';
$lang['payroll_edited'] = 'editado';
$lang['payroll_override_hint'] = 'Cambiado a mano en esta hoja; se mantiene al recalcular la asistencia.';
$lang['payroll_no_attendance'] = 'sin asistencia';
$lang['payroll_salary_payments'] = 'Pagos de salario';
$lang['payroll_no_payments'] = 'Aún no hay pagos.';
$lang['payroll_from_till_short'] = '(cajón)';
$lang['payroll_delete_payment_confirm'] = '¿Borrar este pago? Su gasto también se borra.';
$lang['payroll_basis_note'] = 'Reglas aplicadas';
$lang['payroll_absent_on'] = 'ausencia descontada de';
$lang['payroll_edit_line'] = 'Corregir línea';
$lang['payroll_edit_line_hint'] = 'Deje una casilla vacía para usar el valor de la asistencia. Los valores escritos aquí se mantienen al recalcular la nómina.';
$lang['payroll_from_attendance'] = 'Según asistencia';
$lang['payroll_reset_line'] = 'Volver a la asistencia';
$lang['payroll_pay_salary'] = 'Pagar salario';
$lang['payroll_pay_all_hint'] = 'Paga el saldo pendiente de cada empleado (%s) con el método de abajo.';
$lang['payroll_record_expense'] = 'Añadir a gastos';
$lang['payroll_from_till'] = 'Pagado del cajón de efectivo';
$lang['payroll_from_till_hint'] = 'Marque "pagado del cajón de efectivo" solo cuando el dinero sale del cajón de este mostrador: así la caja abierta lo cuenta.';
$lang['payroll_from_till_closed'] = 'La caja de este mostrador está cerrada, así que "pagado del cajón de efectivo" no se puede usar ahora.';

// salary setup
$lang['payroll_salary_setup_hint'] = 'Estructura salarial de cada empleado. Solo los empleados con una configuración activa entran en la nómina mensual.';
$lang['payroll_n_of_m_set'] = '%d de %d configurados';
$lang['payroll_salary_type'] = 'Tipo de salario';
$lang['payroll_basic_rate'] = 'Básico / tarifa';
$lang['payroll_gross_month'] = 'Bruto / mes';
$lang['payroll_weekly_off'] = 'Descanso semanal';
$lang['payroll_joining_date'] = 'Fecha de alta';
$lang['payroll_leaving_date'] = 'Fecha de baja';
$lang['payroll_left'] = 'baja el';
$lang['payroll_payment_mode'] = 'Forma de pago';
$lang['payroll_no_login'] = 'sin acceso';
$lang['payroll_employees_from_users'] = 'Los empleados son los usuarios de Cuenta y usuario > Usuarios. Añada allí al personal que no inicia sesión con "¿Iniciar sesión?" = No, e introduzca su asistencia a mano.';
$lang['payroll_pay_structure'] = 'Estructura salarial';
$lang['payroll_basic_salary'] = 'Salario básico';
$lang['payroll_basic_monthly'] = 'Salario básico mensual';
$lang['payroll_basic_daily'] = 'Tarifa por día';
$lang['payroll_basic_hourly'] = 'Tarifa por hora';
$lang['payroll_overtime_rate'] = 'Tarifa de horas extra / hora';
$lang['payroll_overtime_rate_hint'] = 'Vacío = se calcula a partir del básico y el multiplicador de horas extra.';
$lang['payroll_on_payroll'] = 'En nómina';
$lang['payroll_monthly_allowances'] = 'Asignaciones mensuales y deducción fija';
$lang['payroll_housing_allowance'] = 'Asignación de vivienda';
$lang['payroll_transport_allowance'] = 'Asignación de transporte';
$lang['payroll_food_allowance'] = 'Asignación de comida';
$lang['payroll_other_allowance'] = 'Otra asignación';
$lang['payroll_fixed_deduction'] = 'Deducción fija';
$lang['payroll_fixed_deduction_for'] = 'La deducción fija es por';
$lang['payroll_fixed_deduction_example'] = 'p. ej. alojamiento, seguro';
$lang['payroll_preview_gross'] = 'Bruto por mes';
$lang['payroll_preview_daily'] = 'Aprox. (26 días)';
$lang['payroll_preview_hourly'] = 'Aprox. (26 días x horas estándar)';
$lang['payroll_preview_perday'] = 'por día';
$lang['payroll_preview_ot'] = 'hora extra / hora';
$lang['payroll_work_calendar'] = 'Calendario laboral';
$lang['payroll_weekly_off_hint'] = 'Días libres cada semana. No son ausencias; los días libres trabajados se pueden pagar aparte (Ajustes de nómina).';
$lang['payroll_leaving_hint'] = 'Último día de trabajo. Déjelo vacío mientras siga contratado.';
$lang['payroll_payment_details'] = 'Datos de pago';
$lang['payroll_bank_name'] = 'Nombre del banco';
$lang['payroll_bank_account'] = 'N.º de cuenta / IBAN';
$lang['payroll_salary_saved'] = 'Configuración salarial guardada.';
$lang['payroll_leaving_before_joining'] = 'La fecha de baja es anterior a la fecha de alta.';

// advances & adjustments
$lang['payroll_adjustments_hint'] = 'Anticipos (efectivo entregado ahora, recuperado del salario), bonificaciones y asignaciones extra (se suman), deducciones como multas (se restan). Cada uno entra en la nómina del mes que elija.';
$lang['payroll_add_adjustment'] = 'Añadir anticipo / bonificación / deducción';
$lang['payroll_adj_advance'] = 'Anticipo de salario';
$lang['payroll_adj_bonus'] = 'Bonificación';
$lang['payroll_adj_allowance'] = 'Asignación extra';
$lang['payroll_adj_deduction'] = 'Deducción';
$lang['payroll_pay_month'] = 'Mes de nómina';
$lang['payroll_pay_month_hint'] = 'Para un anticipo: el primer mes en que se descuenta.';
$lang['payroll_installments'] = 'Cuotas';
$lang['payroll_installments_hint'] = 'Meses en los que recuperarlo.';
$lang['payroll_given_by'] = 'Entregado por';
$lang['payroll_recovery'] = 'Recuperación';
$lang['payroll_n_installments'] = '%d x %s';
$lang['payroll_one_installment'] = 'De una vez';
$lang['payroll_deducted'] = 'descontado del salario';
$lang['payroll_added'] = 'sumado al salario';
$lang['payroll_adjustment_invalid'] = 'Elija un empleado, un tipo, un importe mayor que cero y un mes de nómina.';
$lang['payroll_month_approved'] = 'La nómina de %s ya está aprobada. Reábrala primero.';

// leaves
$lang['payroll_leaves_hint'] = 'El permiso retribuido no se descuenta; el permiso no retribuido se descuenta como una ausencia. Los descansos semanales y festivos dentro de un permiso no se cuentan.';
$lang['payroll_add_leave'] = 'Añadir permiso';
$lang['payroll_leave_paid'] = 'Permiso retribuido';
$lang['payroll_leave_unpaid'] = 'Permiso no retribuido';
$lang['payroll_leave_rule'] = 'Un día con asistencia sigue contando como presente aunque esté dentro de un permiso.';
$lang['payroll_leave_invalid'] = 'Elija un empleado y un rango de fechas válido (un año como máximo).';
$lang['payroll_leave_overlap'] = 'Este empleado ya tiene un permiso en estas fechas.';

// holidays
$lang['payroll_holidays_hint'] = 'Festivos para todos: nunca son ausencia, y el trabajo en un festivo se puede pagar como un día extra.';
$lang['payroll_add_holiday'] = 'Añadir festivo';
$lang['payroll_same_day'] = 'mismo día';
$lang['payroll_holiday_example'] = 'p. ej. Día Nacional';
$lang['payroll_holiday_invalid'] = 'Indique una fecha, un nombre y como máximo 31 días.';

// settings
$lang['payroll_settings_hint'] = 'Cómo la asistencia se convierte en salario. Los cambios se aplican al instante a las nóminas en borrador; las nóminas aprobadas conservan sus cifras.';
$lang['payroll_monthly_salary_rules'] = 'Salario mensual';
$lang['payroll_month_days_basis'] = 'Tarifa por día = salario mensual dividido entre';
$lang['payroll_basis_calendar'] = 'Días del mes (28-31)';
$lang['payroll_basis_fixed30'] = 'Siempre 30 días';
$lang['payroll_basis_working'] = 'Días laborables del mes';
$lang['payroll_month_days_basis_hint'] = 'Se usa para descontar un día de ausencia y para pagar un día libre trabajado.';
$lang['payroll_absent_deduction_on'] = 'El día de ausencia se descuenta de';
$lang['payroll_on_gross'] = 'el bruto (básico + asignaciones)';
$lang['payroll_on_basic'] = 'solo el básico';
$lang['payroll_absent_deduction_on_hint'] = 'Tarifa por día = este importe / los días de arriba.';
$lang['payroll_pay_offday_work'] = 'Pagar el trabajo en descanso semanal / festivo como un día extra';
$lang['payroll_pay_offday_work_hint'] = 'Solo personal mensual; al personal diario y por hora se le paga igualmente cada día u hora trabajada.';
$lang['payroll_default_weekly_off'] = 'Descanso semanal por defecto para nuevas configuraciones salariales';
$lang['payroll_default_weekly_off_hint'] = 'Cada empleado puede tener su propio descanso semanal en la configuración salarial.';
$lang['payroll_attendance_rules'] = 'Asistencia';
$lang['payroll_standard_hours'] = 'Horas de un día completo';
$lang['payroll_standard_hours_hint'] = 'El trabajo por encima de esto son horas extra.';
$lang['payroll_half_day_hours'] = 'Medio día por debajo de (horas)';
$lang['payroll_half_day_hours_hint'] = 'Trabajar menos que esto = medio día.';
$lang['payroll_missing_checkout_rule'] = 'Un día con entrada pero sin salida cuenta como';
$lang['payroll_missing_checkout_rule_hint'] = 'El personal que nunca cierra sesión no tiene hora de salida. Sus horas se toman como un día estándar completo (o medio), sin horas extra.';
$lang['payroll_overtime_enabled'] = 'Pagar horas extra';
$lang['payroll_overtime_multiplier'] = 'Multiplicador de horas extra';
$lang['payroll_overtime_multiplier_hint'] = 'Tarifa por hora x este valor, p. ej. 1.25';
$lang['payroll_overtime_min_minutes'] = 'Ignorar horas extra por debajo de (minutos / día)';
$lang['payroll_overtime_min_minutes_hint'] = 'Unos minutos de más no son horas extra.';
$lang['payroll_payments_expenses'] = 'Pagos y gastos';
$lang['payroll_record_expense_default'] = 'Marcar "Añadir a gastos" por defecto al pagar';
$lang['payroll_expense_category'] = 'Categoría de gasto para salarios';
$lang['payroll_expense_category_auto'] = 'Salarios & jornales (se crea automáticamente)';
$lang['payroll_payslip_footer'] = 'Pie del recibo de nómina';
$lang['payroll_payslip_footer_example'] = 'p. ej. Este recibo de nómina se ha generado por ordenador.';

// reports
$lang['payroll_attendance_sheet_hint'] = 'Cada empleado y cada día del mes, leídos igual que en la nómina.';
$lang['payroll_attendance_sheet_note'] = 'Los empleados sin configuración salarial usan el descanso semanal por defecto. Corrija un día en Asistencia > Lista Asistencia y añada aquí permisos y festivos.';
$lang['payroll_not_on_payroll_hint'] = 'Sin configuración salarial: no se incluye en la nómina.';
$lang['payroll_salary_history_hint'] = 'El salario de cada mes de un empleado, con pagos y anticipos.';
$lang['payroll_monthly_salaries'] = 'Salarios mensuales';
$lang['payroll_no_lines'] = 'No hay nómina para este empleado en este año.';
$lang['payroll_advance_balance'] = 'Saldo de anticipo';
$lang['payroll_given'] = 'Entregado';
$lang['payroll_recovered'] = 'Recuperado';
$lang['payroll_no_advances'] = 'Sin anticipos.';
$lang['payroll_advance_balances_hint'] = 'Anticipos entregados y cuánto queda por recuperar del salario.';
$lang['payroll_advance_balances_note'] = 'Recuperado = deducciones de anticipo en nóminas aprobadas. Las nóminas en borrador aún no se cuentan.';
$lang['payroll_last_advance'] = 'Último anticipo';
$lang['payroll_give_advance'] = 'Dar anticipo';

// payslip, printed sheet
$lang['payroll_payslip'] = 'Recibo de nómina';
$lang['payroll_payslips'] = 'Recibos de nómina';
$lang['payroll_employee_no'] = 'N.º de empleado';
$lang['payroll_monthly_basic'] = 'básico mensual';
$lang['payroll_advance_recovery'] = 'Recuperación de anticipo';
$lang['payroll_no_deductions'] = 'Sin deducciones';
$lang['payroll_employer_signature'] = 'Firma del empleador';
$lang['payroll_employee_signature'] = 'Firma del empleado';
$lang['payroll_register'] = 'Libro de nóminas';
$lang['payroll_printed'] = 'impreso el';
$lang['payroll_prepared_by'] = 'Preparado por';
$lang['payroll_checked_by'] = 'Revisado por';

// messages
$lang['payroll_not_found'] = 'No encontrado.';
$lang['payroll_month_invalid'] = 'Elija este mes o uno anterior.';
$lang['payroll_already_generated'] = 'La nómina de este mes ya existe; aquí la tiene.';
$lang['payroll_no_eligible'] = 'Ningún empleado con configuración salarial activa para este mes.';
$lang['payroll_generated'] = 'Nómina generada como borrador. Revísela y luego apruébela.';
$lang['payroll_locked'] = 'Esta nómina está aprobada y bloqueada.';
$lang['payroll_recalculated'] = 'Nómina recalculada a partir de la asistencia.';
$lang['payroll_absent_too_many'] = 'Los días de ausencia no pueden superar los días del mes.';
$lang['payroll_approved_msg'] = 'Nómina aprobada. Ya se pueden pagar los salarios.';
$lang['payroll_reopen_has_payments'] = 'Borre los pagos de salario de este mes antes de reabrir la nómina.';
$lang['payroll_reopened_msg'] = 'Nómina reabierta como borrador.';
$lang['payroll_delete_only_draft'] = 'Solo se puede borrar una nómina en borrador. Reábrala primero.';
$lang['payroll_choose_method'] = 'Elija un método de pago.';
$lang['payroll_till_needs_register'] = 'Abra primero la caja de este mostrador, o desmarque "pagado del cajón de efectivo".';
$lang['payroll_pay_needs_approval'] = 'Apruebe la nómina antes de pagar salarios.';
$lang['payroll_amount_invalid'] = 'El importe debe ser mayor que cero y no superar el saldo.';
$lang['payroll_payment_saved'] = 'Pago de salario guardado.';
$lang['payroll_nothing_to_pay'] = 'No queda nada por pagar.';
$lang['payroll_paid_n'] = 'Salario pagado a %d empleado(s).';
