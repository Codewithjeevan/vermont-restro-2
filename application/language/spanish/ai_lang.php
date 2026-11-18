<?php
/*
 | AI Insight module (docs/ai_insight_plan.md).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 | The AI-written insight text itself is always English (client decision).
 */

// module, menu and role screen
$lang['ai_insight'] = 'Análisis IA';
$lang['ai_settings'] = 'Ajustes de IA';
$lang['ai_companies'] = 'Empresas IA';
$lang['ai_ask'] = 'Preguntar a la IA';
$lang['ai_regenerate'] = 'Regenerar informe';

// page
$lang['ai_insight_hint'] = 'Análisis diario de este local: qué se vende, qué está por debajo de su precio y dónde se pierden stock y dinero.';
$lang['ai_outlet'] = 'Local';
$lang['ai_not_enabled'] = 'El análisis IA no está activado para su empresa. Consulte a su administrador.';
$lang['ai_not_ready'] = 'El análisis IA aún no está instalado: ejecute Update/ai_insight_migration.sql.';

// data health
$lang['ai_data_health'] = 'Calidad de los datos';
$lang['ai_data_health_hint'] = 'Qué tan completos están los datos de este local. El análisis solo es tan bueno como estos datos.';
$lang['ai_check'] = 'Control';
$lang['ai_result'] = 'Resultado';
$lang['ai_what_to_do'] = 'Qué hacer';
$lang['ai_used_for'] = 'Necesario para';
$lang['ai_status_good'] = 'Bien';
$lang['ai_status_watch'] = 'Requiere atención';
$lang['ai_status_missing'] = 'Falta';
$lang['ai_status_na'] = 'No aplica';
$lang['ai_checked_at'] = 'Revisado el %s';
$lang['ai_open'] = 'Abrir';
$lang['ai_warehouse_note'] = 'No hubo ventas en este local en los últimos %s días, así que se trata como un local solo de stock (compras, transferencias y stock).';
$lang['ai_h_recipe_cover'] = 'Artículos vendidos con coste de receta';
$lang['ai_h_recipe_cover_val'] = '%s%% de la cantidad vendida en los últimos %s días';
$lang['ai_h_recipe_cover_fix'] = 'Añada una receta (ingredientes y cantidades) a los artículos listados abajo. Subir recetas acepta hasta 1000 menús a la vez.';
$lang['ai_h_no_sales'] = 'Sin ventas en los últimos %s días';
$lang['ai_h_menus_recipe'] = 'Menús con receta';
$lang['ai_h_menus_recipe_val'] = '%s de %s menús de este local tienen coste de receta';
$lang['ai_h_menus_recipe_fix'] = 'Los menús sin receta muestran coste 0, por lo que no se puede juzgar su margen.';
$lang['ai_h_ing_price'] = 'Precios de ingredientes';
$lang['ai_h_ing_price_val'] = '%s de %s ingredientes usados en recetas no tienen precio';
$lang['ai_h_ing_price_ok'] = 'Los %s ingredientes usados en recetas tienen precio';
$lang['ai_h_ing_price_none'] = 'Aún no hay recetas, así que no hay precios de ingredientes que revisar';
$lang['ai_h_ing_price_fix'] = 'Registre una compra para ellos o ponga el precio en el ingrediente.';
$lang['ai_h_purchases'] = 'Compras registradas';
$lang['ai_h_purchases_val'] = '%s compras en los últimos %s días (última el %s)';
$lang['ai_h_purchases_old'] = 'Ninguna compra en los últimos %s días (última el %s)';
$lang['ai_h_purchases_none'] = 'Aún no hay compras registradas';
$lang['ai_h_purchases_fix'] = 'Registre cada compra con su precio el día que llega.';
$lang['ai_h_waste'] = 'Mermas registradas';
$lang['ai_h_waste_val'] = '%s mermas en los últimos %s días (última el %s)';
$lang['ai_h_waste_none'] = 'Ninguna merma registrada en los últimos %s días';
$lang['ai_h_waste_fix'] = 'Registre las mermas a diario. Semanas sin mermas suelen significar que no se registran.';
$lang['ai_h_negative'] = 'Stock negativo';
$lang['ai_h_negative_val'] = '%s ingredientes tienen stock por debajo de cero';
$lang['ai_h_negative_ok'] = 'Ningún ingrediente por debajo de cero';
$lang['ai_h_negative_fix'] = 'Un stock por debajo de cero significa que faltan compras o transferencias. Regístrelas o corrija el stock con un ajuste de inventario.';
$lang['ai_h_history'] = 'Historial de ventas';
$lang['ai_h_history_val'] = '%s días con ventas en los últimos %s días (primera venta el %s)';
$lang['ai_h_history_fix'] = 'Las previsiones empiezan tras 4 semanas de ventas; 8 semanas dan buena precisión.';
$lang['ai_h_count'] = 'Último recuento de stock';
$lang['ai_h_count_val'] = 'Último ajuste de inventario el %s (hace %s días)';
$lang['ai_h_count_none'] = 'Aún no hay recuento (ajuste de inventario) guardado';
$lang['ai_h_count_fix'] = 'Cuente el stock una vez al mes y guárdelo como ajuste de inventario.';
$lang['ai_used_menu'] = 'Rendimiento del menú';
$lang['ai_used_price'] = 'Revisión de precios';
$lang['ai_used_purchase'] = 'Compras vs consumo';
$lang['ai_used_pnl'] = 'Pérdidas y ganancias';
$lang['ai_used_forecast'] = 'Previsión de ventas';
$lang['ai_used_waste'] = 'Análisis de mermas';
$lang['ai_used_shrink'] = 'Control de pérdida de stock';
$lang['ai_items_without_recipe'] = 'Artículos más vendidos sin coste de receta';
$lang['ai_qty_sold'] = 'Cant. vendida';
$lang['ai_item'] = 'Artículo';
$lang['ai_stock'] = 'Stock';
$lang['ai_save'] = 'Guardar';

