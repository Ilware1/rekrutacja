#rekrutacja
==========

A Symfony project created on August 18, 2016, 09:41 am.

W katalogach znajdują się dwa bundle z SF3, aby je uruchomić należy zarejestrować je w AppKernel.php.
Pierwszy z nich GeneratorBundle służy do wygenerowania pliku zależnie wymagań.Aby uruchomić generowanie pliku należy wywołać polecenie
```bash
$ php bin/console file:generate
```
creator zapyta o wszyskie wymagane parametry a następnie utworzy plik w katalogu vars.
Kolejny bundle służy do sortowania plików, uruchamia się go poleceniem
```bash
$ php bin/console file:sort
```
również znajduje się tam kreator i przeprowadzi przez cały proces , output znajduje się w vars/output.
---------
Sortowanie posiada dwie opcje :
-database
-standard
przy wywoływaniu opcji database należy wczęniej skonfigurować połącznie do bazy mysql w pliku parameters.yml


## Requirements

* PHP >= 7.0
