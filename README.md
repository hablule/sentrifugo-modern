# Sentrifugo Modern

> **A modern, community-driven continuation of the open-source Sentrifugo Human Resource Management (HRM) platform.**

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE.txt)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](#platform--system-requirements)
[![Live Demo](https://img.shields.io/badge/Live%20Demo-Online-success?logo=google-chrome&logoColor=white)](https://sentrifugo-demo.duckdns.org)
[![Security Policy](https://img.shields.io/badge/Security-Policy-brightgreen)](SECURITY.md)

---

## 🌐 Live Interactive Demo

A fully functional, live cloud sandbox is running 24/7 on Oracle Cloud Infrastructure:

* **URL:** **[https://sentrifugo-demo.duckdns.org](https://sentrifugo-demo.duckdns.org)**
* **SSL Status:** 🔒 Valid HTTPS (Let's Encrypt)

### 🔑 Demo Access Credentials
| Field | Credential |
| :--- | :--- |
| **Username** | `alexander.wright@example.com` *(or `AlexanderW`)* |
| **Password** | `Sentrifugo123!` |
| **Role** | Super Administrator (Full Suite Access) |

> ℹ️ *Note: The live demo sandbox automatically sanitizes and resets its database to a clean baseline every night at 03:00 AM UTC.*

---

## 📖 About the Project

Originally developed by Sapplica Inc. under the GNU General Public License v3 (GPLv3), **Sentrifugo** offered small-to-medium enterprises a self-hosted, modular HR management suite. 

As official development cycles stagnated, the software fell behind modern runtime standards. **Sentrifugo Modern** is an independent open-source modernization initiative led by an Ethiopian solo developer. 

The project refactors the legacy architecture for **strict PHP 8.3+ compliance**, eliminates hardcoded structural bottlenecks, adds a **self-healing declarative database synchronization engine**, and prepares the foundation for high-demand missing modules (Time & Attendance, Biometrics, and modern Payroll).

---

## 👥 Module Status Overview

| Module | Status | Modernization Scope |
| :--- | :--- | :--- |
| **User & Role Management** | ✅ Modernized | Dynamic UI-managed hierarchy, replacing rigid legacy 5-tier structure |
| **ACL & Menu Engine** | ✅ Modernized | Dynamic database-driven permissions and action-level mapping |
| **Employee Records** | 🔄 Modernized | Profile data with configurable dynamic lookup attributes |
| **Holiday Engine** | ✅ Completed | Standalone calculation service, covered by automated unit tests |
| **Performance Appraisal** | 🔄 Modernized | Appraisal cycles, manager evaluations, and rating scale matrices |
| **Incident Management** | 🔄 Modernized | Incident reporting, investigation, and disciplinary records |
| **Enterprise Directory (LDAP)**| ✅ Modernized | OpenLDAP & Active Directory LDAPS support with OpenSSL 3 ciphers |
| **Exit Management** | ⏳ Slated Phase 2 | Separation workflows, exit interviews, clearance checklists |
| **Background Checks** | ⏳ Slated Phase 2 | Candidate screening workflows and agency pipelines |
| **Recruitment & ATS** | ⏳ Slated Phase 2 | Overhaul of job requisitions, candidate pools, and resume parsing |
| **Time & Attendance** | 🚀 In Progress | Biometric device synchronization (ZKTeco/API) & shift logging |
| **Payroll & Expenses** | ⏳ Slated Phase 3 | Compensation structures, tax formulas, and expense tracking |

---

## 🛠️ Platform & System Requirements

| Component | Minimum Specification | Recommended |
| :--- | :--- | :--- |
| **PHP** | 8.3+ | 8.3.x (with `pdo_mysql`, `ldap`, `mbstring`, `curl`, `gd`, `intl`, `zip`) |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ | MySQL 8.0+ / MariaDB 10.6+ |
| **Web Server** | Apache 2.4+ (with `mod_rewrite`) | Apache 2.4+ with clean URL routing |
| **OS** | Linux (Ubuntu, Debian, CentOS) or Windows | Linux (Ubuntu 22.04 / 24.04 LTS) |

---

## 🚀 Quick Start (Local Setup)

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/hablule/sentrifugo-modern.git
   cd sentrifugo-modern
   ```

2. **Install Composer Dependencies:**
   ```bash
   composer install
   ```

3. **Database Initialization:**
   Create a clean database (e.g. `sentrifugo_db`) and stream the compressed baseline:
   ```bash
   gunzip -c data/demo_baseline.sql.gz | mysql -u root -p sentrifugo_db
   ```

4. **Environment Configuration:**
   Copy the example templates and add your local database credentials:
   ```bash
   cp public/db_constants.example.php public/db_constants.php
   cp public/application_constants.example.php public/application_constants.php
   cp public/mail_settings_constants.example.php public/mail_settings_constants.php
   cp public/email_constants.example.php public/email_constants.php
   ```

5. **Run the Declarative Migration Engine:**
   Verify your database is 100% synchronized with the canonical schema:
   ```bash
   php bin/upgrade.php
   ```

6. **Serve:** Point your Apache VirtualHost document root to the project directory and open in your browser!

---

## 🔄 Declarative Database Versioning

Sentrifugo Modern features an intelligent, state-based database synchronization subsystem powered by **Doctrine DBAL 4.5**:

* **Export Canonical Blueprint (Maintainers):**
  ```bash
  php bin/schema_export.php
  ```
  *Inspects 387 tables and compresses the schema into a featherweight `data/canonical_schema.dat` (244 KB).*

* **Upgrade Existing Deployments (Clients & Sysadmins):**
  ```bash
  php bin/upgrade.php
  ```
  *Creates an automatic safety backup, calculates the mathematical structural delta, prompts for approval, and applies `ALTER`/`CREATE` statements with zero data loss.*

---

## 🤝 Contributing & Community

Sentrifugo Modern is currently in an active **Phase 1: Stabilization & Hardening** phase. 

Before contributing code or opening pull requests, please read our:
* **[Contributor Guidelines (CONTRIBUTING.md)](CONTRIBUTING.md)**
* **[Single Source of Truth (SSOT.md)](SSOT.md)**
* **[Security Policy (SECURITY.md)](SECURITY.md)**

Join the conversation or ask setup questions in **[GitHub Discussions](https://github.com/hablule/sentrifugo-modern/discussions)**!

---

## 📄 License & Attribution

* **Original Creator:** Sentrifugo was originally authored by **Sapplica Inc.**
* **License:** Distributed under the **GNU General Public License v3.0 (GPLv3)**. See [LICENSE.txt](LICENSE.txt) for the complete legal text.
* All modernized enhancements, tools, and extensions in this repository remain free and open-source under GPLv3.
