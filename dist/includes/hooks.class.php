<?php

/**
 * Classe principale: ICFW - Importer CSV for Wordpress
 * Sotto-Classe: IcfwHooks
 * 
 * Gestione personalizzata di tutte le azioni e filtri richiamati dalla classe principale.
 * 
 * @dalla v1.0.1
 * 
 */
 
// Disabilita le chiamate dirette a questa classe.
if (!defined('ABSPATH')) die;

// Richiamabile solo tramite la classe principale.
if (!defined('ICFW_CLASS')) die;

class IcfwHooks {
	/**
	 * Costruttore.
	 *
	 * @dalla v1.0
	 *
	 * @accesso   pubblico
	 */
	public function __construct() {
		add_filter('icfw_filter_cell', array($this, 'filter_cell'), 10, 2);
		add_filter('icfw_filter_save_title', array($this, 'filter_save_title'), 10, 1);
		add_filter('icfw_filter_save_content', array($this, 'filter_save_content'), 10, 1);
	}
	
	/**
	 * Filtro predefinito utilizzato per ripulire il titolo del post.
	 *
	 * @dalla v1.0.1
	 *
	 * @parametro mixed $title Il titolo da ripulire.
	 */
	public function filter_save_title($title) {
		if (is_array($title))
			$title = implode(' ', $title);
		
		return wp_strip_all_tags($title);
	}
	
	/**
	 * Filtro predefinito utilizzato per ripulire il contenuto del post.
	 *
	 * @dalla v1.0.1
	 *
	 * @parametro mixed $content Il contenuto da ripulire.
	 */
	public function filter_save_content($content) {
		if (is_array($content))
			$content = implode(' ', $content);
		
		return apply_filters('the_content', $content);
	}
	
	/**
	 * Filtro predefinito per la singola cella della colonna.
	 *
	 * @dalla v1.0.1
	 *
	 * @parametro array  $cell_values Ritorna un array con i valori di ogni singola cella.
	 * @parametro string $column_name Il nome della chiave della colonna mappata inizialmente con il metodo `mapColumns`.
	 */
	public function filter_cell($cell_values, $column_name) {
		return $cell_values;
	}
}

new IcfwHooks;