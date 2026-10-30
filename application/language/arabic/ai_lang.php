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

// report page
$lang['ai_period_28'] = 'آخر 28 يوماً';
$lang['ai_period_7'] = 'آخر 7 أيام';
$lang['ai_report_of'] = 'تقرير يوم %s';
$lang['ai_figures_to'] = 'الأرقام حتى %s';
$lang['ai_made_at'] = 'أُنشئ في %s';
$lang['ai_text_ai'] = 'كتبه الذكاء الاصطناعي';
$lang['ai_text_ai_partly'] = 'بعض الأجزاء تستخدم النص القياسي لأنه تعذر التحقق من أحد الأرقام.';
$lang['ai_text_standard'] = 'نص قياسي';
$lang['ai_text_pending'] = 'نص الذكاء الاصطناعي قيد الكتابة.';
$lang['ai_text_retry'] = 'لم تستجب خدمة الذكاء الاصطناعي؛ ستحاول مجدداً خلال ساعة.';
$lang['ai_text_unavailable'] = 'تعذر استخدام خدمة الذكاء الاصطناعي لهذا التقرير.';
$lang['ai_regen_left'] = 'متبقٍ %s من %s اليوم';
$lang['ai_regen_working'] = 'جارٍ إنشاء التقرير…';
$lang['ai_regen_busy'] = 'هذا التقرير قيد الإنشاء بالفعل. انتظر دقيقة ثم أعد تحميل الصفحة.';
$lang['ai_regen_limit'] = 'يمكن إعادة إنشاء التقرير %s مرات يومياً لكل فرع. حاول غداً.';
$lang['ai_regen_done'] = 'تمت إعادة إنشاء التقرير.';
$lang['ai_regen_done_standard'] = 'تمت إعادة إنشاء التقرير بالنص القياسي (نص الذكاء الاصطناعي غير متاح).';
$lang['ai_no_report'] = 'لا يوجد تقرير لهذا الفرع بعد. تُنشأ التقارير تلقائياً كل يوم بعد الساعة %s:00.';
$lang['ai_no_report_regen'] = 'اضغط إعادة إنشاء التقرير لإنشاء أول تقرير الآن.';
$lang['ai_old_report'] = 'هذا تقرير يوم %s. يُنشأ تقرير اليوم بعد الساعة %s:00.';
$lang['ai_note_ai'] = 'ملاحظة الذكاء الاصطناعي';
$lang['ai_note_standard'] = 'ملخص';
$lang['ai_gate_blocked'] = 'يحتاج هذا القسم إلى بيانات أكثر:';
$lang['ai_gate_limited'] = 'هذه الأرقام غير مكتملة:';
$lang['ai_gate_see_health'] = 'راجع جودة البيانات أدناه.';
$lang['ai_show_all'] = 'عرض الكل (%s)';
$lang['ai_show_less'] = 'عرض أقل';
$lang['ai_vs_prev'] = 'مقارنة بالفترة السابقة';
$lang['ai_against'] = 'مقابل';
$lang['ai_new'] = 'جديد';
$lang['ai_days'] = '%s يوم';
$lang['ai_days_unit'] = 'يوم';
$lang['ai_k_yesterday'] = 'أمس';
$lang['ai_k_orders'] = '%s طلب';
$lang['ai_k_vs_last_week'] = 'مقارنة بنفس اليوم من الأسبوع الماضي';
$lang['ai_k_target'] = 'الهدف %s%%';

// top 5
$lang['ai_top5'] = 'أهم 5 تحسينات';
$lang['ai_top5_hint'] = 'مرتبة حسب قيمة كل منها شهرياً';
$lang['ai_top5_none'] = 'لم يُعثر على شيء عاجل في آخر 28 يوماً.';
$lang['ai_per_month'] = 'حوالي %s شهرياً';
$lang['ai_conf_high'] = 'ثقة عالية';
$lang['ai_conf_medium'] = 'ثقة متوسطة';
$lang['ai_conf_low'] = 'ثقة منخفضة';
$lang['ai_it_underpriced'] = 'سعر منخفض';
$lang['ai_it_loss_item'] = 'يُباع بخسارة';
$lang['ai_it_overbuy'] = 'شراء زائد';
$lang['ai_it_waste'] = 'هدر مرتفع';
$lang['ai_it_discounts'] = 'خصومات';
$lang['ai_it_sales_drop'] = 'انخفاض المبيعات';
$lang['ai_it_item_drop'] = 'صنف تقل مبيعاته';
$lang['ai_it_expense_jump'] = 'زيادة المصروفات';
$lang['ai_it_dog_item'] = 'صنف ضعيف';
$lang['ai_it_unsold_item'] = 'لا يُباع';

