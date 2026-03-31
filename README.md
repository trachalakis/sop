# SOP - Σύστημα Διαχείρισης Εστιατορίου

Ολοκληρωμένο σύστημα διαχείρισης λειτουργιών εστιατορίου. Καλύπτει παραγγελίες, μενού, κρατήσεις, προσωπικό, προμήθειες και εκτυπώσεις σε ενιαία πλατφόρμα.

---

## Τεχνολογίες

| Στρώμα | Τεχνολογία |
|--------|------------|
| Backend | PHP 8.4, Slim Framework 4 |
| ORM | Doctrine ORM 3 / DBAL 4 |
| Βάση δεδομένων | PostgreSQL 17 |
| Templates | Twig 3 |
| API | GraphQL PHP 14 |
| DI Container | PHP-DI 7 |
| Logging | Monolog 3 |
| Caching | Symfony Cache 7 |
| Dev Environment | DDEV (Docker, Nginx FPM) |

---

## Δομή Εφαρμογών

Το σύστημα αποτελείται από τέσσερις ξεχωριστές εφαρμογές:

### Εφαρμογή Παραγγελιών (Orders App)
Διεπαφή για σερβιτόρους και προσωπικό.
- Δημιουργία και διαχείριση παραγγελιών ανά τραπέζι
- Πλοήγηση στο μενού ανά κατηγορία
- Προσθήκη extras και προσαρμογών
- Εκτύπωση αποδείξεων (canvas-based)
- Υποστήριξη παραγγελιών για take-away

### Εφαρμογή Κρατήσεων (Reservations App)
- Προβολή και διαχείριση κρατήσεων
- Ανάθεση τραπεζιών σε κρατήσεις
- Πινακοειδής προβολή ημερήσιων κρατήσεων

### Εφαρμογή Χρηστών (Users App)
Διεπαφή για το προσωπικό.
- Ιστορικό προσωπικών παραγγελιών
- Καταγραφή ωρών εισόδου/εξόδου (ρολόι)
- Σάρωση κάρτας (RFID/barcode)
- Αλλαγή PIN

### Πίνακας Διαχείρισης (Admin Dashboard)
Πλήρης διαχείριση του εστιατορίου:
- **Παραγγελίες:** Προβολή, επεξεργασία, διαγραφή ανοιχτών/κλειστών παραγγελιών
- **Μενού:** Δημιουργία/επεξεργασία μενού με πολύγλωσση υποστήριξη, τμήματα, είδη, extras
- **Συνταγές:** Δημιουργία συνταγών με λίστα υλικών, διάρκεια, απόδοση
- **Προμήθειες:** Διαχείριση αποθέματος, ιστορικό τιμών, ΦΠΑ, ομάδες προμηθειών
- **Λίστες αγορών:** Αυτόματη δημιουργία και αποθήκευση λιστών
- **Τραπέζια:** Δημιουργία, επεξεργασία, ταξινόμηση τραπεζιών
- **Χρήστες:** Διαχείριση λογαριασμών προσωπικού με ρόλους και δικαιώματα
- **Εκτυπωτές:** Ρύθμιση εκτυπωτών κουζίνας/ταμείου
- **Αναφορές:** Στατιστικά ειδών, ημερήσιες αναφορές, πρόβλεψη ζήτησης
- **GraphQL API:** Για admin queries και mutations

---

## Ρόλοι Χρηστών

- Webmaster
- Διαχειριστής (Manager)
- Σερβιτόρος (Waiter)
- Προσωπικό κουζίνας (Kitchen Staff)
- Υπεύθυνος κρατήσεων (Reservations Manager)

---

## Δομή Κώδικα

```
sop/
├── public/              # Web root (index.php)
├── app/
│   ├── settings.php     # Ρυθμίσεις εφαρμογής
│   ├── dependencies.php # Dependency injection
│   ├── routes.php       # Ορισμός routes
│   └── .env             # Μεταβλητές περιβάλλοντος
├── src/
│   ├── Application/
│   │   ├── Actions/     # Request handlers (Admin, Orders, Reservations, Users)
│   │   ├── GraphQl/     # GraphQL types & resolvers
│   │   └── Services/    # Application services
│   ├── Domain/
│   │   ├── Entities/    # Doctrine ORM entities (~30 entities)
│   │   ├── Repositories/# Data access layer
│   │   ├── Enums/       # PHP Enums (UserRole, OrderStatus, κ.λπ.)
│   │   └── Services/    # Domain services
│   ├── Middleware/      # Auth, Authorization, Globals
│   └── templates/       # Twig templates
├── migrations/          # Database migrations
└── logs/                # Application logs
```

---

## Εγκατάσταση & Εκκίνηση

### Προαπαιτούμενα
- [DDEV](https://ddev.readthedocs.io/) εγκατεστημένο στο σύστημα

### Βήματα

```bash
# 1. Κλωνοποίηση αποθετηρίου
git clone <repository-url>
cd sop

# 2. Εκκίνηση DDEV
ddev start

# 3. Εγκατάσταση dependencies
ddev composer install

# 4. Αντιγραφή αρχείου περιβάλλοντος
cp app/.env.example app/.env
# Συμπλήρωσε τις τιμές στο app/.env

# 5. Εκτέλεση migrations βάσης δεδομένων
# (εκτέλεση των αρχείων στον φάκελο migrations/)
```

### Μεταβλητές Περιβάλλοντος

| Μεταβλητή | Περιγραφή |
|-----------|-----------|
| `APP_MODE` | `development` ή `production` |
| `DB_HOST` | Hostname βάσης δεδομένων |
| `DB_USERNAME` | Χρήστης βάσης δεδομένων |
| `DB_PASSWORD` | Κωδικός βάσης δεδομένων |
| `DB_NAME` | Όνομα βάσης δεδομένων |
| `SITE_NAME` | Όνομα εστιατορίου |

---

## Αρχιτεκτονική

Το σύστημα ακολουθεί **Clean Architecture** με σαφή διαχωρισμό στρωμάτων:

- **Domain Layer:** Entities, Repositories, Enums — καθαρή επιχειρηματική λογική χωρίς εξαρτήσεις
- **Application Layer:** Actions (controllers), GraphQL, Services — ορχηστρώνει το domain
- **Middleware:** Authentication → Authorization → Business Logic
- **PSR-7 Compliant:** Slim Framework με PSR-7 υλοποίηση
- **Doctrine Attributes:** Attribute-based ORM mapping (PHP 8)
- **Gettext i18n:** Πολύγλωσση υποστήριξη σε όλη την εφαρμογή
