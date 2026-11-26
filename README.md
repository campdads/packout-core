# Pack-Out Spot library

The code that the two Pack-Out Spot plugins share.

[Pack-Out Spot](https://packout.spot) is community stewardship for places that need attention: people report a Spot, organizations plan missions to clean it, and the result is recorded. Two WordPress plugins run it:

| Plugin | Runs on | Repository |
| --- | --- | --- |
| Pack-Out Spot | packout.spot, the hub | Private |
| Pack-Out Spot Partner | A partner’s own WordPress site | [`campdads/packout-partner`](https://github.com/campdads/packout-partner) |

This library is not a plugin and is never installed on its own. Each plugin carries a copy in `lib/packout-core/`, at a version it pins.

## What is in it

| Part | File | Does |
| --- | --- | --- |
| Kernel | `src/class-kernel.php` | Takes the eleven adapters a plugin supplies and hands them to the rest of the library |
| Adapters | `src/contracts/` | The interfaces a plugin implements: read access, lists, the report form, roles, storage, screens, palette, icons, settings, mail, and the site |
| Capability list | `capabilities.php` | Every act a person can take, who may take it, and which plugin has it. [Read it as a page](docs/capabilities.md). |
| Capability map | `src/class-capability-map.php` | Where a plugin names the code that performs each act |
| Parity check | `src/class-parity.php` | Compares the list with a plugin’s handlers. Both plugins run it in their tests. |
| Release keys | `src/class-release.php` | The public keys a release is signed with, and the check that a package was signed by one |
| Build check | `src/class-build.php` | Fingerprints the copy a plugin carries and compares it with that plugin’s `packout-core.lock` |
| Update client | `src/class-updates.php` | Tells WordPress when the catalog on packout.spot has a newer version, and refuses a package whose checksum or signature does not verify |
| Partner | `src/class-partner.php` | A partner’s states, what each allows, the steps between them, and who leads it |
| Program | `src/class-program.php` | Who owns a program, which organizations take part, and what may be filed under it |
| Schedule | `src/class-schedule.php` | When a mission or a campaign starts and ends: all-day or timed, in the record’s own time zone |
| Calendar | `src/class-calendar.php` | The six forms of the calendar, the days each covers, and where an event sits on a day |
| Calendar file | `src/class-ical.php` | Writes events as an iCalendar file a calendar application can subscribe to |
| Repeat | `src/class-repeat.php` | The days the copies of a repeated mission fall on, and what a copy may carry over |
| Application | `src/app/`, `assets/`, `templates/` | The records and what may be done with them, the public pages, the workspace, the admin screens, the map, the calendar, the impact report, and the field app. In `PackOut\Core\App`. Everything it needs from outside WordPress it asks its plugin for, through the adapters. |
| Contract | `contract/v1/` | The payloads a partner site and the hub exchange. Empty until the partner interface is built. |

The library registers nothing with WordPress when it is loaded. A plugin requires `load.php`, builds a `PackOut\Core\Kernel` with its adapters, calls `register()`, and then registers the application.

```php
require_once __DIR__ . '/lib/packout-core/load.php';

$kernel = new PackOut\Core\Kernel(
	array(
		'access'      => new My_Access(),
		'lists'       => new My_Lists(),
		'report_form' => new My_Report_Form(),
		'roles'       => new My_Roles(),
		'storage'     => new My_Storage(),
		'screens'     => new My_Screens(),
		'palette'     => new My_Palette(),
		'icons'       => new My_Icons(),
		'settings'    => new My_Settings(),
		'mailer'      => new My_Mailer(),
		'site'        => new My_Site(),
	)
);
$kernel->register();

PackOut\Core\App\App::register();
```

`App::register()` registers the application: the record types, the pages, the blocks, the routes, and the admin screens. `App::install()` creates the roles, the category terms, and the invitation tables, and writes the rewrite rules again. A plugin calls it on `init`, after `App::register()`, when the version it last installed is behind.

The interface pieces come through the adapters too. `Screens` and `Lists` name script and style handles the plugin has registered. A component is printed as `.packout-ui[data-packout-ui]` with its plain form inside, and a plugin whose script defines `window.packoutUi.mount( node )` may replace it. A list is plain rows until a script defines `window.packoutLists.render( mount, config )`. The nine colors of `Palette` are printed as `--packout-primary` and the eight after it.

## Requirements

PHP 8.2 or later, with the sodium extension. WordPress 6.8 or later.

## Parity

The partner plugin is meant to do what the hub does, apart from a short list of acts that belong to one side for a stated reason. That promise is `capabilities.php` and the check that reads it.

- A plugin names a handler where it registers the code for an act: `Capability_Map::handle( 'mission.complete', array( Missions::class, 'complete' ) )`.
- `Parity::check_site()` fails when a row the plugin should have by now has no handler, when the plugin has a handler for an act that belongs to the other side alone, when a handler names an id that is not in the list, or when a handler cannot be called.
- A new act is added to `capabilities.php` first.

## Releases and signatures

Pack-Out Spot’s plugins are published from the catalog on packout.spot. Each package is signed with an Ed25519 key. The signature covers the bytes of the package file. `PackOut\Core\Release::PUBLIC_KEYS` lists the public keys a site accepts, and the update client refuses a package that none of them signed, or that has no signature.

## Working on it

```sh
composer install          # the coding-standard tools
php tests/run.php         # unit tests. PHP alone; no WordPress needed.
composer lint             # WordPress coding standards
composer check            # versions, the capability page, tests, and standards
```

| Task | Command |
| --- | --- |
| Add or change an act | Edit `capabilities.php`, then `php bin/capabilities-doc.php` |
| Copy the library into a plugin | `php bin/export.php <plugin directory>`, from a clean checkout of a tagged version |
| Check the version is the same everywhere | `php bin/check-versions.php` |
| Change a schema or a fixture of the contract | Edit `contract/v1/`, then `php bin/freeze-contract.php`, and declare what was added in `tests/compat-test.php` |

A plugin’s `packout-core.lock` records the version, the commit, and a fingerprint of the copy it carries. Each plugin’s checks compute the fingerprint again, so a copy that was edited in place fails.

### Checks that need a site

`tests/site/` holds the acceptance checks of the application: what a person can do with Spots, organizations, missions, impact, programs, and the calendar. They run inside WordPress with `wp eval-file`, against a site where one of the plugins is active, and they create and delete their own records.

`bin/export.php` copies them to the plugin’s `tests/core/`, so the hub and a partner site run the same checks. Each file asks `App::is_hub()` once. A check of what only packout.spot does is left out on a partner site, and the run prints a line that starts with `--` and says what was left out. The partner plugin’s own tests check what a partner site does instead.

Change a check here, never in a plugin’s copy. The copy is written again by the next export.

## Versions

`load.php` holds the version. [CHANGELOG.md](CHANGELOG.md) lists what changed. A version tag makes a GitHub release of the files a plugin carries.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

Three files in `assets/` come from other projects, unmodified, each under its own license:

| File | Project | License |
| --- | --- | --- |
| `assets/lib/maplibre-gl.js`, `assets/lib/maplibre-gl.css` | MapLibre GL JS 5.24.1 | BSD-3-Clause, in `assets/lib/maplibre-gl.LICENSE.txt` |
| `assets/lib/pmtiles.js` | PMTiles 4.5.0 | BSD-3-Clause |
| `assets/js/chart.umd.min.js` | Chart.js 4.6.2 | MIT, in `assets/js/chart.js.LICENSE.md` |
