=== Foxentry Integration ===
Contributors: webklient
Tags: foxentry, validation, email, phone, address, api, data-validation
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Moderní a bezpečný WordPress plugin pro integraci s Foxentry API pro validaci emailů, telefonů a adres.

== Description ==

Foxentry Integration je pokročilý WordPress plugin, který umožňuje snadnou integraci s Foxentry API pro validaci různých typů dat včetně emailových adres, telefonních čísel a fyzických adres.

**Hlavní funkce:**

* **Real-time validace** - Okamžitá validace dat během psaní
* **Podpora více typů dat** - Email, telefon, adresa
* **Cache systém** - Optimalizace výkonu a snížení API volání
* **Moderní UI** - Responzivní design s loading indikátory
* **Bezpečnost** - Implementace WordPress bezpečnostních standardů
* **Shortcodes** - Snadné vkládání validátorů do obsahu
* **Admin rozhraní** - Intuitivní nastavení v WordPress administraci

**Shortcodes:**

* `[foxentry_validator type="email"]` - Validátor emailových adres
* `[foxentry_validator type="phone"]` - Validátor telefonních čísel
* `[foxentry_validator type="address"]` - Validátor fyzických adres
* `[foxentry_promo]` - Propagační banner Foxentry

**Požadavky:**

* WordPress 5.0+
* PHP 7.4+
* Foxentry API klíč (získejte na https://app.foxentry.com/registration?aff=or8eaq)

**O autorovi:**

Plugin byl vytvořen studiem **Webklient** (www.webklient.cz) - **Mediatoring.com s.r.o.**
Specializujeme se na moderní webové řešení a WordPress development.

== Installation ==

1. Nahrajte složku `foxentry-integration` do adresáře `/wp-content/plugins/`
2. Aktivujte plugin v sekci 'Pluginy' WordPress administrace
3. Přejděte do Nastavení > Foxentry pro konfiguraci
4. Zadejte váš Foxentry API klíč
5. Použijte shortcodes ve vašem obsahu

**Manuální instalace:**

1. Stáhněte plugin
2. Rozbalte do `/wp-content/plugins/foxentry-integration/`
3. Aktivujte plugin
4. Nakonfigurujte podle výše uvedených kroků

== Frequently Asked Questions ==

= Kde získám Foxentry API klíč? =

API klíč můžete získat registrací na https://app.foxentry.com/registration?aff=or8eaq

= Je plugin zdarma? =

Plugin je zdarma, ale pro použití potřebujete Foxentry API klíč, který může být zpoplatněn podle tarifu.

= Podporuje plugin cache? =

Ano, plugin obsahuje vestavěný cache systém pro optimalizaci výkonu a snížení počtu API volání.

= Jak použiji validátor ve svém obsahu? =

Jednoduše vložte shortcode `[foxentry_validator type="email"]` do vašeho příspěvku nebo stránky.

= Je plugin bezpečný? =

Ano, plugin implementuje všechny WordPress bezpečnostní standardy včetně nonce validace a sanitizace dat.

== Screenshots ==

1. Admin stránka s nastavením API klíče
2. Email validátor v akci
3. Telefon validátor s real-time validací
4. Propagační banner
5. Responzivní design na mobilních zařízeních

== Changelog ==

= 1.0.0 =
* Počáteční vydání
* Podpora validace emailů, telefonů a adres
* Real-time validace s debounce
* Cache systém
* Admin rozhraní pro nastavení
* Shortcodes pro snadné použití
* Responzivní design
* Bezpečnostní implementace

== Upgrade Notice ==

= 1.0.0 =
Počáteční vydání pluginu. Žádný upgrade není potřeba.

== Developer Notes ==

**API Endpoints používané:**
* Email validace: https://api.foxentry.com/email/validate
* Telefon validace: https://api.foxentry.com/phone/validate
* Adresa validace: https://api.foxentry.com/location/validate

**Hooks a filtry:**
Plugin je připraven na rozšíření pomocí WordPress hooks a filtrů.

**Podpora:**
Pro technickou podporu kontaktujte studio Webklient na www.webklient.cz

== Privacy Policy ==

Plugin odesílá data zadaná uživateli k validaci na servery Foxentry API. 
Žádná data nejsou ukládána v databázi WordPress kromě cache výsledků validace.
Více informací o zpracování dat najdete v privacy policy služby Foxentry.
