<?php
/*
 | Payroll module (monthly salary from attendance).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 */

// module, menu and role screen
$lang['payroll'] = 'Paie';
$lang['payroll_runs'] = 'Paie mensuelle';
$lang['payroll_salary_setup'] = 'Configuration des salaires';
$lang['payroll_adjustments'] = 'Avances & ajustements';
$lang['payroll_leaves'] = 'Congés';
$lang['payroll_holidays'] = 'Jours fériés';
$lang['payroll_reports'] = 'Rapports de paie';
$lang['payroll_settings'] = 'Paramètres de paie';
$lang['payroll_generate'] = 'Générer la paie';
$lang['approve'] = 'Approuver';
$lang['pay'] = 'Payer';
$lang['payroll_tab_reports'] = 'Rapports';
$lang['payroll_tab_setup'] = 'Configuration';
$lang['payroll_attendance_sheet'] = 'Feuille de présence';
$lang['payroll_salary_history'] = 'Historique des salaires';
$lang['payroll_advance_balances'] = 'Soldes des avances';

// common words
$lang['payroll_attendance'] = 'Présence';
$lang['payroll_earnings'] = 'Gains';
$lang['payroll_deductions'] = 'Retenues';
$lang['payroll_net_salary'] = 'Salaire net';
$lang['payroll_net_pay'] = 'Net à payer';
$lang['payroll_net_payable'] = 'Total net à payer';
$lang['payroll_balance'] = 'Solde';
$lang['payroll_basic'] = 'Base';
$lang['payroll_allowances'] = 'Indemnités';
$lang['payroll_bonus'] = 'Prime';
$lang['payroll_deduction'] = 'Retenue';
$lang['payroll_absence'] = 'Absence';
$lang['payroll_advance'] = 'Avance';
$lang['payroll_advances'] = 'Avances';
$lang['payroll_other'] = 'Autre';
$lang['payroll_overtime'] = 'Heures supplémentaires';
$lang['payroll_ot'] = 'HS';
$lang['payroll_ot_offday'] = 'HS / jour de repos';
$lang['payroll_offday_work'] = 'Travail un jour de repos / férié';
$lang['payroll_hrs'] = 'h';
$lang['payroll_days_short'] = 'jours';
$lang['payroll_total_earnings'] = 'Total des gains';
$lang['payroll_total_deductions'] = 'Total des retenues';
$lang['payroll_partial'] = 'Payé en partie';
$lang['payroll_unpaid'] = 'Non payé';
$lang['payroll_status_draft'] = 'Brouillon';
$lang['payroll_status_approved'] = 'Approuvée';
$lang['payroll_status_active'] = 'Actif';
$lang['payroll_status_inactive'] = 'Inactif';
$lang['payroll_type_monthly'] = 'Mensuel';
$lang['payroll_type_daily'] = 'Salaire journalier';
$lang['payroll_type_hourly'] = 'Horaire';
$lang['payroll_per_monthly'] = 'mois';
$lang['payroll_per_daily'] = 'jour';
$lang['payroll_per_hourly'] = 'heure';
$lang['payroll_mode_cash'] = 'Espèces';
$lang['payroll_mode_bank'] = 'Virement bancaire';
$lang['payroll_auto'] = 'Auto';
$lang['payroll_off'] = 'désactivé';
$lang['payroll_open'] = 'Ouvrir';
$lang['payroll_day'] = 'Jour';
$lang['payroll_from_date'] = 'Du';
$lang['payroll_to_date'] = 'Au';
$lang['payroll_reason'] = 'Motif';
$lang['payroll_signature'] = 'Signature';
$lang['payroll_not_set'] = 'Non configuré';
$lang['payroll_set_up'] = 'Configurer';
$lang['payroll_posted'] = 'Dans les dépenses';
$lang['payroll_expense'] = 'Dépense';
$lang['payroll_all_employees'] = 'Tous les employés';
$lang['payroll_all_months'] = 'Tous les mois';
$lang['payroll_all_types'] = 'Tous les types';
$lang['payroll_choose_employee'] = 'Choisissez un employé pour voir le salaire de chaque mois, les paiements et les avances.';

