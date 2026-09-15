# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Choukrane Pâtisserie is a multi-vendor pastry marketplace for Cameroon: Yaoundé and Douala, prices in FCFA, `+237` phone numbers, and payment by Orange Money, MTN MoMo or cash. The repo holds two independent apps:

- `backend/`: Laravel 12 (PHP 8.2) JSON API with Sanctum bearer tokens and MySQL.
- `frontend/`: Vue 3 SPA (Vite 5, Pinia, vue-router, Tailwind 3, axios).

Domain vocabulary, DB columns, routes and user-facing messages are in French (`commande`, `panier`, `vendeur`, `quartier`, `statut`…). Keep new code consistent with that.

`backend/package.json`, `backend/vite.config.js`, `backend/resources/css|js` and `welcome.blade.php` are the unused Laravel skeleton. The real UI is in `frontend/`. The only Blade views in use are `resources/views/pdf/*` (dompdf invoices and reports) and `resources/views/emails/*`.

## Commands

Local development runs on XAMPP on Windows (PHP at `C:\xampp\php`, MySQL; `backend/.env` uses database `patisserie_glacier`). `extension=gd` must be enabled in `C:\xampp\php\php.ini`: dompdf needs it for the images in PDFs.

If requests hang forever (a login button that keeps spinning), check MariaDB first: a corrupted XAMPP data directory can leave `mysqld` accepting connections without ever answering. Look in `C:\xampp\mysql\data\mysql_error.log`. Two safeguards now turn that hang into an error:
- `config/database.php` sets `PDO::ATTR_TIMEOUT` (`DB_TIMEOUT`, 5 s) for the TCP connect.
- `AppServiceProvider` caps `mysqlnd.net_read_timeout` for HTTP requests only (`DB_READ_TIMEOUT`, 5 s). mysqlnd copies that value into each connection, so a long-running query in a web request fails too; console commands keep PHP's default.

`bootstrap/app.php` answers a lost DB connection with a 503 and a French message, and axios times out after 30 s.

Backend (from `backend/`):
```bash
composer install
php artisan migrate --seed          # users, quartiers, catégories, produits, villes livrées par les vendeurs
php artisan storage:link            # uploaded images are served from /storage
php artisan serve                   # http://localhost:8000, API under /api/v1
composer test                       # config:clear + PHPUnit on in-memory SQLite (phpunit.xml)
php artisan test --filter=CheckoutMultiVendeurTest
php vendor/bin/pint                 # formatter; CI runs `pint --test`
```

Seeded accounts (`UserSeeder`, password `password123`): admin `+237699000001`, vendeurs `+237699000002` (Jean) and `+237699000004` (Awa), client `+237699000003`. Seeded products are split between the two vendors.

The seeded vendors:
- have a complete shop profile, with SVG logos copied from `database/seeders/fichiers/` to the public disk;
- use `@choukrane.test` emails, so demo orders never mail a real domain;
- have a delivery minimum: 5000 FCFA for Jean, 3000 FCFA for Awa;
- Jean is also a featured vendor.

`VendeurVilleSeeder` fills the cities each vendor delivers to: Jean delivers Yaoundé only and Awa Douala only, so the catalogue visibly changes with the chosen city. Every other vendor delivers both cities. It uses `insertOrIgnore` and can be rerun alone with `--class`.

With `QUEUE_CONNECTION=sync` (the XAMPP default), invoice emails go out during the vendor's "confirmer" request. Set `MAIL_MAILER=log` locally to avoid sending real mail.

Prefer `composer test`: it clears the config cache first. With a cached config, tests would use the MySQL connection from `.env` instead of in-memory SQLite, and `RefreshDatabase` would wipe the dev database.

Frontend (from `frontend/`):
```bash
npm install
npm run dev       # http://localhost:5173
npm run build
npm run lint      # ESLint 10, flat config in eslint.config.js; CI runs this
npm run lint:fix
```
`frontend/.env` sets `VITE_API_URL=http://localhost:8000/api/v1`, so dev calls the API directly. In the Docker image `VITE_API_URL=/api/v1`, because the SPA and the API are served from the same origin.

Docker (from repo root) — one image holds nginx, php-fpm, the queue worker, the scheduler and the built SPA:
```bash
cp .env.docker.example .env.docker      # then fill in the passwords and APP_KEY
docker compose up -d --build            # app on :8088, phpMyAdmin on :8081
RUN_SEEDERS=true docker compose up -d   # first run only: demo data
```
`docker/entrypoint.sh` waits for MySQL, runs `migrate --force`, then rebuilds `config:cache`, `route:cache` and `view:cache` from the environment. With no `APP_KEY` it generates one into the `app_storage` volume and warns. MySQL's port is deliberately not published, so it never clashes with XAMPP.

