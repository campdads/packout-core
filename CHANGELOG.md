# Changelog

Every version of the library, newest first. Each plugin pins one.

## 0.13.0

Sites and hubs of different versions.

- **A compatibility test.** `tests/compat/` holds the contract as each released version carried it, from 0.7.0: its schemas and its fixtures. `tests/compat-test.php` checks that what a site of each earlier version sends is accepted by this version, that what each earlier version refused is still refused, and that a hub of each earlier version would refuse of this version's payloads only what later versions added. Each addition is listed there by the version that made it, so a schema or a field nobody declared fails the test.
- `php bin/freeze-contract.php` keeps the contract as it stands under the version `load.php` names, and refuses to write over a version that has a tag.
- `Contract::fault_in()` checks a payload against a schema that is given, and not one this build carries. `Contract::fault()` is unchanged.
- `contract/v1/README.md` says what holds between versions while the contract's number stays 1, and lists what each version added.
- Nothing a site or the hub does changes in this version.

## 0.12.0

Trust and the life of a connection: youth teams, an organization kept in two places, a later version of the terms, a new address, a lost key, a copy of what the hub holds, and what happens to records when an account is deleted.

- **A youth team.** An organization's lead can mark a team as a youth team (`Subteams::set_youth()`). Its page names nobody, it takes no request to join, and a mission of the organization that has such a team on it takes a sign-up only from the organization's own members: `Signups::sign_up()` answers `packout_signup_youth` to anyone else. On a partner site, a Spot such a team cleaned goes to packout.spot with no photograph and no credit line, and un-marking the team sends the Spot again. When the partner's name or description mentions young people, the setup says where a team is marked.
- **An organization that is also on packout.spot.** A partner site's administrator can say that an organization listed on the site is the same as one packout.spot keeps (`Connection::claim()`), by the address of its page there. A lead of the organization there answers. Once accepted, the site's snapshots for the organization, and for the partner as a whole, count its work from the day of the acceptance only, so the earlier work is counted once, by the hub. `Impact::totals()` on the site itself is not changed. The administrator can take it back (`Connection::unclaim()`). On the hub, `Remote::moved()` names the listing an organization's work moved to: the organization then plans and records nothing new there, its page says where its work is kept, and its figures stop at the day before.
- **A later version of the partner terms.** The hub names the day a new version must be accepted by. Until that day the site goes on sending, and the Connection screen asks for the acceptance. After it, the site sends nothing until an administrator accepts.
- **A new address.** A site that is no longer at the address it connected with sends nothing, and its Connection screen asks whether it is a copy or the site itself, moved. `Connection::move()` tells the hub, which asks the site one question at the new address before it takes it, and everything is then sent again.
- **A lost key.** `Connection::lost()` is a site the hub knows that holds no key. `Connection::recover()` asks the hub to email the administrator who connected the site, and `Connection::recover_finish()`, after the link in that email was followed, makes a new key and gives the hub its public half. The hub asks the site one question, signed with the new key, before it takes it. A key the hub refuses is not kept.
- **A copy of what the hub holds.** `Connection::export()` asks the hub for everything it holds from the site, and the Connection screen downloads it as a file.
- **Deleting an account never deletes a record.** The nine record types say `delete_with_user: false`, and `Privacy::leaving()` runs the erasure for the account's address before WordPress deletes it: the person's name comes off the Spots they reported and the records they filed, and those stay. `Privacy` registers the exporter and the eraser on both sites, with `packout_privacy_export` and `packout_privacy_erase` for what a site holds outside the library. Deleting a user from a network's Users screen on a multisite still deletes their records there: WordPress does that without asking.
- **Leaving clears what the hub said.** `Connection::disconnect()` now removes the notices of the connection that ended, as `Connection::forget()` did. Before this, a site that disconnected went on listing them.
- **The Platform overview** has a queue for figures held back, filled through `packout_overview_figures_queue`.
- **The contract** gains `claims.json`, `move.json`, and `recover.json`, and the hub's answer to `GET /me` may carry `terms_by` and `claims`. `PACKOUT_CORE_CONTRACT` stays 1: a hub that does not know these answers `404`, and a site that is told nothing of them shows nothing of them.
- `tests/site/phase-25.php` checks a youth team and an account that is deleted on either site, and on a partner site the terms, a claim, the copy, a move, and a lost key, against a hub played in place.