// day codes (attendance sheet, payslip)
$lang['payroll_code_P'] = 'Présent';
$lang['payroll_code_M'] = 'Présent, sans pointage de sortie';
$lang['payroll_code_H'] = 'Demi-journée';
$lang['payroll_code_A'] = 'Absent';
$lang['payroll_code_L'] = 'Congé payé';
$lang['payroll_code_U'] = 'Congé sans solde';
$lang['payroll_code_O'] = 'Repos hebdomadaire';
$lang['payroll_code_HO'] = 'Jour férié';
$lang['payroll_code_W'] = 'Travaillé un jour de repos / férié';
$lang['payroll_code_X'] = 'Pas encore embauché / parti';
$lang['payroll_code_F'] = 'Jour à venir';
$lang['payroll_present_short'] = 'P';
$lang['payroll_half_short'] = 'demi';
$lang['payroll_absent_short'] = 'A';
$lang['payroll_leave_short'] = 'L';
$lang['payroll_off_short'] = 'Repos';
$lang['payroll_holiday_short'] = 'Férié';
$lang['payroll_offday_worked_short'] = 'W';
$lang['payroll_not_employed_short'] = 'hors contrat';

// attendance figures
$lang['payroll_days_in_month'] = 'Jours dans le mois';
$lang['payroll_working_days'] = 'Jours ouvrés';
$lang['payroll_present_days'] = 'Jours de présence';
$lang['payroll_half_days'] = 'Demi-journées';
$lang['payroll_absent_days'] = 'Jours d\'absence';
$lang['payroll_leave_paid_unpaid'] = 'Congés (payés + sans solde)';
$lang['payroll_off_holidays'] = 'Repos / jours fériés';
$lang['payroll_offday_worked'] = 'Jours de repos travaillés';
$lang['payroll_worked_hours'] = 'Heures travaillées';
$lang['payroll_overtime_hours'] = 'Heures supplémentaires';
$lang['payroll_missing_checkout'] = 'Jours sans pointage de sortie';
$lang['payroll_not_employed'] = 'Jours hors contrat';
$lang['payroll_per_day_rate'] = 'Taux journalier';
$lang['payroll_calendar_days'] = 'Jours';
$lang['payroll_full_day'] = 'Journée complète';
$lang['payroll_half_day'] = 'Demi-journée';

// monthly payroll list
$lang['payroll_runs_hint'] = 'Une paie par mois, calculée à partir des présences et de la configuration des salaires. Générez, vérifiez, approuvez, puis payez.';
$lang['payroll_employees_on_payroll'] = 'Employés en paie';
$lang['payroll_employees_on_payroll_hint'] = 'avec une configuration de salaire active';
$lang['payroll_monthly_gross'] = 'Brut mensuel (fixe)';
$lang['payroll_monthly_gross_hint'] = 'base + indemnités du personnel mensuel';
$lang['payroll_not_generated'] = 'Non générée';
$lang['payroll_last_month'] = 'mois dernier';
$lang['payroll_balance_to_pay'] = 'reste à payer';
$lang['payroll_advances_open'] = 'Avances à récupérer';
$lang['payroll_advances_open_hint'] = 'versées, pas encore retenues';
$lang['payroll_no_setup_yet'] = 'Aucun employé n\'a encore de salaire. Commencez par la configuration du salaire de chaque employé.';
$lang['payroll_generate_title'] = 'Générer la paie';
$lang['payroll_generate_hint'] = 'Une nouvelle paie commence en brouillon : elle relit les présences à chaque ouverture, vous pouvez donc corriger les présences et voir le résultat aussitôt. Approuvez-la quand elle est juste.';
$lang['payroll_running'] = 'en cours';
$lang['payroll_generated_by'] = 'Générée par';
$lang['payroll_approved_by'] = 'Approuvée par';

