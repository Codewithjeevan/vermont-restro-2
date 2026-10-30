<?php
/*
 | AI Insight module (docs/ai_insight_plan.md).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 | The AI-written insight text itself is always English (client decision).
 */

// module, menu and role screen
$lang['ai_insight'] = 'تحليلات الذكاء الاصطناعي';
$lang['ai_settings'] = 'إعدادات الذكاء الاصطناعي';
$lang['ai_companies'] = 'شركات الذكاء الاصطناعي';
$lang['ai_ask'] = 'اسأل الذكاء الاصطناعي';
$lang['ai_regenerate'] = 'إعادة إنشاء التقرير';

// page
$lang['ai_insight_hint'] = 'تحليل يومي لهذا الفرع: ما الذي يُباع، وما سعره منخفض، وأين تضيع البضاعة والأموال.';
$lang['ai_outlet'] = 'الفرع';
$lang['ai_not_enabled'] = 'تحليلات الذكاء الاصطناعي غير مفعّلة لشركتك. تواصل مع مسؤول النظام.';
$lang['ai_not_ready'] = 'تحليلات الذكاء الاصطناعي غير مثبتة بعد: شغّل Update/ai_insight_migration.sql.';
$lang['ai_reports_coming'] = 'أداء القائمة وفحص الأسعار والمشتريات مقابل الاستهلاك والأرباح والخسائر وتوقع المبيعات وأهم 5 تحسينات ستظهر هنا بعد تشغيل التقرير اليومي. أصلح البنود الحمراء أدناه أولاً: كل قسم يعتمد عليها.';

// data health
$lang['ai_data_health'] = 'جودة البيانات';
$lang['ai_data_health_hint'] = 'مدى اكتمال بيانات هذا الفرع. دقة التحليلات تعتمد على هذه البيانات.';
$lang['ai_check'] = 'الفحص';
$lang['ai_result'] = 'النتيجة';
$lang['ai_what_to_do'] = 'ما يجب فعله';
$lang['ai_used_for'] = 'مطلوب لـ';
$lang['ai_status_good'] = 'جيد';
$lang['ai_status_watch'] = 'يحتاج انتباه';
$lang['ai_status_missing'] = 'ناقص';
$lang['ai_status_na'] = 'لا ينطبق';
$lang['ai_checked_at'] = 'تم الفحص في %s';
$lang['ai_open'] = 'فتح';
$lang['ai_warehouse_note'] = 'لا توجد مبيعات في هذا الفرع خلال آخر %s يوماً، لذلك يُعامل كفرع مخزون فقط (مشتريات وتحويلات ومخزون).';
$lang['ai_h_recipe_cover'] = 'الأصناف المباعة التي لها تكلفة وصفة';
$lang['ai_h_recipe_cover_val'] = '%s%% من الكمية المباعة خلال آخر %s يوماً';
$lang['ai_h_recipe_cover_fix'] = 'أضف وصفة (المكونات والكميات) للأصناف المذكورة أدناه. رفع الوصفات يقبل حتى 1000 صنف في المرة.';
$lang['ai_h_no_sales'] = 'لا توجد مبيعات خلال آخر %s يوماً';
$lang['ai_h_menus_recipe'] = 'الأصناف التي لها وصفة';
$lang['ai_h_menus_recipe_val'] = '%s من %s صنفاً في هذا الفرع لها تكلفة وصفة';
$lang['ai_h_menus_recipe_fix'] = 'الأصناف بدون وصفة تظهر بتكلفة 0، فلا يمكن الحكم على هامش ربحها.';
$lang['ai_h_ing_price'] = 'أسعار المكونات';
$lang['ai_h_ing_price_val'] = '%s من %s مكوناً مستخدماً في الوصفات بدون سعر';
$lang['ai_h_ing_price_ok'] = 'كل المكونات المستخدمة في الوصفات (%s) لها سعر';
$lang['ai_h_ing_price_none'] = 'لا توجد وصفات بعد، فلا توجد أسعار مكونات للفحص';
$lang['ai_h_ing_price_fix'] = 'أدخل مشتريات لها، أو حدّد السعر في بطاقة المكون.';
$lang['ai_h_purchases'] = 'المشتريات المدخلة';
$lang['ai_h_purchases_val'] = '%s مشتريات خلال آخر %s يوماً (آخرها في %s)';
$lang['ai_h_purchases_old'] = 'لا مشتريات خلال آخر %s يوماً (آخرها في %s)';
$lang['ai_h_purchases_none'] = 'لم تُدخل أي مشتريات بعد';
$lang['ai_h_purchases_fix'] = 'أدخل كل عملية شراء بسعرها في يوم وصولها.';
$lang['ai_h_waste'] = 'الهدر المدخل';
$lang['ai_h_waste_val'] = '%s سجلات هدر خلال آخر %s يوماً (آخرها في %s)';
$lang['ai_h_waste_none'] = 'لم يُدخل أي هدر خلال آخر %s يوماً';
$lang['ai_h_waste_fix'] = 'سجّل الهدر يومياً. أسابيع بدون هدر تعني غالباً أنه لا يُسجَّل.';
$lang['ai_h_negative'] = 'مخزون بالسالب';
$lang['ai_h_negative_val'] = '%s مكونات مخزونها أقل من صفر';
$lang['ai_h_negative_ok'] = 'لا يوجد مكون أقل من صفر';
$lang['ai_h_negative_fix'] = 'المخزون السالب يعني أن مشتريات أو تحويلات لم تُدخل. أدخلها أو صحّح المخزون بتسوية مخزون.';
$lang['ai_h_history'] = 'سجل المبيعات';
$lang['ai_h_history_val'] = '%s أيام بها مبيعات خلال آخر %s يوماً (أول بيع في %s)';
$lang['ai_h_history_fix'] = 'التوقعات تبدأ بعد 4 أسابيع من المبيعات، و8 أسابيع تعطي دقة جيدة.';
$lang['ai_h_count'] = 'آخر جرد للمخزون';
$lang['ai_h_count_val'] = 'آخر تسوية مخزون في %s (منذ %s يوماً)';
$lang['ai_h_count_none'] = 'لم يُحفظ أي جرد (تسوية مخزون) بعد';
$lang['ai_h_count_fix'] = 'اجرد المخزون مرة في الشهر واحفظه كتسوية مخزون.';
$lang['ai_used_menu'] = 'أداء القائمة';
$lang['ai_used_price'] = 'فحص الأسعار';
$lang['ai_used_purchase'] = 'المشتريات مقابل الاستهلاك';
$lang['ai_used_pnl'] = 'الأرباح والخسائر';
$lang['ai_used_forecast'] = 'توقع المبيعات';
$lang['ai_used_waste'] = 'تحليل الهدر';
$lang['ai_used_shrink'] = 'فحص فقد المخزون';
$lang['ai_items_without_recipe'] = 'الأصناف الأكثر مبيعاً بدون تكلفة وصفة';
$lang['ai_qty_sold'] = 'الكمية المباعة';
$lang['ai_item'] = 'الصنف';
$lang['ai_stock'] = 'المخزون';
$lang['ai_save'] = 'حفظ';

