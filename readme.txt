=== Foxentry Integration ===
Contributors: webklient
Tags: foxentry, validation, email, phone, address, integration, data-validation, real-time
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

🚀 **REST API integrace Foxentry do WordPress** - Plugin integruje Foxentry REST API 2.0 pro pokročilou validaci dat s automatickými opravami a návrhy.

== Description ==

**Foxentry Integration** je moderní WordPress plugin, který integruje Foxentry REST API 2.0 pro validaci dat. Stačí vložit API klíč a plugin poskytuje pokročilou validaci s opravami a návrhy!

## ✨ **Hlavní funkce:**

* 🚀 **REST API 2.0** - Přímá integrace s Foxentry API pro maximální spolehlivost
* ⚡ **Real-time validace** - Okamžitá validace dat během psaní s debounce
* 📧 **Podpora více typů** - Email, telefon, adresa, jméno, firma
* 🔧 **Automatické opravy** - API automaticky opravuje chyby v datech
* 💡 **Inteligentní návrhy** - Nabízí alternativy pro neplatné údaje
* 🎨 **Moderní UI** - Responzivní design s loading indikátory a animacemi
* 🔒 **Bezpečnost** - WordPress bezpečnostní standardy a nonce validace
* 📱 **Responzivní** - Funguje na všech zařízeních včetně dark mode
* 🌍 **Překladatelný** - Plná podpora pro překlady
* 🛠️ **Admin kontrola** - Test API klíče přímo v administraci

## 🎯 **Shortcodes:**

```
[foxentry_validator type="email"]     - Validátor emailových adres
[foxentry_validator type="phone"]     - Validátor telefonních čísel  
[foxentry_validator type="address"]   - Validátor fyzických adres
```

## 📋 **Požadavky:**

