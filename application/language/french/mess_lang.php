<?php
/*
 | Mess module (monthly tiffin subscriptions).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 */

// module, menu and role screen
$lang['mess'] = 'Cantine';
$lang['mess_dashboard'] = 'Cantine aujourd\'hui';
$lang['mess_today'] = 'Aujourd\'hui';
$lang['mess_plans'] = 'Formules cantine';
$lang['mess_plan'] = 'Formule';
$lang['mess_members'] = 'Abonnés cantine';
$lang['mess_member'] = 'Abonné';
$lang['mess_subscriptions'] = 'Abonnements';
$lang['mess_subscription'] = 'Abonnement';
$lang['mess_payments'] = 'Paiements cantine';
$lang['mess_payment'] = 'Paiement cantine';
$lang['mess_daily_menu'] = 'Menu du jour';
$lang['mess_meal_register'] = 'Registre des repas';
$lang['mess_extras'] = 'Suppléments';
$lang['mess_extra'] = 'Supplément';
$lang['mess_daily_sheet'] = 'Fiche du jour';
$lang['mess_reports'] = 'Rapports cantine';
$lang['mess_settings'] = 'Paramètres cantine';
$lang['mess_collection'] = 'Encaissement cantine';
$lang['mess_dues'] = 'Impayés';
$lang['mess_expiring'] = 'Échéances / Renouveler';
$lang['reopen'] = 'Rouvrir';
$lang['mess_tab_members'] = 'Abonnés';
$lang['mess_tab_renewals'] = 'Renouvellements';
$lang['mess_tab_payments'] = 'Paiements';
$lang['mess_tab_plans'] = 'Formules';
$lang['mess_tab_reports'] = 'Rapports';
$lang['mess_tab_settings'] = 'Paramètres';

// slots, diet, mode
$lang['mess_slot'] = 'Repas';
$lang['mess_slots'] = 'Créneaux de repas';
$lang['mess_breakfast'] = 'Petit-déjeuner';
$lang['mess_lunch'] = 'Déjeuner';
$lang['mess_dinner'] = 'Dîner';
$lang['mess_diet'] = 'Régime';
$lang['mess_veg'] = 'Végétarien';
$lang['mess_nonveg'] = 'Non végétarien';
$lang['mess_both'] = 'Les deux';
$lang['mess_mode'] = 'Mode';
$lang['mess_pickup'] = 'À emporter';
$lang['mess_delivery'] = 'Livraison';
$lang['mess_persons'] = 'Personnes';
$lang['mess_meals'] = 'Repas';
$lang['mess_meals_persons'] = 'Repas x personnes';
$lang['mess_meals_persons_hint'] = 'Personnes par repas. « Déjeuner + 2 Dîner » = Déjeuner 1, Dîner 2.';

// status
$lang['mess_status_active'] = 'Actif';
$lang['mess_status_expiring'] = 'Bientôt échu';
$lang['mess_status_expired'] = 'Échu';
$lang['mess_status_upcoming'] = 'À venir';
$lang['mess_status_cancelled'] = 'Annulé';
$lang['mess_status_paused'] = 'En pause aujourd\'hui';
$lang['mess_status_inactive'] = 'Inactif';
$lang['mess_current'] = 'En cours';
$lang['mess_renewed'] = 'Renouvelé';

// plans
$lang['mess_add_plan'] = 'Ajouter une formule';
$lang['mess_edit_plan'] = 'Modifier la formule';
$lang['mess_plan_name'] = 'Nom de la formule';
$lang['mess_price_per_person'] = 'Prix par personne / cycle';
$lang['mess_per_meal_rate'] = 'Par repas (prorata)';
$lang['mess_cycle'] = 'Cycle';
$lang['mess_cycle_days'] = 'Jours du cycle';
$lang['mess_included_meals'] = 'Repas inclus';
$lang['mess_plan_hint'] = 'Une formule est un tarif, pas un contrat. Le personnel peut saisir un prix convenu sur chaque abonnement. Prix par repas = prix / (jours du cycle x repas par jour), utilisé pour les cycles courts.';
$lang['mess_plan_need_slot'] = 'Cochez au moins un repas.';
$lang['mess_plan_in_use'] = 'Cette formule est utilisée par des abonnements, elle a été désactivée au lieu d\'être supprimée.';

