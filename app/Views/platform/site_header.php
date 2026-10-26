<?php
helper('platform');
$home = $home ?? false;
$publicPath = trim(service('request')->getUri()->getRoutePath(), '/');
$menu = [
    ['', 'หน้าแรก'],
    [
        'label' => 'เกี่ยวกับสกายเน็กซ์',
        'route' => 'about',
        'sub' => [
            ['about/company', 'เกี่ยวกับบริษัท'],
            ['about/product-experiences', 'ประสบการณ์ผู้ใช้สินค้า'],
            ['about/marketing-plan', 'แผนการตลาด'],
            ['about/documents', 'เอกสารเกี่ยวกับบริษัท'],
            ['about/business-ethics', 'จรรยาบรรณของนักธุรกิจ'],
        ],
    ],
    ['shop', 'ร้านค้าออนไลน์'],
    ['affiliateMenu' => true],
    ['activities', 'กิจกรรม'],
    ['blog', 'บทความ'],
    ['contact-us', 'ติดต่อเรา'],
];
$icons = [
    'search' => '<circle cx="10.8" cy="10.8" r="7.3"/><path d="m16.1 16.1 5 5"/>',
    'heart' => '<path d="M20.9 4.6a5.4 5.4 0 0 0-7.6 0L12 5.9l-1.3-1.3a5.4 5.4 0 0 0-7.6 7.6L12 21l8.9-8.8a5.4 5.4 0 0 0 0-7.6Z" fill="currentColor" stroke="none"/>',
    'user' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="9" r="2.8"/><path d="M6.2 18.4c.8-3 2.6-4.2 5.8-4.2s5 1.2 5.8 4.2"/>',
    'cart' => '<path d="m7 8 3-5m7 5-3-5M3 8h18l-2 12H5L3 8Z"/><path d="M8 11v6m4-6v6m4-6v6"/>',
];
$svg = static fn(string $name): string => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $icons[$name] . '</svg>';
?>
<header class="sn-site-header <?= $home ? 'sn-home-header' : '' ?>" id="sn-site-header">
    <div class="sn-header-inner">
        <a class="sn-brand" href="<?= site_url() ?>" aria-label="SKYNEXT INTER หน้าแรก" style="display:flex; align-items:center; gap:12px; width:auto; text-decoration:none;">
            <div style="background:#ffffff; padding:4px 8px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.25); display:flex; align-items:center;"><img src="<?= base_url('platform/assets/skynext-logo.png') ?>" alt="SkyNext Inter" style="height:44px; width:auto; object-fit:contain;"></div>
            <div class="sn-brand-copy" style="display:flex; flex-direction:column; text-align:left;">
                <span class="sn-brand-title" style="font-weight:800; font-size:22px; color:#ffffff; letter-spacing:0.8px; line-height:1.2; text-shadow:0 2px 4px rgba(0,0,0,0.35); white-space:nowrap;">SKYNEXT INTER</span>
                <small class="sn-brand-sub" style="font-size:12px; color:#bbf7d0; letter-spacing:0.2px; white-space:nowrap;">สกายเน็กซ์ | เคียงคู่เกษตรกรไทย</small>
            </div>
        </a>
        <button class="sn-menu-toggle" type="button" aria-label="เปิดเมนูหลัก" aria-expanded="false" aria-controls="sn-primary-nav"><span></span><span></span><span></span></button>
        <nav class="sn-primary-nav" id="sn-primary-nav" aria-label="เมนูหลัก">
            <?php foreach ($menu as $item): ?>
                <?php if (!empty($item['affiliateMenu'])): ?>
                    <?= view('platform/affiliate_menu') ?>
                <?php elseif (isset($item['sub'])): 
                    $isSubActive = str_starts_with($publicPath, 'about') || $publicPath === 'marketing-plan' || $publicPath === 'company-documents';
                ?>
                    <div class="sn-dropdown">
                        <a href="<?= site_url($item['route']) ?>" class="sn-nav-link <?= $isSubActive ? 'is-active' : '' ?>">
                            <?= esc($item['label']) ?> <svg class="sn-chevron" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" style="vertical-align:middle; margin-left:3px; opacity:0.85;"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                        </a>
                        <div class="sn-dropdown-menu">
                            <?php foreach ($item['sub'] as [$subRoute, $subLabel]): 
                                $isThisSubActive = $publicPath === $subRoute || ($subRoute === 'about/marketing-plan' && $publicPath === 'marketing-plan') || ($subRoute === 'about/documents' && $publicPath === 'company-documents');
                            ?>
                                <a href="<?= site_url($subRoute) ?>" class="<?= $isThisSubActive ? 'is-active' : '' ?>"><?= esc($subLabel) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: 
                    [$route, $label] = $item;
                    $active = $route === '' ? $publicPath === '' : ($publicPath === $route || str_starts_with($publicPath, $route . '/'));
                ?>
                    <a href="<?= site_url($route) ?>" class="sn-nav-link <?= $active ? 'is-active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= esc($label) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sn-header-actions">
            <button type="button" class="sn-icon-button" data-sn-search aria-label="ค้นหาสินค้า" aria-expanded="false" aria-controls="sn-header-search"><?= $svg('search') ?></button>
            <a class="sn-icon-button" href="<?= site_url('favorites') ?>" aria-label="รายการโปรด"><?= $svg('heart') ?></a>
            <a class="sn-icon-button" href="<?= site_url(session('user_id')?'workspace':'login') ?>" aria-label="บัญชีสมาชิก / Workspace"><?= $svg('user') ?></a>
            <a class="sn-cart-pill" href="<?= site_url('cart') ?>" aria-label="ตะกร้าสินค้า"><?= $svg('cart') ?><span>ตะกร้า</span><?php if(array_sum(session('cart') ?? []) > 0): ?><span class="sn-cart-count"><?= array_sum(session('cart')) ?></span><?php endif ?></a>
        </div>
        <form class="sn-header-search" id="sn-header-search" action="<?= site_url('shop') ?>" method="get" hidden>
            <label for="sn-product-search">ค้นหาสินค้า</label><div><input type="search" id="sn-product-search" name="q" placeholder="ชื่อผลิตภัณฑ์ โปรเจ็คฟิลด์" required maxlength="120"><button>ค้นหา</button></div>
        </form>
    </div>
</header>

<link rel="stylesheet" href="<?= base_url('platform/profile-photo.css?v='.filemtime(FCPATH.'platform/profile-photo.css')) ?>">
<?= view('platform/referrer_card') ?>
