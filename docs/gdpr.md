# Data Protection (GDPR)

The Orchestra plugins are mainly used by German orchestras, usually registered associations
(Vereine). The GDPR (DSGVO), the BDSG and the TDDDG (formerly TTDSG) apply. Data protection is a
fundamental requirement of the suite, not an add-on: every feature that touches personal data
follows this document.

This document describes what the software does and what it leaves to the orchestra. It is not
legal advice; the orchestra remains responsible for its processing and should have its privacy
policy checked.

---

## Principles

- **Privacy by design and by default (Art. 25).** Only what a feature needs is stored; nothing is
  public; the default is the most restrictive choice that still works.
- **Data minimisation (Art. 5(1)(c)).** No tracking, no analytics, no IP addresses, no download
  logs, no "who uploaded" or "who viewed". Data without a use is not stored (e.g.
  orchestra-library 0.10 dropped who uploaded a file).
- **Storage limitation (Art. 5(1)(e)).** Sensitive profile data is deleted when it is no longer
  needed; texts of emails once sent; the email log after 12 months.
- **Transparency (Art. 12–14).** Every plugin suggests a text for the privacy policy; new members
  are told who added them and where to see their data.
- **Rights of the people (Art. 15–21)** work through WordPress's privacy tools for every plugin.
- **Need to know.** Capabilities decide who sees what ([capabilities.md](capabilities.md)); the
  [visibility matrix](#who-sees-what) lists it.

---

## Responsibilities

The orchestra is the **controller** (Art. 4(7)); the plugins are its tool. The plugin developers
receive no data.

The orchestra has to:

1. **Publish a privacy policy** (Settings → Privacy). The plugins add their suggested texts to the
   policy guide (Settings → Privacy → Policy guide); copy what applies and add the orchestra's
   name, contact, legal bases, the hosting and email provider and the rights of the people.
   The Orchestra login page, the start page's login form and the profile page link to it.
2. **Sign data processing agreements** (AVV, Art. 28) with the hosting provider and, if used, the
   SMTP or email service.
3. **Keep a record of processing activities** (Art. 30). The [record below](#record-of-processing)
   can be copied into it.
4. **Choose the profile fields** with care (Settings of orchestra-members): only what projects
   need. Fields that reveal health or religion are special categories ([Art. 9](#special-categories-art-9)).
5. **Run the site securely** ([operator checklist](#operator-checklist)).
6. **Answer requests** of members (access, erasure) with the WordPress tools ([rights](#rights-of-the-people)).
7. **Have a legal basis in the association:** the statutes or a resolution of the members' meeting
   should cover the membership data and the project history, and new members get the privacy
   notice when they join. A data protection officer is only required when at least 20 people
   regularly process personal data (§38 BDSG), which is rare for an orchestra.
8. **Publish an Impressum** (§5 DDG) if the website is public.
9. **Know what to do after a data breach** ([below](#data-breaches)).

The plugins link to the privacy policy only once a page is published and selected under
Settings → Privacy.

---

## Record of processing

Legal bases used below:

- **(b)** Art. 6(1)(b): membership and taking part in projects (the association's statutes and
  the member's participation).
- **(f)** Art. 6(1)(f): legitimate interest of the orchestra in organising itself and in its
  history (who played which part).
- **(a)/9(2)(a)** consent, given by entering optional profile data, withdrawn by deleting it.

### orchestra-core

| Data | Purpose | Basis | Stored | Kept |
|------|---------|-------|--------|------|
| Login cookies (WordPress) | Login | (b) | Browser | Session or "remember me" |
| Cookie `orchestra_core_resetpass` (username and key of the reset link) | Setting a new password | (b) | Browser, HttpOnly, SameSite=Lax | Until the password is set or the browser is closed |
| `sessionStorage` key `orchestra-scroll` (page and scroll position) | Return to the same place after a form | (f) | Browser | One minute, read once |
| Transients: notices (may name a member), login error, "reset email sent" per user ID | Show a message once; one reset email per account every 5 minutes | (f) | `wp_options` | 2–5 minutes |

The cookie and the session storage are strictly necessary (TDDDG §25(2) no. 2): no consent
banner is needed for the Orchestra plugins.

### orchestra-members

| Data | Purpose | Basis | Who sees it | Kept |
|------|---------|-------|-------------|------|
| Name, email address, role (WordPress user) | Account, contact | (b) | Organizers, administrators; names: everyone | Until the account is deleted |
| Profile fields chosen by the administrators (e.g. dietary restrictions, financial support) | Planning projects | (a), 9(2)(a) | The member, organizers, administrators | Deleted when set inactive, when the Orchestra role is removed, when the field is deleted, by the eraser |
| Active status | Blocking former members | (f) | Organizers, administrators | Until the account is deleted |
| No automatic emails (opt-out) | Respecting the choice | (b) | The member | Until the account is deleted |

### orchestra-projects

| Data | Purpose | Basis | Who sees it | Kept |
|------|---------|-------|-------------|------|
| Participation (decision, dates, who entered it) | Planning | (b) | Accepted names: everyone who sees the project; the rest: conductors, organizers | History; anonymized when the account is deleted |
| Member comment, organizer note | Planning | (b) | Organizers (the member: own comment) | Deleted by the eraser and with the account |
| Assignments (who plays which part) | Planning, history | (b), (f) | Everyone from "Parts assigned"; history marks: conductors, organizers | History; anonymized when the account is deleted |
| Conductors and organizers of a project | Information | (b) | Everyone who sees the project | Deleted by the eraser and with the account |
| Changed parts not yet reported | Informing members | (b) | Conductors, organizers | Until reported or dismissed |
| CSV of accepted participants with profile fields | Planning (e.g. catering, funding) | (b), (a) | Organizers who may read all those profiles | Not stored on the site |

### orchestra-seating

| Data | Purpose | Basis | Who sees it | Kept |
|------|---------|-------|-------------|------|
| Seats (who sits where) | Rehearsals and concerts | (b) | Everyone from "Parts assigned"; arranging: conductors, organizers | Deleted by the eraser and with the account; people who no longer take part are hidden |

### orchestra-email

| Data | Purpose | Basis | Who sees it | Kept |
|------|---------|-------|-------------|------|
| Log: sender, subject, groups (single members as a reference, not by name), counts | Accountability | (f) | The sender; all entries: administrators | 12 months |
| Text, reply address, attachments | Delivery | (b) | — | Until the email has been sent |
| Queue: recipient user ID (and the text of automatic emails) | Delivery | (b) | — | Until delivered |

### orchestra-library

No personal data of members: downloads are not logged, files do not keep who uploaded them. Pieces
have a WordPress author, which is removed when that account is deleted. File names are shown to
every member, so they should not contain names of people.

---

## Rights of the people

| Right | How |
|-------|-----|
| Access (Art. 15), portability (Art. 20) | **Tools → Export Personal Data**: every plugin adds its data (profile, participation with comments and notes, assignments, projects run, seats, emails sent and written to the person). |
| Rectification (Art. 16) | Members change their profile and email address themselves; organizers and administrators correct the rest. |
| Erasure (Art. 17) | **Tools → Erase Personal Data** deletes what is not history: profile values, comments and notes, being named as conductor or organizer, seats, references in the email log, changed parts. It keeps (and reports) participation and assignments as the orchestra's history and the inactive status and email opt-out, so that the account stays blocked and receives nothing. **Deleting the account** then anonymizes the history: it shows "Former member". |
| Restriction, objection (Art. 18, 21) | Setting the member inactive stops all processing except the history and deletes the profile values. |
| Withdrawing consent (Art. 7(3)) | Deleting the profile values; turning off automatic emails. |

A former member who asks for erasure: run the eraser, then delete the WordPress account (Users),
attributing nothing to another user.

---

## Special categories (Art. 9)

Profile fields such as dietary restrictions can reveal health or religion. The suite treats all
profile values as sensitive:

- They are optional. The profile page says so ("Entering this information is voluntary") and who
  sees them; the policy text explains that entering them is the consent and deleting them
  withdraws it. There is no separate consent checkbox.
- Only the member, organizers and administrators see them.
- They are deleted when the member is set inactive or loses the Orchestra role.
- Organizers can download them for a project's accepted participants as CSV. The file leaves the
  site: store it safely and delete it after the project.

---

## Who sees what

| | Member, Librarian | Conductor | Organizer | Administrator |
|---|---|---|---|---|
| Names of other members | In projects (accepted, parts, seating) | Member list | Member list | All |
| Email addresses | Own | Of groups they may email | Of groups they may email, member list | All |
| Active status of others | — | ✓ | ✓ | ✓ |
| Profile values | Own | Own | All | All |
| Declined / pending participation | Own | ✓ | ✓ | ✓ |
| Comments and notes | Own comment | — | ✓ | ✓ |
| Assignments, seating | From "Parts assigned" | ✓ | ✓ | ✓ |
| Assignment history marks | — | ✓ | ✓ | ✓ |
| Email log | — | Own emails | Own emails | All emails |
| CSV of participants with profiles | — | — | ✓ | ✓ |

---

## Technical and organisational measures (Art. 32)

- **Access control:** WordPress roles and capabilities, checked for every view and action, with
  meta capabilities for "own" data ([capabilities.md](capabilities.md)). Members never use
  wp-admin.
- **Integrity:** nonces on every state-changing request; output escaped; uploads checked by
  detected type; weekly security scans (Psalm taint analysis, Semgrep, CodeQL, `composer audit`).
- **Confidentiality of files:** sheet music and email attachments are in private directories, not
  in the media library, served only through checked downloads with `Cache-Control: private, no-store`.
  orchestra-core checks daily that these directories cannot be downloaded (Site Health).
- **Internal pages:** `noindex`, no sitemaps, no oEmbed; author archives of members are not found.
- **No enumeration:** the Orchestra login page shows one message for an unknown name and a wrong
  password; "Forgot your password?" never says whether an account exists; one reset email per
  account every 5 minutes. (WordPress's own `wp-login.php` still names the reason; a security
  plugin can change that.)
- **Former members:** cannot log in (also not with application passwords); their sessions end at
  once.
- **No third parties in the browser:** no CDNs, web fonts, analytics or embeds; PDFs are generated
  on the server without loading anything remote.

---

## Third parties

| Recipient | What | Why |
|-----------|------|-----|
| Hosting provider | Everything (processor, AVV) | Running the site |
| Mail server / SMTP service | Recipient addresses, texts, attachments (processor, AVV) | Delivering emails |
| GitHub (GitHub Inc., USA) | Twice a day per plugin: site address, WordPress, PHP and plugin versions, language; no member data | Update check |

---

## Operator checklist

- A published privacy policy (Settings → Privacy) and, for a public site, an Impressum.
- HTTPS for the whole site.
- Two-factor authentication for administrators and organizers, who see sensitive profile data
  (a 2FA plugin; WordPress has none built in).
- On nginx, deny the private directories or define `ORCHESTRA_LIBRARY_STORAGE_DIR` and
  `ORCHESTRA_EMAIL_STORAGE_DIR` outside the web root; check **Tools → Site Health**.
- A working WP-Cron (or a server cron): it sends emails and purges the log. Without it, the log is
  purged when an administrator opens wp-admin.
- A brute-force protection for logins (e.g. a security plugin limiting login attempts); the
  plugins have none of their own.
- Page caches must not cache logged-in users ([architecture.md](architecture.md#orchestra-pages)).
- Single sites only: on multisite, removing a user from one site does not remove their Orchestra
  data there.
- Backups: regular, encrypted, stored apart from the server, a restore tested once, old ones
  deleted. They contain all personal data, including deleted and erased data until they expire.
- Monitoring: an uptime check and access to the PHP error log, so that a failing WP-Cron or mail
  delivery is noticed.
- Plugin updates install automatically from the GitHub releases. Their zips are built by GitHub
  Actions with a signed build provenance; `gh attestation verify <zip> --repo
  TheRacoonOfDeath/<plugin>` checks one.

---

## Data breaches

A data breach is any loss, disclosure or unauthorised access to personal data, e.g. a hacked
administrator account, sheet music or attachment directories found to be public (Site Health
reports this), a lost laptop with a participants CSV, or an email sent to the wrong group with
personal data.

1. **Contain it:** change the affected passwords (Users → the user → "Log out everywhere else"),
   remove the cause (e.g. fix the server rule, update the plugin), restrict the access.
2. **Assess it:** which data, how many people, which risk. Profile values can be special
   categories (health, religion) and raise the risk.
3. **Report it within 72 hours** of becoming aware to the data protection authority of the
   association's federal state (Art. 33), unless the breach is unlikely to result in a risk to
   people. Most authorities have an online form.
4. **Inform the affected members** without undue delay if the risk is high (Art. 34), e.g. with
   an email to the affected group.
5. **Document it** in every case (Art. 33(5)): what happened, the effects, what was done, and why
   it was or was not reported.

Security problems in the plugins themselves are reported privately to the developers (each repo's
`SECURITY.md`).

---

## Development checklist

Every change that stores, shows or sends personal data:

1. Store only what the feature needs; no "who did it" columns without a use.
2. Check a capability before showing it; prefer showing less to members.
3. Add it to the plugin's **exporter** and **eraser** (`wp_privacy_personal_data_exporters`,
   `wp_privacy_personal_data_erasers`). History is kept and reported as retained, with a reason;
   everything else is erased.
4. Handle **`deleted_user`**: anonymize history, delete the rest, clear author fields.
5. Remove it in **`uninstall.php`**.
6. Update the plugin's **privacy policy text** (`wp_add_privacy_policy_content` on `admin_init`)
   and its German translation.
7. Update this document: record of processing, visibility matrix, rights.
8. Personal data never goes into URLs, logs or third-party requests.
