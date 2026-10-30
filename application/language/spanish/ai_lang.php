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
$lang['ai_reports_coming'] = 'El rendimiento del menú, la revisión de precios, compras vs consumo, pérdidas y ganancias, la previsión de ventas y las 5 mejoras principales aparecerán aquí cuando se ejecute el informe diario. Corrija primero los puntos en rojo de abajo: cada sección depende de ellos.';

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
$lang['ai_invalid_value'] = 'Revise los valores: el límite mensual debe estar entre 0 y 1000 USD.';
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