## 0.11.0

Joint campaigns, and the report of a program and of a campaign as a whole.

- **A joint campaign.** `Joint` lets the host of a public campaign make it one that other organizations take part in. The host invites organizations packout.spot lists, a lead of each accepts or declines, and each then plans and records its own missions under the campaign. Nothing of an organization shows until it accepts. The campaign's page lists who takes part, by name, with what each recorded: no ranking, and no figure as a share of the whole. An organization that leaves, or is taken out, keeps its past missions and its figures, with the day it left, and can file no new mission there. A closed joint campaign takes no more participants, and no participant can file another mission under it. A joint campaign takes no sign-up of its own.
- **In the workspace.** A campaign's pane has a Joint campaign part for its host: make it joint, invite, see who takes part, take one out, close. An invitation waits above an organization's campaigns, with Accept and Decline for its leads. A campaign the organization takes part in is listed with its own missions in it and a way to leave. The mission form offers it, marked. The organization's page and its calendar show it.
- **On packout.spot** the participants are kept with the campaign. A partner site's line on the campaign's page is what that site reported for the campaign, labeled as its own report and dated. `Campaigns::totals()` of a joint campaign is `Joint::whole()`, and the page says how much of it packout.spot counted and how much partner sites reported.
- **On a partner site** the hub keeps who takes part. `Joint::pull()` asks `GET /campaigns` when the stamp in the hub's answer to `GET /changes` is one the site has not seen, and `Joint::learn()` keeps the answer: an invitation to answer, a copy of a campaign an organization takes part in, and the participants of a campaign the site hosts. A copy belongs to no organization of the site, cannot be changed by anybody there, and is never listed back to the hub. Every text the hub sends is cleaned, and an address that is not packout.spot's is dropped. A mission under a copy is listed with `campaign: hub:N`, and the site's figures for the campaign go as a snapshot of `hub:N`. A campaign the site hosts is listed with `joint: open` or `closed`. Accepting, leaving, inviting, and taking out are signed requests that name an organization and no person.
- **Programs.** A joint campaign filed under a program brings the program only the missions of organizations that take part in the program.
- **The contract** gains `campaigns.json`. `listings.json` lets a mission name `hub:N` as its campaign and a campaign say `joint`, and `stats.json` takes `hub:N` as a campaign's identifier. `PACKOUT_CORE_CONTRACT` stays 1: a hub that does not know joint campaigns sends no stamp, and a site that gets none asks nothing.
- **A report of the whole.** `Overview_Report::data( 0, … )` for a program or a campaign covers it from its first day, so its figures are the ones its page shows. `Overview_Report::link()` asks for that, and a campaign's pane in the workspace has the link. The report says whose program or campaign it is. On packout.spot, what partner sites reported is in a block of its own, "Reported by partner sites", in the report of the platform and of a joint campaign, and is in no other figure. A campaign kept on a partner's site has no report on the hub unless it is joint.
- **A change that must reach the hub is queued again.** A listing is queued once in a request. `Outbox::listing_again()` queues it whether or not it was queued earlier, and making a campaign joint or closing it uses that, so the hub learns of it even when the same request had already queued the campaign, or sent it.
- `tests/site/phase-24.php` checks the report on either site, a joint campaign through the workspace's forms on the hub, and on a partner site the invitation, the copy, the mission, the figures, leaving, and hosting, against a hub played in place.

## 0.10.0

The field app on a partner's own site. On the hub the app is as it was, with four faults mended.

