# 1.5.2
* Events wurden aktualisiert, damit sie mit dem Flow Builder der Shopware-Plattformversion >= 6.4.6 kompatibel sind.
* Import der Core libs behoben
* fix the access to config variables in twig

# 1.5.1
* Paginierung für Serien hinzugefügt und Auswahl für Massenlöschung hinzugefügt.
* Bump lodash from 4.17.20 to 4.17.21 
* Missing compiled JS storefront files are now served

# 1.5.0
* Aktualisiert, um die Api-Version im Router zu entfernen

# 1.4.2
* Aktualisiert, um mit Version 6.4 kompatibel zu sein.
* Mail senden behoben

# 1.4.1
* Korrigierte js-Kompressionsdatei für Version 1.4.0

# 1.4.0
* Deaktivieren der Komprimierung der Zip-Datei in der Plugin-Konfiguration hinzugefügt, um die Komprimierung der Zip-Datei deaktivieren zu können
* Mail-Vorlage für den Mail-Download mit deaktivierter Komprimierung in der Zip-Datei hinzugefügt
* Fehler bei der Identifizierung von ESD-Bestellungen behoben
* Upload von Mediendateien behoben
  
# 1.3.3
* Fehler der Bestellpositionen behoben, wodruch keine Email versendet worden ist.
* Die lokale Sprache in der Download Tabellenspalte `Kaufdatum` auf der Seite `Account > Downloads` wurde angepasst.

# 1.3.2
* Die JSON Response eines Downloads wurde verbessert, da es zu Fehlern bei der `.zip` Generierung kam.

# 1.3.1
* Das Problem, dass die Bestellung nicht abgeschlossen werden kann, wenn der Warenkorb einen physischen Artikel und einen digitalen Artikel enthält, wurde behoben

# 1.3.0
* Es ist nun möglich ESD-Videos anzulegen
* Sie können von ESD-Video zu ESD-Normal wechseln
* Das anzeigen von ESD Produkten und Videos innerhalb der Account Page wurde verbessert

# 1.2.14
* Die Download Methode wurde verbessert und hat einen Fehler bei der Generierung der .zip Dateien behoben.

# 1.2.13
* Die Methode `updateTo120` des Update Lifecycle wurde entfernt, da sie durch die Migration ersetzt worden ist. 

# 1.2.12
* Es wurde ein Fehler behoben, wonach eine falsche Class geladen worden ist, was zu deinen Fehler in den Business Events führe

# 1.2.11
* Die Hauptnavigation wird nun auch angezeigt, wenn man sich auf der Account Download Seite befindet
* Verbessertes versenden der ESD-E-Mail: Es werden nun die Business Events genutzt
* Es ist nun möglich manuell erneut die ESD Emails zu versenden innerhalb einer Order über die Buttons 
  `E-Mail-Download erneut senden` und `E-Mail-Seriennummer erneut senden`.
* Es wurde ein Fehler unter Windows beheben, da gepackte `.zip` Dateien teilweise beschädigt waren

# 1.2.10
* Die url in der E-Mail-Vorlage wurde repariert, Wechsel von url() zu rawUrl(), um die Vertriebskanal-Domain zu erhalten
* Verbesserte Funktion zum Versenden von E-Mails, Sie können die esd-E-Mail an den Käufer senden Kaufen Ändern Sie den Zahlungsstatus auf bezahlt mit dem Schalter `E-Mail an Kunden senden` ist aktiviert

# 1.2.9
* Das Neuladen der verbleibenden Download-Daten in Shopware >= v6.3.2.0 wurde behoben. Der verbleibende Download kann aktualisiert werden, nachdem Sie auf "Jetzt herunterladen" geklickt haben

# 1.2.8
* Ein Hotfix zum Senden der Download-E-Mail in der Shopware version 6.3.3.0 wurde erstellt

# 1.2.7
* fixed a bug with the general terms and condition checkbox during the checkout

# 1.2.6
* Aktualisiert, um das Neuladen der Seite zu deaktivieren und die verbleibenden Download-Daten für Shopware Version 6.3.2.0> = 6.3.3.0 abzurufen

# 1.2.5
* zeige Verzicht auf Widerruf immer bei digitalen Downloads. 
Text Snippet Key ist `sasEsd.checkout.confirmESD`

# 1.2.4
* Fehler bei Umlaute behoben, beim downloaden der .zip Datei

# 1.2.3
* added various filetypes to be able to upload also documents
* it's also possible to upload your very own .zip file

# 1.2.2
* fixed issue with terms of use

# 1.2.1
* added instant download badge on product detail
* added ESD withdrawal notice within checkout

# 1.2.0 
* Email mit Download Link nach erfolgreichen kauf
* Email mit Serial Number nach erfolgreichen kauf
* Download Limit möglich
* Überarbeitete API Dokumentation 

# 1.1.0

* Multiple Datei Uploads hinzugefügt
* Bestellnummer in Download Tabelle im Account hinzugefügt
* Fehler in der Administration behoben, dass ein ESD Artikel nicht lädt,
wenn man die gesamte Seite direkt neu lädt.

# 1.0.0

* Erster Release im Store
