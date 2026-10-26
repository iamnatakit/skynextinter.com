<?php
namespace App\Libraries\Platform;

use RuntimeException;
use Throwable;

class SlipPayments
{
    public function __construct(private Store $s,private ?SlipVerifier $verifier=null) {}

    public static function enabled(): bool
    {
        return env('SLIP_AUTO_VERIFY','0')==='1' && in_array(env('SLIP_PROVIDER'),['easyslip','slipok'],true);
    }

    /** Called inside the payment submission transaction. */
    public static function queue(Store $s,int $payment): void
    {
        if(self::enabled()) {
            $s->exec('INSERT INTO slip_checks(payment_id,provider,updated_at) VALUES(?,?,?)',[$payment,env('SLIP_PROVIDER'),Store::now()]);
        }
    }

    public function check(int $payment): string
    {
        if(!self::enabled()){return 'manual';}
        $lease=bin2hex(random_bytes(16));
        $job=$this->s->transaction(function() use($payment,$lease) {
            $job=$this->s->one("SELECT c.*,p.slip,p.mime,p.status payment_status,p.order_id FROM slip_checks c JOIN payments p ON p.id=c.payment_id WHERE c.payment_id=?",[$payment]);
            if(!$job || $job['status']!=='queued'){return null;}
            $order=$this->s->one('SELECT * FROM orders WHERE id=?',[$job['order_id']]);
            if($job['payment_status']!=='pending' || $order['status']!=='payment_review'){
                $this->s->exec("UPDATE slip_checks SET status='review',note='มีการเปลี่ยนสถานะโดยเจ้าหน้าที่หรือลูกค้าแล้ว',updated_at=? WHERE payment_id=?",[Store::now(),$payment]);
                return null;
            }
            $this->s->exec("UPDATE slip_checks SET status='checking',lease=?,lease_until=?,attempts=attempts+1,updated_at=? WHERE payment_id=?",[$lease,time()+120,Store::now(),$payment]);
            return $job+['order'=>$order];
        });
        if(!$job){return 'unchanged';}
        try {
            if(!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',$job['slip'])) { throw new RuntimeException('ชื่อไฟล์สลิปไม่ถูกต้อง'); }
            $proof=($this->verifier??new SlipVerifier())->verify($job['provider'],WRITEPATH.'slips/'.$job['slip'],$job['mime'],$job['order']);
            return $this->s->transaction(function() use($payment,$lease,$job,$proof) {
                $check=$this->s->one('SELECT * FROM slip_checks WHERE payment_id=?',[$payment]);
                $p=$this->s->one('SELECT * FROM payments WHERE id=?',[$payment]);
                $o=$this->s->one('SELECT * FROM orders WHERE id=?',[$job['order_id']]);
                if($check['status']!=='checking' || !hash_equals($check['lease'],$lease) || $p['status']!=='pending' || $o['status']!=='payment_review') {
                    throw new RuntimeException('สถานะเปลี่ยนระหว่างตรวจสลิป กรุณาให้เจ้าหน้าที่ตรวจสอบ');
                }
                if($this->s->one('SELECT payment_id FROM verified_transfers WHERE transaction_key=?',[$proof['transaction_key']]) || $this->s->one('SELECT invoice_id FROM platform_transfers WHERE transaction_key=?',[$proof['transaction_key']])) {
                    throw new RuntimeException('เลขธุรกรรมนี้เคยใช้ชำระแล้ว');
                }
                $this->s->insert('verified_transfers',['transaction_key'=>$proof['transaction_key'],'payment_id'=>$payment,'provider'=>$job['provider'],'verified_at'=>Store::now()]);
                $this->s->exec("UPDATE payments SET status='approved',reference=?,review_note=? WHERE id=?",[$job['provider'].':'.$proof['reference'],'ยืนยันธุรกรรมอัตโนมัติผ่าน '.$job['provider'],$payment]);
                $this->s->exec("UPDATE orders SET status='paid',updated_at=? WHERE id=?",[Store::now(),$o['id']]);
                (new Membership($this->s))->qualifyPurchase((int)$o['id']);
                // Receipt reference only: this token grants no authentication, AI credit or redemption rights.
                $this->s->insert('payment_tokens',['order_id'=>$o['id'],'payment_id'=>$payment,'token'=>'SNP-'.bin2hex(random_bytes(32)),'issued_at'=>Store::now()]);
                $this->s->exec("UPDATE slip_checks SET status='verified',note='ยืนยันธุรกรรมสำเร็จ',lease='',lease_until=0,updated_at=? WHERE payment_id=?",[Store::now(),$payment]);
                $this->s->audit(null,'slip_auto_verified','order',$o['id'],$job['provider']);
                return 'verified';
            });
        } catch(Throwable $e) {
            // Keep the private uploaded file and pending payment for a human. Never treat transport errors as proof of payment.
            $note=$e instanceof RuntimeException?$e->getMessage():'ตรวจสลิปไม่สำเร็จ กรุณาให้เจ้าหน้าที่ตรวจสอบ';
            $this->s->exec("UPDATE slip_checks SET status='review',note=?,lease='',lease_until=0,updated_at=? WHERE payment_id=? AND lease=? AND status='checking'",[mb_substr($note,0,300),Store::now(),$payment,$lease]);
            return 'review';
        }
    }

    public function work(): int
    {
        if(!self::enabled()){return 0;}
        // A terminated request may already have consumed the provider verification. Do not retry that API call automatically.
        $this->s->exec("UPDATE slip_checks SET status='review',note='การตรวจสลิปขาดการเชื่อมต่อ ให้เจ้าหน้าที่ตรวจสอบกับผู้ให้บริการ',lease='',lease_until=0,updated_at=? WHERE status='checking' AND lease_until<?",[Store::now(),time()]);
        $count=0;
        foreach($this->s->all("SELECT payment_id FROM slip_checks WHERE status='queued' ORDER BY payment_id LIMIT 10") as $row) {
            if($this->check($row['payment_id'])==='verified'){$count++;}
        }
        return $count;
    }
}
