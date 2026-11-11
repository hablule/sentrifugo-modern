# Contributing to Sentrifugo Modern

Thank you for your interest in contributing to **Sentrifugo Modern**! As an open-source enterprise HRM under the **GPLv3 license**, we welcome bug reports, pull requests, and architectural discussions from the global developer community.

> ### 🧭 Orientation & Strategic Alignment: Read This First
> 
> Before committing your time and effort to writing code, we strongly encourage you to install **Sentrifugo Modern** in your local environment and explore the system firsthand. Sentrifugo is a substantial enterprise suite with intricate business logic; taking time to understand its existing modules and workflows will make your contributions far more impactful.
> 
> Most importantly, please review our **[Single Source of Truth (SSOT.md)](./SSOT.md)**. 
> 
> The SSOT serves as our overarching architectural compass. Familiarizing yourself with it ensures that all contributors, backers, and maintainers course their way in a unified, cohesive direction.

---

## 🎯 Current Project Phase: Stabilization & Hardening (Phase 1)

Sentrifugo Modern is currently in an active **Foundation & Runtime Stabilization Phase**. 

To ensure the platform reaches rock-solid enterprise stability before expanding its functional footprint, **we are currently ONLY accepting Pull Requests focused on:**

* 🐛 **Bug fixes & Runtime crashes** (PHP 8.3 / OpenSSL 3 / MySQL 8 compatibility).
* 🐧 **Cross-platform compatibility** (Linux ext4 case-sensitivity and path standardization).
* 🧪 **Automated test coverage** (PHPUnit unit and integration tests).
* 🎨 **UI / CSS defects and responsive layout corrections**.

### 💡 Have an Idea for a New Module?
We love ambitious ideas! However, to protect our **[SSOT](./SSOT.md)** architectural roadmap, **please do NOT submit unannounced Pull Requests for new modules or third-party integrations.** 

Instead, open a proposal in **[GitHub Discussions (Ideas)](https://github.com/hablule/sentrifugo-modern/discussions/categories/ideas)**. We will gladly discuss the architecture with you, ensure it fits the project trajectory, and schedule it for future Phases!

---

## 🛠️ The Core Engineering Rules

To ensure cross-platform stability across both Linux servers and Windows environments, all contributions must strictly adhere to these 5 rules:

### 1. Modern PHP Standard (PHP 8.3+)
- All code must run cleanly under **PHP 8.3+**.
- Strictly avoid deprecated patterns:
  - Do **not** use curly brace array/string access (`$var{0}` ❌ ➔ `$var[0]` ✅).
  - Explicitly declare properties on classes or annotate legacy classes with `#[\AllowDynamicProperties]`.
  - Add `#[\ReturnTypeWillChange]` to internal interface implementations (`Countable`, `Iterator`, `ArrayAccess`).

### 2. Cross-Platform Directory Paths (Linux & Windows)
- **Always use forward slashes `/` in file paths.**
- Never hardcode Windows backslashes `\` (e.g. use `MEDIA_PATH . '/images/'`, never `MEDIA_PATH . '\images\'`).
- Filenames on Linux are **strictly case-sensitive**. Ensure controller, model, and form filenames match class declarations exactly.

### 3. Declarative Database Versioning
- **Never submit raw `ALTER TABLE` manual scripts directly to the repo.**
- If you introduce a new table or modify a column:
  1. Apply it to your local database.
  2. Run `php bin/schema_export.php` to regenerate `data/canonical_schema.dat`.
  3. Commit the updated `canonical_schema.dat` with your PR.
  4. Test that `php bin/upgrade.php` applies the delta safely.

### 4. Small, Atomic Pull Requests (No Mega-PRs)
- A PR should address **one bug or one specific view**. 
- PRs touching dozens of unrelated files or refactoring multiple modules simultaneously will be requested to be broken down. Small, focused PRs get reviewed rapidly.

### 5. Dependency Lock (Zero Unapproved Composer Bloat)
- Contributors must **not** add new packages to `composer.json` without prior discussion and maintainer approval. We maintain a lean, secure dependency footprint.

---

## 📝 Conventional Commits Standard

All commit messages and PR titles must use semantic prefixes:
* `Feat:` A new feature or module (Phase 2+).
* `Fix:` A bug fix or crash resolution.
* `Security:` Security hardening or credential protection.
* `Perf:` Performance optimizations.
* `Chore:` Maintenance tasks, dependency updates, or `.gitignore` changes.
* `Docs:` Documentation, SSOT, or README updates.
* `UI:` Visual layout or CSS updates.

---

## 🚀 How to Set Up Local Development

1. **Fork and Clone:**
   ```bash
   git clone https://github.com/YOUR_USERNAME/sentrifugo-modern.git
   cd sentrifugo-modern
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   ```

3. **Database Initialization:**
   ```bash
   gunzip -c data/demo_baseline.sql.gz | mysql -u root -p sentrifugo_db
   ```

4. **Configuration:**
- Duplicate the example files:
   ```bash
   cp public/db_constants.example.php public/db_constants.php
   cp public/application_constants.example.php public/application_constants.php
   ```
- Enter your local credentials.

---

## 📬 Submitting a Pull Request (PR)
1. Create a feature branch: git checkout -b feat/attendance-clockin
2. Make your changes and test locally.
3. Commit using conventional format: git commit -m "Feat: Implement biometric clock-in API endpoint"
4. Push to your fork and submit a PR targeting main.

---

## ⏳ Code Review & A Gentle Note on Patience

First and foremost: **thank you.** We know that writing code, fixing bugs, and submitting a Pull Request takes your personal time and energy, and we never take that for granted.

Because **Sentrifugo Modern** handles mission-critical enterprise workloads—including employee records, organizational structures, and upcoming payroll pipelines—every Pull Request is reviewed with thorough attention to detail. We manually test against data integrity, cross-platform compatibility, and architectural alignment with our **[SSOT](./SSOT.md)**.

Because of this rigorous review process, **code reviews may take some time.** 

We kindly ask for your patience while your contribution is being evaluated. A thoughtful delay is never a sign of disinterest; it is a reflection of our commitment to maintaining a rock-solid, enterprise-grade standard for organizations that rely on this platform every day.

We promise to review your submission with gratitude, offer constructive feedback, and work alongside you to guide your code to a successful merge!

Thank you for helping modernize open-source enterprise HR software for organizations worldwide!

---