// AI Companies (main admin)
$lang['ai_companies_hint'] = 'Active el análisis IA por empresa y fije un límite de gasto mensual. Todas las empresas comparten una sola clave API.';
$lang['ai_company'] = 'Empresa';
$lang['ai_outlets'] = 'Locales';
$lang['ai_enabled'] = 'Activo';
$lang['ai_monthly_limit'] = 'Límite mensual (USD)';
$lang['ai_report_model'] = 'Modelo del informe diario';
$lang['ai_chat_model'] = 'Modelo del chat';
$lang['ai_spent_this_month'] = 'Gastado este mes (USD)';
$lang['ai_calls_this_month'] = 'Llamadas este mes';
$lang['ai_saved'] = 'Ajustes de IA guardados.';
$lang['ai_invalid_request'] = 'No se pudo verificar la solicitud. Recargue la página e inténtelo de nuevo.';
$lang['ai_invalid_value'] = 'Revise los valores: el límite mensual debe estar entre 0 y 1000 USD, y las preguntas por día entre 0 y 200.';
$lang['ai_key_status'] = 'Clave API';
$lang['ai_key_set'] = 'Configurada en el servidor';
$lang['ai_key_not_set'] = 'No configurada: añada ANTHROPIC_API_KEY a application/.env en el servidor';
$lang['ai_mock_on'] = 'Modo de prueba (AI_MOCK=1): no se hacen llamadas reales.';
$lang['ai_test_connection'] = 'Probar conexión';
$lang['ai_test_ok'] = 'Conexión correcta: %s respondió en %s segundos.';
$lang['ai_test_failed'] = 'Fallo de conexión: %s';

// AI call results
$lang['ai_key_missing'] = 'El servicio de IA no está configurado en el servidor.';
$lang['ai_budget_reached'] = 'Se alcanzó el límite de IA de este mes para su empresa.';
$lang['ai_disabled_company'] = 'El análisis IA está desactivado para esta empresa.';
$lang['ai_error_generic'] = 'No se pudo contactar con el servicio de IA. Las cifras siguen visibles; inténtelo más tarde.';

