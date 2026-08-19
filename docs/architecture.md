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

- shared services
- logging
- email service
- permission helpers
- common UI elements

Contains no business logic.

---

## orchestra-members

Responsibilities:

- member profile extensions
- dietary restrictions
- financial support information

Uses WordPress users as source of truth.

---

## orchestra-library

Responsibilities:

- pieces
- parts
- sheet music

Responsible for secure file access.

---

## orchestra-projects

Responsibilities:

- projects
- participation
- assignments
- assignment history
- project communication