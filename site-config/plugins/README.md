# Premium / bundled plugin zips

Drop premium or bundled plugin zip files here (for example the plugins a
premium theme requires, such as a page builder or slider), then reference them
from the `plugins` array in `site.json`:

```json
"plugins": [
  "contact-form-7",
  { "source": "zip", "slug": "js_composer", "zip": "js_composer.zip" },
  { "source": "url", "slug": "revslider", "url": "https://example.com/revslider.zip" }
]
```

Zip files in this folder are **git-ignored** — only this README is tracked.