// payroll sheet
$lang['payroll_print_sheet'] = 'Imprimer la feuille';
$lang['payroll_all_payslips'] = 'Tous les bulletins';
$lang['payroll_recalculate'] = 'Recalculer';
$lang['payroll_pay_all'] = 'Tout payer';
$lang['payroll_approve_confirm'] = 'Approuver cette paie ? Elle est verrouillée après approbation et les salaires peuvent alors être payés.';
$lang['payroll_draft_info'] = 'Brouillon : les chiffres sont recalculés à partir des présences à chaque ouverture de cette page. Utilisez le crayon pour corriger une ligne (jours d\'absence, heures sup., prime, retenue), puis approuvez.';
$lang['payroll_approved_info'] = 'Approuvée : cette paie est verrouillée. Payez les salaires ligne par ligne ou tous en une fois. Pour la modifier, supprimez ses paiements et rouvrez-la.';
$lang['payroll_month_running_warn'] = 'Ce mois est encore en cours : %d jour(s) à venir ne sont pas encore des absences (le personnel mensuel est payé pour ces jours, le personnel journalier et horaire ne l\'est pas). Approuvez après la fin du mois.';
$lang['payroll_missing_checkout_warn'] = '%d jour(s) ont un pointage d\'entrée sans pointage de sortie. Ils comptent comme %s sans heures supplémentaires (Paramètres de paie). Corrigez l\'heure de sortie dans Présence si besoin.';
$lang['payroll_negative_warn'] = '%d employé(s) ont un salaire net négatif. Réduisez les retenues ou étalez l\'avance sur plus de mois.';
$lang['payroll_missing_setup_warn'] = 'Hors de cette paie (pas de configuration de salaire) :';
$lang['payroll_edited'] = 'modifié';
$lang['payroll_override_hint'] = 'Modifié à la main sur cette feuille ; conservé quand les présences sont recalculées.';
$lang['payroll_no_attendance'] = 'aucune présence';
$lang['payroll_salary_payments'] = 'Paiements de salaire';
$lang['payroll_no_payments'] = 'Aucun paiement pour l\'instant.';
$lang['payroll_from_till_short'] = '(tiroir)';
$lang['payroll_delete_payment_confirm'] = 'Supprimer ce paiement ? Sa dépense est aussi supprimée.';
$lang['payroll_basis_note'] = 'Règles appliquées';
$lang['payroll_absent_on'] = 'absence retenue sur';
$lang['payroll_edit_line'] = 'Corriger la ligne';
$lang['payroll_edit_line_hint'] = 'Laissez une case vide pour utiliser la valeur des présences. Les valeurs saisies ici restent quand la paie est recalculée.';
$lang['payroll_from_attendance'] = 'Selon les présences';
$lang['payroll_reset_line'] = 'Revenir aux présences';
$lang['payroll_pay_salary'] = 'Payer le salaire';
$lang['payroll_pay_all_hint'] = 'Paie le solde restant de chaque employé (%s) avec le mode ci-dessous.';
$lang['payroll_record_expense'] = 'Ajouter aux dépenses';
$lang['payroll_from_till'] = 'Payé depuis le tiroir-caisse';
$lang['payroll_from_till_hint'] = 'Cochez « payé depuis le tiroir-caisse » seulement si l\'argent sort du tiroir de ce comptoir : la caisse ouverte le compte alors.';
$lang['payroll_from_till_closed'] = 'La caisse de ce comptoir est fermée, « payé depuis le tiroir-caisse » ne peut donc pas être utilisé maintenant.';

