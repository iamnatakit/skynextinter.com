<?php
namespace App\Libraries\Platform;

use RuntimeException;
use Throwable;

class Gateways
{
    public function __construct(public Store $s) {}
    public static function connections(?Store $store = null): array
    {
        $check = function(string $key) use ($store): bool {
            if ($store) {
                $val = $store->setting(strtolower($key));
                if ($val !== '' && $val !== null) { return true; }
            }
            return (bool)env($key);
        };
        $requirements = [
            'DeepSeek AI' => ['DEEPSEEK_API_KEY', 'DEEPSEEK_MODEL'],
            'OpenAI' => ['OPENAI_API_KEY', 'OPENAI_MODEL'],
            'Google Gemini' => ['GEMINI_API_KEY', 'GEMINI_MODEL'],
            'Anthropic Claude' => ['CLAUDE_API_KEY', 'CLAUDE_MODEL'],
            'OpenRouter' => ['OPENROUTER_API_KEY', 'OPENROUTER_MODEL'],
            'LINE' => ['LINE_CHANNEL_SECRET', 'LINE_ACCESS_TOKEN'],
            'Facebook' => ['META_APP_SECRET', 'META_PAGE_TOKEN'],
            'Telegram' => ['TELEGRAM_BOT_TOKEN'],
            'EasySlip' => ['EASYSLIP_API_KEY', 'SLIP_RECEIVER_BANK', 'SLIP_RECEIVER_ACCOUNT'],
            'SlipOK' => ['SLIPOK_API_KEY', 'SLIPOK_BRANCH_ID', 'SLIP_RECEIVER_BANK', 'SLIP_RECEIVER_ACCOUNT'],
            'Stripe' => ['STRIPE_SECRET_KEY', 'STRIPE_WEBHOOK_SECRET'],
            'Opn PromptPay' => ['OPN_SECRET_KEY'],
        ];
        $result = [];
        foreach ($requirements as $name => $keys) {
            $result[$name] = array_combine($keys, array_map($check, $keys));
        }
        return $result;
    }
    public static function stripeSignature(string $raw,string $header,string $secret,int $now=0): bool
    {
        if (!$secret) { return false; } $timestamp=null; $signatures=[];
        foreach(explode(',',$header) as $part) { [$k,$v]=array_pad(explode('=',trim($part),2),2,''); if($k==='t'){$timestamp=$v;} if($k==='v1'){$signatures[]=$v;} }
        if (!$timestamp || !ctype_digit($timestamp) || abs(($now?:time())-(int)$timestamp)>300) { return false; }
        $expected=hash_hmac('sha256',$timestamp.'.'.$raw,$secret);
        foreach($signatures as $signature){if(hash_equals($expected,$signature)){return true;}} return false;
    }
    public function start(int $order,int $user,string $provider): array
    {
        if (!in_array($provider,['stripe','opn']) || !env($provider==='stripe'?'STRIPE_SECRET_KEY':'OPN_SECRET_KEY') || ($provider==='stripe'&&!env('STRIPE_WEBHOOK_SECRET'))) { throw new RuntimeException('ช่องทางนี้ยังไม่ได้ตั้งค่าบัญชีรับชำระ'); }
        $attempt=$this->s->transaction(function() use($order,$user,$provider) {
            $o=$this->s->one('SELECT * FROM orders WHERE id=? AND user_id=?',[$order,$user]);
            if(!$o){throw new RuntimeException('ไม่พบคำสั่งซื้อ');}
            $existing=$this->s->one("SELECT * FROM gateway_attempts WHERE order_id=? AND status IN ('starting','ready','uncertain') ORDER BY id DESC",[$order]);
            if($existing){return $existing;}
            if($o['status']!=='awaiting_payment' || $o['total_satang']<2000){throw new RuntimeException('สถานะคำสั่งซื้อหรือยอดชำระไม่รองรับช่องทางนี้ (ขั้นต่ำ 20 บาท)');}
            $id=$this->s->insert('gateway_attempts',['order_id'=>$order,'provider'=>$provider,'created_at'=>Store::now()]);
            $this->s->exec("UPDATE orders SET status='gateway_pending',updated_at=? WHERE id=?",[Store::now(),$order]);
            return ['id'=>$id,'order_id'=>$order,'provider'=>$provider,'status'=>'new','url'=>''];
        });
        if($attempt['status']!=='new') {
            if($attempt['provider']===$provider && $attempt['status']==='ready' && $attempt['url']){return $attempt;}
            throw new RuntimeException('มีรายการรับชำระอยู่แล้ว หากยังไม่แสดงช่องชำระ ให้เจ้าหน้าที่ตรวจสอบกับผู้ให้บริการก่อน');
        }
        $o=$this->s->one('SELECT * FROM orders WHERE id=?',[$order]);
        try {
            if($provider==='stripe') {
                $payload=['mode'=>'payment','success_url'=>site_url('orders/'.$order).'?payment=return','cancel_url'=>site_url('orders/'.$order).'?payment=cancel','client_reference_id'=>(string)$order,'metadata'=>['order_id'=>(string)$order],'line_items'=>[['price_data'=>['currency'=>'thb','unit_amount'=>$o['total_satang'],'product_data'=>['name'=>'Order '.$o['number']]],'quantity'=>1]]];
                $response=Http::request('https://api.stripe.com/v1/checkout/sessions','POST',['Authorization: Bearer '.env('STRIPE_SECRET_KEY'),'Content-Type: application/x-www-form-urlencoded','Idempotency-Key: skynext-order-'.$order.'-'.$attempt['id']],http_build_query($payload));
                $external=$response['id']; $link=$response['url'];
                if(!str_starts_with($link,'https://checkout.stripe.com/')){throw new RuntimeException('ลิงก์ชำระเงินไม่ถูกต้อง');}
            } else {
                $response=Http::request('https://api.omise.co/charges','POST',['Authorization: Basic '.base64_encode(env('OPN_SECRET_KEY').':'),'Content-Type: application/x-www-form-urlencoded'],http_build_query(['amount'=>$o['total_satang'],'currency'=>'thb','source'=>['type'=>'promptpay'],'description'=>$o['number'],'metadata'=>['order_id'=>(string)$order]]));
                $external=$response['id']; $link=$response['source']['scannable_code']['image']['download_uri']??'';
                if(!preg_match('~^https://(?:[^/]+\.)?(?:omise\.co|opn\.ooo)/~',$link)){throw new RuntimeException('ไม่พบ QR จากผู้ให้บริการ');}
            }
            $this->s->exec("UPDATE gateway_attempts SET external_id=?,url=?,status=CASE WHEN status='starting' THEN 'ready' ELSE status END WHERE id=?",[$external,$link,$attempt['id']]);
            return ['url'=>$link];
        } catch(Throwable $e) {
            $this->s->exec("UPDATE gateway_attempts SET status='uncertain' WHERE id=? AND status='starting'",[$attempt['id']]);
            $this->s->audit($user,'gateway_requires_review','order',$order,$provider);
            throw $e;
        }
    }
    public function settle(string $provider,string $event,string $external,int $amount,string $currency,bool $paid): void
    {
        $this->s->transaction(function() use($provider,$event,$external,$amount,$currency,$paid) {
            if(!$this->s->exec('INSERT OR IGNORE INTO webhook_events(event_key,created_at) VALUES(?,?)',[$provider.':'.$event,Store::now()])){return;}
            $a=$this->s->one('SELECT * FROM gateway_attempts WHERE provider=? AND external_id=?',[$provider,$external]);
            // The create response may still be in flight. Roll back so the provider can retry.
            if(!$a){throw new RuntimeException('Payment attempt not ready');}
            $o=$this->s->one('SELECT * FROM orders WHERE id=?',[$a['order_id']]);
            if($a['status']==='paid'){return;}
            if(!$paid){
                $this->s->exec("UPDATE gateway_attempts SET status='expired' WHERE id=?",[$a['id']]);
                if($o['status']==='gateway_pending'){$this->s->exec("UPDATE orders SET status='awaiting_payment',updated_at=? WHERE id=?",[Store::now(),$o['id']]);} return;
            }
            if(strtolower($currency)!=='thb' || $amount!==(int)$o['total_satang']){throw new RuntimeException('Payment amount mismatch');}
            if($o['status']!=='gateway_pending' || $this->s->one("SELECT id FROM payments WHERE order_id=? AND status='approved'",[$o['id']])){
                $this->s->exec("UPDATE gateway_attempts SET status='requires_review' WHERE id=?",[$a['id']]);
                $this->s->audit(null,'duplicate_or_late_payment','order',$o['id'],$external); return;
            }
            $this->s->insert('payments',['order_id'=>$o['id'],'amount_satang'=>$amount,'reference'=>$provider.':'.$external,'slip'=>'','slip_hash'=>$provider.':'.$external,'mime'=>'application/json','status'=>'approved','review_note'=>'Verified provider event','created_at'=>Store::now()]);
            $this->s->exec("UPDATE gateway_attempts SET status='paid' WHERE id=?",[$a['id']]);
            $this->s->exec("UPDATE orders SET status='paid',updated_at=? WHERE id=?",[Store::now(),$o['id']]);
            (new Membership($this->s))->qualifyPurchase((int)$o['id']);
            $this->s->audit(null,'gateway_paid','order',$o['id'],$provider.':'.$external);
        });
    }
    public function reconcile(int $attempt,int $actor,string $reference=''): void
    {
        $a=$this->s->one('SELECT * FROM gateway_attempts WHERE id=?',[$attempt]);
        if(!$a){throw new RuntimeException('ไม่พบรายการรับชำระ');}
        $external=$a['external_id']?:trim($reference);
        $stripe=$a['provider']==='stripe';$key=(string)env($stripe?'STRIPE_SECRET_KEY':'OPN_SECRET_KEY');
        $pattern=$stripe?'/^cs_(test_|live_)?[A-Za-z0-9]+$/D':'/^chrg_(test_)?[A-Za-z0-9]+$/D';
        if(!$key||!preg_match($pattern,$external)){throw new RuntimeException('ตั้งค่าคีย์และระบุเลขอ้างอิงจากผู้ให้บริการ');}
        $r=Http::request($stripe?'https://api.stripe.com/v1/checkout/sessions/'.$external:'https://api.omise.co/charges/'.$external,'GET',[$stripe?'Authorization: Bearer '.$key:'Authorization: Basic '.base64_encode($key.':')]);
        if((string)($r['metadata']['order_id']??'')!==(string)$a['order_id']){throw new RuntimeException('รายการนี้ไม่ใช่การชำระของคำสั่งซื้อนี้');}
        $o=$this->s->one('SELECT * FROM orders WHERE id=?',[$a['order_id']]);
        $amount=(int)($stripe?$r['amount_total']:$r['amount']);
        if($amount!==(int)$o['total_satang']||strtolower($r['currency']??'')!=='thb'){throw new RuntimeException('ยอดเงินหรือสกุลเงินไม่ตรงคำสั่งซื้อ');}
        $this->s->exec('UPDATE gateway_attempts SET external_id=? WHERE id=?',[$external,$attempt]);
        $paid=$stripe?($r['payment_status']??'')==='paid':(($r['status']??'')==='successful'&&!empty($r['paid']));
        $expired=$stripe?($r['status']??'')==='expired':in_array($r['status']??'',['expired','failed']);
        if($paid||$expired){$this->settle($a['provider'],'reconcile:'.$attempt.':'.($paid?'paid':'expired'),$external,$amount,$r['currency'],$paid);}
        else{
            $link=$stripe?($r['url']??''):($r['source']['scannable_code']['image']['download_uri']??'');
            $valid=$stripe?str_starts_with($link,'https://checkout.stripe.com/'):preg_match('~^https://(?:[^/]+\.)?(?:omise\.co|opn\.ooo)/~',$link);
            if($valid){$this->s->exec("UPDATE gateway_attempts SET status='ready',url=? WHERE id=? AND status IN ('starting','uncertain','ready')",[$link,$attempt]);}
        }
        $this->s->audit($actor,'gateway_reconciled','order',$a['order_id'],$a['provider'].':'.$external);
    }
}
