<?php
/*
 | Email Invoice module (docs/email_invoice.md). Same key order as english/email_invoice_lang.php.
 | The bill email itself is always English (Invoice_mailer::t), so its keys are not repeated here.
 */

// module, menu and role screen
$lang['email_invoice'] = 'الفاتورة بالبريد الإلكتروني';
$lang['ei_send_email'] = 'إرسال / إعادة إرسال الفاتورة بالبريد';
$lang['ei_email_settings'] = 'إعدادات البريد الإلكتروني';
$lang['ei_email_log'] = 'سجل البريد الإلكتروني';

// settings page
$lang['ei_on'] = 'مفعّل';
$lang['ei_off'] = 'متوقف';
$lang['ei_settings_hint'] = 'اربط حساب البريد الذي تُرسل منه الفواتير. احفظ ثم أرسل بريدًا تجريبيًا للتأكد من أنه يعمل.';
$lang['ei_enable'] = 'إرسال الفواتير بالبريد الإلكتروني';
$lang['ei_enable_help'] = 'عند التفعيل تظهر خانة "إرسال الفاتورة بالبريد" في شاشة الدفع.';
$lang['ei_email_account'] = 'حساب البريد';
$lang['ei_provider'] = 'مزود البريد';
$lang['ei_username'] = 'البريد الإلكتروني (تسجيل الدخول)';
$lang['ei_username_help'] = 'حساب البريد الذي يرسل الفواتير.';
$lang['ei_password'] = 'كلمة المرور / كلمة مرور التطبيق';
$lang['ei_password_help'] = 'في Gmail استخدم كلمة مرور التطبيق وليس كلمة المرور العادية.';
$lang['ei_password_saved'] = 'محفوظة - اتركها فارغة للإبقاء عليها';
$lang['ei_password_keep'] = 'توجد كلمة مرور محفوظة. اتركها فارغة للإبقاء عليها أو اكتب كلمة جديدة.';
$lang['ei_help_gmail'] = 'Gmail: فعّل التحقق بخطوتين في حساب Google، ثم افتح حساب Google > الأمان > كلمات مرور التطبيقات، وأنشئ واحدة والصق الأحرف الـ16 هنا. كلمة مرور Gmail العادية لن تعمل.';
$lang['ei_help_outlook'] = 'Outlook / Office 365: استخدم البريد الكامل وكلمة مروره. في حسابات الشركات يجب على المسؤول السماح بـ "Authenticated SMTP" لهذا البريد.';
$lang['ei_help_zoho'] = 'Zoho: استخدم البريد الكامل وكلمة مروره، أو كلمة مرور خاصة بالتطبيق إذا كان التحقق الثنائي مفعّلًا.';
$lang['ei_help_hostinger'] = 'Hostinger: استخدم حساب بريد تم إنشاؤه في hPanel > Emails مع كلمة مروره.';
$lang['ei_help_custom'] = 'اطلب من شركة الاستضافة / البريد خادم SMTP والمنفذ ونوع الأمان (cPanel: Email Accounts > Connect Devices) وأدخلها في الإعدادات المتقدمة.';
$lang['ei_advanced'] = 'متقدم (الخادم، المنفذ، اسم المرسل)';
$lang['ei_host'] = 'خادم SMTP';
$lang['ei_port'] = 'المنفذ';
$lang['ei_encryption'] = 'الأمان';
$lang['ei_none'] = 'بدون';
$lang['ei_from_email'] = 'الإرسال من عنوان';
$lang['ei_from_email_help'] = 'فارغ = بريد تسجيل الدخول. معظم المزودين يسمحون فقط ببريد تسجيل الدخول.';
$lang['ei_from_name'] = 'اسم المرسل';
$lang['ei_from_name_help'] = 'فارغ = اسم الفرع.';
$lang['ei_reply_to'] = 'الردود تذهب إلى';
$lang['ei_reply_to_help'] = 'فارغ = بريد الفرع.';
$lang['ei_bcc_email'] = 'أرسل لي نسخة (BCC)';
$lang['ei_bcc_help'] = 'اختياري: تُرسل كل فاتورة أيضًا إلى هذا العنوان.';
$lang['ei_pos_section'] = 'نقطة البيع';
$lang['ei_pos_auto_tick'] = 'تحديد "إرسال الفاتورة بالبريد" تلقائيًا عندما يكون للعميل بريد';
$lang['ei_pos_auto_tick_help'] = 'يمكن للكاشير إلغاء التحديد أو كتابة عنوان آخر.';
$lang['ei_test_section'] = 'اختبار';
$lang['ei_test_to'] = 'أرسل الاختبار إلى (فارغ = بريد تسجيل الدخول)';
$lang['ei_send_test'] = 'إرسال بريد تجريبي';
$lang['ei_test_help'] = 'يستخدم القيم الموجودة في هذه الصفحة الآن حتى قبل الحفظ. يحتوي الاختبار على آخر فاتورة كنموذج.';
$lang['ei_save'] = 'حفظ';
$lang['ei_saved'] = 'تم حفظ إعدادات البريد.';
$lang['ei_save_error'] = 'تعذر حفظ الإعدادات. حاول مرة أخرى.';
$lang['ei_last_test_ok'] = 'آخر اختبار: تم الإرسال';
$lang['ei_last_test_failed'] = 'آخر اختبار: فشل';
$lang['ei_pending_count'] = '%d رسالة بانتظار الإرسال';
$lang['ei_sending'] = 'جارٍ الإرسال...';
$lang['ei_network_error'] = 'لا يوجد رد من الخادم. تحقق من الاتصال بالإنترنت وحاول مرة أخرى.';
$lang['ei_test_ok'] = 'تم إرسال البريد التجريبي إلى %s. تحقق من البريد الوارد (ومجلد الرسائل غير المرغوب فيها).';
$lang['ei_test_subject'] = 'بريد تجريبي';
$lang['ei_test_body'] = 'This is a test from your POS. If you can read this, bill emails will work. Below is a sample of how your customers will see their bill.';
$lang['ei_err_host'] = 'أدخل خادم SMTP صحيحًا.';
$lang['ei_err_port'] = 'أدخل منفذًا بين 1 و 65535.';
$lang['ei_err_encryption'] = 'اختر نوع الأمان.';
$lang['ei_err_username'] = 'أدخل البريد الإلكتروني (تسجيل الدخول).';
$lang['ei_err_password'] = 'أدخل كلمة المرور.';
$lang['ei_err_email_field'] = '%s ليس بريدًا إلكترونيًا صحيحًا.';
$lang['ei_hint_gmail'] = 'رفض Gmail تسجيل الدخول. استخدم كلمة مرور التطبيق (حساب Google > الأمان > كلمات مرور التطبيقات) وليس كلمة المرور العادية.';
$lang['ei_hint_auth'] = 'رفض خادم البريد تسجيل الدخول. تحقق من البريد وكلمة المرور. بعض المزودين يحتاجون كلمة مرور تطبيق أو السماح بـ SMTP للبريد.';
$lang['ei_hint_connect'] = 'تعذر الوصول إلى خادم البريد. تحقق من الخادم والمنفذ. كثير من الاستضافات تحجب المنفذ 25 و 587؛ جرّب SSL مع المنفذ 465.';
$lang['ei_hint_tls'] = 'فشل الاتصال الآمن. جرّب خيار الأمان الآخر (TLS مع 587 أو SSL مع 465).';