// salary setup
$lang['payroll_salary_setup_hint'] = 'Structure salariale de chaque employé. Seuls les employés avec une configuration active sont inclus dans la paie mensuelle.';
$lang['payroll_n_of_m_set'] = '%d sur %d configurés';
$lang['payroll_salary_type'] = 'Type de salaire';
$lang['payroll_basic_rate'] = 'Base / taux';
$lang['payroll_gross_month'] = 'Brut / mois';
$lang['payroll_weekly_off'] = 'Repos hebdomadaire';
$lang['payroll_joining_date'] = 'Date d\'embauche';
$lang['payroll_leaving_date'] = 'Date de départ';
$lang['payroll_left'] = 'parti le';
$lang['payroll_payment_mode'] = 'Mode de paiement';
$lang['payroll_no_login'] = 'sans connexion';
$lang['payroll_employees_from_users'] = 'Les employés sont les utilisateurs de Compte et utilisateur > Utilisateurs. Ajoutez-y le personnel qui ne se connecte pas avec « Va se connecter? » = Non, et saisissez sa présence à la main.';
$lang['payroll_pay_structure'] = 'Structure de rémunération';
$lang['payroll_basic_salary'] = 'Salaire de base';
$lang['payroll_basic_monthly'] = 'Salaire de base mensuel';
$lang['payroll_basic_daily'] = 'Taux journalier';
$lang['payroll_basic_hourly'] = 'Taux horaire';
$lang['payroll_overtime_rate'] = 'Taux heures sup. / heure';
$lang['payroll_overtime_rate_hint'] = 'Vide = calculé à partir de la base et du coefficient des heures supplémentaires.';
$lang['payroll_on_payroll'] = 'En paie';
$lang['payroll_monthly_allowances'] = 'Indemnités mensuelles et retenue fixe';
$lang['payroll_housing_allowance'] = 'Indemnité de logement';
$lang['payroll_transport_allowance'] = 'Indemnité de transport';
$lang['payroll_food_allowance'] = 'Indemnité de repas';
$lang['payroll_other_allowance'] = 'Autre indemnité';
$lang['payroll_fixed_deduction'] = 'Retenue fixe';
$lang['payroll_fixed_deduction_for'] = 'Objet de la retenue fixe';
$lang['payroll_fixed_deduction_example'] = 'ex. logement, assurance';
$lang['payroll_preview_gross'] = 'Brut par mois';
$lang['payroll_preview_daily'] = 'Environ (26 jours)';
$lang['payroll_preview_hourly'] = 'Environ (26 jours x heures standard)';
$lang['payroll_preview_perday'] = 'par jour';
$lang['payroll_preview_ot'] = 'heure sup. / heure';
$lang['payroll_work_calendar'] = 'Calendrier de travail';
$lang['payroll_weekly_off_hint'] = 'Jours de repos chaque semaine. Ce ne sont pas des absences ; les jours de repos travaillés peuvent être payés en plus (Paramètres de paie).';
$lang['payroll_leaving_hint'] = 'Dernier jour travaillé. Laissez vide tant que l\'employé est en poste.';
$lang['payroll_payment_details'] = 'Détails de paiement';
$lang['payroll_bank_name'] = 'Nom de la banque';
$lang['payroll_bank_account'] = 'N° de compte / IBAN';
$lang['payroll_salary_saved'] = 'Configuration du salaire enregistrée.';
$lang['payroll_leaving_before_joining'] = 'La date de départ est antérieure à la date d\'embauche.';

// advances & adjustments
$lang['payroll_adjustments_hint'] = 'Avances (espèces versées maintenant, récupérées sur le salaire), primes et indemnités supplémentaires (ajoutées), retenues comme les amendes (déduites). Chacune entre dans la paie du mois choisi.';
$lang['payroll_add_adjustment'] = 'Ajouter avance / prime / retenue';
$lang['payroll_adj_advance'] = 'Avance sur salaire';
$lang['payroll_adj_bonus'] = 'Prime';
$lang['payroll_adj_allowance'] = 'Indemnité supplémentaire';
$lang['payroll_adj_deduction'] = 'Retenue';
$lang['payroll_pay_month'] = 'Mois de paie';
$lang['payroll_pay_month_hint'] = 'Pour une avance : le premier mois où elle est retenue.';
$lang['payroll_installments'] = 'Mensualités';
$lang['payroll_installments_hint'] = 'Nombre de mois pour la récupérer.';
$lang['payroll_given_by'] = 'Versée par';
$lang['payroll_recovery'] = 'Récupération';
$lang['payroll_n_installments'] = '%d x %s';
$lang['payroll_one_installment'] = 'En une fois';
$lang['payroll_deducted'] = 'retenu sur le salaire';
$lang['payroll_added'] = 'ajouté au salaire';
$lang['payroll_adjustment_invalid'] = 'Choisissez un employé, un type, un montant supérieur à zéro et un mois de paie.';
$lang['payroll_month_approved'] = 'La paie de %s est déjà approuvée. Rouvrez-la d\'abord.';

// leaves
$lang['payroll_leaves_hint'] = 'Le congé payé n\'est pas retenu ; le congé sans solde est retenu comme une absence. Les repos hebdomadaires et jours fériés compris dans un congé ne sont pas comptés.';
$lang['payroll_add_leave'] = 'Ajouter un congé';
$lang['payroll_leave_paid'] = 'Congé payé';
$lang['payroll_leave_unpaid'] = 'Congé sans solde';
$lang['payroll_leave_rule'] = 'Un jour avec présence reste un jour de présence, même pendant un congé.';
$lang['payroll_leave_invalid'] = 'Choisissez un employé et une période valide (un an au maximum).';
$lang['payroll_leave_overlap'] = 'Cet employé a déjà un congé sur ces dates.';

