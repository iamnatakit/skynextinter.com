# Platform membership

Admin configuration: `/admin/user_management`. The six user/network/permissions menus are grouped under “จัดการผู้ใช้”. No free roots are selected initially, as requested; choose them later in Admin. Roots identify network grouping. Affiliate accounts still have a 30-day trial and must qualify through a confirmed purchase of at least 100 PV per order or verified annual payment. A payment never grants Admin access.

Affiliate accounts receive 30 days from account creation. `downline_team` (ทีมงานใต้สายงาน) has free access; `external_team` (ทีมงานต่างสายงาน) uses the annual paid entitlement. Both team roles can use all business modules, while users/roles, permissions, integrations, store settings, audit and licensing administration remain Admin-only. Each team member can still manage their own Affiliate profile. Existing manual locks remain in effect after payment. Expired or manually suspended members cannot use management or Affiliate routes, including POST mutations. Account and renewal pages remain accessible. Expired public Affiliate profiles pause until access is restored. Admin accounts are exempt. All stored deadlines use UTC; UI dates use the existing date formatter.

## Activate payments and email

In Admin enter the payee name, PromptPay phone/ID, full recipient bank account and bank code, and EasySlip or SlipOK credentials. Enter SMTP host, port, TLS settings, account/password and sender email. Secret fields are never prefilled; leaving them blank preserves the saved value. Configure the free roots independently whenever ready.

The renewal price is fixed server-side at 990 baht for one year. A pending invoice lasts 24 hours. The QR is generated locally from a PromptPay payload; no customer data is sent to an external QR-image service. Members upload a private JPG/PNG/WebP slip up to 4 MB. A banking slip verification provider must confirm the recipient, amount, currency, transaction time and unique transaction reference before annual access and the external team role are granted. Downline teams and Admin are never downgraded by an annual payment. OCR or an AI interpretation alone cannot approve payment. Transfers and image hashes cannot be reused across commerce orders and subscriptions. Review/failed/unknown results do not unlock access. Admin can retry verification, but there is no unverified “mark paid” shortcut.

Expiry notices enter a deduplicated email queue. SMTP failures retry with a delay and eventually require Admin review. An uncertain delivery remains for review to avoid blindly sending duplicate notices. Missing SMTP configuration keeps notices queued.

## Worker

`php spark platform:billing-work` queues expiry notices, verifies pending renewal slips and sends queued emails. On this Windows host, scheduled task `SkyNextInter-PlatformBilling` runs every minute using `C:\xampp\php\php.exe` and the absolute workspace `spark` path. It runs under the current signed-in Windows user, hides the process window, and prevents overlapping workers. For hosting, configure the equivalent persistent scheduler on that server. Apache and the scheduler must remain available for unattended operation.

## Validation and current limits

The additive schema is in `app/Database/platform_billing.sql`; `Store::install` installs it for new databases. The live database was backed up in `writable/backups` before applying it.

- `php vendor/phpunit/phpunit/phpunit tests/platform --no-coverage`: 60 tests, 253 assertions.
- `python tools/run-isolated-smoke.py smoke_membership.py`: 28 HTTP checks on a disposable database, including expiry redirects, blocked writes, deduplicated email, 100 PV checkout snapshots and purchase qualification, role isolation, and rejection of legacy default-password bypasses.
- Browser checked the grouped menus and configuration screens. Windows task ran with result 0.

No real bank slip or SMTP delivery has been tested: the live receiver, provider credentials and mail settings have not yet been supplied. The UI keeps payment creation disabled until verification configuration is ready. Do not interpret unit-test fixtures as live bank confirmation.

Provider references: [EasySlip image verification](https://document.easyslip.com/en/v2/verify/bank/image), [PromptPay payload source](https://github.com/dtinth/promptpay-qr), [CodeIgniter email](https://codeigniter.com/user_guide/libraries/email.html). QR rendering uses the MIT-licensed Nayuki library in `public/platform/vendor/qrcodegen.js`.


## Product PV and role qualification (updated)

In Admin → สินค้าและสต็อก, edit **PV ต่อหน่วย**. Existing products start at 0 (“รอกำหนด”); no conversion from baht is assumed. Current price, stock and inquiry-only status are preserved. Product PV is an integer, configured independently of price. A single confirmed order must total at least 100 PV (`SUM(item.pv * quantity)`); separate orders are not accumulated. PV is captured from the server at checkout, stored on each order item, and displayed on the cart, checkout and order. Later catalog edits cannot retroactively qualify an older order. Shipping contributes no PV.

Only approved payments on positive-value paid orders qualify an Affiliate/member/external-team buyer as `downline_team`. Creating an order, uploading a slip, rejected payments, missing PV and totals below 100 PV do not qualify. Qualification runs in the same transaction as manual bank confirmation, verified automatic slip payment, or verified gateway settlement. Team members cannot approve their own payments. An Admin can assign the roles directly through the existing user editor. Manual role assignments remain the Admin's responsibility. Pending unpaid annual invoices are closed when a purchase qualifies the member; already submitted transfers stay available for verification.

Migration: back up the SQLite database, then run `php spark platform:roles`. It safely expands the users CHECK constraint, preserves existing data/references/indexes/triggers and sequence, adds product/order-item PV, and reconciles already confirmed payments. Fresh installations also include these changes through `Store::install`.

Products currently configured for inquiry only still require stock and sale settings to be confirmed before normal cart checkout. No unrequested Packet or price has been created. The threshold uses the online shop products once their PV and sellable stock are configured.