// members
$lang['mess_add_member'] = 'Ajouter un abonné';
$lang['mess_edit_member'] = 'Modifier l\'abonné';
$lang['mess_member_code'] = 'Code abonné';
$lang['mess_phone2'] = 'Téléphone 2';
$lang['mess_building_room'] = 'Immeuble / chambre';
$lang['mess_default_diet'] = 'Régime préféré';
$lang['mess_default_mode'] = 'À emporter ou livraison';
$lang['mess_member_hint'] = 'Un abonné est une personne ou une chambre, créé une seule fois. Chaque mois est un nouvel abonnement du même abonné.';
$lang['mess_phone_used_by'] = 'Ce téléphone est déjà utilisé par';
$lang['mess_member_has_records'] = 'Cet abonné a des abonnements ou des paiements. Il a été rendu inactif au lieu d\'être supprimé.';
$lang['mess_last_cycle_end'] = 'Fin du dernier cycle';
$lang['mess_last_payment'] = 'Dernier paiement';
$lang['mess_no_subscription'] = 'Aucun abonnement';
$lang['mess_member_profile'] = 'Profil de l\'abonné';
$lang['mess_ledger'] = 'Relevé';
$lang['mess_charge'] = 'Débit';
$lang['mess_paid'] = 'Payé';
$lang['mess_balance_due'] = 'Solde dû';
$lang['mess_advance_credit'] = 'Avoir';
$lang['mess_print_statement'] = 'Imprimer le relevé';
$lang['mess_statement'] = 'Relevé de l\'abonné';
$lang['mess_whatsapp_reminder'] = 'Rappel WhatsApp';
$lang['mess_whatsapp'] = 'WhatsApp';
$lang['mess_no_entries'] = 'Aucune écriture';
$lang['mess_active_members'] = 'Abonnés actifs';

// subscriptions
$lang['mess_new_subscription'] = 'Nouvel abonnement';
$lang['mess_edit_subscription'] = 'Modifier l\'abonnement';
$lang['mess_renew'] = 'Renouveler';
$lang['mess_renew_cycle'] = 'Renouveler le cycle';
$lang['mess_renewing_from'] = 'Renouvellement du cycle qui se termine le';
$lang['mess_suggested_price'] = 'Prix suggéré';
$lang['mess_agreed_price'] = 'Prix convenu';
$lang['mess_price_note'] = 'Note sur le prix';
$lang['mess_price_note_ph'] = 'ex. ancien client, tarif chauffeur';
$lang['mess_delivery_address'] = 'Adresse de livraison';
$lang['mess_end_date_auto'] = 'La date de fin = date de début + jours du cycle';
$lang['mess_advance_now'] = 'Encaisser maintenant';
$lang['mess_advance_now_hint'] = 'Facultatif. Va dans la caisse du jour comme tout paiement cantine.';
$lang['mess_save_subscription'] = 'Enregistrer l\'abonnement';
$lang['mess_overlap_warning'] = 'Attention : cet abonné a déjà un cycle sur ces dates';
$lang['mess_meals_required'] = 'Saisissez des personnes pour au moins un repas.';
$lang['mess_dates_invalid'] = 'La date de fin doit être le jour du début ou après.';
$lang['mess_agreed_price_required'] = 'Le prix convenu est obligatoire.';
$lang['mess_cancel_subscription'] = 'Annuler l\'abonnement';
$lang['mess_cancel_confirm'] = 'Annuler cet abonnement ? Son montant est retiré du relevé et plus aucun repas n\'est attendu.';
$lang['mess_sub_has_payments'] = 'Cet abonnement a des paiements. Il a été annulé au lieu d\'être supprimé ; les paiements restent au relevé comme avoir.';
$lang['mess_pause'] = 'Pause';
$lang['mess_pauses'] = 'Pauses (congé)';
$lang['mess_add_pause'] = 'Ajouter une pause';
$lang['mess_pause_hint'] = 'Abonné en voyage : la date de fin avance d\'autant de jours et aucun repas n\'est attendu ces jours-là.';
$lang['mess_pause_dates_invalid'] = 'La fin de la pause doit être le jour de son début ou après.';
$lang['mess_pause_outside_cycle'] = 'La pause doit commencer dans les dates de l\'abonnement.';
$lang['mess_pause_overlaps'] = 'Cette pause chevauche une autre pause.';
$lang['mess_pause_added'] = 'Pause enregistrée, date de fin repoussée.';
$lang['mess_paused_days'] = 'Jours de pause';
$lang['mess_days_left'] = 'Jours restants';
$lang['mess_ends_today'] = 'se termine aujourd\'hui';
$lang['mess_ends_in'] = 'se termine dans';
$lang['mess_ended'] = 'terminé depuis';
$lang['mess_expiring_list'] = 'Bientôt échus';
$lang['mess_expired_not_renewed'] = 'Échus, non renouvelés';
$lang['mess_expired_not_renewed_hint'] = 'Cycles terminés dans les 45 derniers jours sans cycle plus récent pour l\'abonné.';
$lang['mess_within_days'] = 'sous (jours)';

