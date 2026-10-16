# Domain Model

## Purpose

This document describes the core business entities of the Orchestra Management System and the relationships between them.

The domain model is implementation-independent. It does not describe:

- database tables
- WordPress internals
- API endpoints
- UI layouts

Its purpose is to define business concepts and ownership boundaries.

---

# Domain Overview

The system consists of three primary domains:

- Members
- Library
- Projects

The following entities exist within these domains:

- Member
- Piece
- Part
- Sheet Music
- Project
- Program Entry
- Participation
- Assignment
- Assignment History (derived)
- Communication Group (derived)

---

# Domain Diagram

Member
├── Member Profile
├── Participation (one per project)
└── Assignment

Piece
├── Parts
│   └── Sheet Music (exactly one file)
└── Piece Files (score, notes, …)

Project
├── Program Entries → Piece (ordered)
├── Participation → Member
├── Conductors → Member
├── Organizers → Member
└── Assignments

Assignment
├── Project
├── Part (and through it, the Piece)
└── Member

---

# Member

## Description

A member represents a person within the orchestra: a musician, conductor or organizer.

Authentication and account management are provided by WordPress.

The system extends WordPress users with orchestra-specific information.

## Attributes

### Core User Data

Owned by WordPress.

Examples:

- Username
- Email Address
- Password
- Display Name

### Orchestra Profile Data

Owned by orchestra-members.

- Active Status
- Financial Support Required (sensitive)
- Dietary Restrictions (sensitive)

## Roles

- Member: plays in projects.
- Organizer: manages projects and participation. Organizers may also play in projects.
- Conductor: manages the library and assignments. Conductors do not play in projects.
- Administrator: everything.

Permissions are global: every conductor and every organizer has their rights for all projects.

## Accounts

Accounts are created by an administrator. WordPress sends the new member a link to set a password.
There is no self-registration.

## Lifecycle

Active
→ Inactive
→ Active (reactivation)

### Inactive Members

- cannot log in
- are told after entering a correct password that their account is inactive and that they should
  contact an administrator (a wrong password shows the normal error, so the account status is not
  revealed to others)
- receive no participation records for new projects
- are not part of any communication group
- remain in historical records (participation, assignments, assignment history)

When a member is set to inactive, the sensitive profile data (financial support, dietary
restrictions) is deleted. It must be entered again after a reactivation.

## Privacy

Profile data is personal data under the GDPR.

- Members can read and edit their own profile.
- Only organizers and administrators can read other members' profiles.
- Other members only see names.
- Personal data can be exported and erased with the WordPress privacy tools. Erasing a member with
  history anonymizes their historical records instead of deleting them.

---

# Project

## Description

A project represents a rehearsal and performance period.

A project normally culminates in a concert.

Examples:

- Spring Concert 2027
- Christmas Concert 2027
- Summer Project 2028

## Attributes

### Basic Information

- Name
- Description
- Start Date
- End Date
- Concert Date

### Personnel

- One or more Organizers
- One or more Conductors

These are informational ("who runs this project"). They do not grant permissions.

### Program

An ordered list of pieces (see Program Entry).

## Lifecycle

Draft
→ Open for Participation
→ Preparation
→ Completed

Organizers move a project between statuses.

| Status | Visible to members | Members can respond | Assignments can be changed |
|--------|:------------------:|:-------------------:|:--------------------------:|
| Draft | no | no | no |
| Open for Participation | yes | yes | yes |
| Preparation | yes | no | yes |
| Completed | yes | no | no |

Completed projects leave the list of active projects and are listed separately.

Organizers and conductors see projects in every status.

Opening a project for participation creates the participation records (see Participation).

---

# Participation

## Description

A participation represents a member's response to a project.

Every member has at most one participation record per project.

## Creation

When a project is opened for participation, every active member who may play
(members and organizers) receives a participation record with status Open.
Members who are created or reactivated while a project is open receive one as well.

## Status

### Open

No response has been submitted yet. Shown as "pending".

### Accepted

The member commits to participating.

### Declined

The member commits to not participating.

## Business Rules

A member can respond once, while the project is Open for Participation.
The decision is final: after submission, the member cannot change it.

Organizers can change any participation status at any time, e.g. after a member calls them.

If an Accepted participation is changed to Declined or Open, the member's assignments in that
project are removed. The organizer is warned before.

---

# Piece

## Description

A piece is a musical work that can be performed within projects.

Examples:

- Pirates of the Caribbean
- Lord of the Dance
- El Camino Real

