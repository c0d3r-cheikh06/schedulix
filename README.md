# EduSchedule v2 — Plateforme académique de gestion des emplois du temps

> Système professionnel de gestion des emplois du temps scolaires — PHP 8 · MySQL · Design académique premium

---

## ✨ Fonctionnalités

| Module | Fonctionnalité |
|--------|---------------|
| 🏛 Admin | Gestion des classes, salles, matières, professeurs |
| 📅 Génération | Génération automatique avec détection des conflits |
| ✅ Validation | Workflow de validation professeur → admin |
| 📧 Emails | Notifications HTML automatiques (SMTP) |
| 🔔 In-app | Système de notifications en temps réel |
| 📱 Responsive | Desktop, laptop, tablette, mobile |
| 🔒 Sécurité | CSRF, password_hash, PDO prepared statements |

---

## 🚀 Installation rapide

### Prérequis
- PHP ≥ 8.0 (avec extensions : pdo_mysql, mbstring, openssl)
- MySQL ≥ 5.7 ou MariaDB ≥ 10.3
- Serveur web : Apache (XAMPP/Laragon) ou Nginx
- Composer (optionnel, pour PHPMailer)

---

### 1. Cloner le projet

```bash
git clone https://github.com/c0d3r-cheikh06/emplois_du_temps.git
cd emplois_du_temps
```

---

### 2. Configurer l'environnement

```bash
cp .env.example .env
```

Puis éditez `.env` avec vos valeurs :

```env
DB_HOST=localhost
DB_NAME=emploi_du_temps
DB_USER=root
DB_PASS=
APP_URL=http://localhost/emplois_du_temps
APP_ENV=development
```

> **Important :** `APP_URL` doit correspondre exactement à l'URL depuis laquelle vous accédez au projet dans votre navigateur.

---

### 3. Créer la base de données

**Option A — phpMyAdmin :**
1. Ouvrez `http://localhost/phpmyadmin`
2. Créez une base de données nommée `emploi_du_temps` (encodage : `utf8mb4_unicode_ci`)
3. Importez le fichier `database.sql`

**Option B — Ligne de commande :**
```bash
mysql -u root -p < database.sql
```

---

### 4. (Optionnel) Installer PHPMailer pour les emails

```bash
composer require phpmailer/phpmailer
```

Sans PHPMailer, l'application fonctionne normalement mais les emails ne sont pas envoyés.

---

### 5. Lancer le projet

**XAMPP :** Placez le dossier dans `C:/xampp/htdocs/` et accédez à :
```
http://localhost/emplois_du_temps
```

**Laragon :** Placez dans `C:/laragon/www/` et accédez à :
```
http://emplois_du_temps.test
```

**PHP built-in server (développement) :**
```bash
php -S localhost:8000
# puis ouvrir http://localhost:8000
```

---

## 🔑 Comptes de démonstration

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | `admin@ecole.fr` | `password` |
| Professeur | `Babacar.Thioye@ecole.fr` | `password` |
| Professeur | `aissatou.diallo@ecole.fr` | `password` |

---

## 📧 Configuration SMTP (Gmail)

1. Activez la validation en deux étapes sur votre compte Google
2. Allez dans **Sécurité → Mots de passe d'application**
3. Créez un mot de passe pour "Mail" et "Autre appareil"
4. Renseignez dans `.env` :

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=votre.email@gmail.com
SMTP_PASS=le_mot_de_passe_app_généré
EMAIL_FROM=noreply@votreecole.sn
```

---

## 🗂 Structure du projet

```
emplois_du_temps/
├── index.php               # Page de connexion
├── register.php            # Inscription élève
├── logout.php
├── database.sql            # Schéma complet + données démo
├── .env.example            # Modèle de configuration
├── .gitignore
├── README.md
│
├── includes/
│   ├── config.php          # Configuration centrale (lit .env)
│   ├── db.php              # Connexion PDO singleton
│   ├── functions.php       # Fonctions utilitaires + emails
│   ├── auth.php            # Authentification + rôles
│   ├── header.php          # En-tête HTML + topbar
│   ├── sidebar_admin.php   # Navigation admin
│   ├── sidebar_prof.php    # Navigation professeur
│   └── sidebar_eleve.php   # Navigation élève
│
├── admin/
│   ├── dashboard.php       # Tableau de bord admin
│   ├── classes.php         # CRUD classes
│   ├── matieres.php        # CRUD matières
│   ├── professeurs.php     # CRUD professeurs
│   ├── salles.php          # CRUD salles
│   ├── horaires.php        # Gestion créneaux
│   ├── generer.php         # Génération EDT + emails
│   ├── emplois_du_temps.php# Vue grille EDT
│   ├── suivi_validations.php # Suivi workflow
│   ├── modifier_edt.php    # Modification manuelle
│   └── notifications.php   # Centre de notifications
│
├── professeur/
│   ├── dashboard.php
│   ├── emplois_du_temps.php
│   ├── valider.php         # Validation/rejet créneaux
│   ├── disponibilites.php
│   └── notifications.php
│
├── eleve/
│   ├── dashboard.php
│   └── emploi_du_temps.php
│
└── assets/
    ├── css/main.css        # Design système complet
    └── js/main.js          # Interactions UI
```

---

## 🎨 Design

- **Typographie :** DM Sans + DM Serif Display (Google Fonts)
- **Palette :** Bleu académique `#1A56DB`, blanc, gris léger
- **Composants :** Cards, tables, modals, toasts, badges, skeleton loading
- **Responsive :** Sidebar mobile avec overlay, grille fluide

---

## ⚙️ Variables d'environnement complètes

| Variable | Description | Défaut |
|----------|-------------|--------|
| `DB_HOST` | Hôte MySQL | `localhost` |
| `DB_NAME` | Nom de la base | `emploi_du_temps` |
| `DB_USER` | Utilisateur MySQL | `root` |
| `DB_PASS` | Mot de passe MySQL | *(vide)* |
| `APP_URL` | URL complète du projet | auto-détection |
| `APP_ENV` | Environnement | `development` |
| `SMTP_HOST` | Serveur SMTP | `smtp.gmail.com` |
| `SMTP_PORT` | Port SMTP | `587` |
| `SMTP_USER` | Email SMTP | *(vide)* |
| `SMTP_PASS` | Mot de passe SMTP | *(vide)* |
| `EMAIL_FROM` | Adresse expéditeur | `noreply@eduschedule.sn` |
| `EMAIL_FROM_NAME` | Nom expéditeur | `EduSchedule` |

---

## 🐛 Dépannage

**Page blanche / erreur 500**
- Activez les erreurs : `APP_ENV=development` dans `.env`
- Vérifiez les logs Apache : `C:/xampp/apache/logs/error.log`

**"APP_URL incorrect" / redirections cassées**
- Renseignez manuellement `APP_URL` dans `.env`

**Emails non reçus**
- Vérifiez les paramètres SMTP dans `.env`
- Sans PHPMailer installé, les emails sont loggés mais non envoyés
- Gmail : vérifiez que vous utilisez un "mot de passe d'application" et non votre mot de passe habituel

---

## 📄 Licence

Projet académique — Usage éducatif.
