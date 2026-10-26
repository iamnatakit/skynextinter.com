<?php
namespace App\Controllers;

use App\Libraries\Platform\{Store,Bot,Gateways,Http};
use RuntimeException;
use Throwable;

class PlatformApi extends Platform
{
    private function json(array $data,int $status=200)
    { return $this->response->setStatusCode($status)->setJSON($data+['csrf'=>csrf_hash(),'csrf_name'=>csrf_token()]); }
    private function visitor(): string
    {
        if(!session('widget_visitor')){session()->set('widget_visitor',bin2hex(random_bytes(24)));}
        return session('widget_visitor');
    }
    public function bootstrap()
    {
        if(!$this->store->installed()){return $this->json(['ready'=>false]);}
        $this->visitor();
        $provider = $this->store->setting('ai_provider') ?: (env('AI_PROVIDER') ?: 'deepseek');
        $hasKey = (bool)($this->store->setting($provider.'_api_key') ?: env(strtoupper($provider).'_API_KEY') ?: ($provider === 'custom'));
        return $this->json([
            'ready' => true,
            'bot_enabled' => $this->store->setting('bot_enabled', '1') === '1',
            'ai_enabled' => $this->store->setting('ai_enabled', '1') === '1' && $hasKey,
            'web_widget_enabled' => $this->store->setting('web_widget_enabled', '1') === '1',
            'web_widget_title' => $this->store->setting('web_widget_title') ?: 'SkyNext Assistant',
            'web_widget_welcome' => $this->store->setting('web_widget_welcome') ?: ($this->store->setting('bot_welcome_msg') ?: 'สวัสดีครับ มีอะไรให้เราช่วยเหลือสอบถามได้เลยครับ'),
            'web_default_ai' => $this->store->setting('web_default_ai', '1') === '1',
            'analytics_enabled' => $this->store->setting('analytics_enabled') === '1'
        ]);
    }
    public function catalogMap()
    {
        return $this->json(['products'=>$this->store->all('SELECT id,source_path,price_satang,stock,active FROM products WHERE source_path IS NOT NULL')]);
    }
    public function chat()
    {
        try{
            $bot=new Bot($this->store); $visitor=$this->visitor();
            if($this->post()){
                if($this->input('consent')!=='1'){throw new RuntimeException('กรุณายินยอมให้บันทึกการสนทนาเพื่อให้บริการ');}
                $this->store->rate('chat-ip:'.$this->request->getIPAddress(),20,60);
                $this->store->rate('chat:'.$visitor,10,60);
                $text=$this->input('message'); if(!$text || mb_strlen($text)>2000){throw new RuntimeException('ข้อความต้องยาว 1–2000 ตัวอักษร');}
                $c=$bot->conversation('web',$visitor);
                $this->store->exec('UPDATE conversations SET ai_consent=? WHERE id=?',[$this->input('ai_consent')==='1'?1:0,$c['id']]);
                $c=$this->store->one('SELECT * FROM conversations WHERE id=?',[$c['id']]);
                $bot->message($c['id'],'in',$text); $reply=$bot->reply($c,$text);
                if($reply!==null){$bot->message($c['id'],'bot',$reply);}
            }
            $c=$this->store->one("SELECT * FROM conversations WHERE platform='web' AND external_id=?",[$visitor]);
            $messages=$c?$this->store->all('SELECT id,direction,body,created_at FROM messages WHERE conversation_id=? ORDER BY id DESC LIMIT 50',[$c['id']]):[];
            return $this->json(['messages'=>array_reverse($messages),'mode'=>$c['mode']??'auto']);
        }catch(Throwable $e){return $this->json(['error'=>$e instanceof RuntimeException&&!$e instanceof \PDOException?$e->getMessage():'ไม่สามารถตอบข้อความได้ในขณะนี้'],422);}
    }
    public function lead()
    {
        try{
            $this->store->rate('lead:'.$this->request->getIPAddress(),5,3600);
            $bot=new Bot($this->store);$c=$bot->conversation('web',$this->visitor());
            $bot->captureLead($c['id'],$this->input('name'),$this->input('contact'),$this->input('consent')==='1');
            $bot->message($c['id'],'bot','รับข้อมูลติดต่อแล้วค่ะ ทีมงานจะติดต่อกลับ');
            return $this->json(['ok'=>true]);
        }catch(Throwable $e){return $this->json(['error'=>'กรุณาตรวจชื่อ ช่องทางติดต่อ และความยินยอม หรือลองใหม่ภายหลัง'],422);}
    }
    public function track()
    {
        if($this->store->setting('analytics_enabled')!=='1'||$this->input('consent')!=='1'){return $this->json(['ok'=>false]);}
        try{
            $this->store->rate('visit:'.$this->visitor(),60,60);
            $path=(string)(parse_url($this->input('path'),PHP_URL_PATH)?:'/');
            if(!str_starts_with($path,'/')||strlen($path)>1000||str_starts_with($path,'/admin')||str_starts_with($path,'/r/')){return $this->json(['ok'=>false]);}
            $referrer=(string)(parse_url($this->input('referrer'),PHP_URL_HOST)?:'');
            $source=preg_replace('/[^a-zA-Z0-9_-]/','',mb_substr($this->input('source'),0,64));
            $this->store->insert('visits',['visitor'=>hash('sha256',$this->visitor()),'path'=>$path,'referrer'=>substr($referrer,0,200),'source'=>$source,'created_at'=>Store::now()]);
            return $this->json(['ok'=>true]);
        }catch(Throwable $e){return $this->json(['ok'=>false],429);}
    }
    public function hook(string $provider)
    {
        try{
            $raw=$this->request->getBody();
            if(strlen($raw)>2*1024*1024){return $this->response->setStatusCode(413);}
            if($provider==='facebook' && !$this->post()){
                $secret=(string)($this->store->setting('facebook_verify_token') ?: env('META_VERIFY_TOKEN'));
                if($secret && $this->request->getGet('hub_mode')==='subscribe' && hash_equals($secret,(string)$this->request->getGet('hub_verify_token'))){return $this->response->setContentType('text/plain')->setBody((string)$this->request->getGet('hub_challenge'));}
                return $this->response->setStatusCode(403)->setBody('Verification failed');
            }
            if($provider==='telegram'){
                if($this->store->setting('telegram_enabled')!=='1'){return $this->response->setStatusCode(503)->setBody('Channel disabled');}
                $data=json_decode($raw,true,512,JSON_THROW_ON_ERROR);$bot=new Bot($this->store);
                $msg=$data['message'] ?? $data['edited_message'] ?? null;
                if($msg && isset($msg['text'], $msg['chat']['id'])){
                    $chatId=(string)$msg['chat']['id'];
                    $msgId=(string)($msg['message_id'] ?? bin2hex(random_bytes(8)));
                    $bot->inbound('telegram',$msgId,$chatId,mb_substr($msg['text'],0,2000));
                }
                return $this->response->setJSON(['ok'=>true]);
            }
            if(in_array($provider,['line','facebook'])){
                if($this->store->setting($provider.'_enabled')!=='1'){return $this->response->setStatusCode(503)->setBody('Channel disabled');}
                $secret=(string)($this->store->setting($provider==='line'?'line_channel_secret':'facebook_app_secret') ?: env($provider==='line'?'LINE_CHANNEL_SECRET':'META_APP_SECRET'));
                $signature=$this->request->getHeaderLine($provider==='line'?'X-Line-Signature':'X-Hub-Signature-256');
                if(!Bot::signature($provider,$raw,$signature,$secret)){return $this->response->setStatusCode(401)->setBody('Invalid signature');}
                $data=json_decode($raw,true,512,JSON_THROW_ON_ERROR);$bot=new Bot($this->store);
                if($provider==='line'){
                    foreach($data['events']??[] as $e){
                        if(($e['type']??'')!=='message'||($e['message']['type']??'')!=='text'){continue;}
                        $external=$e['source']['userId']??''; $event=$e['webhookEventId']??$e['message']['id']??'';
                        if($external&&$event){$bot->inbound('line',$event,$external,mb_substr($e['message']['text'],0,2000));}
                    }
                }else{
                    foreach($data['entry']??[] as $entry){foreach($entry['messaging']??[] as $e){
                        if(!isset($e['message']['text'],$e['message']['mid'],$e['sender']['id'])||!empty($e['message']['is_echo'])){continue;}
                        $bot->inbound('facebook',$e['message']['mid'],$e['sender']['id'],mb_substr($e['message']['text'],0,2000));
                    }}
                }
            }elseif($provider==='stripe'){
                if(!Gateways::stripeSignature($raw,$this->request->getHeaderLine('Stripe-Signature'),(string)env('STRIPE_WEBHOOK_SECRET'))){return $this->response->setStatusCode(401)->setBody('Invalid signature');}
                $event=json_decode($raw,true,512,JSON_THROW_ON_ERROR);$obj=$event['data']['object']??[];
                $types=['checkout.session.completed','checkout.session.async_payment_succeeded','checkout.session.expired','checkout.session.async_payment_failed'];
                if(in_array($event['type']??'',$types)){
                    if(in_array($event['type'],['checkout.session.completed','checkout.session.async_payment_succeeded'])&&($obj['payment_status']??'')!=='paid'){return $this->response->setJSON(['ok'=>true]);}
                    (new Gateways($this->store))->settle('stripe',$event['id'],$obj['id'],(int)($obj['amount_total']??0),$obj['currency']??'',($obj['payment_status']??'')==='paid');
                }
            }elseif($provider==='opn'){
                if(!env('OPN_SECRET_KEY')){return $this->response->setStatusCode(503)->setBody('Provider not configured');}
                $this->store->rate('opn-hook:'.$this->request->getIPAddress(),30,60);
                $event=json_decode($raw,true,512,JSON_THROW_ON_ERROR);$charge=$event['data']['id']??'';
                if(!preg_match('/^chrg_(test_)?[a-zA-Z0-9]+$/D',$charge)){return $this->response->setStatusCode(400)->setBody('Invalid charge');}
                if(!$this->store->one("SELECT id FROM gateway_attempts WHERE provider='opn' AND external_id=?",[$charge])){return $this->response->setStatusCode(409)->setBody('Unknown payment attempt');}
                // Opn events are confirmed by retrieving the charge using the merchant secret.
                $verified=Http::request('https://api.omise.co/charges/'.$charge,'GET',['Authorization: Basic '.base64_encode(env('OPN_SECRET_KEY').':')]);
                if(in_array($verified['status']??'',['successful','failed','expired'])){
                    (new Gateways($this->store))->settle('opn',$charge.':'.$verified['status'],$charge,(int)$verified['amount'],$verified['currency'],($verified['status']==='successful' && !empty($verified['paid'])));
                }
            }else{return $this->response->setStatusCode(404);}
            return $this->response->setJSON(['ok'=>true]);
        }catch(Throwable $e){log_message('error','Platform webhook could not be processed: {provider}',['provider'=>$provider]);return $this->response->setStatusCode(503)->setJSON(['error'=>'Webhook not processed; retry later']);}
    }
}
