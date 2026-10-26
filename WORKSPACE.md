# Member workspaces

- `/workspace`: authenticated user's workspace. After login members arrive here; administrators retain `/admin`.
- `/workspace/salespages`: own structured page editor, draft, preview, publish, archive. Public routes are `/s/{immutable Affiliate ID}/{slug}`. Owner is always taken from the authenticated session. Public pages use the owner's contact and registration links and preserve affiliate attribution.
- `/workspace/profile`: public introduction plus encrypted private membership application fields. Drafts can be incomplete; submission requires key identity, address and banking fields plus both document types. Submitting an application does not approve documents or change a membership role.
- `/workspace/documents`: own identity card and bankbook. JPEG, PNG, PDF, maximum 5 MB. Files remain outside `public/` and are encrypted with AES-256-GCM. Downloads require owner or active administrator; attachments use no-store and nosniff headers.
- `/workspace/review` or `/admin/member_documents`: administrators can inspect submitted information and review documents. Reads, downloads, uploads and reviews are audited without storing document content in audit logs. Replacing a document or changing relevant identity/bank information resets its review.
- Workspace menus follow role permissions and platform access. Expired members can still access their private profile, documents and renewal page; publishing/editing Sales Pages requires active platform access. Member/affiliate/team accounts cannot enter shared `/admin` modules. Explicit staff roles (sales and manager) keep their assigned central shop duties.

## Installation and permissions

Run `php spark workspace:install`. This adds tables and initializes the new `salespages` permission once without importing products or creating accounts. Admin role-menu management can then disable or enable `salespages` for configurable roles. Existing team-role business permissions retain the platform's existing fixed policy.

## Backups

Back up the platform SQLite database **together with** `writable/private/member-workspaces/`, including `encryption.key`. Losing the key makes saved private profiles and documents unreadable. Keep the private directory and backups inaccessible from the web. The existing project and writable Apache access rules enforce this locally. `MEMBER_WORKSPACE_STORAGE` can override storage for an isolated environment; it must remain outside public web roots.

## Verification

`php vendor/phpunit/phpunit/phpunit tests/platform --no-progress --no-coverage`

Workspace tests cover ownership, permissions, subscription expiry, encrypted records, restricted document download/review, replacement, application submission and safe public rendering. Browser tests use a separate SQLite fixture under `writable/testing/`, never production accounts or identity documents.

## Existing SKYNEXT member registration
- `/register`: `membership_kind=new|existing`. Existing members must supply a unique original `skynext_member_code` and explicit `annual_opt_in=1`. This does not verify the number against an external SKYNEXT registry.
- Original SKYNEXT codes live in `member_enrollments`; they never replace or alter sequential immutable Affiliate IDs. Enrollment choice is fixed at registration and not editable through profile POST.
- Existing members get a 30-day trial and a scheduled `PACKET-990-ANNUAL` renewal order for 99000 satang in `platform_renewal_orders`. Trial end uses the existing UTC subscription timestamps; dates display in Thailand time.
- Workspace, profile, and billing display this selected plan. Due status advances on owner page reads or the existing expiration worker. QR invoices become available after trial ends; verified payment marks the linked order paid and grants one year. There is no automatic debit. Requires existing PromptPay/slip provider setup for live payment and SMTP/worker for email delivery.
- Existing annual enrollment keeps its selected plan rather than automatically switching to free 100PV access. New members and legacy accounts retain their existing flow.
- Schema: `app/Database/registration_plans.sql`, installed through Store::install / `php spark workspace:install`, without product import. Existing users are not enrolled retroactively.

## Profile photos
- Workspace > Profile provides an independent photo form (POST `/workspace/photo`) to upload, replace, or remove the current authenticated user's portrait. Profile POST cannot accept another owner ID.
- `member_photos` stores re-encoded 640px maximum square JPEGs with a random revision token; accepted input JPG/PNG/WebP up to 5 MB and 12 million pixels. Re-encoding strips embedded metadata and appended content. Photos are separate from encrypted membership documents.
- GET `/workspace/photo` previews only the signed-in owner's image. Public `/member-photo/{AffiliateID}` returns an image only for active, published profiles with valid platform access; otherwise 404. All image responses use no-store and nosniff.
- Affiliate landing pages, Sales Pages, and a shared referrer card use the owner's photo. The referrer card follows existing referral session/cookie attribution, including registration through `/r/{AffiliateID}`. Hidden profiles show initials, not their stored photograph.
- No external avatar service, cloud upload, or production profile data is needed. Schema installs through Store::install / workspace:install.

