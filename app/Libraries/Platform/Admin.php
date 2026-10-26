<?php
namespace App\Libraries\Platform;

use RuntimeException;

class Admin
{
    public function __construct(public Store $s) {}
    public static function fields(string $module): array
    {
        return match ($module) {
            'blog' => ['title'=>['ชื่อบทความ','text'],'slug'=>['ลิงก์บทความ a-z, 0-9, ขีดกลาง (เว้นว่างเพื่อสร้างอัตโนมัติ)','text'],'category'=>['หมวดหมู่ (เลือกประสบการณ์ผู้ใช้สินค้า เพื่อรวมในหน้าประสบการณ์)','datalist',[Blog::EXPERIENCE_CATEGORY]],'excerpt'=>['คำโปรย','textarea'],'body'=>['เนื้อหาบทความ (ข้อความ แบ่งย่อหน้าด้วยบรรทัดว่าง)','textarea'],'cover_image'=>['รูปปกภายในระบบ (เว้นว่างเพื่อใช้ภาพกราฟิก)','text'],'status'=>['สถานะ','select',['draft'=>'แบบร่าง','published'=>'เผยแพร่','archived'=>'เก็บเข้าคลัง / ซ่อน']]],
            'products' => ['name'=>['ชื่อสินค้า','text'], 'sku'=>['รหัส SKU','text'], 'price'=>['ราคา (บาท)','number'], 'member_price'=>['ราคาสมาชิก (บาท / เว้นว่างถ้าไม่มี)','number'], 'discount_kind'=>['ประเภทส่วนลดสินค้า','select',[''=>'ไม่มีส่วนลด','percent'=>'เปอร์เซ็นต์','fixed'=>'จำนวนเงินบาท']], 'discount_value'=>['ส่วนลด (% หรือ บาท)','number'], 'pv'=>['PV ต่อหน่วย (0 = รอกำหนด)','integer'], 'stock'=>['สต็อกจริง','integer'], 'category'=>['หมวดหมู่','text'], 'short_description'=>['คำอธิบายสั้น','textarea'], 'description'=>['รายละเอียด','textarea'], 'inquiry_only'=>['รูปแบบการขาย','select',['0'=>'สั่งซื้อผ่านตะกร้า','1'=>'สอบถาม / บริการรายปี']], 'inquiry_label'=>['ข้อความปุ่มสอบถาม','text'], 'image'=>['ไฟล์ภาพในเครื่อง (mirror/assets/...)','text'], 'active'=>['สถานะ','select',['1'=>'เปิดขาย','0'=>'ซ่อนสินค้า']]],
            'promotions' => ['code'=>['รหัสโปรโมชั่น','text'], 'kind'=>['ประเภท','select',['percent'=>'เปอร์เซ็นต์','fixed'=>'ลดจำนวนบาท']], 'value'=>['ส่วนลด (% หรือ บาท)','number'], 'minimum'=>['ยอดขั้นต่ำ (บาท)','number'], 'starts_at'=>['เริ่มต้น (เวลาไทย)','datetime-local'], 'ends_at'=>['สิ้นสุด (เวลาไทย)','datetime-local'], 'usage_limit'=>['จำกัดจำนวนสิทธิ์','integer'], 'active'=>['สถานะ','select',['1'=>'ใช้งาน','0'=>'ปิด']]],
            'users' => ['name'=>['ชื่อ','text'], 'email'=>['อีเมล','email'], 'phone'=>['โทรศัพท์','text'], 'password'=>['รหัสผ่าน (อย่างน้อย 12 ตัว / เว้นว่างเพื่อใช้รหัสเดิม)','password'], 'role'=>['สิทธิ์ (Role)','select',['downline_team'=>'ทีมงานใต้สายงาน','external_team'=>'ทีมงานต่างสายงาน','affiliate'=>'Affiliate (พันธมิตร)','member'=>'สมาชิกทั่วไป','sales'=>'ฝ่ายขาย CRM','manager'=>'ผู้จัดการร้าน','admin'=>'ผู้ดูแลระบบ']], 'active'=>['สถานะ','select',['1'=>'ใช้งาน','0'=>'ระงับ']]],
            'leads' => ['name'=>['ชื่อ Lead','text'], 'contact'=>['อีเมลหรือเบอร์โทร','text'], 'source'=>['แหล่งที่มา','text'], 'stage'=>['ขั้นตอน','select',['new'=>'ใหม่','contacted'=>'ติดต่อแล้ว','qualified'=>'มีคุณสมบัติ','proposal'=>'เสนอราคา','won'=>'ปิดการขาย','lost'=>'ไม่สำเร็จ']], 'score'=>['คะแนนความสนใจ 0–100','integer'], 'owner_id'=>['ผู้รับผิดชอบ','staff'], 'notes'=>['ข้อมูล / บันทึกการติดตาม','textarea']],
            'tasks' => ['title'=>['งานที่ต้องทำ','text'], 'lead_id'=>['Lead ID (เว้นว่างได้)','integer'], 'owner_id'=>['ผู้รับผิดชอบ','staff'], 'due_at'=>['กำหนดติดตาม (เวลาไทย)','datetime-local'], 'status'=>['สถานะ','select',['open'=>'รอดำเนินการ','done'=>'เสร็จแล้ว']]],
            default => [],
        };
    }
    public function data(string $module, int $edit, string $query, int $page, ?array $actor = null): array
    {
        $offset = ($page-1)*50; $rows=[]; $stats=[]; $record=null; $extra=[];
        $params=[];
        $sql = match($module) {
            'blog'=>"SELECT id,title,slug,category,status,published_at,updated_at FROM blog WHERE title LIKE ? OR excerpt LIKE ? ORDER BY updated_at DESC,id DESC",
            'products'=>'SELECT id,sku,name,price_satang,discount_kind,discount_value,pv,stock,active,category FROM products WHERE name LIKE ? OR sku LIKE ? ORDER BY id DESC',
            'promotions'=>'SELECT * FROM promotions ORDER BY id DESC',
            'users'=>'SELECT id,name,email,role,active,phone,referral_code,sponsor_id,created_at FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY id DESC',
            'customers'=>"SELECT u.id,u.name,u.email,u.phone,COUNT(o.id) orders_count,COALESCE(SUM(CASE WHEN o.status IN ('paid','processing','shipped','completed') THEN o.total_satang ELSE 0 END),0) lifetime_satang FROM users u LEFT JOIN orders o ON u.id=o.user_id WHERE u.role IN ('member','affiliate','downline_team','external_team') AND (u.name LIKE ? OR u.email LIKE ?) GROUP BY u.id ORDER BY u.id DESC",
            'orders'=>'SELECT o.id,o.number,u.name,o.total_satang,o.status,o.recipient,o.phone,o.address,o.tracking,o.created_at FROM orders o JOIN users u ON u.id=o.user_id WHERE o.number LIKE ? OR u.name LIKE ? ORDER BY o.id DESC',
            'payments'=>'SELECT p.id,o.number,p.amount_satang,p.reference,p.status,p.review_note,c.status slip_check,c.note verification_note,t.token payment_token,t.revoked_at token_revoked_at,p.created_at FROM payments p JOIN orders o ON o.id=p.order_id LEFT JOIN slip_checks c ON c.payment_id=p.id LEFT JOIN payment_tokens t ON t.payment_id=p.id ORDER BY p.id DESC',
            'leads'=>"SELECT l.*,u.name owner FROM leads l LEFT JOIN users u ON u.id=l.owner_id WHERE l.name LIKE ? OR l.contact LIKE ? ORDER BY CASE l.stage WHEN 'new' THEN 0 WHEN 'qualified' THEN 1 ELSE 2 END,l.id DESC",
            'tasks'=>'SELECT t.*,u.name owner FROM tasks t LEFT JOIN users u ON u.id=t.owner_id ORDER BY t.status DESC,t.due_at',
            'conversations'=>"SELECT c.id,c.platform,c.external_id,c.mode,c.status,l.name lead,c.updated_at FROM conversations c LEFT JOIN leads l ON l.id=c.lead_id ORDER BY c.updated_at DESC",
            'affiliates'=>($actor && $actor['role']==='affiliate')
                ? "SELECT u.id,u.name,u.referral_code,p.name sponsor,(SELECT COUNT(*) FROM referral_clicks c WHERE c.sponsor_id=u.id) link_clicks,(SELECT COUNT(*) FROM users d WHERE d.sponsor_id=u.id) direct_members,(SELECT COUNT(*) FROM orders o JOIN users r ON r.id=o.user_id WHERE r.sponsor_id=u.id) referred_orders FROM users u LEFT JOIN users p ON p.id=u.sponsor_id WHERE (u.id=? OR u.sponsor_id=?) ORDER BY u.id DESC"
                : "SELECT u.id,u.name,u.referral_code,p.name sponsor,(SELECT COUNT(*) FROM referral_clicks c WHERE c.sponsor_id=u.id) link_clicks,(SELECT COUNT(*) FROM users d WHERE d.sponsor_id=u.id) direct_members,(SELECT COUNT(*) FROM orders o JOIN users r ON r.id=o.user_id WHERE r.sponsor_id=u.id) referred_orders FROM users u LEFT JOIN users p ON p.id=u.sponsor_id ORDER BY u.id DESC",
            'audit'=>'SELECT a.id,u.name actor,a.action,a.entity,a.entity_id,a.detail,a.created_at FROM audit a LEFT JOIN users u ON u.id=a.actor_id ORDER BY a.id DESC',
            default=>'',
        };
        if ($module==='affiliates' && $actor && $actor['role']==='affiliate') {
            $params = [$actor['id'], $actor['id']];
        } elseif (str_contains($sql,'LIKE ?')) {
            $params=['%'.$query.'%','%'.$query.'%'];
        }
        if ($sql) { $rows=$this->s->all($sql.' LIMIT 50 OFFSET '.$offset,$params); }
        if($module==='products'){
            foreach($rows as &$row){
                $row['discount']=($row['discount_kind']??'')!==''?(($row['discount_kind']==='percent'?rtrim(rtrim(number_format($row['discount_value']/100,2),'0'),'.').'%':number_format($row['discount_value']/100,2).' บาท')):'—';
                unset($row['discount_kind'],$row['discount_value']);
            }
            unset($row);
        }
        if (self::fields($module) && $edit) {
            $record=$this->s->one('SELECT * FROM '.$module.' WHERE id=?',[$edit]);
            if ($record) {
                unset($record['password_hash']); $record['password']='';
                if ($module==='products') { $record['price']=number_format($record['price_satang']/100,2,'.',''); $record['member_price']=isset($record['member_price_satang'])?number_format($record['member_price_satang']/100,2,'.',''):''; $record['discount_value']=$record['discount_kind']==='percent'?number_format($record['discount_value']/100,2,'.',''):($record['discount_kind']==='fixed'?number_format($record['discount_value']/100,2,'.',''):''); }
                if ($module==='promotions') { $record['minimum']=number_format($record['minimum_satang']/100,2,'.',''); $record['value']=number_format($record['value']/100,2,'.',''); }
                if ($module==='leads') { $extra['activities']=$this->s->all('SELECT a.*,u.name actor FROM activities a LEFT JOIN users u ON u.id=a.actor_id WHERE lead_id=? ORDER BY a.id DESC LIMIT 30',[$edit]); }
            }
        }
        if ($module==='orders' && $edit) {
            $extra['order']=$this->s->one('SELECT * FROM orders WHERE id=?',[$edit]);
            $extra['items']=$this->s->all('SELECT * FROM order_items WHERE order_id=?',[$edit]);
            $extra['gateway_attempts']=$this->s->all('SELECT * FROM gateway_attempts WHERE order_id=? ORDER BY id DESC',[$edit]);
        }
        if ($module==='conversations' && $edit) {
            $extra['conversation']=$this->s->one('SELECT * FROM conversations WHERE id=?',[$edit]);
            $extra['messages']=$this->s->all('SELECT * FROM messages WHERE conversation_id=? ORDER BY id DESC LIMIT 100',[$edit]);
            $extra['outbox']=$this->s->all('SELECT * FROM outbox WHERE conversation_id=? ORDER BY id DESC LIMIT 20',[$edit]);
        }
        if ($module==='dashboard') {
            if ($actor && $actor['role']==='affiliate') {
                $uid=(int)$actor['id'];
                $clicks=(int)($this->s->one("SELECT COUNT(*) n FROM referral_clicks WHERE sponsor_id=?",[$uid])['n']??0);
                $members=(int)($this->s->one("SELECT COUNT(*) n FROM users WHERE sponsor_id=?",[$uid])['n']??0);
                $ordersCount=(int)($this->s->one("SELECT COUNT(o.id) n FROM orders o JOIN users u ON u.id=o.user_id WHERE u.sponsor_id=?",[$uid])['n']??0);
                $salesSatang=(int)($this->s->one("SELECT COALESCE(SUM(o.total_satang),0) n FROM orders o JOIN users u ON u.id=o.user_id WHERE u.sponsor_id=? AND o.status IN ('paid','processing','shipped','completed')",[$uid])['n']??0);
                $stats=['จำนวนคลิกลิงก์แนะนำ'=>number_format($clicks),'สมาชิกที่แนะนำตรง'=>number_format($members),'คำสั่งซื้อจากเครือข่าย'=>number_format($ordersCount),'ยอดขายเครือข่าย (บาท)'=>number_format($salesSatang/100,2)];
                $extra['recent_orders']=$this->s->all("SELECT o.number,o.status,o.total_satang,o.created_at FROM orders o JOIN users u ON u.id=o.user_id WHERE u.sponsor_id=? ORDER BY o.id DESC LIMIT 8",[$uid]);
                $extra['pipeline']=[];
            } else {
                $stats=['ยอดขายรับชำระ (บาท)' => number_format(($this->s->one("SELECT COALESCE(SUM(total_satang),0) n FROM orders WHERE status IN ('paid','processing','shipped','completed')")['n'])/100,2), 'คำสั่งซื้อทั้งหมด'=>$this->s->one('SELECT COUNT(*) n FROM orders')['n'], 'รอตรวจชำระ'=>$this->s->one("SELECT COUNT(*) n FROM payments WHERE status='pending'")['n'], 'Lead ใหม่'=>$this->s->one("SELECT COUNT(*) n FROM leads WHERE stage='new'")['n'], 'สมาชิก'=>$this->s->one('SELECT COUNT(*) n FROM users')['n'], 'สินค้าสต็อกหมด'=>$this->s->one('SELECT COUNT(*) n FROM products WHERE active=1 AND stock=0')['n']];
                $extra['recent_orders']=$this->s->all('SELECT number,status,total_satang,created_at FROM orders ORDER BY id DESC LIMIT 8');
                $extra['pipeline']=$this->s->all('SELECT stage,COUNT(*) count FROM leads GROUP BY stage');
            }
        }
        if ($module==='menus') {
            $extra['role_menus']=Access::getRoleMenus($this->s);
            $extra['roles']=Access::ROLES;
            $extra['modules']=Access::MODULES;
        }
        if ($module==='affiliate_profile') {
            $targetId=($actor && $actor['role']!=='admin')?(int)$actor['id']:($edit?:(int)($actor['id']??0));
            $extra['profile_user']=$this->s->one('SELECT id,name,email,phone,public_name,line_url,affiliate_headline,affiliate_bio,affiliate_public,bank_name,bank_account,bank_account_name,referral_code FROM users WHERE id=?',[$targetId]);
        }
        if ($module==='analytics') {
            $stats=['การเข้าชม 30 วัน'=>$this->s->one("SELECT COUNT(*) n FROM visits WHERE created_at>=datetime('now','-30 days')")['n'], 'ผู้เข้าชมที่ยินยอม (30 วัน)'=>$this->s->one("SELECT COUNT(DISTINCT visitor) n FROM visits WHERE created_at>=datetime('now','-30 days')")['n'], 'คำสั่งซื้อ 30 วัน'=>$this->s->one("SELECT COUNT(*) n FROM orders WHERE created_at>=datetime('now','-30 days')")['n']];
            $rows=$this->s->all("SELECT path,COUNT(*) pageviews,COUNT(DISTINCT visitor) visitors FROM visits WHERE created_at>=datetime('now','-30 days') GROUP BY path ORDER BY pageviews DESC LIMIT 50");
            $extra['daily']=$this->s->all("SELECT substr(created_at,1,10) day,COUNT(*) views FROM visits WHERE created_at>=datetime('now','-30 days') GROUP BY day ORDER BY day");
            $extra['sources']=$this->s->all("SELECT referrer,source,COUNT(*) views FROM visits GROUP BY referrer,source ORDER BY views DESC LIMIT 20");
        }
        if($module==='affiliates' && $edit){
            $extra['affiliate_owner']=$this->s->one('SELECT id,name,public_name,line_url,affiliate_headline,affiliate_bio,affiliate_public,referral_code FROM users WHERE id=?',[$edit]);
            if($extra['affiliate_owner']){$extra['affiliate_tree']=(new Affiliate($this->s))->network($edit,'',$page);}
        }
        if ($module==='integrations') { $extra['connections']=Gateways::connections($this->s); }
        if ($module==='ai') {
            $extra['ai_stats'] = [
                'conversations' => (int)($this->s->one("SELECT COUNT(*) c FROM conversations")['c'] ?? 0),
                'messages' => (int)($this->s->one("SELECT COUNT(*) c FROM messages")['c'] ?? 0),
                'leads' => (int)($this->s->one("SELECT COUNT(*) c FROM leads WHERE source LIKE '%chat%'")['c'] ?? 0),
                'active_human' => (int)($this->s->one("SELECT COUNT(*) c FROM conversations WHERE mode='human'")['c'] ?? 0),
            ];
            $extra['settings'] = [
                'bot_enabled' => $this->s->setting('bot_enabled', '1'),
                'ai_enabled' => $this->s->setting('ai_enabled', '1'),
                'bot_mode' => $this->s->setting('bot_mode', 'hybrid'),
                'bot_welcome_msg' => $this->s->setting('bot_welcome_msg', 'สวัสดีครับ มีอะไรให้เราช่วยเหลือสอบถามได้เลยครับ'),
                'bot_fallback_msg' => $this->s->setting('bot_fallback_msg', 'ขออภัยครับ ยังไม่พบข้อมูลในระบบ หรือต้องการให้เจ้าหน้าที่ติดต่อกลับพิมพ์ "แอดมิน" ได้เลยครับ'),
                'bot_handoff_msg' => $this->s->setting('bot_handoff_msg', 'ส่งต่อให้เจ้าหน้าที่แล้วค่ะ กรุณาฝากชื่อและเบอร์โทร/LINE ทีมงานจะตอบกลับในกล่องข้อความนี้โดยเร็ว'),
                'bot_handoff_keywords' => $this->s->setting('bot_handoff_keywords', 'แอดมิน, เจ้าหน้าที่, human, agent, ติดต่อคน'),
                'bot_system_prompt' => $this->s->setting('bot_system_prompt', 'คุณคือ AI ผู้ช่วยร้านค้าอัจฉริยะของ SkyNext ตอบคำถามลูกค้าเป็นภาษาไทยอย่างสุภาพ กระชับ เป็นมิตร และให้ข้อมูลที่ถูกต้องเป็นประโยชน์ตามข้อมูลร้านค้าและรายการสินค้าที่ให้ไว้เท่านั้น'),
                'bot_include_catalog' => $this->s->setting('bot_include_catalog', '1'),
                'bot_knowledge' => $this->s->setting('bot_knowledge', ''),
                'bot_temperature' => $this->s->setting('bot_temperature', '0.7'),
                'bot_max_tokens' => $this->s->setting('bot_max_tokens', '800'),
                'bot_rate_limit' => $this->s->setting('bot_rate_limit', '200'),
                'bot_lead_capture' => $this->s->setting('bot_lead_capture', '1'),

                'ai_provider' => $this->s->setting('ai_provider') ?: (env('AI_PROVIDER') ?: 'deepseek'),

                'deepseek_api_key' => $this->s->setting('deepseek_api_key') ?: (string)env('DEEPSEEK_API_KEY'),
                'deepseek_model' => $this->s->setting('deepseek_model') ?: (string)env('DEEPSEEK_MODEL') ?: 'deepseek-chat',
                'deepseek_base_url' => $this->s->setting('deepseek_base_url') ?: (string)env('DEEPSEEK_BASE_URL') ?: 'https://api.deepseek.com',

                'openai_api_key' => $this->s->setting('openai_api_key') ?: (env('AI_PROVIDER') === 'openai' ? (string)env('OPENAI_API_KEY') : ''),
                'openai_model' => $this->s->setting('openai_model') ?: 'gpt-4o-mini',
                'openai_base_url' => $this->s->setting('openai_base_url') ?: 'https://api.openai.com/v1',

                'gemini_api_key' => $this->s->setting('gemini_api_key') ?: (string)env('GEMINI_API_KEY'),
                'gemini_model' => (!empty($this->s->setting('gemini_model')) && !str_starts_with($this->s->setting('gemini_model'), 'gemini-1.') && !str_starts_with($this->s->setting('gemini_model'), 'gemini-2.')) ? $this->s->setting('gemini_model') : ((string)env('GEMINI_MODEL') ?: 'gemini-3.5-flash'),
                'gemini_base_url' => $this->s->setting('gemini_base_url') ?: (string)env('GEMINI_BASE_URL') ?: 'https://generativelanguage.googleapis.com/v1beta',

                'claude_api_key' => $this->s->setting('claude_api_key') ?: (string)(env('CLAUDE_API_KEY') ?: env('ANTHROPIC_API_KEY')),
                'claude_model' => $this->s->setting('claude_model') ?: 'claude-3-5-sonnet-20241022',
                'claude_base_url' => $this->s->setting('claude_base_url') ?: 'https://api.anthropic.com/v1',

                'openrouter_api_key' => $this->s->setting('openrouter_api_key') ?: (string)env('OPENROUTER_API_KEY'),
                'openrouter_model' => $this->s->setting('openrouter_model') ?: 'deepseek/deepseek-chat',
                'openrouter_base_url' => $this->s->setting('openrouter_base_url') ?: 'https://openrouter.ai/api/v1',

                'custom_api_key' => $this->s->setting('custom_api_key', 'ollama'),
                'custom_model' => $this->s->setting('custom_model', 'llama3.2'),
                'custom_base_url' => $this->s->setting('custom_base_url', 'http://localhost:11434/v1'),

                'web_widget_enabled' => $this->s->setting('web_widget_enabled', '1'),
                'web_widget_title' => $this->s->setting('web_widget_title', 'SkyNext Assistant'),
                'web_widget_welcome' => $this->s->setting('web_widget_welcome', 'สวัสดีครับ มีอะไรให้เราช่วยเหลือสอบถามได้เลยครับ'),
                'web_default_ai' => $this->s->setting('web_default_ai', '1'),

                'line_enabled' => $this->s->setting('line_enabled', '0'),
                'line_channel_id' => $this->s->setting('line_channel_id', ''),
                'line_channel_secret' => $this->s->setting('line_channel_secret') ?: (string)env('LINE_CHANNEL_SECRET'),
                'line_access_token' => $this->s->setting('line_access_token') ?: (string)env('LINE_ACCESS_TOKEN'),

                'facebook_enabled' => $this->s->setting('facebook_enabled', '0'),
                'facebook_page_token' => $this->s->setting('facebook_page_token') ?: (string)env('META_PAGE_TOKEN'),
                'facebook_app_secret' => $this->s->setting('facebook_app_secret') ?: (string)env('META_APP_SECRET'),
                'facebook_verify_token' => $this->s->setting('facebook_verify_token') ?: (string)env('META_VERIFY_TOKEN'),

                'telegram_enabled' => $this->s->setting('telegram_enabled', '0'),
                'telegram_bot_token' => $this->s->setting('telegram_bot_token') ?: (string)env('TELEGRAM_BOT_TOKEN'),
            ];
            $extra['webhook_urls'] = [
                'line' => (function_exists('site_url') ? site_url('hooks/line') : '/hooks/line'),
                'facebook' => (function_exists('site_url') ? site_url('hooks/facebook') : '/hooks/facebook'),
                'telegram' => (function_exists('site_url') ? site_url('hooks/telegram') : '/hooks/telegram'),
            ];
            $extra['knowledge'] = $extra['settings']['bot_knowledge'];
        }
        return ['rows'=>$rows,'record'=>$record,'fields'=>self::fields($module),'stats'=>$stats,'extra'=>$extra,'staff'=>$this->s->all("SELECT id,name FROM users WHERE active=1 AND role!='member'"),'query'=>$query,'currentPage'=>$page];
    }
    private function date(string $value): string
    {
        $d=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,new \DateTimeZone('Asia/Bangkok'));
        if (!$d || $d->format('Y-m-d\TH:i')!==$value) { throw new RuntimeException('วันที่ไม่ถูกต้อง'); }
        return $d->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
    private function integer(mixed $value, int $min, int $max): int
    { $n=filter_var($value,FILTER_VALIDATE_INT); if ($n===false || $n<$min || $n>$max) { throw new RuntimeException('จำนวนหรือสถานะไม่ถูกต้อง'); } return $n; }
    public function save(string $module,string $action,int $id,array $input,array $actor)
    {
        $p=[]; foreach ($input as $k=>$v) { if (is_string($v)) { $p[$k]=trim($v); } }
        if (!Access::can($actor,$module,$this->s)) { throw new RuntimeException('ไม่มีสิทธิ์'); }
        if ($module==='ai' && $action==='test') {
            $provider = strtolower(trim($p['test_provider'] ?? $p['ai_provider'] ?? 'deepseek'));
            $override = [
                'key' => $p[$provider.'_api_key'] ?? null,
                'model' => $p[$provider.'_model'] ?? null,
                'base_url' => $p[$provider.'_base_url'] ?? null,
                'temperature' => (float)($p['bot_temperature'] ?? 0.7),
                'max_tokens' => (int)($p['bot_max_tokens'] ?? 300),
            ];
            $testPrompt = 'คุณคือผู้ช่วย AI ร้านค้า ตอบสั้นๆ ไม่เกิน 1 ประโยค';
            $testMsg = $p['test_message'] ?? 'ทดสอบการเชื่อมต่อ API กรุณาตอบกลับสั้นๆ พร้อมชื่อโมเดล';
            $res = Bot::callAi($this->s, $provider, $testPrompt, $testMsg, $override);
            @file_put_contents(WRITEPATH . 'ai_test_debug.log', json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->s->audit($actor['id'], 'test_ai_connection', 'ai', 0, json_encode(['provider'=>$provider, 'success'=>$res['success']??false]));
            return $res;
        }
        if($module==='affiliates' && $action==='profile'){
            (new Affiliate($this->s))->saveProfile($id,$input,$actor['id']);return;
        }
        if($module==='affiliate_profile' && $action==='save'){
            $targetId=($actor['role']!=='admin')?(int)$actor['id']:($id?:(int)$actor['id']);
            (new Affiliate($this->s))->saveProfile($targetId,$input,$actor['id']);return;
        }
        if($module==='menus' && $action==='save'){
            if (!Access::isAdmin($actor)) { throw new RuntimeException('เฉพาะ Admin ที่สามารถจัดการเมนูได้'); }
            $rawMenus=$input['role_menus']??[];
            $saved=[];
            $allModules=array_keys(Access::MODULES);
            foreach (array_keys(Access::ROLES) as $r) {
                if(in_array($r,['downline_team','external_team'],true)){$saved[$r]=Access::TEAM_MENUS;continue;}
                $saved[$r]=[];
                if (isset($rawMenus[$r]) && is_array($rawMenus[$r])) {
                    foreach ($rawMenus[$r] as $m) {
                        if (in_array($m,$allModules,true)) {
                            $saved[$r][]=$m;
                        }
                    }
                }
            }
            $this->s->set('role_menus',json_encode($saved,JSON_UNESCAPED_UNICODE));
            $this->s->audit($actor['id'],'update_role_menus','menus',0,json_encode($saved));
            return;
        }
        if ($module==='blog') {
            if($action==='save'){(new Blog($this->s))->save($id,$input,$actor);return;}
            if($action==='archive'){(new Blog($this->s))->archive($id,$actor);return;}
            throw new RuntimeException('ไม่รองรับรายการนี้');
        }
        if ($module==='payments' && in_array($action,['approve','reject'])) { (new Commerce($this->s))->reviewPayment($id,$actor['id'],$action==='approve',mb_substr($p['note']??'',0,1000)); return; }
        if ($module==='orders' && $action==='status') { (new Commerce($this->s))->transition($id,$actor['id'],$p['status']??'',mb_substr($p['note']??'',0,1000)); return; }
        if ($module==='orders' && $action==='reconcile') { (new Gateways($this->s))->reconcile($id,$actor['id'],$p['reference']??''); return; }
        if ($module==='conversations' && $action==='reply') {
            $body=mb_substr($p['body']??'',0,2000);
            if (!$body) { throw new RuntimeException('กรอกข้อความตอบกลับ'); }
            $this->s->transaction(function () use($id,$body,$actor) {
                if (!$this->s->one('SELECT id FROM conversations WHERE id=?',[$id])) { throw new RuntimeException('ไม่พบการสนทนา'); }
                $this->s->exec("UPDATE conversations SET mode='human',updated_at=? WHERE id=?",[Store::now(),$id]);
                (new Bot($this->s))->message($id,'staff',$body);
                $this->s->audit($actor['id'],'reply','conversation',$id);
            }); return;
        }
        if ($module==='conversations' && $action==='mode') {
            if (!in_array($p['mode']??'',['auto','human'])) { throw new RuntimeException('โหมดไม่ถูกต้อง'); }
            $this->s->exec('UPDATE conversations SET mode=?,updated_at=? WHERE id=?',[$p['mode'],Store::now(),$id]);
            $this->s->audit($actor['id'],'mode_changed','conversation',$id,$p['mode']); return;
        }
        if (in_array($module,['settings','ai','integrations']) && $action==='save') {
            $values=match($module) {
                'settings'=>['store_name'=>mb_substr($p['store_name']??'',0,120),'shipping_satang'=>(string)Store::amount($p['shipping']??'0'),'bank_details'=>mb_substr($p['bank_details']??'',0,2000),'analytics_enabled'=>(string)$this->integer($p['analytics_enabled']??0,0,1)],
                'ai'=>[
                    'bot_enabled'=>(string)$this->integer($p['bot_enabled']??0,0,1),
                    'ai_enabled'=>(string)$this->integer($p['ai_enabled']??0,0,1),
                    'bot_mode'=>in_array($p['bot_mode']??'',['hybrid','ai_only','rules_only'])?$p['bot_mode']:'hybrid',
                    'bot_welcome_msg'=>mb_substr($p['bot_welcome_msg']??'',0,500),
                    'bot_fallback_msg'=>mb_substr($p['bot_fallback_msg']??'',0,500),
                    'bot_handoff_msg'=>mb_substr($p['bot_handoff_msg']??'',0,500),
                    'bot_handoff_keywords'=>mb_substr($p['bot_handoff_keywords']??'',0,500),
                    'bot_system_prompt'=>mb_substr($p['bot_system_prompt']??'',0,4000),
                    'bot_include_catalog'=>(string)$this->integer($p['bot_include_catalog']??0,0,1),
                    'bot_knowledge'=>mb_substr($p['bot_knowledge']??'',0,32000),
                    'bot_temperature'=>(string)max(0.0, min(1.0, (float)($p['bot_temperature']??0.7))),
                    'bot_max_tokens'=>(string)max(50, min(4000, (int)($p['bot_max_tokens']??800))),
                    'bot_rate_limit'=>(string)max(10, min(10000, (int)($p['bot_rate_limit']??200))),
                    'bot_lead_capture'=>(string)$this->integer($p['bot_lead_capture']??0,0,1),

                    'ai_provider'=>in_array($p['ai_provider']??'',['deepseek','openai','gemini','claude','openrouter','custom'])?$p['ai_provider']:'deepseek',

                    'deepseek_api_key'=>mb_substr($p['deepseek_api_key']??'',0,255),
                    'deepseek_model'=>mb_substr($p['deepseek_model']??'',0,100),
                    'deepseek_base_url'=>mb_substr($p['deepseek_base_url']??'',0,255),

                    'openai_api_key'=>mb_substr($p['openai_api_key']??'',0,255),
                    'openai_model'=>mb_substr($p['openai_model']??'',0,100),
                    'openai_base_url'=>mb_substr($p['openai_base_url']??'',0,255),

                    'gemini_api_key'=>mb_substr($p['gemini_api_key']??'',0,255),
                    'gemini_model'=>mb_substr($p['gemini_model']??'',0,100),
                    'gemini_base_url'=>mb_substr($p['gemini_base_url']??'',0,255),

                    'claude_api_key'=>mb_substr($p['claude_api_key']??'',0,255),
                    'claude_model'=>mb_substr($p['claude_model']??'',0,100),
                    'claude_base_url'=>mb_substr($p['claude_base_url']??'',0,255),

                    'openrouter_api_key'=>mb_substr($p['openrouter_api_key']??'',0,255),
                    'openrouter_model'=>mb_substr($p['openrouter_model']??'',0,100),
                    'openrouter_base_url'=>mb_substr($p['openrouter_base_url']??'',0,255),

                    'custom_api_key'=>mb_substr($p['custom_api_key']??'',0,255),
                    'custom_model'=>mb_substr($p['custom_model']??'',0,100),
                    'custom_base_url'=>mb_substr($p['custom_base_url']??'',0,255),

                    'web_widget_enabled'=>(string)$this->integer($p['web_widget_enabled']??0,0,1),
                    'web_widget_title'=>mb_substr($p['web_widget_title']??'',0,100),
                    'web_widget_welcome'=>mb_substr($p['web_widget_welcome']??'',0,500),
                    'web_default_ai'=>(string)$this->integer($p['web_default_ai']??0,0,1),

                    'line_enabled'=>(string)$this->integer($p['line_enabled']??0,0,1),
                    'line_channel_id'=>mb_substr($p['line_channel_id']??'',0,100),
                    'line_channel_secret'=>mb_substr($p['line_channel_secret']??'',0,255),
                    'line_access_token'=>mb_substr($p['line_access_token']??'',0,500),

                    'facebook_enabled'=>(string)$this->integer($p['facebook_enabled']??0,0,1),
                    'facebook_page_token'=>mb_substr($p['facebook_page_token']??'',0,500),
                    'facebook_app_secret'=>mb_substr($p['facebook_app_secret']??'',0,255),
                    'facebook_verify_token'=>mb_substr($p['facebook_verify_token']??'',0,255),

                    'telegram_enabled'=>(string)$this->integer($p['telegram_enabled']??0,0,1),
                    'telegram_bot_token'=>mb_substr($p['telegram_bot_token']??'',0,255),
                ],
                'integrations'=>['line_enabled'=>(string)$this->integer($p['line_enabled']??0,0,1),'facebook_enabled'=>(string)$this->integer($p['facebook_enabled']??0,0,1)],
            };
            $this->s->transaction(function () use($values,$actor,$module) { foreach($values as $k=>$v){$this->s->set($k,$v);} $this->s->audit($actor['id'],'settings_updated',$module); }); return ['saved'=>true];
        }
        if ($module==='analytics' && $action==='purge') {
            $days=$this->integer($p['days']??90,1,730);
            $this->s->exec('DELETE FROM visits WHERE created_at<?',[gmdate('Y-m-d H:i:s',time()-$days*86400)]);
            $this->s->exec('DELETE FROM rate_limits WHERE expires<?',[time()]);
            $this->s->audit($actor['id'],'retention_purge','analytics',0,(string)$days); return;
        }
        if ($action!=='save' || !self::fields($module)) { throw new RuntimeException('ไม่รองรับรายการนี้'); }
        $data=[];
        foreach(self::fields($module) as $key=>$definition) {
            $value=$p[$key]??'';
            if($module==='products' && !array_key_exists($key,$p) && in_array($key,['short_description','inquiry_only','inquiry_label','member_price','discount_kind','discount_value'],true)){
                $previous=$id?$this->s->one('SELECT short_description,inquiry_only,inquiry_label,member_price_satang,discount_kind,discount_value FROM products WHERE id=?',[$id]):null;
                $value=match($key){
                    'member_price'=>isset($previous['member_price_satang'])?number_format($previous['member_price_satang']/100,2,'.',''):'',
                    'discount_value'=>($previous['discount_kind']??'')!==''?number_format($previous['discount_value']/100,2,'.',''):'',
                    default=>$previous[$key]??($key==='inquiry_only'?'0':''),
                };
                $value=(string)$value;
            }
            if (mb_strlen($value)>16000) { throw new RuntimeException('ข้อความยาวเกินไป'); }
            if ($definition[1]==='select' && !array_key_exists($value,$definition[2])) { throw new RuntimeException('ตัวเลือกไม่ถูกต้อง'); }
            if (in_array($definition[1],['text','email']) && mb_strlen($value)>200) { throw new RuntimeException('ข้อความยาวเกินไป'); }
            if ($definition[1]==='staff') {
                if ($value!=='' && !$this->s->one("SELECT id FROM users WHERE id=? AND role!='member' AND active=1",[(int)$value])) { throw new RuntimeException('ไม่พบผู้รับผิดชอบ'); }
                $value=$value===''?null:(int)$value;
            }
            $data[$key]=$value;
        }
        if ($module==='products') {
            if (!$data['name'] || !$data['sku']) { throw new RuntimeException('ระบุชื่อสินค้าและ SKU'); }
            $data['price_satang']=Store::amount($data['price']); unset($data['price']);
            $data['member_price_satang']=$data['member_price']===''?null:Store::amount($data['member_price']);unset($data['member_price']);
            $discountValue=$data['discount_value'];
            if($data['discount_kind']===''){
                if($discountValue!=='' && Store::amount($discountValue)>0){throw new RuntimeException('เลือกประเภทส่วนลดก่อนระบุส่วนลด');}
                $data['discount_value']=0;
            }else{
                if($discountValue===''){throw new RuntimeException('ระบุจำนวนส่วนลดสินค้า');}
                $data['discount_value']=Store::amount($discountValue);
                if($data['discount_kind']==='percent' && $data['discount_value']>10000){throw new RuntimeException('ส่วนลดเปอร์เซ็นต์ต้องไม่เกิน 100%');}
                if($data['discount_kind']==='fixed' && $data['discount_value']>$data['price_satang']){throw new RuntimeException('ส่วนลดต้องไม่เกินราคาปกติ');}
                if($data['sku']===AnnualWebsitePackage::SKU && ($data['discount_kind']==='percent' && $data['discount_value']>=10000 || $data['discount_kind']==='fixed' && $data['discount_value']>=$data['price_satang'])){
                    throw new RuntimeException('ส่วนลดแพ็กเกจรายปีต้องเหลือค่าบริการมากกว่า 0 บาท');
                }
            }
            $data['stock']=$this->integer($data['stock'],0,10000000);
            $data['pv']=$this->integer($data['pv']===''?0:$data['pv'],0,1000000);
            if ($data['image']!=='' && (!preg_match('~^(?:mirror/assets/[a-f0-9]{24}|platform/uploads/[a-f0-9]{32}|platform/assets/[a-zA-Z0-9_-]+)\.(?:png|jpg|jpeg|webp|gif)$~D',$data['image']) || !is_file(FCPATH.$data['image']))) { throw new RuntimeException('ใช้ภาพที่อัปโหลดหรือไฟล์ภาพภายในระบบเท่านั้น'); }
        }
        if ($module==='promotions') {
            if (!preg_match('/^[A-Z0-9_-]{3,32}$/D',$data['code'])) { throw new RuntimeException('ใช้รหัส A-Z, 0-9, _ หรือ - ความยาว 3–32 ตัว'); }
            $data['value']=Store::amount($data['value']);
            if ($data['value']<=0 || ($data['kind']==='percent' && $data['value']>10000)) { throw new RuntimeException('ส่วนลดต้องมากกว่า 0 และเปอร์เซ็นต์ไม่เกิน 100'); }
            $data['minimum_satang']=Store::amount($data['minimum']); unset($data['minimum']);
            $data['starts_at']=$this->date($data['starts_at']); $data['ends_at']=$this->date($data['ends_at']);
            if ($data['ends_at']<=$data['starts_at']) { throw new RuntimeException('วันสิ้นสุดต้องหลังวันเริ่ม'); }
            $data['usage_limit']=$this->integer($data['usage_limit'],1,10000000);
        }
        if ($module==='users') {
            if (mb_strlen($data['name'])<2 || !filter_var($data['email'],FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('ชื่อหรืออีเมลไม่ถูกต้อง'); }
            $password=(string)($input['password']??''); unset($data['password']);
            if ($password!=='' || !$id) {
                if (strlen($password)<12 || strlen($password)>200) { throw new RuntimeException('รหัสผ่านอย่างน้อย 12 ตัวอักษร'); }
                $data['password_hash']=password_hash($password,PASSWORD_DEFAULT);
            }
            $reqCode=trim((string)($input['referral_code']??''));
            if ($reqCode!=='') {
                $data['referral_code']=Affiliate::validateCustomCode($reqCode,$id?:null,$this->s);
            } elseif (!$id) {
                $data['referral_code']=Affiliate::nextCode($this->s);
            } else {
                unset($data['referral_code']);
            }
        }
        if ($module==='leads') {
            if (!$data['name'] || strlen($data['contact'])<3 || !$data['source']) { throw new RuntimeException('กรอกชื่อ ช่องทางติดต่อ และแหล่งที่มา'); }
            $data['score']=$this->integer($data['score'],0,100); $data['updated_at']=Store::now();
        }
        if ($module==='tasks') {
            if (!$data['title']) { throw new RuntimeException('กรอกชื่องาน'); }
            $data['lead_id']=$data['lead_id']===''?null:$this->integer($data['lead_id'],1,PHP_INT_MAX);
            if ($data['lead_id'] && !$this->s->one('SELECT id FROM leads WHERE id=?',[$data['lead_id']])) { throw new RuntimeException('ไม่พบ Lead'); }
            $data['due_at']=$this->date($data['due_at']);
        }
        $this->s->transaction(function () use($module,$data,$id,$actor) {
            if ($id) {
                $old=$this->s->one('SELECT * FROM '.$module.' WHERE id=?',[$id]);
                if (!$old) { throw new RuntimeException('ไม่พบข้อมูล'); }
                if($module==='users' && isset($data['password_hash'])){$data['auth_version']=(int)$old['auth_version']+1;}
                if ($module==='users' && $old['role']==='admin' && ($data['role']!=='admin' || !$data['active'])) {
                    if ($id===$actor['id'] || $this->s->one("SELECT COUNT(*) n FROM users WHERE role='admin' AND active=1")['n']<=1) { throw new RuntimeException('ไม่สามารถปิดสิทธิ์ผู้ดูแลที่กำลังใช้งานหรือผู้ดูแลคนสุดท้าย'); }
                }
                if ($module==='promotions' && $data['usage_limit']<$old['used']) { throw new RuntimeException('จำนวนสิทธิ์ต้องไม่น้อยกว่าที่ใช้ไปแล้ว'); }
                $this->s->exec('UPDATE '.$module.' SET '.implode(',',array_map(fn($k)=>$k.'=?',array_keys($data))).' WHERE id=?',[...array_values($data),$id]);
                $entity=$id;
            } else {
                if ($module!=='promotions') { $data['created_at']=Store::now(); }
                $entity=$this->s->insert($module,$data);
            }
            if ($module==='leads' && $data['notes']!=='') { $this->s->insert('activities',['lead_id'=>$entity,'actor_id'=>$actor['id'],'note'=>$data['notes'],'created_at'=>Store::now()]); }
            $this->s->audit($actor['id'],$id?'updated':'created',$module,$entity);
        });
    }
}
