<?php

/**
 * ICFW - Importer CSV for Wordpress
 * 
 * È un utility per Wordpress per l'importazione dati da un file in formato CSV.
 * 
 * @versione                        1.0
 * @data ultimo aggiornamento       11 Febbraio 2019
 * @data prima versione             11 Febbraio 2019
 * 
 * @autore                          Giorgio Suadoni
 * @sito                            https://github.com/GiorgioKM/ImporterCSVforWordpress
 * @wiki                            https://github.com/GiorgioKM/ImporterCSVforWordpress/wiki
 * 
 */

// Disabilita le chiamate dirette a questa classe.
if (!defined('ABSPATH')) die;

// Controllo che il motore di Wordpress sia stato già inizializzato.
if (!isset($wp_version)) die;

class ImporterCSVforWP {
	/**
	 * Uno o più percorsi della cartella di lavoro della classe.
	 *
	 * @dalla v1.0
	 *
	 * @accesso privato
	 * @var     array
	 */
	private $class_paths = [];
	
	/**
	 * Gestione degli errori.
	 *
	 * @dalla v1.0
	 *
	 * @accesso privato
	 * @var     array
	 */
	private $errors = [];
	
	/**
	 * Risorsa del file CSV caricato.
	 *
	 * @dalla v1.0
	 *
	 * @accesso privato
	 */
	private $handle;
	
	/**
	 * Array di dati caricati dal file CSV.
	 *
	 * @dalla v1.0
	 *
	 * @accesso privato
	 * @var     array
	 */
	private $csv_data = [];
	
	/**
	 * Determina o meno se eliminare gli spazi prima e dopo da una singola cella di una colonna del file CSV.
	 *
	 * @dalla v1.0
	 *
	 * @accesso privato
	 * @var     bool
	 */
	private $can_trim_cell = false;
	
	/**
	 * Costruttore.
	 *
	 * @dalla v1.0
	 *
	 * @accesso   pubblico
	 * @parametro string   $filename_csv Obbligatorio. Il nome del file utilizzato per l'importazione dei dati CSV.
	 */
	public function __construct($filename_csv = '') {
		$this->errors = new WP_Error;
		
		if (empty(trim($filename_csv)))
			$this->errors->add('csv_undefined', 'Il parametro `$filename_csv` non è stato definito!');
		
		$this->class_paths['WWW'] = __DIR__;
		$this->class_paths['IMPORT_CSV'] = __DIR__ .'/csv';
		
		$path_absolute_class = explode('/', $this->class_paths['WWW']);
		$path_absolute_wp = explode('/', ABSPATH);
		$res = array_diff($path_absolute_class, $path_absolute_wp);
		
		$rebuild_uri_path = home_url(implode('/', $res));
		
		$this->class_paths['URI'] = $rebuild_uri_path;
		
		if (!file_exists($this->class_paths['IMPORT_CSV'] .'/'. $filename_csv))
			$this->errors->add('csv_not_found', "Il file `{$filename_csv}` non è stato trovato nella cartella `{$this->class_paths['IMPORT_CSV']}`!");
		
		$this->class_paths['IMPORT_CSV'] .= '/'. $filename_csv;
		
		if ($this->_is_errors())
			wp_die($this->errors->get_error_message());
	}
	
	/**
	 * Definisce i dati da importare per ogni colonna del file CSV.
	 *
	 * @dalla v1.0
	 *
	 * @accesso   pubblico
	 * @parametro array    $args      Obbligatorio. Lista di parametri per la definizione di ogni singola colonna.
	 * @parametro integer  $start_row Facoltativo.  Da quale riga deve partire l'importo dei dati (l'indice di partenza è 0 che è considerato come la 1° riga del file).
	 */
	public function ruleColumns($args = array(), $start_row = 0) {
		if (!is_array($args))
			$this->errors->add('args_not_defined', 'Il parametro `$args` deve essere definito come array!');
		
		if ($this->_is_errors())
			wp_die($this->errors->get_error_message());
		
		$this->handle = fopen($this->class_paths['IMPORT_CSV'], 'r');
		
		while (($data_row_from_csv = fgetcsv($this->handle, 1000, ';')) !== FALSE) {
			$data_row_from_csv = array_filter(array_merge(array(''), $data_row_from_csv));
			
			$this->csv_data[] = $this->_get_single_data_row($args, $data_row_from_csv);
		}

		fclose($this->handle);
		
		$this->csv_data = array_slice($this->csv_data, $start_row);
		
		if ($this->_is_errors())
			wp_die($this->errors->get_error_message());
	}
	