// report page
$lang['ai_period_28'] = 'Últimos 28 días';
$lang['ai_period_7'] = 'Últimos 7 días';
$lang['ai_report_of'] = 'Informe del %s';
$lang['ai_figures_to'] = 'cifras hasta el %s';
$lang['ai_made_at'] = 'creado el %s';
$lang['ai_text_ai'] = 'Escrito por IA';
$lang['ai_text_ai_partly'] = 'Algunas partes usan el texto estándar porque una cifra no se pudo comprobar.';
$lang['ai_text_standard'] = 'Texto estándar';
$lang['ai_text_pending'] = 'El texto de IA todavía se está escribiendo.';
$lang['ai_text_retry'] = 'El servicio de IA no respondió; lo intentará de nuevo dentro de una hora.';
$lang['ai_text_unavailable'] = 'No se pudo usar el servicio de IA para este informe.';
$lang['ai_regen_left'] = 'quedan %s de %s hoy';
$lang['ai_regen_working'] = 'Creando el informe…';
$lang['ai_regen_busy'] = 'Este informe ya se está creando. Espere un minuto y recargue la página.';
$lang['ai_regen_limit'] = 'Un informe se puede volver a crear %s veces al día por local. Inténtelo mañana.';
$lang['ai_regen_done'] = 'El informe se volvió a crear.';
$lang['ai_regen_done_standard'] = 'El informe se volvió a crear con el texto estándar (el texto de IA no estaba disponible).';
$lang['ai_no_report'] = 'Este local aún no tiene informe. Los informes se crean automáticamente cada día después de las %s:00.';
$lang['ai_no_report_regen'] = 'Pulse Regenerar informe para crear el primero ahora.';
$lang['ai_old_report'] = 'Este es el informe del %s. El informe de hoy se crea después de las %s:00.';
$lang['ai_note_ai'] = 'Nota de IA';
$lang['ai_note_standard'] = 'Resumen';
$lang['ai_gate_blocked'] = 'Esta sección necesita más datos:';
$lang['ai_gate_limited'] = 'Estas cifras están incompletas:';
$lang['ai_gate_see_health'] = 'Vea Calidad de los datos más abajo.';
$lang['ai_show_all'] = 'Mostrar todo (%s)';
$lang['ai_show_less'] = 'Mostrar menos';
$lang['ai_vs_prev'] = 'vs el periodo anterior';
$lang['ai_against'] = 'frente a';
$lang['ai_new'] = 'Nuevo';
$lang['ai_days'] = '%s días';
$lang['ai_days_unit'] = 'días';
$lang['ai_k_yesterday'] = 'Ayer';
$lang['ai_k_orders'] = '%s pedidos';
$lang['ai_k_vs_last_week'] = 'vs el mismo día de la semana pasada';
$lang['ai_k_target'] = 'Objetivo %s%%';

// top 5
$lang['ai_top5'] = '5 mejoras principales';
$lang['ai_top5_hint'] = 'Ordenadas por lo que vale cada una al mes';
$lang['ai_top5_none'] = 'No se encontró nada urgente en los últimos 28 días.';
$lang['ai_per_month'] = 'unos %s al mes';
$lang['ai_conf_high'] = 'Confianza alta';
$lang['ai_conf_medium'] = 'Confianza media';
$lang['ai_conf_low'] = 'Confianza baja';
$lang['ai_it_underpriced'] = 'Precio bajo';
$lang['ai_it_loss_item'] = 'Se vende con pérdida';
$lang['ai_it_overbuy'] = 'Compra excesiva';
$lang['ai_it_waste'] = 'Mermas altas';
$lang['ai_it_discounts'] = 'Descuentos';
$lang['ai_it_sales_drop'] = 'Caída de ventas';
$lang['ai_it_item_drop'] = 'Artículo vende menos';
$lang['ai_it_expense_jump'] = 'Aumento de gastos';
$lang['ai_it_dog_item'] = 'Artículo débil';
$lang['ai_it_unsold_item'] = 'No se vende';

// menu performance
$lang['ai_c_star'] = 'Popular, deja buen margen';
$lang['ai_c_plowhorse'] = 'Popular, deja poco margen';
$lang['ai_c_puzzle'] = 'Buen margen, se vende poco';
$lang['ai_c_dog'] = 'Se vende poco, deja poco margen';
$lang['ai_c_none'] = 'Sin costo de receta';
$lang['ai_menu_avg_margin'] = 'Margen medio por plato: %s. Cada artículo se agrupa por su margen frente a esta media y por cuánto se vende.';
$lang['ai_col_sold'] = 'Vendidos';
$lang['ai_free'] = '+%s gratis';
$lang['ai_col_share'] = 'Parte de ventas';
$lang['ai_col_avg_price'] = 'Precio medio';
$lang['ai_col_plate_cost'] = 'Costo por plato';
$lang['ai_col_margin'] = 'Margen por plato';
$lang['ai_col_total_margin'] = 'Margen total';
$lang['ai_col_vs_prev'] = 'vs antes';
$lang['ai_col_group'] = 'Grupo';
$lang['ai_unsold'] = 'Sin ventas en el periodo (%s)';

