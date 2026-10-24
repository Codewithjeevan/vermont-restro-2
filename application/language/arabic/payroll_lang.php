<?php
/*
 | Payroll module (monthly salary from attendance).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 */

// module, menu and role screen
$lang['payroll'] = 'الرواتب';
$lang['payroll_runs'] = 'الرواتب الشهرية';
$lang['payroll_salary_setup'] = 'إعداد الرواتب';
$lang['payroll_adjustments'] = 'السلف & التسويات';
$lang['payroll_leaves'] = 'الإجازات';
$lang['payroll_holidays'] = 'العطلات الرسمية';
$lang['payroll_reports'] = 'تقارير الرواتب';
$lang['payroll_settings'] = 'إعدادات الرواتب';
$lang['payroll_generate'] = 'إنشاء كشف الرواتب';
$lang['approve'] = 'اعتماد';
$lang['pay'] = 'دفع';
$lang['payroll_tab_reports'] = 'التقارير';
$lang['payroll_tab_setup'] = 'الإعداد';
$lang['payroll_attendance_sheet'] = 'كشف الحضور';
$lang['payroll_salary_history'] = 'تاريخ الرواتب';
$lang['payroll_advance_balances'] = 'أرصدة السلف';

// common words
$lang['payroll_attendance'] = 'الحضور';
$lang['payroll_earnings'] = 'الاستحقاقات';
$lang['payroll_deductions'] = 'الخصومات';
$lang['payroll_net_salary'] = 'صافي الراتب';
$lang['payroll_net_pay'] = 'صافي الأجر';
$lang['payroll_net_payable'] = 'صافي المستحق';
$lang['payroll_balance'] = 'الرصيد';
$lang['payroll_basic'] = 'الأساسي';
$lang['payroll_allowances'] = 'البدلات';
$lang['payroll_bonus'] = 'مكافأة';
$lang['payroll_deduction'] = 'خصم';
$lang['payroll_absence'] = 'الغياب';
$lang['payroll_advance'] = 'سلفة';
$lang['payroll_advances'] = 'السلف';
$lang['payroll_other'] = 'أخرى';
$lang['payroll_overtime'] = 'العمل الإضافي';
$lang['payroll_ot'] = 'إضافي';
$lang['payroll_ot_offday'] = 'إضافي / يوم راحة';
$lang['payroll_offday_work'] = 'العمل في يوم الراحة / العطلة';
$lang['payroll_hrs'] = 'ساعة';
$lang['payroll_days_short'] = 'أيام';
$lang['payroll_total_earnings'] = 'إجمالي الاستحقاقات';
$lang['payroll_total_deductions'] = 'إجمالي الخصومات';
$lang['payroll_partial'] = 'مدفوع جزئياً';
$lang['payroll_unpaid'] = 'غير مدفوع';
$lang['payroll_status_draft'] = 'مسودة';
$lang['payroll_status_approved'] = 'معتمد';
$lang['payroll_status_active'] = 'نشط';
$lang['payroll_status_inactive'] = 'غير نشط';
$lang['payroll_type_monthly'] = 'شهري';
$lang['payroll_type_daily'] = 'أجر يومي';
$lang['payroll_type_hourly'] = 'بالساعة';
$lang['payroll_per_monthly'] = 'شهر';
$lang['payroll_per_daily'] = 'يوم';
$lang['payroll_per_hourly'] = 'ساعة';
$lang['payroll_mode_cash'] = 'نقداً';
$lang['payroll_mode_bank'] = 'تحويل بنكي';
$lang['payroll_auto'] = 'تلقائي';
$lang['payroll_off'] = 'معطّل';
$lang['payroll_open'] = 'فتح';
$lang['payroll_day'] = 'اليوم';
$lang['payroll_from_date'] = 'من';
$lang['payroll_to_date'] = 'إلى';
$lang['payroll_reason'] = 'السبب';
$lang['payroll_signature'] = 'التوقيع';
$lang['payroll_not_set'] = 'غير محدد';
$lang['payroll_set_up'] = 'إعداد';
$lang['payroll_posted'] = 'في المصروفات';
$lang['payroll_expense'] = 'المصروف';
$lang['payroll_all_employees'] = 'كل الموظفين';
$lang['payroll_all_months'] = 'كل الأشهر';
$lang['payroll_all_types'] = 'كل الأنواع';
$lang['payroll_choose_employee'] = 'اختر موظفاً لعرض راتب كل شهر والمدفوعات والسلف.';