// holidays
$lang['payroll_holidays_hint'] = 'Jours fériés pour tout le monde : jamais une absence, et le travail un jour férié peut être payé comme un jour en plus.';
$lang['payroll_add_holiday'] = 'Ajouter un jour férié';
$lang['payroll_same_day'] = 'même jour';
$lang['payroll_holiday_example'] = 'ex. Fête nationale';
$lang['payroll_holiday_invalid'] = 'Indiquez une date, un nom, et 31 jours au maximum.';

// settings
$lang['payroll_settings_hint'] = 'Comment la présence devient un salaire. Les changements s\'appliquent aussitôt aux paies en brouillon ; les paies approuvées gardent leurs chiffres.';
$lang['payroll_monthly_salary_rules'] = 'Salaire mensuel';
$lang['payroll_month_days_basis'] = 'Taux journalier = salaire mensuel divisé par';
$lang['payroll_basis_calendar'] = 'Jours du mois (28-31)';
$lang['payroll_basis_fixed30'] = 'Toujours 30 jours';
$lang['payroll_basis_working'] = 'Jours ouvrés du mois';
$lang['payroll_month_days_basis_hint'] = 'Sert à retenir un jour d\'absence et à payer un jour de repos travaillé.';
$lang['payroll_absent_deduction_on'] = 'Le jour d\'absence est retenu sur';
$lang['payroll_on_gross'] = 'le brut (base + indemnités)';
$lang['payroll_on_basic'] = 'la base seulement';
$lang['payroll_absent_deduction_on_hint'] = 'Taux journalier = ce montant / les jours ci-dessus.';
$lang['payroll_pay_offday_work'] = 'Payer le travail un jour de repos / férié comme un jour en plus';
$lang['payroll_pay_offday_work_hint'] = 'Personnel mensuel seulement ; le personnel journalier et horaire est de toute façon payé pour chaque jour ou heure travaillé.';
$lang['payroll_default_weekly_off'] = 'Repos hebdomadaire par défaut des nouvelles configurations de salaire';
$lang['payroll_default_weekly_off_hint'] = 'Chaque employé peut avoir son propre repos hebdomadaire dans la configuration du salaire.';
$lang['payroll_attendance_rules'] = 'Présence';
$lang['payroll_standard_hours'] = 'Heures d\'une journée complète';
$lang['payroll_standard_hours_hint'] = 'Au-delà, ce sont des heures supplémentaires.';
$lang['payroll_half_day_hours'] = 'Demi-journée en dessous de (heures)';
$lang['payroll_half_day_hours_hint'] = 'Moins que cela = demi-journée.';
$lang['payroll_missing_checkout_rule'] = 'Un jour avec pointage d\'entrée sans sortie compte comme';
$lang['payroll_missing_checkout_rule_hint'] = 'Le personnel qui ne se déconnecte jamais n\'a pas d\'heure de sortie. Ses heures sont prises comme une journée standard complète (ou une demi-journée), sans heures supplémentaires.';
$lang['payroll_overtime_enabled'] = 'Payer les heures supplémentaires';
$lang['payroll_overtime_multiplier'] = 'Coefficient des heures supplémentaires';
$lang['payroll_overtime_multiplier_hint'] = 'Taux horaire x ce coefficient, ex. 1.25';
$lang['payroll_overtime_min_minutes'] = 'Ignorer les heures sup. en dessous de (minutes / jour)';
$lang['payroll_overtime_min_minutes_hint'] = 'Quelques minutes en plus ne sont pas des heures supplémentaires.';
$lang['payroll_payments_expenses'] = 'Paiements et dépenses';
$lang['payroll_record_expense_default'] = 'Cocher « Ajouter aux dépenses » par défaut lors du paiement';
$lang['payroll_expense_category'] = 'Catégorie de dépense des salaires';
$lang['payroll_expense_category_auto'] = 'Salaires & traitements (créée automatiquement)';
$lang['payroll_payslip_footer'] = 'Pied de page du bulletin';
$lang['payroll_payslip_footer_example'] = 'ex. Ce bulletin de paie est généré par ordinateur.';

