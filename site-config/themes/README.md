# Premium / pre-built theme zips

Drop premium theme zip files here (e.g. `betheme.zip`, `betheme-child.zip`),
then reference them from the `theme` block in `site.json`:

```json
"theme": {
  "source": "zip",
  "slug": "betheme",
  "zip": "betheme.zip",
  "child": { "source": "zip", "slug": "betheme-child", "zip": "betheme-child.zip" }
}
```

Zip files in this folder are **git-ignored** — premium themes are licensed and
must not be committed. Only this README is tracked.