- **The app is the site's.** `Field_App::name()`, `title()`, `color()`, and `icons()` give the app the partner's name, the primary color of the palette the plugin gives, and the site's icon. The page's title, the name on a home screen, and the app's manifest carry them. On the hub the app stays Pack-Out Spot. `Field_App::manifest_data()` is the manifest as an array, and `Field_App::config()` is what the page hands its script.
- **A site icon goes by the size it is.** The manifest lists square copies of the site's icon by the width each file has, takes the smallest that is at least 192 pixels and the smallest that is at least 512, and falls back to the app's own icons when the site has none of 144 pixels or more.
- **The hub's Spots come along with a region.** On a connected site that shows packout.spot's layers, the hub's Spots inside a downloaded region are kept on the device with it and drawn in the quieter style the site's map gives them. One opens to be read, with a link to packout.spot, and cannot be reported on, ticked, or recorded against. The phone gets them from the site's own `/hub-layers` and never asks the hub. Outside every downloaded region nothing of the hub is kept.
- **The page names the worker the worker says it is.** `Field_App::tags()` worked out the list of files the app keeps twice in a request and the second time had nothing to list, so the page asked for the worker under one version and the worker gave itself another, and a change to a kept file did not change the address. The list is now made once. A new version of the plugin, or a change to any file the app keeps, gives the worker a new address, and the new worker removes what the old one kept.
- **While a new worker waits, the app still opens with no signal.** A browser lets a new worker take over only when the old one has gone quiet. Chrome does that at once on most updates and, on about one in twenty, when the app is next opened or has been left alone for half a minute. Until then the page is already the new version's and asks the old worker for files only the new one kept. The worker now answers one of the site's own scripts, styles, fonts, or images from what any version of the app kept when there is no network. Before this, the app opened with no signal in that half minute had its page and none of its scripts.
- **The app's address without its last slash** now goes to the address with it. It answered, outside what the worker controls, so the app opened there could not be opened again with no signal.
- **Answers that ask not to be kept also carry a date in the past.** The app's page, its manifest, its worker, every answer `Rest::fresh()` marks, and the search answers send `Expires` beside `Cache-Control`. A host that dates what has no date would otherwise date these.
- **A page cache plugin is told to leave the app's page alone.** The page, the manifest, and the worker define `DONOTCACHEPAGE` before they answer.
- `tests/site/phase-23.php` checks the name, the color, the icons by the size of the file, the manifest, the configuration, and the hub's Spots. It runs on a partner site and on the hub.

## 0.9.1

- **A send that is not going is on the overview.** On a connected partner site, the site's overview says, above the queue, when a send to packout.spot keeps failing, when the connection has stopped and why, and when the copy of the hub's map has gone stale. An administrator gets a link to the Connection screen. Anyone else is told that an administrator can look. Nothing is drawn while all is well, or on a site that is not connected, or on the hub.
- **The partner badge can carry the site's totals.** An option of the block adds a line under it: the Spots cleaned and the verified hours, from the records a coordinator on the site counts publicly. It adds nothing while nothing is counted.
- **A plugin the hub no longer accepts is a critical check.** The Connection screen already said so. Site Health, the setup's last step, and `wp packout doctor` now say it too, with the version the hub accepts.
- On a partner site the overview's Site Setup list sends a missing page to the Pages screen, where it is made, in place of advice written for packout.spot's own pages. Its notice no longer runs under its button, and on a phone the button sits below the text.
- `tests/site/phase-22.php` checks each.

## 0.9.0

What a partner site needs around the application: a setup, its pages, a report on how it stands, commands, privacy, and translation. On the hub the one change is that a remote partner's logo and cover are its own site's to change.