The app port defaults to 8088 because an Oracle listener holds 8080 on the dev machine. Override it with `APP_PORT` in the shell or in a root `.env`: `env_file: .env.docker` does not feed compose's `${…}` interpolation. Invoices and reports are stored on the `local` disk, `storage/app/private`, which lives in the `app_storage` volume.

CI (`.github/workflows/ci.yml`), four jobs:
- **backend**: `pint --test` then `composer test` (SQLite).
- **migrations-mysql**: `migrate --seed` against a real MySQL 8 service, then `migrate:reset` + `migrate` so every `down()` stays usable.
- **frontend**: `npm run lint` (no `--fix`) then `npm run build`.
- **docker**: build, Trivy scan blocking on CRITICAL/HIGH, then a Compose smoke test (SPA, `/up`, catalogue, login with an `Origin` header, an authenticated route, and the presence of both background workers).

Tests live in `backend/tests/Feature/`, with shared factories in `tests/Concerns/CreeDonneesBoutique.php`:
- `creerUtilisateur('vendeur')` creates a vendor with a complete shop profile;
- `creerVendeurIncomplet()` creates one without it;
- the base `TestCase` fakes the `local` and `public` disks, so generated PDFs never land in `storage/app`.

All migrations must run on SQLite as well as MySQL:
- `2026_08_04_000001_rename_role_livreur_to_vendeur` runs raw `ALTER TABLE … MODIFY COLUMN` on MySQL/MariaDB only and uses the schema builder's `->change()` elsewhere.
- A migration that drops a column must drop that column's indexes first, or SQLite refuses. This applies to `down()` rollbacks too.
- On MySQL, an index can also back a foreign key. Create its replacement before dropping it, or MySQL fails with error 1553. See `2026_09_15_000004_vendeurs_vedette`, which drops `produits.est_vedette`.

## Architecture

### Roles and routing
`users.role` is `client | admin | vendeur`. The `vendeur` role replaced the old `livreur` (delivery person) role; vendors now sell and deliver their own products. Some legacy names remain:
- `commandes.livreur_id` is still written and mirrors `vendeur_id`.
- `Admin\CommandeController::assignLivreur` handles both the `assign-livreur` and `assign-vendeur` routes.
- The `vendeur/livraisons/*` routes are aliases of `vendeur/commandes/*`.

Use `vendeur` in new code.

`bootstrap/app.php` registers the middleware aliases `admin`, `client`, `vendeur`, `actif` and `profil.vendeur`. Route groups in `routes/api.php`, all under `/api/v1`:
- public: auth, catalogue (optional `?ville=`), `livraison/quartiers`, `livraison/vendeur/{id}`, `vendeurs/{id}`, `conditions-vendeur`
- `auth:sanctum`: profile, adresses, notifications, plus a nested `client` group for panier, commandes and `commandes/{id}/facture`
- `auth:sanctum` + `admin`: `/admin/*`, including `rapports`
- `auth:sanctum` + `vendeur`: `/vendeur/profil` (GET/PUT), then everything else under `/vendeur/*` behind `profil.vendeur`

### Vendor shop profile
- A vendor must complete a shop profile before selling: `email`, `logo_boutique` (public disk, `boutiques/`), `description_boutique` (30+ chars) and `conditions_acceptees_le`.
- The conditions text is the `conditions_vendeur` parameter.
- `User::estProfilVendeurComplet()` and `scopeProfilVendeurComplet()` implement the rule. The appended attribute `profil_vendeur_complet` is `true` for non-vendors.
- An incomplete vendor:
  - gets a 403 `{ code: 'profil_vendeur_incomplet' }` from `EnsureProfilVendeurComplet`;
  - is redirected by the router guard to `/vendeur/profil-boutique`. `api.js` also redirects on that 403;
  - has hidden products: `Produit::scopeVisible()` shows a product only if its creator is not a vendor, or is an active vendor with a complete profile. The same rule blocks those products at panier and checkout.
- `Admin\UserController::updateRole` notifies a newly promoted vendor (`NotificationsCompte::devenuVendeur`).
- The public shop page is `GET /vendeurs/{id}`, shown by `views/VendeurProfil.vue`. The API returns 404 unless the vendor is active and complete.

### Featured vendors
- The admin sets `users.est_vendeur_vedette` with `PATCH /admin/users/{id}/vedette`. It replaced the old `produits.est_vedette`.
- `Produit::scopeVedette()` keeps the products of featured vendors. `scopeVendeursVedettesEnTete()` sorts them first and runs before the requested sort in the catalogue lists.
- Public product JSON loads the creator with `Produit::VENDEUR_PUBLIC`: `id, nom_complet, logo_boutique, est_vendeur_vedette`, with no phone and no email. The badge reads `produit.createur.est_vendeur_vedette`.