// day codes (attendance sheet, payslip)
$lang['payroll_code_P'] = 'حاضر';
$lang['payroll_code_M'] = 'حاضر، بدون تسجيل خروج';
$lang['payroll_code_H'] = 'نصف يوم';
$lang['payroll_code_A'] = 'غائب';
$lang['payroll_code_L'] = 'إجازة مدفوعة';
$lang['payroll_code_U'] = 'إجازة غير مدفوعة';
$lang['payroll_code_O'] = 'راحة أسبوعية';
$lang['payroll_code_HO'] = 'عطلة رسمية';
$lang['payroll_code_W'] = 'عمل في يوم الراحة / العطلة';
$lang['payroll_code_X'] = 'لم يُعيَّن بعد / غادر';
$lang['payroll_code_F'] = 'يوم قادم';
$lang['payroll_present_short'] = 'P';
$lang['payroll_half_short'] = 'نصف';
$lang['payroll_absent_short'] = 'A';
$lang['payroll_leave_short'] = 'L';
$lang['payroll_off_short'] = 'راحة';
$lang['payroll_holiday_short'] = 'عطلة';
$lang['payroll_offday_worked_short'] = 'W';
$lang['payroll_not_employed_short'] = 'خارج التوظيف';

// attendance figures
$lang['payroll_days_in_month'] = 'أيام الشهر';
$lang['payroll_working_days'] = 'أيام العمل';
$lang['payroll_present_days'] = 'أيام الحضور';
$lang['payroll_half_days'] = 'أيام نصف الدوام';
$lang['payroll_absent_days'] = 'أيام الغياب';
$lang['payroll_leave_paid_unpaid'] = 'الإجازات (مدفوعة + غير مدفوعة)';
$lang['payroll_off_holidays'] = 'الراحة / العطلات';
$lang['payroll_offday_worked'] = 'أيام راحة تم العمل فيها';
$lang['payroll_worked_hours'] = 'ساعات العمل';
$lang['payroll_overtime_hours'] = 'ساعات العمل الإضافي';
$lang['payroll_missing_checkout'] = 'أيام بدون تسجيل خروج';
$lang['payroll_not_employed'] = 'أيام خارج فترة التوظيف';
$lang['payroll_per_day_rate'] = 'أجر اليوم';
$lang['payroll_calendar_days'] = 'الأيام';
$lang['payroll_full_day'] = 'يوم كامل';
$lang['payroll_half_day'] = 'نصف يوم';

// monthly payroll list
$lang['payroll_runs_hint'] = 'كشف رواتب واحد لكل شهر، يُحسب من الحضور وإعداد الرواتب. أنشئه، راجعه، اعتمده، ثم ادفع.';
$lang['payroll_employees_on_payroll'] = 'الموظفون في كشف الرواتب';
$lang['payroll_employees_on_payroll_hint'] = 'لديهم إعداد راتب نشط';
$lang['payroll_monthly_gross'] = 'الإجمالي الشهري (ثابت)';
$lang['payroll_monthly_gross_hint'] = 'الأساسي + البدلات للموظفين الشهريين';
$lang['payroll_not_generated'] = 'لم يُنشأ';
$lang['payroll_last_month'] = 'الشهر الماضي';
$lang['payroll_balance_to_pay'] = 'متبقٍ للدفع';
$lang['payroll_advances_open'] = 'سلف قيد الاسترداد';
$lang['payroll_advances_open_hint'] = 'صُرفت ولم تُخصم بعد';
$lang['payroll_no_setup_yet'] = 'لا يوجد موظف له راتب بعد. ابدأ بإعداد راتب كل موظف.';
$lang['payroll_generate_title'] = 'إنشاء كشف الرواتب';
$lang['payroll_generate_hint'] = 'يبدأ كشف الرواتب الجديد كمسودة: يعيد قراءة الحضور في كل مرة تفتحه، فيمكنك تصحيح الحضور ورؤية النتيجة فوراً. اعتمده عندما يكون صحيحاً.';
$lang['payroll_running'] = 'جارٍ';
$lang['payroll_generated_by'] = 'أنشأه';
$lang['payroll_approved_by'] = 'اعتمده';