// AI Companies (main admin)
$lang['ai_companies_hint'] = 'فعّل تحليلات الذكاء الاصطناعي لكل شركة وحدد حداً شهرياً للإنفاق. كل الشركات تستخدم مفتاح API واحداً.';
$lang['ai_company'] = 'الشركة';
$lang['ai_outlets'] = 'الفروع';
$lang['ai_enabled'] = 'مفعّل';
$lang['ai_monthly_limit'] = 'الحد الشهري (دولار)';
$lang['ai_report_model'] = 'نموذج التقرير اليومي';
$lang['ai_chat_model'] = 'نموذج المحادثة';
$lang['ai_spent_this_month'] = 'المصروف هذا الشهر (دولار)';
$lang['ai_calls_this_month'] = 'الطلبات هذا الشهر';
$lang['ai_saved'] = 'تم حفظ إعدادات الذكاء الاصطناعي.';
$lang['ai_invalid_request'] = 'تعذر التحقق من الطلب. أعد تحميل الصفحة وحاول مرة أخرى.';
$lang['ai_invalid_value'] = 'تحقق من القيم: الحد الشهري يجب أن يكون بين 0 و1000 دولار.';
$lang['ai_key_status'] = 'مفتاح API';
$lang['ai_key_set'] = 'مضبوط على الخادم';
$lang['ai_key_not_set'] = 'غير مضبوط: أضف ANTHROPIC_API_KEY إلى application/.env على الخادم';
$lang['ai_mock_on'] = 'وضع الاختبار (AI_MOCK=1): لا تُرسل طلبات حقيقية.';
$lang['ai_test_connection'] = 'اختبار الاتصال';
$lang['ai_test_ok'] = 'الاتصال يعمل: رد %s خلال %s ثانية.';
$lang['ai_test_failed'] = 'فشل الاتصال: %s';

// AI call results
$lang['ai_key_missing'] = 'خدمة الذكاء الاصطناعي غير مضبوطة على الخادم.';
$lang['ai_budget_reached'] = 'تم الوصول إلى حد الذكاء الاصطناعي لهذا الشهر لشركتك.';
$lang['ai_disabled_company'] = 'تحليلات الذكاء الاصطناعي متوقفة لهذه الشركة.';
$lang['ai_error_generic'] = 'تعذر الوصول إلى خدمة الذكاء الاصطناعي. الأرقام ما زالت معروضة؛ حاول لاحقاً.';
