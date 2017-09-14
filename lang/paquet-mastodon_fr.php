<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// Fichier source, a modifier dans svn://zone.spip.org/spip-zone/_plugins_/twidget/lang/
if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// T
	'mastodon_description' => 'Envoyer simplement des micro-messages via mastodon et son API oAuth.

Un widget Mastodon facile a installer
_ Ajoutez une inclusion dans vos squelettes pour afficher un widget mastodon :
_ <code>#INCLURE{fond=inclure/twidget_profile}</code>
 ou <code>#INCLURE{fond=inclure/twidget_search}</code>

 Le plugin fait office de proxy afin qu\'aucune requête ne soit faite par vos visiteurs vers mastodon et éviter tout traçage possible de leur activité (préservation de leur vie personnelle).',
	'mastodon_slogan' => 'Mastodon facilement : afficher et envoyer des messages'
);

?>
