# Graffette Schema Manager

Plugin WordPress leggero per generare JSON-LD globale per `LocalBusiness`/sottotipi e `Person`.

## Funzionalità

- Settings page nativa WordPress, senza ACF/SCF.
- **Integrazione Yoast** (default): se Yoast è attivo, arricchisce la sua `Organization` e aggiunge `LocalBusiness`/`Person` al suo `@graph`, senza stampare un secondo script.
- `@id` dell'Organization unico in tutte le lingue WPML (`/#organization` invece di `/it/#organization`, `/de/#organization`...).
- `LocalBusiness` solo in homepage (anche quelle tradotte) o su tutte le pagine, collegato con `parentOrganization`.
- Area servita multipla (`Country` per i codici ISO a 2 lettere, `Place` per il resto), lingue parlate, valute accettate, `ContactPoint`, `vatID`.
- Descrizione traducibile con WPML (Traduzione stringhe, dominio `graffette-schema-manager`).
- URL sito vuoto = home della lingua principale: resta corretto dopo un cambio di dominio.
- Orari raggruppati: i giorni con la stessa fascia finiscono in un solo `OpeningHoursSpecification`.
- Output standalone in `wp_head` o `wp_footer` quando Yoast non c'è.
- Modalità solo manuale con shortcode `[graffette_schema]` e funzione PHP `graffette_schema()`.
- Schema `Person` opzionale collegato all'azienda con `worksFor`.

## Estendere da un tema

Filtro `gsm_schema_graph` (`$graph`, `$context` di Yoast o `null` in standalone) per aggiungere nodi.
Helper per collegarsi alle entità del plugin: `gsm_get_organization_id()`, `gsm_get_local_business_id()`.

## Cosa non genera

Il plugin non genera volutamente:

- `Review`
- `AggregateRating`
- `WebSite`
- `WebPage`
- `BreadcrumbList`
- `Article`
- `FAQPage`
- `Product`

Questi elementi restano in carico a Yoast, Rank Math, WooCommerce o al tema.

## Installazione

1. Caricare la cartella `graffette-schema-manager` in `wp-content/plugins/`.
2. Attivare il plugin da WordPress.
3. Andare in `Impostazioni > Schema Graffette`.
4. Compilare i dati e attivare l’output.

## Changelog

- `0.2.1`: licenza GPL-2.0-or-later, nessuna modifica al codice.
- `0.2.0`: integrazione Yoast, `@id` unico multilingua, LocalBusiness solo in home, area servita multipla, lingue, valute, ContactPoint, vatID, descrizione traducibile WPML, orari raggruppati, filtro `gsm_schema_graph`.
- `0.1.0`: versione iniziale interna.

## Licenza

GPL-2.0-or-later. Vedi il file `LICENSE`.