// payroll sheet
$lang['payroll_print_sheet'] = 'طباعة الكشف';
$lang['payroll_all_payslips'] = 'كل قسائم الراتب';
$lang['payroll_recalculate'] = 'إعادة الحساب';
$lang['payroll_pay_all'] = 'دفع الكل';
$lang['payroll_approve_confirm'] = 'اعتماد كشف الرواتب هذا؟ يُقفل بعد الاعتماد ويمكن عندها دفع الرواتب.';
$lang['payroll_draft_info'] = 'مسودة: تُحسب الأرقام من جديد من الحضور في كل مرة تُفتح فيها هذه الصفحة. استخدم القلم لتصحيح سطر (أيام الغياب، الإضافي، المكافأة، الخصم)، ثم اعتمد.';
$lang['payroll_approved_info'] = 'معتمد: كشف الرواتب هذا مقفل. ادفع الرواتب سطراً سطراً أو دفعة واحدة. لتعديله، احذف مدفوعاته ثم أعد فتحه.';
$lang['payroll_month_running_warn'] = 'هذا الشهر ما زال جارياً: الأيام القادمة (%d) لا تُحسب غياباً بعد (يُدفع للموظفين الشهريين عنها، ولا يُدفع للموظفين باليومية أو بالساعة). اعتمد بعد انتهاء الشهر.';
$lang['payroll_missing_checkout_warn'] = 'أيام فيها تسجيل دخول بدون تسجيل خروج: %d. تُحسب على أنها %s بدون عمل إضافي (إعدادات الرواتب). صحّح وقت الخروج في الحضور عند الحاجة.';
$lang['payroll_negative_warn'] = 'موظفون لديهم صافي راتب سالب: %d. قلّل الخصومات أو وزّع السلفة على أشهر أكثر.';
$lang['payroll_missing_setup_warn'] = 'غير مشمولين في كشف الرواتب هذا (بدون إعداد راتب):';
$lang['payroll_edited'] = 'معدّل';
$lang['payroll_override_hint'] = 'عُدّل يدوياً في هذا الكشف؛ ويبقى عند إعادة حساب الحضور.';
$lang['payroll_no_attendance'] = 'لا يوجد حضور';
$lang['payroll_salary_payments'] = 'مدفوعات الرواتب';
$lang['payroll_no_payments'] = 'لا توجد دفعات بعد.';
$lang['payroll_from_till_short'] = '(الدرج)';
$lang['payroll_delete_payment_confirm'] = 'حذف هذه الدفعة؟ يُحذف قيد المصروف الخاص بها أيضاً.';
$lang['payroll_basis_note'] = 'القواعد المستخدمة';
$lang['payroll_absent_on'] = 'يُخصم الغياب من';
$lang['payroll_edit_line'] = 'تصحيح السطر';
$lang['payroll_edit_line_hint'] = 'اترك الخانة فارغة لاستخدام القيمة من الحضور. القيم المكتوبة هنا تبقى عند إعادة حساب الرواتب.';
$lang['payroll_from_attendance'] = 'من الحضور';
$lang['payroll_reset_line'] = 'إرجاع إلى الحضور';
$lang['payroll_pay_salary'] = 'دفع الراتب';
$lang['payroll_pay_all_hint'] = 'يدفع الرصيد المتبقي لكل موظف (%s) بالطريقة أدناه.';
$lang['payroll_record_expense'] = 'إضافة إلى المصروفات';
$lang['payroll_from_till'] = 'مدفوع من درج النقد';
$lang['payroll_from_till_hint'] = 'علّم "مدفوع من درج النقد" فقط عندما يخرج المال من درج هذا الكاونتر: عندها يحتسبه سجل الصندوق المفتوح.';
$lang['payroll_from_till_closed'] = 'سجل الصندوق لهذا الكاونتر مغلق، لذلك لا يمكن استخدام "مدفوع من درج النقد" الآن.';