- **Pages from templates.** `templates/pages/` holds fifteen pages as block markup: the map, Report a Spot, Organizations, Calendar, Impact, How it works, Sign up, Sign in, My organizations, My account, and the five parts of the account. `Pages` makes each as an ordinary page, or uses a page the site already has, and remembers which page is which and what the template was when the page was made. A page nobody has edited is offered the newer template. A page somebody has edited is never written to: the site keeps it, makes a draft copy of the new template to compare, or replaces it, and replacing leaves what was written in the page's revisions. An address that is taken gets `pack-out-` in front. The five pages the settings name are those settings, so the two cannot disagree. Each template is also a block pattern.
- **The Pages screen.** `Pages_Screen`, for an administrator: every page, how it stands, its address, and what can be done. A page in the trash can be taken out, and a page that is gone can be made again.
- **The setup.** `Wizard`, for an administrator, in eight steps: what this server can do, the partner, a first organization, connecting to packout.spot, the pages, the map, the people, and how the site stands at the end. Every step can be left for later and opened again, and every step says what it will do before it does it. The connect step explains and links to the Connection screen and does not connect. The dashboard says the setup is there until it is finished or put off. The setup never sets the front page, changes a menu, or touches the theme.
- **How the site stands.** `Health::checks()` is one list, read by the setup, by Site Health, and by the commands: HTTPS, permalinks, WP-Cron, whether the server can sign and can make WebP copies of photos, memory, mail, the partner and its organizations, the pages, the map, the connection, what is waiting to be sent and for how long, the last full send, and the hub's layers. No check makes a request to another server. Site Health's Info tab says how the site stands with the hub and never a key. `Health::test_mail()` sends a test email and keeps only when it was sent and whether it was taken. The filter `packout_health_checks` adds a plugin's own rows.
- **Commands.** `wp packout doctor`, `status`, `sync`, `reconcile`, `notices`, `connect`, `disconnect`, and `pages`. `doctor` and `status` exit with 1 when something needs fixing.
- **Privacy.** `Privacy` suggests text for the site's privacy policy, and adds what the plugin keeps about a person to WordPress's export and erasure of personal data. An erasure removes the profile, private notes, and invitations to the address. Spots, public updates, and impact records stay, with no author and no name. On the hub this comes with phase 11.
- **The partner's logo and cover reach the hub.** `profile.json` gains `images`, which names the logo and the cover by a token. The hub answers with the ones it does not hold, and `Outbox` sends each as the body of a signed request, as photographs go. `Partners::set_logo()`, `remove_logo()`, and `set_cover()` refuse on a partner that keeps its records on its own site.
- **The partner badge.** The block `packout/partner-badge` says the site is a partner of packout.spot and links to its page there. It shows only while the site is connected and listed. The hub does not have it.
- **The map's Contributions menu** lists what the site keeps: on a partner site, Spots. Places and map features are the hub's.
- **Disconnecting asks first.** The form carries a box that must be ticked, checked when the form arrives, so it is asked with or without scripts. A form of the admin that carries `data-pos-confirm` asks its question before it is sent.
- **What is sent.** The Connection screen's list now names Spots, the work on a Spot taken from the hub, and what is never sent of either.
- **Translation.** The library loads its text domain `packout-core` from `languages/plugins/` or else from the `languages/` directory of the plugin that carries it, and gives the admin and editor scripts their translations from the same place. `App::languages()` says where.
- `Outbox::clear()` also forgets what the request had queued, so a row can be queued again after it.
- On a phone the tables of the setup and the Pages screen stack, one page to a block.
- The capability list: a person's data on a partner site is due at phase 8, and `admin.health` is new, for a partner site alone.
- `tests/site/phase-22.php` checks the templates, the pages, the setup, the health list, the badge, the logo and cover against a played hub, and export and erasure. It runs on a partner site.

## 0.8.0

Spots go between a partner site and the hub. Nothing changes on a site that is not connected.