// payments
$lang['mess_receive_payment'] = 'Recevoir un paiement';
$lang['mess_payment_type'] = 'Type d\'écriture';
$lang['mess_type_payment'] = 'Paiement (entrée d\'argent)';
$lang['mess_type_refund'] = 'Remboursement (sortie d\'argent)';
$lang['mess_type_adjustment'] = 'Ajustement (sans argent)';
$lang['mess_adjustment'] = 'Ajustement';
$lang['mess_adjust_credit'] = 'Crédit : réduire le dû (remise, geste)';
$lang['mess_adjust_debit'] = 'Débit : ajouter un montant';
$lang['mess_against_subscription'] = 'Pour l\'abonnement';
$lang['mess_general_advance'] = 'Général (sans abonnement)';
$lang['mess_amount_positive'] = 'Le montant doit être supérieur à zéro.';
$lang['mess_payment_method_required'] = 'Le mode de paiement est obligatoire pour un paiement ou un remboursement.';
$lang['mess_payment_saved'] = 'Paiement enregistré.';
$lang['mess_print_receipt'] = 'Imprimer le reçu';
$lang['mess_receipt'] = 'Reçu cantine';
$lang['mess_receipt_title'] = 'REÇU DE PAIEMENT CANTINE';
$lang['mess_received_from'] = 'Reçu de';
$lang['mess_received_by'] = 'Reçu par';
$lang['mess_balance_after'] = 'Solde après ce paiement';
$lang['mess_thank_you'] = 'Merci';
$lang['mess_payment_closed_register'] = 'Ce paiement appartient à une caisse déjà clôturée. Seul l\'administrateur peut le supprimer.';
$lang['mess_register_needed'] = 'Les paiements cantine exigent une caisse ouverte (Paramètres cantine). Ouvrez d\'abord la caisse.';
$lang['mess_refund_adjust_admin'] = 'Remboursement et ajustement exigent l\'administrateur ou le droit de suppression des paiements cantine.';
$lang['mess_collection_today'] = 'Encaissé aujourd\'hui';
$lang['mess_total_dues'] = 'Total impayés';
$lang['mess_members_with_due'] = 'abonnés avec impayé';
$lang['mess_payments_count'] = 'paiements';

// register card
$lang['mess_collection_plus'] = 'Encaissement cantine (+)';
$lang['mess_collection_register_hint'] = 'Paiements cantine pris sur cette caisse, inclus dans l\'espèce en main';

// daily menu
$lang['mess_menu_for'] = 'Menu du';
$lang['mess_dishes'] = 'Plats';
$lang['mess_dish'] = 'Plat';
$lang['mess_add_dish'] = 'Ajouter un plat';
$lang['mess_portion_per_person'] = 'Portion / personne';
$lang['mess_buffer'] = 'Marge';
$lang['mess_expected_persons'] = 'Personnes attendues';
$lang['mess_planned_portions'] = 'Portions prévues';
$lang['mess_copy_yesterday'] = 'Copier hier';
$lang['mess_copy_last_week'] = 'Copier le même jour la semaine passée';
$lang['mess_copy_from'] = 'Copier depuis une date';
$lang['mess_copy_done'] = 'plats copiés';
$lang['mess_copy_confirm'] = 'Remplacer le menu de ce jour par la copie ?';
$lang['mess_menu_text'] = 'Texte du menu (WhatsApp)';
$lang['mess_menu_missing'] = 'menu manquant';
$lang['mess_menu_saved'] = 'Menu enregistré';
$lang['mess_no_recipe'] = 'sans recette';
$lang['mess_no_recipe_hint'] = 'Ce plat n\'a pas de recette dans les menus, le stock ne bougera pas pour lui.';
$lang['mess_free_text_dish'] = 'ou saisissez un nom de plat';
$lang['mess_pick_food_menu'] = '-- Plat du menu (avec recette) --';
$lang['mess_expected_hint'] = 'Les personnes attendues viennent en direct des abonnements du jour moins les absences.';
$lang['mess_copied_to_clipboard'] = 'Copié';
$lang['mess_copy_text'] = 'Copier le texte';