// salary setup
$lang['payroll_salary_setup_hint'] = 'هيكل راتب كل موظف. لا يُشمل في كشف الرواتب الشهري إلا الموظفون الذين لديهم إعداد نشط.';
$lang['payroll_n_of_m_set'] = 'تم إعداد %d من %d';
$lang['payroll_salary_type'] = 'نوع الراتب';
$lang['payroll_basic_rate'] = 'الأساسي / الأجر';
$lang['payroll_gross_month'] = 'الإجمالي / شهر';
$lang['payroll_weekly_off'] = 'الراحة الأسبوعية';
$lang['payroll_joining_date'] = 'تاريخ الالتحاق';
$lang['payroll_leaving_date'] = 'تاريخ المغادرة';
$lang['payroll_left'] = 'غادر في';
$lang['payroll_payment_mode'] = 'طريقة الدفع';
$lang['payroll_no_login'] = 'بدون دخول';
$lang['payroll_employees_from_users'] = 'الموظفون هم مستخدمو الحسابات والمستخدمين > المستخدمون. أضف هناك الموظفين الذين لا يسجلون الدخول مع "تسجيل الدخول؟" = لا، وأدخل حضورهم يدوياً.';
$lang['payroll_pay_structure'] = 'هيكل الأجر';
$lang['payroll_basic_salary'] = 'الراتب الأساسي';
$lang['payroll_basic_monthly'] = 'الراتب الأساسي الشهري';
$lang['payroll_basic_daily'] = 'أجر اليوم';
$lang['payroll_basic_hourly'] = 'أجر الساعة';
$lang['payroll_overtime_rate'] = 'أجر العمل الإضافي / ساعة';
$lang['payroll_overtime_rate_hint'] = 'فارغ = يُحسب من الأساسي ومعامل العمل الإضافي.';
$lang['payroll_on_payroll'] = 'ضمن كشف الرواتب';
$lang['payroll_monthly_allowances'] = 'البدلات الشهرية والخصم الثابت';
$lang['payroll_housing_allowance'] = 'بدل السكن';
$lang['payroll_transport_allowance'] = 'بدل المواصلات';
$lang['payroll_food_allowance'] = 'بدل الطعام';
$lang['payroll_other_allowance'] = 'بدل آخر';
$lang['payroll_fixed_deduction'] = 'خصم ثابت';
$lang['payroll_fixed_deduction_for'] = 'الخصم الثابت مقابل';
$lang['payroll_fixed_deduction_example'] = 'مثال: السكن، التأمين';
$lang['payroll_preview_gross'] = 'الإجمالي شهرياً';
$lang['payroll_preview_daily'] = 'تقريباً (26 يوماً)';
$lang['payroll_preview_hourly'] = 'تقريباً (26 يوماً x الساعات القياسية)';
$lang['payroll_preview_perday'] = 'لليوم';
$lang['payroll_preview_ot'] = 'إضافي / ساعة';
$lang['payroll_work_calendar'] = 'تقويم العمل';
$lang['payroll_weekly_off_hint'] = 'أيام الراحة كل أسبوع. ليست غياباً؛ ويمكن الدفع الإضافي عن أيام الراحة التي تم العمل فيها (إعدادات الرواتب).';
$lang['payroll_leaving_hint'] = 'آخر يوم عمل. اتركه فارغاً طوال مدة التوظيف.';
$lang['payroll_payment_details'] = 'تفاصيل الدفع';
$lang['payroll_bank_name'] = 'اسم البنك';
$lang['payroll_bank_account'] = 'رقم الحساب / IBAN';
$lang['payroll_salary_saved'] = 'تم حفظ إعداد الراتب.';
$lang['payroll_leaving_before_joining'] = 'تاريخ المغادرة قبل تاريخ الالتحاق.';

