# MASTER DEVELOPMENT PROMPT
## FROZEN FOOD INVENTORY & SALES MANAGEMENT SYSTEM

### YOUR ROLE

You are a senior Laravel developer, backend architect, database designer, and frontend developer.

Your responsibility is to develop a complete, production-ready Frozen Food Inventory and Sales Management System.

The software must be reliable, secure, maintainable, fully responsive, and designed with mobile users as the first priority.

This is a real business application intended for a frozen food retail store. It must correctly manage products, stock receiving, sales, stock losses, expenses, staff accounts, and financial reporting.

Do not treat this project as a school demonstration or basic CRUD application.

Build it as practical business software that will eventually operate in a real frozen food store.

Follow Laravel conventions, maintain a clear project structure, and keep business calculations consistent across every module.

---

# 1. PROJECT OVERVIEW

**Project Name:** Frozen Food Inventory & Sales Management System

**Application Type:** Web-based Inventory and Point of Sale (POS) System

**Business Type:** Frozen Food Retail Store

**Primary Users:**
1. Administrator
2. Cashier

**Primary Inventory Measurement:** Kilograms (kg)

**Currency:** Nigerian Naira (₦)

**Development Environment:** Localhost using XAMPP

**Production Environment:** Online web hosting

**Architecture:** Single-store application

The purpose of this application is to help a frozen food store manage its stock, sell products by weight or monetary amount, track damaged or spoiled products, monitor expenses, and generate accurate business reports.

The system should replace manual stock calculations and handwritten sales records.

The administrator must be able to monitor business activities, while cashiers must have a simple interface for recording sales.

---

# 2. REQUIRED TECHNOLOGY STACK

Use the following technologies.

### Backend

- PHP 8.2
- Laravel 12
- MySQL
- Laravel Eloquent ORM
- Laravel migrations
- Laravel Form Requests
- Laravel Policies and Middleware
- Laravel service classes
- Laravel database transactions

### Frontend

- Laravel Blade
- Tailwind CSS
- Livewire 4
- Alpine.js, using the version bundled with Livewire where appropriate
- Chart.js for dashboard charts
- Blade components for reusable interface elements

### Supporting Technologies

- Laravel authentication
- Vite for frontend assets
- PHPUnit for automated testing
- Select2 or a compatible searchable dropdown component where necessary
- Browser-based receipt printing
- Laravel logging

### Important Restrictions

1. Do not upgrade PHP beyond 8.2.
2. Do not upgrade Laravel beyond version 12.
3. Do not introduce React, Vue, Next.js, or another frontend framework.
4. Do not introduce a separate Node.js backend.
5. Do not introduce unnecessary third-party packages.
6. Do not require Redis or a separate queue service for core features.
7. Ensure the application can run on a typical Laravel-compatible shared hosting environment.
8. Keep the application suitable for local XAMPP development.
9. Use MySQL as the primary database.
10. Keep the architecture as a Laravel monolith.

---

# 3. MOBILE-FIRST DESIGN REQUIREMENTS

The application must be designed for mobile devices first.

The administrator and cashier must be able to perform their daily activities comfortably from a smartphone.

Do not build a desktop-only interface and simply reduce its dimensions for smaller screens.

### Required Screen Support

Design and test layouts at these widths:

- 320px
- 360px
- 375px
- 390px
- 430px
- 768px
- 1024px
- 1280px and above

### Mobile Design Rules

1. Use responsive layouts throughout the application.
2. Ensure forms fit properly on small screens.
3. Avoid unnecessary horizontal scrolling.
4. Use readable text sizes.
5. Use touch-friendly buttons.
6. Make important actions easy to reach.
7. Use responsive cards when tables become difficult to read.
8. Support mobile navigation.
9. Use accessible labels and validation messages.
10. Display loading indicators during submissions.
11. Prevent accidental duplicate form submissions.
12. Display helpful empty states when records do not exist.
13. Use confirmation dialogs for sensitive actions.
14. Make mobile POS calculations visible without excessive scrolling.
15. Keep commonly used actions within easy reach.