// meal register
$lang['mess_expected'] = 'Attendus';
$lang['mess_served'] = 'Servi';
$lang['mess_pending'] = 'En attente';
$lang['mess_not_taken'] = 'Non pris';
$lang['mess_skipped'] = 'Absent';
$lang['mess_partial'] = 'Partiel';
$lang['mess_undo'] = 'Annuler';
$lang['mess_mark_all_served'] = 'Marquer tout l\'attente comme Servi';
$lang['mess_mark_all_not_taken'] = 'Marquer tout l\'attente comme Non pris';
$lang['mess_bulk_confirm'] = 'Marquer toutes les lignes en attente ?';
$lang['mess_search_member'] = 'Rechercher nom / téléphone / immeuble';
$lang['mess_print_tick_sheet'] = 'Imprimer la feuille de pointage';
$lang['mess_no_members_expected'] = 'Aucun abonné n\'attend ce repas. Vérifiez la date, le repas ou les abonnements.';
$lang['mess_skip_ahead'] = 'Absence prévue';
$lang['mess_skip_ahead_hint'] = '« Je ne viens pas demain » : le repas sort du décompte, la cuisine prépare moins.';
$lang['mess_skip_saved'] = 'Absence enregistrée';
$lang['mess_skip_not_expected'] = 'Cet abonné n\'attend pas ce repas à cette date.';
$lang['mess_skip_already_marked'] = 'Ce repas est déjà pointé et ne peut pas être marqué absent.';
$lang['mess_served_at'] = 'Servi';
$lang['mess_flip_diet'] = 'Basculer Végétarien / Non végétarien pour aujourd\'hui';
$lang['mess_attendance_last_30'] = 'Repas des 30 derniers jours';
$lang['mess_future_only_skip'] = 'Un repas d\'une date future peut seulement être marqué absent.';
$lang['mess_invalid_action'] = 'Action inconnue.';

// extras
$lang['mess_add_extra'] = 'Ajouter un supplément';
$lang['mess_settle'] = 'Règlement';
$lang['mess_pay_now'] = 'Payer maintenant';
$lang['mess_on_account'] = 'Sur le compte';
$lang['mess_on_account_hint'] = 'Ajouté au solde de l\'abonné, encaissé avec le mensuel.';
$lang['mess_pay_now_hint'] = 'Un paiement cantine est créé maintenant et arrive dans la caisse ce soir.';
$lang['mess_custom_item'] = 'Article libre';
$lang['mess_item_name'] = 'Nom de l\'article';
$lang['mess_unit_price'] = 'Prix unitaire';
$lang['mess_extra_saved'] = 'Supplément enregistré';
$lang['mess_mark_delivered'] = 'Marquer livré';
$lang['mess_extra_status_requested'] = 'Demandé';
$lang['mess_extra_status_delivered'] = 'Livré';
$lang['mess_extra_status_cancelled'] = 'Annulé';
$lang['mess_cancel_extra'] = 'Annuler le supplément';
$lang['mess_extras_today'] = 'Suppléments du jour';
$lang['mess_extra_paid_cancel_note'] = 'Ce supplément a été payé sur place. L\'annuler garde le paiement comme avoir (remboursez-le depuis Recevoir un paiement si besoin).';

// daily sheet
$lang['mess_sheet_title'] = 'FICHE CANTINE DU JOUR';
$lang['mess_headcount'] = 'Effectif';
$lang['mess_production_plan'] = 'Plan de production';
$lang['mess_ingredient_need'] = 'Besoin en ingrédients (recette x portions)';
$lang['mess_stock'] = 'Stock';
$lang['mess_short'] = 'manque';
$lang['mess_ok'] = 'ok';
$lang['mess_member_checklist'] = 'Liste des abonnés';
$lang['mess_sign'] = 'Signature';
$lang['mess_printed_by'] = 'imprimé par';
$lang['mess_stock_shortage'] = 'Manque de stock';
$lang['mess_delivery_route'] = 'Liste de livraison';
$lang['mess_skipped_advance'] = 'Absences prévues';
$lang['mess_portions'] = 'Portions';

// today dashboard
$lang['mess_expected_today'] = 'attendus';
$lang['mess_menu_today'] = 'Menu du jour';
$lang['mess_needs_attention'] = 'À traiter';
$lang['mess_open_register'] = 'Ouvrir le registre des repas';
$lang['mess_quick_actions'] = 'Actions rapides';
$lang['mess_nothing_to_show'] = 'Rien à afficher';

// reports
$lang['mess_attendance_matrix'] = 'Grille de présence';
$lang['mess_attendance_matrix_hint'] = 'S servi, P partiel, N non pris, K absent, - en attente, vide non abonné';
$lang['mess_daily_served_summary'] = 'Résumé des repas servis';
$lang['mess_collection_report'] = 'Rapport d\'encaissement';
$lang['mess_by_method'] = 'Par mode de paiement';
$lang['mess_member_x_day'] = 'Abonné x jour';