## Profile social links
- Public profiles include `users.facebook_url`, editable through Workspace profile and Affiliate profile. Existing forms that omit this field preserve the saved value. An explicit empty value clears it.
- Mirrored topbar LINE/Facebook controls use first-party `contact-us/{AffiliateID}` and `contact-facebook/{AffiliateID}` routes, resolving the current affiliate context/cookie ahead of the signed-in member.
- Facebook links accept HTTPS Facebook profiles/pages (including numeric profile.php IDs), normalize the host, remove tracking queries, and reject external hosts and PHP redirect endpoints. Public redirects recheck active membership, publication, and valid channel URL. Missing/hidden channels show a contact notice without redirecting to a company account.
- Schema migration adds only `facebook_url` with empty default. No existing profile URLs or account identifiers are rewritten.

## Promotion destination and public site information
- Workspace overview has an owner-scoped promotion selector. A stable `/go/{AffiliateID}` link opens either the homepage (default) or the selected published Sales Page owned by that member, using the same attribution cookies as existing Affiliate links. Existing `/p/` profile and `/r/` registration routes keep their meanings.
- Preferences are in `member_promotion_preferences`; selection of another owner's, draft, or inaccessible page is rejected. A subsequently archived/deleted page falls back to the homepage; expired members see the existing unavailable screen.
- Affiliate dashboard shares the stable `/go/` URL, and Workspace includes a working copy control.
- Footer shows unique visitors today (Asia/Bangkok midnight), unique visitors overall, and pageviews from the existing consent-based visits table. Counts are global site totals, never generated numbers; they refresh on page load.
- The final homepage section before Footer lists up to 10 latest active non-admin members. It selects only public display name, public province, and Affiliate ID. Hidden/unset public names use a generic member label; unset/hidden provinces use an unspecified tag. Private application information is never used.
- `users.public_province` is a separate optional public profile input. It is not copied from encrypted membership province. Store::install / workspace:install adds this column and the preferences table without importing products.
- Validation: platform suite 95 tests / 395 assertions; isolated UI checked home/custom destinations, attribution, province publication, and copy; production UI checked final section and live footer counts.

## Required registration details and readable form
- Public `/register` requires name, phone, province, email, password, membership type, and consent. Existing members additionally require the original SKYNEXT code and annual opt-in. Conditional fields are disabled when not applicable.
- ThaiProvinces::ALL supplies 77 province choices. The controller always passes province to Commerce::register, which rejects missing/invalid province and missing/invalid phone before creating a user or allocating an Affiliate ID. Existing internal imports/fixtures without the province key retain backward compatibility.
- Phone formatting is normalized; province is saved into the public-profile province field, displayed publicly under the existing publication setting. No existing accounts are updated retroactively.
- Registration-only styling widens the card to 760px and uses 18px controls, larger labels, clear focus states, and a single-column mobile layout; login retains its existing card.
- Verified with an isolated browser registration: required name/province blocking, 77 options, saved phone/province, correct next Affiliate ID and sponsor; platform suite 98 tests / 409 assertions.

## Registered full name on latest member cards
- Workspace profile includes an independent owner-only publication form at POST `/workspace/name-visibility`. It explicitly names the registered full name and asks permission before showing it on the public latest-member section.
- Opt-in copies the approved name into `member_name_publications` with consent_at; opt-out removes that public copy. No existing member is opted in automatically. Private changes to users.name do not silently change the consented public copy.
- PublicSiteStats selects the consented public copy first, then the existing published public_name, then a generic member label. It does not query private users.name for the public cards.
- This consent flow was used because automatic approval review rejected blanket publication of names for members with hidden profiles. Schema is additive through Store::install / workspace:install. Browser opt-in verified using an isolated synthetic account.