// advances & adjustments
$lang['payroll_adjustments_hint'] = 'السلف (نقد يُصرف الآن ويُسترد من الراتب)، المكافآت والبدلات الإضافية (تُضاف)، الخصومات مثل الغرامات (تُطرح). كل منها يدخل في كشف رواتب الشهر الذي تختاره.';
$lang['payroll_add_adjustment'] = 'إضافة سلفة / مكافأة / خصم';
$lang['payroll_adj_advance'] = 'سلفة على الراتب';
$lang['payroll_adj_bonus'] = 'مكافأة';
$lang['payroll_adj_allowance'] = 'بدل إضافي';
$lang['payroll_adj_deduction'] = 'خصم';
$lang['payroll_pay_month'] = 'شهر الرواتب';
$lang['payroll_pay_month_hint'] = 'للسلفة: أول شهر تُخصم فيه.';
$lang['payroll_installments'] = 'الأقساط';
$lang['payroll_installments_hint'] = 'عدد الأشهر لاستردادها.';
$lang['payroll_given_by'] = 'صرفها';
$lang['payroll_recovery'] = 'الاسترداد';
$lang['payroll_n_installments'] = '%d x %s';
$lang['payroll_one_installment'] = 'دفعة واحدة';
$lang['payroll_deducted'] = 'يُخصم من الراتب';
$lang['payroll_added'] = 'يُضاف إلى الراتب';
$lang['payroll_adjustment_invalid'] = 'اختر موظفاً ونوعاً ومبلغاً أكبر من صفر وشهر رواتب.';
$lang['payroll_month_approved'] = 'كشف رواتب %s معتمد بالفعل. أعد فتحه أولاً.';

// leaves
$lang['payroll_leaves_hint'] = 'الإجازة المدفوعة لا تُخصم؛ والإجازة غير المدفوعة تُخصم مثل الغياب. لا تُحسب أيام الراحة الأسبوعية والعطلات الواقعة داخل الإجازة.';
$lang['payroll_add_leave'] = 'إضافة إجازة';
$lang['payroll_leave_paid'] = 'إجازة مدفوعة';
$lang['payroll_leave_unpaid'] = 'إجازة غير مدفوعة';
$lang['payroll_leave_rule'] = 'اليوم الذي فيه حضور يبقى حضوراً حتى داخل الإجازة.';
$lang['payroll_leave_invalid'] = 'اختر موظفاً ونطاق تواريخ صحيحاً (سنة واحدة كحد أقصى).';
$lang['payroll_leave_overlap'] = 'لهذا الموظف إجازة بالفعل في هذه التواريخ.';

// holidays
$lang['payroll_holidays_hint'] = 'عطلات رسمية للجميع: ليست غياباً أبداً، ويمكن دفع العمل في العطلة كيوم إضافي.';
$lang['payroll_add_holiday'] = 'إضافة عطلة';
$lang['payroll_same_day'] = 'نفس اليوم';
$lang['payroll_holiday_example'] = 'مثال: اليوم الوطني';
$lang['payroll_holiday_invalid'] = 'أدخل تاريخاً واسماً، وبحد أقصى 31 يوماً.';

