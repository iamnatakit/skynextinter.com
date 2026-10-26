<?php use App\Libraries\Platform\Access; $actionBase=site_url('admin/'.$module); ?>
<?php if($stats): ?><div class="stats"><?php foreach($stats as $label=>$value): ?><div class="stat"><span class="stat-marker">↗</span><div class="stat-label"><?= esc($label) ?></div><div class="stat-value"><?= esc($value) ?></div></div><?php endforeach ?></div><?php endif ?>
<?php if($module==='dashboard'): ?><div class="grid-two"><section class="panel"><div class="table-toolbar"><h2>คำสั่งซื้อล่าสุด</h2><?php if(Access::can($user,'orders')): ?><a href="<?= site_url('admin/orders') ?>">ดูทั้งหมด →</a><?php endif ?></div><?= view('platform/table',['rows'=>$extra['recent_orders'],'module'=>'']) ?></section><section class="panel"><h2>Lead Pipeline</h2><?php if(!$extra['pipeline']): ?><div class="empty">เริ่มเพิ่ม Lead หรือรับข้อมูลจาก Chatbot</div><?php endif ?><?php foreach($extra['pipeline'] as $p): ?><div class="pipeline-item"><p><?= esc(p_label($p['stage'])) ?> <strong style="float:right"><?= $p['count'] ?></strong></p><div class="progress"><span style="width:<?= min(100,$p['count']*10) ?>%"></span></div></div><?php endforeach ?></section></div><div class="notice info">เริ่มต้น: ตรวจราคาและสต็อกสินค้า → ตั้งค่าบัญชีรับโอน → เพิ่มทีมงาน → เชื่อมต่อบริการภายนอก</div><?php endif ?>
<?php if($fields): ?><details class="panel" <?= $record?'open':'' ?>><summary><strong><?= $record?'แก้ไขรายการ #'.$record['id']:'＋ เพิ่มรายการใหม่' ?></strong></summary><form method="post" enctype="multipart/form-data" action="<?= $actionBase.'/save/'.($record['id']??0) ?>" style="margin-top:20px"><?= csrf_field() ?><div class="form-grid"><?php foreach($fields as $key=>$f): $value=$record[$key]??($key==='active'?'1':($key==='value'||$key==='price'||$key==='minimum'?'0':'')); if($f[1]==='datetime-local'&&$value){$value=(new DateTimeImmutable($value,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Bangkok'))->format('Y-m-d\TH:i');} ?><label class="<?= $f[1]==='textarea'?'field-full':'' ?>"><?= esc($f[0]) ?><?php if($f[1]==='textarea'): ?><textarea name="<?= $key ?>" maxlength="16000"><?= esc($value) ?></textarea><?php elseif($f[1]==='datalist'): ?><input type="text" name="<?= esc($key,'attr') ?>" value="<?= esc($value,'attr') ?>" list="sn-options-<?= esc($key,'attr') ?>" maxlength="100"><datalist id="sn-options-<?= esc($key,'attr') ?>"><?php foreach($f[2] as $suggestion): ?><option value="<?= esc($suggestion,'attr') ?>"></option><?php endforeach ?></datalist><small>เลือกหมวดหมู่ที่แนะนำ หรือพิมพ์หมวดหมู่อื่นได้</small><?php elseif($f[1]==='select'||$f[1]==='staff'): ?><select name="<?= $key ?>"><?php $options=$f[1]==='staff'?[''=>'ยังไม่มอบหมาย']+array_column($staff,'name','id'):$f[2];foreach($options as $v=>$label): ?><option value="<?= esc($v,'attr') ?>" <?= (string)$value===(string)$v?'selected':'' ?>><?= esc($label) ?></option><?php endforeach ?></select><?php else: ?><input type="<?= $f[1]==='integer'?'number':$f[1] ?>" name="<?= $key ?>" value="<?= esc($value,'attr') ?>" <?= $f[1]==='number'?'step="0.01" min="0"':'' ?> <?= $f[1]==='integer'?'min="0" step="1"':'' ?> <?= $f[1]==='password'?'autocomplete="new-password" minlength="12" maxlength="200"':'' ?>><?php endif ?></label><?php endforeach ?><?php if(in_array($module,['products','blog'])): ?><label class="field-full">อัปโหลดรูปปก / ภาพสินค้า<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label><?php endif ?></div><div class="actions"><button>บันทึกข้อมูล</button><?php if($record): ?><a class="button secondary" href="<?= $actionBase ?>">ยกเลิกการแก้ไข</a><?php endif ?></div></form></details><?php endif ?>
<?php if($module==='products'): ?><p class="section-note">ตั้งส่วนลดได้ทั้งเปอร์เซ็นต์และจำนวนเงินบาท โดยคิดจากราคาปกติเท่านั้น ไม่หักซ้ำจากราคาสมาชิก ส่วนลดแพ็กเกจรายปีจะใช้กับยอดต่ออายุด้วย สต็อกต้องระบุจำนวนจริงก่อนขาย สามารถซ่อนสินค้าโดยเปลี่ยนสถานะ</p><?php endif ?>
<?php if($module==='affiliates'): ?><section class="panel"><h2>เครือข่ายแนะนำสมาชิก</h2><p>ลิงก์ส่วนตัว → ผู้สมัครใหม่ → คำสั่งซื้อที่เกิดจากผู้แนะนำ ติดตามความสัมพันธ์ได้โดยไม่มีแผนรายได้ ค่าคอมมิชชัน หรือตำแหน่ง</p><p class="muted">ผู้แนะนำถูกผูกเมื่อสมัครสมาชิกและแก้ไขผ่านหน้าจอนี้ไม่ได้ เพื่อป้องกันวงจรสายงาน</p></section><?php endif ?>
<?php if($module==='leads'): ?><section class="panel"><h2>ติดตามโอกาสขาย</h2><p>ใหม่ → ติดต่อแล้ว → มีคุณสมบัติ → เสนอราคา → สำเร็จ / ไม่สำเร็จ</p><p class="muted">คะแนนจาก Chatbot เป็นกฎตามข้อความแสดงความสนใจ ไม่ใช่การประเมินโดยโมเดล AI การฝากข้อมูลต้องมีความยินยอม</p></section><?php endif ?>
<?php if($module==='orders'&&!empty($extra['order'])): $o=$extra['order']; $next=['awaiting_payment'=>['cancelled'],'payment_review'=>['cancelled'],'paid'=>['processing','refund_pending'],'processing'=>['shipped','refund_pending'],'shipped'=>['completed','refund_pending'],'completed'=>['refund_pending'],'refund_pending'=>['refunded']][$o['status']]??[]; ?><section class="panel"><h2><?= esc($o['number']) ?> · <?= esc(p_label($o['status'])) ?></h2><p><?= esc($o['recipient']) ?> · <?= esc($o['phone']) ?><br><?= nl2br(esc($o['address'])) ?></p><?= view('platform/table',['rows'=>$extra['items'],'module'=>'']) ?><?php if($next): ?><form class="inline-form" method="post" action="<?= $actionBase.'/status/'.$o['id'] ?>"><?= csrf_field() ?><select name="status"><?php foreach($next as $status): ?><option value="<?= $status ?>"><?= esc(p_label($status)) ?></option><?php endforeach ?></select><input name="note" placeholder="เลขพัสดุ / เหตุผล / เลขอ้างอิงคืนเงิน" maxlength="1000"><button>อัปเดตสถานะ</button></form><?php endif ?><p class="muted">สถานะคืนเงินใช้บันทึกผลหลังคืนเงินผ่านธนาคารหรือผู้ให้บริการแล้ว ระบบไม่โอนเงินคืนอัตโนมัติ</p></section><?php endif ?>
<?php if($module==='conversations'&&!empty($extra['conversation'])): $c=$extra['conversation']; ?><section class="panel"><div class="table-toolbar"><h2>บทสนทนา #<?= $c['id'] ?> · <?= esc($c['platform']) ?></h2><form method="post" action="<?= $actionBase.'/mode/'.$c['id'] ?>"><?= csrf_field() ?><input type="hidden" name="mode" value="<?= $c['mode']==='auto'?'human':'auto' ?>"><button class="secondary small"><?= $c['mode']==='auto'?'หยุดบอท / รับช่วงสนทนา':'เปิดบอทอัตโนมัติ' ?></button></form></div><div class="chat-history"><?php foreach(array_reverse($extra['messages']) as $m): ?><div class="message <?= esc($m['direction'],'attr') ?>"><small class="muted"><?= esc(p_label($m['direction'])) ?> · <?= p_date($m['created_at']) ?></small><br><?= esc($m['body']) ?></div><?php endforeach ?></div><form method="post" class="stack" action="<?= $actionBase.'/reply/'.$c['id'] ?>" style="margin-top:16px"><?= csrf_field() ?><label>ตอบกลับในนามเจ้าหน้าที่<textarea name="body" required maxlength="2000"></textarea></label><button>บันทึกและส่งคำตอบ</button></form><?php if($c['platform']!=='web'): ?><p class="muted">คำตอบส่งผ่านคิวเมื่อเปิดช่องทางและรัน php spark platform:work</p><?= view('platform/table',['rows'=>$extra['outbox'],'module'=>'']) ?><?php endif ?></section><?php endif ?>
<?php if($module==='ai'): 
$cfg = $extra['settings'] ?? [];
$aiStats = $extra['ai_stats'] ?? [];
$webhooks = $extra['webhook_urls'] ?? [];
$activeProvider = $cfg['ai_provider'] ?? 'deepseek';
?>
<div class="stats" style="margin-bottom:20px">
    <div class="stat">
        <span class="stat-marker">💬</span>
        <div class="stat-label">การสนทนาทั้งหมด</div>
        <div class="stat-value"><?= number_format($aiStats['conversations'] ?? 0) ?></div>
    </div>
    <div class="stat">
        <span class="stat-marker">📨</span>
        <div class="stat-label">ข้อความสะสม</div>
        <div class="stat-value"><?= number_format($aiStats['messages'] ?? 0) ?></div>
    </div>
    <div class="stat">
        <span class="stat-marker">🎯</span>
        <div class="stat-label">Leads จาก Chatbot</div>
        <div class="stat-value"><?= number_format($aiStats['leads'] ?? 0) ?></div>
    </div>
    <div class="stat">
        <span class="stat-marker">👤</span>
        <div class="stat-label">คุยกับเจ้าหน้าที่ (คนจริง)</div>
        <div class="stat-value"><?= number_format($aiStats['active_human'] ?? 0) ?></div>
    </div>
</div>

<div class="ai-tabs-container">
    <button type="button" class="ai-tab-btn active" data-tab="tab-chatbot">
        <span>🤖</span> 1. จัดการ Chatbot (Chatbot Management)
    </button>
    <button type="button" class="ai-tab-btn" data-tab="tab-providers">
        <span>⚡</span> 2. เชื่อมต่อ AI API หลายค่าย (Multi-Provider AI)
    </button>
    <button type="button" class="ai-tab-btn" data-tab="tab-platforms">
        <span>🌐</span> 3. เชื่อมต่อหลายแพลตฟอร์ม (Multi-Platform)
    </button>
</div>

<form method="post" action="<?= $actionBase.'/save/0' ?>" id="ai-main-form" class="stack">
    <?= csrf_field() ?>

    <!-- TAB 1: Chatbot Management -->
    <div id="tab-chatbot" class="ai-tab-pane active">
        <div class="grid-two">
            <section class="panel">
                <h2>⚙️ สถานะและโหมดการทำงานของบอท</h2>
                <div class="form-grid">
                    <label>
                        สถานะระบบตอบอัตโนมัติ (Chatbot Engine)
                        <select name="bot_enabled">
                            <option value="1" <?= ($cfg['bot_enabled']??'1')==='1'?'selected':'' ?>>🟢 เปิดใช้งาน (Active)</option>
                            <option value="0" <?= ($cfg['bot_enabled']??'1')==='0'?'selected':'' ?>>🔴 ปิดใช้งานชั่วคราว (Disabled)</option>
                        </select>
                    </label>
                    <label>
                        ระบบผู้ช่วย AI อัจฉริยะ (AI Assistant)
                        <select name="ai_enabled">
                            <option value="1" <?= ($cfg['ai_enabled']??'1')==='1'?'selected':'' ?>>✨ เปิดประมวลผลด้วย AI (AI Enabled)</option>
                            <option value="0" <?= ($cfg['ai_enabled']??'1')==='0'?'selected':'' ?>>🔒 ปิด AI (ใช้เฉพาะกฎคำสำคัญ)</option>
                        </select>
                    </label>
                    <label class="field-full">
                        รูปแบบการตอบกลับ (Bot Processing Mode)
                        <select name="bot_mode">
                            <option value="hybrid" <?= ($cfg['bot_mode']??'hybrid')==='hybrid'?'selected':'' ?>>⚡ Hybrid (แนะนำ): ตรวจค้นหาสินค้า &amp; คำสำคัญก่อน หากไม่ตรงส่งต่อให้ AI อัจฉริยะตอบ</option>
                            <option value="ai_only" <?= ($cfg['bot_mode']??'hybrid')==='ai_only'?'selected':'' ?>>🧠 AI Only: ส่งทุกคำถามให้โมเดล AI วิเคราะห์และตอบโดยตรง</option>
                            <option value="rules_only" <?= ($cfg['bot_mode']??'hybrid')==='rules_only'?'selected':'' ?>>📋 Rules &amp; Catalog Only: ตอบเฉพาะคำค้นหาสินค้าและคำสำคัญ (ไม่เรียก AI API)</option>
                        </select>
                    </label>
                    <label class="field-full">
                        บันทึก Lead Capture อัตโนมัติ
                        <select name="bot_lead_capture">
                            <option value="1" <?= ($cfg['bot_lead_capture']??'1')==='1'?'selected':'' ?>>เปิด: ตรวจจับชื่อและเบอร์โทร/LINE เพื่อสร้าง Lead ในระบบอัตโนมัติ</option>
                            <option value="0" <?= ($cfg['bot_lead_capture']??'1')==='0'?'selected':'' ?>>ปิด: ไม่บันทึก Lead อัตโนมัติ</option>
                        </select>
                    </label>
                </div>

                <h2 style="margin-top:28px">💬 ข้อความตอบกลับอัตโนมัติ (Automated Responses)</h2>
                <div class="stack">
                    <label>
                        ข้อความต้อนรับเมื่อเริ่มสนทนา (Welcome Message)
                        <input name="bot_welcome_msg" value="<?= esc($cfg['bot_welcome_msg']??'สวัสดีครับ มีอะไรให้เราช่วยเหลือสอบถามได้เลยครับ','attr') ?>" maxlength="500">
                    </label>
                    <label>
                        ข้อความสำรองเมื่อไม่พบข้อมูล (Fallback Message)
                        <input name="bot_fallback_msg" value="<?= esc($cfg['bot_fallback_msg']??'ขออภัยครับ ยังไม่พบข้อมูลในระบบ หรือต้องการให้เจ้าหน้าที่ติดต่อกลับพิมพ์ "แอดมิน" ได้เลยครับ','attr') ?>" maxlength="500">
                    </label>
                    <label>
                        ข้อความเมื่อส่งต่อเจ้าหน้าที่ (Handoff Message)
                        <input name="bot_handoff_msg" value="<?= esc($cfg['bot_handoff_msg']??'ส่งต่อให้เจ้าหน้าที่แล้วค่ะ กรุณาฝากชื่อและเบอร์โทร/LINE ทีมงานจะตอบกลับในกล่องข้อความนี้โดยเร็ว','attr') ?>" maxlength="500">
                    </label>
                    <label>
                        คำสำคัญเรียกแอดมินคนจริง (Handoff Keywords - คั่นด้วยเครื่องหมายจุลภาค ,)
                        <input name="bot_handoff_keywords" value="<?= esc($cfg['bot_handoff_keywords']??'แอดมิน, เจ้าหน้าที่, human, agent, ติดต่อคน','attr') ?>" maxlength="500">
                        <small class="muted">เมื่อลูกค้าพิมพ์คำเหล่านี้ ระบบจะสลับโหมดเป็นคุยกับเจ้าหน้าที่ (Human Mode) ทันที</small>
                    </label>
                </div>
            </section>

            <section class="panel">
                <h2>🎭 บุคลิก AI และฐานความรู้ร้านค้า</h2>
                <div class="stack">
                    <label>
                        บุคลิกและการทำงานของ AI (System Persona &amp; Instructions)
                        <textarea name="bot_system_prompt" style="min-height:140px" maxlength="4000"><?= esc($cfg['bot_system_prompt']??'คุณคือ AI ผู้ช่วยร้านค้าอัจฉริยะของ SkyNext ตอบคำถามลูกค้าเป็นภาษาไทยอย่างสุภาพ กระชับ เป็นมิตร และให้ข้อมูลที่ถูกต้องเป็นประโยชน์ตามข้อมูลร้านค้าและรายการสินค้าที่ให้ไว้เท่านั้น') ?></textarea>
                    </label>
                    <label>
                        เชื่อมโยงแคตตาล็อกสินค้าจริงเข้าสู่บริบท AI
                        <select name="bot_include_catalog">
                            <option value="1" <?= ($cfg['bot_include_catalog']??'1')==='1'?'selected':'' ?>>เปิด: นำรายการสินค้าจริงในระบบเข้า System Prompt อัตโนมัติ (AI รู้จักชื่อสินค้า ราคา สต็อก)</option>
                            <option value="0" <?= ($cfg['bot_include_catalog']??'1')==='0'?'selected':'' ?>>ปิด: ไม่ส่งรายการสินค้าเข้า Prompt</option>
                        </select>
                    </label>
                    <label>
                        ฐานความรู้ร้านค้า (Knowledge Base - FAQ, นโยบายส่งของ, การเคลม, บัญชีโอนเงิน)
                        <textarea name="bot_knowledge" style="min-height:220px" maxlength="32000" placeholder="ระบุข้อมูลสำคัญ เช่น เวลาทำการ, นโยบายการรับประกันสินค้า, วิธีสั่งซื้อ, คำถามที่พบบ่อย (FAQ)..."><?= esc($cfg['bot_knowledge']??'') ?></textarea>
                    </label>
                </div>

                <h2 style="margin-top:28px">🎛️ ปรับแต่งพารามิเตอร์ AI (Tuning)</h2>
                <div class="form-grid">
                    <label>
                        Temperature (ความคิดสร้างสรรค์ 0.0 - 1.0)
                        <input name="bot_temperature" type="number" step="0.1" min="0" max="1" value="<?= esc($cfg['bot_temperature']??'0.7','attr') ?>">
                        <small class="muted">0.2 = เที่ยงตรงตามข้อมูล, 0.7 = ตอบเป็นธรรมชาติ</small>
                    </label>
                    <label>
                        Max Output Tokens
                        <input name="bot_max_tokens" type="number" min="50" max="4000" step="50" value="<?= esc($cfg['bot_max_tokens']??'800','attr') ?>">
                        <small class="muted">ความยาวคำตอบสูงสุด (ค่าแนะนำ: 800)</small>
                    </label>
                    <label class="field-full">
                        จำกัดการเรียกใช้งาน AI ต่อวัน (Daily Rate Limit)
                        <input name="bot_rate_limit" type="number" min="10" max="10000" step="10" value="<?= esc($cfg['bot_rate_limit']??'200','attr') ?>">
                        <small class="muted">จำนวนครั้งสูงสุดต่อวันสำหรับทั้งระบบ เพื่อควบคุมค่าใช้จ่าย API</small>
                    </label>
                </div>
            </section>
        </div>
    </div>

    <!-- TAB 2: Multi-Provider AI Connection -->
    <div id="tab-providers" class="ai-tab-pane">
        <section class="panel" style="background:linear-gradient(180deg,#f9fbf9 0%,#fff 100%); border:1px solid #c9dfca">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px">
                <div>
                    <h2 style="margin:0 0 6px">🎯 ผู้ให้บริการ AI หลักที่ใช้งานปัจจุบัน (Active AI Provider)</h2>
                    <p class="muted" style="margin:0">เลือกว่าจะให้ระบบ Chatbot ส่งคำถามไปประมวลผลที่ค่ายใดเป็นหลัก คุณสามารถสลับค่ายได้ทันที</p>
                </div>
                <div>
                    <select name="ai_provider" id="ai-active-provider-select" style="font-size:16px; font-weight:bold; padding:10px 16px; border:2px solid var(--green); border-radius:8px">
                        <option value="deepseek" <?= $activeProvider==='deepseek'?'selected':'' ?>>🐋 DeepSeek AI (แนะนำ · ค่าบริการประหยัด)</option>
                        <option value="openai" <?= $activeProvider==='openai'?'selected':'' ?>>🟢 OpenAI (GPT-4o mini / GPT-4o)</option>
                        <option value="gemini" <?= $activeProvider==='gemini'?'selected':'' ?>>✨ Google Gemini (Gemini 3.8 Flash)</option>
                        <option value="claude" <?= $activeProvider==='claude'?'selected':'' ?>>🟣 Anthropic Claude (Claude 3.5 Sonnet)</option>
                        <option value="openrouter" <?= $activeProvider==='openrouter'?'selected':'' ?>>🌐 OpenRouter (Unified API รวมหลายค่าย)</option>
                        <option value="custom" <?= $activeProvider==='custom'?'selected':'' ?>>💻 Local AI / Ollama / Custom API</option>
                    </select>
                </div>
            </div>
        </section>

        <div class="ai-provider-grid">
            <!-- 1. DeepSeek -->
            <?php $dsHasKey = !empty($cfg['deepseek_api_key']) || (bool)env('DEEPSEEK_API_KEY'); ?>
            <div class="ai-provider-card <?= $activeProvider==='deepseek'?'active-provider':'' ?>" data-provider-card="deepseek">
                <div class="ai-provider-header">
                    <div class="ai-provider-title">
                        <div class="ai-provider-icon" style="background:#e0f2fe; color:#0284c7">🐋</div>
                        <div>
                            <div>DeepSeek AI</div>
                            <small class="muted">deepseek.com</small>
                        </div>
                    </div>
                    <div>
                        <?php if($activeProvider==='deepseek'): ?>
                            <span class="ai-badge current">กำลังใช้งาน</span>
                        <?php elseif($dsHasKey): ?>
                            <span class="ai-badge ready">พร้อมใช้งาน</span>
                        <?php else: ?>
                            <span class="ai-badge waiting">ยังไม่มี Key</span>
                        <?php endif ?>
                    </div>
                </div>
                <div class="stack" style="gap:10px">
                    <label>
                        API Key
                        <input type="password" name="deepseek_api_key" id="key-deepseek" value="<?= esc($cfg['deepseek_api_key']??'','attr') ?>" placeholder="sk-..." autocomplete="off">
                    </label>
                    <label>
                        Model
                        <input name="deepseek_model" id="model-deepseek" value="<?= esc($cfg['deepseek_model']??'deepseek-chat','attr') ?>" placeholder="deepseek-chat">
                    </label>
                    <label>
                        Base URL
                        <input name="deepseek_base_url" id="url-deepseek" value="<?= esc($cfg['deepseek_base_url']??'https://api.deepseek.com','attr') ?>" placeholder="https://api.deepseek.com">
                    </label>
                </div>
                <div class="ai-test-panel">
                    <button type="button" class="ai-test-btn" onclick="testAiConnection('deepseek', this)">
                        <span>⚡</span> ทดสอบการเชื่อมต่อ DeepSeek
                    </button>
                    <div class="ai-test-result" id="test-result-deepseek"></div>
                </div>
            </div>

            <!-- 2. OpenAI -->
            <?php $oaHasKey = !empty($cfg['openai_api_key']) || (env('AI_PROVIDER')==='openai' && (bool)env('OPENAI_API_KEY')); ?>
            <div class="ai-provider-card <?= $activeProvider==='openai'?'active-provider':'' ?>" data-provider-card="openai">
                <div class="ai-provider-header">
                    <div class="ai-provider-title">
                        <div class="ai-provider-icon" style="background:#dcfce7; color:#16a34a">🟢</div>
                        <div>
                            <div>OpenAI</div>
                            <small class="muted">openai.com</small>
                        </div>
                    </div>
                    <div>
                        <?php if($activeProvider==='openai'): ?>
                            <span class="ai-badge current">กำลังใช้งาน</span>
                        <?php elseif($oaHasKey): ?>
                            <span class="ai-badge ready">พร้อมใช้งาน</span>
                        <?php else: ?>
                            <span class="ai-badge waiting">ยังไม่มี Key</span>
                        <?php endif ?>
                    </div>
                </div>
                <div class="stack" style="gap:10px">
                    <label>
                        API Key
                        <input type="password" name="openai_api_key" id="key-openai" value="<?= esc($cfg['openai_api_key']??'','attr') ?>" placeholder="sk-..." autocomplete="off">
                    </label>
                    <label>
                        Model
                        <input name="openai_model" id="model-openai" value="<?= esc($cfg['openai_model']??'gpt-4o-mini','attr') ?>" placeholder="gpt-4o-mini หรือ gpt-4o">
                    </label>
                    <label>
                        Base URL
                        <input name="openai_base_url" id="url-openai" value="<?= esc($cfg['openai_base_url']??'https://api.openai.com/v1','attr') ?>" placeholder="https://api.openai.com/v1">
                    </label>
                </div>
                <div class="ai-test-panel">
                    <button type="button" class="ai-test-btn" onclick="testAiConnection('openai', this)">
                        <span>⚡</span> ทดสอบการเชื่อมต่อ OpenAI
                    </button>
                    <div class="ai-test-result" id="test-result-openai"></div>
                </div>
            </div>

            <!-- 3. Google Gemini -->
            <?php $gmHasKey = !empty($cfg['gemini_api_key']) || (bool)env('GEMINI_API_KEY'); ?>
            <div class="ai-provider-card <?= $activeProvider==='gemini'?'active-provider':'' ?>" data-provider-card="gemini">
                <div class="ai-provider-header">
                    <div class="ai-provider-title">
                        <div class="ai-provider-icon" style="background:#ede9fe; color:#7c3aed">✨</div>
                        <div>
                            <div>Google Gemini</div>
                            <small class="muted">ai.google.dev</small>
                        </div>
                    </div>
                    <div>
                        <?php if($activeProvider==='gemini'): ?>
                            <span class="ai-badge current">กำลังใช้งาน</span>
                        <?php elseif($gmHasKey): ?>
                            <span class="ai-badge ready">พร้อมใช้งาน</span>
                        <?php else: ?>
                            <span class="ai-badge waiting">ยังไม่มี Key</span>
                        <?php endif ?>
                    </div>
                </div>
                <div class="stack" style="gap:10px">
                    <label>
                        API Key (Google AI Studio)
                        <input type="password" name="gemini_api_key" id="key-gemini" value="<?= esc($cfg['gemini_api_key']??'','attr') ?>" placeholder="AQ... หรือ AIzaSy..." autocomplete="off">
                    </label>
                    <label>
                        Model
                        <input name="gemini_model" id="model-gemini" value="<?= esc(!empty($cfg['gemini_model']) && !str_starts_with($cfg['gemini_model'], 'gemini-1.') && !str_starts_with($cfg['gemini_model'], 'gemini-2.') ? $cfg['gemini_model'] : 'gemini-3.5-flash','attr') ?>" placeholder="gemini-3.5-flash หรือ gemini-3.5-flash-lite">
                    </label>
                    <label>
                        Base URL
                        <input name="gemini_base_url" id="url-gemini" value="<?= esc($cfg['gemini_base_url']??'https://generativelanguage.googleapis.com/v1beta','attr') ?>" placeholder="https://generativelanguage.googleapis.com/v1beta">
                    </label>
                </div>
                <div class="ai-test-panel">
                    <button type="button" class="ai-test-btn" onclick="testAiConnection('gemini', this)">
                        <span>⚡</span> ทดสอบการเชื่อมต่อ Gemini
                    </button>
                    <div class="ai-test-result" id="test-result-gemini"></div>
                </div>
            </div>

            <!-- 4. Anthropic Claude -->
            <?php $clHasKey = !empty($cfg['claude_api_key']) || (bool)env('CLAUDE_API_KEY') || (bool)env('ANTHROPIC_API_KEY'); ?>
            <div class="ai-provider-card <?= $activeProvider==='claude'?'active-provider':'' ?>" data-provider-card="claude">
                <div class="ai-provider-header">
                    <div class="ai-provider-title">
                        <div class="ai-provider-icon" style="background:#fef3c7; color:#d97706">🟣</div>
                        <div>
                            <div>Anthropic Claude</div>
                            <small class="muted">anthropic.com</small>
                        </div>
                    </div>
                    <div>
                        <?php if($activeProvider==='claude'): ?>
                            <span class="ai-badge current">กำลังใช้งาน</span>
                        <?php elseif($clHasKey): ?>
                            <span class="ai-badge ready">พร้อมใช้งาน</span>
                        <?php else: ?>
                            <span class="ai-badge waiting">ยังไม่มี Key</span>
                        <?php endif ?>
                    </div>
                </div>
                <div class="stack" style="gap:10px">
                    <label>
                        API Key
                        <input type="password" name="claude_api_key" id="key-claude" value="<?= esc($cfg['claude_api_key']??'','attr') ?>" placeholder="sk-ant-..." autocomplete="off">
                    </label>
                    <label>
                        Model
                        <input name="claude_model" id="model-claude" value="<?= esc($cfg['claude_model']??'claude-3-5-sonnet-20241022','attr') ?>" placeholder="claude-3-5-haiku-20241022">
                    </label>
                    <label>
                        Base URL
                        <input name="claude_base_url" id="url-claude" value="<?= esc($cfg['claude_base_url']??'https://api.anthropic.com/v1','attr') ?>" placeholder="https://api.anthropic.com/v1">
                    </label>
                </div>
                <div class="ai-test-panel">
                    <button type="button" class="ai-test-btn" onclick="testAiConnection('claude', this)">
                        <span>⚡</span> ทดสอบการเชื่อมต่อ Claude
                    </button>
                    <div class="ai-test-result" id="test-result-claude"></div>
                </div>
            </div>

            <!-- 5. OpenRouter -->
            <?php $orHasKey = !empty($cfg['openrouter_api_key']) || (bool)env('OPENROUTER_API_KEY'); ?>
            <div class="ai-provider-card <?= $activeProvider==='openrouter'?'active-provider':'' ?>" data-provider-card="openrouter">
                <div class="ai-provider-header">
                    <div class="ai-provider-title">
                        <div class="ai-provider-icon" style="background:#f1f5f9; color:#475569">🌐</div>
                        <div>
                            <div>OpenRouter</div>
                            <small class="muted">openrouter.ai</small>
                        </div>
                    </div>
                    <div>
                        <?php if($activeProvider==='openrouter'): ?>
                            <span class="ai-badge current">กำลังใช้งาน</span>
                        <?php elseif($orHasKey): ?>
                            <span class="ai-badge ready">พร้อมใช้งาน</span>
                        <?php else: ?>
                            <span class="ai-badge waiting">ยังไม่มี Key</span>
                        <?php endif ?>
                    </div>
                </div>
                <div class="stack" style="gap:10px">
                    <label>
                        API Key
                        <input type="password" name="openrouter_api_key" id="key-openrouter" value="<?= esc($cfg['openrouter_api_key']??'','attr') ?>" placeholder="sk-or-v1-..." autocomplete="off">
                    </label>
                    <label>
                        Model
                        <input name="openrouter_model" id="model-openrouter" value="<?= esc($cfg['openrouter_model']??'deepseek/deepseek-chat','attr') ?>" placeholder="deepseek/deepseek-chat หรือ meta-llama/...">
                    </label>
                    <label>
                        Base URL
                        <input name="openrouter_base_url" id="url-openrouter" value="<?= esc($cfg['openrouter_base_url']??'https://openrouter.ai/api/v1','attr') ?>" placeholder="https://openrouter.ai/api/v1">
                    </label>
                </div>
                <div class="ai-test-panel">
                    <button type="button" class="ai-test-btn" onclick="testAiConnection('openrouter', this)">
                        <span>⚡</span> ทดสอบการเชื่อมต่อ OpenRouter
                    </button>
                    <div class="ai-test-result" id="test-result-openrouter"></div>
                </div>
            </div>

            <!-- 6. Custom / Local AI -->
            <div class="ai-provider-card <?= $activeProvider==='custom'?'active-provider':'' ?>" data-provider-card="custom">
                <div class="ai-provider-header">
                    <div class="ai-provider-title">
                        <div class="ai-provider-icon" style="background:#fee2e2; color:#dc2626">💻</div>
                        <div>
                            <div>Local AI / Ollama</div>
                            <small class="muted">Localhost / Private GPU</small>
                        </div>
                    </div>
                    <div>
                        <?php if($activeProvider==='custom'): ?>
                            <span class="ai-badge current">กำลังใช้งาน</span>
                        <?php else: ?>
                            <span class="ai-badge ready">พร้อมใช้งาน</span>
                        <?php endif ?>
                    </div>
                </div>
                <div class="stack" style="gap:10px">
                    <label>
                        API Key (ถ้ามี เช่น ollama หรือ vllm)
                        <input type="password" name="custom_api_key" id="key-custom" value="<?= esc($cfg['custom_api_key']??'ollama','attr') ?>" placeholder="ollama">
                    </label>
                    <label>
                        Model Name
                        <input name="custom_model" id="model-custom" value="<?= esc($cfg['custom_model']??'llama3.2','attr') ?>" placeholder="llama3.2 หรือ qwen2.5">
                    </label>
                    <label>
                        Base URL
                        <input name="custom_base_url" id="url-custom" value="<?= esc($cfg['custom_base_url']??'http://localhost:11434/v1','attr') ?>" placeholder="http://localhost:11434/v1">
                    </label>
                </div>
                <div class="ai-test-panel">
                    <button type="button" class="ai-test-btn" onclick="testAiConnection('custom', this)">
                        <span>⚡</span> ทดสอบการเชื่อมต่อ Local AI
                    </button>
                    <div class="ai-test-result" id="test-result-custom"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: Multi-Platform Connection -->
    <div id="tab-platforms" class="ai-tab-pane">
        <section class="panel">
            <h2>🌐 ช่องทางเชื่อมต่อ Chatbot Multi-Platform</h2>
            <p class="muted">เชื่อมต่อแชทบอทให้สามารถตอบลูกค้าได้พร้อมกันทุกช่องทาง ทั้งบนหน้าเว็บไซต์, LINE Official Account, Facebook Messenger และ Telegram Bot</p>

            <div class="ai-platform-grid">
                <!-- 1. Web Widget -->
                <div class="ai-platform-card <?= ($cfg['web_widget_enabled']??'1')==='1'?'enabled':'' ?>">
                    <div class="ai-platform-header">
                        <div class="ai-provider-title">
                            <span style="font-size:24px">🌐</span>
                            <div>
                                <div>Web Chat Widget</div>
                                <small class="muted">หน้าต่างแชทบนเว็บไซต์</small>
                            </div>
                        </div>
                        <div>
                            <select name="web_widget_enabled" style="font-size:12px; padding:4px 8px; min-height:auto">
                                <option value="1" <?= ($cfg['web_widget_enabled']??'1')==='1'?'selected':'' ?>>เปิดใช้งาน</option>
                                <option value="0" <?= ($cfg['web_widget_enabled']??'1')==='0'?'selected':'' ?>>ปิด</option>
                            </select>
                        </div>
                    </div>
                    <div class="stack" style="gap:10px">
                        <label>
                            หัวข้อหน้าต่างแชท (Widget Title)
                            <input name="web_widget_title" value="<?= esc($cfg['web_widget_title']??'SkyNext Assistant','attr') ?>" maxlength="100">
                        </label>
                        <label>
                            ข้อความต้อนรับบนเว็บ
                            <input name="web_widget_welcome" value="<?= esc($cfg['web_widget_welcome']??'สวัสดีครับ มีอะไรให้เราช่วยเหลือสอบถามได้เลยครับ','attr') ?>" maxlength="500">
                        </label>
                        <label>
                            สิทธิ์ AI เริ่มต้น
                            <select name="web_default_ai">
                                <option value="1" <?= ($cfg['web_default_ai']??'1')==='1'?'selected':'' ?>>ยินยอม AI อัตโนมัติ (ตอบด้วย AI ได้ทันที)</option>
                                <option value="0" <?= ($cfg['web_default_ai']??'1')==='0'?'selected':'' ?>>ต้องพิมพ์ "ยินยอม AI" ก่อน</option>
                            </select>
                        </label>
                    </div>
                    <div style="margin-top:auto; padding-top:16px">
                        <a href="<?= site_url('shop') ?>" target="_blank" class="button secondary small" style="width:100%">
                            🛒 เปิดหน้าร้านเพื่อทดสอบ Web Widget ↗
                        </a>
                    </div>
                </div>

                <!-- 2. LINE OA -->
                <div class="ai-platform-card <?= ($cfg['line_enabled']??'0')==='1'?'enabled':'' ?>">
                    <div class="ai-platform-header">
                        <div class="ai-provider-title">
                            <span style="font-size:24px">🟢</span>
                            <div>
                                <div>LINE Official Account</div>
                                <small class="muted">Messaging API</small>
                            </div>
                        </div>
                        <div>
                            <select name="line_enabled" style="font-size:12px; padding:4px 8px; min-height:auto">
                                <option value="1" <?= ($cfg['line_enabled']??'0')==='1'?'selected':'' ?>>เปิดใช้งาน</option>
                                <option value="0" <?= ($cfg['line_enabled']??'0')==='0'?'selected':'' ?>>ปิด</option>
                            </select>
                        </div>
                    </div>
                    <div class="stack" style="gap:10px">
                        <label>
                            Webhook URL (นำไปใส่ที่ LINE Developers)
                            <div class="copy-input-group">
                                <input type="text" id="webhook-line" value="<?= esc($webhooks['line']??site_url('hooks/line'),'attr') ?>" readonly>
                                <button type="button" class="copy-btn" onclick="copyWebhook('webhook-line')">คัดลอก</button>
                            </div>
                        </label>
                        <label>
                            Channel ID
                            <input name="line_channel_id" value="<?= esc($cfg['line_channel_id']??'','attr') ?>" placeholder="เช่น 200xxxxxxx">
                        </label>
                        <label>
                            Channel Secret
                            <input type="password" name="line_channel_secret" value="<?= esc($cfg['line_channel_secret']??'','attr') ?>" placeholder="Secret Key จาก LINE Developers">
                        </label>
                        <label>
                            Channel Access Token (Long-lived)
                            <input type="password" name="line_access_token" value="<?= esc($cfg['line_access_token']??'','attr') ?>" placeholder="Token จากแท็บ Messaging API">
                        </label>
                    </div>
                </div>

                <!-- 3. Facebook Messenger -->
                <div class="ai-platform-card <?= ($cfg['facebook_enabled']??'0')==='1'?'enabled':'' ?>">
                    <div class="ai-platform-header">
                        <div class="ai-provider-title">
                            <span style="font-size:24px">🔵</span>
                            <div>
                                <div>Facebook Messenger</div>
                                <small class="muted">Meta Graph API</small>
                            </div>
                        </div>
                        <div>
                            <select name="facebook_enabled" style="font-size:12px; padding:4px 8px; min-height:auto">
                                <option value="1" <?= ($cfg['facebook_enabled']??'0')==='1'?'selected':'' ?>>เปิดใช้งาน</option>
                                <option value="0" <?= ($cfg['facebook_enabled']??'0')==='0'?'selected':'' ?>>ปิด</option>
                            </select>
                        </div>
                    </div>
                    <div class="stack" style="gap:10px">
                        <label>
                            Webhook URL (ใส่ที่ Meta for Developers)
                            <div class="copy-input-group">
                                <input type="text" id="webhook-fb" value="<?= esc($webhooks['facebook']??site_url('hooks/facebook'),'attr') ?>" readonly>
                                <button type="button" class="copy-btn" onclick="copyWebhook('webhook-fb')">คัดลอก</button>
                            </div>
                        </label>
                        <label>
                            Verify Token (กำหนดเองเพื่อยืนยัน Webhook)
                            <input name="facebook_verify_token" value="<?= esc($cfg['facebook_verify_token']??'','attr') ?>" placeholder="เช่น skynext_fb_verify_2026">
                        </label>
                        <label>
                            Page Access Token
                            <input type="password" name="facebook_page_token" value="<?= esc($cfg['facebook_page_token']??'','attr') ?>" placeholder="Token จากเพจ Facebook">
                        </label>
                        <label>
                            Meta App Secret
                            <input type="password" name="facebook_app_secret" value="<?= esc($cfg['facebook_app_secret']??'','attr') ?>" placeholder="App Secret จากแดชบอร์ด Meta">
                        </label>
                    </div>
                </div>

                <!-- 4. Telegram Bot -->
                <div class="ai-platform-card <?= ($cfg['telegram_enabled']??'0')==='1'?'enabled':'' ?>">
                    <div class="ai-platform-header">
                        <div class="ai-provider-title">
                            <span style="font-size:24px">✈️</span>
                            <div>
                                <div>Telegram Bot</div>
                                <small class="muted">Telegram Bot API</small>
                            </div>
                        </div>
                        <div>
                            <select name="telegram_enabled" style="font-size:12px; padding:4px 8px; min-height:auto">
                                <option value="1" <?= ($cfg['telegram_enabled']??'0')==='1'?'selected':'' ?>>เปิดใช้งาน</option>
                                <option value="0" <?= ($cfg['telegram_enabled']??'0')==='0'?'selected':'' ?>>ปิด</option>
                            </select>
                        </div>
                    </div>
                    <div class="stack" style="gap:10px">
                        <label>
                            Webhook URL (สำหรับ SetWebhook)
                            <div class="copy-input-group">
                                <input type="text" id="webhook-tg" value="<?= esc($webhooks['telegram']??site_url('hooks/telegram'),'attr') ?>" readonly>
                                <button type="button" class="copy-btn" onclick="copyWebhook('webhook-tg')">คัดลอก</button>
                            </div>
                        </label>
                        <label>
                            Bot Token (จาก @BotFather)
                            <input type="password" name="telegram_bot_token" value="<?= esc($cfg['telegram_bot_token']??'','attr') ?>" placeholder="เช่น 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ">
                            <small class="muted">สร้างบอทผ่าน Telegram แชทกับ @BotFather เพื่อรับ Bot Token</small>
                        </label>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Sticky / Bottom Actions -->
    <div class="panel" style="margin-top:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; position:sticky; bottom:12px; z-index:9; box-shadow:0 6px 20px rgba(0,0,0,0.12)">
        <div style="display:flex; align-items:center; gap:10px">
            <button type="submit" style="font-size:16px; padding:12px 28px; background:var(--green)">
                💾 บันทึกการตั้งค่าทั้งหมด (Save Settings)
            </button>
            <span class="muted">การตั้งค่าจะมีผลต่อ Chatbot และ API ทุกช่องทางทันที</span>
        </div>
        <div>
            <a class="button secondary" href="<?= site_url('shop') ?>" target="_blank">
                🛒 ทดสอบหน้าร้านค้า (Shop) ↗
            </a>
        </div>
    </div>
</form>

<script>
// Tabs Navigation
document.querySelectorAll('.ai-tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-tab');
        document.querySelectorAll('.ai-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.ai-tab-pane').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const targetPane = document.getElementById(targetId);
        if (targetPane) { targetPane.classList.add('active'); }
        if (history.replaceState) { history.replaceState(null, null, '#' + targetId); }
    });
});

