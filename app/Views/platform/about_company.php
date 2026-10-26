<div class="sn-page-content sn-about-company">
    <link rel="stylesheet" href="<?= base_url('platform/company-hero.css?v='.filemtime(FCPATH.'platform/company-hero.css')) ?>">
    <?= view('platform/company_hero',['companyHeroContext'=>'about']) ?>
    <?= view('platform/company_about_section') ?>
    <?= view('platform/company_values_section') ?>

    <!-- Executive Message Card -->
    <div style="background: #ffffff; border-radius: 14px; padding: 36px 32px; border: 1px solid #e2e8f0; margin-bottom: 36px; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
        <div style="display: flex; gap: 28px; align-items: center; flex-wrap: wrap;">
            <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; font-size: 42px; color: white; flex-shrink: 0; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);">
                👤
            </div>
            <div style="flex: 1; min-width: 280px;">
                <span style="font-size: 13px; font-weight: 700; color: #059669; letter-spacing: 0.5px;">สารจากประธานเจ้าหน้าที่บริหาร (CEO Message)</span>
                <h3 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 4px 0 12px;">"ความสำเร็จของเกษตรกร คือหัวใจสำคัญของ สกายเน็กซ์"</h3>
                <p style="font-size: 15px; line-height: 1.8; color: #475569; margin: 0 0 12px;">
                    "เราเชื่อมั่นว่าเกษตรกรไทยมีศักยภาพอันยิ่งใหญ่ สิ่งที่ขาดไปมีเพียงการเข้าถึงผลิตภัณฑ์ที่ได้มาตรฐานตรงจุด และการวางแผนอย่างถูกต้อง สกายเน็กซ์ อินเตอร์ จึงไม่ได้เป็นเพียงผู้จัดจำหน่าย แต่เราคือพันธมิตรที่พร้อมร่วมทุกข์ร่วมสุข วางแผนตั้งแต่เริ่มปลูกจนถึงวันเก็บเกี่ยวผลผลิต"
                </p>
                <div style="font-weight: 700; color: #0f172a; font-size: 15px;">คุณสุวิจักษณ์ เก่งป้อง</div>
                <div style="font-size: 13px; color: #64748b;">ประธานเจ้าหน้าที่บริหารและผู้ก่อตั้ง บริษัท สกายเน็กซ์ อินเตอร์ จำกัด</div>
            </div>
        </div>
    </div>

    <?= view('platform/company_trust_section') ?>

    <!-- Company Contact Banner -->
    <div style="background: #f1f5f9; border-radius: 12px; padding: 24px; border: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="font-size: 16px; font-weight: 700; color: #0f172a;">ต้องการปรึกษาการดูแลพืชหรือสอบถามข้อมูลบริษัท?</div>
            <div style="font-size: 14px; color: #64748b;">โทรศัพท์: 02-791-2999 | 087-677-0596 (วันจันทร์ - ศุกร์ 08:30 - 17:30 น.)</div>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="https://lin.ee/TFroyLG" target="_blank" class="button" style="background: #06c755; color: white; padding: 9px 18px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">💬 LINE Official</a>
            <a href="<?= site_url('contact-us') ?>" class="button" style="background: #0f172a; color: white; padding: 9px 18px; border-radius: 6px; font-weight: 600; text-decoration: none;">📞 ติดต่อเรา</a>
        </div>
    </div>
    <?= view('platform/company_products_section') ?>
</div>