// settings
$lang['payroll_settings_hint'] = 'كيف يتحول الحضور إلى راتب. تُطبَّق التغييرات على كشوف الرواتب المسودة فوراً؛ وتحتفظ الكشوف المعتمدة بأرقامها.';
$lang['payroll_monthly_salary_rules'] = 'الراتب الشهري';
$lang['payroll_month_days_basis'] = 'أجر اليوم = الراتب الشهري مقسوماً على';
$lang['payroll_basis_calendar'] = 'أيام الشهر (28-31)';
$lang['payroll_basis_fixed30'] = '30 يوماً دائماً';
$lang['payroll_basis_working'] = 'أيام العمل في الشهر';
$lang['payroll_month_days_basis_hint'] = 'يُستخدم لخصم يوم الغياب ولدفع يوم الراحة الذي تم العمل فيه.';
$lang['payroll_absent_deduction_on'] = 'يُخصم يوم الغياب من';
$lang['payroll_on_gross'] = 'الإجمالي (الأساسي + البدلات)';
$lang['payroll_on_basic'] = 'الأساسي فقط';
$lang['payroll_absent_deduction_on_hint'] = 'أجر اليوم = هذا المبلغ / الأيام أعلاه.';
$lang['payroll_pay_offday_work'] = 'دفع العمل في الراحة الأسبوعية / العطلة كيوم إضافي';
$lang['payroll_pay_offday_work_hint'] = 'للموظفين الشهريين فقط؛ أما الموظفون باليومية وبالساعة فيُدفع لهم عن كل يوم أو ساعة عمل في كل الأحوال.';
$lang['payroll_default_weekly_off'] = 'الراحة الأسبوعية الافتراضية لإعدادات الرواتب الجديدة';
$lang['payroll_default_weekly_off_hint'] = 'يمكن أن تكون لكل موظف راحته الأسبوعية الخاصة في إعداد الراتب.';
$lang['payroll_attendance_rules'] = 'الحضور';
$lang['payroll_standard_hours'] = 'ساعات اليوم الكامل';
$lang['payroll_standard_hours_hint'] = 'ما يزيد على ذلك يُعد عملاً إضافياً.';
$lang['payroll_half_day_hours'] = 'نصف يوم إذا قلّ عن (ساعات)';
$lang['payroll_half_day_hours_hint'] = 'العمل أقل من ذلك = نصف يوم.';
$lang['payroll_missing_checkout_rule'] = 'اليوم الذي فيه تسجيل دخول بدون تسجيل خروج يُحسب';
$lang['payroll_missing_checkout_rule_hint'] = 'الموظفون الذين لا يسجلون الخروج أبداً ليس لهم وقت خروج. تُحسب ساعاتهم يوماً قياسياً كاملاً (أو نصف يوم)، بدون عمل إضافي.';
$lang['payroll_overtime_enabled'] = 'دفع العمل الإضافي';
$lang['payroll_overtime_multiplier'] = 'معامل العمل الإضافي';
$lang['payroll_overtime_multiplier_hint'] = 'أجر الساعة x هذا الرقم، مثال: 1.25';
$lang['payroll_overtime_min_minutes'] = 'تجاهل العمل الإضافي الأقل من (دقائق / يوم)';
$lang['payroll_overtime_min_minutes_hint'] = 'بضع دقائق إضافية لا تُعد عملاً إضافياً.';
$lang['payroll_payments_expenses'] = 'المدفوعات والمصروفات';
$lang['payroll_record_expense_default'] = 'تعليم "إضافة إلى المصروفات" افتراضياً عند الدفع';
$lang['payroll_expense_category'] = 'فئة المصروفات للرواتب';
$lang['payroll_expense_category_auto'] = 'الرواتب & الأجور (تُنشأ تلقائياً)';
$lang['payroll_payslip_footer'] = 'تذييل قسيمة الراتب';
$lang['payroll_payslip_footer_example'] = 'مثال: هذه قسيمة راتب صادرة إلكترونياً.';