## Homepage activities Section 3
- Homepage order: hero, recommended products, `sn-home-activities`, existing smart-business content. Two activity cards link to real `/activities#...` sections.
- `ActivityHighlights::CATALOG` is shared by the activities page and homepage selector, ordered by the most recent additions to the website (not future travel dates). Add the view/anchor/title/image metadata there when adding another activity.
- Admin-only `/admin/home_activities` and sidebar menu configure `latest`, `random`, or `manual` modes. Settings live in `settings.home_activity_highlights`. Default latest uses the first two entries; random chooses two distinct entries per page load; manual requires exactly two different valid activities and preserves order. Corrupt settings fall back to latest. No production selection was overwritten during testing.
- Model and controller both enforce Admin access. Isolated browser verified Admin save -> actual homepage cards, random mode, and activity detail navigation; public homepage verified exact third-section order and both poster loads.
- Login form styling is isolated in `login.css`: 620px card, 18px labels and controls, larger green/gold actions, responsive padding. Existing credentials and login behavior are unchanged; synthetic Admin login was verified in the isolated database.

## Downline qualification restricted to Affiliate 0005
- Membership::roots resolves the active immutable Affiliate code 0005, never the user ID 5 or configurable legacy roots. The root itself is excluded.
- Membership::isDownline requires an explicit member_enrollments row of kind=new, annual_opt_in=0 and a direct or indirect sponsor chain to 0005. Missing enrollment, existing SKYNEXT enrollment, unrelated/cyclic ancestry, or an inactive/missing root cannot qualify.
- Confirmed purchases still require 100 PV in one order and approved full payment before promotion. Role alone no longer bypasses free-access eligibility. Existing user roles, Affiliate IDs and sponsor links are not rewritten; membership access is recalculated under this rule. No future-only signup cutoff was added: new refers to the registration membership type.
- Admin cannot select another root; old platform_free_roots settings are ignored. Registration, billing, order PV notices and expiry notices describe the same rule.
- Platform suite: 108 tests / 470 assertions, including direct/indirect members, outside lines, root exclusion, existing membership and legacy-role/config bypass.

## Annual smart website service product
- SKU PACKET-990-ANNUAL, 99000 satang per year, service category, zero PV/stock and inquiry_only=1. Production product ID 300, local supplied poster platform/assets/skynext-smart-website-990.png.
- Add safely with `php spark platform:annual-website`; AnnualWebsitePackage::add is idempotent and preserves existing Admin edits. Backed up the SQLite database before adding.
- Shared product cards/details show annual pricing, service-specific copy, Apply and owner LINE inquiry controls. `/website-package/apply` sends authenticated users to their own platform/billing, or guests to registration with a fixed session intent that returns to billing after signup/login. No invoice/payment is created by GET.
- Physical Commerce checkout rejects this SKU even if stock/inquiry flags are edited. Annual access continues through existing verified PlatformBilling payments; no downline qualification or PV is granted. Existing SKYNEXT annual trial waits until due under existing rules.
- Verified product and authenticated Apply button in browser, guest redirect and category listing over HTTP; platform tests 110 tests / 480 assertions. Screenshot build/annual-website-package.png. Live payment/provider verification was not performed.

## Direct signups holding group 00000
- New registrations without a valid referral enter pending_referrals with group_code=00000 and users.sponsor_id=NULL. This is a virtual holding group, not a users account, and never consumes or replaces a member's immutable sequential Affiliate ID. Existing unassigned accounts are not backfilled automatically.
- Existing valid referral sessions/tokens retain their original attribution; registration GET notices and POST use the same resolver. No referrer falls back to an Admin or to 0005. Signup and member workspace/profile display 00000 while pending.
- Admin-only `/admin/referral_assignments` (sidebar สมาชิก 00000 / กำหนดผู้แนะนำ) lists pending accounts and accepts an active sponsor's Affiliate code. Assignment is transactional, once-only from the queue, records assigned_by/assigned_to/time and an audit event. It cannot reparent already-sponsored accounts, use self/descendants, select an inactive/unknown sponsor, or be called by a non-Admin. The member's Affiliate code and role remain unchanged.
- Assignment does not recalculate historical commissions or grant free access. The existing new-member + 0005 ancestry + verified 100 PV purchase rule still applies; existing SKYNEXT annual enrollment stays external even after assignment under 0005.
- Additive schema installed via workspace:install after database backup. Tests: 116 tests / 517 assertions; isolated browser Admin assigned synthetic member 0006 to 0005 and saw the completed history entry. Production sponsors were not changed.

