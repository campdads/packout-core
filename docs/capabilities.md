# Capabilities

Every act a person can take in Pack-Out Spot, who may take it, and which plugin has it. This page is written from `capabilities.php` by `bin/capabilities-doc.php`. Change that file, not this one.

The hub is the plugin that runs packout.spot. A partner site runs the partner plugin. **Surface** says which of them has the act: `both`, `hub`, or `partner`. **Since** is the phase of the partner build that gave each plugin the act; 0 means the hub had it before the build began.

The parity test in each plugin fails when a row that plugin should have has no handler, when a plugin has a handler for an act that belongs to the other alone, when a handler names an id that is not here, or when a row only one plugin has gives no reason.

## Spots

| Id | Act | Who | Surface | Since: hub | Since: partner | Reason or note |
| --- | --- | --- | --- | --- | --- | --- |
| `spot.report.form` | Report a Spot from the website form | Visitor | both | 0 | 5 |  |
| `spot.report.app` | Report a Spot from the field app, with photographs | Reporter | both | 0 | 9 |  |
| `spot.edit.own` | Change one’s own report while it waits for review | Reporter | both | 0 | 5 |  |
| `spot.credit` | Choose who is credited on one’s own Spot | Reporter | both | 0 | 5 |  |
| `spot.note` | Keep private notes on a Spot, and show one | Reporter | both | 0 | 5 |  |
| `spot.update` | Share an update on a Spot’s page | Reporter | both | 0 | 5 |  |
| `spot.review` | Publish, keep private, or return a report | Coordinator | both | 0 | 5 |  |
| `spot.coarsen` | Set public precision | Coordinator | both | 0 | 5 |  |
| `spot.hazard` | Mark a hazard value | Coordinator | both | 0 | 5 |  |
| `spot.clear` | Record a clearance | Coordinator | both | 0 | 5 |  |
| `spot.duplicate` | Link a Spot as a duplicate of another | Coordinator | both | 0 | 5 |  |
| `spot.photos.hide` | Hide or show a Spot’s photographs | Coordinator | both | 0 | 5 |  |
| `spot.claim` | “I cleaned this Spot” from its page | Visitor | both | 0 | 5 |  |
| `spot.claim.confirm` | Confirm or dismiss a cleanup report | Coordinator | both | 0 | 5 |  |
| `spot.return` | File a return visit: still clear, or it’s back | Reporter | both | 0 | 5 |  |
| `spot.exact` | See the exact pin of a reviewed Spot | Lead | both | 0 | 5 |  |
| `spot.remote.hide` | Hide, return, or coarsen a partner’s Spot | Coordinator | hub | 7 |  | Only the hub holds other sites’ Spots |
| `spot.remote.hold` | Hold a partner’s Spots for review | Coordinator | hub | 7 |  | Only the hub holds other sites’ Spots |
| `spot.hub.adopt` | Take a hub Spot into a mission | Lead, planner | partner |  | 7 | A hub mission includes a hub Spot directly |
| `spot.update.remove` | Remove one’s own update or note; a coordinator removes any | Reporter, coordinator | both | 0 | 5 |  |
| `spot.claim.followup` | Add a photograph to one’s cleanup report and choose its credit | Whoever filed it | both | 0 | 5 |  |
| `spot.stage` | Set a Spot’s stage by hand, override its credit, or remove it | Coordinator | both | 0 | 5 |  |

## Map and search

