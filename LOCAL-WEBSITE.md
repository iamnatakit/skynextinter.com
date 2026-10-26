# Local copy of laemtongagri.com

The public pages and their assets are saved in this CodeIgniter 4 project.

Start Apache in XAMPP on port 88, then check from this directory:

```powershell
.\tools\start-platform.ps1
```

Open http://localhost:88/skynextinter.com/public/ . The development base URL is configured in `.env`.
For an Apache virtual host, set its document root to this project's `public`
directory and update `app.baseURL` in `.env` to match its URL.

- `writable/mirror/pages/`: locally rewritten HTML pages.
- `writable/mirror/raw/`: original public HTML responses.
- `public/mirror/assets/`: downloaded images, stylesheets, scripts, and fonts.
- `writable/mirror/manifest.json`: source URL mapping, capture date, and failed downloads.
- `tools/mirror_site.py`: repeatable public download and link conversion script.
- `tools/complete_mirror.py`: completes the capture, including on-demand Elementor and Blocksy JavaScript modules and their original runtime paths.
- `tools/validate_mirror.py`: checks saved assets, CSS dependencies, and the HTTP status and page title of every captured page.
- `writable/mirror/validation.json`: latest validation results.
- `app/Controllers/Mirror.php`: serves the captured pages through CodeIgniter.

This is a presentation snapshot. The original WordPress PHP files, database,
admin system, customer accounts, orders, live stock, and payment integrations
cannot be obtained from a public URL. Forms are prevented from submitting.
External links and embedded third-party services may still require the Internet.
The manifest lists dependencies that could not be downloaded.

To refresh the snapshot:

```powershell
python tools/complete_mirror.py
```

With the local server running, validate the copy:

```powershell
python tools/validate_mirror.py
```

The capture contains 256 public pages and 1,579 mapped assets (about 244 MiB,
excluding runtime aliases). Thai URLs and multi-segment product/category URLs
are supported by the application's routing configuration. Links, product
descriptions, prices, and images represent the captured site, not live data.
Server-backed search, sorting, wishlist/cart, and AJAX integrations are not
implemented by this presentation snapshot.

The downloader follows public sitemap and page links without submitting forms,
adding items to carts, logging in, or crawling search/filter combinations.

## Live local business platform

An independent database-backed store and administration platform has now been
added at `/shop` and `/admin`. It supplies carts, accounts, promotions, orders,
manual payment review, CRM, leads, affiliate links, opt-in analytics and chat.
Captured product links connect to the live store through the local widget.
See [PLATFORM.md](PLATFORM.md) for setup, administrator access, verification and
the status of external payment/AI/messaging integrations. Use
a writable private `upload_tmp_dir` in Apache PHP configuration when uploads are unavailable.