### Vendor ownership
`created_by_user_id` identifies a product's vendor; categories also have this column. The `Admin\` Categorie and Produit controllers are also mounted under `/vendeur/catalogue/*`. Each has a private `isVendeur()` that limits vendors to their own rows. Keep that scoping working for both entry points when editing them.

### Cart → orders (multi-vendor)
- `paniers.vendeur_id` is copied from `produit.created_by_user_id` and re-synced when the cart is read and at checkout.
- Cart lines expire at `date_expiration`. The lifetime comes from `ParametreSite::get('panier_expiration_heures', 24)`, and every panier endpoint purges expired rows first.
- `Api\CommandeController::store` groups the cart by `vendeur_id` and creates **one `Commande` per vendor** inside a `DB::transaction`. Each order gets its own delivery fee, `LigneCommande` rows, stock decrement and first `HistoriqueStatutCommande` entry. Vendors are notified after the commit (in-app `Notification` plus an email via `Mail::raw`). Business-rule failures throw `\InvalidArgumentException`, which is returned as a 422.
- Delivery rules live in `App\Services\LivraisonVendeur`:
  - the fee is the same for everyone: the `frais_livraison_standard` parameter, 1500 FCFA by default, charged per vendor order;
  - the vendor must deliver the address's city (`vendeur_villes`, managed from "Ma livraison"). The city is `Adresse::villeDeLivraison()`, i.e. the city of the chosen quartier. There are no delivery delays;
  - the vendor's products must reach `users.montant_minimum_livraison` (0 means no minimum). Pickup in store ignores the minimum and the fee.
- Vendors set their cities and minimum together with `GET/PUT /vendeur/livraison` (`Vendeur\LivraisonController`).
- The frontend previews all of this through the public `GET /livraison/vendeur/{id}`, which returns `{ frais_livraison, montant_minimum_livraison, villes }`. The backend re-checks it authoritatively in `store()` and `update()`.
- Catalogue filtered by city:
  - the client picks a city (`stores/ville.js`, kept in `localStorage`, header selector and first-visit prompt);
  - `api.js` adds `?ville=` to the catalogue calls (products, featured, new, promotions, similar, categories);
  - the backend applies `Produit::scopeLivrableDans($ville)`, which keeps only products whose vendor delivers that city;
  - a vendor's public page ignores the city and shows the cities they deliver.
- The former zone system (`zone_livraisons`, `livreur_zone_livraisons`, `adresses.zone_livraison_id`, `calculate-shipping`) and the per-quartier coverage (`vendeur_tarifs_livraison`) were removed in `2026_09_15_000005_livraison_par_ville`.
- Change order status with `Commande::changerStatut()`, which records history. `scopeArchivee` and `scopeVisibleDansListes` define which orders the operational lists hide (cancelled, or delivered and paid).

### Invoices and reports (dompdf)
- **Invoices.** Moving an order to `confirmee` dispatches `GenererEtEnvoyerFacture` from `changerStatut()`, whoever confirms it:
  - `Services\Factures::generer()` is idempotent and creates one `Facture` per order. Numbers are `FAC-AAAAMM-NNNN`; the PDF is written to `factures/AAAA/MM/` on the private `local` disk;
  - if the client has an email, the job sends `FactureCommandeMail` with the PDF attached and sets `envoyee_le`;
  - `FactureController` serves the download to the client (`commandes/{id}/facture`), the vendor (`vendeur/commandes/{id}/facture`) and the admin (`admin/commandes/{id}/facture`). A missing file is regenerated;
  - order `show` endpoints load `facture:id,commande_id,numero_facture,envoyee_le`.
- **Monthly reports.** `Services\RapportMensuelVendeurs` covers every vendor, based on orders created in the month; amounts exclude cancelled orders:
  - `GET /admin/rapports/mensuel?mois=AAAA-MM&format=json|pdf|csv`. CSV is UTF-8 with BOM and a `;` separator. A closed month's files are stored under `rapports/AAAA-MM/` and served again;
  - `rapports:mensuels` (scheduled on the 1st at 06:00) stores last month and notifies the admins.
- dompdf cannot load remote URLs: the vendor logo is inlined as a data URI, and `enable_font_subsetting` keeps PDFs around 25 KB instead of 850 KB.
- The frontend downloads with `responseType: 'blob'` plus `utils/telechargement.js`, and reads blob errors with `lireErreurBlob()`. CORS exposes `Content-Disposition`.

### Other backend conventions
- The password column is `mot_de_passe`, hashed by a mutator on `User`. Login is by `telephone`, which `AuthController` normalizes to `+237XXXXXXXXX`. Logging in revokes all previous tokens.
- JSON responses use `{ success, message?, data }`.
- Runtime settings are stored in `parametre_sites` and read and written with `ParametreSite::get()` / `set()`. Values are typed: string, integer, boolean or json.
- Cities are the lowercase values `yaoundé` / `douala`:
  - backend: `Quartier::VILLES`, `Quartier::regleVille()` for validation, `Quartier::libelleVille()` for display;
  - frontend: `utils/villes.js`;
  - admins manage quartiers at `/admin/quartiers`.