// menu performance
$lang['ai_c_star'] = 'مطلوب، ربحه جيد';
$lang['ai_c_plowhorse'] = 'مطلوب، ربحه قليل';
$lang['ai_c_puzzle'] = 'ربحه جيد، مبيعاته قليلة';
$lang['ai_c_dog'] = 'مبيعاته قليلة، ربحه قليل';
$lang['ai_c_none'] = 'بدون تكلفة وصفة';
$lang['ai_menu_avg_margin'] = 'متوسط الربح لكل طبق: %s. يُصنَّف كل صنف حسب ربحه مقارنة بهذا المتوسط وحسب عدد مرات بيعه.';
$lang['ai_col_sold'] = 'المباع';
$lang['ai_free'] = '+%s مجاناً';
$lang['ai_col_share'] = 'حصة المبيعات';
$lang['ai_col_avg_price'] = 'متوسط السعر';
$lang['ai_col_plate_cost'] = 'تكلفة الطبق';
$lang['ai_col_margin'] = 'الربح لكل طبق';
$lang['ai_col_total_margin'] = 'إجمالي الربح';
$lang['ai_col_vs_prev'] = 'مقارنة بالسابق';
$lang['ai_col_group'] = 'المجموعة';
$lang['ai_unsold'] = 'لم يُبع خلال الفترة (%s)';

// price check
$lang['ai_price_hint'] = 'تكلفة الطعام المستهدفة %s%%، الأسعار المقترحة مقرّبة للأعلى إلى %s';
$lang['ai_b_loss'] = 'يُباع بخسارة';
$lang['ai_b_critical'] = 'أكثر من 50%';
$lang['ai_b_high'] = 'أعلى بكثير من الهدف';
$lang['ai_b_watch'] = 'أعلى قليلاً من الهدف';
$lang['ai_b_good'] = 'ضمن الهدف';
$lang['ai_col_price'] = 'السعر';
$lang['ai_col_net_price'] = 'السعر بدون ضريبة';
$lang['ai_col_food_cost'] = 'تكلفة الطعام';
$lang['ai_col_status'] = 'الحالة';
$lang['ai_col_sold_28'] = 'المباع (28 يوماً)';
$lang['ai_col_suggested'] = 'السعر المقترح';
$lang['ai_col_gain'] = 'الإضافي (28 يوماً)';
$lang['ai_price_foot'] = 'تكلفة الطعام = تكلفة الطبق / السعر بدون ضريبة. الخط الداكن على كل شريط هو الهدف. الإضافي = ما كان السعر المقترح سيضيفه على نفس الكمية.';

// purchase vs usage
$lang['ai_t_bought'] = 'المشتريات';
$lang['ai_t_used'] = 'المستهلك';
$lang['ai_t_wasted'] = 'الهدر';
$lang['ai_t_shrink'] = 'فروقات الجرد';
$lang['ai_t_excess'] = 'مخزون زائد مشترى';
$lang['ai_t_high_waste'] = '%s بهدر مرتفع';
$lang['ai_t_overbought'] = '%s بشراء زائد';
$lang['ai_col_ingredient'] = 'المكوّن';
$lang['ai_col_bought'] = 'المشترى';
$lang['ai_col_transfers'] = 'التحويلات وارد / صادر';
$lang['ai_col_used'] = 'المستهلك';
$lang['ai_col_wasted'] = 'الهدر';
$lang['ai_col_stock_now'] = 'المخزون الحالي';
$lang['ai_col_days_cover'] = 'يكفي المخزون';
$lang['ai_col_flags'] = 'تنبيهات';
$lang['ai_f_overbuy'] = 'شراء زائد';
$lang['ai_f_waste'] = 'هدر مرتفع';
$lang['ai_f_negative'] = 'أقل من صفر';
$lang['ai_purchase_foot'] = 'الكميات بوحدة الاستهلاك. الشراء الزائد = مخزون يكفي أكثر من %s يوماً (الخط الداكن على الشريط) أو مشتريات أعلى بكثير من الاستهلاك والهدر.';

