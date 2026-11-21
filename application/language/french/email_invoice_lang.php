<?php
/*
 | Email Invoice module (docs/email_invoice.md). Same key order as english/email_invoice_lang.php.
 | The bill email itself is always English (Invoice_mailer::t), so its keys are not repeated here.
 */

// module, menu and role screen
$lang['email_invoice'] = 'Facture par e-mail';
$lang['ei_send_email'] = 'Envoyer / renvoyer la facture par e-mail';
$lang['ei_email_settings'] = 'Paramètres e-mail';
$lang['ei_email_log'] = 'Journal des e-mails';

// settings page
$lang['ei_on'] = 'ACTIVÉ';
$lang['ei_off'] = 'DÉSACTIVÉ';
$lang['ei_settings_hint'] = 'Connectez le compte e-mail qui envoie vos factures. Enregistrez, puis envoyez un e-mail de test pour vérifier.';
$lang['ei_enable'] = 'Envoyer les factures par e-mail';
$lang['ei_enable_help'] = 'Une fois activé, l\'écran de paiement du POS affiche une case « Envoyer la facture par e-mail ».';
$lang['ei_email_account'] = 'Compte e-mail';
$lang['ei_provider'] = 'Fournisseur e-mail';
$lang['ei_username'] = 'Adresse e-mail (identifiant)';
$lang['ei_username_help'] = 'Le compte e-mail qui envoie les factures.';
$lang['ei_password'] = 'Mot de passe / mot de passe d\'application';
$lang['ei_password_help'] = 'Pour Gmail, utilisez un mot de passe d\'application, pas votre mot de passe habituel.';
$lang['ei_password_saved'] = 'Enregistré - laisser vide pour le garder';
$lang['ei_password_keep'] = 'Un mot de passe est enregistré. Laissez vide pour le garder ou saisissez-en un nouveau.';
$lang['ei_help_gmail'] = 'Gmail : activez la validation en deux étapes de votre compte Google, puis Compte Google > Sécurité > Mots de passe des applications, créez-en un et collez les 16 lettres ici. Votre mot de passe Gmail habituel ne fonctionnera pas.';
$lang['ei_help_outlook'] = 'Outlook / Office 365 : utilisez l\'adresse complète et son mot de passe. Pour les comptes professionnels, l\'administrateur doit autoriser « Authenticated SMTP » pour cette boîte.';
$lang['ei_help_zoho'] = 'Zoho : utilisez l\'adresse complète et son mot de passe, ou un mot de passe d\'application si la double authentification est active.';
$lang['ei_help_hostinger'] = 'Hostinger : utilisez un compte e-mail créé dans hPanel > Emails et son mot de passe.';
$lang['ei_help_custom'] = 'Demandez à votre hébergeur le serveur SMTP, le port et la sécurité (cPanel : Email Accounts > Connect Devices), puis remplissez-les dans Avancé.';
$lang['ei_advanced'] = 'Avancé (serveur, port, nom de l\'expéditeur)';
$lang['ei_host'] = 'Serveur SMTP';
$lang['ei_port'] = 'Port';
$lang['ei_encryption'] = 'Sécurité';
$lang['ei_none'] = 'Aucune';
$lang['ei_from_email'] = 'Adresse d\'envoi';
$lang['ei_from_email_help'] = 'Vide = l\'e-mail de connexion. La plupart des fournisseurs n\'acceptent que celui-ci.';
$lang['ei_from_name'] = 'Nom de l\'expéditeur';
$lang['ei_from_name_help'] = 'Vide = le nom du point de vente.';
$lang['ei_reply_to'] = 'Les réponses vont à';
$lang['ei_reply_to_help'] = 'Vide = l\'e-mail du point de vente.';
$lang['ei_bcc_email'] = 'M\'envoyer une copie (Cci)';
$lang['ei_bcc_help'] = 'Facultatif : chaque facture est aussi envoyée à cette adresse.';
$lang['ei_pos_section'] = 'POS';
$lang['ei_pos_auto_tick'] = 'Cocher « Envoyer la facture par e-mail » automatiquement si le client a un e-mail';
$lang['ei_pos_auto_tick_help'] = 'Le caissier peut toujours décocher ou saisir une autre adresse.';
$lang['ei_test_section'] = 'Test';
$lang['ei_test_to'] = 'Envoyer le test à (vide = e-mail de connexion)';
$lang['ei_send_test'] = 'Envoyer un e-mail de test';
$lang['ei_test_help'] = 'Utilise les valeurs de cette page, même avant l\'enregistrement. Le test contient votre dernière facture comme exemple.';
$lang['ei_save'] = 'Enregistrer';
$lang['ei_saved'] = 'Paramètres e-mail enregistrés.';
$lang['ei_save_error'] = 'Impossible d\'enregistrer les paramètres. Réessayez.';
$lang['ei_last_test_ok'] = 'Dernier test : envoyé';
$lang['ei_last_test_failed'] = 'Dernier test : échec';
$lang['ei_pending_count'] = '%d e-mail(s) en attente d\'envoi';
$lang['ei_sending'] = 'Envoi...';
$lang['ei_network_error'] = 'Pas de réponse du serveur. Vérifiez la connexion internet et réessayez.';
$lang['ei_test_ok'] = 'E-mail de test envoyé à %s. Vérifiez la boîte de réception (et les spams).';
$lang['ei_test_subject'] = 'E-mail de test';
$lang['ei_test_body'] = 'This is a test from your POS. If you can read this, bill emails will work. Below is a sample of how your customers will see their bill.';
$lang['ei_err_host'] = 'Saisissez un serveur SMTP valide.';
$lang['ei_err_port'] = 'Saisissez un port entre 1 et 65535.';
$lang['ei_err_encryption'] = 'Choisissez une option de sécurité.';
$lang['ei_err_username'] = 'Saisissez l\'adresse e-mail (identifiant).';
$lang['ei_err_password'] = 'Saisissez le mot de passe.';
$lang['ei_err_email_field'] = '%s n\'est pas une adresse e-mail valide.';
$lang['ei_hint_gmail'] = 'Gmail a refusé la connexion. Utilisez un mot de passe d\'application (Compte Google > Sécurité > Mots de passe des applications).';
$lang['ei_hint_auth'] = 'Le serveur a refusé la connexion. Vérifiez l\'adresse et le mot de passe. Certains fournisseurs exigent un mot de passe d\'application ou l\'activation du SMTP.';
$lang['ei_hint_connect'] = 'Impossible de joindre le serveur e-mail. Vérifiez le serveur et le port. Beaucoup d\'hébergeurs bloquent les ports 25 et 587 ; essayez SSL avec le port 465.';
$lang['ei_hint_tls'] = 'La connexion sécurisée a échoué. Essayez l\'autre option de sécurité (TLS avec 587 ou SSL avec 465).';