### Navigation

**Desktop:**

Use a professional sidebar layout containing the available modules.

**Mobile:**

Use a compact header, collapsible navigation, and bottom navigation where it improves usability.

The POS interface should have a mobile-friendly cart and checkout area.

### Visual Design

Use a clean, modern business software design.

Recommended visual direction:

- Light background
- White cards
- Deep green as the primary brand colour
- Neutral grey borders
- Clear status colours
- Consistent spacing
- Rounded interface components
- Readable typography
- Simple line icons

Avoid excessive animations, crowded dashboards, oversized components, and unnecessary decoration.

All modules must follow the same design system.

---

# 4. USER ROLES AND ACCESS CONTROL

The application will initially have two roles.

## 4.1 Administrator

The administrator has full business management access.

Permissions include:

- Access administrator dashboard.
- Create and manage cashier accounts.
- Create product categories.
- Create and manage products.
- Set product selling prices.
- Record new stock.
- View stock receiving history.
- Record damaged or spoiled stock.
- Perform approved stock adjustments.
- View and manage sales records.
- Review and reverse incorrect sales when authorised.
- Manage expenses.
- View revenue and profit reports.
- View stock valuation.
- View cashier activities.
- Configure store information.
- Manage low-stock settings.
- View system activity records.

## 4.2 Cashier

The cashier must have limited access.

Permissions include:

- Log in to their account.
- Access the POS interface.
- Search available products.
- View product selling prices.
- View available stock quantities.
- Sell products by amount.
- Sell products by kilogram.
- Add multiple items to a sale.
- Complete customer transactions.
- Print receipts.
- View their own sales history.
- Update their own password.

### Cashier Restrictions

A cashier must not:

- Create products.
- Modify product prices.
- Receive stock.
- Record damaged stock.
- Adjust inventory.
- Create other staff accounts.
- Manage expenses.
- Access purchase costs.
- View sensitive profit reports.
- Modify business settings.
- Delete or reverse completed sales without administrator approval.

Enforce permissions on the backend, not just by hiding navigation links.

### Authentication

Use secure Laravel session-based authentication.

Public registration must be disabled.

Only the administrator can create cashier accounts.

Provide a secure way to create the first administrator account during local installation.

Do not commit passwords or secrets to the repository.

---

# 5. PRODUCT CATEGORY MANAGEMENT

The system must support different frozen food categories.

Examples:

- Fish
- Chicken
- Turkey
- Seafood
- Other Frozen Foods

These are example categories, not permanent restrictions.

The administrator must be able to create, edit, activate, and deactivate categories.

Each category can contain multiple products.

Examples:

**Fish**
- Titus Fish
- Croaker Fish
- Panla Fish
- Catfish

**Chicken**
- Chicken Wings
- Chicken Thighs
- Chicken Legs
- Whole Chicken

**Turkey**
- Turkey Wings
- Turkey Legs
- Turkey Thighs

Categories must be connected to products using proper database relationships.

---

# 6. PRODUCT MANAGEMENT

Each product must have the following information:

- Product ID
- Product name
- Product category
- Optional product description
- Optional product image
- Selling price per kg
- Low-stock threshold in kg
- Product status
- Created date
- Last updated date

Example:

Product: Chicken Wings

Category: Chicken

Selling Price: ₦4,500/kg

Low-Stock Threshold: 10 kg

Status: Active

### Product Rules

1. Every product is measured in kilograms.
2. Products must belong to a category.
3. Product names must be clearly displayed.
4. Product prices must be controlled by the administrator.
5. Product prices must be greater than zero.
6. A product can have multiple stock receiving records.
7. Changing a selling price must not change previous sales.
8. Products with historical transactions must not be permanently deleted through normal application actions.
9. Products can be deactivated without losing historical records.

Initial product registration and subsequent stock receiving must be treated as separate responsibilities.

---

# 7. WEIGHT AND QUANTITY HANDLING

This is one of the most important project requirements.

**All user-facing weight measurements must use kilograms (kg).**

The cashier must never be required to manually convert kilograms into grams.

