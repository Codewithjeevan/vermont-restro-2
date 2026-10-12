<?php
/*
 | Mess module (monthly tiffin subscriptions).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 */

// module, menu and role screen
$lang['mess'] = 'الميس';
$lang['mess_dashboard'] = 'الميس اليوم';
$lang['mess_today'] = 'اليوم';
$lang['mess_plans'] = 'باقات الميس';
$lang['mess_plan'] = 'الباقة';
$lang['mess_members'] = 'أعضاء الميس';
$lang['mess_member'] = 'العضو';
$lang['mess_subscriptions'] = 'الاشتراكات';
$lang['mess_subscription'] = 'الاشتراك';
$lang['mess_payments'] = 'مدفوعات الميس';
$lang['mess_payment'] = 'دفعة الميس';
$lang['mess_daily_menu'] = 'القائمة اليومية';
$lang['mess_meal_register'] = 'سجل الوجبات';
$lang['mess_extras'] = 'الإضافات';
$lang['mess_extra'] = 'إضافة';
$lang['mess_daily_sheet'] = 'الورقة اليومية';
$lang['mess_reports'] = 'تقارير الميس';
$lang['mess_settings'] = 'إعدادات الميس';
$lang['mess_collection'] = 'تحصيل الميس';
$lang['mess_dues'] = 'المستحقات';
$lang['mess_expiring'] = 'قارب على الانتهاء / تجديد';
$lang['reopen'] = 'إعادة فتح';
$lang['mess_tab_members'] = 'الأعضاء';
$lang['mess_tab_renewals'] = 'التجديدات';
$lang['mess_tab_payments'] = 'المدفوعات';
$lang['mess_tab_plans'] = 'الباقات';
$lang['mess_tab_reports'] = 'التقارير';
$lang['mess_tab_settings'] = 'الإعدادات';
$lang['mess_tab_group_daily'] = 'يومي';
$lang['mess_tab_group_accounts'] = 'الأعضاء والحسابات';
$lang['mess_tab_group_setup'] = 'إعداد لمرة واحدة';

// slots, diet, mode
$lang['mess_slot'] = 'الوجبة';
$lang['mess_slots'] = 'فترات الوجبات';
$lang['mess_breakfast'] = 'الفطور';
$lang['mess_lunch'] = 'الغداء';
$lang['mess_dinner'] = 'العشاء';
$lang['mess_diet'] = 'النظام الغذائي';
$lang['mess_veg'] = 'نباتي';
$lang['mess_nonveg'] = 'غير نباتي';
$lang['mess_both'] = 'كلاهما';
$lang['mess_mode'] = 'الطريقة';
$lang['mess_pickup'] = 'استلام';
$lang['mess_delivery'] = 'توصيل';
$lang['mess_persons'] = 'الأشخاص';
$lang['mess_meals'] = 'الوجبات';
$lang['mess_meals_persons'] = 'الوجبات × الأشخاص';
$lang['mess_meals_persons_hint'] = 'عدد الأشخاص لكل وجبة. "غداء + 2 عشاء" = غداء 1، عشاء 2.';

// status
$lang['mess_status_active'] = 'نشط';
$lang['mess_status_expiring'] = 'قارب على الانتهاء';
$lang['mess_status_expired'] = 'منتهي';
$lang['mess_status_upcoming'] = 'قادم';
$lang['mess_status_cancelled'] = 'ملغى';
$lang['mess_status_paused'] = 'متوقف اليوم';
$lang['mess_status_inactive'] = 'غير نشط';
$lang['mess_current'] = 'الحالي';
$lang['mess_renewed'] = 'تم التجديد';

// plans
$lang['mess_add_plan'] = 'إضافة باقة';
$lang['mess_edit_plan'] = 'تعديل الباقة';
$lang['mess_plan_name'] = 'اسم الباقة';
$lang['mess_price_per_person'] = 'السعر للشخص / الدورة';
$lang['mess_per_meal_rate'] = 'سعر الوجبة (نسبي)';
$lang['mess_cycle'] = 'الدورة';
$lang['mess_cycle_days'] = 'أيام الدورة';
$lang['mess_included_meals'] = 'الوجبات المشمولة';
$lang['mess_plan_hint'] = 'الباقة قائمة أسعار وليست عقداً. يمكن للموظف كتابة سعر متفق عليه لكل اشتراك. سعر الوجبة = السعر / (أيام الدورة × الوجبات يومياً) ويُستخدم للدورات القصيرة.';
$lang['mess_plan_need_slot'] = 'اختر وجبة واحدة على الأقل.';
$lang['mess_plan_in_use'] = 'هذه الباقة مستخدمة في اشتراكات، تم إيقافها بدلاً من حذفها.';