// price check
$lang['ai_price_hint'] = 'Costo de alimentos objetivo %s%%, precios sugeridos redondeados hacia arriba a %s';
$lang['ai_b_loss'] = 'Se vende con pérdida';
$lang['ai_b_critical'] = 'Más del 50%';
$lang['ai_b_high'] = 'Muy por encima del objetivo';
$lang['ai_b_watch'] = 'Algo por encima del objetivo';
$lang['ai_b_good'] = 'En el objetivo';
$lang['ai_col_price'] = 'Precio';
$lang['ai_col_net_price'] = 'Precio sin IVA';
$lang['ai_col_food_cost'] = 'Costo de alimentos';
$lang['ai_col_status'] = 'Estado';
$lang['ai_col_sold_28'] = 'Vendidos (28 días)';
$lang['ai_col_suggested'] = 'Precio sugerido';
$lang['ai_col_gain'] = 'Extra (28 días)';
$lang['ai_price_foot'] = 'Costo de alimentos = costo por plato / precio sin IVA. La línea oscura de cada barra es el objetivo. Extra = lo que el precio sugerido habría añadido con la misma cantidad.';

// purchase vs usage
$lang['ai_t_bought'] = 'Comprado';
$lang['ai_t_used'] = 'Consumido';
$lang['ai_t_wasted'] = 'Merma';
$lang['ai_t_shrink'] = 'Diferencias de inventario';
$lang['ai_t_excess'] = 'Stock comprado de más';
$lang['ai_t_high_waste'] = '%s con merma alta';
$lang['ai_t_overbought'] = '%s comprados de más';
$lang['ai_col_ingredient'] = 'Ingrediente';
$lang['ai_col_bought'] = 'Comprado';
$lang['ai_col_transfers'] = 'Transferencias entrada / salida';
$lang['ai_col_used'] = 'Consumido';
$lang['ai_col_wasted'] = 'Merma';
$lang['ai_col_stock_now'] = 'Stock actual';
$lang['ai_col_days_cover'] = 'El stock dura';
$lang['ai_col_flags'] = 'Alertas';
$lang['ai_f_overbuy'] = 'Comprado de más';
$lang['ai_f_waste'] = 'Merma alta';
$lang['ai_f_negative'] = 'Bajo cero';
$lang['ai_purchase_foot'] = 'Cantidades en la unidad de consumo. Comprado de más = más de %s días de stock (la línea oscura de la barra) o compras muy por encima de lo consumido y la merma.';

// profit & loss
$lang['ai_col_this'] = 'Este periodo';
$lang['ai_col_prev'] = 'Periodo anterior';
$lang['ai_col_change'] = 'Cambio';
$lang['ai_l_orders'] = 'Pedidos';
$lang['ai_l_gross'] = 'Ventas con IVA';
$lang['ai_l_vat'] = 'IVA';
$lang['ai_l_tips'] = 'Propinas (no son ingresos)';
$lang['ai_l_net_sales'] = 'Ventas netas';
$lang['ai_l_mess'] = 'Ingresos de comedor';
$lang['ai_l_refunds'] = 'Devoluciones';
$lang['ai_l_food'] = 'Costo de alimentos';
$lang['ai_l_waste'] = 'Merma';
$lang['ai_l_salaries'] = 'Sueldos pagados';
$lang['ai_l_expenses'] = 'Gastos';
$lang['ai_l_net_profit'] = 'Beneficio neto';
$lang['ai_l_included'] = 'Ya descontado de las ventas netas';
$lang['ai_l_discounts'] = 'Descuentos';
$lang['ai_l_complimentary'] = 'Artículos de cortesía';
$lang['ai_l_staff'] = 'Comidas del personal';
$lang['ai_l_avg_ticket'] = 'Ticket medio';
$lang['ai_pnl_foot'] = 'El costo de alimentos es el costo de receta de lo vendido, comidas de comedor incluidas. Los sueldos son los pagos de nómina de este local; los gastos pagados por nómina no se cuentan dos veces.';