| Id | Act | Who | Surface | Since: hub | Since: partner | Reason or note |
| --- | --- | --- | --- | --- | --- | --- |
| `map.view` | See the public map, filter, and open a card | Visitor | both | 0 | 5 |  |
| `map.search` | Search the map | Visitor | both | 0 | 5 |  |
| `map.filter.partner` | Filter the map to one partner | Visitor | hub | 3 |  | A partner site is one partner |
| `map.layers.hub` | Show hub Spots, Places, and features as layers | Administrator | partner |  | 7 | The hub has them as its own records |
| `feature.author` | Add, change, or import a map feature | Reporter | hub | 0 |  | Map features are the hub’s shared layer |
| `feature.suggest` | Suggest a change to a feature | Reporter | hub | 0 |  | As above |
| `place.outline` | Outline and list a Place | Coordinator | hub | 0 |  | Places are the hub’s shared geography |
| `place.adopt.request` | Ask to adopt a Place | Member | both | 0 | 7 | On a partner site a lead of the organization asks the hub |
| `place.adopt.answer` | Accept or decline an adoption request | Lead; coordinator for a partner’s request | hub | 0 |  | Places are the hub’s |
| `place.adopt.record` | Record or end an adoption directly, attach Spots to a Place, and set its sponsors | Coordinator | hub | 0 |  | Places are the hub’s shared geography |
| `feature.update` | Add a photograph or an update to a map feature’s page | Reporter | hub | 0 |  | Map features are the hub’s shared layer |

## Organizations and teams

| Id | Act | Who | Surface | Since: hub | Since: partner | Reason or note |
| --- | --- | --- | --- | --- | --- | --- |
| `org.propose` | Propose an organization | Reporter | both | 0 | 5 |  |
| `org.approve` | Approve or decline an organization | Coordinator | both | 0 | 5 |  |
| `org.profile` | Change profile, address, logo, cover, listing, join policy | Lead | both | 0 | 5 |  |
| `org.planning` | Let members plan | Lead | both | 0 | 5 |  |
| `org.join` | Join, ask to join, withdraw, leave | Reporter | both | 0 | 5 |  |
| `org.join.answer` | Accept or decline a request to join | Lead | both | 0 | 5 |  |
| `org.invite` | Invite by email | Lead | both | 0 | 5 |  |
| `org.member.add` | Add a person directly | Coordinator | both | 0 | 5 |  |
| `org.member.remove` | Remove a member | Lead | both | 0 | 5 |  |
| `org.colead` | Name or remove a co-lead | Lead, not a co-lead | both | 0 | 5 |  |
| `org.handover` | Hand the organization to another member | Lead, not a co-lead | both | 0 | 5 |  |
| `org.message` | Write to the organization, a team, or a mission’s people | Lead, team lead | both | 0 | 5 |  |
| `org.resource` | Add or remove a resource at a level one leads | Lead, team lead, planner | both | 0 | 5 |  |
| `org.sponsor` | Add, change, or remove a sponsor | Lead | both | 0 | 5 |  |
| `org.contact.share` | Share a phone or address with one’s leads | Member | both | 0 | 5 |  |
| `team.manage` | Create, rename, remove teams and name their leads | Lead | both | 0 | 5 |  |
| `team.run` | Add and remove a team’s members, set its page, logo, cover, join rule | Team lead | both | 0 | 5 |  |
| `team.join` | Join, ask to join, or leave a team | Member | both | 0 | 5 |  |
| `team.youth` | Mark a team as a youth team | Lead | both | 11 | 11 |  |
| `org.overview` | Open the organization’s overview | Lead, coordinator | both | 3 | 5 |  |
| `org.manage` | Create, change, or remove an organization directly | Coordinator | both | 0 | 5 |  |
| `org.invite.accept` | Accept an invitation | Whoever was invited | both | 0 | 5 |  |
| `org.invite.revoke` | Take an invitation back | Lead, coordinator | both | 0 | 5 |  |
| `org.answer.link` | Answer a request from the one-time link in its email, without signing in | Whoever the email was sent to | both | 0 | 5 |  |
| `org.mail.quiet` | Stop an organization’s email from the link in a message | Member | both | 0 | 5 |  |
| `org.message.read` | Read the organization’s messages | Member | both | 0 | 5 |  |
| `team.join.answer` | Accept or decline a request to join a team | Team lead | both | 0 | 5 |  |
| `team.colead` | Name or remove a team’s co-lead | Lead, team lead | both | 0 | 5 |  |

## Partners and programs

