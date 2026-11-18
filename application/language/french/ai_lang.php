<?php
/*
 | AI Insight module (docs/ai_insight_plan.md).
 | Loaded by config/autoload.php (English fallback) and by Common_model in the
 | user's language. Keep the four language files in the same key order.
 | The AI-written insight text itself is always English (client decision).
 */

// module, menu and role screen
$lang['ai_insight'] = 'Analyse IA';
$lang['ai_settings'] = 'Paramètres IA';
$lang['ai_companies'] = 'Sociétés IA';
$lang['ai_ask'] = 'Demander à l\'IA';
$lang['ai_regenerate'] = 'Régénérer le rapport';

// page
$lang['ai_insight_hint'] = 'Analyse quotidienne de ce point de vente : ce qui se vend, ce qui est sous-évalué, où se perdent le stock et l\'argent.';
$lang['ai_outlet'] = 'Point de vente';
$lang['ai_not_enabled'] = 'L\'analyse IA n\'est pas activée pour votre société. Contactez votre administrateur.';
$lang['ai_not_ready'] = 'L\'analyse IA n\'est pas encore installée : exécutez Update/ai_insight_migration.sql.';

// data health
$lang['ai_data_health'] = 'Qualité des données';
$lang['ai_data_health_hint'] = 'Niveau de complétude des données de ce point de vente. Les analyses ne valent que ces données.';
$lang['ai_check'] = 'Contrôle';
$lang['ai_result'] = 'Résultat';
$lang['ai_what_to_do'] = 'Que faire';
$lang['ai_used_for'] = 'Nécessaire pour';
$lang['ai_status_good'] = 'Bon';
$lang['ai_status_watch'] = 'À surveiller';
$lang['ai_status_missing'] = 'Manquant';
$lang['ai_status_na'] = 'Non applicable';
$lang['ai_checked_at'] = 'Contrôlé le %s';
$lang['ai_open'] = 'Ouvrir';
$lang['ai_warehouse_note'] = 'Aucune vente sur ce point de vente ces %s derniers jours : il est traité comme un point de stock uniquement (achats, transferts et stock).';
$lang['ai_h_recipe_cover'] = 'Articles vendus avec un coût de recette';
$lang['ai_h_recipe_cover_val'] = '%s%% de la quantité vendue ces %s derniers jours';
$lang['ai_h_recipe_cover_fix'] = 'Ajoutez une recette (ingrédients et quantités) aux articles listés ci-dessous. L\'import de recettes accepte jusqu\'à 1000 menus à la fois.';
$lang['ai_h_no_sales'] = 'Aucune vente ces %s derniers jours';
$lang['ai_h_menus_recipe'] = 'Menus avec une recette';
$lang['ai_h_menus_recipe_val'] = '%s sur %s menus de ce point de vente ont un coût de recette';
$lang['ai_h_menus_recipe_fix'] = 'Les menus sans recette ont un coût de 0, leur marge ne peut donc pas être évaluée.';
$lang['ai_h_ing_price'] = 'Prix des ingrédients';
$lang['ai_h_ing_price_val'] = '%s sur %s ingrédients utilisés dans les recettes n\'ont pas de prix';
$lang['ai_h_ing_price_ok'] = 'Les %s ingrédients utilisés dans les recettes ont tous un prix';
$lang['ai_h_ing_price_none'] = 'Aucune recette pour le moment, donc aucun prix d\'ingrédient à contrôler';
$lang['ai_h_ing_price_fix'] = 'Saisissez un achat pour eux, ou indiquez le prix sur l\'ingrédient.';
$lang['ai_h_purchases'] = 'Achats saisis';
$lang['ai_h_purchases_val'] = '%s achats ces %s derniers jours (dernier le %s)';
$lang['ai_h_purchases_old'] = 'Aucun achat ces %s derniers jours (dernier le %s)';
$lang['ai_h_purchases_none'] = 'Aucun achat saisi pour le moment';
$lang['ai_h_purchases_fix'] = 'Saisissez chaque achat avec son prix le jour de sa réception.';
$lang['ai_h_waste'] = 'Pertes saisies';
$lang['ai_h_waste_val'] = '%s pertes saisies ces %s derniers jours (dernière le %s)';
$lang['ai_h_waste_none'] = 'Aucune perte saisie ces %s derniers jours';
$lang['ai_h_waste_fix'] = 'Enregistrez les pertes chaque jour. Des semaines sans perte signifient souvent qu\'elles ne sont pas enregistrées.';
$lang['ai_h_negative'] = 'Stock négatif';
$lang['ai_h_negative_val'] = '%s ingrédients ont un stock inférieur à zéro';
$lang['ai_h_negative_ok'] = 'Aucun ingrédient sous zéro';
$lang['ai_h_negative_fix'] = 'Un stock sous zéro signifie que des achats ou transferts n\'ont pas été saisis. Saisissez-les ou corrigez le stock par un ajustement d\'inventaire.';
$lang['ai_h_history'] = 'Historique des ventes';
$lang['ai_h_history_val'] = '%s jours avec ventes ces %s derniers jours (première vente le %s)';
$lang['ai_h_history_fix'] = 'Les prévisions démarrent après 4 semaines de ventes ; 8 semaines donnent une bonne précision.';
$lang['ai_h_count'] = 'Dernier inventaire';
$lang['ai_h_count_val'] = 'Dernier ajustement d\'inventaire le %s (il y a %s jours)';
$lang['ai_h_count_none'] = 'Aucun inventaire (ajustement d\'inventaire) enregistré';
$lang['ai_h_count_fix'] = 'Comptez le stock une fois par mois et enregistrez-le comme ajustement d\'inventaire.';
$lang['ai_used_menu'] = 'Performance du menu';
$lang['ai_used_price'] = 'Contrôle des prix';
$lang['ai_used_purchase'] = 'Achats vs consommation';
$lang['ai_used_pnl'] = 'Profits et pertes';
$lang['ai_used_forecast'] = 'Prévision des ventes';
$lang['ai_used_waste'] = 'Analyse des pertes';
$lang['ai_used_shrink'] = 'Contrôle des pertes de stock';
$lang['ai_items_without_recipe'] = 'Meilleures ventes sans coût de recette';
$lang['ai_qty_sold'] = 'Qté vendue';
$lang['ai_item'] = 'Article';
$lang['ai_stock'] = 'Stock';
$lang['ai_save'] = 'Enregistrer';