	/**
	 * Ottiene un array con tutti i dati caricati dal CSV.
	 *
	 * @dalla v1.0
	 *
	 * @accesso pubblico
	 * @ritorno array    Ritorna un array con i dati caricati dal CSV.
	 */
	public function getData() {
		return $this->csv_data;
	}
	
	/**
	 * Elimina gli spazi prima e dopo da una cella dati di una colonna del file CSV.
	 *
	 * @dalla v1.0
	 *
	 * @accesso pubblico
	 */
	public function trimCell() {
		$this->can_trim_cell = true;
	}
	
	/**
	 * Stampa a video.
	 *
	 * @dalla v1.0
	 *
	 * @accesso pubblico
	 */
	public function debugData() {
		$this->_print_inline_style();
		
		print '<pre id="icfw-debug">';
		print_r($this->getData());
		print '</pre>';
	}
	
	/**************************************************************************************************
	 * METODI PRIVATI
	 **************************************************************************************************/
	
	/**
	 * Parsa i dati CSV della singola riga.
	 *
	 * @dalla v1.0
	 *
	 * @accesso   privato
	 * @parametro array   $args              Obbligatorio. Lista di parametri per la definizione di ogni singola colonna.
	 * @parametro array   $data_row_from_csv Obbligatorio. La singola riga contenente i dati caricati dal file CSV.
	 */
	private function _get_single_data_row($args, $data_row_from_csv) {
		$single_data = [];
		
		foreach ($args as $col_name => $data_col) {
			if (!is_array($data_col)) {
				$cell = ($this->can_trim_cell ? trim($data_row_from_csv[$data_col]) : $data_row_from_csv[$data_col]);
				
				$single_data[$col_name] = $cell;
			} else {
				if (!isset($data_col['col']) || (isset($data_col['col']) && empty($data_col['col'])))
					continue;
				elseif (!is_array($data_col['col']))
					$get_cols = array($data_col['col']);
				elseif (is_array($data_col['col']))
					$get_cols = $data_col['col'];
				
				$single_data[$col_name] = [];
				
				foreach ($get_cols as $col_id) {
					if (isset($data_col['cell_separator']) && $data_col['cell_separator'])
						$single_data[$col_name] = array_merge($single_data[$col_name], array_map('trim', explode($data_col['cell_separator'], $data_row_from_csv[$col_id])));
					else
						$single_data[$col_name][] = $this->_get_single_data_row(array($col_name => $col_id), $data_row_from_csv)[$col_name];
				}
			}
			
			/**
			 * Filtra la singola cella della colonna.
			 *
			 * @dalla v1.0
			 *
			 * @parametro array  $single_data[$col_name] Array di valori della singola cella.
			 * @parametro string $col_name               Nome della colonna.
			 */
			$single_data[$col_name] = apply_filters('icfw_filter_cell', $single_data[$col_name], $col_name);
		}
		
		return $single_data;
	}
	
	/**
	 * Stampa lo stile css della classe in linea.
	 *
	 * @dalla v1.0
	 *
	 * @accesso privato
	 */
	private function _print_inline_style() {
		if (file_exists($this->class_paths['WWW'] .'/css/icfw-style.css')) {
			?>
			<style type="text/css">
				<?= file_get_contents($this->class_paths['WWW'] .'/css/icfw-style.css') ?>
			</style>
			<?php
		}
	}
	
	/**
	 * Controlla se c'è almeno un errore.
	 *
	 * @dalla v1.0
	 *
	 * @accesso privato
	 * @ritorno bool    Ritorna vero o falso se avviene un errore.
	 */
	private function _is_errors() {
		return count($this->errors->get_error_codes());
	}
	
}
	
?>