// Restore tab from hash
if (location.hash && document.getElementById(location.hash.replace('#', ''))) {
    const hashId = location.hash.replace('#', '');
    const tabBtn = document.querySelector(`.ai-tab-btn[data-tab="${hashId}"]`);
    if (tabBtn) { tabBtn.click(); }
}

// Active provider visual indicator
const providerSelect = document.getElementById('ai-active-provider-select');
if (providerSelect) {
    providerSelect.addEventListener('change', () => {
        const val = providerSelect.value;
        document.querySelectorAll('.ai-provider-card').forEach(c => {
            if (c.getAttribute('data-provider-card') === val) {
                c.classList.add('active-provider');
            } else {
                c.classList.remove('active-provider');
            }
        });
    });
}

// Copy Webhook URL
function copyWebhook(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = event.target;
        const orig = btn.innerText;
        btn.innerText = '✓ คัดลอกแล้ว';
        btn.style.background = '#86efac';
        setTimeout(() => { btn.innerText = orig; btn.style.background = ''; }, 2000);
    }).catch(() => {
        alert('คัดลอก: ' + input.value);
    });
}

// Test AI Connection via AJAX
function testAiConnection(provider, btnEl) {
    const btn = btnEl || (typeof event !== 'undefined' && event ? (event.currentTarget || event.target) : null);
    const resultBox = document.getElementById('test-result-' + provider);
    const keyInput = document.getElementById('key-' + provider);
    const modelInput = document.getElementById('model-' + provider);
    const urlInput = document.getElementById('url-' + provider);
    const tempInput = document.querySelector('input[name="bot_temperature"]');
    const tokensInput = document.querySelector('input[name="bot_max_tokens"]');

    if (!resultBox) return;

    resultBox.style.display = 'block';
    resultBox.className = 'ai-test-result';
    resultBox.innerHTML = '⏳ กำลังส่งคำร้องขอทดสอบไปยัง ' + provider.toUpperCase() + ' API...';

    const formCsrf = document.querySelector('#ai-main-form input[name="<?= csrf_token() ?>"]');
    const csrfVal = formCsrf ? formCsrf.value : '<?= csrf_hash() ?>';

    const formData = new FormData();
    formData.append('<?= csrf_token() ?>', csrfVal);
    formData.append('test_provider', provider);
    if (keyInput) formData.append(provider + '_api_key', keyInput.value);
    if (modelInput) formData.append(provider + '_model', modelInput.value);
    if (urlInput) formData.append(provider + '_base_url', urlInput.value);
    if (tempInput) formData.append('bot_temperature', tempInput.value);
    if (tokensInput) formData.append('bot_max_tokens', tokensInput.value);
    formData.append('test_message', 'สวัสดี ทดสอบการเชื่อมต่อ API SkyNext Platform กรุณาตอบสั้นๆ ไม่เกิน 1 ประโยค');

    if (btn) btn.disabled = true;

    fetch('<?= $actionBase ?>/test/0', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(async res => {
        const data = await res.json().catch(() => null);
        if (!res.ok) {
            if (data && data.csrf) {
                document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(i => i.value = data.csrf);
            }
            throw new Error((data && data.message) ? data.message : ('HTTP Error ' + res.status));
        }
        return data;
    })
    .then(data => {
        if (data.csrf) {
            document.querySelectorAll('input[name="<?= csrf_token() ?>"]').forEach(i => i.value = data.csrf);
        }
        const res = data.data || data;
        if (res.success) {
            resultBox.className = 'ai-test-result success';
            resultBox.innerHTML = `
                <div class="ai-test-meta">
                    <span>✅ เชื่อมต่อสำเร็จ!</span>
                    <span>โมเดล: <code>${res.model || '-'}</code></span>
                    <span>ความเร็ว: <strong>${res.latency_ms || 0} ms</strong></span>
                </div>
                <div><strong>ข้อความตอบกลับ:</strong> ${res.answer || '-'}</div>
            `;
            if (res.model && modelInput && modelInput.value !== res.model) {
                modelInput.value = res.model;
            }
        } else {
            resultBox.className = 'ai-test-result error';
            resultBox.innerHTML = `
                <div class="ai-test-meta">
                    <span>❌ การเชื่อมต่อล้มเหลว</span>
                    <span>ความเร็ว: <strong>${res.latency_ms || 0} ms</strong></span>
                </div>
                <div><strong>ข้อผิดพลาด:</strong> ${res.error || 'ไม่สามารถติดต่อ API ได้'}</div>
            `;
        }
    })
    .catch(err => {
        resultBox.className = 'ai-test-result error';
        resultBox.innerHTML = `
            <div class="ai-test-meta"><span>❌ เกิดข้อผิดพลาดในการส่งคำขอ</span></div>
            <div>${err.message || 'Network / Server Error'}</div>
        `;
    })
    .finally(() => {
        if (btn) btn.disabled = false;
    });
}
</script>
<?php endif ?>
<?php if($module==='settings'): ?><section class="panel"><form method="post" action="<?= $actionBase.'/save/0' ?>" class="stack"><?= csrf_field() ?><label>ชื่อร้าน<input name="store_name" value="<?= esc($store->setting('store_name'),'attr') ?>" maxlength="120"></label><label>ค่าจัดส่งมาตรฐาน (บาท)<input name="shipping" type="number" step="0.01" min="0" value="<?= number_format((int)$store->setting('shipping_satang')/100,2,'.','') ?>"></label><label>รายละเอียดธนาคารรับโอน<textarea name="bank_details" maxlength="2000"><?= esc($store->setting('bank_details')) ?></textarea></label><label>สถิติผู้เข้าชม<select name="analytics_enabled"><option value="1" <?= $store->setting('analytics_enabled')==='1'?'selected':'' ?>>เปิด (เก็บเมื่อผู้เข้าชมยินยอม)</option><option value="0" <?= $store->setting('analytics_enabled')==='0'?'selected':'' ?>>ปิด</option></select></label><button>บันทึกการตั้งค่า</button></form></section><?php endif ?>
<?php if($module==='integrations'): ?><div class="connection-grid"><?php foreach($extra['connections'] as $name=>$keys): ?><section class="panel connection"><h2><?= esc($name) ?></h2><span class="badge"><?= in_array(false,$keys,true)?'รอตั้งค่า Environment':'พบค่าตั้งค่า · รอทดสอบกับบริการจริง' ?></span><?php foreach($keys as $key=>$ready): ?><div class="key-status"><code><?= esc($key) ?></code><span><?= $ready?'ตั้งค่าแล้ว':'ยังไม่มี' ?></span></div><?php endforeach ?></section><?php endforeach ?></div><section class="panel"><h2>ช่องทางสนทนา</h2><form method="post" class="form-grid" action="<?= $actionBase.'/save/0' ?>"><?= csrf_field() ?><?php foreach(['line'=>'LINE','facebook'=>'Facebook'] as $channel=>$label): ?><label><?= $label ?><select name="<?= $channel ?>_enabled"><option value="0" <?= $store->setting($channel.'_enabled')==='0'?'selected':'' ?>>ปิด</option><option value="1" <?= $store->setting($channel.'_enabled')==='1'?'selected':'' ?>>เปิด (ต้องมีคีย์และ Webhook)</option></select></label><?php endforeach ?><div class="field-full"><button>บันทึกช่องทาง</button></div></form><div class="section-note">Webhook: <?= esc(site_url('hooks/line')) ?><br><?= esc(site_url('hooks/facebook')) ?><br><?= esc(site_url('hooks/stripe')) ?><br><?= esc(site_url('hooks/opn')) ?></div><p class="muted">บริการภายนอกต้องใช้ HTTPS ที่เข้าถึงได้ บัญชีผู้ให้บริการ และการอนุมัติที่จำเป็น ตั้งรหัสลับใน .env ตาม PLATFORM.md เท่านั้น แล้วรัน worker: php spark platform:work</p><p class="notice info">การตรวจสลิปปัจจุบันเป็นเจ้าหน้าที่ตรวจเทียบกับธนาคาร ยังไม่เชื่อมผู้ให้บริการตรวจสลิปอัตโนมัติ</p></section><?php endif ?>
<?php if($module==='analytics'): ?><section class="panel"><h2>การเข้าชมรายวัน · 30 วัน</h2><?php $max=max([1,...array_column($extra['daily'],'views')]); ?><div class="bar-chart"><?php foreach($extra['daily'] as $d): ?><div title="<?= esc($d['day'].' : '.$d['views'],'attr') ?>" style="height:<?= max(2,(int)($d['views']/$max*100)) ?>%"></div><?php endforeach ?></div><p class="muted">เฉพาะผู้ที่ยินยอมให้เก็บสถิติ ไม่มีการเก็บ IP หรือ Query string ในรายงาน</p></section><section class="panel"><h2>แหล่งที่มาของผู้เข้าชม</h2><?= view('platform/table',['rows'=>$extra['sources'],'module'=>'']) ?><form method="post" class="inline-form" style="margin-top:20px" action="<?= $actionBase.'/purge/0' ?>"><?= csrf_field() ?><label>ลบสถิติเก่ากว่า (วัน)<input name="days" type="number" min="1" max="730" value="90"></label><button class="secondary">ล้างสถิติตามอายุ</button></form></section><?php endif ?>
<?php if($rows||in_array($module,['blog','products','users','customers','leads','tasks','orders','payments','promotions','conversations','affiliates','audit','analytics'])): ?><section class="panel"><div class="table-toolbar"><h2><?= esc(Access::MODULES[$module]) ?></h2><?php if(in_array($module,['blog','products','users','customers','leads','orders'])): ?><form method="get"><input aria-label="ค้นหารายการ" name="q" placeholder="ค้นหารายการ…" value="<?= esc($query,'attr') ?>"><button class="small secondary">ค้นหา</button></form><?php endif ?></div><?= view('platform/table',['rows'=>$rows,'module'=>$module]) ?><?php if($module!=='analytics'): ?><div class="actions"><?php if($currentPage>1): ?><a href="?<?= esc(http_build_query(['q'=>$query,'page'=>$currentPage-1]),'attr') ?>">← ก่อนหน้า</a><?php endif ?><span class="muted">หน้า <?= $currentPage ?> · สูงสุด 50 รายการต่อหน้า</span><?php if(count($rows)===50): ?><a href="?<?= esc(http_build_query(['q'=>$query,'page'=>$currentPage+1]),'attr') ?>">ถัดไป →</a><?php endif ?></div><?php endif ?></section><?php endif ?>
<?php if(!empty($extra['activities'])): ?><section class="panel"><h2>ประวัติการติดตาม Lead</h2><?= view('platform/table',['rows'=>$extra['activities'],'module'=>'']) ?></section><?php endif ?>

<?php if(!empty($extra['gateway_attempts'])): ?><?= view('platform/gateway_admin',['attempts'=>$extra['gateway_attempts']]) ?><?php endif ?>

<?php if($module==='integrations'): ?>
<section class="panel"><h2>ตรวจสลิปอัตโนมัติ</h2>
<p>สถานะ: <?= \App\Libraries\Platform\SlipPayments::enabled() ? 'เปิดตรวจอัตโนมัติ' : 'รอตั้งค่าผู้ให้บริการ' ?></p>
<p>ตั้งค่า SLIP_PROVIDER เป็น easyslip หรือ slipok และ SLIP_AUTO_VERIFY เป็น 1 ใน .env พร้อม API key รหัสธนาคาร 3 หลัก และเลขบัญชีรับเงิน ต้องผูกบัญชีเดียวกันกับผู้ให้บริการด้วย</p>
<p>ระบบตรวจยอด บัญชีผู้รับ เวลาการโอน และธุรกรรมซ้ำ หากยืนยันไม่ได้จะรอเจ้าหน้าที่ตรวจสอบ ดูผลได้ในรายการชำระเงิน</p></section>
<?php endif ?>

<?php if($module==='blog'): ?><p class="section-note">เพิ่มบทความใหม่ แก้ไข อัปโหลดรูปปก และเลือกเผยแพร่ได้จากแบบฟอร์มด้านบน เฉพาะสถานะเผยแพร่จะแสดงในหน้าบทความ การเก็บเข้าคลังเป็นการซ่อนและเปิดกลับได้</p><a class="button secondary" href="<?= site_url('blog') ?>">เปิดหน้ารวมบทความ ↗</a><?php endif ?>
<?php if($module==='affiliates'&&!empty($extra['affiliate_owner'])): $member=$extra['affiliate_owner'];$tree=$extra['affiliate_tree']; ?>
<section class="panel"><h2>สายงานของ <?= esc($member['name']) ?></h2><p>พบ <?= $tree['total'] ?> คน · แสดงสูงสุด 30 ชั้น หน้าละ 50 คน</p><div class="table-scroll"><table><thead><tr><th>สมาชิก</th><th>ผู้แนะนำ</th><th>ระยะการแนะนำ</th></tr></thead><tbody><?php foreach($tree['members'] as $child): ?><tr><td><?= esc($child['name']) ?><br><small><?= esc($child['referral_code']) ?></small></td><td><?= esc($child['sponsor']) ?></td><td>ชั้น <?= $child['depth'] ?></td></tr><?php endforeach ?></tbody></table></div><div class="actions"><?php if($currentPage>1): ?><a href="?<?= http_build_query(['edit'=>$member['id'],'page'=>$currentPage-1]) ?>">← ก่อนหน้า</a><?php endif ?><?php if($currentPage*50<$tree['total']): ?><a href="?<?= http_build_query(['edit'=>$member['id'],'page'=>$currentPage+1]) ?>">ถัดไป →</a><?php endif ?></div></section>
<?php if($user['role']==='admin'): ?><section class="panel"><h2>จัดการโปรไฟล์ Affiliate ของสมาชิก</h2><p class="muted">แก้ไขข้อมูลโปรไฟล์ บัญชีรับเงิน รหัส Affiliate ID ออกโดยระบบและเปลี่ยนไม่ได้</p><form method="post" class="stack" action="<?= site_url('admin/affiliates/profile/'.$member['id']) ?>"><?= csrf_field() ?><label>รหัส Affiliate ID (ระบบกำหนดอัตโนมัติ)<input readonly aria-readonly="true" value="<?= esc($member['referral_code'],'attr') ?>" pattern="[A-Za-z0-9_-]{3,30}" placeholder="ระบบออกเลขเริ่มต้น 0001 และเรียงต่อเนื่อง"><small class="muted">ความยาว 3–30 ตัวอักษร (A-Z, 0-9, ขีดกลาง -, ขีดล่าง _) ไม่ซ้ำกับผู้อื่น</small></label><label>ชื่อที่แสดง<input name="public_name" maxlength="100" value="<?= esc($member['public_name'],'attr') ?>"></label><label>LINE URL<input name="line_url" maxlength="250" value="<?= esc($member['line_url'],'attr') ?>"></label><label>หัวข้อหน้าโปรโมท<input name="affiliate_headline" maxlength="180" value="<?= esc($member['affiliate_headline'],'attr') ?>"></label><label>ข้อความแนะนำ<textarea name="affiliate_bio" maxlength="2000"><?= esc($member['affiliate_bio']) ?></textarea></label><div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px;"><label>ธนาคาร<input name="bank_name" maxlength="100" value="<?= esc($member['bank_name']??'','attr') ?>" placeholder="เช่น กสิกรไทย"></label><label>เลขที่บัญชี<input name="bank_account" maxlength="50" value="<?= esc($member['bank_account']??'','attr') ?>"></label><label>ชื่อบัญชี<input name="bank_account_name" maxlength="120" value="<?= esc($member['bank_account_name']??'','attr') ?>"></label></div><label>สถานะโปรไฟล์<select name="affiliate_public"><option value="0" <?= !$member['affiliate_public']?'selected':'' ?>>ซ่อนชื่อและ LINE</option><option value="1" <?= $member['affiliate_public']?'selected':'' ?>>เผยแพร่ชื่อและ LINE</option></select></label><button>บันทึกโปรไฟล์สมาชิก</button></form></section><?php endif ?>
<?php endif ?>

<?php if($module==='menus'): ?>
<section class="panel">
    <div class="table-toolbar">
        <div>
            <h2>จัดการการแสดงผลเมนูตามสิทธิ์ (Role Menu Management)</h2>
            <p class="muted">กำหนดสิทธิ์การมองเห็นเมนูหลังบ้านสำหรับแต่ละ Role โดย Admin สามารถ ลด หรือ เพิ่ม เมนูได้อย่างอิสระ ผู้ใช้จะมองเห็นและเข้าถึงได้เฉพาะเมนูที่ได้รับอนุญาตเท่านั้น (Admin มีสิทธิ์เข้าถึงทุกเมนูโดยอัตโนมัติ)</p>
        </div>
    </div>
    
    <form method="post" action="<?= $actionBase.'/save/0' ?>">
        <?= csrf_field() ?>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th style="min-width:180px">เมนู / โมดูลระบบ</th>
                        <th style="min-width:130px">คีย์โมดูล</th>
                        <?php foreach($extra['roles'] as $rKey=>$rLabel): ?>
                            <th style="text-align:center; min-width:140px">
                                <div><?= esc($rLabel) ?></div>
                                <small class="muted"><code><?= esc($rKey) ?></code></small>
                            </th>
                        <?php endforeach ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $adminOnly = \App\Libraries\Platform\Access::ADMIN_ONLY;
                    foreach($extra['modules'] as $mKey=>$mLabel): 
                        $isAdminSpecial = in_array($mKey, $adminOnly, true);
                    ?>
                    <tr>
                        <td>
                            <strong><?= esc($mLabel) ?></strong>
                            <?php if($isAdminSpecial): ?>
                                <span class="badge" style="font-size:11px; margin-left:6px; background:#fef3c7; color:#92400e; padding:2px 6px; border-radius:4px">เฉพาะ Admin</span>
                            <?php endif ?>
                        </td>
                        <td><code><?= esc($mKey) ?></code></td>
                        <?php foreach($extra['roles'] as $rKey=>$rLabel): 
                            $isChecked = in_array($mKey, $extra['role_menus'][$rKey] ?? [], true);
                        ?>
                            <td style="text-align:center">
                                <?php if($isAdminSpecial): ?>
                                    <span class="muted" title="สงวนสิทธิ์เฉพาะผู้ดูแลระบบ">—</span>
                                <?php else: ?>
                                    <label style="display:inline-flex; align-items:center; justify-content:center; cursor:pointer; width:100%; height:100%; margin:0">
                                        <input type="checkbox" name="role_menus[<?= esc($rKey,'attr') ?>][]" value="<?= esc($mKey,'attr') ?>" <?= $isChecked ? 'checked' : '' ?> <?= in_array($rKey,['downline_team','external_team'],true)?'disabled':'' ?> style="width:18px; height:18px; cursor:pointer">
                                    </label>
                                <?php endif ?>
                            </td>
                        <?php endforeach ?>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <div class="actions" style="margin-top:20px; display:flex; gap:12px; align-items:center">
            <button type="submit">💾 บันทึกสิทธิ์การแสดงผลเมนู</button>
            <span class="muted">การเปลี่ยนแปลงจะมีผลทันทีต่อผู้ใช้ที่ล็อกอินในแต่ละ Role</span>
        </div>
    </form>
</section>
<?php endif ?>

<?php if($module==='affiliate_profile' && !empty($extra['profile_user'])): $prof=$extra['profile_user']; ?>
<div class="grid-two">
    <section class="panel">
        <div class="table-toolbar">
            <h2>ข้อมูลโปรไฟล์ Affiliate</h2>
            <?php if($user['role']==='admin' && $prof['id']!==$user['id']): ?>
                <span class="badge">แก้ไขของ: <?= esc($prof['name']) ?> (#<?= $prof['id'] ?>)</span>
            <?php endif ?>
        </div>
        <p class="muted">ข้อมูลนี้จะแสดงในหน้าแนะนำตัวสาธารณะเมื่อเปิดการเผยแพร่ และใช้สำหรับติดต่อกับลูกค้าในสายงาน</p>

        <form method="post" action="<?= $actionBase.'/save/'.$prof['id'] ?>" class="stack" style="margin-top:16px">
            <?= csrf_field() ?>
            <label>
                รหัส Affiliate ID / Referral Code (กำหนดเองได้) *
                <input readonly aria-readonly="true" value="<?= esc($prof['referral_code'],'attr') ?>" pattern="[A-Za-z0-9_-]{3,30}" placeholder="ระบบออกเลขเริ่มต้น 0001 และเรียงต่อเนื่อง" required>
                <small class="muted">ความยาว 3–30 ตัวอักษร (A-Z, 0-9, ขีดกลาง -, ขีดล่าง _) ใช้เป็นรหัสแนะนำและลิงก์ส่วนตัวของคุณ</small>
            </label>

            <label>
                ชื่อหรือนามแฝงที่แสดง (Public Name) *
                <input name="public_name" maxlength="100" value="<?= esc($prof['public_name'],'attr') ?>" placeholder="เช่น โค้ชต้น / คุณสมชาย การตลาดออนไลน์" required>
            </label>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px">
                <label>
                    เบอร์โทรศัพท์ติดต่อ
                    <input name="phone" maxlength="40" value="<?= esc($prof['phone'],'attr') ?>" placeholder="08x-xxx-xxxx">
                </label>
                <label>
                    LINE ID หรือ LINE URL *
                    <input name="line_url" maxlength="250" value="<?= esc($prof['line_url'],'attr') ?>" placeholder="@lineid หรือ https://line.me/ti/p/..." required>
                    <small class="muted">ใส่ @ID หรือ ลิงก์ lin.ee / line.me</small>
                </label>
            </div>

            <label>
                หัวข้อ / คำโปรยหน้าโปรโมท (Headline)
                <input name="affiliate_headline" maxlength="180" value="<?= esc($prof['affiliate_headline'],'attr') ?>" placeholder="เช่น ที่ปรึกษาธุรกิจครบวงจร ยินดีให้คำแนะนำฟรี">
            </label>

            <label>
                ข้อความแนะนำตัว / ประวัติโดยย่อ (Bio)
                <textarea name="affiliate_bio" maxlength="2000" rows="4" placeholder="แนะนำตัวและประสบการณ์ของคุณ..."><?= esc($prof['affiliate_bio']) ?></textarea>
            </label>

            <fieldset style="border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin:8px 0">
                <legend style="padding:0 8px; font-weight:600; color:#1e293b">🏦 ข้อมูลบัญชีธนาคารสำหรับรับเงินโอน</legend>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px">
                    <label>
                        ชื่อธนาคาร
                        <input name="bank_name" maxlength="100" value="<?= esc($prof['bank_name']??'','attr') ?>" placeholder="เช่น กสิกรไทย, ไทยพาณิชย์">
                    </label>
                    <label>
                        เลขที่บัญชี
                        <input name="bank_account" maxlength="50" value="<?= esc($prof['bank_account']??'','attr') ?>" placeholder="เช่น 123-4-56789-0">
                    </label>
                    <label>
                        ชื่อบัญชี
                        <input name="bank_account_name" maxlength="120" value="<?= esc($prof['bank_account_name']??'','attr') ?>" placeholder="ชื่อ-นามสกุลเจ้าของบัญชี">
                    </label>
                </div>
            </fieldset>

            <label>
                สถานะการเผยแพร่โปรไฟล์
                <select name="affiliate_public">
                    <option value="1" <?= $prof['affiliate_public'] ? 'selected' : '' ?>>เปิดเผยแพร่ (แสดงหน้าเว็บส่วนตัวและปุ่มติดต่อ LINE)</option>
                    <option value="0" <?= !$prof['affiliate_public'] ? 'selected' : '' ?>>ซ่อนโปรไฟล์ (ไม่แสดงข้อมูลสาธารณะ)</option>
                </select>
            </label>

            <div class="actions" style="margin-top:12px">
                <button type="submit">💾 บันทึกโปรไฟล์ Affiliate</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>ลิงก์และเครื่องมือแนะนำของฉัน</h2>
        <p class="muted">ใช้ลิงก์เหล่านี้ในการแนะนำสมาชิกใหม่ เมื่อมีผู้สมัครผ่านลิงก์ ระบบจะผูกเป็นสมาชิกในสายงานของคุณทันที</p>

        <div style="margin:20px 0; padding:16px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0">
            <label style="font-weight:600">รหัสแนะนำของคุณ (Referral Code)</label>
            <div style="display:flex; gap:8px; align-items:center; margin-top:6px">
                <input type="text" readonly value="<?= esc($prof['referral_code']) ?>" style="font-size:18px; font-weight:700; letter-spacing:1px; background:#fff">
                <button type="button" class="secondary" onclick="navigator.clipboard.writeText('<?= esc($prof['referral_code'],'js') ?>');alert('คัดลอกรหัสแล้ว');">คัดลอก</button>
            </div>
        </div>

        <div style="margin:16px 0; padding:16px; background:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0">
            <label style="font-weight:600; color:#166534">🔗 ลิงก์แนะนำสมัครสมาชิก (Referral URL)</label>
            <p class="muted" style="margin:4px 0 8px 0; font-size:13px">ส่งลิงก์นี้ให้ผู้ที่สนใจสมัครสมาชิก</p>
            <div style="display:flex; gap:8px; align-items:center">
                <input type="text" readonly value="<?= site_url('r/'.$prof['referral_code']) ?>" style="background:#fff">
                <button type="button" class="secondary" onclick="navigator.clipboard.writeText('<?= site_url('r/'.$prof['referral_code']) ?>');alert('คัดลอกลิงก์แนะนำแล้ว');">คัดลอก</button>
            </div>
        </div>

        <div style="margin:16px 0; padding:16px; background:#eff6ff; border-radius:8px; border:1px solid #bfdbfe">
            <label style="font-weight:600; color:#1e40af">🌐 หน้าเว็บแนะนำส่วนตัว (Landing Page)</label>
            <p class="muted" style="margin:4px 0 8px 0; font-size:13px">หน้าเว็บพร้อมข้อมูลโปรไฟล์และช่องทางติดต่อ LINE ของคุณ</p>
            <div style="display:flex; gap:8px; align-items:center">
                <input type="text" readonly value="<?= site_url('p/'.$prof['referral_code']) ?>" style="background:#fff">
                <a class="button secondary" href="<?= site_url('p/'.$prof['referral_code']) ?>" target="_blank">เปิดดู ↗</a>
            </div>
        </div>

        <div class="notice info" style="margin-top:20px">
            <strong>คำแนะนำ:</strong> เมื่อกรอกชื่อ นามแฝง และ LINE แล้ว อย่าลืมเลือกสถานะเป็น "เปิดเผยแพร่" เพื่อให้ผู้เข้าชมสามารถติดต่อท่านได้โดยตรง
        </div>
    </section>
</div>
<?php endif ?>