// AI Companies (main admin)
$lang['ai_companies_hint'] = 'Activez l\'analyse IA par société et fixez une limite de dépense mensuelle. Toutes les sociétés partagent une seule clé API.';
$lang['ai_company'] = 'Société';
$lang['ai_outlets'] = 'Points de vente';
$lang['ai_enabled'] = 'Activé';
$lang['ai_monthly_limit'] = 'Limite mensuelle (USD)';
$lang['ai_report_model'] = 'Modèle du rapport quotidien';
$lang['ai_chat_model'] = 'Modèle du chat';
$lang['ai_spent_this_month'] = 'Dépensé ce mois (USD)';
$lang['ai_calls_this_month'] = 'Appels ce mois';
$lang['ai_saved'] = 'Paramètres IA enregistrés.';
$lang['ai_invalid_request'] = 'La requête n\'a pas pu être vérifiée. Rechargez la page et réessayez.';
$lang['ai_invalid_value'] = 'Vérifiez les valeurs : la limite mensuelle doit être entre 0 et 1000 USD, et les questions par jour entre 0 et 200.';
$lang['ai_key_status'] = 'Clé API';
$lang['ai_key_set'] = 'Définie sur le serveur';
$lang['ai_key_not_set'] = 'Non définie : ajoutez ANTHROPIC_API_KEY dans application/.env sur le serveur';
$lang['ai_mock_on'] = 'Mode test (AI_MOCK=1) : aucun appel réel n\'est effectué.';
$lang['ai_test_connection'] = 'Tester la connexion';
$lang['ai_test_ok'] = 'Connexion OK : %s a répondu en %s secondes.';
$lang['ai_test_failed'] = 'Échec de la connexion : %s';

// AI call results
$lang['ai_key_missing'] = 'Le service IA n\'est pas configuré sur le serveur.';
$lang['ai_budget_reached'] = 'La limite IA de ce mois pour votre société est atteinte.';
$lang['ai_disabled_company'] = 'L\'analyse IA est désactivée pour cette société.';
$lang['ai_error_generic'] = 'Le service IA est injoignable. Les chiffres restent affichés ; réessayez plus tard.';

