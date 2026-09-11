# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Choukrane Pâtisserie is a multi-vendor pastry marketplace for Cameroon: Yaoundé and Douala, prices in FCFA, `+237` phone numbers, and payment by Orange Money, MTN MoMo or cash. The repo holds two independent apps:

- `backend/`: Laravel 12 (PHP 8.2) JSON API with Sanctum bearer tokens and MySQL.
- `frontend/`: Vue 3 SPA (Vite 5, Pinia, vue-router, Tailwind 3, axios).

Domain vocabulary, DB columns, routes and user-facing messages are in French (`commande`, `panier`, `vendeur`, `quartier`, `statut`…). Keep new code consistent with that.

`backend/resources/`, `backend/package.json` and `backend/vite.config.js` are the unused Laravel skeleton (welcome page only). The real UI is in `frontend/`.

## Commands

Local development runs on XAMPP on Windows (PHP at `C:\xampp\php`, MySQL; `backend/.env` uses database `patisserie_glacier`).

Backend (from `backend/`):
```bash
composer install
php artisan migrate --seed          # users, quartiers, zones, catégories, produits, tarifs vendeurs
php artisan storage:link            # uploaded images are served from /storage
php artisan serve                   # http://localhost:8000, API under /api/v1
composer test                       # config:clear + PHPUnit on in-memory SQLite (phpunit.xml)
php artisan test --filter=CheckoutMultiVendeurTest
php vendor/bin/pint                 # formatter; CI runs `pint --test`
```

Seeded accounts (`UserSeeder`, password `password123`): admin `+237699000001`, vendeurs `+237699000002` (Jean) and `+237699000004` (Awa), client `+237699000003`. Seeded products are split between the two vendors. `VendeurTarifLivraisonSeeder` gives every vendor a 1000 FCFA / 30-60 min tarif on every active quartier. It uses `insertOrIgnore`, so it never overwrites an existing tarif and can be rerun alone with `--class`.

Prefer `composer test`: it clears the config cache first. With a cached config, tests would use the MySQL connection from `.env` instead of in-memory SQLite, and `RefreshDatabase` would wipe the dev database.

Frontend (from `frontend/`):
```bash
npm install
npm run dev       # http://localhost:5173
npm run build
```
`frontend/.env` sets `VITE_API_URL=http://localhost:8000/api/v1`, so dev calls the API directly. The Vite `/api` proxy only applies when `VITE_API_URL` is relative, as in the Docker build.

Docker (from repo root): `docker compose --env-file .env.docker up -d --build` starts:
- MySQL 8.4
- phpMyAdmin on :8081
- php-fpm app, which runs `migrate --force` at startup
- nginx for the API on :8000
- a queue worker
- the built SPA on nginx at :5173, which proxies `/api` and `/storage` to the API

Seeding is manual: `docker compose exec backend-app php artisan db:seed`.

CI (`.github/workflows/ci.yml`): backend `php artisan test` + `pint --test`; frontend `npm run lint` + `npm run build`; then a Trivy scan of the backend image.

Known tooling gaps:
- `npm run lint` cannot pass. ESLint is missing from `frontend/package.json` and the lockfile, and there is no ESLint config.
- `pint --test` fails on many existing files (e.g. `routes/api.php`). Format only the files you touch.

Tests: `tests/Feature/CheckoutMultiVendeurTest.php` covers the multi-vendor checkout, addresses and tarif ownership. All migrations must also run on SQLite. For example, `2026_08_04_000001_rename_role_livreur_to_vendeur` runs raw `ALTER TABLE … MODIFY COLUMN` on MySQL/MariaDB only and uses the schema builder's `->change()` on other drivers.

## Architecture

### Roles and routing
`users.role` is `client | admin | vendeur`. The `vendeur` role replaced the old `livreur` (delivery person) role; vendors now sell and deliver their own products. Some legacy names remain:
- `commandes.livreur_id` is still written and mirrors `vendeur_id`.
- `Admin\CommandeController::assignLivreur` handles both the `assign-livreur` and `assign-vendeur` routes.
- The `vendeur/livraisons/*` routes are aliases of `vendeur/commandes/*`.

Use `vendeur` in new code.

`bootstrap/app.php` registers the middleware aliases `admin`, `client` and `vendeur`. Route groups in `routes/api.php`, all under `/api/v1`:
- public: auth, catalogue, zones/quartiers
- `auth:sanctum`: profile, adresses, notifications, plus a nested `client` group for panier and commandes
- `auth:sanctum` + `admin`: `/admin/*`
- `auth:sanctum` + `vendeur`: `/vendeur/*`

### Vendor ownership
`created_by_user_id` identifies a product's vendor; categories and zones also have this column. The `Admin\` Categorie, Produit and ZoneLivraison controllers are also mounted under `/vendeur/catalogue/*`. Each has a private `isVendeur()` that limits vendors to their own rows. Keep that scoping working for both entry points when editing them.