## U-CALIN and member product prices
- Added SKU SKN-U-CALIN (production ID 301), supplied local image platform/assets/skynext-u-calin.png, retail 159000 satang, member 89000 satang, 100 PV and stock 10000. Supplied nutritional copy remains qualified; no invented dosage, ingredient quantities, registration numbers or medical claims. Labels awaiting confirmation remain so.
- products.member_price_satang is nullable and additive via Store::install. Existing products have no member override. Admin product editor can edit/clear this optional price.
- Active signed-in accounts receive the optional member price, including during trial so a first qualifying purchase can use it. Public cards/detail show retail and member price with a sign-in note. Cart and checkout quote use the authenticated account; Commerce::checkout independently resolves its buyer from the database and freezes the unit price and PV in order_items. Inactive/unknown buyers do not receive member pricing.
- U-CALIN has both physical add-to-cart and owner LINE inquiry buttons. Imported idempotently by build/add-u-calin.php using build/u-calin-product.json. Existing SKU records are preserved on repeated import. SQLite backup taken before schema/product addition.
- Verified isolated browser add-to-cart and checkout summary at 890 baht / 100 PV, production detail/image/stock/contact link; no production purchase or payment created. Tests: 118 tests / 526 assertions. Screenshot build/u-calin-product.png.

## Admin product edit from storefront
- Product detail shows Admin-only Edit Product and product list controls. `/admin/products/edit/{id}` is a dedicated GET/POST controller with explicit active Admin enforcement in addition to PlatformGuard.
- Editor covers name, SKU, category, short/long descriptions, retail/member prices, PV, stock, publication/inquiry settings and a 5 MB JPG/PNG/WebP upload. It shows the existing image and provides Save and Save then View controls, responsive fields and sticky save actions. Hidden products stay in the editor after save. Validation errors retain posted fields using withInput.
- Saves reuse Admin::save for validation, transactions and auditing; optional inquiry/description/member-price fields preserve existing values for legacy submissions. Image validation now also accepts existing safe filenames under platform/assets, preventing supplied product images from blocking edits.
- Isolated browser verified edit link -> prefilled form -> stock update -> saved public detail, then restored fixture stock to 10000. Guest sees no Edit action and is redirected to login on direct editor access. Production product values were not changed by tests. Platform suite: 118 tests / 526 assertions. Screenshot build/product-editor.png.

## Affiliate-only member price visibility
- ProductPricing::eligible centralizes active roles admin, affiliate, downline_team and external_team. Guests and member/sales/manager roles cannot see or receive member prices; Admin retains price management visibility. This supersedes the earlier rule granting member prices to all active accounts.
- Shared product cards/detail render member prices only for eligible authenticated viewers. Product copy filters legacy structured ราคาสมาชิก: lines from ineligible views so a duplicated price in the specification table cannot reveal it.
- Cart and authoritative checkout quote use the same role rule, with the buyer fetched from the database. Public catalog-map remains retail-only. Guest HTTP verified product/catalog contain neither member-price label nor 890; browser verified retail-only view at 1590. Tests cover every role, missing/inactive users and filtered copy.

