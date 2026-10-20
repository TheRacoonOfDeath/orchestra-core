# Use Cases

## Purpose

This document describes the primary user interactions with the Orchestra Management System.

The purpose of the use cases is to define expected system behavior from a business perspective.

Implementation details such as database structures, APIs, or user interface designs are intentionally excluded.

Business rules referenced here are defined in [domain-model.md](domain-model.md).

---

# Actors

The system defines four actor types:

- Member
- Conductor
- Organizer
- Administrator

Organizers can also act as Members (they may play in projects). Conductors cannot.

Administrators may perform all actions available to other actors.

---

# Overview

| ID | Use Case | Primary Actor |
|----|----------|---------------|
| UC-001 | Login | Member |
| UC-002 | View Dashboard | Member |
| UC-003 | Update Personal Profile | Member |
| UC-004 | Create Piece | Conductor |
| UC-005 | Create Part | Conductor |
| UC-006 | Upload Sheet Music | Conductor |
| UC-007 | Search Sheet Music | Member |
| UC-008 | Download Sheet Music | Member |
| UC-009 | Create Project | Organizer |
| UC-010 | Assign Conductors | Organizer |
| UC-011 | Build Program | Organizer, Conductor |
| UC-012 | View Project Information | Member |
| UC-013 | Accept Participation | Member |
| UC-014 | Decline Participation | Member |
| UC-015 | Change Participation Decision | Organizer |
| UC-016 | View Participation Overview | Organizer |
| UC-017 | Assign Member To Part | Conductor, Organizer |
| UC-018 | Change Project Status | Organizer |
| UC-019 | Download Project Sheet Music | Member |
| UC-020 | Send Email To Group | Organizer, Conductor |
| UC-021 | Create Member Account | Administrator, Organizer, Conductor |
| UC-022 | Deactivate Or Reactivate Member | Organizer |
| UC-023 | Archive Piece | Conductor |

---

# UC-001 Login

## Goal

Allow a registered user to access protected parts of the application.

## Primary Actor

Member

## Preconditions

- User account exists

## Flow

1. User opens the login page.
2. User enters credentials.
3. System validates credentials.
4. System verifies that the member is active.
5. System grants access.

## Alternative Flows

### 3a. Invalid credentials

1. System shows the standard login error.

### 4a. Member is inactive

1. System denies access.
2. System informs the user that their account is inactive and that they should contact an administrator.

This message is only shown after valid credentials, so the account status is not revealed to others.

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
    - Active projects (Open for Participation or Preparation); completed projects in a separate tab
    - Own participation status per project, with a prompt to respond where it is Open
    - Assigned parts per project
    - Download of the assigned sheet music per project (UC-019)

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
- The profile fields chosen by the administrators (by default financial support requirement and
  dietary restrictions)

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
3. System validates that part names are unique within the piece.
4. System stores the parts.

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

Provide sheet music for a specific part, or a file for the whole piece.

## Primary Actor

Conductor

## Preconditions

- Piece exists.
- For part sheet music: part exists.

## Flow

1. Conductor selects a part, or the piece itself.
2. Conductor uploads a file.
3. System validates the file type and size.
4. System stores the file securely.

## Alternative Flows

### 2a. Part already has sheet music

1. System asks for confirmation to replace the existing file.
2. On confirmation, the new file replaces the old one.

## Postconditions

- The part has exactly one sheet music file, or the piece has an additional piece file.
- The file is available for authorized users.

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

Download a sheet music file or piece file.

## Primary Actor

Member

## Preconditions

- User is authenticated.

## Flow

1. User selects a file.
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
    - Concert dates (any number; none by default)
3. System stores the project with status Draft.

## Postconditions

- Project exists and is not yet visible to members.

---

# UC-010 Assign Conductors

## Goal

Record which conductors lead a project.

## Primary Actor

Organizer

## Preconditions

- Project exists.

## Flow

1. Organizer selects a project.
2. Organizer adds or removes conductors.
3. System stores the selection.

## Postconditions

- The project shows its conductors.

The selection is informational. Every conductor can manage assignments in every project.

---

# UC-011 Build Program

## Goal

Define the pieces performed within a project and their order.

## Primary Actor

Organizer, Conductor

## Preconditions

- Project exists.
- Pieces exist and are not archived.

## Flow

1. User opens a project.
2. User selects pieces from the library.
3. User arranges the pieces in program order.
4. System creates program entries.

## Alternative Flows

### 2a. User removes a piece that has assignments

1. System warns that the assignments for this piece will be removed.
2. On confirmation, the program entry and its assignments are removed.

## Postconditions

- Ordered project program exists.

---

# UC-012 View Project Information

## Goal

Allow members to view project details.

## Primary Actor

Member

## Preconditions

- User is authenticated.
- Project is not in status Draft.

## Flow

1. User opens a project.
2. System displays:
    - Dates
    - Description
    - Conductors
    - Own participation status
    - Own assignments
3. On the tab "Program", the system lists the pieces in program order.
4. On the tab "Who accepted", the system lists the names of everyone who accepted.

## Postconditions

- User understands project requirements.

---

# UC-013 Accept Participation

## Goal

Commit to participating in a project.

## Primary Actor