### Accepted Inputs

Examples:

- 1 kg
- 1.5 kg
- 0.5 kg
- 0.25 kg
- 2.75 kg
- 0.125 kg

Display quantities in a readable format.

Examples:

- 1 kg
- 1.5 kg
- 2.25 kg
- 0.375 kg

### Database Precision

Store inventory quantities with precision equivalent to three decimal places in kilograms.

For example:

`DECIMAL(12,3)`

Avoid floating-point arithmetic for inventory and money calculations.

Use a shared calculation service with consistent decimal handling.

Internally, scaled integers may be used when necessary to prevent calculation errors, but all user-facing values must remain in kilograms.

### Validation

- Do not accept negative quantities.
- Do not accept zero quantities for stock receiving or sales.
- Do not allow sales beyond available stock.
- Do not allow stock losses beyond available stock.
- Limit entered quantities to three decimal places.
- Handle rounding consistently.
- Ensure calculated stock balances never become negative.

All stock-related modules must use the same calculation rules.

---

# 8. STOCK RECEIVING MANAGEMENT

The administrator must be able to receive new stock without modifying previous receiving records.

### Example

Product: Chicken Wings

First Delivery:

Quantity: 100 kg

Second Delivery:

Quantity: 50 kg

Third Delivery:

Quantity: 25 kg

Total Received: 175 kg

Each delivery must have its own record.

### Stock Receiving Fields

- Product
- Quantity received in kg
- Purchase cost per kg
- Total purchase cost, calculated automatically
- Supplier name, optional
- Receiving date
- Optional reference or note
- Administrator who recorded the stock

### Important Rules

1. New stock must create a separate receiving record.
2. Previous receiving records must remain available.
3. Receiving new stock increases available inventory.
4. The administrator must see the resulting stock balance.
5. Each delivery must preserve its purchase cost.
6. Stock receiving must be recorded in the stock movement history.
7. Never silently overwrite historical stock records.
8. Corrections must be recorded as separate approved adjustments.

### Batch Tracking

Use stock batches to track separate deliveries.

Each batch should preserve:

- Original quantity received
- Remaining quantity
- Purchase cost per kg
- Receiving date
- Relevant stock adjustments

Use FIFO allocation for stock cost tracking, consuming available stock from the oldest eligible batch first.

This allows purchase costs to remain accurate when deliveries arrive at different prices.

---

# 9. POS AND SALES MANAGEMENT

The POS module is the core daily working area for the cashier.

It must be simple, responsive, and fast to use.

The POS must support two sales methods.

## 9.1 Sell by Amount

The cashier selects a product and enters the amount the customer wants to spend.

Example:

Product: Titus Fish

Selling Price: ₦4,000/kg

Amount Entered: ₦5,000

System Calculation:

5,000 ÷ 4,000 = 1.25 kg

The system displays:

Quantity: 1.25 kg

Total Amount: ₦5,000

After confirmation:

- Record the sale.
- Deduct 1.25 kg.
- Update the available stock.
- Save the transaction.
- Generate the receipt.

The cashier must not manually calculate the weight.

## 9.2 Sell by Kilogram

The cashier selects a product and enters the required weight.

Example:

Product: Chicken Wings

Selling Price: ₦4,500/kg

Quantity Entered: 2.5 kg

System Calculation:

2.5 × 4,500 = ₦11,250

The system displays:

Quantity: 2.5 kg

Total Amount: ₦11,250

After confirmation, deduct 2.5 kg from available inventory.

## 9.3 Precision and Rounding Rules

For amount-based sales, the calculated weight may contain more than three decimal places.

Example:

₦5,000 ÷ ₦4,500 = 1.111111... kg

The system must round the weight to three decimal places.

Displayed Weight: 1.111 kg

Customer Amount: ₦5,000

Preserve the customer-entered amount as the payable amount and record any small difference caused by weight rounding.

Use a consistent rounding rule across the POS, stock records, sales history, and receipts.

Keep the rounding difference available for financial reconciliation.

If a requested amount produces a calculated weight smaller than the minimum supported quantity, display a validation message.