- **A site's Spots go to the hub.** `Exchange` decides, on the site, what is sent of a Spot. A report nobody has reviewed goes with its category, its day, its hazard, and a point rounded to two decimal places, and nothing else. A published Spot goes as the site's own public sees it, with the credit its reporter chose and a list of the photographs that public sees. Anything else goes as removed. A Spot is queued when it is saved and sent at once, and what the hub holds is compared with what it should hold once a night.
- **Photographs follow.** The hub says which listed photographs it lacks, and `Outbox` sends each as the body of a signed request: the site's own public copy, which carries nothing from the camera. A site can keep one Spot's photographs to itself with the filter `packout_exchange_photos`.
- **What the hub says back.** The answer to each Spot says how it stands on the hub, and `GET /changes` says what changed there since. The Spots screen tells a coordinator when the hub hid a Spot or sent it back and why, and when the hub knows of a Spot nearby that may be the same thing.
- **Other sites' Spots on the hub.** `Remote_Spots` keeps them as a type of their own, sealed like every record kept elsewhere. The map draws them with the name of the partner they came through, links to the partner's page for the Spot, and leaves them out of the field app, search, missions, and every total. A Spot's page and a Place's page on the hub name the partner Spots that touch them. `Spots::visible()` merges them in when asked, and `Photos::receive()` makes the hub's copy of a photograph.
- **The hub on a site's map.** `Hub_Layers`: an administrator names a region on the Connection screen, at most 500 kilometres on a side, and the site's map gains three layers under "On packout.spot": the hub's Spots, Places, and map features there. The site's server asks the hub's public routes every fifteen minutes and keeps a cleaned copy; a visitor's browser reads `GET /hub-layers` from the site and never asks the hub. The layers are off until a visitor turns one on, cannot be changed, and leave out the Spots the site itself sent.
- **Taking a Spot of the hub's on.** `Adoptions`: the lead of a mission picks a Spot of the hub's on the Missions screen. The hub is asked at once, and when it agrees the site keeps a copy of what the hub shows everyone and adds it to the mission. The hub is told when the mission starts, when the work is recorded, and when the Spot is let go, and the photographs of the work follow. The copy is never sent as a Spot of the site's, and `Snapshots` leaves the work on it out of every total the site sends, because the hub counts it from its own record.
- **Asking to adopt a Place.** A lead asks, on the Organizations screen, that the organization adopt a Place the hub lists. `Places::request_from_partner()` keeps the request on the hub with the others.
- **Public routes.** `GET /places` lists the Places a site shows, with their outlines and the names of their adopters. `GET /places` and `GET /features` take `bbox`.
- **The contract.** `spots.json`, `adoptions.json`, and `place-requests.json`, with fixtures. A schema can now say that a payload comes in several shapes and name the field that says which, so a Spot awaiting review cannot carry a title. `Geo::bounds()` and `Geo::reaches()` say whether a shape reaches into a box.
- **Hooks.** `packout_spot_notes` adds a line to a Spot's card and page. `packout_mission_takes_spot` lets a plugin say why a mission may not take a Spot. `packout_outbox_send` and `packout_outbox_image` let another part of a plugin send through the queue. `packout_place_request_decided` fires when a request to adopt a Place is answered.
- A row of the queue that was queued again while it was being sent was dropped with the send. It is now kept.
- The capability list: marking a team as a youth team moves to the phase that builds the rest of what a youth team changes.
- `tests/site/phase-20.php` and `tests/site/phase-21.php` play the hub in place and read every request a site makes, for what must not be in any of them.

## 0.7.0

A partner site connects to the hub. Nothing changes on a site that does not.