// reports
$lang['payroll_attendance_sheet_hint'] = 'كل موظف وكل يوم في الشهر، مقروءة بنفس طريقة كشف الرواتب.';
$lang['payroll_attendance_sheet_note'] = 'الموظفون بدون إعداد راتب يستخدمون الراحة الأسبوعية الافتراضية. صحّح اليوم من الحضور > قائمة الحضور، وأضف الإجازات والعطلات هنا.';
$lang['payroll_not_on_payroll_hint'] = 'بدون إعداد راتب: غير مشمول في كشف الرواتب.';
$lang['payroll_salary_history_hint'] = 'راتب كل شهر لموظف واحد، مع المدفوعات والسلف.';
$lang['payroll_monthly_salaries'] = 'الرواتب الشهرية';
$lang['payroll_no_lines'] = 'لا يوجد كشف رواتب لهذا الموظف في هذه السنة.';
$lang['payroll_advance_balance'] = 'رصيد السلفة';
$lang['payroll_given'] = 'الممنوح';
$lang['payroll_recovered'] = 'المسترد';
$lang['payroll_no_advances'] = 'لا توجد سلف.';
$lang['payroll_advance_balances_hint'] = 'السلف الممنوحة والمبلغ المتبقي لاسترداده من الراتب.';
$lang['payroll_advance_balances_note'] = 'المسترد = خصومات السلف في كشوف الرواتب المعتمدة. كشوف الرواتب المسودة لا تُحسب بعد.';
$lang['payroll_last_advance'] = 'آخر سلفة';
$lang['payroll_give_advance'] = 'منح سلفة';

// payslip, printed sheet
$lang['payroll_payslip'] = 'قسيمة الراتب';
$lang['payroll_payslips'] = 'قسائم الرواتب';
$lang['payroll_employee_no'] = 'رقم الموظف';
$lang['payroll_monthly_basic'] = 'الأساسي الشهري';
$lang['payroll_advance_recovery'] = 'استرداد السلفة';
$lang['payroll_no_deductions'] = 'لا توجد خصومات';
$lang['payroll_employer_signature'] = 'توقيع صاحب العمل';
$lang['payroll_employee_signature'] = 'توقيع الموظف';
$lang['payroll_register'] = 'سجل الرواتب';
$lang['payroll_printed'] = 'طُبع في';
$lang['payroll_prepared_by'] = 'أعدّه';
$lang['payroll_checked_by'] = 'راجعه';

// messages
$lang['payroll_not_found'] = 'غير موجود.';
$lang['payroll_month_invalid'] = 'اختر هذا الشهر أو شهراً سابقاً.';
$lang['payroll_already_generated'] = 'كشف رواتب هذا الشهر موجود بالفعل؛ ها هو.';
$lang['payroll_no_eligible'] = 'لا يوجد موظف لديه إعداد راتب نشط لهذا الشهر.';
$lang['payroll_generated'] = 'تم إنشاء كشف الرواتب كمسودة. راجعه ثم اعتمده.';
$lang['payroll_locked'] = 'كشف الرواتب هذا معتمد ومقفل.';
$lang['payroll_recalculated'] = 'أُعيد حساب كشف الرواتب من الحضور.';
$lang['payroll_absent_too_many'] = 'لا يمكن أن تكون أيام الغياب أكثر من أيام الشهر.';
$lang['payroll_approved_msg'] = 'تم اعتماد كشف الرواتب. يمكن دفع الرواتب الآن.';
$lang['payroll_reopen_has_payments'] = 'احذف مدفوعات رواتب هذا الشهر قبل إعادة فتحه.';
$lang['payroll_reopened_msg'] = 'أُعيد فتح كشف الرواتب كمسودة.';
$lang['payroll_delete_only_draft'] = 'لا يمكن حذف إلا كشف رواتب في حالة مسودة. أعد فتحه أولاً.';
$lang['payroll_choose_method'] = 'اختر طريقة الدفع.';
$lang['payroll_till_needs_register'] = 'افتح سجل الصندوق لهذا الكاونتر أولاً، أو ألغِ تعليم "مدفوع من درج النقد".';
$lang['payroll_pay_needs_approval'] = 'اعتمد كشف الرواتب قبل دفع الرواتب.';
$lang['payroll_amount_invalid'] = 'يجب أن يكون المبلغ أكبر من صفر ولا يزيد على الرصيد.';
$lang['payroll_payment_saved'] = 'تم حفظ دفعة الراتب.';
$lang['payroll_nothing_to_pay'] = 'لم يتبقَّ شيء للدفع.';
$lang['payroll_paid_n'] = 'تم دفع الرواتب. عدد الموظفين: %d.';