The displayed weight, stock deduction, and transaction record must agree.

Never perform these calculations using unchecked floating-point values.

## 9.4 Multiple Products

The cashier must be able to sell multiple products in one transaction.

Example:

- Titus Fish: ₦5,000
- Chicken Wings: ₦3,000
- Turkey Legs: ₦4,000

Total Sale: ₦12,000

Each product must have its own calculated weight.

The system must deduct the correct weight from each product.

## 9.5 POS Features

Include:

- Product search
- Category filtering
- Available stock display
- Selling price per kg
- Sell by amount
- Sell by kg
- Automatic calculation
- Cart management
- Multiple sale items
- Remove item from cart
- Update quantity or amount
- Payment method selection
- Checkout confirmation
- Receipt printing
- Clear error messages
- Duplicate transaction protection

### Payment Methods

Initially support:

- Cash
- Bank Transfer
- POS/Card

Only confirmed paid transactions should be completed and deducted from inventory.

Do not introduce customer credit or debt management unless a later module explicitly requires it.

---

# 10. STOCK LOSS AND DAMAGE MANAGEMENT

The application must support proper spoiled and damaged stock records.

### Supported Loss Reasons

- Spoiled
- Damaged
- Freezer failure
- Missing stock
- Other reason

### Example

Product: Titus Fish

Available Quantity: 100 kg

Damaged Quantity: 8.5 kg

Remaining Quantity: 91.5 kg

The system must automatically deduct the damaged quantity.

### Required Fields

- Product
- Loss category
- Quantity lost in kg
- Reason
- Optional description
- Date
- Recorded by
- Calculated financial loss

### Rules

1. Stock loss must reduce available stock.
2. Stock loss must never be counted as a sale.
3. The original loss record must remain available.
4. Losses must appear in reports.
5. Losses must be traceable to the administrator who recorded them.
6. Prevent quantities exceeding available stock.
7. Preserve the purchase cost allocated to each loss.
8. Support an approved correction process without silently deleting history.

Calculate the financial value of stock losses from the purchase cost of the affected stock batches, not the current selling price.

When the exact affected batch is known, allow the administrator to select it.

Otherwise, use the standard FIFO stock allocation rule.

---

# 11. INVENTORY CALCULATION RULES

The core inventory formula is:

**Available Stock = Total Stock Received - Total Stock Sold - Total Stock Lost + Approved Positive Adjustments - Approved Negative Adjustments**

Example:

Initial Stock: 100 kg

New Stock: +50 kg

Sales: -12.5 kg

Damaged Stock: -5 kg

Available Stock: 132.5 kg

Every stock change must create an auditable record.

### Stock Movement Types

- Stock received
- Sale
- Damaged stock
- Spoiled stock
- Positive adjustment
- Negative adjustment
- Sale reversal

### Data Consistency

Use database transactions when processing operations that modify inventory.

Lock the relevant stock records during deduction.

Prevent concurrent sales from deducting the same available stock twice.

If any part of a sale fails, roll back the entire operation.

Never save a completed sale while leaving its inventory deduction incomplete.

Do not allow manual editing of calculated stock balances.

Use a reliable stock ledger and batch balances as the basis for inventory reporting.

---

# 12. SALES HISTORY AND RECEIPTS

Every completed sale must be recorded.

### Sales Information

- Unique transaction number
- Transaction date and time
- Cashier
- Products sold
- Quantity sold in kg
- Price per kg at the time of sale
- Total amount
- Payment method
- Payment status
- Transaction status

### Receipts

Generate clean, printable receipts containing:

- Store name
- Store contact information
- Receipt number
- Transaction date
- Cashier name
- Product names
- Quantity in kg
- Unit price
- Amount per product
- Total amount
- Payment method
- Thank-you message

Receipts must work on mobile and desktop browsers.

Support ordinary browser printing and a compact thermal-printer-friendly layout.

### Historical Accuracy

Store selling-price snapshots for every sale item.

Changing product prices must not modify completed sales.

Completed sales must not be silently edited or deleted.

