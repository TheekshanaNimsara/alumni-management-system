# KDU Alumni Network — Technical Documentation Suite

**Project:** General Sir John Kotelawala Defence University (KDU) Alumni Network  
**Target Environment:** PHP 8+, MySQL / MariaDB, Apache (XAMPP), Vanilla JavaScript, Semantic HTML5, CSS3  
**Repository Branch:** `feature/kdu-alumni-network`  
**Package Version:** 1.0.0 (Comprehensive Architecture & Engineering Reference)  

---

## Welcome to the Technical Documentation Package

This documentation package provides an exhaustive, source-verified architectural specification, file taxonomy, source code dictionary, four-phase project roadmap, and manual testing guide for the **KDU Alumni Network** platform.

Every statement, diagram, and workflow documented in this package is derived directly from the verified source code in the repository.

---

## Documentation Index & Reading Order

For an optimal understanding of the project, review the documents in the following sequence:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ 1. [01-architectural-system-overview.md]                                    │
│    System mission, layered architecture, request lifecycle, ER diagram,    │
│    and security controls.                                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│ 2. [02-directory-and-folder-taxonomy.md]                                    │
│    Complete repository directory tree, folder responsibilities, layer       │
│    separation, and Mermaid dependency map.                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│ 3. [03-complete-file-by-file-technical-description.md]                     │
│    16-point technical specification covering every source file in the       │
│    project individually.                                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│ 4. [04-four-phase-project-roadmap.md]                                       │
│    Project breakdown into 4 structured phases with objectives, acceptance   │
│    criteria, and task matrix.                                               │
├─────────────────────────────────────────────────────────────────────────────┤
│ 5. [05-testing-and-acceptance-guide.md]                                     │
│    Step-by-step manual testing guide with 29 test cases across all modules. │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Document Descriptions

| # | Document | File Path | Focus & Purpose |
| :-: | :--- | :--- | :--- |
| **1** | **Architectural System Overview** | [`01-architectural-system-overview.md`](./01-architectural-system-overview.md) | High-level architectural specification: project objectives, technology stack, 5-tier architecture, complete 10-step request lifecycle, Mermaid architecture diagram, authentication workflows, normalized 9-table database schema with Mermaid ER diagram, functional module index, verified security controls, and local deployment instructions. |
| **2** | **Directory & Folder Taxonomy** | [`02-directory-and-folder-taxonomy.md`](./02-directory-and-folder-taxonomy.md) | Structural repository reference: verified directory tree, folder-by-folder explanation (purpose, layer, dependencies, security), application layer separation, file naming conventions, and Mermaid dependency flowchart. |
| **3** | **Complete File-by-File Technical Description** | [`03-complete-file-by-file-technical-description.md`](./03-complete-file-by-file-technical-description.md) | Deep technical specification: analyzes every source file individually using the rigorous 16-point template (purpose, responsibilities, execution flow, inputs/outputs, database queries, auth guards, security considerations, manual testing steps, status, and related phase). |
| **4** | **Four-Phase Project Roadmap** | [`04-four-phase-project-roadmap.md`](./04-four-phase-project-roadmap.md) | Comprehensive project planning and milestone framework: divides the entire platform into 4 logical phases (Foundation, Profiles & Directory, Events & Jobs, Messaging & Administration) with detailed objectives, checklists, definitions of done, and an evidence-based Task Matrix. |
| **5** | **Testing & Acceptance Guide** | [`05-testing-and-acceptance-guide.md`](./05-testing-and-acceptance-guide.md) | Practical manual verification handbook: prerequisites, database setup, seed accounts, and 29 structured test cases with preconditions, test steps, expected results, actual results, and pass/fail statuses covering all functional modules. |

---

## Key Technology Highlights

* **Pure Native Web Stack:** Strictly utilizes HTML5, CSS3, Vanilla JavaScript (ES6+), PHP 8+, and MySQL PDO. Zero external frontend or backend frameworks.
* **KDU Cinematic Maroon & Gold Palette:** Custom CSS design system adhering to university visual branding (`#24070A`, `#120305`, `#4A1116`, `#D4AF37`, `#F1D77A`, `#F3EFE6`, `#FFFFFF`).
* **Robust Security Posture:** BCRYPT password hashing (`password_hash`), 100% PDO prepared statements with parameter binding, XSS escaping (`htmlspecialchars`), session fixation defense (`session_regenerate_id`), session cookie hardening (`HttpOnly`, `SameSite=Lax`), and upload directory execution denial via `.htaccess`.
* **Relational Database Design:** 9 normalized tables with foreign keys and cascading referential integrity.