// settings
$lang['mess_settings_saved'] = 'Paramètres cantine enregistrés';
$lang['mess_slot_on'] = 'Actif';
$lang['mess_from_time'] = 'De';
$lang['mess_to_time'] = 'À';
$lang['mess_default_cycle_days'] = 'Jours de cycle par défaut';
$lang['mess_expiring_days'] = 'Alerte d\'échéance (jours avant la fin)';
$lang['mess_skip_credit_policy'] = 'Politique d\'avoir sur absence';
$lang['mess_skip_credit_none'] = 'Pas d\'avoir, les absences réduisent seulement la cuisine';
$lang['mess_skip_credit_informed'] = 'Avoir si prévenu avant le repas (ajustement manuel)';
$lang['mess_skip_credit_always'] = 'Toujours un avoir (ajustement manuel)';
$lang['mess_require_open_register'] = 'Exiger une caisse ouverte pour les paiements cantine';
$lang['mess_require_open_register_hint'] = 'Recommandé. L\'argent pris sans caisse ouverte n\'arrive jamais dans une clôture, l\'espèce en main ne correspondra pas.';
$lang['mess_member_code_prefix'] = 'Préfixe du code abonné';
$lang['mess_whatsapp_country_code'] = 'Indicatif pays WhatsApp';
$lang['mess_whatsapp_templates'] = 'Modèles de texte WhatsApp';
$lang['mess_template_menu'] = 'Menu du jour';
$lang['mess_template_due'] = 'Rappel d\'impayé';
$lang['mess_template_expiry'] = 'Rappel d\'échéance';
$lang['mess_template_vars'] = 'Variables : {name} {amount} {currency} {end_date} {slot} {date} {menu}';
$lang['mess_roles_hint'] = 'Donnez à chaque rôle seulement ce qu\'il lui faut dans Paramètres > Rôles : Caisse = Registre des repas, Suppléments, Paiements, Aujourd\'hui. Cuisine = Aujourd\'hui, Menu du jour, Fiche du jour.';

// day close (stock)
$lang['mess_day_close'] = 'Clôture du repas';
$lang['mess_cooked'] = 'Cuisiné';
$lang['mess_leftover'] = 'Reste';
$lang['mess_leftover_goes_to'] = 'Le reste va à';
$lang['mess_waste'] = 'Perte';
$lang['mess_carry'] = 'Reporter au repas suivant';
$lang['mess_staff_meal'] = 'Repas du personnel';
$lang['mess_food_cost'] = 'Coût matière';
$lang['mess_waste_cost'] = 'Coût des pertes';
$lang['mess_cost_per_meal'] = 'Coût par repas servi';
$lang['mess_served_portions'] = 'Servi (est.)';
$lang['mess_close_and_post'] = 'Clôturer et passer au stock';
$lang['mess_close_confirm'] = 'Clôturer ce repas ? Les portions cuisinées x recette sortent du stock, le reste jeté est passé en perte.';
$lang['mess_close_hint'] = 'Confirmez ce que la cuisine a réellement cuisiné et ce qui reste. La clôture passe la consommation d\'ingrédients (cuisiné moins jeté, x recette) au stock et le reste jeté en perte, donc les écrans de stock bougent exactement de ce qui a été cuisiné. Un reste reporté ou en repas du personnel compte comme utilisé. Les plats sans recette ne bougent pas le stock.';
$lang['mess_already_closed'] = 'Ce repas est déjà clôturé. Rouvrez-le d\'abord.';
$lang['mess_close_failed'] = 'La clôture n\'a pas pu être enregistrée, rien n\'a été passé. Réessayez.';
$lang['mess_close_future'] = 'Un repas se clôture le jour même ou après.';
$lang['mess_closed_ok'] = 'Repas clôturé, stock mis à jour.';
$lang['mess_reopened_ok'] = 'Repas rouvert, le mouvement de stock a été annulé.';
$lang['mess_reopen_confirm'] = 'Rouvrir ce repas ? La consommation et la perte passées au stock seront annulées.';
$lang['mess_closed_by'] = 'par';
$lang['mess_pending_warning'] = 'Des abonnés sont encore en attente dans le registre des repas. Pointez-les d\'abord pour des chiffres justes.';
$lang['mess_open_waste'] = 'Ouvrir la perte';
$lang['mess_status_closed'] = 'Clôturé';
$lang['mess_status_open'] = 'Ouvert';
$lang['mess_day_close_report'] = 'Clôtures et coût matière';
$lang['mess_meals_served'] = 'Repas servis';
$lang['mess_not_closed'] = 'Non clôturé';