// report page
$lang['ai_period_28'] = '28 derniers jours';
$lang['ai_period_7'] = '7 derniers jours';
$lang['ai_report_of'] = 'Rapport du %s';
$lang['ai_figures_to'] = 'chiffres jusqu\'au %s';
$lang['ai_made_at'] = 'créé le %s';
$lang['ai_text_ai'] = 'Rédigé par l\'IA';
$lang['ai_text_ai_partly'] = 'Certaines parties utilisent le texte standard car un chiffre n\'a pas pu être vérifié.';
$lang['ai_text_standard'] = 'Texte standard';
$lang['ai_text_pending'] = 'Le texte IA est en cours de rédaction.';
$lang['ai_text_retry'] = 'Le service IA n\'a pas répondu ; nouvel essai dans l\'heure.';
$lang['ai_text_unavailable'] = 'Le service IA n\'a pas pu être utilisé pour ce rapport.';
$lang['ai_regen_left'] = '%s sur %s restants aujourd\'hui';
$lang['ai_regen_working'] = 'Création du rapport…';
$lang['ai_regen_busy'] = 'Ce rapport est déjà en cours de création. Attendez une minute puis rechargez la page.';
$lang['ai_regen_limit'] = 'Un rapport peut être recréé %s fois par jour et par point de vente. Réessayez demain.';
$lang['ai_regen_done'] = 'Le rapport a été recréé.';
$lang['ai_regen_done_standard'] = 'Le rapport a été recréé avec le texte standard (le texte IA n\'était pas disponible).';
$lang['ai_no_report'] = 'Ce point de vente n\'a pas encore de rapport. Les rapports sont créés automatiquement chaque jour après %s:00.';
$lang['ai_no_report_regen'] = 'Cliquez sur Régénérer le rapport pour créer le premier maintenant.';
$lang['ai_old_report'] = 'Ceci est le rapport du %s. Le rapport du jour est créé après %s:00.';
$lang['ai_note_ai'] = 'Note de l\'IA';
$lang['ai_note_standard'] = 'Résumé';
$lang['ai_gate_blocked'] = 'Cette section a besoin de plus de données :';
$lang['ai_gate_limited'] = 'Ces chiffres sont incomplets :';
$lang['ai_gate_see_health'] = 'Voir Qualité des données ci-dessous.';
$lang['ai_show_all'] = 'Tout afficher (%s)';
$lang['ai_show_less'] = 'Afficher moins';
$lang['ai_vs_prev'] = 'vs la période précédente';
$lang['ai_against'] = 'contre';
$lang['ai_new'] = 'Nouveau';
$lang['ai_days'] = '%s jours';
$lang['ai_days_unit'] = 'jours';
$lang['ai_k_yesterday'] = 'Hier';
$lang['ai_k_orders'] = '%s commandes';
$lang['ai_k_vs_last_week'] = 'vs le même jour la semaine dernière';
$lang['ai_k_target'] = 'Objectif %s%%';

// top 5
$lang['ai_top5'] = '5 améliorations prioritaires';
$lang['ai_top5_hint'] = 'Classées selon ce que chacune rapporte par mois';
$lang['ai_top5_none'] = 'Rien d\'urgent n\'a été trouvé sur les 28 derniers jours.';
$lang['ai_per_month'] = 'environ %s par mois';
$lang['ai_conf_high'] = 'Confiance élevée';
$lang['ai_conf_medium'] = 'Confiance moyenne';
$lang['ai_conf_low'] = 'Confiance faible';
$lang['ai_it_underpriced'] = 'Prix trop bas';
$lang['ai_it_loss_item'] = 'Vendu à perte';
$lang['ai_it_overbuy'] = 'Achat excessif';
$lang['ai_it_waste'] = 'Pertes élevées';
$lang['ai_it_discounts'] = 'Remises';
$lang['ai_it_sales_drop'] = 'Baisse des ventes';
$lang['ai_it_item_drop'] = 'Article moins vendu';
$lang['ai_it_expense_jump'] = 'Hausse des dépenses';
$lang['ai_it_dog_item'] = 'Article faible';
$lang['ai_it_unsold_item'] = 'Ne se vend pas';

// menu performance
$lang['ai_c_star'] = 'Populaire, rapporte bien';
$lang['ai_c_plowhorse'] = 'Populaire, rapporte peu';
$lang['ai_c_puzzle'] = 'Rapporte bien, se vend peu';
$lang['ai_c_dog'] = 'Se vend peu, rapporte peu';
$lang['ai_c_none'] = 'Sans coût de recette';
$lang['ai_menu_avg_margin'] = 'Marge moyenne par assiette : %s. Chaque article est classé selon sa marge par rapport à cette moyenne et selon sa fréquence de vente.';
$lang['ai_col_sold'] = 'Vendus';
$lang['ai_free'] = '+%s offerts';
$lang['ai_col_share'] = 'Part des ventes';
$lang['ai_col_avg_price'] = 'Prix moyen';
$lang['ai_col_plate_cost'] = 'Coût assiette';
$lang['ai_col_margin'] = 'Marge par assiette';
$lang['ai_col_total_margin'] = 'Marge totale';
$lang['ai_col_vs_prev'] = 'vs avant';
$lang['ai_col_group'] = 'Groupe';
$lang['ai_unsold'] = 'Non vendus sur la période (%s)';

