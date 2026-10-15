# Architecture

## Purpose

The Orchestra Manager is an internal web application built on
WordPress.

The goal is to support orchestra administration while reusing standard
WordPress functionality whenever possible.

---

## Architectural Principles

### WordPress First

Standard WordPress functionality shall be reused whenever possible.

Examples:

- Users
- Authentication
- Roles
- Permissions
- Media handling

Custom development is limited to orchestra-specific functionality.

---

### Domain Separation

The system is divided into independent business domains:

- Members
- Library
- Projects

Each domain is implemented in its own plugin.

---

### Single Ownership

Every business object has one owner.

Example:

Piece
→ owned by orchestra-library

Project
→ owned by orchestra-projects

Member Profile Extension
→ owned by orchestra-members

## orchestra-core

Technical infrastructure.

Responsibilities:

- roles and the capability registry (see [capabilities.md](capabilities.md))
- automatic updates from GitHub releases

Planned:

- email service (see below)
- logging
- common UI elements

Contains no business logic. Core owns the *roles*, but each domain plugin owns its *capabilities*
and decides which roles receive them.

All other plugins depend on orchestra-core (`Requires Plugins: orchestra-core`).
Domain plugins do not depend on each other's internals; they communicate through public services,
actions and filters.

---

## orchestra-members

Responsibilities:

- member profile extensions
- dietary restrictions
- financial support information
- active status and blocking inactive members from logging in
- global communication groups

Uses WordPress users as source of truth.

---

## orchestra-library

Responsibilities:

- pieces
- parts
- sheet music and piece files

Responsible for secure file access.

---

## orchestra-projects

Responsibilities:

- projects
- participation
- assignments
- assignment history
- project and assignment-based communication groups

---

## Email Service

Email is a technical service of orchestra-core. It contains no business rules.

The domain plugins register **recipient groups**, following the same pattern as capabilities.
A group has an ID, a label, a category it is listed under (e.g. a project), a function that
returns the recipients' user IDs and the capability required to email it, checked with optional
arguments (e.g. the project ID), so that rules per group can be added later.

orchestra-members registers the global groups (everyone, all members, all conductors, all
organizers), orchestra-projects the project and assignment-based groups. Currently conductors and
organizers may email all groups.

Core provides:

- the screen for writing an email: it lists only the groups the user may email, can be opened
  with groups preselected, e.g. from a project page, and shows the recipients before sending
- resolving groups to recipients, without duplicates
- sending one plain-text email per recipient, so recipients never see each other's addresses
- sending in the background in batches with WP-Cron, to avoid timeouts and hosting limits
- a log with metadata only (sender, groups, subject, number of recipients, time), deleted after
  12 months; the text is deleted as soon as the email has been sent

A filter allows plugins to remove recipients from every group. orchestra-members uses it to
exclude inactive members.

Delivery uses `wp_mail()`, so an SMTP plugin can be added at any time without changes. See
[email.md](email.md) for the API, sending details and deliverability.

