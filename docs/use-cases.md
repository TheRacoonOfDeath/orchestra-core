# Use Cases

## Purpose

This document describes the primary user interactions with the Orchestra Management System.

The purpose of the use cases is to define expected system behavior from a business perspective.

Implementation details such as database structures, APIs, or user interface designs are intentionally excluded.

---

# Actors

The system defines four actor types:

- Member
- Conductor
- Organizer
- Administrator

Administrators may perform all actions available to other actors.

---

# UC-001 Login

## Goal

Allow a registered user to access protected parts of the application.

## Primary Actor

Member

## Preconditions

- User account exists
- User is active

## Flow

1. User opens the login page.
2. User enters credentials.
3. System validates credentials.
4. System grants access.

## Postconditions

- User is authenticated.
- User can access protected content.

---

# UC-002 View Dashboard

## Goal

Provide a personalized overview after login.

## Primary Actor

Member

## Preconditions

- User is authenticated.

## Flow

1. User opens the dashboard.
2. System displays:
    - Active projects
    - Participation status
    - Assigned parts
    - Relevant sheet music

## Postconditions

- User has an overview of current responsibilities.

---

# UC-003 Update Personal Profile

## Goal

Allow members to maintain personal information.

## Primary Actor

Member

## Preconditions

- User is authenticated.

## Flow

1. User opens profile settings.
2. User modifies information.
3. User saves changes.
4. System validates and stores data.

## Editable Fields

- Email address
- Financial support requirement
- Dietary restrictions

## Postconditions

- Profile information is updated.

---

# UC-004 Create Piece

## Goal

Create a new musical piece in the library.

## Primary Actor

Conductor

## Preconditions

- User has conductor permissions.

## Flow

1. Conductor creates a new piece.
2. Conductor enters title and metadata.
3. System stores the piece.

## Postconditions

- New piece exists in the library.

---

# UC-005 Create Part

## Goal

Define parts for a musical piece.

## Primary Actor

Conductor

## Preconditions

- Piece exists.

## Flow

1. Conductor opens a piece.
2. Conductor creates one or more parts.
3. System stores the parts.

## Example

Pirates of the Caribbean

- Horn 1
- Horn 2
- Percussion

## Postconditions

- Piece contains usable parts.

---

# UC-006 Upload Sheet Music

## Goal

Provide sheet music for a specific part.

## Primary Actor

Conductor

## Preconditions

- Piece exists.
- Part exists.

## Flow

1. Conductor selects a part.
2. Conductor uploads a sheet music file.
3. System validates the file.
4. System stores the file securely.

## Postconditions

- Sheet music is available for authorized users.

---

# UC-007 Search Sheet Music

## Goal

Locate sheet music within the music library.

## Primary Actor

Member

## Preconditions

- User is authenticated.

## Flow

1. User opens the library.
2. User searches or filters pieces.
3. System displays matching results.

## Postconditions

- User finds the desired piece.

---

# UC-008 Download Sheet Music

## Goal

Download an authorized sheet music file.

## Primary Actor

Member

## Preconditions

- User is authenticated.
- User has access to the file.

## Flow

1. User selects a sheet music file.
2. System validates permissions.
3. System initiates download.

## Postconditions

- User receives the file.

---

# UC-009 Create Project

## Goal

Create a new orchestra project.

## Primary Actor

Organizer

## Preconditions

- User has organizer permissions.

## Flow

1. Organizer creates a project.
2. Organizer enters:
    - Name
    - Description
    - Start date
    - End date
    - Concert date
3. System stores the project.

## Postconditions

- Project exists.

---

# UC-010 Assign Conductors

## Goal

Associate conductors with a project.

## Primary Actor

Organizer

## Preconditions

- Project exists.

## Flow

1. Organizer selects a project.
2. Organizer adds one or more conductors.
3. System stores assignments.

## Postconditions

- Conductors can manage project assignments.

---

# UC-011 Build Program

## Goal

Define the pieces performed within a project.

## Primary Actor

Organizer

## Preconditions

- Project exists.
- Pieces exist.

## Flow

1. Organizer opens a project.
2. Organizer selects pieces from the library.
3. System creates program entries.

## Postconditions

- Project program exists.

---

# UC-012 View Project Information

## Goal

Allow members to view project details.

## Primary Actor

Member

## Preconditions

- User is authenticated.

## Flow

1. User opens a project.
2. System displays:
    - Dates
    - Description
    - Conductors
    - Program
    - Participation status

## Postconditions

- User understands project requirements.

---

# UC-013 Accept Participation

## Goal

Commit to participating in a project.

## Primary Actor

Member

## Preconditions

- Participation status is Open.

## Flow

1. User reviews project information.
2. User selects Accept.
3. System asks for confirmation.
4. User confirms.
5. System locks the participation record.

## Postconditions

- Participation status becomes Accepted.
- User can no longer change the decision.

---

# UC-014 Decline Participation

## Goal

Decline participation in a project.

## Primary Actor

Member

## Preconditions

- Participation status is Open.

## Flow

1. User reviews project information.
2. User selects Decline.
3. System asks for confirmation.
4. User confirms.
5. System locks the participation record.

## Postconditions

- Participation status becomes Declined.
- User can no longer change the decision.

---

# UC-015 Unlock Participation Decision

## Goal

Allow an organizer to modify a locked participation decision.

## Primary Actor

Organizer

## Preconditions

- Participation record exists.

## Flow

1. Organizer opens participation details.
2. Organizer selects Unlock.
3. System removes the lock.

## Postconditions

- Member can submit a new decision.

---

# UC-016 View Participation Overview

## Goal

Track project participation.

## Primary Actor

Organizer

## Preconditions

- Project exists.

## Flow

1. Organizer opens project participation overview.
2. System displays:
    - Accepted members
    - Declined members
    - Pending members

## Postconditions

- Organizer understands participation status.

---

# UC-017 Assign Member To Part

## Goal

Assign a musician to a specific part.

## Primary Actor

Conductor

## Preconditions

- Project exists.
- Piece exists in project program.
- Member participates