// price check
$lang['ai_price_hint'] = 'Coût matière cible %s%%, prix suggérés arrondis au-dessus à %s';
$lang['ai_b_loss'] = 'Vendu à perte';
$lang['ai_b_critical'] = 'Au-dessus de 50%';
$lang['ai_b_high'] = 'Bien au-dessus de l\'objectif';
$lang['ai_b_watch'] = 'Légèrement au-dessus de l\'objectif';
$lang['ai_b_good'] = 'Dans l\'objectif';
$lang['ai_col_price'] = 'Prix';
$lang['ai_col_net_price'] = 'Prix hors TVA';
$lang['ai_col_food_cost'] = 'Coût matière';
$lang['ai_col_status'] = 'Statut';
$lang['ai_col_sold_28'] = 'Vendus (28 jours)';
$lang['ai_col_suggested'] = 'Prix suggéré';
$lang['ai_col_gain'] = 'Gain (28 jours)';
$lang['ai_price_foot'] = 'Coût matière = coût assiette / prix hors TVA. Le trait foncé sur chaque barre est l\'objectif. Gain = ce que le prix suggéré aurait ajouté sur la même quantité.';

// purchase vs usage
$lang['ai_t_bought'] = 'Acheté';
$lang['ai_t_used'] = 'Consommé';
$lang['ai_t_wasted'] = 'Perdu';
$lang['ai_t_shrink'] = 'Écarts d\'inventaire';
$lang['ai_t_excess'] = 'Stock acheté en trop';
$lang['ai_t_high_waste'] = '%s avec pertes élevées';
$lang['ai_t_overbought'] = '%s trop achetés';
$lang['ai_col_ingredient'] = 'Ingrédient';
$lang['ai_col_bought'] = 'Acheté';
$lang['ai_col_transfers'] = 'Transferts entrée / sortie';
$lang['ai_col_used'] = 'Consommé';
$lang['ai_col_wasted'] = 'Perdu';
$lang['ai_col_stock_now'] = 'Stock actuel';
$lang['ai_col_days_cover'] = 'Durée du stock';
$lang['ai_col_flags'] = 'Alertes';
$lang['ai_f_overbuy'] = 'Trop acheté';
$lang['ai_f_waste'] = 'Pertes élevées';
$lang['ai_f_negative'] = 'Sous zéro';
$lang['ai_purchase_foot'] = 'Quantités en unité de consommation. Trop acheté = plus de %s jours de stock (le trait foncé sur la barre) ou achat bien supérieur à la consommation et aux pertes.';

// profit & loss
$lang['ai_col_this'] = 'Cette période';
$lang['ai_col_prev'] = 'Période précédente';
$lang['ai_col_change'] = 'Évolution';
$lang['ai_l_orders'] = 'Commandes';
$lang['ai_l_gross'] = 'Ventes TTC';
$lang['ai_l_vat'] = 'TVA';
$lang['ai_l_tips'] = 'Pourboires (pas un revenu)';
$lang['ai_l_net_sales'] = 'Ventes nettes';
$lang['ai_l_mess'] = 'Revenus cantine';
$lang['ai_l_refunds'] = 'Remboursements';
$lang['ai_l_food'] = 'Coût matière';
$lang['ai_l_waste'] = 'Pertes';
$lang['ai_l_salaries'] = 'Salaires versés';
$lang['ai_l_expenses'] = 'Dépenses';
$lang['ai_l_net_profit'] = 'Bénéfice net';
$lang['ai_l_included'] = 'Déjà déduit des ventes nettes';
$lang['ai_l_discounts'] = 'Remises';
$lang['ai_l_complimentary'] = 'Articles offerts';
$lang['ai_l_staff'] = 'Repas du personnel';
$lang['ai_l_avg_ticket'] = 'Ticket moyen';
$lang['ai_pnl_foot'] = 'Le coût matière est le coût recette de ce qui a été vendu, repas de cantine compris. Les salaires sont les paiements de paie de ce point de vente ; les dépenses payées via la paie ne sont pas comptées deux fois.';

