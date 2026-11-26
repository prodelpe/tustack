# Roadmap

## Refactors

- Add CI with GitHub Actions: run Pint and the test suite on every push, and show the badge in the README.
- Add a summary table company–technology (offer count and last offer date) to replace the four-table join repeated across `LandingPages`, `LandingController`, `Company` and the salary and tendency endpoints.
- Simplify the fetch orchestration with batch callbacks instead of the polling command.
- Retire the versioned enrichment snapshot now that production data lives in the database.
- Honour the free-text `query` filter in search alerts.
- Move the Google Translate key out of the URL and into a header.