| Id | Act | Who | Surface | Since: hub | Since: partner | Reason or note |
| --- | --- | --- | --- | --- | --- | --- |
| `partner.propose` | Propose a partner | A lead of an active organization | hub | 3 |  | A partner site is one partner, made by its wizard |
| `partner.approve` | Approve, decline, suspend, or lift a partner | Coordinator with `packout_partner` | hub | 3 |  | As above |
| `partner.profile` | Change the partner’s profile, address, logo, cover, listing | Partner lead | both | 3 | 8 |  |
| `partner.colead` | Name or remove a partner co-lead; hand over | Partner lead, not a co-lead | both | 3 | 8 |  |
| `partner.org.create` | Create an organization under the partner | Partner lead | both | 3 | 5 |  |
| `partner.org.claim` | Ask an organization to join, answer, or leave | Lead, partner lead | hub | 3 |  | On a partner site every organization is the partner’s |
| `partner.message` | Write to everyone in the partner’s organizations, or their leads | Partner lead | both | 3 | 5 |  |
| `partner.workspace` | Use the Partner workspace | Partner lead | both | 3 | 5 |  |
| `partner.overview` | Open the partner’s overview | Partner lead, coordinator | both | 3 | 5 | On a partner site it is the site overview |
| `partner.directory` | Browse the directory of partners | Visitor | hub | 3 |  | A partner site is one partner |
| `program.manage` | Open, change, close, or remove a program | The owner’s lead | both | 3 | 5 |  |
| `program.file` | File a campaign or mission under a program | Lead, planner | both | 3 | 5 |  |
| `program.report` | Download a program’s impact report | Whoever may change it | both | 10 | 10 |  |

## Campaigns and missions

| Id | Act | Who | Surface | Since: hub | Since: partner | Reason or note |
| --- | --- | --- | --- | --- | --- | --- |
| `campaign.plan` | Plan, change, or remove a campaign | Lead, planner | both | 0 | 5 |  |
| `campaign.report` | Download a campaign’s impact report | Whoever may change it | both | 10 | 10 |  |
| `campaign.joint.host` | Open a joint campaign and invite participants | Lead, partner lead | both | 10 | 10 |  |
| `campaign.joint.answer` | Accept, decline, or leave a joint campaign | Lead, partner lead | both | 10 | 10 |  |
| `mission.plan` | Plan, change, or remove a mission | Lead, team lead for an assigned mission, planner | both | 0 | 5 |  |
| `mission.assign` | Change a mission’s campaign, program, teams, sponsors | Lead, planner | both | 0 | 5 |  |
| `mission.start` | Start a mission | Whoever may change it | both | 0 | 5 |  |
| `mission.complete` | Complete a mission and settle each Spot’s outcome | Whoever may change it | both | 0 | 5 |  |
| `mission.calloff` | Call a mission off | Whoever may change it | both | 0 | 5 |  |
| `mission.repeat` | Repeat a mission | Whoever may change it | both | 4 | 5 |  |
| `mission.work` | See a mission’s exact pins and work its checklist | Its crew | both | 0 | 5 |  |
| `event.signup` | Sign up for a public campaign or mission, and take it back | Reporter | both | 0 | 5 |  |
| `calendar.view` | See a calendar, switch its form, subscribe to its feed | Visitor | both | 4 | 5 |  |
| `mission.decide` | Choose between a crew phone’s copy of a mission and the site’s | Whoever may change it | both | 0 | 9 |  |

## Impact

| Id | Act | Who | Surface | Since: hub | Since: partner | Reason or note |
| --- | --- | --- | --- | --- | --- | --- |
| `impact.record` | Record the result of work on a Spot | Whoever did the work | both | 0 | 5 |  |
| `impact.verify` | Verify an impact record | `packout_impact` | both | 0 | 5 |  |
| `impact.count` | Count a record in public totals | `packout_impact` and `packout_team` together | both | 0 | 5 |  |
| `impact.totals` | See an organization’s, partner’s, program’s, or campaign’s totals | Its leads; anyone, for what is public | both | 0 | 5 |  |
| `impact.own` | See one’s own impact and reported Spots | Reporter | both | 0 | 5 |  |
| `impact.partner.flag` | Act on a flag raised on a partner’s figures | Coordinator | hub | 11 |  | Only the hub receives snapshots |
| `impact.change` | Change or remove an impact record, and add photographs to it | Whoever recorded it, `packout_impact` | both | 0 | 5 |  |