// reports
$lang['payroll_attendance_sheet_hint'] = 'Chaque employé et chaque jour du mois, lus de la même façon que la paie.';
$lang['payroll_attendance_sheet_note'] = 'Les employés sans configuration de salaire utilisent le repos hebdomadaire par défaut. Corrigez un jour dans Présence > Liste Présence, ajoutez les congés et jours fériés ici.';
$lang['payroll_not_on_payroll_hint'] = 'Pas de configuration de salaire : non inclus dans la paie.';
$lang['payroll_salary_history_hint'] = 'Le salaire de chaque mois d\'un employé, avec les paiements et les avances.';
$lang['payroll_monthly_salaries'] = 'Salaires mensuels';
$lang['payroll_no_lines'] = 'Aucune paie pour cet employé cette année.';
$lang['payroll_advance_balance'] = 'Solde d\'avance';
$lang['payroll_given'] = 'Versé';
$lang['payroll_recovered'] = 'Récupéré';
$lang['payroll_no_advances'] = 'Aucune avance.';
$lang['payroll_advance_balances_hint'] = 'Avances versées et montant restant à récupérer sur le salaire.';
$lang['payroll_advance_balances_note'] = 'Récupéré = retenues d\'avance dans les paies approuvées. Les paies en brouillon ne sont pas encore comptées.';
$lang['payroll_last_advance'] = 'Dernière avance';
$lang['payroll_give_advance'] = 'Verser une avance';

// payslip, printed sheet
$lang['payroll_payslip'] = 'Bulletin de paie';
$lang['payroll_payslips'] = 'Bulletins de paie';
$lang['payroll_employee_no'] = 'N° d\'employé';
$lang['payroll_monthly_basic'] = 'base mensuelle';
$lang['payroll_advance_recovery'] = 'Remboursement d\'avance';
$lang['payroll_no_deductions'] = 'Aucune retenue';
$lang['payroll_employer_signature'] = 'Signature de l\'employeur';
$lang['payroll_employee_signature'] = 'Signature de l\'employé';
$lang['payroll_register'] = 'Livre de paie';
$lang['payroll_printed'] = 'imprimé le';
$lang['payroll_prepared_by'] = 'Préparé par';
$lang['payroll_checked_by'] = 'Vérifié par';

// messages
$lang['payroll_not_found'] = 'Introuvable.';
$lang['payroll_month_invalid'] = 'Choisissez ce mois-ci ou un mois antérieur.';
$lang['payroll_already_generated'] = 'La paie de ce mois existe déjà ; la voici.';
$lang['payroll_no_eligible'] = 'Aucun employé avec une configuration de salaire active pour ce mois.';
$lang['payroll_generated'] = 'Paie générée en brouillon. Vérifiez-la, puis approuvez-la.';
$lang['payroll_locked'] = 'Cette paie est approuvée et verrouillée.';
$lang['payroll_recalculated'] = 'Paie recalculée à partir des présences.';
$lang['payroll_absent_too_many'] = 'Les jours d\'absence ne peuvent pas dépasser les jours du mois.';
$lang['payroll_approved_msg'] = 'Paie approuvée. Les salaires peuvent maintenant être payés.';
$lang['payroll_reopen_has_payments'] = 'Supprimez les paiements de salaire de ce mois avant de rouvrir la paie.';
$lang['payroll_reopened_msg'] = 'Paie rouverte en brouillon.';
$lang['payroll_delete_only_draft'] = 'Seule une paie en brouillon peut être supprimée. Rouvrez-la d\'abord.';
$lang['payroll_choose_method'] = 'Choisissez un mode de paiement.';
$lang['payroll_till_needs_register'] = 'Ouvrez d\'abord la caisse de ce comptoir, ou décochez « payé depuis le tiroir-caisse ».';
$lang['payroll_pay_needs_approval'] = 'Approuvez la paie avant de payer les salaires.';
$lang['payroll_amount_invalid'] = 'Le montant doit être supérieur à zéro et ne pas dépasser le solde.';
$lang['payroll_payment_saved'] = 'Paiement de salaire enregistré.';
$lang['payroll_nothing_to_pay'] = 'Plus rien à payer.';
$lang['payroll_paid_n'] = 'Salaire payé à %d employé(s).';
