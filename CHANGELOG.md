﻿Changelog
=========

#### v1.0.2 - 13 Febbraio 2019
* Rinominato metodo `ruleColumns` in `mapColumns`.
* Quando si salvano i dati parsati dal file CSV sul Database di Wordpress, ora vengono memorizzati tutti gli ID appena
  inseriti come opzione di Wordpress, in modo che negli inserimenti successivi sarà la classe a chiedere la conferma per
  continuare con l'operazione o svuotare prima tutti i dati precedentemente importati.

#### v1.0.1 - 12 Febbraio 2019
* Aggiunta una nuova sotto-classe con tutte le azioni e filtri utilizzabili dalla classe.
* Aggiunti 2 nuovi filtri: `icfw_filter_save_title` e `icfw_filter_save_content`.
* Aggiunto nuovo metodo `saveDataToPost' con cui è possibile salvare nel database i dati appena importati dal file CSV.
* Risolto un problema con l'inizio riga dei dati caricati dal file CSV.
* Risolti vari problemi di parsing dei dati.

#### v1.0 - 11 Febbraio 2019
* Prima release.