# Roadmap

## Refactors

- Add CI with GitHub Actions: run Pint and the test suite on every push, and show the badge in the README.
- Move the closures in `routes/web.php` (dashboard, alerts, Telegram connect/disconnect) into controllers.
- Extract a single Gemini client: the HTTP call and the `MODEL` constant are duplicated in `EnrichCompanyWithGeminiAction` and `AskGeminiAboutCompanyNamesAction`.
- Write the `FetchReport` mail in one language: some lines are in Catalan and the rest in English.