- **The contract.** `contract/v1/` holds the schema of every payload a partner site sends, fixtures of each, and a README that says the routes, the signature, the order the hub checks a request in, and every answer. `PackOut\Core\Contract::fault()` checks a payload against its schema, on both ends. A field no schema lists is a fault, and the figures of an overview are checked as figures, so no name and no sentence fits.
- **Signing.** `PackOut\Core\Signing`: an Ed25519 key pair, the text a request is signed over, and the four `X-PackOut-*` headers. The secret key is made on the partner site and stays there.
- **The connection.** `PackOut\Core\App\Connection` registers a site, answers the hub's challenge at `packout/v1/hub-challenge`, asks how the connection stands, accepts changed terms, replaces the key, and disconnects. It corrects for a clock that is off when the hub says by how much. A copy of the site at another address sends nothing until its administrator says whether it is a copy or the site moved.
- **What is listed.** `Listings` builds the listing of an organization, a team, a program, a campaign, or a mission from what is public, and nothing else: no member, lead, or sign-up, a mission's landmark only when its lead shared it, and never the exact meeting point. `Snapshots` builds the totals of the partner and of each listing, and the figures of the overview of the partner and of each organization.
- **Sending.** `Outbox` queues a change to something public when it is saved and sends it from WP-Cron, never while a visitor waits. Everything public is sent in full once a night, in batches under one sweep id, so the hub can drop what is no longer there. A send that fails is kept and tried again after a minute, five, fifteen, an hour, and then every six hours. A log keeps ninety days of what was sent and how it was answered, and never what it carried.
- **The Connection screen.** `Connection_Screen`, under Pack-Out Spot on a partner site, for an administrator: what is sent and what never is, the terms, the steps before the site is listed, how the connection stands, what the hub has told the site, what is waiting and why, the log, Send now, Send everything again, Replace the key, and Disconnect.
- **Records kept elsewhere.** `Remote` marks a record on the hub that is a copy of one on a partner's site. Such a record cannot be saved, renamed, given or stripped of a field, trashed, or deleted except by the code that takes what the site sent. Its page sends people to the partner's site to join and sign up, its totals are the ones the site reported and say so, and the lists a coordinator manages leave it out. `Teams::all()` is the organizations kept here; `Teams::all( true )` adds the ones that are not.
- **The overview of a partner's site** on the hub shows the figures the site sent, says whose they are, and opens no list and no report, because the records are on the site.
- The overview's notice named whichever record the page was about, not the partner. An organization's reported totals were looked for under the wrong record. Both are corrected.
- `tests/site/phase-19.php` plays the hub in place and checks a site's whole side of the connection, with the screen. `tests/contract-test.php` and `tests/signing-test.php` check the contract and the signature without WordPress.

## 0.6.2

Nothing in the application changes.

- One of the acceptance checks in `tests/site/phase-15.php` read a line of the export with `str_getcsv()` and left the escape character to its default. PHP 8.4 says that default will change, and a run on PHP 8.4 fails on anything PHP says. The check names the character.

## 0.6.1

Three places where the time taken grew much faster than the site, found by timing a site with ten thousand Spots, fifty missions of ten Spots each, and two hundred records. Nothing else changes.

- The calendar asked the database one question that joined every row a mission keeps to every other. A mission with ten Spots took over a minute to find, and one with thirty would not have come back. It now asks three small questions, one for each way a record’s days can be stored, and answers in a few hundredths of a second.
- The list of Spots for the map asked twice for every Spot, about its photos and its records. It asks twice for the whole list. A thousand Spots took over a second and take a tenth.
- The overview looked through every record for every cleaned Spot, and for every organization. Each of those thirteen lookups is now built once for the question. A current-state snapshot took a second and takes half, a period snapshot took nearly two and takes a little over half, and neither grows with the square of the site any more. The screen reads a stored snapshot, as before.
- `GET /spots` with a box asks the database for the Spots in or beside the box first. It used to take the thousand most recently changed Spots of the whole site and then keep those in the box, so on a site with more than a thousand the field app’s download of a region and the map were missing Spots. What a viewer may see, and where a Spot with an approximate location is shown, are decided as before.
- The answer of `GET /spots` carries `more`, true when it was full. The map then asks for the area on view each time it comes to rest and adds the Spots it had not been given, so an area looked at closely is complete. `Spots::ANSWER` is the thousand, `Spots::more()` says whether the last list was full, and the filter `packout_spots_answer` lowers the number.

No index table was added: with these corrections the map answers in about a tenth of a second and the calendar in under two hundredths at ten thousand Spots.