// members
$lang['mess_add_member'] = 'إضافة عضو';
$lang['mess_edit_member'] = 'تعديل العضو';
$lang['mess_member_code'] = 'رمز العضو';
$lang['mess_phone2'] = 'هاتف 2';
$lang['mess_building_room'] = 'المبنى / الغرفة';
$lang['mess_default_diet'] = 'النظام الغذائي المفضل';
$lang['mess_default_mode'] = 'استلام أو توصيل';
$lang['mess_member_hint'] = 'العضو شخص واحد أو غرفة واحدة، يُنشأ مرة واحدة. كل شهر هو اشتراك جديد لنفس العضو.';
$lang['mess_phone_used_by'] = 'هذا الهاتف مستخدم بالفعل من قبل';
$lang['mess_member_has_records'] = 'لهذا العضو اشتراكات أو مدفوعات. تم تعليمه كغير نشط بدلاً من حذفه.';
$lang['mess_last_cycle_end'] = 'نهاية آخر دورة';
$lang['mess_last_payment'] = 'آخر دفعة';
$lang['mess_no_subscription'] = 'لا يوجد اشتراك';
$lang['mess_member_profile'] = 'ملف العضو';
$lang['mess_ledger'] = 'كشف الحساب';
$lang['mess_charge'] = 'مستحق';
$lang['mess_paid'] = 'مدفوع';
$lang['mess_balance_due'] = 'الرصيد المستحق';
$lang['mess_advance_credit'] = 'رصيد مقدم';
$lang['mess_print_statement'] = 'طباعة كشف الحساب';
$lang['mess_statement'] = 'كشف حساب العضو';
$lang['mess_whatsapp_reminder'] = 'تذكير واتساب';
$lang['mess_whatsapp'] = 'واتساب';
$lang['mess_no_entries'] = 'لا توجد قيود بعد';
$lang['mess_active_members'] = 'الأعضاء النشطون';

