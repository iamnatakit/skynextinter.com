<?php
namespace App\Libraries\Platform;

use RuntimeException;
use Throwable;

class Bot
{
    public function __construct(public Store $s) {}
    public function conversation(string $platform,string $external): array
    {
        $this->s->exec('INSERT OR IGNORE INTO conversations(platform,external_id,created_at,updated_at) VALUES(?,?,?,?)',[$platform,$external,Store::now(),Store::now()]);
        return $this->s->one('SELECT * FROM conversations WHERE platform=? AND external_id=?',[$platform,$external]);
    }
    public function message(int $conversation,string $direction,string $body): int
    {
        $id=$this->s->insert('messages',['conversation_id'=>$conversation,'direction'=>$direction,'body'=>mb_substr($body,0,4000),'created_at'=>Store::now()]);
        $c=$this->s->one('SELECT platform FROM conversations WHERE id=?',[$conversation]);
        $this->s->exec('UPDATE conversations SET updated_at=? WHERE id=?',[Store::now(),$conversation]);
        if($direction!=='in' && $c['platform']!=='web'){$this->s->insert('outbox',['conversation_id'=>$conversation,'message_id'=>$id,'created_at'=>Store::now()]);}
        return $id;
    }
    public function captureLead(int $conversation,string $name,string $contact,bool $consent): int
    {
        if(!$consent || mb_strlen($name)<2 || mb_strlen($name)>120 || strlen($contact)>200 || (!filter_var($contact,FILTER_VALIDATE_EMAIL)&&!preg_match('/^[+0-9 ()-]{8,30}$/D',$contact))){throw new RuntimeException('กรอกชื่อ เบอร์โทรหรืออีเมล และยินยอมให้ติดต่อกลับ');}
        return $this->s->transaction(function()use($conversation,$name,$contact){
            $c=$this->s->one('SELECT * FROM conversations WHERE id=?',[$conversation]);
            if(!$c){throw new RuntimeException('ไม่พบการสนทนา');}
            $l=$this->s->one('SELECT id FROM leads WHERE contact=? ORDER BY id LIMIT 1',[$contact]);
            $id=$l['id']??$this->s->insert('leads',['name'=>$name,'contact'=>$contact,'source'=>$c['platform'].':chat','score'=>20,'consent_at'=>Store::now(),'created_at'=>Store::now(),'updated_at'=>Store::now()]);
            $this->s->exec('UPDATE conversations SET lead_id=? WHERE id=?',[$id,$conversation]);
            $this->s->insert('activities',['lead_id'=>$id,'note'=>'ลูกค้ายินยอมให้ติดต่อกลับผ่าน '.$c['platform'],'created_at'=>Store::now()]);
            return (int)$id;
        });
    }
    public static function callAi(Store $store, string $provider, string $systemPrompt, string $userMessage, ?array $override = null): array
    {
        $start = hrtime(true);
        $provider = strtolower(trim($provider ?: 'deepseek'));
        
        $key = $override['key'] ?? null;
        $model = $override['model'] ?? null;
        $baseUrl = $override['base_url'] ?? null;
        $temperature = (float)($override['temperature'] ?? $store->setting('bot_temperature', '0.7'));
        $maxTokens = (int)($override['max_tokens'] ?? $store->setting('bot_max_tokens', '800'));

        if ($key === null || $key === '') {
            $key = match($provider) {
                'deepseek'   => $store->setting('deepseek_api_key') ?: (string)env('DEEPSEEK_API_KEY'),
                'openai'     => $store->setting('openai_api_key') ?: (env('AI_PROVIDER') === 'openai' ? (string)env('OPENAI_API_KEY') : ''),
                'gemini'     => $store->setting('gemini_api_key') ?: (string)env('GEMINI_API_KEY'),
                'claude'     => $store->setting('claude_api_key') ?: (string)(env('CLAUDE_API_KEY') ?: env('ANTHROPIC_API_KEY')),
                'openrouter' => $store->setting('openrouter_api_key') ?: (string)env('OPENROUTER_API_KEY'),
                'custom'     => $store->setting('custom_api_key') ?: 'ollama',
                default      => '',
            };
        }

        if ($model === null || $model === '') {
            $model = match($provider) {
                'deepseek'   => $store->setting('deepseek_model') ?: (string)env('DEEPSEEK_MODEL') ?: 'deepseek-chat',
                'openai'     => $store->setting('openai_model') ?: 'gpt-4o-mini',
                'gemini'     => $store->setting('gemini_model') ?: (string)env('GEMINI_MODEL') ?: 'gemini-3.8-flash',
                'claude'     => $store->setting('claude_model') ?: 'claude-3-5-sonnet-20241022',
                'openrouter' => $store->setting('openrouter_model') ?: 'deepseek/deepseek-chat',
                'custom'     => $store->setting('custom_model') ?: 'llama3.2',
                default      => 'deepseek-chat',
            };
        }

        if ($baseUrl === null || $baseUrl === '') {
            $baseUrl = match($provider) {
                'deepseek'   => $store->setting('deepseek_base_url') ?: (string)env('DEEPSEEK_BASE_URL') ?: 'https://api.deepseek.com',
                'openai'     => $store->setting('openai_base_url') ?: 'https://api.openai.com/v1',
                'gemini'     => $store->setting('gemini_base_url') ?: (string)env('GEMINI_BASE_URL') ?: 'https://generativelanguage.googleapis.com/v1beta',
                'claude'     => $store->setting('claude_base_url') ?: 'https://api.anthropic.com/v1',
                'openrouter' => $store->setting('openrouter_base_url') ?: 'https://openrouter.ai/api/v1',
                'custom'     => $store->setting('custom_base_url') ?: 'http://localhost:11434/v1',
                default      => 'https://api.deepseek.com',
            };
        }

        if ($provider !== 'custom' && empty($key)) {
            return [
                'success' => false,
                'provider' => $provider,
                'model' => $model,
                'error' => 'ยังไม่ได้ระบุ API Key สำหรับผู้ให้บริการ ' . strtoupper($provider),
                'latency_ms' => 0
            ];
        }

        try {
            $answer = '';
            if ($provider === 'claude') {
                $endpoint = rtrim($baseUrl, '/') . '/messages';
                $headers = [
                    'x-api-key: ' . $key,
                    'anthropic-version: 2023-06-01',
                    'content-type: application/json'
                ];
                $payload = [
                    'model' => $model,
                    'system' => $systemPrompt,
                    'messages' => [
                        ['role' => 'user', 'content' => mb_substr($userMessage, 0, 2000)]
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => $temperature
                ];
                $res = Http::request($endpoint, 'POST', $headers, json_encode($payload, JSON_UNESCAPED_UNICODE));
                $answer = $res['content'][0]['text'] ?? '';
            } elseif ($provider === 'gemini') {
                // Check if user explicitly configured an OpenAI-compatible proxy (e.g. LiteLLM) and not Google directly
                if (str_contains($baseUrl, '/openai') && !str_contains($baseUrl, 'generativelanguage.googleapis.com')) {
                    $endpoint = rtrim($baseUrl, '/') . (str_ends_with($baseUrl, '/chat/completions') ? '' : '/chat/completions');
                    $headers = [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $key,
                        'x-goog-api-key: ' . $key
                    ];
                    $payload = [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => mb_substr($userMessage, 0, 2000)]
                        ],
                        'temperature' => $temperature,
                        'max_tokens' => $maxTokens,
                        'stream' => false
                    ];
                    $res = Http::request($endpoint, 'POST', $headers, json_encode($payload, JSON_UNESCAPED_UNICODE));
                    $answer = $res['choices'][0]['message']['content'] ?? '';
                } else {
                    // Official Google AI Studio Native REST API
                    $cleanModel = preg_replace('#^models/#', '', trim($model));
                    if ($cleanModel === '' || str_starts_with($cleanModel, 'gemini-1.') || str_starts_with($cleanModel, 'gemini-2.')) {
                        $cleanModel = 'gemini-3.5-flash';
                    }
                    $cleanBase = rtrim(preg_replace('#/(openai|models|chat/completions|interactions).*$#i', '', $baseUrl), '/');
                    if (!str_contains($cleanBase, '/v1beta') && !str_contains($cleanBase, '/v1')) {
                        $cleanBase .= '/v1beta';
                    }
                    $headers = [
                        'Content-Type: application/json',
                        'x-goog-api-key: ' . $key
                    ];

                    $payload = [
                        'system_instruction' => [
                            'parts' => [['text' => $systemPrompt]]
                        ],
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [['text' => mb_substr($userMessage, 0, 3000)]]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => $temperature,
                            'maxOutputTokens' => $maxTokens,
                        ]
                    ];

                    // Primary model and verified working fallbacks
                    $attemptModels = [$cleanModel];
                    foreach (['gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-3.8-flash'] as $cModel) {
                        if (!in_array($cModel, $attemptModels, true)) {
                            $attemptModels[] = $cModel;
                        }
                    }

                    $lastEx = null;
                    $res = null;

                    foreach ($attemptModels as $attemptModel) {
                        $endpoint = $cleanBase . '/models/' . $attemptModel . ':generateContent?key=' . urlencode($key);
                        try {
                            $res = Http::request($endpoint, 'POST', $headers, json_encode($payload, JSON_UNESCAPED_UNICODE));
                            if ($res !== null) {
                                $model = $attemptModel;
                                break;
                            }
                        } catch (RuntimeException $e) {
                            $lastEx = $e;
                            // Check if model was deprecated (404 / no longer available) or 503 (high demand)
                            if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), '503') || str_contains($e->getMessage(), 'no longer available') || str_contains($e->getMessage(), 'not found')) {
                                continue;
                            }
                            throw $e;
                        }
                    }
                    if ($res === null && $lastEx !== null) {
                        throw $lastEx;
                    }
                    if (!empty($res['promptFeedback']['blockReason'])) {
                        throw new RuntimeException('Google Gemini ปฏิเสธการตอบกลับเนื่องจาก: ' . $res['promptFeedback']['blockReason']);
                    }
                    $answer = $res['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if ($answer === '' && isset($res['candidates'][0]['finishReason']) && $res['candidates'][0]['finishReason'] !== 'STOP') {
                        throw new RuntimeException('Gemini finishReason: ' . $res['candidates'][0]['finishReason']);
                    }
                }
            } else {
                $endpoint = rtrim($baseUrl, '/') . (str_ends_with($baseUrl, '/chat/completions') ? '' : '/chat/completions');
                $headers = ['Content-Type: application/json'];
                if ($key !== '') {
                    $headers[] = 'Authorization: Bearer ' . $key;
                }
                if ($provider === 'openrouter') {
                    $headers[] = 'HTTP-Referer: ' . (function_exists('site_url') ? site_url() : 'http://localhost');
                    $headers[] = 'X-Title: SkyNext Platform';
                }
                $payload = [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => mb_substr($userMessage, 0, 2000)]
                    ],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                    'stream' => false
                ];
                $res = Http::request($endpoint, 'POST', $headers, json_encode($payload, JSON_UNESCAPED_UNICODE));
                $answer = $res['choices'][0]['message']['content'] ?? '';
            }

            $elapsedMs = (int)round((hrtime(true) - $start) / 1e6);
            $answer = trim((string)$answer);
            if ($answer === '') {
                throw new RuntimeException('ผู้ให้บริการตอบกลับแต่ไม่มีเนื้อหาข้อความ');
            }
            return [
                'success' => true,
                'provider' => $provider,
                'model' => $model,
                'answer' => $answer,
                'latency_ms' => $elapsedMs
            ];
        } catch (Throwable $e) {
            $elapsedMs = (int)round((hrtime(true) - $start) / 1e6);
            return [
                'success' => false,
                'provider' => $provider,
                'model' => $model,
                'error' => $e->getMessage(),
                'latency_ms' => $elapsedMs
            ];
        }
    }

    public function reply(array $c,string $text): ?string
    {
        $handoffKeywords = $this->s->setting('bot_handoff_keywords') ?: 'แอดมิน|เจ้าหน้าที่|human|agent';
        if(preg_match('/' . $handoffKeywords . '/iu', $text)){
            $this->s->exec("UPDATE conversations SET mode='human' WHERE id=?",[$c['id']]);
            return $this->s->setting('bot_handoff_msg') ?: 'ส่งต่อให้เจ้าหน้าที่แล้วค่ะ กรุณาฝากชื่อและช่องทางติดต่อ ทีมงานจะตอบกลับในกล่องข้อความนี้';
        }
        if(trim($text)==='ยินยอม AI'){$this->s->exec('UPDATE conversations SET ai_consent=1 WHERE id=?',[$c['id']]); return 'รับทราบค่ะ เปิดการส่งข้อความให้ AI ช่วยตอบแล้วค่ะ พิมพ์ หยุด AI เพื่อยกเลิก';}
        if(trim($text)==='หยุด AI'){$this->s->exec('UPDATE conversations SET ai_consent=0 WHERE id=?',[$c['id']]); return 'ปิดการส่งข้อความให้ AI แล้วค่ะ';}
        if($this->s->setting('bot_lead_capture', '1') === '1' && preg_match('/^lead:\s*([^|]+)\|\s*([^|]+)\|\s*ยินยอม\s*$/iu',$text,$m)){
            $this->captureLead($c['id'],trim($m[1]),trim($m[2]),true); return 'รับข้อมูลแล้วค่ะ ทีมงานจะติดต่อกลับตามข้อมูลที่ให้ไว้';
        }
        if($c['mode']==='human' || $this->s->setting('bot_enabled', '1')!=='1'){return null;}
        if(function_exists('helper')){ @helper('url'); }
        $urlOf = fn(string $path) => function_exists('site_url') ? site_url($path) : '/' . ltrim($path, '/');

        $term=mb_substr(trim($text),0,100);
        if(preg_match('/\d{1,2}-\d{1,2}-\d{1,2}/',$term,$formula)){$term=$formula[0];}
        $products=$this->s->all('SELECT * FROM products WHERE active=1 AND name LIKE ? LIMIT 5',['%'.$term.'%']);
        $catalog=implode("\n",array_map(function($p)use($urlOf){$priced=ProductPricing::apply($p,false);return $p['name'].' '.number_format($priced['price_satang']/100,2).' บาท'.($priced['discount_satang']>0?' (ลด '.ProductPricing::discountLabel($p).')':'').' | สต็อก '.$p['stock'].' | '.$urlOf('shop/product/'.$p['id']);},$products));
        if($c['lead_id'] && preg_match('/ราคา|สั่งซื้อ|ใบเสนอราคา/u',$text)){$this->s->exec("UPDATE leads SET score=MIN(100,score+10),updated_at=? WHERE id=?",[Store::now(),$c['lead_id']]);}

        $botMode = $this->s->setting('bot_mode', 'hybrid');
        $aiEnabled = $this->s->setting('ai_enabled', '1') === '1';
        $activeProvider = $this->s->setting('ai_provider') ?: env('AI_PROVIDER') ?: 'deepseek';

        if(($c['ai_consent'] || $this->s->setting('web_default_ai', '1') === '1') && $aiEnabled && $botMode !== 'rules_only'){
            try{
                $rateLimit = (int)($this->s->setting('bot_rate_limit') ?: 200);
                $this->s->rate('ai-global', $rateLimit, 86400);

                $systemPrompt = $this->s->setting('bot_system_prompt') ?: 'คุณคือ AI ผู้ช่วยร้านค้าอัจฉริยะของ SkyNext (Project Field) ตอบคำถามลูกค้าเป็นภาษาไทยอย่างสุภาพ กระชับ เป็นมิตร และให้ข้อมูลที่ถูกต้องเป็นประโยชน์ตามข้อมูลร้านค้าและรายการสินค้าที่ให้ไว้เท่านั้น';
                $instructions = $systemPrompt . "\n\nข้อมูลร้านค้า:\n" . $this->s->setting('bot_knowledge');

                if($this->s->setting('bot_include_catalog', '1') === '1'){
                    $sampleProducts = $this->s->all('SELECT * FROM products WHERE active=1 LIMIT 12');
                    $sampleCatalog = implode("\n", array_map(function($p)use($urlOf){$priced=ProductPricing::apply($p,false);return '- '.$p['name'].' ราคา '.number_format($priced['price_satang']/100,2).' บาท'.($priced['discount_satang']>0?' (ลด '.ProductPricing::discountLabel($p).')':'').' (สต็อก '.$p['stock'].' ชิ้น) ดูสินค้า: '.$urlOf('shop/product/'.$p['id']);},$sampleProducts));
                    $instructions .= "\n\nรายการสินค้าของร้าน:\n" . $sampleCatalog;
                }
                if($catalog){
                    $instructions .= "\n\nสินค้าที่ตรงคำค้นหาของลูกค้า:\n" . $catalog;
                }

                $instructions .= "\n\nแนวทางการตอบ:\n1. แนะนำสินค้า ระบุชื่อ ราคา และลิงก์สินค้าได้อย่างชัดเจนจากรายการสินค้าที่ให้ไว้\n2. ห้ามกุราคาหรือสต็อกสินค้าขึ้นมาเองโดยไม่มีข้อมูล\n3. ไม่ยืนยันการชำระเงินหรือสถานะจัดส่งด้วยตนเอง แนะนำให้ลูกค้าส่งสลิปผ่านระบบ หรือตรวจสอบในหน้าคำสั่งซื้อ\n4. หากลูกค้าถามสิ่งที่นอกเหนือจากสินค้าของร้าน หรือต้องการติดต่อทีมงาน ให้แนะนำพิมพ์คำว่า 'แอดมิน' หรือใช้แบบฟอร์มฝากข้อมูลติดต่อกลับ\n5. ตอบสั้นกระชับเข้าใจง่าย ไม่เยิ่นเย้อ";

                $callResult = self::callAi($this->s, $activeProvider, $instructions, $text);
                if(!empty($callResult['success']) && !empty($callResult['answer'])){
                    return mb_substr($callResult['answer'], 0, 2000);
                }
                if(!empty($callResult['error'])){
                    $this->s->audit(null, 'ai_unavailable', 'conversation', $c['id'], mb_substr($callResult['error'], 0, 200));
                }
            }catch(Throwable $e){
                $this->s->audit(null, 'ai_unavailable', 'conversation', $c['id'], mb_substr($e->getMessage(), 0, 200));
            }
        }

        if($catalog){return "สินค้าที่พบ:\n".$catalog;}
        $defaultFallback = $this->s->setting('bot_knowledge')."\nค้นหาสินค้า: ".$urlOf('shop')."\nต้องการให้ติดต่อกลับ ใช้แบบฟอร์มฝากข้อมูล หรือพิมพ์ lead: ชื่อ | เบอร์โทรหรืออีเมล | ยินยอม\nพิมพ์ แอดมิน เพื่อคุยกับเจ้าหน้าที่";
        return $this->s->setting('bot_fallback_msg') ?: $defaultFallback;
    }
    public static function signature(string $provider,string $body,string $signature,string $secret): bool
    {
        if(!$secret || !$signature){return false;}
        $expected=$provider==='line'?base64_encode(hash_hmac('sha256',$body,$secret,true)):'sha256='.hash_hmac('sha256',$body,$secret);
        return hash_equals($expected,$signature);
    }
    public function inbound(string $platform,string $event,string $external,string $text): void
    {
        $this->s->transaction(function()use($platform,$event,$external,$text){
            if(!$this->s->exec('INSERT OR IGNORE INTO webhook_events(event_key,created_at) VALUES(?,?)',[$platform.':'.$event,Store::now()])){return;}
            $c=$this->conversation($platform,$external); $this->message($c['id'],'in',$text);
            $this->s->insert('inbox_jobs',['conversation_id'=>$c['id'],'body'=>mb_substr($text,0,2000),'created_at'=>Store::now()]);
        });
    }
    public function work(): array
    {
        $result=['replies'=>0,'sent'=>0,'failed'=>0];
        foreach($this->s->all("SELECT * FROM inbox_jobs WHERE status='queued' ORDER BY id LIMIT 20") as $job){
            if(!$this->s->exec("UPDATE inbox_jobs SET status='processing' WHERE id=? AND status='queued'",[$job['id']])){continue;}
            try{
                $c=$this->s->one('SELECT * FROM conversations WHERE id=?',[$job['conversation_id']]); $reply=$this->reply($c,$job['body']);
                $this->s->transaction(function()use($c,$reply,$job){
                    $current=$this->s->one('SELECT mode FROM conversations WHERE id=?',[$c['id']]);
                    if($reply!==null && ($current['mode']==='auto' || preg_match('/แอดมิน|เจ้าหน้าที่|human|agent/iu',$job['body']))){$this->message($c['id'],'bot',$reply);}
                    $this->s->exec("UPDATE inbox_jobs SET status='done' WHERE id=?",[$job['id']]);
                }); $result['replies']++;
            }catch(Throwable $e){$this->s->exec("UPDATE inbox_jobs SET status='failed' WHERE id=?",[$job['id']]);$result['failed']++;}
        }
        foreach($this->s->all("SELECT o.*,m.body,c.platform,c.external_id FROM outbox o JOIN messages m ON m.id=o.message_id JOIN conversations c ON c.id=o.conversation_id WHERE o.status='queued' ORDER BY o.id LIMIT 20") as $out){
            $enabled=$this->s->setting($out['platform'].'_enabled')==='1';
            $token=match($out['platform']){
                'line'     => $this->s->setting('line_access_token') ?: env('LINE_ACCESS_TOKEN'),
                'facebook' => $this->s->setting('meta_page_token') ?: env('META_PAGE_TOKEN'),
                'telegram' => $this->s->setting('telegram_bot_token') ?: env('TELEGRAM_BOT_TOKEN'),
                default    => ''
            };
            if(!$enabled||!$token){continue;}
            if(!$this->s->exec("UPDATE outbox SET status='sending',attempts=attempts+1 WHERE id=? AND status='queued'",[$out['id']])){continue;}
            try{
                if($out['platform']==='line'){
                    Http::request('https://api.line.me/v2/bot/message/push','POST',['Authorization: Bearer '.$token,'Content-Type: application/json','X-Line-Retry-Key: '.self::uuid($out['id'])],json_encode(['to'=>$out['external_id'],'messages'=>[['type'=>'text','text'=>$out['body']]]],JSON_UNESCAPED_UNICODE));
                }elseif($out['platform']==='facebook'){
                    $last=$this->s->one("SELECT created_at FROM messages WHERE conversation_id=? AND direction='in' ORDER BY id DESC LIMIT 1",[$out['conversation_id']]);
                    if(!$last || strtotime($last['created_at'].' UTC')<time()-86400){throw new RuntimeException('Outside the standard reply window');}
                    $version=(string)($this->s->setting('meta_graph_version') ?: env('META_GRAPH_VERSION') ?: 'v21.0');
                    if(!preg_match('/^v\d+\.\d+$/D',$version)){throw new RuntimeException('Set META_GRAPH_VERSION');}
                    Http::request('https://graph.facebook.com/'.$version.'/me/messages','POST',['Authorization: Bearer '.$token,'Content-Type: application/json'],json_encode(['recipient'=>['id'=>$out['external_id']],'message'=>['text'=>$out['body']],'messaging_type'=>'RESPONSE'],JSON_UNESCAPED_UNICODE));
                }elseif($out['platform']==='telegram'){
                    Http::request('https://api.telegram.org/bot'.$token.'/sendMessage','POST',['Content-Type: application/json'],json_encode(['chat_id'=>$out['external_id'],'text'=>$out['body']],JSON_UNESCAPED_UNICODE));
                }
                $this->s->exec("UPDATE outbox SET status='sent',error='' WHERE id=?",[$out['id']]); $result['sent']++;
            }catch(Throwable $e){
                $this->s->exec("UPDATE outbox SET status='requires_review',error='ตรวจสอบสถานะกับแพลตฟอร์มก่อนส่งซ้ำ: '.$e->getMessage() WHERE id=?",[$out['id']]); $result['failed']++;
            }
        }
        return $result;
    }
    private static function uuid(int $id): string { $h=hash('sha256','outbox-'.$id); return substr($h,0,8).'-'.substr($h,8,4).'-4'.substr($h,13,3).'-8'.substr($h,17,3).'-'.substr($h,20,12); }
}