Member

## Preconditions

- Project is Open for Participation.
- Participation status is Open.

## Flow

1. User reviews project information.
2. User selects Accept.
3. System informs the user that the decision is final and asks for confirmation.
4. User confirms.
5. System stores the decision.

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

- Project is Open for Participation.
- Participation status is Open.

## Flow

1. User reviews project information.
2. User selects Decline.
3. System informs the user that the decision is final and asks for confirmation.
4. User confirms.
5. System stores the decision.

## Postconditions

- Participation status becomes Declined.
- User can no longer change the decision.

---

# UC-015 Change Participation Decision

## Goal

Allow an organizer to change a member's participation, e.g. after the member asked for it.

## Primary Actor

Organizer

## Preconditions

- Participation record exists.

## Flow

1. Organizer opens participation details.
2. Organizer selects a new status (Open, Accepted or Declined).
3. System stores the new status.

## Alternative Flows

### 2a. An Accepted participation with assignments is changed

1. System warns that the member's assignments in this project will be removed.
2. On confirmation, the status is changed and the assignments are removed.

## Postconditions

- Participation has the new status.
- If the new status is Open and the project is Open for Participation, the member can respond again.

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

Conductor, Organizer

## Preconditions

- Project is Open for Participation or in Preparation.
- Piece exists in project program.
- Member has Accepted participation.

## Flow

1. User opens the assignments of a piece in the project.
2. User selects a part.
3. System lists the accepted participants who have no part in this piece yet and marks for each
   of them:
    - Played Last Time
    - Played in a Completed Project
    - Never Played
4. User selects one or more members.
5. System stores the assignments.

While an accepted participant has no part in a piece, the piece shows a warning next to its title.

## Alternative Flows

### 4a. Member is already assigned to another part of this piece

The member is not listed. To move them, the user removes their assignment first. A request that
assigns them anyway (e.g. from an outdated page) is refused unless it asks to move them; then the
previous assignment is replaced.

## Postconditions

- Members are assigned to the part.
- The assignments are shown on the members' dashboards.

---

# UC-018 Change Project Status

## Goal

Move a project through its lifecycle.

## Primary Actor

Organizer

## Preconditions

- Project exists.

## Flow

1. Organizer opens the project.
2. Organizer selects the next or the previous status.
3. System asks for confirmation.
4. System stores the new status.

## Alternative Flows

### 4a. Project is opened for participation

1. System creates a participation record with status Open for every active member and organizer
   who has none for this project yet.

## Postconditions

- Project has the new status.
- Visibility and allowed actions follow the status (see domain model).

---

# UC-019 Download Project Sheet Music

## Goal

Download all sheet music needed for one project in one step.

## Primary Actor

Member

## Preconditions

- User is authenticated.
- User has assignments in the project.

## Flow

1. User opens the project or the dashboard.
2. User selects "Download my sheet music".
3. System bundles the sheet music of all parts assigned to the user in this project.
4. System initiates download.

## Postconditions

- User receives all sheet music for their assigned parts.

---

# UC-020 Send Email To Group

## Goal

Inform a group of people by email.

## Primary Actor

Organizer, Conductor

## Preconditions

- User may send email to at least one group.

## Flow

1. User opens the email screen, optionally from a project (groups of that project are preselected).
2. User selects one or more groups.
3. System shows the number of recipients.
4. User enters subject and message.
5. User sends the email.
6. System sends one email per recipient in the background and logs the sending.

## Postconditions

- Every active member of the selected groups receives the email.
- Recipients do not see each other's addresses.

---

# UC-021 Create Member Account

## Goal

Give a new person access to the system.

## Primary Actor

Administrator, Organizer, Conductor

## Preconditions

- No account exists for the person.

## Flow

1. User adds a person with name and email address.
2. An administrator selects the role (Member, Conductor or Organizer); for organizers and
   conductors the role is always Member.
3. System creates the WordPress user and sends the person a link to set a password.

## Alternative Flows

### 3a. A project is Open for Participation

1. System creates a participation record with status Open for the new member (Members and Organizers only).

## Postconditions

- The person can log in.

---

# UC-022 Deactivate Or Reactivate Member

## Goal

Mark members who left the orchestra, or who return.

## Primary Actor

Organizer

## Preconditions

- Member exists.

## Flow

1. Organizer opens the member.
2. Organizer sets the member to inactive.
3. System informs the organizer that the sensitive profile data will be deleted and asks for confirmation.
4. System sets the member to inactive and deletes the values of all profile fields.

## Alternative Flows

### 2a. Reactivation

1. Organizer sets an inactive member to active.
2. System sets the member to active.
3. System creates Open participation records for projects that are Open for Participation.

## Postconditions

- Inactive members cannot log in, receive no new participation records and no group emails.
- Historical records remain.

---

# UC-023 Archive Piece

## Goal

Remove a piece from active use without losing history.

## Primary Actor

Conductor

## Preconditions

- Piece exists.

## Flow

1. Conductor opens the piece.
2. Conductor archives the piece.
3. System stores the archived status.

## Postconditions

- The piece cannot be added to new programs.
- Existing programs, assignments and the assignment history keep referring to it.
- The piece can be unarchived.