// subscriptions
$lang['mess_new_subscription'] = 'اشتراك جديد';
$lang['mess_edit_subscription'] = 'تعديل الاشتراك';
$lang['mess_renew'] = 'تجديد';
$lang['mess_renew_cycle'] = 'تجديد الدورة';
$lang['mess_renewing_from'] = 'تجديد الدورة التي تنتهي في';
$lang['mess_suggested_price'] = 'السعر المقترح';
$lang['mess_agreed_price'] = 'السعر المتفق عليه';
$lang['mess_price_note'] = 'ملاحظة السعر';
$lang['mess_price_note_ph'] = 'مثال: عميل قديم، سعر السائق';
$lang['mess_delivery_address'] = 'عنوان التوصيل';
$lang['mess_end_date_auto'] = 'تاريخ الانتهاء = تاريخ البدء + أيام الدورة';
$lang['mess_advance_now'] = 'تحصيل الآن';
$lang['mess_advance_now_hint'] = 'اختياري. يذهب إلى سجل الصندوق اليوم مثل أي دفعة ميس.';
$lang['mess_save_subscription'] = 'حفظ الاشتراك';
$lang['mess_overlap_warning'] = 'تنبيه: لهذا العضو دورة أخرى في هذه التواريخ';
$lang['mess_meals_required'] = 'أدخل عدد الأشخاص لوجبة واحدة على الأقل.';
$lang['mess_dates_invalid'] = 'يجب أن يكون تاريخ الانتهاء في يوم البدء أو بعده.';
$lang['mess_agreed_price_required'] = 'السعر المتفق عليه مطلوب.';
$lang['mess_cancel_subscription'] = 'إلغاء الاشتراك';
$lang['mess_cancel_confirm'] = 'إلغاء هذا الاشتراك؟ يُحذف مبلغه من كشف الحساب ولن تُتوقع وجبات بعد الآن.';
$lang['mess_sub_has_payments'] = 'لهذا الاشتراك مدفوعات. تم إلغاؤه بدلاً من حذفه؛ تبقى المدفوعات في كشف الحساب كرصيد مقدم.';
$lang['mess_pause'] = 'إيقاف مؤقت';
$lang['mess_pauses'] = 'فترات الإيقاف (إجازة)';
$lang['mess_add_pause'] = 'إضافة إيقاف';
$lang['mess_pause_hint'] = 'العضو مسافر: يتأخر تاريخ الانتهاء بنفس عدد الأيام ولا تُتوقع وجبات في تلك الأيام.';
$lang['mess_pause_dates_invalid'] = 'يجب أن تكون نهاية الإيقاف في يوم بدايته أو بعده.';
$lang['mess_pause_outside_cycle'] = 'يجب أن يبدأ الإيقاف داخل تواريخ الاشتراك.';
$lang['mess_pause_overlaps'] = 'هذا الإيقاف يتداخل مع إيقاف آخر.';
$lang['mess_pause_added'] = 'تم حفظ الإيقاف وتأخير تاريخ الانتهاء.';
$lang['mess_paused_days'] = 'أيام الإيقاف';
$lang['mess_days_left'] = 'الأيام المتبقية';
$lang['mess_ends_today'] = 'ينتهي اليوم';
$lang['mess_ends_in'] = 'ينتهي خلال';
$lang['mess_ended'] = 'انتهى منذ';
$lang['mess_expiring_list'] = 'تنتهي قريباً';
$lang['mess_expired_not_renewed'] = 'منتهي ولم يُجدد';
$lang['mess_expired_not_renewed_hint'] = 'دورات انتهت خلال آخر 45 يوماً بدون دورة أحدث للعضو.';
$lang['mess_within_days'] = 'خلال أيام';

// payments
$lang['mess_receive_payment'] = 'استلام دفعة';
$lang['mess_payment_type'] = 'نوع القيد';
$lang['mess_type_payment'] = 'دفعة (مال داخل)';
$lang['mess_type_refund'] = 'استرداد (مال خارج)';
$lang['mess_type_adjustment'] = 'تسوية (بدون مال)';
$lang['mess_adjustment'] = 'تسوية';
$lang['mess_adjust_credit'] = 'دائن: تخفيض المستحق (خصم، إعفاء)';
$lang['mess_adjust_debit'] = 'مدين: إضافة مبلغ';
$lang['mess_against_subscription'] = 'مقابل الاشتراك';
$lang['mess_general_advance'] = 'عام (بدون اشتراك)';
$lang['mess_amount_positive'] = 'يجب أن يكون المبلغ أكبر من صفر.';
$lang['mess_payment_method_required'] = 'طريقة الدفع مطلوبة للدفعة أو الاسترداد.';
$lang['mess_payment_saved'] = 'تم حفظ الدفعة.';
$lang['mess_print_receipt'] = 'طباعة الإيصال';
$lang['mess_receipt'] = 'إيصال الميس';
$lang['mess_receipt_title'] = 'إيصال دفعة الميس';
$lang['mess_received_from'] = 'استلمنا من';
$lang['mess_received_by'] = 'استلمها';
$lang['mess_balance_after'] = 'الرصيد بعد هذه الدفعة';
$lang['mess_thank_you'] = 'شكراً لكم';
$lang['mess_payment_closed_register'] = 'هذه الدفعة تخص سجل صندوق مغلق. يمكن للمدير فقط حذفها.';
$lang['mess_register_needed'] = 'مدفوعات الميس تتطلب سجل صندوق مفتوح (إعدادات الميس). افتح السجل أولاً.';
$lang['mess_refund_adjust_admin'] = 'الاسترداد والتسوية يتطلبان المدير أو صلاحية حذف مدفوعات الميس.';
$lang['mess_collection_today'] = 'المحصّل اليوم';
$lang['mess_total_dues'] = 'إجمالي المستحقات';
$lang['mess_members_with_due'] = 'أعضاء عليهم مستحقات';
$lang['mess_payments_count'] = 'دفعات';