### Cart → orders (multi-vendor)
- `paniers.vendeur_id` is copied from `produit.created_by_user_id` and re-synced when the cart is read and at checkout.
- Cart lines expire at `date_expiration`. The lifetime comes from `ParametreSite::get('panier_expiration_heures', 24)`, and every panier endpoint purges expired rows first.
- `Api\CommandeController::store` groups the cart by `vendeur_id` and creates **one `Commande` per vendor** inside a `DB::transaction`. Each order gets its own delivery fee, `LigneCommande` rows, stock decrement and first `HistoriqueStatutCommande` entry. Vendors are notified after the commit (in-app `Notification` plus an email via `Mail::raw`). Business-rule failures throw `\InvalidArgumentException`, which is returned as a 422.
- The delivery fee comes from `VendeurTarifLivraison` (vendeur × `Quartier`, `actif`) for the address's `quartier_id`. Checkout requires a `quartier_id` and is blocked if any vendor has no active tarif for that quartier. Vendors manage their tarifs through `/vendeur/tarifs-livraison`.
- The frontend previews fees itself with the public `GET /livraison/quartiers/vendeur/{id}`. The backend recomputes them authoritatively in `store()` and `update()`.
- `calculate-shipping` is no longer called by the frontend. It is cart-based and still falls back to the legacy `ZoneLivraison` (`adresses.zone_livraison_id`, kept nullable for the transition period).
- Change order status with `Commande::changerStatut()`, which records history. `scopeArchivee` and `scopeVisibleDansListes` define which orders the operational lists hide (cancelled, or delivered and paid).

### Other backend conventions
- The password column is `mot_de_passe`, hashed by a mutator on `User`. Login is by `telephone`, which `AuthController` normalizes to `+237XXXXXXXXX`. Logging in revokes all previous tokens.
- JSON responses use `{ success, message?, data }`.
- Runtime settings are stored in `parametre_sites` and read and written with `ParametreSite::get()` / `set()`. Values are typed: string, integer, boolean or json.
- `quartiers.ville` is an enum of lowercase `yaoundé` / `douala`. `zone_livraisons.ville` stores capitalized `Yaoundé` / `Douala` and is queried with `LIKE`.
- `adresses` has a text column `quartier` (the name) and a `quartier_id` FK. The relation is deliberately named `Adresse::quartierLivraison()` (JSON key `quartier_livraison`). A relation named `quartier()` would replace the text column in the JSON whenever it is eager-loaded. `AdresseController` fills `quartier` and `ville` from the `Quartier` when the client sends only `quartier_id`.
- Uploads go to the `public` disk under `produits/`, `categories/` and `profils/`.

### Frontend
- `src/services/api.js` is the only axios client. It adds the bearer token from the auth store, logs out and redirects to `login` on a 401, and groups methods by resource (e.g. `api.panier.add(...)`). The `admin.*` methods pick their prefix from the role: `/vendeur/catalogue` or `/vendeur/commandes` for vendors, `/admin/...` for admins. As a result, the `views/admin/*` screens serve both roles.
- Multipart updates are sent as `POST` with `_method=PUT` (Laravel method spoofing), because PHP doesn't parse multipart PUT bodies.
- Router guards in `src/router/index.js` read these meta flags:
  - `requiresAuth`
  - `requiresAdmin`
  - `requiresCatalogueManager` / `requiresCommandesManager` (admin or vendeur)
  - `requiresVendeur` (the vendor-only page `/admin/tarifs-livraison`)
  - `guest`
  - `mobileOnly`

  Admins and vendors are redirected away from the client cart and order pages.
- `composables/useLivraisonVendeurs.js` holds the multi-vendor logic shared by `Panier`, `Checkout` and `CommandeDetail`:
  - `grouperParVendeur(items)`;
  - a per-vendor cache of covered quartiers;
  - `livraisonDesGroupes(groupes, quartierId)`, which returns a status (`ok | non_couvert | sans_quartier | sans_vendeur | chargement | erreur`) and fees for each group.
- `components/adresse/AdresseFormModal.vue` is the single address form, used by Checkout and Profil. It sends `quartier_id` only.
- `api.js` also exports `messageErreur(error, fallback)`, which returns the first validation error or else the backend message.
- Pinia stores: `auth` (token in `localStorage`, role getters), `panier`, and `notifications` (polls the unread count with `setInterval`). `composables/useVendeurCommandesBadge.js` polls the vendor's order count every 30 s.
- Image URLs are built as the origin of `VITE_API_URL` + `/storage/<path>`. Each component that shows images has its own copy of this helper.
- `@` is an alias for `frontend/src`.

The root `todo` file lists pending work (in French).
