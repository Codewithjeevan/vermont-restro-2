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
$lang['ai_reports_coming'] = 'La performance du menu, le contrôle des prix, les achats vs consommation, les profits et pertes, la prévision des ventes et les 5 améliorations prioritaires apparaîtront ici dès que le rapport quotidien sera exécuté. Corrigez d\'abord les points rouges ci-dessous : chaque section en dépend.';

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
$lang['ai_invalid_value'] = 'Vérifiez les valeurs : la limite mensuelle doit être entre 0 et 1000 USD.';
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