## Member buyer registration and workspace
- Register offers customer (Member for purchases), new Affiliate, or existing SKYNEXT annual plan. customer is validated server-side to users.role=member; enrollment records new registration without annual opt-in, while role remains the authoritative buyer purpose. Client-supplied role/annual opt-in cannot promote it. Existing users are not reclassified.
- Member workspace contains product browsing, own profile/orders, cart and product payment channels. Own account form updates only authenticated name/phone; orders and slip routes retain owner checks. Member cannot access Affiliate, business workspaces/documents/salespages or platform annual billing. No trial expiry or annual Platform charge applies to buyer accounts. Member owners cannot issue referral links.
- Member receives retail prices and does not see member-price fields/copy. A 100 PV purchase does not auto-promote this role, even when its sponsor is 0005. Direct Member registrations still enter holding group 00000 without changing immutable sequential account codes.
- Isolated browser verified radio-toggle help, customer login/workspace, profile and payment pages, retail-only product and denied Affiliate route. Synthetic customer-browser@example.test is fixture-only. Screenshot build/customer-member-workspace.png.

## Monthly annual website launch offer
- AnnualWebsiteOffer reads explicit monthly approvals in settings.annual_website_promotions. Regular annual price stays 99000 satang; approved months charge 39900 satang. Month boundaries use Asia/Bangkok; no automatic rollover to next month. Production enabled only 2026-10, ending 31 October 2569 / 2026-10-31 17:00:00 UTC.
- Admin-only /admin/annual_promotion and sidebar โปรเว็บไซต์รายเดือน allow enable/disable per selected YYYY-MM with auditing. Production price/product records are unchanged; shared service product/card and billing show effective offer and dated conditions. Existing 30-day trials do not reserve a promo; price applies in the month invoices can be created.
- New invoices freeze the effective amount and expire at the earlier of 24 hours or the promotion month boundary. Existing invoices retain their amount. QR, slip verification and billing history use invoice amounts; approved bank proof remains required. Due renewal orders copy their bound invoice amount.
- Store::migrateAnnualOfferAmounts rebuilds legacy fixed-990 invoice/renewal tables to accept 39900 or 99000, preserving rows, indexes, sequence and foreign-key integrity. SQLite backup taken before production migration.
- Tests: 125 tests / 597 assertions, including customer privilege boundaries, Bangkok month rollover, no auto-renewed discount, frozen invoices, legacy schema preservation and verified fake 399-baht annual payment. No live payment/provider transaction performed. Isolated Admin UI saved November disabled while October stayed active. Screenshots build/annual-launch-promo-399.png and build/admin-monthly-website-promo.png.

## Three supplied homepage hero banners
- Presentation::apply replaces the captured skynextHeroSlider section with platform/home_hero and loads versioned hero-banner.css/js. The old hero markup and old slider script are absent from the rendered homepage; adjacent products/activities retain their existing order. Existing ribbon styling stays in hero-slider.css.
- Three local supplied PNGs: skynext-hero-join.png, skynext-hero-products.png, skynext-hero-smart.png. Artwork remains uncropped at its native 1672:941 ratio. Full-image links go to registration, shop and #sn-smart-capabilities, with compact topic/navigation controls and an additional CTA on narrow screens.
- Autoplay every 3000 ms, pause/resume, previous/next, topic selection, keyboard arrows and swipe. Hover/focus/hidden page suspend autoplay; reduced-motion starts paused. Inactive slides are hidden/inert and removed from tab order.
- Verified on live homepage: all three images loaded, one hero only, autoplay index changed, previous/next/pause controls worked, registration/shop/section links reached correct destinations and no horizontal overflow. PHP lint and JS syntax check passed. Screenshot build/hero-slideshow-desktop.png.

## Corporate blue/silver/gold company hero
- Shared company_hero partial uses the supplied local skynext-corporate-vision.png as decorative artwork with actual HTML heading, subtitle and three CTA links. A navy overlay separates the live text from baked-in poster text; no new identity, job title or experience claim is derived from the portrait.
- About/company replaces its former green hero while preserving subsequent company content. Know Company scrolls to #sn-company-story; Register opens /register; Explore Smart Website opens homepage #sn-smart-capabilities. A small caption labels the supplied artwork as brand illustration.
- Homepage includes this as the fourth slide after the three existing supplied banners. hero-banner.js now derives count/CTA labels from slide metadata and manages every anchor's tab index for multi-CTA slides. No nested section is added inside the hero, preserving Presentation's downstream product relocation logic.
- company-hero.css provides desktop and mobile layouts. Browser verified About's three destinations, homepage 04/04 selection, desktop content fit and the 390px corporate slide's three visible CTA buttons fitting inside its frame. About's mobile page has no horizontal overflow. Existing mirrored homepage header has an unrelated mobile overflow issue; no header changes were made here. Viewport override was reset after checks. PHP lint/JS syntax passed. Screenshot build/company-corporate-hero.png and mobile companion.

