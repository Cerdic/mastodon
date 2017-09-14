<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
if (!defined("_ECRIRE_INC_VERSION")) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(
	// A
	'article' => 'article',

	// B
	'bouton_envoyer' => 'Envoyer',
	'bouton_envoyer_maintenant' => 'Envoyer maintenant',
	'bouton_ne_pas_bloguer' => 'Ne rien envoyer',
	'bouton_preferer_compte' => 'Utiliser ce compte par défaut',

	// C
	'choisir' => 'choisir',
	'compte_tests' => 'Compte de tests',
	'compte_defaut' => 'Compte par d&#233;faut pour la fonction @fonction@',
	'creer_compte_services' => 'Vous pouvez cr&#233;er un compte pour votre site SPIP sur l\'un des services suivants',

	// E
	'elements_signaler' => 'Envoyer un message avec le compte par défaut lors des événements ci-dessous.',
	'erreur_verifier_configuration' => 'Il y a une erreur, veuillez v&eacute;rifiez la configuration.',
	'explication_mastodon_api_oauth' => 'Créez une application Mastodon <a href="http://dev.mastodon.com/apps/new">dans l\'espace développeurs (http://dev.mastodon.com/apps/new)</a>.
Entrez ci-dessous les clés d\'identification et enregistrez (<a href="https://contrib.spip.net/4394">Plus d\'aide</a>).',
	'erreur_connexion_compte' => 'Impossible de se connecter avec le compte @account@.',
	'erreur_envoi_desactive' => 'L\'envoi de Tweet est desactivé par la constante <tt>_TEST_MICROBLOG_SERVICE</tt>',
	'erreur_config_pour_widget' => 'Configurer l\'Application Mastodon et ajouter un compte Mastodon pour utiliser les Widgets',
	'erreur_config_pour_tweeter' => 'Configurer le plugin pour envoyer un message.',

	// I
	'invite' => 'Invite',
	'invite_statut' => 'Invite de statut',
	'invite_statut_explications' => 'Le plugin peut afficher dans l\'espace priv&#233; une invite de statut. Voulez-vous afficher une invite de statut pour les r&#233;dacteurs autoris&#233;s (par d&#233;faut&nbsp;: les administrateurs du site) ?',
	'invite_afficher' => 'Afficher l\'invite de statut',

	// L
	'label_associer_compte_mastodon' => 'Ajouter un compte Mastodon',
	'label_aucun_compte_mastodon' => 'Aucun compte Mastodon enregistré.',
	'label_dissocier_compte_mastodon' => 'Supprimer ce compte',
	'label_status' => 'Quoi de neuf ?',
	'label_mastodon_consumer_key' => 'Cl&eacute; cliente (<em>API key</em>)',
	'label_mastodon_consumer_secret' => 'Cl&eacute; secr&#232;te (<em>API secret</em>)',
	'label_microblog'=> 'Modifier le message',
	'label_username' => 'Nom d\'utilisateur',
	'legend_api_mastodon' => 'Application Mastodon',
	'legend_comptes_mastodon' => 'Comptes Mastodon',
	'lien_documentation' => 'Cf. documentation',
	'longueur_maxi_status' => 'Le message doit comporter au maximum 140 caract&egrave;res',

	// M
	'message_envoye'=> 'Tweet&nbsp;:',

	// P
	'presentation_laconica' => 'le site public du logiciel laconi.ca',

	// N
	'necessite_job_queue' => 'n&#233;cessite job_queue',
	'notifications' => 'Notifications',
	'notifications_publiques' => 'Notifications publiques',

	// P
	'poster_forums' => 'Forums post&#233;s',
	'publier_forums' => 'Forums publi&#233;s',
	'proposer_articles' => 'Articles propos&#233;s',
	'propose' => 'propos&#233;',
	'publier_articles' => 'Articles publi&#233;s',
	'publie' => 'publi&#233;',
	'publier_articles_futurs_immediatement'=>'Annoncer les articles quelle que soit leur date de publication',
	'publier_articles_futurs_visibles'=>'Ne pas annoncer les articles avant la date de publication fix&eacute;e',
	'publier_articles_shorturl'=>'Utiliser des urls courtes (n&#233;cessite un .htaccess)',
	'publier_articles_attente' => 'Espacer les publications dans le temps (minutes)',

	// S
	'service' => 'Service',

	// T
	'titre_microblog' => 'Mastodon',
	'titre_configurer_microblog' => 'Configurer Mastodon',
	'titre_configurer_mastodon_app' => 'Application &amp; Comptes',


	'explication_commun_widgets' => "Configuration Nécessaire à l'utilisation du plugin Twitdget. Partie commune entre le widget profil et le widget recherche",
	'explication_recherche_widget' => "Configuration pour le widget de recherche",
	'explication_profil_widget' => "Configuration Nécessaire à l'utilisation du widget profil ",

	'label_search' => "Recherche. Terme recherché, l'usage de tweeter implique souvent de précéder le terme par un # ",
	'label_interval' => "Delai entre le défilement de chaque tweet (ms)",
	'label_subject' => "Sujet de la fenêtre tweete",
	'label_title' => "Titre de la fenêtre",
	'label_footer' => "Texte du footer",
	'label_width' => "Largeur de la fenêtre",
	'label_height' => "Hauteur de la fenêtre",
	'label_shell_background' => "Couleur de fond de la fenêtre tweeter",
	'label_shell_color' => "Couleur du texte",
	'label_tweets_background' => "Couleur fond tweet",
	'label_tweets_color' => "Couleur de texte des tweet",
	'label_tweets_link' => "Couleur des liens",
	'label_rpp' => "Nombre de résultats par page",
	'label_user' => "Utilisateur mastodon (ne pas précéder du @)",

	'legend_commun_widgets' => "Configuration Commune",
	'legend_recherche_widget' => "Widget Recherche",
	'legend_profil_widget' => "Widget Profil",


	// T
	'titre_configurer_widget' => "Mastodon Widgets",
	'titre_twidget' => "Twidget",

);

?>
