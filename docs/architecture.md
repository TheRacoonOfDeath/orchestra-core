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

The domain plugins register **recipient groups**, following the same pattern as capabilities:

- a label, e.g. "Accepted participants"
- optionally a parameter, e.g. the project
- a function that returns the recipients' user IDs
- the capability required to email the group

orchestra-members registers the global groups (all members, all conductors, all organizers),
orchestra-projects the project and assignment-based groups.

Core provides:

- the screen for writing an email: it lists only the groups the user may email and can be opened
  with a group preselected, e.g. from a project page
- resolving groups to recipients, without duplicates
- sending one email per recipient, so recipients never see each other's addresses
- sending in the background in batches (WP-Cron or Action Scheduler), to avoid timeouts and
  hosting limits
- a log with metadata only (sender, groups, subject, number of recipients, time), deleted after a
  fixed retention period

A filter allows plugins to remove recipients from every group. orchestra-members uses it to
exclude inactive members.

Delivery uses `wp_mail()`. Sites should configure an SMTP plugin, because mail sent directly from
the web server is often classified as spam.