## About company Section 2
- Replaced the former unverified quick-stats strip after the corporate hero with company_about_section.php at #sn-company-story. The new introduction uses the user-supplied mission paragraph, four HTML feature cards, a qualitative company/approach/commitment strip and Know More CTA to #sn-company-values.
- Corporate navy, silver and gold design lives in company-about.css with 4/2/1 responsive card columns. The supplied skynext-company-about-concept.png is used only as a cropped portrait illustration; lower numeric statistics are excluded and a caption states that artwork does not verify personal history or company premises. No member/product/province counts or years are asserted in the new section.
- Old unsupported 30+ years/1994, 50+ products, 77 provinces and 10000+ families quick-stat tiles were removed. Later existing company narrative remains intact. Vision/Mission grid minimum size was adjusted to fit small screens.
- Verified on live About page: Section 2 follows Hero, four cards, no unverified numeric claims in section text, CTA reaches existing values content, and 390px layout has no horizontal overflow. PHP lint passed. Screenshots build/company-about-section-two.png and mobile companion.

## About company Section 3: Vision, Mission, Core Values
- company_values_section.php replaces the old green Vision/Mission and SKYNEXT-acronym values blocks at the existing #sn-company-values anchor. The About page now has Hero, About Section 2, then this Section 3 without duplicated values content. Later executive/contact blocks remain unchanged.
- Supplied Vision is HTML text, Mission is a four-item ordered list, and Core Values are five HTML cards: Integrity, Quality, Innovation, Teamwork and Sustainability. Corporate navy/gold/white presentation is isolated in company-values.css, with responsive stacked layouts.
- The supplied skynext-company-vision-concept.png is a cropped decorative portrait; its baked-in experience badge is covered/excluded, and no name, position, years or earnings guarantee is derived from the artwork. Existing site logo is displayed separately.
- Live browser verified section order, 4 mission items, 5 values cards, and no desktop or 390px mobile horizontal overflow. Mobile values grid is one column. Viewport reset after verification; PHP lint passed. Screenshots build/company-vision-mission-values.png and mobile companion.

## About company Section 5: Corporate Trust
- Added company_trust_section.php after the executive block, with scoped company-trust.css in navy/gold/white. HTML headline, commitment panel, three document-status cards, official DBD/OCPB links, ethics link and contact CTA.
- Existing about_documents.php contains placeholder IDs and alert-only previews; it was not used as evidence or linked as verified documentation. New section explicitly awaits authentic registration, direct-selling and product certification records. No mock certificate, FDA Approved, GMP/HACCP seal or unverified number from the supplied design is displayed.
- Official verification destinations checked against DBD/OCPB sources. PHP lint passed, three cards rendered, desktop and 390px mobile have no horizontal overflow. Inquiry CTA reached existing LINE contact destination; viewport reset. Screenshots build/company-trust-section.png and build/company-trust-mobile.png.

## About company final section: Products & Innovation
- Added company_products_section.php as the last content element before the shared footer, with scoped company-products.css. User-supplied full product artwork stored as platform/assets/skynext-products-overview.png; responsive image preserves its full composition.
- HTML headline, four neutral category cards (health/agriculture/beauty/lifestyle), and closing navy/gold CTA link to actual shop/contact routes. Artwork caption directs visitors to current product listings rather than implying every pictured product is available.
- PHP lint passed; browser confirmed image loads at natural width 1672, final-section placement, four cards, and no overflow on desktop/390px mobile. Shop CTA navigated to the live catalog. Viewport reset. Screenshots build/company-products-final.png and mobile companion.

