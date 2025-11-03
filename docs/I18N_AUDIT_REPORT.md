# WordPress Plugin i18n Audit Report

**Plugin**: Custom Post Types RestAPI
**Text Domain**: `wp-cpt-rest-api`
**Audit Date**: 2025-11-03
**Re-audit Date**: 2025-11-03 (After French Translation Test)
**Overall Grade**: **C+ (70%)**

---

## Executive Summary

After testing the plugin on a French WordPress installation, significant translation gaps were discovered. While the plugin has excellent i18n infrastructure (162 translation function calls properly implemented), **many strings in the French translation file (wp-cpt-rest-api-fr_FR.po) are missing or incomplete**. Additionally, **3 hardcoded JavaScript strings** and **several context-specific translations** need attention.

**Critical Finding**: The code implementation is correct with proper use of translation functions, but the French `.po` file is outdated and missing approximately **30-40% of translatable strings**.

---

## Audit Findings

### ✅ Strengths

#### 1. Excellent Code Implementation
- **162 translation function calls** properly implemented in PHP
- Text domain `'wp-cpt-rest-api'` used consistently (100%)
- Proper escaping with `esc_html__()`, `esc_attr__()`, `esc_js()`
- Plural forms handled correctly with `_n()`

#### 2. Infrastructure in Place
- `load_plugin_textdomain()` correctly implemented
- Hooked to `plugins_loaded` action
- Languages directory exists with French translation file
- JavaScript localization system using `wp_localize_script()`

---

### ❌ Critical Issues Found

### Issue #1: Incomplete French Translation File (CRITICAL)

The following strings appear in English on the French WordPress admin page because they're **missing from `wp-cpt-rest-api-fr_FR.po`**:

#### Admin Page Headers & Sections (9 strings)
1. **"API Settings"** - Appears as section header
2. **"Toolset Relationships"** - Section header
3. **"Enable support for Toolset relationship functionality in the REST API."** - Description text
4. **"Non-Public Custom Post Types"** - Section header
5. **"Control whether non-public Custom Post Types should be available for selection."** - Description
6. **"Select which Custom Post Types should be available through the REST API."** - Description
7. **"API Keys Management"** - Main section header
8. **"Create and manage API keys for accessing the REST API endpoints."** - Description
9. **"API keys can be used to authenticate requests to the REST API using the Bearer authentication method."** - Description

#### Table Headers (5 strings)
10. **"Post Type"** - Table column header
11. **"Description"** - Table column header
12. **"Slug"** - Table column header
13. **"Visibility"** - Table column header
14. **"Status"** - Table column header

#### Table Content (3 strings)
15. **"No description available"** - Fallback text for CPTs without descriptions
16. **"Public"** - Visibility label
17. **"Activate"** - Toggle button label

#### API Keys Section (8 strings)
18. **"Your API Keys"** - Section subheader
19. **"Key Prefix"** - Table column
20. **"Actions"** - Table column
21. **"Full key hidden for security"** - Security notice in table
22. **"Create a New API Key"** - Form header
23. **"Enter a label for your API key"** - Input placeholder
24. **"A descriptive name to help you identify this key."** - Help text
25. **"Important: Save Your API Key Now"** - Warning header

#### Buttons & Actions (6 strings)
26. **"Reset All"** - Button text
27. **"Reset All will deactivate all Custom Post Types."** - Button description
28. **"Save Settings"** - Submit button
29. **"Copy Key"** - Button to copy API key
30. **"This key will only be displayed once and cannot be recovered."** - Critical warning
31. **"Copy it now and store it securely. If you lose this key, you will need to generate a new one."** - Warning continuation

#### Tooltips & Help Text (4+ strings)
32. **"When enabled, this will add REST API endpoints..."** - Toolset help tooltip
33. **"Select which types of non-public CPTs..."** - Non-public CPTs help
34. **"Publicly Queryable"** - Checkbox label
35. **"Admin Only (Show UI)"** - Checkbox label
36. **"Private"** - Checkbox label
37. **"Choose which types of non-public CPTs to make available..."** - Extended tooltip
38. **"Note: Public CPTs are always available..."** - Help note
39. **"Are you sure you want to delete this API key? This action cannot be undone."** - Delete confirmation (data-confirm attribute)