// profit & loss
$lang['ai_col_this'] = 'هذه الفترة';
$lang['ai_col_prev'] = 'الفترة السابقة';
$lang['ai_col_change'] = 'التغير';
$lang['ai_l_orders'] = 'الطلبات';
$lang['ai_l_gross'] = 'المبيعات مع الضريبة';
$lang['ai_l_vat'] = 'ضريبة القيمة المضافة';
$lang['ai_l_tips'] = 'الإكراميات (ليست دخلاً)';
$lang['ai_l_net_sales'] = 'صافي المبيعات';
$lang['ai_l_mess'] = 'دخل الوجبات الشهرية';
$lang['ai_l_refunds'] = 'المرتجعات';
$lang['ai_l_food'] = 'تكلفة الطعام';
$lang['ai_l_waste'] = 'الهدر';
$lang['ai_l_salaries'] = 'الرواتب المدفوعة';
$lang['ai_l_expenses'] = 'المصروفات';
$lang['ai_l_net_profit'] = 'صافي الربح';
$lang['ai_l_included'] = 'مخصوم مسبقاً من صافي المبيعات';
$lang['ai_l_discounts'] = 'الخصومات';
$lang['ai_l_complimentary'] = 'أصناف مجانية';
$lang['ai_l_staff'] = 'وجبات الموظفين';
$lang['ai_l_avg_ticket'] = 'متوسط الفاتورة';
$lang['ai_pnl_foot'] = 'تكلفة الطعام هي تكلفة وصفات ما تم بيعه، بما فيها الوجبات الشهرية. الرواتب هي مدفوعات الرواتب لهذا الفرع؛ المصروفات المدفوعة عبر الرواتب لا تُحسب مرتين.';

// sales prediction
$lang['ai_forecast_title'] = 'توقع المبيعات: الأيام السبعة القادمة';
$lang['ai_fc_line'] = 'يُتوقع بيع حوالي %s صنف بقيمة %s. أكثر الأيام ازدحاماً: %s.';
$lang['ai_fc_accuracy'] = 'خلال آخر 7 أيام ابتعد التوقع بنسبة %s.';
$lang['ai_fc_items'] = 'الكمية المتوقعة لكل صنف';
$lang['ai_col_total'] = 'الإجمالي';
$lang['ai_fc_purchase'] = 'المشتريات المقترحة للأيام السبعة القادمة';
$lang['ai_fc_no_purchase'] = 'المخزون الحالي يكفي للأيام السبعة القادمة.';
$lang['ai_col_need'] = 'المطلوب';
$lang['ai_col_safety'] = 'مخزون الأمان';
$lang['ai_col_in_stock'] = 'في المخزون';
$lang['ai_col_buy'] = 'اشترِ';
$lang['ai_col_est_cost'] = 'التكلفة التقديرية';
$lang['ai_fc_foot'] = 'المطلوب = المبيعات المتوقعة × كميات الوصفات، مع الهدر المعتاد. مخزون الأمان = أيام استهلاك إضافية (إعدادات الذكاء الاصطناعي). مقرّب لأعلى إلى وحدات شراء كاملة.';

// AI Settings
$lang['ai_settings_hint'] = 'تُطبق من التقرير القادم: غداً بعد الساعة %s:00، أو اضغط إعادة إنشاء التقرير في صفحة AI Insight.';
$lang['ai_s_target'] = 'تكلفة الطعام المستهدفة';
$lang['ai_s_target_help'] = 'الأصناف التي تتجاوزها تحصل على سعر مقترح. معظم المطاعم تستهدف 25-35%.';
$lang['ai_s_rounding'] = 'تقريب الأسعار المقترحة إلى';
$lang['ai_s_rounding_help'] = 'مثلاً 0.50 يحوّل 21.14 إلى 21.50.';
$lang['ai_s_overbuy'] = 'شراء زائد عندما تزيد المشتريات بأكثر من';
$lang['ai_s_overbuy_help'] = 'عن الاستهلاك والهدر خلال الفترة.';
$lang['ai_s_cover'] = 'شراء زائد عندما يكفي المخزون أكثر من';
$lang['ai_s_cover_help'] = 'بمعدل الاستهلاك اليومي الحالي.';
$lang['ai_s_safety'] = 'مخزون الأمان في المشتريات المقترحة';
$lang['ai_s_safety_help'] = 'أيام استهلاك إضافية تبقى في المخزون.';
$lang['ai_settings_saved'] = 'تم حفظ إعدادات الذكاء الاصطناعي. تُطبق من التقرير القادم.';
$lang['ai_settings_invalid'] = 'تحقق من القيم: الهدف 15-60%، الشراء الزائد 5-200%، المخزون 3-60 يوماً، مخزون الأمان 0-7 أيام.';