## Accounts

| Id | Act | Who | Surface | Since: hub | Since: partner | Reason or note |
| --- | --- | --- | --- | --- | --- | --- |
| `account.signup` | Sign up and confirm by email | Visitor | both | 0 | 5 |  |
| `account.signin` | Sign in, sign out, reset a password | Visitor | both | 0 | 5 |  |
| `account.profile` | Change personal info, email preferences, sign-in details | Reporter | both | 0 | 5 |  |
| `account.join.network` | Join Pack-Out Spot with an existing network account | A CampDads account holder | hub | 0 |  | The CampDads network is the hub’s |
| `account.data` | Export or erase a person’s data through WordPress’s privacy tools | Administrator, at the person’s request | both | 11 | 8 |  |

## Admin

| Id | Act | Who | Surface | Since: hub | Since: partner | Reason or note |
| --- | --- | --- | --- | --- | --- | --- |
| `admin.overview` | Open the site’s overview | Coordinator | both | 0 | 5 |  |
| `admin.spots` | The Spots screen | Coordinator | both | 0 | 5 |  |
| `admin.missions` | The Missions screen | `packout_lead` | both | 0 | 5 |  |
| `admin.organizations` | The Organizations screen | `packout_screens` | both | 0 | 5 |  |
| `admin.programs` | The Programs screen | `packout_lead` | both | 3 | 5 |  |
| `admin.people` | The People screen | Coordinator | both | 0 | 5 |  |
| `admin.invitations` | The Invitations screen, with resend and timeline | Coordinator | both | 0 | 5 |  |
| `admin.impact` | The Impact screen | `packout_impact` | both | 0 | 5 |  |
| `admin.export` | Download Spots as GeoJSON, impact and people as CSV | `packout_export` | both | 0 | 5 |  |
| `admin.report` | Export the impact report from an overview | Whoever may open that overview | both | 0 | 5 |  |
| `admin.settings` | The Settings screen | Coordinator | both | 0 | 5 |  |
| `admin.places` | The Places screen | Coordinator | hub | 0 |  | Places are the hub’s |
| `admin.partners` | The Partners screen | `packout_partner` | hub | 3 |  | A partner site is one partner |
| `admin.connection` | Connect, see the connection, send now, replace the key, disconnect | Administrator | partner |  | 6 | The hub is what a site connects to |
| `admin.pages` | Create pages from templates and keep them current | Administrator | partner |  | 8 | The hub’s pages are built and deployed from `content/` |
| `admin.wizard` | The setup wizard | Administrator | partner |  | 8 | The hub is set up by `deploy/setup.php` |
| `admin.health` | See how the site and what it sends stand, in Site Health and from the command line | Administrator | partner |  | 8 | The hub is watched by `deploy/live-check.cjs` after each release |
| `admin.addresses` | Choose the base of each public path | Administrator | partner |  | 5 | The hub’s addresses are fixed |
| `admin.regions` | Publish offline map regions | Coordinator | both | 0 | 9 |  |
| `admin.people.edit` | Change a person’s profile, contact details, mail choices, and memberships | Coordinator | both | 0 | 5 |  |

## Field app

| Id | Act | Who | Surface | Since: hub | Since: partner | Reason or note |
| --- | --- | --- | --- | --- | --- | --- |
| `field.capture` | Save a Spot offline and sync it | Reporter | both | 0 | 9 |  |
| `field.region` | Download a map region | Reporter | both | 0 | 9 |  |
| `field.mission` | Work a mission offline: checklist and outcomes | Its crew | both | 0 | 9 |  |
| `field.cleaned` | Mark one’s own Spot cleaned | Reporter | both | 0 | 9 |  |

## Counts

124 rows. 100 are for both plugins. 17 are the hub’s alone, and 7 are the partner plugin’s alone.