## 0.6.0

The application runs on a partner site. Nothing changes on the hub.

- A partner site is one partner. `Partners::site()` is that partner’s record and `Partners::make_site()` makes it when the plugin is installed: named after the site, led by the administrator who installed it, and not listed until the site says so. Every organization on the site is that partner’s without being told, so `Partners::of_team()` and `Partners::organization_ids()` answer without stored links.
- What only the hub does is refused on a partner site with `App::hub_only()`: proposing, approving, declining, suspending, and lifting a partner; an organization asking to join a partner or leaving one; adopting a place; and writing a map feature. Places and map features have no public address there, and the Places and Partners blocks are not registered.
- The admin overview of a partner site is titled “On this site” and leaves out the hub’s counts of partners, places, and adoptions. The partner’s own overview is the site’s.
- `assets/css/ui.css` styles a badge, a notice, and an empty state from their plain markup, for a plugin with no component library. The map’s components carry the same classes, and a notice’s plain form keeps its description.
- `Report_Form::entry_note()` gives the sentence a coordinator reads about the form’s own record of a report, or nothing when the form keeps none.
- `Ui::wrapper()` gives a block’s outer element its attributes. A workspace, a calendar, or a profile drawn outside a block, on a screen or in a test, no longer raises the WordPress 6.8 warning about block supports.
- `Post_Access::can_read()` takes a record or its ID.
- `tests/site/` holds the acceptance checks of the application: seventeen files that run inside WordPress, on either side. `bin/export.php` copies them to the plugin’s `tests/core/`, so both plugins run the same checks. A check of what only the hub does is left out on a partner site, and the run says so.

| Interface | Change |
| --- | --- |
| `Report_Form` | `entry_note()` is new |

## 0.5.2

- A cleanup’s one-line summary says again whether its bags and its weight were measured or self-reported. Since 0.4.0 it left the word out and raised a PHP warning.

0.5.1 was tagged with the same correction written in a way the coding standard refuses. Its checks failed and it was not released.

## 0.5.0

The application no longer names Vectra Toolkit, Vectra UI, or a theme. What it took from them it now asks its plugin for, through the adapters. A plugin that carried 0.4.0 has to change with it: five of the interfaces grew, and the names in the markup, the styles, and the scripts changed.

- `App::register()` and `App::install()` register and install the application, so a plugin does not list its parts. `App::kernel()` is where the application reaches the adapters, `App::is_hub()` says which side it is on, and `App::base()` gives the base of a public path.
- `App\Post_Access` decides who may read a record through the `Access` adapter. Columns, roles, and the settings option come from `Storage`, `Roles`, and `Settings`. Mail goes through `Mailer`.
- `App\Ui` is the one place the application gets its interface pieces: components, the list runtime, icons, the drawer, and the nine colors.
- The drawer is the library’s own: `assets/js/drawer.js`, `window.packoutDrawer.bind()`, and the classes `packout-drawer…`.
- A component is `.packout-ui[data-packout-ui]` with its plain form inside. Where the plugin’s script defines `window.packoutUi.mount()`, it is called with the node. A list is drawn by `window.packoutLists.render( mount, config )` where the plugin defines it, and stays the plain rows where it does not.
- The nine colors are the custom properties `--packout-primary`, `-secondary`, `-accent`, `-accent-dark`, `-surface`, `-subtle`, `-line`, `-muted`, and `-ink`, printed once from `Palette`.
- The map editor’s icon search is a plain search field.
- `packout_admin_menu` lets a plugin add a screen of its own to the menu, after Organizations or after Impact. `packout_page_changed` says a record’s page changed, for a plugin whose host keeps a page cache.
- `tests/standalone-test.php` reads every file a plugin carries and fails on a name that belongs to Vectra, a theme, or a host.

The interfaces that grew:

| Interface | Change |
| --- | --- |
| `Screens` | `assets()` names the scripts and styles the components need |
| `Lists` | `render()` is gone. `assets()` names the runtime’s scripts and styles, and `saved()` gives the columns a person kept for a list. |
| `Icons` | `svg()` takes the classes for the icon, not a size |
| `Report_Form` | `render()` takes `before_submit`. `selectors()` says where the location and photograph fields are, `photo_limit()` how many photographs it takes, `status()` whether it is set up, and `settings()` its rows on the Settings screen. |
| `Settings` | `option()` names the option the settings are kept in |

The Places and Partners screens, the hub’s theme filter, and its host’s cache stay in the hub’s plugin.

## 0.4.0

The application moves in. Until now the library held rules and the hub’s plugin held the application: the records, the pages, the workspace, the admin screens, the map, the calendar, the report, and the field app. They are here now, unchanged in what they do, so that both plugins can run them.

- `src/app/`: sixty classes in `PackOut\Core\App`, with the text domain `packout-core`. Loading the library defines them and registers nothing. A plugin registers the parts it runs.
- `assets/` and `templates/`: the scripts, styles, images, and the impact report’s template. Their addresses are under the library’s directory in the plugin that carries it, and their version is the library’s.
- `PACKOUT_CORE_FILE` names `load.php`, for `plugins_url()`.
- `Build::named_files()` lists every file the code of a plugin and of its copy of the library names, so a plugin’s checks can refuse a package that lacks one and ask a live site for each.
- `bin/export.php` writes only the files that changed, removes only the ones that left, and stops unless the copy then holds exactly the library’s files. A sync client that keeps a deleted file as a second copy no longer changes the fingerprint.
- `bin/make-icons.php` draws the field app’s icons.

The application still names Vectra Toolkit, Vectra UI, and the Astra theme where the hub used them, and three of the hub’s own screens. A site without them cannot run it yet. The next versions replace each of those with an adapter.

Four things changed on the way, none of them in what a person sees. Parameters named with a PHP keyword were renamed. `get_post_meta()` read for every value says so. A redirect filter that ignored its second argument no longer asks for it. The coding standard’s limit on a list’s length is 2,000 here, the highest any query asks for, and a template’s variables are not held to the rule for globals.

## 0.3.0

The rules of the calendar. A plugin finds the events and draws them, and asks here what a day, a week, and a file of events are.

- `PackOut\Core\Schedule`: when a mission or a campaign starts and ends. A day with no time is all-day. A time is local to the record’s time zone, and the instant is kept. A time with no day, an end before the start, and a zone PHP does not know are refused.
- `PackOut\Core\Calendar`: the six forms (Day, Week, Month, Agenda, Upcoming, Past), the days each covers, the step to the next and the one before, the 62-day limit on one request, which events fall on a day, and where an event sits on a day’s hours beside the ones it overlaps.
- `PackOut\Core\Ical`: a calendar file a calendar application can subscribe to, with lines folded and text escaped as RFC 5545 asks, all-day events as dates with the end day exclusive, and timed events in the record’s own time zone with that zone defined in the file.
- `PackOut\Core\Repeat`: the days a repeated mission’s copies fall on, every 1 to 12 weeks or on the same day each month, for 1 to 12 copies, and what a copy may carry over.

## 0.2.0

The rules of the two records the partner build adds. The plugins store the records and ask here before they act.

- `PackOut\Core\Partner`: a partner’s five states, what each allows, the steps between them, and who leads it.
- `PackOut\Core\Program`: who owns a program, which organizations take part, what may be filed under it, and the rule that a mission follows its campaign.

## 0.1.0

The first version. It holds what both plugins need before any of the application moves in.

- The kernel and the eleven adapter interfaces a plugin implements.
- The capability list: 123 acts, with who may take each and which plugin has it.
- The capability map and the parity check both plugins run.
- The release keys and the check that a package was signed by one.
- The update client the partner plugin uses to take new versions from the catalog on packout.spot.
- The build check: a fingerprint of the copy a plugin carries, compared with that plugin’s `packout-core.lock`.
