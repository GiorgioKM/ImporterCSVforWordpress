<?php

/**
 * ICFW - Importer CSV for Wordpress
 * 
 * È un utility per Wordpress per l'importazione dati da un file in formato CSV.
 * 
 * @versione                        1.0.1
 * @data ultimo aggiornamento       12 Febbraio 2019
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

define('ICFW_CLASS', true);

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
	 * Carattere speciale che delimita i campi di dati.
	 *
	 * @dalla v1.0
	 *
	 * @accesso privato
	 * @var     string
	 */
	private $column_delimiter = ';';
	
	/**
	 * Regole di comportamento, definite dall'utente, su come deve essere parsata una colonna da un file CSV.
	 *
	 * @dalla v1.0.1
	 *
	 * @accesso privato
	 * @var     array
	 */
	private $rule_columns_csv = [];
	
	/**
	 * Costruttore.
	 *
	 * @dalla v1.0
	 *
	 * @accesso   pubblico
	 * @parametro string   $filename_csv      Obbligatorio. Il nome del file utilizzato per l'importazione dei dati CSV.
	 * @parametro string   $column_delimiter  Facoltativo.  Carattere speciale che delimita i campi di dati.
	 */
	public function __construct($filename_csv = '', $column_delimiter = '') {
		$this->errors = new WP_Error;
		
		if (empty(trim($filename_csv)))
			$this->errors->add('csv_undefined', 'Il parametro `$filename_csv` non è stato definito!');
		
		$this->class_paths['WWW'] = __DIR__;
		$this->class_paths['IMPORT_CSV'] = __DIR__ .'/csv';
		$this->class_paths['INCLUDES'] = __DIR__ .'/includes';
		
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
		
		if (!empty($column_delimiter))
			$this->column_delimiter = $column_delimiter;
		
		require_once($this->class_paths['INCLUDES'] .'/hooks.class.php');
	}
	
	/**
	 * Definisce i dati da importare per ogni colonna del file CSV.
	 *
	 * @dalla v1.0
	 *
	 * @accesso   pubblico
	 * @parametro array    $args      Obbligatorio. Lista di parametri per la definizione di ogni singola colonna.
	 * @parametro integer  $start_row Facoltativo.  Da quale riga deve partire l'importo dei dati.
	 */
	public function ruleColumns($args = array(), $start_row = 1) {
		if (!is_array($args))
			$this->errors->add('args_not_defined', 'Il parametro `$args` deve essere definito come array!');
		
		if ($this->_is_errors())
			wp_die($this->errors->get_error_message());
		
		$this->handle = fopen($this->class_paths['IMPORT_CSV'], 'r');
		
		$count_row = 1;
		
		while (($data_row_from_csv = fgetcsv($this->handle, 1000, $this->column_delimiter)) !== FALSE) {
			if ($count_row < $start_row) {
				$count_row++;
				
				continue;
			}
			
			$data_row_from_csv = array_filter(array_merge(array(''), $data_row_from_csv));
			
			$this->csv_data[] = $this->_get_single_data_row($args, $data_row_from_csv);
		}

		fclose($this->handle);
		
		$this->rule_columns_csv = $args;
	}
	
	/**
	 * Ottiene un array con tutti i dati caricati dal CSV.
	 *
	 * @dalla v1.0.1 - Aggiunto parametro $id.
	 * @dalla v1.0
	 *
	 * @accesso   pubblico
	 * @parametro integer  $id Facoltativo. Se specificato, verrà restituita soltando la riga dati corrispondente.
	 * @ritorno   mixed    Ritorna il dato in base alla richiesta fatta.
	 */
	public function getData($id = false) {
		if ($id === false)
			return $this->csv_data;
		elseif (isset($this->csv_data[$id]))
			return $this->csv_data[$id];
		
		return false;
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
	 * Stampa a video i dati CSV processati dalla classe.
	 *
	 * @dalla v1.0
	 *
	 * @accesso pubblico
	 */
	public function debugData() {
		$this->printDebug($this->getData(), 'Dati parsati dal CSV');
	}
	
	/**
	 * Salva i dati parsati dal file CSV in un post di Wordpress.
	 *
	 * @dalla v1.0.1
	 *
	 * @accesso   pubblico
	 * @parametro array    $args  {
	 *     Facoltativo. Una serie di elementi per costruire il post da inserire all'interno di Wordpress.
	 *
	 *     @tipo array $wp_insert_post   Array di elementi che compongono un post da inserire. Vedere il metodo nativo di Wordpress `wp_insert_post` (facendo riferimento alla variabile $postarr).
	 *     @type array $custom_post_meta Array di elementi che permettono di salvare alcuni dati all'interno di custom post meta.
	 * }
	 * @parametro bool     $save_to_db Facoltativo. Se il valore è false, stamperà a video un debug sull'operazione da effettuare. I dati non verranno salvati!
	 */
	public function saveDataToPost($args = array(), $save_to_db = false) {
		$get_data = $this->getData();
		
		if (!count($get_data))
			$this->errors->add('data_not_defined', 'Dati vuoti o non trovati dal CSV. Non è possibile salvare i dati in un post Wordpress!');
		
		if ($this->_is_errors())
			wp_die($this->errors->get_error_message());
		
		$default_insert_post = array(
			'post_title' => key($this->rule_columns_csv),
			'post_content' => '',
			'post_status' => 'draft',
			'post_type' => 'post',
		);
		
		$defaults = array(
			'wp_insert_post' => array(),
			'custom_post_meta' => array(),
		);
		
		$args = wp_parse_args($args, $defaults);
		
		$args['wp_insert_post'] = wp_parse_args($args['wp_insert_post'], $default_insert_post);
		
		if (!$save_to_db) {
			$this->printDebug($args, 'ARRAY POST');
			
			$debug_operation = [];
		}
		
		foreach ($get_data as $data_id => $row) {
			$post = $args['wp_insert_post'];
			
			$post_title = $this->_get_column_data($post['post_title'], $data_id);
			$post_content = $this->_get_column_data($post['post_content'], $data_id);
			
			/**
			 * Filtra il titolo da salvare.
			 *
			 * @dalla v1.0.1
			 *
			 * @parametro mixed $post_title Il titolo a cui applicare il filtro.
			 */
			$post['post_title'] = apply_filters('icfw_filter_save_title', $post_title);
			
			/**
			 * Filtra il contenuto da salvare.
			 *
			 * @dalla v1.0.1
			 *
			 * @parametro mixed $post_content Il contenuto a cui applicare il filtro.
			 */
			$post['post_content'] = apply_filters('icfw_filter_save_content', $post_content);
			
			if (!$save_to_db)
				$debug_operation[$data_id]['post'] = $post;
			else
				$post_id = wp_insert_post($post);
			
			foreach ($args['custom_post_meta'] as $meta_key_name => $meta_data) {
				$meta_key_db = $meta_key_name;
				
				if (!is_array($meta_data))
					$meta_value_db = $meta_data;
				else {
					$array_meta = [];
					
					foreach ($meta_data as $array_key => $sub_meta_data) {
						$array_meta[(is_numeric($array_key) ? $sub_meta_data : $array_key)] = $this->_get_column_data($sub_meta_data, $data_id);
					}
					
					$meta_value_db = $array_meta;
				}
				
				if (!$save_to_db) {
					$debug_operation[$data_id]['custom_post_meta'][] = array(
						'meta_key' => $meta_key_db,
						'meta_value' => $meta_value_db,
					);
				} else
					update_post_meta($post_id, $meta_key_db, $meta_value_db);
			}
		}
		
		if (!$save_to_db)
			$this->printDebug($debug_operation, 'SALVATAGGIO DATI SU POST');
		else
			$this->printDebug('Dati CSV importati e salvati correttamente su Wordpress', 'OPERAZIONE COMPLETATA CON SUCCESSO!');
	}
	
	/**
	 * Stampa una stringa a video.
	 *
	 * @dalla v1.0.1
	 *
	 * @accesso   pubblico
	 * @parametro mixed    $mixed Obbligatorio. Il dato da stampare a video.
	 */
	public function printDebug($mixed, $title = 'Data Debug') {
		$this->_print_inline_style();
		
		print '<pre id="icfw-debug">';
		print '<h2><strong>DEBUG:</strong> '. wp_strip_all_tags($title) .'</h2>';
		print_r($mixed);
		print '</pre>';
	}
	
	/**************************************************************************************************
	 * METODI PRIVATI
	 **************************************************************************************************/
	
	/**
	 * Ottiene la colonna dei dati CSV già parsati dalla classe.
	 *
	 * @dalla v1.0.1
	 *
	 * @accesso   privato
	 * @parametro string  $column_name Obbligatorio. Il nome della colonna salvata da ottenere all'interno dei dati parsati.
	 * @parametro bool    $row_id      Facoltativo. Filtra direttamente i dati per l'ID della riga.
	 */
	private function _get_column_data($column_name, $row_id = false) {
		$get_data = $this->getData($row_id);
		
		if (!count($get_data))
			$this->errors->add('data_not_defined', 'Dati vuoti o non ancora processati dalla classe!');
		
		if ($this->_is_errors())
			wp_die($this->errors->get_error_message());
		
		if ($row_id === false)
			return array_column($get_data, $column_name);
		else
			return $get_data[$column_name];
	}
	
	/**
	 * Parsa i dati CSV della singola riga.
	 *
	 * @dalla v1.0.1 - Aggiunto parametro $self_call.
	 * @dalla v1.0
	 *
	 * @accesso   privato
	 * @parametro array   $args              Obbligatorio. Lista di parametri per la definizione di ogni singola colonna.
	 * @parametro array   $data_row_from_csv Obbligatorio. La singola riga contenente i dati caricati dal file CSV.
	 * @parametro bool    $self_call         Facoltativo. Determina o meno se c'è una chiamata allo stesso metodo.
	 */
	private function _get_single_data_row($args, $data_row_from_csv, $self_call = false) {
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
						$single_data[$col_name][] = $this->_get_single_data_row(array($col_name => $col_id), $data_row_from_csv, true)[$col_name];
				}
			}
			
			if ($self_call)
				continue;
			
			/**
			 * Filtra la singola cella della colonna.
			 *
			 * @dalla v1.0
			 *
			 * @parametro array  $cell_values Array di valori della singola cella.
			 * @parametro string $col_name    Nome della colonna.
			 */
			$cell_values = (!is_array($single_data[$col_name]) ? array($single_data[$col_name]) : $single_data[$col_name]);
			$cell_values = apply_filters('icfw_filter_cell', $cell_values, $col_name);
			
			if (!is_array($single_data[$col_name])) {
				if (is_array($cell_values))
					$single_data[$col_name] = $cell_values[0];
				else
					$single_data[$col_name] = $cell_values;
			} else
				$single_data[$col_name] = $cell_values;
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