// email log
$lang['ei_log_hint'] = 'آخر 200 فاتورة مرسلة بالبريد. الرسائل الفاشلة تُعاد 3 مرات ثم تظهر كفاشلة.';
$lang['ei_log_empty'] = 'لا توجد فواتير مرسلة بالبريد بعد.';
$lang['ei_send_now'] = 'أرسل الآن';
$lang['ei_outlet'] = 'الفرع';
$lang['ei_to'] = 'إلى';
$lang['ei_status'] = 'الحالة';
$lang['ei_attempts'] = 'المحاولات';
$lang['ei_details'] = 'التفاصيل';
$lang['ei_resend'] = 'أعيد إرسالها';
$lang['ei_retry'] = 'إعادة المحاولة';
$lang['ei_retry_queued'] = 'سيتم إرسال البريد مرة أخرى.';
$lang['ei_retry_error'] = 'لا يمكن إعادة محاولة هذا البريد.';
$lang['ei_next_try'] = 'المحاولة التالية: %s';
$lang['ei_status_pending'] = 'بالانتظار';
$lang['ei_status_sending'] = 'جارٍ الإرسال';
$lang['ei_status_sent'] = 'تم الإرسال';
$lang['ei_status_failed'] = 'فشل';

// POS and sales list
$lang['ei_email_bill'] = 'إرسال الفاتورة بالبريد';
$lang['ei_email_placeholder'] = 'customer@email.com';
$lang['ei_email_invoice'] = 'إرسال الفاتورة بالبريد';
$lang['ei_send'] = 'إرسال';
$lang['ei_customer_email'] = 'بريد العميل';
$lang['ei_last_email'] = 'آخر بريد';
$lang['ei_pos_invalid'] = 'بريد الفاتورة غير صحيح. صحّحه أو ألغِ تحديد "إرسال الفاتورة بالبريد".';

// messages
$lang['ei_reload'] = 'تغيّرت الجلسة. أعد تحميل الصفحة.';
$lang['ei_not_ready'] = 'وحدة الفاتورة بالبريد غير مثبتة بعد: نفّذ Update/email_invoice_migration.sql على قاعدة البيانات.';
$lang['ei_not_enabled'] = 'إرسال الفواتير بالبريد متوقف. فعّله من الإعدادات > إعدادات البريد الإلكتروني.';
$lang['ei_invalid_email'] = 'أدخل بريدًا إلكترونيًا صحيحًا.';
$lang['ei_sale_not_found'] = 'الفاتورة غير موجودة.';
$lang['ei_already_queued'] = 'هذه الفاتورة قيد الإرسال إلى هذا العنوان بالفعل.';
$lang['ei_too_many'] = 'تم إرسال هذه الفاتورة بالبريد مرات كثيرة.';
$lang['ei_queue_error'] = 'تعذر إضافة البريد إلى قائمة الإرسال.';
$lang['ei_queued'] = 'سيتم إرسال الفاتورة بالبريد خلال لحظات.';
$lang['ei_sent_to'] = 'تم إرسال الفاتورة إلى %s.';
$lang['ei_will_retry'] = 'لم تُرسل بعد، ستتم إعادة المحاولة تلقائيًا.';
$lang['ei_password_unreadable'] = 'لا يمكن قراءة كلمة مرور البريد المحفوظة. اكتبها مرة أخرى في إعدادات البريد.';
$lang['ei_from_invalid'] = 'عنوان المرسل غير صحيح.';
