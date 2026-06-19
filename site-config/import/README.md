# Pre-built / demo website import files

Place exported demo files here to import a pre-built website, then reference
them from the `import` block in `site.json`:

```json
"import": {
  "content": "demo-content.xml",     // WordPress eXtended RSS (WXR) export
  "authors": "create",                // create | skip
  "widgets": "widgets.wie",           // Widget Importer/Exporter file (optional)
  "customizer": "customizer.dat",     // Customizer Export/Import file (optional)
  "options": "theme-options.json",    // { "option_name": value, ... } (optional)
  "menus": { "primary": "Main Menu" } // assign imported menu to a theme location
}
```

How to get these files for a pre-built theme (e.g. BeTheme):

1. Install the theme + its demo once (locally or on the vendor's sandbox).
2. Export the content via **Tools → Export → All content** to get the WXR XML.
3. (Optional) Export widgets with the *Widget Importer/Exporter* plugin and
   theme settings with *Customizer Export/Import*.
4. Drop the files here and point `site.json` at them.

Exported data files here are **git-ignored** — only this README is tracked.