Pieces belong to the music library and may be reused across multiple projects.

## Attributes

- Title
- Composer
- Description
- Archived Status
- Piece Files: any number of files that belong to the piece as a whole, e.g. the full score or
  performance notes

## Relationships

A piece contains one or more parts.

A piece may appear in multiple projects.

## Business Rules

Pieces are archived, not deleted, once they are used in a project. Archived pieces cannot be added
to new programs but remain visible in old projects and the assignment history.

---

# Part

## Description

A part represents a playable role within a piece.

Parts are defined individually for each piece.

There is no global catalog of parts.

## Examples

For one piece:

- Horn 1
- Horn 2
- Percussion

For another piece:

- Solo Flute
- Accompaniment
- Synthesizer

## Attributes

- Name
- Description
- Sheet Music

## Business Rules

Part names are not standardized.

Conductors may create arbitrary names.

Part names are unique within a piece.

---

# Sheet Music

## Description

Sheet music is the downloadable file of a specific part.

Each part has exactly one sheet music file. Uploading a new file replaces the old one.

Files that belong to the whole piece (score, notes) are Piece Files.

## Examples

Pirates of the Caribbean
→ Horn 1
→ Pirates_Horn1.pdf

Pirates of the Caribbean
→ Horn 2
→ Pirates_Horn2.pdf

## Ownership

Owned by the Library domain.

## Business Rules

Sheet music and piece files are not publicly accessible.

Downloads require authentication and authorization.

Every member may download all sheet music in the library.

For convenience, members can download the sheet music of their assigned parts per project in one
step.

---

# Program Entry

## Description

A program entry links a piece to a project at a position in the program.

## Example

Spring Concert 2027

Program:

1. Pirates of the Caribbean
2. Lord of the Dance
3. El Camino Real

## Business Rules

A piece may appear in multiple projects.

A project may contain multiple pieces.

A piece appears at most once in a project's program.

The order can be changed at any time.

Removing a piece from a program removes its assignments in that project.

---

# Assignment

## Description

An assignment links a member to a part within a project.

The piece is given by the part. An assignment does not reference the piece separately.

Assignments are created by conductors.

## Example

Project:
Spring Concert 2027

Part:
Horn 2 (Pirates of the Caribbean)

Member:
Julian

## Business Rules

- The part's piece must be in the project's program.
- The member must have Accepted participation in the project.
- Several members may be assigned to the same part (e.g. eight players on Violin 1).
- A member is assigned to at most one part per piece in a project.

---

# Assignment History

## Description

Assignment history is the set of all assignments of past projects.

It is not stored separately but derived from the assignments, ordered by the projects' concert dates.

The primary use case is assisting conductors when distributing parts.

## Supported Evaluations

When creating a new assignment the system should identify, for a member and a part:

### Never Played

The member has never been assigned to this part.

### Played Before

The member has been assigned to this part in an earlier project.

### Played Last Time

The member was assigned to this part in the most recent earlier project whose program contained
the piece.

This is generally considered the preferred assignment.

---

# Communication Groups

## Description

The system supports dynamic recipient groups.

Groups are not stored directly.

Instead, they are generated from current data whenever an email is sent.

Inactive members are never part of a group.

## Examples

### Global (provided by orchestra-members)

- All Members
- All Conductors
- All Organizers

### Project-Based (provided by orchestra-projects)

- All Participants
- Accepted Participants
- Declined Participants
- Pending Participants

### Assignment-Based (provided by orchestra-projects)

- All Members assigned to Horn 1
- All Members assigned to Horn 2
- All Members assigned to any part of a piece

## Sending

Sending is a technical service of orchestra-core. The domain plugins define the groups; core
resolves them, sends one email per recipient and keeps a log.

---

# Domain Ownership

## orchestra-core

Owns:

- Roles
- Email sending and the email log

---

## orchestra-members

Owns:

- Member Profile Data
- Global Communication Groups

Uses:

- WordPress Users

---

## orchestra-library

Owns:

- Pieces
- Piece Files
- Parts
- Sheet Music

---

## orchestra-projects

Owns:

- Projects
- Participation
- Programs
- Assignments
- Assignment History
- Project-Based and Assignment-Based Communication Groups

---

# Future Extensions

The following concepts are intentionally excluded from Version 1:

- Rehearsals
- Attendance Tracking
- Calendar Management
- Travel Planning
- Accommodation Management
- Equipment Management

The domain model should remain extensible enough to support these areas in future releases.
