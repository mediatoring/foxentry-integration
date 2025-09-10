# 🚀 Foxentry Integration WordPress Plugin

[![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-green.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-red.svg)](https://github.com/webklient/foxentry-integration)

> **REST API integrace Foxentry do WordPress** - Plugin integruje Foxentry REST API 2.0 pro pokročilou validaci dat s automatickými opravami a návrhy.

## ✨ Hlavní funkce

- 🚀 **REST API 2.0** - Přímá integrace s Foxentry API pro maximální spolehlivost
- ⚡ **Real-time validace** - Okamžitá validace dat během psaní s debounce
- 📧 **Podpora více typů** - Email, telefon, adresa, jméno, firma
- 🔧 **Automatické opravy** - API automaticky opravuje chyby v datech
- 💡 **Inteligentní návrhy** - Nabízí alternativy pro neplatné údaje
- 🎨 **Moderní UI** - Responzivní design s loading indikátory a animacemi
- 🔒 **Bezpečnost** - WordPress bezpečnostní standardy a nonce validace
- 📱 **Responzivní** - Funguje na všech zařízeních včetně dark mode
- 🌍 **Překladatelný** - Plná podpora pro překlady
- 🛠️ **Admin kontrola** - Test API klíče přímo v administraci

## 🎯 Shortcodes

```html
[foxentry_validator type="email"]     <!-- Validátor emailových adres -->
[foxentry_validator type="phone"]     <!-- Validátor telefonních čísel -->
[foxentry_validator type="address"]   <!-- Validátor fyzických adres -->
[foxentry_promo]                      <!-- Propagační banner Foxentry -->
```

## 📋 Požadavky

- WordPress 5.0+
- PHP 7.4+
- Foxentry API klíč ([získejte zde](https://app.foxentry.com/registration?aff=or8eaq))

## 🚀 Rychlá instalace

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

## 📝 Manuální instalace

```bash
# Stáhněte a rozbalte do plugins složky
cd /wp-content/plugins/
git clone https://github.com/webklient/foxentry-integration.git
```

## 🔧 Jak to funguje

1. **Vložíte Foxentry kód** do admin rozhraní
2. **Plugin automaticky** vloží kód do patičky webu před `</body>` tag
3. **Použijete shortcodes** pro validátory ve vašem obsahu
4. **Real-time validace** probíhá přímo na frontendu

## 🛠️ Admin rozhraní

Plugin poskytuje intuitivní admin rozhraní s:

- 📋 **Detailní návod** k získání Foxentry kódu
- 🔍 **Test kódu** - ověření platnosti vloženého kódu
- 🌐 **Kontrola frontendu** - automatická kontrola, zda se kód nachází na webu
- ⚙️ **Nastavení cache** - konfigurace doby ukládání výsledků

## 🎨 Screenshots

| Admin rozhraní | Email validátor | Telefon validátor |
|---|---|---|
| ![Admin](screenshots/admin.png) | ![Email](screenshots/email.png) | ![Phone](screenshots/phone.png) |

## ❓ FAQ

### Jak získám Foxentry kód?

1. Zaregistrujte se na [Foxentry](https://app.foxentry.com/registration?aff=or8eaq)
2. V dashboardu klikněte na váš projekt
3. V levém menu klikněte na "Integrace"
4. Zkopírujte kód ze sekce "Vložení kódu do stránek"

### Je plugin zdarma?

Ano, plugin je 100% zdarma! Potřebujete pouze Foxentry účet pro získání integračního kódu.

### Funguje se všemi tématy?

Ano, plugin je bulletproof a funguje se všemi tématy. Používá několik fallback hooků pro zajištění vložení kódu.

### Jak ověřím, že kód funguje?

V adminu pluginu klikněte na "Zkontrolovat, zda je kód na frontendu" - plugin automaticky ověří přítomnost kódu na vašem webu.

## 🔧 Pro vývojáře

### WordPress Hooks

```php
// Hooks pro rozšíření
do_action('foxentry_before_injection', $foxentry_code);
do_action('foxentry_after_injection', $foxentry_code);

// Filtry
$foxentry_code = apply_filters('foxentry_injection_code', $foxentry_code);
```

### Technické detaily

- **WordPress Hooks:** `wp_footer`, `wp_print_footer_scripts`, `wp_print_scripts`
- **JavaScript API:** Foxentry validace na frontendu s debounce 500ms
- **Bezpečnost:** Nonce validace, sanitizace vstupů, ověření oprávnění
- **Cache:** Nastavitelný cache systém pro optimalizaci výkonu

## 🔒 Ochrana dat

- Plugin **neukládá** žádná osobní data uživatelů
- Foxentry kód se vkládá pouze do HTML výstupu
- Validace probíhá přímo na frontendu pomocí Foxentry API
- Cache výsledků validace je dočasný (nastavitelný)
- Všechna data jsou zpracovávána podle privacy policy Foxentry

## 📞 Podpora

Pro technickou podporu kontaktujte:

- **Webklient** - [www.webklient.cz](https://www.webklient.cz)
- **Email:** info@webklient.cz

## 📄 Licence

Tento plugin je licencován pod GPL v2 nebo novější. Více informací najdete v [LICENSE](LICENSE) souboru.

## 🏢 O autorovi

Plugin vytvořen studiem **Webklient** - [www.webklient.cz](https://www.webklient.cz)  
**Mediatoring.com s.r.o.** - Specializace na moderní webová řešení a WordPress development

---

⭐ **Pokud se vám plugin líbí, dejte nám hvězdičku na GitHubu!** ⭐