* WordPress 5.0+
* PHP 7.4+
* Foxentry API klíč (získejte na [Foxentry](https://app.foxentry.com/registration?aff=or8eaq))


== Installation ==

### 🚀 **Rychlá instalace:**

1. **Stáhněte plugin** z WordPress.org nebo GitHub
2. **Nahrajte** do `/wp-content/plugins/foxentry-integration/`
3. **Aktivujte** plugin v WordPress administraci
4. **Přejděte** do Nastavení > Foxentry
5. **Získejte API klíč:**
   - Zaregistrujte se na [Foxentry](https://app.foxentry.com/registration?aff=or8eaq)
   - V dashboardu klikněte na tlačítko pro vytvoření nového projektu
   - Pojmenujte projekt a zvolte možnost **"Aplikace"**
   - V projektu klikněte na **"Nastavení"**
   - Klikněte na **"API klíče"**
   - Klikněte na **"Vytvořit API klíč"**
   - Zkopírujte vygenerovaný API klíč
6. **Vložte API klíč** do pole v pluginu
7. **Otestujte** pomocí tlačítka "Otestovat API klíč"

### 📝 **Manuální instalace:**

```bash
# Stáhněte a rozbalte do plugins složky
cd /wp-content/plugins/
git clone https://github.com/webklient/foxentry-integration.git
```

== Frequently Asked Questions ==

= Jak získám Foxentry API klíč? =

1. Zaregistrujte se na [Foxentry](https://app.foxentry.com/registration?aff=or8eaq)
2. V dashboardu klikněte na tlačítko pro vytvoření nového projektu
3. Pojmenujte projekt a zvolte možnost **"Aplikace"**
4. V projektu klikněte na **"Nastavení"**
5. Klikněte na **"API klíče"**
6. Klikněte na **"Vytvořit API klíč"**
7. Zkopírujte vygenerovaný API klíč

= Je plugin zdarma? =

Ano, plugin je 100% zdarma! Potřebujete pouze Foxentry účet pro získání API klíče. Nové projekty dostávají 100 kreditů zdarma na 14 dní.

= Jak plugin funguje? =

Plugin používá Foxentry REST API 2.0 pro validaci dat. Všechna validace probíhá na serveru, což zajišťuje maximální spolehlivost a bezpečnost.

= Jaké typy dat podporuje? =

Plugin podporuje validaci emailů, telefonních čísel, adres, jmen a firem. API automaticky opravuje chyby a nabízí inteligentní návrhy.

= Jak ověřím, že API funguje? =

V adminu pluginu klikněte na "Otestovat API klíč" - plugin automaticky otestuje připojení k Foxentry API.

= Podporuje plugin překlady? =

Ano, plugin je plně připraven na překlady. Obsahuje .pot soubor a všechny texty jsou lokalizované.

== Screenshots ==

1. **Admin rozhraní** - Nastavení API klíče s návodem
2. **Email validátor** - Real-time validace s automatickými opravami
3. **Telefon validátor** - Formátování a validace telefonních čísel
4. **Adresa validátor** - Validace a formátování adres s návrhy
5. **Propagační banner** - Responzivní Foxentry banner
6. **Mobilní verze** - Optimalizováno pro všechna zařízení

== Changelog ==

= 1.0.0 =
* 🎉 **Počáteční vydání**
* 🚀 Integrace Foxentry REST API 2.0
* 🔄 Real-time validace s debounce (800ms)
* 📧 Podpora validace emailů, telefonů, adres, jmen a firem
* 🔧 Automatické opravy chyb v datech
* 💡 Inteligentní návrhy alternativ
* 🎨 Moderní UI s loading indikátory a animacemi
* 🔒 WordPress bezpečnostní standardy
* 📱 Responzivní design s dark mode podporou
* 🌍 Plná podpora pro překlady
* 🛠️ Admin test API klíče
* 📋 Detailní návod k získání API klíče
* ⚡ Server-side validace pro maximální spolehlivost

== Upgrade Notice ==

= 1.0.0 =
🎉 **Počáteční vydání pluginu** - Žádný upgrade není potřeba.

== Developer Notes ==

### 🔧 **Technické detaily:**

**Foxentry REST API 2.0:**
* Endpoint: `https://api.foxentry.com/`
* Autentizace: Bearer token
* Verze API: 2.0
* Podporované typy: email, phone, address, name, company

**WordPress integrace:**
* Server-side validace pomocí `wp_remote_post()`
* Cache systém pro optimalizaci výkonu
* Debounce 800ms pro lepší UX

**Bezpečnost:**
* Nonce validace pro všechny AJAX požadavky
* Sanitizace všech vstupů
* Ověření uživatelských oprávnění
* API klíč je uložen bezpečně v databázi

### 🛠️ **Pro vývojáře:**

```php
// Hooks pro rozšíření
do_action('foxentry_before_validation', $type, $value);
do_action('foxentry_after_validation', $result);

// Filtry
$api_key = apply_filters('foxentry_api_key', $api_key);
$validation_result = apply_filters('foxentry_validation_result', $result);
```

**API Endpoints:**
* Email: `https://api.foxentry.com/email/validate`
* Telefon: `https://api.foxentry.com/phone/validate`
* Adresa: `https://api.foxentry.com/location/validate`
* Jméno: `https://api.foxentry.com/name/validate`
* Firma: `https://api.foxentry.com/company/validate`

### 📞 **Podpora:**

Pro technickou podporu kontaktujte:  
**Webklient** - [www.webklient.cz](https://www.webklient.cz)  
**Email:** info@webklient.cz

== Privacy Policy ==

### 🔒 **Ochrana dat:**

* Plugin **neukládá** žádná osobní data uživatelů
* Validace probíhá server-side pomocí Foxentry REST API
* API klíč je uložen bezpečně v WordPress databázi
* Cache výsledků validace je dočasný (nastavitelný)
* Všechna data jsou zpracovávána podle privacy policy Foxentry
* API komunikace je šifrovaná (HTTPS)

**Více informací:** [Foxentry Privacy Policy](https://foxentry.com/privacy) | [API Dokumentace](https://foxentry.dev/reference/intro)

== O autorovi ==

Plugin vytvořen studiem **Webklient** - [www.webklient.cz](https://www.webklient.cz)  
**Mediatoring.com s.r.o.** - Specializace na moderní webová řešení a WordPress development
