<?php
/*
 * Plugin spip|mastodon
 * (c) 2009-2013
 *
 * envoyer et lire des messages de Mastodon
 * distribue sous licence GNU/LGPL
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


/**
 * Table principale
 * un champ microblog sur les articles
 *
 * @param array $tables
 * @return array
 */
function mastodon_declarer_tables_objets_sql($tables) {
	$tables['spip_articles']['field']['microblog'] = "VARCHAR(140) DEFAULT '' NOT NULL";
	
	return $tables;
}

/**
 * maj dede la table article
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function mastodon_upgrade($nom_meta_base_version,$version_cible){

	$maj = array();
	$maj['create'] = array(
		array('sql_alter',"TABLE spip_articles ADD microblog VARCHAR(140) DEFAULT '' NOT NULL"),
	);

	$maj['0.1.1'] = array(
		array('sql_alter',"TABLE spip_articles ADD microblog VARCHAR(140) DEFAULT '' NOT NULL"),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Desinstallation/suppression
 *
 * @param string $nom_meta_base_version
 */
function mastodon_vider_tables($nom_meta_base_version) {
	sql_alter("table spip_articles DROP microblog");
	effacer_meta($nom_meta_base_version);
}


?>