// sales prediction
$lang['ai_forecast_title'] = 'Previsión de ventas: próximos 7 días';
$lang['ai_fc_line'] = 'Se esperan unos %s artículos por %s. Día de más venta: %s.';
$lang['ai_fc_accuracy'] = 'En los últimos 7 días la previsión se desvió un %s.';
$lang['ai_fc_items'] = 'Cantidad esperada por artículo';
$lang['ai_col_total'] = 'Total';
$lang['ai_fc_purchase'] = 'Compra sugerida para los próximos 7 días';
$lang['ai_fc_no_purchase'] = 'El stock disponible cubre los próximos 7 días.';
$lang['ai_col_need'] = 'Necesario';
$lang['ai_col_safety'] = 'Stock de seguridad';
$lang['ai_col_in_stock'] = 'En stock';
$lang['ai_col_buy'] = 'Comprar';
$lang['ai_col_est_cost'] = 'Costo estimado';
$lang['ai_fc_foot'] = 'Necesario = ventas esperadas x cantidades de receta, más la merma habitual. Stock de seguridad = días extra de consumo (Ajustes de IA). Redondeado a unidades de compra completas.';

// AI Settings
$lang['ai_settings_hint'] = 'Se aplica desde el próximo informe: mañana después de las %s:00, o pulse Regenerar informe en la página AI Insight.';
$lang['ai_s_target'] = 'Costo de alimentos objetivo';
$lang['ai_s_target_help'] = 'Los artículos por encima reciben un precio sugerido. La mayoría de restaurantes apunta al 25-35%.';
$lang['ai_s_rounding'] = 'Redondear precios sugeridos a';
$lang['ai_s_rounding_help'] = 'Por ejemplo, 0.50 convierte 21.14 en 21.50.';
$lang['ai_s_overbuy'] = 'Compra excesiva cuando lo comprado supera en más de';
$lang['ai_s_overbuy_help'] = 'lo consumido y la merma del periodo.';
$lang['ai_s_cover'] = 'Compra excesiva cuando el stock dura más de';
$lang['ai_s_cover_help'] = 'con el consumo diario actual.';
$lang['ai_s_safety'] = 'Stock de seguridad en las compras sugeridas';
$lang['ai_s_safety_help'] = 'Días extra de consumo a tener en stock.';
$lang['ai_settings_saved'] = 'Ajustes de IA guardados. Se aplican desde el próximo informe.';
$lang['ai_settings_invalid'] = 'Revise los valores: objetivo 15-60%, compra excesiva 5-200%, stock 3-60 días, stock de seguridad 0-7 días.';

// Ask AI
$lang['ai_chat_daily'] = 'Preguntas / usuario / día';
$lang['ai_chat_hint'] = 'Las respuestas salen solo de los datos de este local.';
$lang['ai_chat_new'] = 'Nueva conversación';
$lang['ai_chat_close'] = 'Cerrar';
$lang['ai_chat_send'] = 'Enviar';
$lang['ai_chat_placeholder'] = 'Pregunte sobre ventas, precios, stock, compras o costos';
$lang['ai_chat_thinking'] = 'Revisando sus datos…';
$lang['ai_chat_left'] = 'quedan %s de %s preguntas hoy';
$lang['ai_chat_left_chat'] = 'quedan %s de %s en esta conversación';
$lang['ai_chat_s1'] = '¿Por qué es alto mi costo de alimentos?';
$lang['ai_chat_s2'] = '¿Qué debo comprar para los próximos 3 días?';
$lang['ai_chat_s3'] = '¿Qué artículos debería quitar del menú?';
$lang['ai_chat_s4'] = '¿Cómo fueron las ventas la semana pasada frente a la anterior?';
$lang['ai_chat_off'] = 'Ask AI está desactivado para su empresa.';
$lang['ai_chat_empty'] = 'Escriba una pregunta.';
$lang['ai_chat_too_long'] = 'La pregunta debe tener menos de %s caracteres.';
$lang['ai_chat_session_full'] = 'Esta conversación llegó a %s preguntas. Pulse Nueva conversación para seguir.';
$lang['ai_chat_busy'] = 'Su pregunta anterior todavía se está respondiendo.';
$lang['ai_chat_day_limit'] = 'Ya usó sus %s preguntas de hoy. Inténtelo mañana.';
$lang['ai_chat_refused'] = 'Lo siento, no puedo responder a eso. Pregunte sobre las ventas, el menú, el stock, las compras o los costos de este local.';
$lang['ai_chat_failed'] = 'No se pudo completar la respuesta. Inténtelo de nuevo.';
$lang['ai_chat_reload'] = 'Recargue la página e inténtelo de nuevo.';
