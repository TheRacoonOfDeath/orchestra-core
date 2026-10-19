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
- Seating Plan

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
├── Assignments
└── Seating Plan → Seats → Member

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
- Profile fields chosen by the administrators (sensitive); by default Financial Support Required
  and Dietary Restrictions

## Roles

- Member: plays in projects.
- Organizer: manages projects and participation. Organizers may also play in projects.
- Conductor: manages the library and assignments. Conductors do not play in projects.
- Administrator: everything.

Permissions are global: every conductor and every organizer has their rights for all projects.

## Accounts

Accounts are created by administrators, conductors and organizers. Conductors and organizers add
people with the role Member; only administrators choose another role. WordPress sends the new
member a link to set a password. There is no self-registration.

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

When a member is set to inactive, the sensitive profile data (the values of all profile fields)
is deleted. It must be entered again after a reactivation.

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

A project usually culminates in one or more concerts. It can also have none, e.g. a workshop.

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
- Concert Dates (any number, also none)

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

Organizers move a project one status forward or back, e.g. back to Open for Participation for
late responses.

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

Everyone who can see the project sees the names of those who accepted (members on a tab of
their own). Who declined or has not
responded yet is only visible to conductors and organizers.

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

The program and its order can be changed until the project is completed.

Removing a piece from a program removes its assignments in that project.

---

# Assignment

## Description

An assignment links a member to a part within a project.

The piece is given by the part. An assignment does not reference the piece separately.

Assignments are created by conductors and organizers.

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

Assignment history is the set of all assignments of a piece in other projects.

It is not stored separately but derived from the assignments. Only the order in which the
assignments were made counts, not the projects' dates.

The primary use case is assisting conductors when distributing parts.

## Supported Evaluations

When creating a new assignment the system should identify, for a member and a part:

### Played Last Time

The member's latest assignment of this piece, in any other project, was to this part.

This is generally considered the preferred assignment.

### Played in a Completed Project

The member has been assigned to this part in a completed project. Shown together with Played Last
Time when both apply.

### Never Played

The member has never been assigned to this part in another project.

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

- Accepted and Pending Participants
- Accepted Participants
- Pending Participants

Members who declined are not emailed about the project. There are no groups per piece or part.

## Sending

Sending is a technical service of the optional plugin orchestra-email. The domain plugins define
the groups; orchestra-email resolves them, sends one email per recipient and keeps a log.

---

# Seating Plan

## Description

A seating plan shows who sits where in a project. It is a grid of seats of equal size; each seat
holds one person and shows their name and their part (voice) in a piece of the program. The
seating itself does not depend on a piece.

## Business Rules

- Every project has at most one seating plan.
- Seated are the accepted participants who are active members or organizers. Conductors are never
  seated. Organizers are seated by default; this can be switched off per project.
- A new plan has a seat for everyone. Rows and columns can be added; only an empty last row or
  column can be removed, and the grid always keeps a seat for everyone.
- Everyone who sees the seating can step through the pieces of the program; only the voices
  change (first shown: the first piece).
- The seating of another project can be imported: people who take part in both projects keep
  their seat, the seats of everyone else stay empty, and new people are not seated.
- Conductors and organizers arrange the seating; members see it once it has been arranged.
- The seating of completed projects cannot be changed.
- People who no longer take part (declined, inactive, deleted) leave the seating.

---

# Domain Ownership

## orchestra-core

Owns:

- Roles

---

## orchestra-email

Owns:

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
- Project-Based Communication Groups

---

## orchestra-seating

Owns:

- Seating Plans

Uses:

- Projects, Participation, Programs and Assignments (orchestra-projects)
- Members (orchestra-members) and Parts (orchestra-library)

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
