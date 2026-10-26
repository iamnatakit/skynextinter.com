<div class="sn-page-content sn-activities">
    <link rel="stylesheet" href="<?= base_url('platform/activities.css?v='.filemtime(FCPATH.'platform/activities.css')) ?>">
    <?php foreach(\App\Libraries\Platform\ActivityHighlights::CATALOG as $activity): ?><?= view('platform/'.$activity['view']) ?><?php endforeach ?>
    <!-- Hero Banner Card -->
    <div class="sn-card sn-hero-banner" style="background: linear-gradient(135deg, #072612 0%, #0d381c 50%, #164e28 100%); color: #ffffff; border-radius: 16px; padding: 48px 36px; margin-bottom: 32px; box-shadow: 0 10px 30px rgba(7, 38, 18, 0.25); position: relative; overflow: hidden; border: 1px solid rgba(74, 222, 128, 0.3);">
        <div style="position: absolute; right: -40px; bottom: -40px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(74, 222, 128, 0.15) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
        <div style="max-width: 800px; position: relative; z-index: 2;">
            <span style="display: inline-block; background: rgba(74, 222, 128, 0.2); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.4); font-size: 13px; font-weight: 600; padding: 4px 14px; border-radius: 20px; margin-bottom: 16px; letter-spacing: 0.5px;">🤝 เคียงข้างชุมชนเกษตร | SkyNext Community & CSR</span>
            <h1 style="font-size: clamp(28px, 4vw, 38px); font-weight: 800; line-height: 1.25; margin: 0 0 16px; color: #ffffff; letter-spacing: -0.5px;">กิจกรรมและโครงการเพื่อเกษตรกร<br><span style="color: #4ade80;">บริษัท สกายเน็กซ์ อินเตอร์ จำกัด</span></h1>
            <p style="font-size: 16px; line-height: 1.75; color: #cbd5e1; margin: 0 0 24px;">ภาพกิจกรรมการลงพื้นที่ให้ความรู้ด้านการปรับปรุงดิน การบริหารจัดการโรคพืช งานสัมมนาถ่ายทอดเทคโนโลยีเกษตรสมัยใหม่ และโครงการส่งเสริมคุณภาพชีวิตเกษตรกรไทยทั่วประเทศ</p>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255, 255, 255, 0.1); padding: 8px 18px; border-radius: 30px; border: 1px solid rgba(255, 255, 255, 0.2); font-size: 13.5px; color: #e2e8f0;">
                <span style="color: #4ade80;">🚜</span> ลงพื้นที่จริง ร่วมพัฒนาผลผลิตเคียงข้างเกษตรกรทุกแปลงปลูก
            </div>
        </div>
    </div>

    <!-- Activities Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 36px;">
        <!-- Activity 1 -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
            <div style="height: 200px; background: #0c3e1e; position: relative; display: flex; align-items: center; justify-content: center; color: #4ade80; font-size: 56px;">
                🌾
                <span style="position: absolute; top: 14px; left: 14px; background: rgba(7, 38, 18, 0.85); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px;">สัมมนาเกษตร</span>
            </div>
            <div style="padding: 24px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <span style="font-size: 12.5px; color: #64748b; font-weight: 500;">📅 18 ตุลาคม 2567 | สุพรรณบุรี</span>
                    <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 8px 0 10px; line-height: 1.4;">โครงการตรวจวิเคราะห์สุขภาพดินและวางแผนปุ๋ยลดต้นทุน แปลงนาข้าว</h3>
                    <p style="font-size: 14px; color: #475569; line-height: 1.6; margin: 0 0 16px;">ทีมผู้เชี่ยวชาญสกายเน็กซ์ลงพื้นที่ตรวจค่า pH และความอุดมสมบูรณ์ของดิน พร้อมแนะนำสูตรปุ๋ยเฉพาะแปลง ช่วยชาวนาลดต้นทุนปุ๋ยเคมีได้กว่า 25%</p>
                </div>
                <div style="border-top: 1px solid #f1f5f9; padding-top: 14px;">
                    <span style="color: #059669; font-weight: 600; font-size: 13.5px;">✓ เกษตรกรเข้าร่วมกว่า 120 ครัวเรือน</span>
                </div>
            </div>
        </div>

        <!-- Activity 2 -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
            <div style="height: 200px; background: #1e3a8a; position: relative; display: flex; align-items: center; justify-content: center; color: #60a5fa; font-size: 56px;">
                🌳
                <span style="position: absolute; top: 14px; left: 14px; background: rgba(30, 58, 138, 0.85); color: #93c5fd; border: 1px solid rgba(147, 197, 253, 0.3); font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px;">ลงพื้นที่สวนทุเรียน</span>
            </div>
            <div style="padding: 24px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <span style="font-size: 12.5px; color: #64748b; font-weight: 500;">📅 5 กันยายน 2567 | จันทบุรี</span>
                    <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 8px 0 10px; line-height: 1.4;">สัมมนาเทคนิคการฟื้นฟูต้นทุเรียนหลังเก็บเกี่ยวและการสะสมอาหาร</h3>
                    <p style="font-size: 14px; color: #475569; line-height: 1.6; margin: 0 0 16px;">สาธิตการใช้ธาตุอาหารรองและปุ๋ยน้ำสกายเน็กซ์ เพื่อเตรียมใบชุดใหม่ให้สมบูรณ์พร้อมสำหรับการออกดอกในฤดูกาลถัดไป</p>
                </div>
                <div style="border-top: 1px solid #f1f5f9; padding-top: 14px;">
                    <span style="color: #0284c7; font-weight: 600; font-size: 13.5px;">✓ ชาวสวนทุเรียนเข้าร่วมกว่า 80 แปลง</span>
                </div>
            </div>
        </div>

        <!-- Activity 3 -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
            <div style="height: 200px; background: #78350f; position: relative; display: flex; align-items: center; justify-content: center; color: #fbbf24; font-size: 56px;">
                💻
                <span style="position: absolute; top: 14px; left: 14px; background: rgba(120, 53, 15, 0.85); color: #fde68a; border: 1px solid rgba(253, 230, 138, 0.3); font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px;">ฝึกอบรมออนไลน์</span>
            </div>
            <div style="padding: 24px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <span style="font-size: 12.5px; color: #64748b; font-weight: 500;">📅 22 สิงหาคม 2567 | อบรมทั่วประเทศผ่านระบบออนไลน์</span>
                    <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 8px 0 10px; line-height: 1.4;">เวิร์กช็อป "การตลาดเกษตรออนไลน์และการใช้ AI ช่วยขายสินค้า"</h3>
                    <p style="font-size: 14px; color: #475569; line-height: 1.6; margin: 0 0 16px;">เสริมทักษะดิจิทัลให้แก่พันธมิตร Affiliate สกายเน็กซ์ เทคนิคการสร้างคอนเทนต์เกษตร และการใช้ระบบผู้ช่วย AI ตอบคำถามลูกค้าอัตโนมัติ</p>
                </div>
                <div style="border-top: 1px solid #f1f5f9; padding-top: 14px;">
                    <span style="color: #d97706; font-weight: 600; font-size: 13.5px;">✓ พันธมิตรเข้าร่วมกว่า 350 ท่าน</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact for Workshop -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; text-align: center;">
        <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 8px;">สนใจเชิญทีมวิทยากร สกายเน็กซ์ ลงพื้นที่ให้ความรู้กลุ่มเกษตรกรในชุมชน?</h3>
        <p style="font-size: 14px; color: #64748b; max-width: 650px; margin: 0 auto 18px;">เรายินดีให้การสนับสนุนวิทยากรและชุดตรวจสุขภาพดินสำหรับกลุ่มเกษตรกร วิสาหกิจชุมชน และสหกรณ์การเกษตร</p>
        <a href="<?= site_url('contact-us') ?>" class="button" style="background: #059669; color: white; padding: 9px 22px; border-radius: 6px; font-weight: 600; text-decoration: none;">🤝 ติดต่อฝ่ายพัฒนาองค์ความรู้ชุมชน</a>
    </div>
</div>
