# AGENTS.md

Legacy PHP web app (USVN, "User-friendly SVN") built on **Zend Framework 1**. It manages Subversion repositories via a web UI and shelling out to the `svn` binary.

## Toolchain gotchas

- **No Composer, no npm, no CI, no phpunit.xml.** All dependencies are vendored in `src/library/` (`Zend/`, `geshi/`, `USVN/`). Do not run `composer install` or any package-manager step.
- **Minimum PHP 7.3** (`USVN_MINIMUM_PHP_VERSION` in `src/app/bootstrap.php`). Recent work is PHP 8.x compatibility fixes: no `create_function`, no curly-brace string offsets, no `E_STRICT`. Bootstrap sets `error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED)`.
- No namespaces, no `use`; classes are `Zend_*`, `USVN_*`, `menus_*` autoloaded by `Zend_Loader_Autoloader` with PSR-0-style paths under `src/library/`.
- **No PHP available on the dev host** — you cannot run the app or tests locally. Verify by inspection; use the Vagrant VM (see below) to actually run it.

## Architecture

- Entry points: `src/public/index.php` (app) → `src/app/bootstrap.php`; `src/public/install.php` → `src/app/install/index.php` (web installer); `src/app/install/install-cli.php` (CLI installer).
- **`src/config/config.ini` is gitignored and created at install time** from `src/config/config.ini.example`. If it is missing or has no `version`, the app redirects to `install.php`.
- Config `version` must equal `USVN_CONFIG_VERSION` (currently `1.0.12`, defined in bootstrap). Any other version triggers `USVN_Update::runUpdate()` (in `src/library/USVN/Update.php`), which steps version→version in strict order and **`die()`s on unknown versions** — bump the version constant and the update chain together.
- URL routing is defined in `src/app/routes.ini` (not in PHP): `:controller/:action`, plus `admin/`, `project/:project/`, `group/:group/` areas. New routes go there.
- ZF1 layout: controllers `src/app/controllers/*Controller.php`, views `src/app/views/scripts/<controller>/<action>.phtml`, layouts `src/app/layouts/`, view helpers `src/app/helpers/`.
- Database: Zend_Db (MYSQLI or PDO_SQLITE), table prefix `usvn_` from `database.prefix` config. Schemas: `src/app/install/sql/{mysql,sqlite}.sql`. Tables are wrapped in `src/library/USVN/Db/Table/`.
- Subversion integration shells out via `passthru`/`system` (`src/library/USVN/ConsoleUtils.php`) using config keys `subversion.path/passwd/authz/url`. `src/files/` is gitignored runtime data.

## Running / installing

- `vagrant up` provisions ubuntu/focal64 (Apache + PHP + MySQL + SVN), serves the app at `http://localhost:8080/usvn`. First visit runs the installer, which writes `config.ini`, generates `src/public/.htaccess`, and creates the DB.
- `make rw` writes an empty `[general]` into `src/config/config.ini` and chmods 777 the config/public dirs (used to re-trigger installation after `vagrant destroy`).
- `make clean` removes `src/public/.htaccess` and `src/config/config.ini`. `src/public/.htaccess` is gitignored; `src/public/dot.htaccess` is the tracked template.

## i18n (this project's own convention — easy to get wrong)

- User-facing strings must go through **`T_()`** (defined in `src/app/functions.php`; also `h_()` for `htmlspecialchars`). Hardcoded strings will never be translated.
- Locale files: `src/app/locale/<locale>/messages.{po,mo}`. Both `.po` **and** compiled `.mo` are **tracked in git** — you must commit the recompiled `.mo`, or the app (which reads `.mo` via `Zend_Translate`) keeps old strings.
- Regenerate the POT with `src/app/locale/generate_translation_template.sh` — it runs `xgettext` over **both `*.php` and `*.phtml`** (recently fixed; only scanning php files silently missed view templates).
- Compile catalogs with `src/app/locale/msgfmt.sh` (only recompiles changed `.po`). Verify with `msgfmt --statistics -o /dev/null <locale>/messages.po` — 0 fuzzy, 0 untranslated.
- Menu labels in `src/app/layouts/default.phtml` are already translated and must **not** be wrapped in `T_()` again (double-translation bug).

## Tests

- Unit tests are **PHPUnit 3.x-era** (`PHPUnit_Framework_TestCase`, `PHPUnit_MAIN_METHOD` bootstrap) and depend on `library/USVN/autoload.php`, **which does not exist** — the suite is effectively broken/unmaintained. Per a commit note: "current class autoloading doesn't work for all Test Case files." Do not promise running tests as verification; treat them as historical artifacts unless you also fix the harness.

## Style / workflow

- Code style follows ZF1 conventions (tabs, `{` on own line, class header docblocks). Match surrounding style; do not reformat files wholesale.
- Commits use conventional prefixes (`fix:`, `feat:`, `i18n:`, `cus:`). Recent work: PHP 8 compat fixes and zh_CN translation completeness.