// email log
$lang['ei_log_hint'] = 'Les 200 derniers e-mails de facture. Un envoi échoué est retenté 3 fois, puis marqué Échec.';
$lang['ei_log_empty'] = 'Aucun e-mail de facture pour l\'instant.';
$lang['ei_send_now'] = 'Envoyer maintenant';
$lang['ei_outlet'] = 'Point de vente';
$lang['ei_to'] = 'À';
$lang['ei_status'] = 'Statut';
$lang['ei_attempts'] = 'Essais';
$lang['ei_details'] = 'Détails';
$lang['ei_resend'] = 'renvoyé';
$lang['ei_retry'] = 'Réessayer';
$lang['ei_retry_queued'] = 'L\'e-mail sera renvoyé.';
$lang['ei_retry_error'] = 'Cet e-mail ne peut pas être renvoyé.';
$lang['ei_next_try'] = 'Prochain essai : %s';
$lang['ei_status_pending'] = 'En attente';
$lang['ei_status_sending'] = 'Envoi';
$lang['ei_status_sent'] = 'Envoyé';
$lang['ei_status_failed'] = 'Échec';

// POS and sales list
$lang['ei_email_bill'] = 'Facture par e-mail';
$lang['ei_email_placeholder'] = 'client@email.com';
$lang['ei_email_invoice'] = 'Envoyer la facture par e-mail';
$lang['ei_send'] = 'Envoyer';
$lang['ei_customer_email'] = 'E-mail du client';
$lang['ei_last_email'] = 'Dernier e-mail';
$lang['ei_pos_invalid'] = 'L\'adresse e-mail de la facture n\'est pas valide. Corrigez-la ou décochez « Facture par e-mail ».';

// messages
$lang['ei_reload'] = 'Votre session a changé. Rechargez la page.';
$lang['ei_not_ready'] = 'La facture par e-mail n\'est pas encore installée : exécutez Update/email_invoice_migration.sql sur la base de données.';
$lang['ei_not_enabled'] = 'L\'envoi des factures par e-mail est désactivé. Activez-le dans Paramètres > Paramètres e-mail.';
$lang['ei_invalid_email'] = 'Saisissez une adresse e-mail valide.';
$lang['ei_sale_not_found'] = 'Facture introuvable.';
$lang['ei_already_queued'] = 'Cette facture est déjà en cours d\'envoi à cette adresse.';
$lang['ei_too_many'] = 'Cette facture a été envoyée trop de fois.';
$lang['ei_queue_error'] = 'Impossible de mettre l\'e-mail en file d\'attente.';
$lang['ei_queued'] = 'La facture sera envoyée dans un instant.';
$lang['ei_sent_to'] = 'Facture envoyée à %s.';
$lang['ei_will_retry'] = 'Pas encore envoyé, un nouvel essai sera fait automatiquement.';
$lang['ei_password_unreadable'] = 'Le mot de passe e-mail enregistré est illisible. Saisissez-le à nouveau dans Paramètres e-mail.';
$lang['ei_from_invalid'] = 'L\'adresse de l\'expéditeur n\'est pas valide.';