// sales prediction
$lang['ai_forecast_title'] = 'Prévision des ventes : 7 prochains jours';
$lang['ai_fc_line'] = 'Environ %s articles pour %s sont attendus. Jour le plus chargé : %s.';
$lang['ai_fc_accuracy'] = 'Sur les 7 derniers jours, la prévision s\'est écartée de %s.';
$lang['ai_fc_items'] = 'Quantité attendue par article';
$lang['ai_col_total'] = 'Total';
$lang['ai_fc_purchase'] = 'Achats suggérés pour les 7 prochains jours';
$lang['ai_fc_no_purchase'] = 'Le stock disponible couvre les 7 prochains jours.';
$lang['ai_col_need'] = 'Besoin';
$lang['ai_col_safety'] = 'Stock de sécurité';
$lang['ai_col_in_stock'] = 'En stock';
$lang['ai_col_buy'] = 'Acheter';
$lang['ai_col_est_cost'] = 'Coût estimé';
$lang['ai_fc_foot'] = 'Besoin = ventes attendues x quantités des recettes, plus les pertes habituelles. Stock de sécurité = jours de consommation en plus (Paramètres IA). Arrondi à l\'unité d\'achat supérieure.';

// AI Settings
$lang['ai_settings_hint'] = 'Appliqué à partir du prochain rapport : demain après %s:00, ou cliquez sur Régénérer le rapport dans la page AI Insight.';
$lang['ai_s_target'] = 'Coût matière cible';
$lang['ai_s_target_help'] = 'Les articles au-dessus reçoivent un prix suggéré. La plupart des restaurants visent 25-35%.';
$lang['ai_s_rounding'] = 'Arrondir les prix suggérés à';
$lang['ai_s_rounding_help'] = 'Par exemple, 0.50 transforme 21.14 en 21.50.';
$lang['ai_s_overbuy'] = 'Achat excessif quand l\'achat dépasse de plus de';
$lang['ai_s_overbuy_help'] = 'la consommation et les pertes de la période.';
$lang['ai_s_cover'] = 'Achat excessif quand le stock dure plus de';
$lang['ai_s_cover_help'] = 'à la consommation journalière actuelle.';
$lang['ai_s_safety'] = 'Stock de sécurité dans les achats suggérés';
$lang['ai_s_safety_help'] = 'Jours de consommation en plus à garder en stock.';
$lang['ai_settings_saved'] = 'Paramètres IA enregistrés. Ils s\'appliquent à partir du prochain rapport.';
$lang['ai_settings_invalid'] = 'Vérifiez les valeurs : objectif 15-60%, achat excessif 5-200%, stock 3-60 jours, stock de sécurité 0-7 jours.';

// Ask AI
$lang['ai_chat_daily'] = 'Questions / utilisateur / jour';
$lang['ai_chat_hint'] = 'Les réponses viennent uniquement des données de ce point de vente.';
$lang['ai_chat_new'] = 'Nouvelle discussion';
$lang['ai_chat_close'] = 'Fermer';
$lang['ai_chat_send'] = 'Envoyer';
$lang['ai_chat_placeholder'] = 'Posez une question sur les ventes, les prix, le stock, les achats ou les coûts';
$lang['ai_chat_thinking'] = 'Analyse de vos données…';
$lang['ai_chat_left'] = '%s questions sur %s restantes aujourd\'hui';
$lang['ai_chat_left_chat'] = '%s sur %s restantes dans cette discussion';
$lang['ai_chat_s1'] = 'Pourquoi mon coût matière est-il élevé ?';
$lang['ai_chat_s2'] = 'Que dois-je acheter pour les 3 prochains jours ?';
$lang['ai_chat_s3'] = 'Quels articles retirer du menu ?';
$lang['ai_chat_s4'] = 'Comment étaient les ventes la semaine dernière par rapport à la précédente ?';
$lang['ai_chat_off'] = 'Ask AI est désactivé pour votre entreprise.';
$lang['ai_chat_empty'] = 'Veuillez saisir une question.';
$lang['ai_chat_too_long'] = 'Veuillez limiter la question à %s caractères.';
$lang['ai_chat_session_full'] = 'Cette discussion a atteint %s questions. Cliquez sur Nouvelle discussion pour continuer.';
$lang['ai_chat_busy'] = 'Votre question précédente est encore en cours de réponse.';
$lang['ai_chat_day_limit'] = 'Vous avez utilisé vos %s questions du jour. Réessayez demain.';
$lang['ai_chat_refused'] = 'Désolé, je ne peux pas répondre à cela. Posez une question sur les ventes, le menu, le stock, les achats ou les coûts de ce point de vente.';
$lang['ai_chat_failed'] = 'La réponse n\'a pas pu être terminée. Veuillez réessayer.';
$lang['ai_chat_reload'] = 'Veuillez recharger la page et réessayer.';