If a sale needs to be reversed, use an administrator-controlled reversal workflow that restores the correct stock quantities and financial records.

---

# 13. EXPENSE MANAGEMENT

The administrator must be able to record business expenses.

Example categories:

- Electricity
- Generator fuel
- Transportation
- Staff salaries
- Shop rent
- Freezer repairs
- Maintenance
- Miscellaneous expenses

### Required Fields

- Expense category
- Description
- Amount
- Expense date
- Recorded by
- Optional note

Expenses must appear in financial reports.

Cashiers must not have access to business expenses.

---

# 14. DASHBOARD AND REPORTS

Create separate dashboard experiences for administrators and cashiers.

## Administrator Dashboard

Display:

- Today's sales
- Today's transaction count
- Total available stock
- Low-stock products
- Recent sales
- Stock received
- Stock losses
- Expenses
- Gross profit
- Estimated net profit
- Sales performance charts

## Cashier Dashboard

Display only information relevant to the cashier.

Examples:

- Today's personal sales
- Number of completed transactions
- Recent personal transactions
- Quick access to POS

### Reports

Support:

- Daily sales
- Weekly sales
- Monthly sales
- Sales by product
- Sales by category
- Sales by cashier
- Stock receiving history
- Available inventory
- Low-stock products
- Spoiled/damaged stock
- Expense reports
- Gross profit
- Estimated net profit

### Profit Calculation

Do not confuse revenue with profit.

**Revenue:** Total completed sales.

**Cost of Goods Sold:** Purchase cost of stock allocated to completed sales.

**Gross Profit:** Revenue minus Cost of Goods Sold.

**Estimated Net Profit:** Gross Profit minus recorded operating expenses and separately recognized losses not already included in Cost of Goods Sold.

Avoid counting the same stock loss or expense twice.

Use historical purchase costs for accurate reporting.

---

# 15. STAFF MANAGEMENT

The administrator must be able to create and manage cashier accounts.

### Cashier Information

- Full name
- Username or email
- Phone number, optional
- Password
- Account status
- Creation date

### Functions

- Create cashier
- Edit cashier information
- Activate or deactivate cashier
- Reset cashier password
- View cashier sales activity

Disabled users must not be allowed to authenticate or continue using protected application features.

Preserve historical sales made by deactivated cashiers.

---

# 16. BUSINESS SETTINGS

The administrator must be able to configure:

- Business name
- Business logo
- Phone number
- Address
- Receipt footer message
- Currency display
- Low-stock settings
- Other basic store preferences

Business settings must be accessible only to the administrator.

---

# 17. NOTIFICATIONS AND ALERTS

Implement practical system notifications.

Initial alerts should include:

- Low stock
- Out-of-stock products
- Important stock adjustments
- Recorded stock losses
- Other important administrator activities

Display notifications within the application.

External SMS, WhatsApp, and email notification services are not required for the initial version.

---

# 18. DATABASE DESIGN STANDARDS

Design the database using proper relationships, indexes, and constraints.

Expected core entities include:

- Users
- Categories
- Products
- Stock batches
- Stock movements
- Stock losses
- Stock loss allocations
- Sales
- Sale items
- Sale item stock allocations
- Payments
- Expenses
- Business settings
- Notifications
- Audit logs where appropriate

Create only the tables and fields needed by the active development module and its required dependencies.

Avoid duplicate data that can create inconsistent balances.

Use foreign keys where appropriate.

Preserve transaction history.

Use database migrations for all schema changes.

Use decimal-safe calculations for money and quantities.

Never rely on frontend calculations as the final authority for stock or financial values.

All critical calculations must be verified on the backend.

---

# 19. SECURITY AND DATA PROTECTION

Follow Laravel security practices.

Required protections include:

- Authentication
- Role-based access control
- Route authorization
- CSRF protection
- Input validation
- Secure password hashing
- Protected administrator routes
- Protected cashier routes
- Safe file uploads
- Database transaction handling
- Mass-assignment protection
- Transaction activity records
- Secure session handling
- Safe error handling

