ImporterCSVforWordpress
================

![Build Status](https://img.shields.io/badge/build-v1.0.2-green.svg?style=flat)

È un utility per Wordpress per l'importazione dati da un file in formato CSV e successivo salvataggio come post di Wordpress.



Crediti
-------

|Tipo|Descrizione|
|:---|---:|
|@autore|Giorgio Suadoni|
|@versione|1.0.2|
|@data ultimo aggiornamento|13 Febbraio 2019|
|@data prima versione|11 Febbraio 2019|



Changelog
---------

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



Link utili
----------

* [Wiki di ImporterCSVforWordpress](https://github.com/GiorgioKM/ImporterCSVforWordpress/wiki)