// register card
$lang['mess_collection_plus'] = 'تحصيل الميس (+)';
$lang['mess_collection_register_hint'] = 'مدفوعات الميس المستلمة في هذا السجل، ومحسوبة ضمن النقد في اليد';

// daily menu
$lang['mess_menu_for'] = 'قائمة يوم';
$lang['mess_dishes'] = 'الأطباق';
$lang['mess_dish'] = 'الطبق';
$lang['mess_add_dish'] = 'إضافة طبق';
$lang['mess_portion_per_person'] = 'الحصة / الشخص';
$lang['mess_buffer'] = 'احتياطي';
$lang['mess_expected_persons'] = 'الأشخاص المتوقعون';
$lang['mess_planned_portions'] = 'الحصص المخططة';
$lang['mess_copy_yesterday'] = 'نسخ الأمس';
$lang['mess_copy_last_week'] = 'نسخ نفس اليوم من الأسبوع الماضي';
$lang['mess_copy_from'] = 'نسخ من تاريخ';
$lang['mess_copy_done'] = 'أطباق تم نسخها';
$lang['mess_copy_confirm'] = 'استبدال قائمة هذا اليوم بالقائمة المنسوخة؟';
$lang['mess_menu_text'] = 'نص القائمة (واتساب)';
$lang['mess_menu_missing'] = 'القائمة غير محددة';
$lang['mess_menu_saved'] = 'تم حفظ القائمة';
$lang['mess_no_recipe'] = 'بدون وصفة';
$lang['mess_no_recipe_hint'] = 'هذا الطبق بدون وصفة في قائمة الطعام، لن يتحرك المخزون له.';
$lang['mess_free_text_dish'] = 'أو اكتب اسم الطبق';
$lang['mess_pick_food_menu'] = '-- طبق من قائمة الطعام (بوصفة) --';
$lang['mess_expected_hint'] = 'الأشخاص المتوقعون يأتون مباشرة من اشتراكات اليوم ناقص الاعتذارات.';
$lang['mess_copied_to_clipboard'] = 'تم النسخ';
$lang['mess_copy_text'] = 'نسخ النص';

// meal register
$lang['mess_expected'] = 'المتوقع';
$lang['mess_served'] = 'تم التقديم';
$lang['mess_pending'] = 'معلّق';
$lang['mess_not_taken'] = 'لم يستلم';
$lang['mess_skipped'] = 'معتذر';
$lang['mess_partial'] = 'جزئي';
$lang['mess_undo'] = 'تراجع';
$lang['mess_mark_all_served'] = 'تعليم كل المعلّق كتم التقديم';
$lang['mess_mark_all_not_taken'] = 'تعليم كل المعلّق كلم يستلم';
$lang['mess_bulk_confirm'] = 'تعليم كل الصفوف المعلّقة؟';
$lang['mess_search_member'] = 'ابحث بالاسم / الهاتف / المبنى';
$lang['mess_print_tick_sheet'] = 'طباعة ورقة التأشير';
$lang['mess_no_members_expected'] = 'لا يوجد عضو يتوقع هذه الوجبة. تحقق من التاريخ أو الوجبة أو الاشتراكات.';
$lang['mess_skip_ahead'] = 'اعتذار مسبق';
$lang['mess_skip_ahead_hint'] = '"لن آتي غداً": تُحذف الوجبة من العدد فيطبخ المطبخ أقل.';
$lang['mess_skip_saved'] = 'تم حفظ الاعتذار';
$lang['mess_skip_not_expected'] = 'هذا العضو لا يتوقع هذه الوجبة في هذا التاريخ.';
$lang['mess_skip_already_marked'] = 'هذه الوجبة معلّمة بالفعل ولا يمكن الاعتذار عنها.';
$lang['mess_served_at'] = 'تم التقديم';
$lang['mess_flip_diet'] = 'تبديل نباتي / غير نباتي لليوم';
$lang['mess_attendance_last_30'] = 'الوجبات في آخر 30 يوماً';
$lang['mess_future_only_skip'] = 'وجبة تاريخ مستقبلي يمكن الاعتذار عنها فقط.';
$lang['mess_invalid_action'] = 'إجراء غير معروف.';

