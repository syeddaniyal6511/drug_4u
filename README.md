# Drug 4U — Pharmacy Management System

A web-based pharmacy management system built with PHP, MySQL, and Abyss Web Server.

---

## Requirements

| Software | Version |
|----------|---------|
| [Abyss Web Server](https://www.aprelium.com/abyssws/) | Any recent version |
| PHP | 7.4 or higher |
| MySQL | 5.7 or higher |
| Web browser | Chrome, Firefox, Edge |

---

## Setup

### 1. Place the project

Copy the `drug_4u` folder into the Abyss Web Server web root:

```
C:\Abyss Web Server\htdocs\drug_4u\
```

### 2. Create the database

Open **phpMyAdmin** (or any MySQL client) and create a new database:

```sql
CREATE DATABASE clinic_db CHARACTER SET utf8 COLLATE utf8_general_ci;
```

### 3. Configure the database connection

Open `database/connect_db.php` and confirm the credentials match your MySQL setup:

```php
$dbc = mysqli_connect('localhost', 'root', '', 'clinic_db');
// ...
$objPdo = new PDO('mysql:host=localhost;port=3306;dbname=clinic_db', 'root');
```

Change `root` and the empty password `''` if your MySQL uses different credentials.

### 4. Start Abyss Web Server

Launch Abyss Web Server and ensure it is running on port **80**.

### 5. Create the database tables

Visit the following URL in your browser — this runs the schema and creates all tables automatically:

```
http://localhost/drug_4u/pages/login.html
```

Tables are created on first load via `database/createtables.sql`.

### 6. Seed initial data (optional but recommended)

Run the seeders once to populate a default admin account and sample drug inventory.

**Admin user:**
```
http://localhost/drug_4u/database/seed_admin.php
```

**Sample drugs & stock:**
```
http://localhost/drug_4u/database/seed_drugs.php
```

Both seeders are safe to re-run — they skip records that already exist.

---

## Logging In

Navigate to:

```
http://localhost/drug_4u/pages/login.html
```

**Default admin credentials** (created by `seed_admin.php`):

| Field | Value |
|-------|-------|
| Username | `admin@gmail.com` |
| Password | `password` |

> Change the password after first login.

**Forgot password?** Use the "Forgot password?" link on the login page. A reset link will be sent by email (requires PHPMailer configuration — see below).

---

## Features & Pages

After logging in you will be taken to the **Dashboard**. All features are accessible from the left sidebar.

### Register Customer
`pages/register_customer.php`

Register a new patient. Fields: first name, last name, gender, date of birth, postcode, and optional known allergies.

### Add Drug
`pages/newdrug.php`

Add a new drug to the system. Fields: drug name, basic unit, collective unit, units per pack, and minimum age limit.

### Add Stock
`pages/add_stock.php`

Record a new stock batch for an existing drug. Fields: drug, description, quantity, batch number, buying price, selling price, and expiry date.

### All Stock
`pages/all_stock.php`

View all stock batches across all drugs. Expired batches and zero-quantity rows are highlighted.

### New Order
`pages/new_order.php`

Create a cashier order for a customer. Select a customer, cashier, order status, and one or more drugs with quantities. Stock is automatically decremented (FEFO — earliest expiry consumed first).

### Rx History
`pages/prescription_history.php`

Look up a customer's full order/prescription history.

### All Orders
`pages/all_orders.php`

Browse every order in the system across all customers.

### Logout
`pages/logout.php`

Ends the session and returns to the login page.

---

## Project Structure

```
drug_4u/
├── assets/
│   └── css/
│       └── main.css              # Global stylesheet
├── controllers/
│   ├── authenticate.php          # Login POST handler
│   ├── forgot_password.php       # Password reset request
│   ├── reset_password.php        # Password reset confirm
│   └── register_customer.php     # Customer form handler
├── database/
│   ├── connect_db.php            # DB connection (mysqli + PDO)
│   ├── createtables.sql          # Schema — all CREATE TABLE statements
│   ├── queries.php               # Reusable DB query functions
│   ├── run_schema.php            # Runs createtables.sql on first boot
│   ├── seed_admin.php            # Seeds default admin user
│   └── seed_drugs.php            # Seeds sample drugs and stock
├── pages/
│   ├── partials/
│   │   ├── header.php            # Shared sidebar + navigation
│   │   └── footer.php            # Closes layout
│   ├── login.html                # Login page
│   ├── forgot_password.html      # Forgot password page
│   ├── reset_password.html       # Reset password page
│   ├── dashboard.php
│   ├── register_customer.php
│   ├── newdrug.php
│   ├── add_stock.php
│   ├── all_stock.php
│   ├── new_order.php
│   ├── prescription_history.php
│   ├── all_orders.php
│   └── logout.php
├── vendor/                       # PHPMailer (password reset emails)
└── README.md
```

---

## PHPMailer / Email Setup (optional)

Password reset emails are sent via PHPMailer. To enable this, open `controllers/forgot_password.php` and configure your SMTP credentials:

```php
$mail->Host     = 'smtp.gmail.com';
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->Port     = 587;
```

If you do not configure SMTP, all other features work normally — only the "Forgot password" email will not send.

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Blank page or "server error" | Check PHP error logs in the Abyss Web Server console |
| Tables not created | Visit `login.html` once to trigger schema creation, or import `database/createtables.sql` manually in phpMyAdmin |
| Cannot log in after seeding | Confirm `seed_admin.php` ran successfully and shows "Seeded default admin user" |
| Database connection refused | Verify MySQL is running and credentials in `connect_db.php` are correct |
| Stock not decrementing | Ensure the drug has stock entries added via **Add Stock** before creating an order |