## Unified homepage header spacing
- Presentation now renders platform/site_header on the homepage and loads the same versioned site-header.css/js as other public pages. Removed the duplicate homepage-only referrer-card injection because the shared header already renders it. Existing topbar and hero are retained.
- Live comparison at 1440px confirmed identical homepage/shop header geometry: 1400px container, 72px height, 24px horizontal padding. Homepage shop navigation works. At 390px the shared menu opens/closes correctly, header is 68px high, and the page has no horizontal overflow. PHP lint passed; viewport reset. Screenshots build/header-spacing-desktop.png and build/header-spacing-mobile.png.

## Company documents page: sourced company registration
- Replaced legacy mock verified document cards, fake registration placeholders, unsupported certifications and alert-only previews with sourced registration details, six official-document request categories, DBD links, Company Profile draft status and existing contact/ethics routes.
- DataForThai company/0605557001314 checked 2026-10-26: current name, operating status, original registration 2014-12-01, name change 2026-02-11, capital 1000000 THB and Khu Khot/Lam Luk Ka address. Page identifies this secondary source and requires latest DBD certificate for official use. DBD e-Service and certificate inquiry services checked against official sources.
- No Word/PDF supplied or found, so no pretend downloads or claims that six-page documents were generated. Profile summary labels AI development/trial and management-review status. Documents controller description no longer asserts unsupported licenses/certifications.
- PHP lint passed. Browser confirms six cards, zero onclick alert previews, functional registry anchor and no desktop/390px mobile overflow. Screenshots build/company-documents-page.png and mobile companion; viewport reset.

## Product user experiences custom blog
- Added /about/product-experiences and submenu in the shared About dropdown (also used by homepage). Reuses blog publishing, article detail/cards and search, with fixed Blog::EXPERIENCE_CATEGORY category; query-string category cannot escape this collection. Existing general blog remains unchanged.
- Admin blog category input now offers the canonical experience category through a datalist, while preserving free-text categories and existing records. Publishing in that category automatically adds the article; drafts, archived and future posts stay hidden. Experience article breadcrumb returns to the collection.
- No production experience articles currently exist; no testimonials were fabricated or unrelated existing articles recategorized. Empty/search states are explicit. Scoped hero styling added; shared blog CSS is versioned to invalidate stale caches.
- BlogTest passes 7 tests/31 assertions including isolated experience filtering, draft/future exclusions and admin category suggestion. PHP lint passed. Live browser verified homepage submenu navigation, scoped search, empty state, updated style, and no desktop/390px overflow. Screenshots build/product-experiences-page.png and mobile companion; viewport reset.

## Annual package external-member price wording
- annual_website_offer.php now always displays the target audience (SKYNEXT INTER members from other teams) and regular price 990 THB per annual service cycle. Active monthly 399 THB promotion remains secondary with its existing expiry/trial rules; no billing or promotion settings changed.
- PHP lint and live product/300 text verified. Screenshot build/annual-price-external-members.png.

## Removed fixed monthly annual-service promotion
- AnnualWebsiteOffer now reads PACKET-990-ANNUAL product.price_satang and ignores legacy monthly settings. Removed fixed promotional amount, monthly activation UI and public promotion wording. Legacy admin/annual_promotion redirects authorized Admins to the package product editor.
- Product price and annual audience banner read the product directly. Billing and newly scheduled renewal orders use catalog price; existing invoice snapshots/history are preserved. Removed promotional description rewriting. Current catalog price remains 990 THB; no product price changed.
- Billing/renewal amount CHECK constraints expanded to positive amounts via existing transactional migration, preserving indexes, triggers, sequences and foreign-key baseline. Live backup writable/backups/before-catalog-annual-pricing-20261026-233953.sqlite; applied workspace:install without product import.
- Full platform suite passes 125 tests/599 assertions, including catalog-price changes, invoice snapshot preservation, legacy schema migration and exact bank-proof verification at a custom amount. PHP lint passed. Live product/300 contains neither old price nor promotion wording, and displays 990.00 THB/year. Screenshot build/annual-price-from-product.png.