// extras
$lang['mess_add_extra'] = 'إضافة طلب إضافي';
$lang['mess_settle'] = 'التسوية';
$lang['mess_pay_now'] = 'ادفع الآن';
$lang['mess_on_account'] = 'على الحساب';
$lang['mess_on_account_hint'] = 'يُضاف إلى رصيد العضو ويُحصّل مع المبلغ الشهري.';
$lang['mess_pay_now_hint'] = 'تُنشأ دفعة ميس الآن وتصل إلى سجل الصندوق الليلة.';
$lang['mess_custom_item'] = 'صنف مخصص';
$lang['mess_item_name'] = 'اسم الصنف';
$lang['mess_unit_price'] = 'سعر الوحدة';
$lang['mess_extra_saved'] = 'تم حفظ الإضافة';
$lang['mess_mark_delivered'] = 'تعليم كمُسلّم';
$lang['mess_extra_status_requested'] = 'مطلوب';
$lang['mess_extra_status_delivered'] = 'مُسلّم';
$lang['mess_extra_status_cancelled'] = 'ملغى';
$lang['mess_cancel_extra'] = 'إلغاء الإضافة';
$lang['mess_extras_today'] = 'إضافات اليوم';
$lang['mess_extra_paid_cancel_note'] = 'دُفعت هذه الإضافة فوراً. إلغاؤها يُبقي الدفعة رصيداً للعضو (استردها من استلام دفعة عند الحاجة).';

// daily sheet
$lang['mess_sheet_title'] = 'ورقة الميس اليومية';
$lang['mess_headcount'] = 'العدد';
$lang['mess_production_plan'] = 'خطة الإنتاج';
$lang['mess_ingredient_need'] = 'احتياج المكونات (الوصفة × الحصص)';
$lang['mess_stock'] = 'المخزون';
$lang['mess_short'] = 'نقص';
$lang['mess_ok'] = 'متوفر';
$lang['mess_member_checklist'] = 'قائمة الأعضاء';
$lang['mess_sign'] = 'التوقيع';
$lang['mess_printed_by'] = 'طُبع بواسطة';
$lang['mess_stock_shortage'] = 'نقص المخزون';
$lang['mess_delivery_route'] = 'قائمة التوصيل';
$lang['mess_skipped_advance'] = 'اعتذار مسبق';
$lang['mess_portions'] = 'الحصص';

// today dashboard
$lang['mess_expected_today'] = 'متوقع';
$lang['mess_menu_today'] = 'قائمة اليوم';
$lang['mess_needs_attention'] = 'يحتاج متابعة';
$lang['mess_open_register'] = 'فتح سجل الوجبات';
$lang['mess_quick_actions'] = 'إجراءات سريعة';
$lang['mess_nothing_to_show'] = 'لا يوجد ما يُعرض';

// reports
$lang['mess_attendance_matrix'] = 'مصفوفة الحضور';
$lang['mess_attendance_matrix_hint'] = 'S تم التقديم، P جزئي، N لم يستلم، K معتذر، - معلّق، فارغ غير مشترك';
$lang['mess_daily_served_summary'] = 'ملخص التقديم اليومي';
$lang['mess_collection_report'] = 'تقرير التحصيل';
$lang['mess_by_method'] = 'حسب طريقة الدفع';
$lang['mess_member_x_day'] = 'العضو × اليوم';

