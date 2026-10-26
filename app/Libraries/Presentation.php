<?php
namespace App\Libraries;

/** Apply presentation adjustments to mirrored pages. */
class Presentation
{
    public static function apply(string $html, string $path): string
    {
        helper('platform');
        $html=preg_replace_callback('~<div class="projectfield-topbar"[^>]*>.*?(?=<header\b)~s',static function(array $bar):string{
            return preg_replace_callback('~<a\b[^>]*>.*?</a>~s',static function(array $link):string{
                $label=trim(strip_tags($link[0]));$channel=str_contains($label,'LINE Official')?'line':(str_contains($label,'Facebook')?'facebook':null);
                if($channel===null){return $link[0];}
                return preg_replace('~href="[^"]*"~','href="'.esc(p_affiliate_contact_url($channel),'attr').'" rel="noopener noreferrer"',$link[0],1);
            },$bar[0]);
        },$html,1);
        $html=preg_replace_callback(
            '~(<div class="projectfield-topbar"[^>]*>\s*)<div\b[^>]*>.*?</div>~s',
            static fn(array $match):string=>$match[1].'<div style="min-width:0; line-height:1.7;">ระบบ Online ทำงานแทนคุณแบบอัตโนมัติ 24 ชั่วโมง ไม่ว่าคุณอยู่มุมไหนของโลก</div>',
            $html,1
        );
        // Use crisp SVGs for the feature ribbon, independent of icon fonts.
        $featureIcons = [
            'leaf' => '<path d="M20 4c-8-1-14 2-14 8a6 6 0 0 0 6 6c6 0 9-6 8-14Z"/><path d="m4 21 12-12M9 16v-5m0 5h5"/>',
            'truck' => '<path d="M3 6h11v11H3zM14 10h4l3 4v3h-7"/><path d="M14 14h7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
            'handshake-o' => '<path d="m3 8 4-3 4 2 3-1 7 4-4 8-3 1-7-4-4-7Z"/><path d="m11 7-3 4 2 2 4-3 5 4M7 15l2 3 3 2 2-1M3 8l-2 5 4 2m16-5 2 3-3 3"/>',
            'user-circle' => '<path d="M4 13v-2a8 8 0 0 1 16 0v2M5 12H3v6h3v-6H5Zm14 0h2v6h-3v-6h1ZM19 18c0 3-3 3-6 3"/><circle cx="12" cy="9" r="3"/><path d="M8 17c0-5 8-5 8 0"/>',
        ];
        $html = preg_replace_callback(
            '~(<div class="hero-feature-icon"[^>]*>)\s*<i class="fa fa-([a-z-]+)"></i>~',
            static fn(array $match): string => $match[1] . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($featureIcons[$match[2]] ?? '') . '</svg>',
            $html
        );
        $html = str_replace(base_url('platform/hero-slider.css') . '"', base_url('platform/hero-slider.css?v=' . filemtime(FCPATH . 'platform/hero-slider.css')) . '"', $html);
        // Replace the complete captured hero with the local campaign banners and shared corporate hero.
        if(str_contains($html,'id="skynextHeroSlider"')){
            $html=preg_replace_callback('~<section\b[^>]*\bid="skynextHeroSlider"[^>]*>.*?</section>~s',static fn()=>view('platform/home_hero'),$html,1);
            $html=str_replace('</head>','<link rel="stylesheet" href="'.base_url('platform/hero-banner.css?v='.filemtime(FCPATH.'platform/hero-banner.css')).'"></head>',$html);
            $html=str_replace('</head>','<link rel="stylesheet" href="'.base_url('platform/company-hero.css?v='.filemtime(FCPATH.'platform/company-hero.css')).'"></head>',$html);
            $html=str_replace(base_url('platform/hero-slider.js'),base_url('platform/hero-banner.js?v='.filemtime(FCPATH.'platform/hero-banner.js')),$html);
        }
        // Replace the captured homepage cards with current published articles.
        if (preg_match('~<div\b[^>]*\bid="Component6a5db91d8b722c0012b37523"[^>]*>~i', $html, $start, PREG_OFFSET_CAPTURE)) {
            $offset = $start[0][1];
            preg_match_all('~</?div\b[^>]*>~i', substr($html, $offset), $tags, PREG_OFFSET_CAPTURE);
            $depth = 0;
            foreach ($tags[0] as [$tag, $position]) {
                $depth += str_starts_with($tag, '</') ? -1 : 1;
                if ($depth === 0) {
                    $store = new \App\Libraries\Platform\Store();
                    $articles = $store->installed() ? (new \App\Libraries\Platform\Blog($store))->latest() : [];
                    $html = substr_replace($html, view('platform/home_articles', ['articles' => $articles]), $offset, $position + strlen($tag));
                    break;
                }
            }
        }
        // Show up to nine current products, keeping the updated formulas first.
        if (preg_match('~<div\b[^>]*\bid="Component6a5dd4c190e46400136ee4a0"[^>]*>~i', $html, $start, PREG_OFFSET_CAPTURE)) {
            $offset = $start[0][1];
            preg_match_all('~</?div\b[^>]*>~i', substr($html, $offset), $tags, PREG_OFFSET_CAPTURE);
            $depth = 0;
            foreach ($tags[0] as [$tag, $position]) {
                $depth += str_starts_with($tag, '</') ? -1 : 1;
                if ($depth === 0) {
                    $store = new \App\Libraries\Platform\Store();
                    $skus = ['SKN-RED-TRICHODERMA-HUMIC-50KG', 'SKN-HUMIC-AMINO-SILICON-50KG', 'SKN-RUBBER-HUMIC-50KG'];
                    $products = $store->installed() ? $store->all('SELECT * FROM products WHERE active=1 ORDER BY CASE sku WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END, id DESC LIMIT 9', $skus) : [];
                    $html = substr_replace($html, view('platform/home_products', ['products' => $products]), $offset, $position + strlen($tag));
                    break;
                }
            }
        }
        $footerStyles = '<link rel="stylesheet" href="' . base_url('platform/site-footer.css?v=' . filemtime(FCPATH . 'platform/site-footer.css')) . '">'
            . '<link rel="stylesheet" href="' . base_url('platform/home-activities.css?v=' . filemtime(FCPATH . 'platform/home-activities.css')) . '">'
            . '<link rel="stylesheet" href="' . base_url('platform/products.css?v=' . filemtime(FCPATH . 'platform/products.css')) . '">';
        $html = str_replace('</head>', $footerStyles . '</head>', $html);
        $latestMembers=str_contains($html,'id="smart-business-section"')?view('platform/home_recent_members'):'';
        $html = str_replace('</body>', $latestMembers . view('platform/site_footer') . '</body>', $html);
        if (str_contains($html, 'id="smart-business-section"') && !str_contains($html, 'id="sn-partner-benefits"')) {
            $html = preg_replace_callback(
                '~<div\s+id="contentnewmode6a5d8b138b722c0012b36643"[^>]*>~',
                static fn(array $match): string => view('platform/partner_benefits') . $match[0],
                $html,
                1
            );
        }
        if (str_contains($html, 'id="smart-business-section"') && !str_contains($html, 'id="smart-platform-section"')) {
            $html = str_replace('<!-- Lightbox Modal for Homepage -->', view('platform/smart_platform_section') . "\n<!-- Lightbox Modal for Homepage -->", $html);
        }
        if (str_contains($html, 'id="smart-business-section"') && !str_contains($html, 'id="sn-smart-capabilities"')) {
            $html = preg_replace_callback(
                '~<div\s+id="contentnewmode6a5d8e868b722c0012b36798"[^>]*>~',
                static fn(array $match): string => view('platform/smart_capabilities') . $match[0],
                $html,
                1
            );
        }
        $html = str_replace('platform/uploads/95c7036068f286157c427067e73bf0e9.jpg', 'platform/assets/skynext-smart-business.png', $html);
        // The infographic contains painted buttons. Add real, keyboard-accessible
        // links over those regions while retaining the image's zoom behaviour.
        $html = preg_replace_callback(
            '~<img\b[^>]*\bsrc=["\'][^"\']*platform/assets/skynext-smart-business\.png["\'][^>]*>~i',
            static fn(array $match): string => view('platform/infographic_actions', ['image' => $match[0]]),
            $html
        );

        // Place the complete recommendations block immediately after the hero.
        if (str_contains($html, 'id="skynextHeroSlider"') && preg_match('~<section\b[^>]*\bid="sn-home-products"[^>]*>.*?</section>~s', $html, $recommendations, PREG_OFFSET_CAPTURE)) {
            $section = $recommendations[0][0];
            $html = substr_replace($html, '', $recommendations[0][1], strlen($section));
            // The former heading belongs to the moved section, not its old position.
            if (preg_match('~<div\b[^>]*\bid="contentnewmode6a5d8e868b722c0012b36798"[^>]*>~i', $html, $heading, PREG_OFFSET_CAPTURE)) {
                $offset = $heading[0][1];
                preg_match_all('~</?div\b[^>]*>~i', substr($html, $offset), $tags, PREG_OFFSET_CAPTURE);
                $depth = 0;
                foreach ($tags[0] as [$tag, $position]) {
                    $depth += str_starts_with($tag, '</') ? -1 : 1;
                    if ($depth === 0) {
                        $html = substr_replace($html, '', $offset, $position + strlen($tag));
                        break;
                    }
                }
            }
            $html = preg_replace_callback('~<section\b[^>]*\bid="skynextHeroSlider"[^>]*>.*?</section>~s', static fn(array $hero): string => $hero[0] . "\n" . $section . "\n" . view('platform/home_activity_highlights'), $html, 1);
        }

        // 1. Clean up leftover AngularJS template expressions in text
        $html = preg_replace('/\{\{\s*langContentmanager\[0\]\.Value\.Post\s*\}\}/', 'โพสต์', $html);

        // 2. Ensure "Affiliate" is present in the main navigation linking to login
        if (!str_contains($html, '>Affiliate<') && !str_contains($html, 'href="' . base_url('login') . '"')) {
            $affiliateLink = '<a href="' . base_url('login') . '" style="color: #333; text-decoration: none; font-size: 15px; padding: 6px 0;">Affiliate</a>';
            $html = preg_replace(
                '/(<a\s+[^>]*href=["\'][^"\']*\/shop["\'][^>]*>.*?<\/a>)/us',
                '$1' . "\n          " . $affiliateLink,
                $html,
                1
            );
        }

        // 3. Update header branding and logo to SkyNext Inter
        $html = str_replace(
            ['PROJECT FIELD', 'บริษัท โปรเจ็คฟิลด์ จำกัด | เคียงข้างเกษตรกรไทย', 'บริษัท โปรเจ็คฟิลด์ จำกัด'],
            ['SKYNEXT INTER', 'สกายเน็กซ์ | เคียงคู่เกษตรกรไทย', 'สกายเน็กซ์ | เคียงคู่เกษตรกรไทย'],
            $html
        );
        $html = str_replace(
            ['mirror/assets/fav-icon.png'],
            ['platform/assets/skynext-logo.png'],
            $html
        );

        // 4. Normalize navigation menu labels to be clean and consistent
        $html = str_replace(
            ['<i class="fa fa-shopping-bag"></i> ร้านค้าออนไลน์ (Shop)', 'ร้านค้าออนไลน์ (Shop)', '<i class="fa fa-handshake-o"></i> Affiliate'],
            ['ร้านค้าออนไลน์', 'ร้านค้าออนไลน์', 'Affiliate'],
            $html
        );

        // 5. Update ผลิตภัณฑ์ to เกี่ยวกับสกายเน็กซ์ and its submenus
        $html = str_replace(
            ['>ผลิตภัณฑ์ <i class="fa fa-angle-down"></i><', '>ผลิตภัณฑ์<'],
            ['>เกี่ยวกับสกายเน็กซ์ <i class="fa fa-angle-down"></i><', '>เกี่ยวกับสกายเน็กซ์<'],
            $html
        );
        $html = preg_replace(
            '/href=["\'][^"\']*ผลิตภัณฑ์\/6a585ac3a89624001409822a["\']/u',
            'href="' . base_url('about') . '"',
            $html
        );
        if (str_contains($html, 'สารป้องกันกำจัดแมลง')) {
            $newDropdown = '<a href="' . base_url('about/company') . '" style="display: block; padding: 10px 18px; color: #e2e8f0; text-decoration: none; font-size: 14.5px; border-bottom: 1px solid rgba(255, 255, 255, 0.06);">เกี่ยวกับบริษัท</a>' . "\n"
                . '              <a href="' . base_url('about/marketing-plan') . '" style="display: block; padding: 10px 18px; color: #e2e8f0; text-decoration: none; font-size: 14.5px; border-bottom: 1px solid rgba(255, 255, 255, 0.06);">แผนการตลาด</a>' . "\n"
                . '              <a href="' . base_url('about/documents') . '" style="display: block; padding: 10px 18px; color: #e2e8f0; text-decoration: none; font-size: 14.5px;">เอกสารเกี่ยวกับบริษัท</a>' . "\n"
                . '              <a href="' . base_url('about/business-ethics') . '" style="display: block; padding: 10px 18px; color: #e2e8f0; text-decoration: none; font-size: 14.5px;">จรรยาบรรณของนักธุรกิจ</a>';
            $html = preg_replace(
                '/<div class="pf-dropdown-menu"[^>]*>.*?<\/div>/s',
                '<div class="pf-dropdown-menu" style="position: absolute; top: 100%; left: 0; background: rgba(11, 46, 23, 0.98); border: 1px solid rgba(74, 222, 128, 0.25); box-shadow: 0 12px 30px rgba(0,0,0,0.45); border-radius: 8px; padding: 6px 0; min-width: 230px; display: none; z-index: 1001; backdrop-filter: blur(12px);">' . "\n              " . $newDropdown . "\n            </div>",
                $html,
                1
            );
        }

        // 6. Normalize activities, blog, and contact URLs
        $html = str_replace(
            [
                'กิจกรรม/6a585ac9a89624001409823b',
                'บทความ/6a585acca89624001409824b',
                'ติดต่อเรา/6a585ad0a89624001409825f',
            ],
            [
                'activities',
                'blog',
                'contact-us',
            ],
            $html
        );

        // 4. Hide leftover static loading backdrops, uncompiled template repeaters, and unused gallery types
        $override = <<<'CSS'
<style>
  /* Retire the original mirrored footer and its spacer components. */
  .homepage-content-container:has(#sn-partner-benefits) > #contentnewmode6a5d8b138b722c0012b36643,
  .homepage-content-container:has(#sn-partner-benefits) > #contentnewmode6a5d8b378b722c0012b36684,
  .homepage-content-container:has(#sn-partner-benefits) > #Component6a5d8a97ea77390013e5bcfa,
  #mainmenu6a5d8b2f8b722c0012b36679,
  #mainmenu6a5efc588b722c0012b38c22,
  #mainmenu6a5ef469ea77390013e5d9fe,
  #HtmlTool6a5efc588b722c0012b38c22,
  #HtmlTool6a5ef469ea77390013e5d9fe,
  .space-footer-top, .space-footer-bottom { display: none !important; }
  /* Remove the retired company-history and category blocks from the homepage. */
  .homepage-content-container > #mainmenu6a5d8e728b722c0012b36758,
  .homepage-content-container > #contentnewmode6a585b0547d3510013ead455,
  .homepage-content-container > #contentnewmode6a5d92db77fcb300136966b4 { display: none !important; }
  .sn-infographic-crop { position: relative; overflow: hidden; }
  .sn-infographic-actions { position: relative; width: 100%; }
  .sn-infographic-action { position: absolute; z-index: 2; display: block; border-radius: 8px; cursor: pointer; }
  .sn-infographic-action:hover { background: rgba(255, 255, 255, .14); }
  .sn-infographic-action:focus-visible { outline: 3px solid #fff; box-shadow: 0 0 0 6px #176136; outline-offset: 2px; }
  .sn-infographic-action span { position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
  .sn-infographic-join-top { left: 7%; top: 29.7%; width: 18.5%; height: 6.5%; }
  .sn-infographic-workflow { left: 26.6%; top: 29.7%; width: 12.8%; height: 6.5%; }
  .sn-infographic-join-bottom { left: 41.3%; top: 91.6%; width: 17.5%; height: 6.3%; }
  .sn-infographic-mobile-join { display: none; }
  @media (max-width: 640px) {
    .sn-infographic-mobile-join { display: flex; align-items: center; justify-content: center; min-height: 48px; margin: 12px; padding: 12px 20px; border-radius: 8px; background: #d6ad50; color: #173521; font-size: 16px; font-weight: 700; text-decoration: none; }
  }
  /* === Premium Emerald Agricultural Header Background & Design === */
  .projectfield-topbar {
    background: linear-gradient(90deg, #072612 0%, #0d381c 50%, #164e28 100%) !important;
    border-bottom: 1px solid rgba(74, 222, 128, 0.25) !important;
  }

  .projectfield-header {
    background: 
      url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cpath d='M15 55 C 26 30, 52 22, 65 35 C 52 50, 26 58, 15 55 Z' fill='none' stroke='%2322c55e' stroke-width='1' stroke-opacity='0.05'/%3E%3Cpath d='M15 55 Q 38 43 65 35' fill='none' stroke='%2322c55e' stroke-width='0.6' stroke-opacity='0.05'/%3E%3C/svg%3E"),
      radial-gradient(circle at 12% 40%, rgba(74, 222, 128, 0.14) 0%, transparent 40%),
      radial-gradient(circle at 88% 70%, rgba(245, 158, 11, 0.08) 0%, transparent 35%),
      linear-gradient(135deg, #072612 0%, #0c3e1e 35%, #135328 70%, #1b6633 100%) !important;
    border-bottom: 2px solid rgba(74, 222, 128, 0.35) !important;
    box-shadow: 0 4px 22px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 1000 !important;
    backdrop-filter: blur(10px) !important;
  }

  .projectfield-header > div {
    max-width: 1480px !important;
    padding: 10px 24px !important;
    gap: 16px !important;
  }

  /* Unify All Homepage Containers Scale to 1480px */
  .hero-container,
  .hero-features-container,
  #smart-business-section > div,
  .homepage-content-container,
  .container {
    max-width: 1480px !important;
    width: 100% !important;
    margin-left: auto !important;
    margin-right: auto !important;
    box-sizing: border-box !important;
  }

  .projectfield-page .projectfield-main-wrapper > div:not(.skynext-hero-slider):not(.hero-features-ribbon):not(#smart-business-section):not(#sn-home-lightbox),
  .projectfield-page .projectfield-main-wrapper > .Override,
  .projectfield-page .CASS-JaikothaiHead-border,
  .projectfield-page .CASS-A-border,
  .projectfield-page .CASS-B-border,
  .projectfield-page .CASS-D-border,
  .projectfield-page .CASS-E-border,
  .projectfield-page .CASS-H-border,
  .projectfield-page .CASS-GrayBackground-border,
  .projectfield-page .CASS-BlackHead-border {
    max-width: 1480px !important;
    margin-left: auto !important;
    margin-right: auto !important;
    box-sizing: border-box !important;
  }

  .projectfield-header img[src*="skynext-logo"],
  .projectfield-header img[src*="fav-icon"] {
    background: #ffffff !important;
    padding: 3px 6px !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.25) !important;
    height: 44px !important;
    width: auto !important;
    object-fit: contain !important;
  }


  .projectfield-header .pf-brand-name,
  .projectfield-header a:first-child div > div:first-child {
    color: #ffffff !important;
    font-weight: 800 !important;
    letter-spacing: 0.8px !important;
    text-shadow: 0 2px 4px rgba(0,0,0,0.35) !important;
    white-space: nowrap !important;
  }

  .projectfield-header .pf-brand-desc,
  .projectfield-header a:first-child div > div:last-child {
    color: #bbf7d0 !important;
    letter-spacing: 0.2px !important;
    white-space: nowrap !important;
  }

  /* === Unified Navigation Menu === */
  .projectfield-header nav {
    gap: 4px !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
  }

  .projectfield-header nav > a,
  .projectfield-header nav .pf-dropdown > a,
  .projectfield-header .pf-nav-item,
  .projectfield-header nav a:not([href*="/cart"]):not(.pf-cart-btn) {
    color: #f1f5f9 !important;
    text-decoration: none !important;
    font-size: 15px !important;
    font-weight: 500 !important;
    padding: 8px 14px !important;
    border-radius: 8px !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 5px !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
  }

  .projectfield-header nav > a:hover,
  .projectfield-header nav .pf-dropdown > a:hover,
  .projectfield-header .pf-nav-item:hover,
  .projectfield-header nav a:not([href*="/cart"]):not(.pf-cart-btn):hover {
    color: #4ade80 !important;
    background: rgba(255, 255, 255, 0.12) !important;
    transform: translateY(-1px) !important;
  }

  .projectfield-header nav > a.is-active,
  .projectfield-header .pf-nav-item.is-active,
  .projectfield-header nav a:not([href*="/cart"]):not(.pf-cart-btn).is-active {
    color: #4ade80 !important;
    background: rgba(74, 222, 128, 0.14) !important;
    font-weight: 600 !important;
  }

  /* Cart Action Button */
  .projectfield-header a[href*="/cart"],
  .projectfield-header .pf-cart-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    border-radius: 20px !important;
    font-weight: 600 !important;
    font-size: 14px !important;
    padding: 7px 18px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 7px !important;
    margin-left: 8px !important;
    text-decoration: none !important;
    white-space: nowrap !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
  }
  .projectfield-header a[href*="/cart"]:hover,
  .projectfield-header .pf-cart-btn:hover {
    background: linear-gradient(135deg, #34d399 0%, #10b981 100%) !important;
    color: #ffffff !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.5) !important;
  }

  /* Dropdown Menu */
  .pf-dropdown {
    position: relative !important;
    display: inline-block !important;
  }
  .pf-dropdown::after {
    content: '' !important;
    position: absolute !important;
    top: 100% !important;
    left: 0 !important;
    right: 0 !important;
    height: 8px !important;
  }
  .pf-dropdown:hover .pf-dropdown-menu,
  .pf-dropdown:focus-within .pf-dropdown-menu,
  .pf-dropdown.is-open .pf-dropdown-menu {
    display: block !important;
  }
  .pf-dropdown-menu {
    background: rgba(11, 46, 23, 0.98) !important;
    border: 1px solid rgba(74, 222, 128, 0.25) !important;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45) !important;
    border-radius: 8px !important;
    padding: 6px 0 !important;
    min-width: 230px !important;
    backdrop-filter: blur(12px) !important;
    position: absolute !important;
    top: 100% !important;
    left: 0 !important;
    z-index: 1001 !important;
  }
  .pf-dropdown-menu a {
    display: block !important;
    padding: 10px 18px !important;
    color: #e2e8f0 !important;
    text-decoration: none !important;
    font-size: 14px !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
    transition: all 0.15s ease !important;
  }
  .pf-dropdown-menu a:last-child {
    border-bottom: none !important;
  }
  .pf-dropdown-menu a:hover {
    background: rgba(34, 197, 94, 0.22) !important;
    color: #4ade80 !important;
    padding-left: 22px !important;
  }
  /* Suppress CMS backdrops and loaders */
  .loadingFilter, .modal-backdrop, .loading-wrap, [id*="Loading"], .itopplusshowdropzoneHide {
    display: none !important;
  }

  /* Suppress unrendered AngularJS template repeater blocks and unused alternative gallery types */
  [ng-repeat],
  [bo-if]:not([bo-if="Images.imgType == 1"]),
  component-pagination,
  .carousel-indicators.hidden,
  .camera_wrap,
  .rslides-pager,
  .navPaperContainer,
  .slidermaster,
  .callbacks_container2,
  .CustomImageGalleryType_2,
  .bootstrap-collum-text,
  .image-one,
  .spritezoom,
  .ele-zoom,
  .image_gallery_template_slide,
  .itp-slide-gallery-thumb,
  [id*="ImagesLoading"] {
    display: none !important;
  }

  /* Hide empty carousels that don't have items */
  .carousel:not(:has(.item)),
  .carousel-inner:empty {
    display: none !important;
  }

  /* Ensure active carousel slide displays properly */
  .carousel .item.active {
    display: block !important;
  }
  .carousel .item:not(.active) {
    display: none !important;
  }

  /* Fix Hero Banner layout: prevent jump or overflow */
  .carousel .imageCarousel {
    width: 100% !important;
    height: auto !important;
    max-width: 100% !important;
    display: block !important;
  }

  /* Count read container hide */
  .countReadContainer {
    display: none !important;
  }
</style>
CSS;

        $html = preg_replace_callback('~<a\b[^>]*>\s*Affiliate\s*</a>~i', static fn(): string => view('platform/affiliate_menu'), $html, 1);
        $affiliateAssets = '<link rel="stylesheet" href="' . base_url('platform/affiliate-menu.css?v=' . filemtime(FCPATH . 'platform/affiliate-menu.css')) . '">'
            . '<script src="' . base_url('platform/affiliate-menu.js?v=' . filemtime(FCPATH . 'platform/affiliate-menu.js')) . '" defer></script>';
        $photoAssets='<link rel="stylesheet" href="'.base_url('platform/profile-photo.css?v='.filemtime(FCPATH.'platform/profile-photo.css')).'">';
        // Share header spacing and responsive controls with the other public pages.
        $html=preg_replace_callback('~<header\b[^>]*class="[^"]*\bprojectfield-header\b[^"]*"[^>]*>.*?</header>~s',static fn():string=>view('platform/site_header',['home'=>false]),$html,1);
        $headerAssets='<link rel="stylesheet" href="'.base_url('platform/site-header.css?v='.filemtime(FCPATH.'platform/site-header.css')).'">'
            .'<script src="'.base_url('platform/site-header.js?v='.filemtime(FCPATH.'platform/site-header.js')).'" defer></script>';
        return str_replace('</head>', $override . $headerAssets . $affiliateAssets . $photoAssets . '</head>', $html);
    }
}