Prevent users from accessing records or actions outside their permissions.

Never expose database credentials in frontend code.

Never store plaintext passwords.

Prevent public access to sensitive configuration files.

---

# 20. SEARCHABLE DROPDOWNS

Whenever a dropdown can contain many records, use Select2 or another searchable component compatible with Laravel, Livewire, and Alpine.js.

Examples:

- Product selection
- Category selection
- Staff selection
- Stock receiving product selection
- Stock loss product selection
- POS product selection

If Select2 is used, handle Livewire DOM updates correctly.

Avoid conflicts between JavaScript components and Livewire rendering.

Prefer lightweight solutions where possible.

---

# 21. CODE QUALITY AND PROJECT STRUCTURE

Follow Laravel's recommended project structure.

Use:

- Controllers when appropriate
- Livewire components
- Eloquent models
- Form Requests
- Policies
- Service classes for business logic
- Database migrations
- Seeders
- Reusable Blade components
- Feature tests
- Unit tests

### Business Logic

Keep inventory and financial calculations out of Blade templates.

Create reusable services for operations such as:

- Stock receiving
- Stock deductions
- Sale processing
- Stock losses
- Stock adjustments
- Financial calculations

All modules must use the same stock calculation services.

Avoid duplicating business logic.

### Code Rules

- Write readable code.
- Use meaningful names.
- Keep methods focused.
- Validate all inputs.
- Handle failures clearly.
- Add comments where the reasoning is not obvious.
- Avoid unnecessary abstractions.
- Follow existing project conventions.
- Do not introduce conflicting dependencies.

---

# 22. DEVELOPMENT ENVIRONMENT

The application will initially run locally using:

- Windows
- XAMPP
- PHP 8.2
- MySQL
- Composer
- Node.js and NPM
- Laravel development server

Use `.env` for environment configuration.

The application must work locally without requiring an internet connection during normal use, once its dependencies and frontend assets have been installed and built.

Do not assume that localhost testing means the hosted production application must support disconnected offline transactions.

Offline synchronization is outside the initial project scope.

---

# 23. PRODUCTION DEPLOYMENT

The application will later be hosted online.

Prepare the project so that it can be deployed without rebuilding the application architecture.

Requirements:

- Environment-based configuration
- Safe production error handling
- Production asset builds
- Proper Laravel public directory configuration
- Database migrations
- Secure sessions
- Writable Laravel storage directories
- Correct file permissions
- Database backup guidance
- Hosting setup documentation

Never commit the production `.env` file or production credentials.

---

# 24. DEVELOPMENT MODULES

The application will be developed in the following order.

### Module 01: Project Foundation and Authentication

Laravel setup, authentication, user roles, responsive layouts, and shared UI components.

### Module 02: Staff and Cashier Management

Administrator-controlled cashier account management and permissions.

### Module 03: Business Settings

Business information, branding, and receipt settings.

### Module 04: Product Categories

Category creation, editing, status management, and category listing.

### Module 05: Product Management

Product creation, selling prices, category relationships, and low-stock thresholds.

### Module 06: Stock Receiving and Inventory

Stock batches, stock receiving, stock balances, purchase costs, and receiving history.

### Module 07: Spoiled/Damaged Stock

Stock losses, stock deductions, loss reasons, loss costs, and adjustment history.

### Module 08: POS and Sales Management

Sell by amount, sell by kg, cart management, payments, and automatic stock deductions.

### Module 09: Sales History and Receipts

Transaction records, printable receipts, cashier history, and controlled sale reversals.

### Module 10: Expense Management

Expense categories, recording, history, and reporting.

### Module 11: Dashboard and Reports

Sales reports, stock reports, cost tracking, profit reports, and charts.

### Module 12: Notifications and Stock Alerts

Low-stock warnings, administrator notifications, and important stock alerts.

### Module 13: Testing, Security, and Deployment

Automated tests, final checks, responsive testing, security review, and hosting preparation.

---

# 25. MODULE DEVELOPMENT RULES

Follow these instructions whenever a new module prompt is provided.