// settings
$lang['mess_settings_saved'] = 'تم حفظ إعدادات الميس';
$lang['mess_slot_on'] = 'مفعّل';
$lang['mess_from_time'] = 'من';
$lang['mess_to_time'] = 'إلى';
$lang['mess_default_cycle_days'] = 'أيام الدورة الافتراضية';
$lang['mess_expiring_days'] = 'تنبيه الانتهاء (أيام قبل النهاية)';
$lang['mess_skip_credit_policy'] = 'سياسة رصيد الاعتذار';
$lang['mess_skip_credit_none'] = 'بدون رصيد، الاعتذار يقلل الطبخ فقط';
$lang['mess_skip_credit_informed'] = 'رصيد عند الإبلاغ قبل الوجبة (تسوية يدوية)';
$lang['mess_skip_credit_always'] = 'رصيد دائماً (تسوية يدوية)';
$lang['mess_require_open_register'] = 'اشتراط سجل صندوق مفتوح لمدفوعات الميس';
$lang['mess_require_open_register_hint'] = 'موصى به. المال المستلم بدون سجل مفتوح لا يصل إلى إغلاق الصندوق، فلا يتطابق النقد في اليد.';
$lang['mess_member_code_prefix'] = 'بادئة رمز العضو';
$lang['mess_whatsapp_country_code'] = 'رمز الدولة لواتساب';
$lang['mess_whatsapp_templates'] = 'قوالب رسائل واتساب';
$lang['mess_template_menu'] = 'القائمة اليومية';
$lang['mess_template_due'] = 'تذكير بالمستحق';
$lang['mess_template_expiry'] = 'تذكير بالانتهاء';
$lang['mess_template_vars'] = 'المتغيرات: {name} {amount} {currency} {end_date} {slot} {date} {menu}';
$lang['mess_roles_hint'] = 'أعط كل دور ما يحتاجه فقط من الإعدادات > الأدوار: الكاشير = سجل الوجبات، الإضافات، المدفوعات، اليوم. المطبخ = اليوم، القائمة اليومية، الورقة اليومية.';

// day close (stock)
$lang['mess_day_close'] = 'إغلاق اليوم';
$lang['mess_cooked'] = 'المطبوخ';
$lang['mess_leftover'] = 'المتبقي';
$lang['mess_leftover_goes_to'] = 'مصير المتبقي';
$lang['mess_waste'] = 'هدر';
$lang['mess_carry'] = 'ترحيل للوجبة التالية';
$lang['mess_staff_meal'] = 'وجبة موظفين';
$lang['mess_food_cost'] = 'تكلفة الطعام';
$lang['mess_waste_cost'] = 'تكلفة الهدر';
$lang['mess_cost_per_meal'] = 'التكلفة لكل وجبة مقدّمة';
$lang['mess_served_portions'] = 'المقدّم (تقديري)';
$lang['mess_close_and_post'] = 'إغلاق وترحيل للمخزون';
$lang['mess_close_confirm'] = 'إغلاق هذه الوجبة؟ الحصص المطبوخة × الوصفة تخرج من المخزون، والمتبقي المهدر يُسجّل كهدر.';
$lang['mess_close_hint'] = 'أكّد ما طبخه المطبخ فعلاً وما تبقى. الإغلاق يرحّل استهلاك المكونات (المطبوخ ناقص المهدر × الوصفة) إلى سجل المخزون، والمتبقي المهدر كقيد هدر، فتتحرك شاشات المخزون بمقدار ما طُبخ بالضبط. المتبقي المرحّل أو وجبة الموظفين يُحسب كمستخدم. الأطباق بدون وصفة لا تحرك المخزون.';
$lang['mess_already_closed'] = 'هذه الوجبة مغلقة بالفعل. أعد فتحها أولاً.';
$lang['mess_close_failed'] = 'تعذر حفظ الإغلاق ولم يُرحّل شيء. حاول مرة أخرى.';
$lang['mess_close_future'] = 'يمكن إغلاق الوجبة في يومها أو بعده.';
$lang['mess_closed_ok'] = 'تم إغلاق الوجبة وترحيل المخزون.';
$lang['mess_reopened_ok'] = 'تمت إعادة فتح الوجبة وعكس حركة المخزون المرحّلة.';
$lang['mess_reopen_confirm'] = 'إعادة فتح هذه الوجبة؟ سيُعكس استهلاك المخزون والهدر المرحّلان.';
$lang['mess_closed_by'] = 'بواسطة';
$lang['mess_pending_warning'] = 'بعض الأعضاء ما زالوا معلّقين في سجل الوجبات. علّمهم أولاً لتكون أرقام التقديم صحيحة.';
$lang['mess_open_waste'] = 'فتح قيد الهدر';
$lang['mess_status_closed'] = 'مغلق';
$lang['mess_status_open'] = 'مفتوح';
$lang['mess_day_close_report'] = 'إغلاق اليوم وتكلفة الطعام';
$lang['mess_meals_served'] = 'الوجبات المقدّمة';
$lang['mess_not_closed'] = 'غير مغلق';
