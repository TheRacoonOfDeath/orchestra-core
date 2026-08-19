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

Member
Project
Participation
Piece
Part
Assignment
Assignment History
Sheet Music

---

# Domain Diagram

Member
├── Participation
├── Assignment
└── Member Profile

Project
├── Participation
├── Program
├── Conductors
└── Assignments

Piece
├── Parts
└── Sheet Music

Part
├── Assignment
└── Sheet Music

Assignment
├── Member
├── Project
├── Piece
└── Part

---

# Member

## Description

A member represents a musician within the orchestra.

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

Examples:

- Financial Support Required
- Dietary Restrictions
- Active Status

## Lifecycle

A member may be:

- Active
- Inactive

Inactive members remain in historical records but should not participate in new projects.

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

### Program

A project references one or more musical pieces.

## Lifecycle

Draft
→ Open for Participation
→ Preparation
→ Completed
→ Archived

The exact workflow may evolve over time.

---

# Participation

## Description

A participation represents a member's response to a project invitation.

Every member has at most one participation record per project.

## Status

### Open

No response has been submitted.

### Accepted

The member commits to participating.

### Declined

The member commits to not participating.

## Business Rules

A member may change their decision only until a decision is submitted.

After submission:

- Accepted becomes locked
- Declined becomes locked

Only an organizer may modify a locked decision.

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

## Relationships

A piece contains one or more parts.

A piece may appear in multiple projects.

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

## Business Rules

Part names are not standardized.

Conductors may create arbitrary names.

---

# Sheet Music

## Description

Sheet music represents a downloadable file associated with a specific part.

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

Sheet music files are not publicly accessible.

Downloads require authentication and authorization.

---

# Program Entry

## Description

A program entry links a piece to a project.

## Example

Spring Concert 2027

Program:

- Pirates of the Caribbean
- Lord of the Dance
- El Camino Real

## Business Rules

A piece may appear in multiple projects.

A project may contain multiple pieces.

Program order may become relevant in future versions.

---

# Assignment

## Description

An assignment links a member to a specific part within a specific piece of a specific project.

Assignments are created by conductors.

## Example

Project:
Spring Concert 2027

Piece:
Pirates of the Caribbean

Part:
Horn 2

Member:
Julian

## Relationships

Assignment
→ belongs to Project

Assignment
→ references Piece

Assignment
→ references Part

Assignment
→ references Member

---

# Assignment History

## Description

Assignment history records previous assignments for analytical purposes.

The primary use case is assisting conductors when distributing parts.

## Example Record

Member:
Julian

Piece:
Pirates of the Caribbean

Part:
Horn 2

Project:
Spring Concert 2026

Date:
2026-05-15

## Supported Evaluations

When creating a new assignment the system should identify:

### Never Played

The member has never played this part in this piece.

### Played Before

The member has played this part previously.

### Played Last Time

The most recent assignment for this piece and part belongs to the same member.

This is generally considered the preferred assignment.

---

# Communication Groups

## Description

The system supports dynamic recipient groups.

Groups are not stored directly.

Instead, they are generated from current project data.

## Examples

### Global

- All Members
- All Conductors
- All Organizers

### Project-Based

- All Participants
- Accepted Participants
- Declined Participants
- Pending Participants

### Assignment-Based

- All Members assigned to Horn 1
- All Members assigned to Horn 2
- All Percussion Players

---

# Domain Ownership

## orchestra-members

Owns:

- Member Profile Data

Uses:

- WordPress Users

---

## orchestra-library

Owns:

- Pieces
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
- Communication Groups

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