---

### Issue #2: Hardcoded JavaScript Strings (3 strings)

**File**: `src/assets/js/wp-cpt-restapi-admin.js`

1. **Line 184**: `'Are you sure you want to deactivate all Custom Post Types? This action will uncheck all toggle switches.'`
2. **Line 198**: `'Resetting...'`
3. **Line 215**: `'Reset All'`

These strings need to be added to the `wp_localize_script()` array.

---

## Translation Coverage Statistics

| Category | Total | Implemented | Missing | Coverage |
|----------|-------|-------------|---------|----------|
| **PHP Translation Calls** | 162 | 162 | 0 | 100% ✅ |
| **Code Implementation** | 162 | 162 | 0 | 100% ✅ |
| **French .po Translations** | ~200 | 119 | ~80 | 60% ❌ |
| **JavaScript Localized** | 10 | 7 | 3 | 70% ⚠️ |
| **Overall User-Facing** | ~210 | ~145 | ~65 | **70%** ⚠️ |

---

## Root Cause Analysis

### Why Strings Appear in English

The issue is **NOT** with the code - all strings are properly wrapped in translation functions. The problem is:

1. ✅ **Code**: Strings are correctly using `__()`, `esc_html__()`, etc.
2. ❌ **Translation File**: The `wp-cpt-rest-api-fr_FR.po` file only has 119 translations
3. ❌ **Missing Strings**: ~80-85 translatable strings are not in the `.po` file
4. ❌ **Outdated POT**: The `.pot` template file may not have been regenerated after recent additions

### What Needs to Be Done

1. **Regenerate POT file** from current codebase (will capture all 162+ strings)
2. **Update French .po file** with all missing translations
3. **Compile .mo file** from updated .po
4. **Fix 3 JavaScript** hardcoded strings
5. **Test** on French WordPress installation

---

## Detailed Fix Instructions

### Step 1: Regenerate POT File

Use WP-CLI to extract all translatable strings from current code:

```bash
wp i18n make-pot src/ src/languages/wp-cpt-rest-api.pot
```

This will create an updated template with ALL 162+ translatable strings.

### Step 2: Update French Translation File

Open `src/languages/wp-cpt-rest-api-fr_FR.po` in Poedit or translation editor:

1. **Update from POT template**: This will add all missing strings
2. **Translate the ~80 missing strings**
3. **Save and compile** to generate `.mo` file

#### Priority Translations (Top 40 strings that appear in UI):