1. Read the Master Prompt before making changes.
2. Inspect the existing project structure.
3. Understand previously completed modules.
4. Check the database relationships.
5. Build only the requested module and required supporting changes.
6. Do not recreate existing working features unnecessarily.
7. Preserve the established UI design system.
8. Maintain mobile-first responsiveness.
9. Keep administrator and cashier permissions correct.
10. Reuse existing service classes and components.
11. Test the requested module.
12. Check that existing modules still work.
13. Fix errors discovered during development.
14. Do not introduce unrelated features.
15. Do not redesign completed pages unless the new module requires a correction.

### Important

Do not build the entire application in a single development run.

Development must follow the defined module order.

Complete and verify each module before proceeding to the next.

---

# 26. TESTING REQUIREMENTS

Use automated tests for important business operations.

### Inventory Tests

Verify:

- Receiving stock increases available quantity.
- Separate deliveries remain recorded.
- Sales reduce stock correctly.
- Stock losses reduce stock correctly.
- Stock balances never become negative.
- Stock calculations support fractional kilograms.

### Sales Tests

Verify:

- Selling by amount calculates kilograms correctly.
- Selling by kg calculates the total amount correctly.
- Multiple products are handled correctly.
- Insufficient stock prevents checkout.
- Completed sales are saved correctly.
- Duplicate checkout requests do not create duplicate sales.
- Failed transactions do not deduct stock.
- Historical selling prices remain unchanged.

### Security Tests

Verify:

- Cashiers cannot access administrator-only features.
- Unauthenticated users cannot access protected pages.
- Disabled accounts cannot operate the system.
- Users cannot bypass stock validation.

### Responsive Tests

Check the main application screens on mobile, tablet, and desktop layouts.

Ensure the POS remains usable on narrow screens.

---

# 27. DOCUMENTATION REQUIREMENTS

Maintain project documentation throughout development.

Include:

- Project overview
- Technology stack
- Module roadmap
- Database structure
- Business rules
- Installation instructions
- Environment setup
- Administrator account setup
- Development progress
- Testing instructions
- Deployment guide

Maintain a development progress file showing completed and pending modules.

Do not mark a module as completed until its required features have been built and tested.

---

# 28. INSTRUCTIONS FOR CODEX

Before beginning development:

1. Inspect the current project directory.
2. Determine whether an existing Laravel project is present.
3. Do not overwrite existing project files unnecessarily.
4. Review the specified technology versions.
5. Establish the software architecture.
6. Establish the database relationship plan.
7. Establish the mobile-first UI design system.
8. Create or update `AGENTS.md` with the project rules.
9. Create `docs/PROJECT_SPEC.md` containing the agreed business requirements and calculation rules.
10. Create `docs/MODULE_ROADMAP.md` listing all 13 modules and their progress.
11. Create `docs/ARCHITECTURE.md` describing the intended application structure.
12. Prepare the project for Module 01.

If the project directory is empty, do not generate every application feature immediately.

Module 01 will handle the Laravel application setup and authentication foundation.

If an existing Laravel application is present, inspect it before proposing changes.

### After Processing This Master Prompt

Provide a clear report containing:

- Project understanding
- Confirmed technology choices
- Planned application structure
- Planned database relationships
- Planned mobile-first design system
- Documentation files created
- Any compatibility concerns
- Readiness for Module 01

**Do not proceed with Modules 01 through 13 until their individual prompts are supplied.**

---

# FINAL PROJECT GOAL

Build a reliable Frozen Food Inventory and Sales Management System where:

- Administrators can manage products, stock, cashiers, losses, expenses, and reports.
- Cashiers can sell products by amount or kilograms without manually calculating weights.
- Every stock receipt is preserved separately.
- Every sale deducts the correct quantity.
- Every stock loss deducts the correct quantity.
- Purchase costs are tracked.
- Reports show accurate sales and stock information.
- All weight values displayed to users remain in kilograms.
- All critical transactions are secure and traceable.
- The entire interface is designed for mobile devices first.
- The application works locally during development and can later be hosted online.

Maintain these requirements throughout every development module.