- `adresses` has a text column `quartier` (the name) and a `quartier_id` FK. The relation is deliberately named `Adresse::quartierLivraison()` (JSON key `quartier_livraison`). A relation named `quartier()` would replace the text column in the JSON whenever it is eager-loaded. An address is created with `ville` and `quartier_id` (both required, and the quartier must belong to that city), plus a free, optional `zone` (sector, crossroads…). `AdresseController` copies the quartier name and city into the `quartier` / `ville` columns.
- Uploads go to the `public` disk under `produits/`, `categories/`, `profils/` and `boutiques/`.
- In-app and email notifications go through `NotificationsCommande::creer()` / `envoyerEmail()` (queued). `NotificationsCompte` reuses them for account events.
- Emails:
  - every message handed to the mail transport is logged with its Message-ID in `storage/logs/mail.log` (`MessageSent` listener in `AppServiceProvider`). Use it to match counts with Brevo's logs;
  - queued email closures never rethrow, so a worker retry cannot duplicate a mail;
  - the invoice job claims `factures.envoyee_le` atomically before sending;
  - `EmailsCommandeTest` pins the flow "order + confirmation" to exactly 2 emails.
- `RegleMetierException` (a business-rule refusal rendered as 422) is excluded from error reporting.
- PHPUnit runs with `LOG_CHANNEL=null`: tests never write to the dev `laravel.log`.

### Frontend
- `src/services/api.js` is the only axios client. It adds the bearer token from the auth store, logs out and redirects to `login` on a 401, and groups methods by resource (e.g. `api.panier.add(...)`). The `admin.*` methods pick their prefix from the role: `/vendeur/catalogue` or `/vendeur/commandes` for vendors, `/admin/...` for admins. As a result, the `views/admin/*` screens serve both roles.
- Multipart updates are sent as `POST` with `_method=PUT` (Laravel method spoofing), because PHP doesn't parse multipart PUT bodies.
- Router guards in `src/router/index.js` read these meta flags:
  - `requiresAuth`
  - `requiresAdmin`
  - `requiresCatalogueManager` / `requiresCommandesManager` (admin or vendeur)
  - `requiresVendeur` (vendor-only pages `/vendeur/livraison` "Ma livraison" and `/vendeur/profil-boutique`)
  - `guest`
  - `mobileOnly`

  Admins and vendors are redirected away from the client cart and order pages. A vendor whose `user.profil_vendeur_complet === false` is sent to `vendeur-profil-boutique` from any other route.
- `composables/useLivraisonVendeurs.js` holds the multi-vendor logic shared by `Panier`, `Checkout` and `CommandeDetail`:
  - `grouperParVendeur(items)`;
  - a per-vendor cache of `{ frais, minimum, villes }`;
  - `livraisonDesGroupes(groupes, ville)`, which returns a status for each group (`ok | non_couvert | minimum_non_atteint | sans_ville | sans_vendeur | chargement | erreur`), plus `frais`, `minimum` and `manque`.

  `utils/villes.js` provides `villeAdresse()` and `libelleAdresse()`.
- `components/adresse/AdresseFormModal.vue` is the single address form, used by Checkout and Profil. The fields come in order: city, then a quartier filtered by that city, then an optional zone.
- `api.js` also exports `messageErreur(error, fallback)`, which returns the first validation error or else the backend message. The client times out after 30 s, and on a 403 `profil_vendeur_incomplet` it reloads the user and opens the shop-profile form.
- Pinia stores: `auth` (token in `localStorage`, role getters), `panier`, `notifications` and `toast`.
- Polling goes through `utils/sondagePartage.js`:
  - at most one request per interval (2 min) across all open tabs;
  - the value and the next due time are shared in `localStorage`, keyed per user;
  - no request while the tab is hidden, and the delay doubles on errors (up to 15 min).
- The unread notification count (Header) and the vendor's pending-orders badge (`composables/useVendeurCommandesBadge.js`) use it. Navigating never triggers a request. Pass `{ force: true }` only when fresh data is needed: the Notifications page, or after a vendor acts on an order.
- Shared helpers, used instead of per-component copies: `utils/images.js` (`resolveImageUrl`, `onImageError`, `verifierImage`), `utils/format.js` (prices, dates, status labels and classes) and `utils/redirection.js`.
- No `alert`/`confirm`/`prompt`: use the `toast` store and `useConfirm()` (`components/common/ConfirmDialog.vue`).
- `@` is an alias for `frontend/src`.

The root `todo` file lists pending work (in French).