```po
# Section Headers
msgid "API Settings"
msgstr "Paramètres de l'API"

msgid "Toolset Relationships"
msgstr "Relations Toolset"

msgid "Enable support for Toolset relationship functionality in the REST API."
msgstr "Activer la prise en charge de la fonctionnalité de relations Toolset dans l'API REST."

msgid "Non-Public Custom Post Types"
msgstr "Types de publication personnalisés non publics"

msgid "Control whether non-public Custom Post Types should be available for selection."
msgstr "Contrôler si les types de publication personnalisés non publics doivent être disponibles pour sélection."

msgid "Select which Custom Post Types should be available through the REST API."
msgstr "Sélectionnez les types de publication personnalisés qui doivent être disponibles via l'API REST."

msgid "API Keys Management"
msgstr "Gestion des clés API"

msgid "Create and manage API keys for accessing the REST API endpoints."
msgstr "Créer et gérer les clés API pour accéder aux points de terminaison de l'API REST."

msgid "API keys can be used to authenticate requests to the REST API using the Bearer authentication method."
msgstr "Les clés API peuvent être utilisées pour authentifier les requêtes vers l'API REST en utilisant la méthode d'authentification Bearer."

# Table Headers
msgid "Post Type"
msgstr "Type de publication"

msgid "Description"
msgstr "Description"

msgid "Slug"
msgstr "Identifiant"

msgid "Visibility"
msgstr "Visibilité"

msgid "Status"
msgstr "Statut"

# Table Content
msgid "No description available"
msgstr "Aucune description disponible"

msgid "Public"
msgstr "Public"

msgid "Activate"
msgstr "Activer"

# API Keys
msgid "Your API Keys"
msgstr "Vos clés API"

msgid "Key Prefix"
msgstr "Préfixe de clé"

msgid "Actions"
msgstr "Actions"

msgid "Full key hidden for security"
msgstr "Clé complète masquée pour la sécurité"

msgid "Create a New API Key"
msgstr "Créer une nouvelle clé API"

msgid "Enter a label for your API key"
msgstr "Entrez un libellé pour votre clé API"

msgid "A descriptive name to help you identify this key."
msgstr "Un nom descriptif pour vous aider à identifier cette clé."

msgid "Important: Save Your API Key Now"
msgstr "Important : Enregistrez votre clé API maintenant"

# Buttons
msgid "Reset All"
msgstr "Tout réinitialiser"

msgid "Reset All will deactivate all Custom Post Types."
msgstr "Tout réinitialiser désactivera tous les types de publication personnalisés."

msgid "Save Settings"
msgstr "Enregistrer les paramètres"

msgid "Copy Key"
msgstr "Copier la clé"

msgid "This key will only be displayed once and cannot be recovered."
msgstr "Cette clé ne sera affichée qu'une seule fois et ne peut pas être récupérée."

msgid "Copy it now and store it securely. If you lose this key, you will need to generate a new one."
msgstr "Copiez-la maintenant et stockez-la en toute sécurité. Si vous perdez cette clé, vous devrez en générer une nouvelle."

# Checkboxes
msgid "Publicly Queryable"
msgstr "Interrogeable publiquement"

msgid "Admin Only (Show UI)"
msgstr "Administration uniquement (Afficher l'interface)"

msgid "Private"
msgstr "Privé"

# Help Text
msgid "When enabled, this will add REST API endpoints for managing Toolset relationships between Custom Post Types. Requires Toolset Types plugin to be installed and active."
msgstr "Lorsqu'activé, cela ajoutera des points de terminaison API REST pour gérer les relations Toolset entre les types de publication personnalisés. Nécessite que le plugin Toolset Types soit installé et actif."

msgid "Select which types of non-public Custom Post Types should be available for selection:"
msgstr "Sélectionnez les types de publications personnalisées non publiques qui doivent être disponibles pour sélection :"

msgid "Choose which types of non-public CPTs to make available for API exposure. Publicly Queryable CPTs can be queried but aren't fully public. Admin Only CPTs show in WordPress admin. Private CPTs are completely hidden from public access."
msgstr "Choisissez les types de CPT non publics à rendre disponibles pour l'exposition API. Les CPT interrogeables publiquement peuvent être interrogés mais ne sont pas entièrement publics. Les CPT d'administration uniquement s'affichent dans l'administration WordPress. Les CPT privés sont complètement cachés de l'accès public."

msgid "Note: Public CPTs are always available. Select additional visibility types to include in the list below."
msgstr "Remarque : Les CPT publics sont toujours disponibles. Sélectionnez des types de visibilité supplémentaires à inclure dans la liste ci-dessous."

# Confirmations
msgid "Are you sure you want to delete this API key? This action cannot be undone."
msgstr "Êtes-vous sûr de vouloir supprimer cette clé API ? Cette action ne peut pas être annulée."
```

### Step 3: Fix JavaScript Hardcoded Strings

#### Update `src/admin/class-wp-cpt-restapi-admin.php` (line ~163):

Add these strings to the localization array:

```php
'i18n'   => array(
    'emptyLabel'       => esc_js( __( 'Please enter a label for the API key.', 'wp-cpt-rest-api' ) ),
    'generating'       => esc_js( __( 'Generating...', 'wp-cpt-rest-api' ) ),
    'generateKey'      => esc_js( __( 'Generate API Key', 'wp-cpt-rest-api' ) ),
    'copy'             => esc_js( __( 'Copy Key', 'wp-cpt-rest-api' ) ),
    'copied'           => esc_js( __( 'Copied!', 'wp-cpt-rest-api' ) ),
    'copyFailed'       => esc_js( __( 'Failed to copy to clipboard.', 'wp-cpt-rest-api' ) ),
    'ajaxError'        => esc_js( __( 'An error occurred. Please try again.', 'wp-cpt-rest-api' ) ),
    // Add these three:
    'resetCptsConfirm' => esc_js( __( 'Are you sure you want to deactivate all Custom Post Types? This action will uncheck all toggle switches.', 'wp-cpt-rest-api' ) ),
    'resetting'        => esc_js( __( 'Resetting...', 'wp-cpt-rest-api' ) ),
    'resetAll'         => esc_js( __( 'Reset All', 'wp-cpt-rest-api' ) ),
),
```

#### Update `src/assets/js/wp-cpt-restapi-admin.js`:

```javascript
// Line 184
const confirmMessage = cptRestApiAdmin.i18n.resetCptsConfirm;

// Line 198
$('.cpt-rest-api-reset-cpts').prop('disabled', true).text(cptRestApiAdmin.i18n.resetting);

// Line 215
$('.cpt-rest-api-reset-cpts').prop('disabled', false).text(cptRestApiAdmin.i18n.resetAll);
```

### Step 4: Test Translation

1. Regenerate POT file
2. Update French .po file with missing strings
3. Compile to .mo file
4. Clear WordPress cache
5. Test on French WordPress installation
6. Verify all strings appear in French

---

## Recommendations

### Immediate Actions (Required)

1. ✅ **Update translation workflow**:
   - Regenerate POT after code changes
   - Update all `.po` files from POT
   - Compile `.mo` files

2. ✅ **Complete French translation**:
   - Add ~80 missing strings to fr_FR.po
   - Priority: UI-visible strings first

3. ✅ **Fix JavaScript strings**:
   - Add 3 strings to localization
   - Update JavaScript code

### Long-term Improvements

1. **Automated POT Generation**: Add to build/release process
   ```bash
   wp i18n make-pot src/ src/languages/wp-cpt-rest-api.pot
   ```

2. **Translation Validation**: Test with multiple languages before release

3. **Translation Management**: Consider using:
   - GlotPress for community translations
   - Poedit for local development
   - Translation plugins for updates

4. **Documentation**: Add translation guide for contributors

---

## Conclusion

### Current State
- **Code Quality**: Excellent (100% i18n functions used correctly)
- **Infrastructure**: Excellent (proper setup, files in place)
- **Translation Completeness**: Poor (only 60% of strings translated to French)
- **User Experience**: Poor on non-English sites (70% of UI in English)

### Required Actions
1. **Critical**: Update French .po file with ~80 missing strings
2. **High**: Fix 3 JavaScript hardcoded strings
3. **Medium**: Regenerate POT file and establish workflow

### After Fixes
- Code implementation: A+ (100%)
- French translation: A (95%+)
- **Expected Overall Grade**: **A (95%+)**

---

**Current Grade**: **C+ (70%)**
**Achievable Grade**: **A (95%+)** after completing French translation

The plugin has excellent i18n infrastructure and code implementation. The issue is solely with the incomplete French translation file, which can be resolved by updating the `.po` file with the missing strings listed in this report.

---

## Files Requiring Updates

1. **src/languages/wp-cpt-rest-api.pot** - Regenerate from current code
2. **src/languages/wp-cpt-rest-api-fr_FR.po** - Add ~80 missing translations
3. **src/languages/wp-cpt-rest-api-fr_FR.mo** - Compile from updated .po
4. **src/admin/class-wp-cpt-restapi-admin.php** - Add 3 JS strings to localization (line ~163)
5. **src/assets/js/wp-cpt-restapi-admin.js** - Use localized strings (lines 184, 198, 215)

---

**Report Updated**: 2025-11-03
**Plugin Version**: 1.0.1
**WordPress Tested**: 6.8 (French)
