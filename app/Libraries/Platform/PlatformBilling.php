<?php
namespace App\Libraries\Platform;

use RuntimeException;
use Throwable;

class PlatformBilling
{
    public function __construct(private Store $s, private ?SlipVerifier $verifier = null) {}
    public function ready(): bool
    {
        try { PromptPay::payload($this->s->setting('platform_promptpay'), Membership::ANNUAL_SATANG); }
        catch (Throwable $e) { return false; }
        return SlipVerifier::ready($this->s->setting('platform_slip_provider', 'easyslip'), $this->s);
    }
    public function invoice(int $user): array
    {
        if (!$this->ready()) { throw new RuntimeException('ผู้ดูแลยังตั้งค่าพร้อมเพย์และบริการตรวจสลิปไม่ครบ'); }
        $state = (new Membership($this->s))->status($user);
        if ((new RegistrationPlan($this->s))->annual($user) && $state['state']==='trial') { throw new RuntimeException('Packet 990 จะถึงกำหนดเมื่อทดลองใช้ครบ 30 วัน'); }
        if (in_array($state['state'], ['admin','free','locked','customer'], true)) { throw new RuntimeException('บัญชีนี้ไม่ต้องชำระรายปี หรือถูกระงับโดยผู้ดูแล'); }
        return $this->s->transaction(function() use ($user) {
            $this->s->exec("UPDATE platform_invoices SET status='expired' WHERE user_id=? AND status='pending' AND expires_at<=?", [$user, Store::now()]);
            $existing = $this->s->one("SELECT * FROM platform_invoices WHERE user_id=? AND status IN ('pending','checking','review')", [$user]);
            if ($existing) { (new RegistrationPlan($this->s))->bindInvoice($user,(int)$existing['id']);return $existing; }
            $now = Store::now();$offer=(new AnnualWebsiteOffer($this->s))->current($now);
            $expires=Membership::utc($now)->modify('+24 hours')->format('Y-m-d H:i:s');
            if($offer['active']){$expires=min($expires,$offer['expires_at']);}
            $id = $this->s->insert('platform_invoices', ['number'=>'PLT-'.gmdate('Ymd').'-'.strtoupper(bin2hex(random_bytes(5))),
                'user_id'=>$user,'amount_satang'=>$offer['amount_satang'],'created_at'=>$now,'expires_at'=>$expires]);
            $this->s->audit($user, 'platform_invoice_created', 'platform_invoices', $id);
            (new RegistrationPlan($this->s))->bindInvoice($user,$id);
            return $this->s->one('SELECT * FROM platform_invoices WHERE id=?', [$id]);
        });
    }
    public function submit(int $invoice, int $user, array $file): int
    {
        return $this->s->transaction(function() use ($invoice, $user, $file) {
            $bill = $this->s->one('SELECT * FROM platform_invoices WHERE id=? AND user_id=?', [$invoice, $user]);
            if (!$bill || $bill['status'] !== 'pending' || $bill['expires_at'] <= Store::now()) { throw new RuntimeException('รายการชำระเงินไม่อยู่ในสถานะรับสลิป'); }
            if (!$this->ready()) { throw new RuntimeException('ระบบตรวจสลิปยังไม่พร้อม กรุณาติดต่อผู้ดูแล'); }
            if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $file['slip'] ?? '') || !preg_match('/^[a-f0-9]{64}$/D', $file['slip_hash'] ?? '')) { throw new RuntimeException('ไฟล์สลิปไม่ถูกต้อง'); }
            if ($this->s->one('SELECT id FROM payments WHERE slip_hash=?', [$file['slip_hash']]) || $this->s->one('SELECT id FROM platform_slips WHERE slip_hash=?', [$file['slip_hash']])) { throw new RuntimeException('สลิปนี้เคยส่งเข้าระบบแล้ว'); }
            $now = Store::now();
            $id = $this->s->insert('platform_slips', ['invoice_id'=>$invoice,'slip'=>$file['slip'],'mime'=>$file['mime'],'slip_hash'=>$file['slip_hash'],
                'provider'=>$this->s->setting('platform_slip_provider','easyslip'),'created_at'=>$now,'updated_at'=>$now]);
            $this->s->exec("UPDATE platform_invoices SET status='checking' WHERE id=?", [$invoice]);
            $this->s->audit($user, 'platform_slip_submitted', 'platform_invoices', $invoice);
            return $id;
        });
    }
    public function check(int $slip): string
    {
        $lease = bin2hex(random_bytes(16));
        $job = $this->s->transaction(function() use ($slip, $lease) {
            $job = $this->s->one('SELECT s.*,i.number,i.user_id,i.amount_satang,i.status invoice_status,i.created_at invoice_created FROM platform_slips s JOIN platform_invoices i ON i.id=s.invoice_id WHERE s.id=?', [$slip]);
            if (!$job || $job['status'] !== 'queued' || $job['invoice_status'] !== 'checking') { return null; }
            if (!SlipVerifier::ready($job['provider'], $this->s)) { return null; }
            $this->s->exec("UPDATE platform_slips SET status='checking',lease=?,lease_until=?,updated_at=? WHERE id=?", [$lease,time()+120,Store::now(),$slip]);
            return $job;
        });
        if (!$job) { return 'unchanged'; }
        try {
            $proof = ($this->verifier ?? new SlipVerifier($this->s))->verify($job['provider'], WRITEPATH.'platform_slips/'.$job['slip'], $job['mime'],
                ['number'=>$job['number'],'total_satang'=>$job['amount_satang'],'created_at'=>$job['invoice_created']]);
            return $this->s->transaction(function() use ($job, $slip, $lease, $proof) {
                $current = $this->s->one('SELECT * FROM platform_slips WHERE id=?', [$slip]);
                $invoice = $this->s->one('SELECT * FROM platform_invoices WHERE id=?', [$job['invoice_id']]);
                if ($current['status'] !== 'checking' || !hash_equals($current['lease'], $lease) || $invoice['status'] !== 'checking') { throw new RuntimeException('สถานะรายการเปลี่ยนระหว่างตรวจสอบ'); }
                if ($this->s->one('SELECT transaction_key FROM platform_transfers WHERE transaction_key=?', [$proof['transaction_key']]) || $this->s->one('SELECT transaction_key FROM verified_transfers WHERE transaction_key=?', [$proof['transaction_key']])) { throw new RuntimeException('ธุรกรรมนี้เคยใช้ชำระแล้ว'); }
                $now = Store::now();
                $membership = (new Membership($this->s))->ensure($invoice['user_id']);
                $start = max($now, $membership['paid_until'] ?? $now);
                $until = Membership::utc($start)->modify('+1 year')->format('Y-m-d H:i:s');
                $this->s->insert('platform_transfers', ['transaction_key'=>$proof['transaction_key'],'invoice_id'=>$invoice['id'],'verified_at'=>$now]);
                $this->s->exec('UPDATE platform_subscriptions SET paid_until=? WHERE user_id=?', [$until, $invoice['user_id']]);
                (new Membership($this->s))->qualifyAnnual((int)$invoice['user_id']);
                $this->s->exec("UPDATE platform_invoices SET status='paid',paid_at=? WHERE id=?", [$now,$invoice['id']]);
                (new RegistrationPlan($this->s))->paid((int)$invoice['id']);
                $this->s->exec("UPDATE platform_slips SET status='verified',lease='',lease_until=0,note='ยืนยันยอดและบัญชีผู้รับสำเร็จ เปิดสิทธิ์รายปีแล้ว',updated_at=? WHERE id=?", [$now,$slip]);
                $this->s->audit(null, 'platform_renewed_verified', 'users', $invoice['user_id'], 'invoice='.$invoice['number'].'; until='.$until);
                return 'verified';
            });
        } catch (Throwable $e) {
            $this->s->transaction(function() use ($job, $slip, $lease, $e) {
                $note = $e instanceof RuntimeException ? mb_substr($e->getMessage(), 0, 300) : 'ตรวจสลิปไม่สำเร็จ กรุณาติดต่อผู้ดูแล';
                if ($this->s->exec("UPDATE platform_slips SET status='review',lease='',lease_until=0,note=?,updated_at=? WHERE id=? AND lease=? AND status='checking'", [$note,Store::now(),$slip,$lease])) {
                    $this->s->exec("UPDATE platform_invoices SET status='review' WHERE id=? AND status='checking'", [$job['invoice_id']]);
                }
            });
            return 'review';
        }
    }
    public function work(): int
    {
        $this->s->transaction(function() {
            $stale = $this->s->all("SELECT id,invoice_id FROM platform_slips WHERE status='checking' AND lease_until<?", [time()]);
            foreach ($stale as $row) {
                $this->s->exec("UPDATE platform_slips SET status='review',note='การตรวจขาดการเชื่อมต่อ ให้ตรวจสอบกับผู้ให้บริการก่อนลองใหม่',lease='',lease_until=0 WHERE id=?", [$row['id']]);
                $this->s->exec("UPDATE platform_invoices SET status='review' WHERE id=? AND status='checking'", [$row['invoice_id']]);
            }
        });
        $count = 0;
        foreach ($this->s->all("SELECT id FROM platform_slips WHERE status='queued' ORDER BY id LIMIT 10") as $row) {
            if ($this->check($row['id']) === 'verified') { $count++; }
        }
        return $count;
    }
    public function retry(int $slip, int $actor): void
    {
        $this->s->transaction(function() use ($slip,$actor) {
            $row=$this->s->one("SELECT * FROM platform_slips WHERE id=? AND status='review'",[$slip]);
            if (!$row) { throw new RuntimeException('รายการนี้ไม่อยู่ในสถานะรอตรวจ'); }
            $this->s->exec("UPDATE platform_slips SET status='queued',note='',updated_at=? WHERE id=?",[Store::now(),$slip]);
            $this->s->exec("UPDATE platform_invoices SET status='checking' WHERE id=? AND status='review'",[$row['invoice_id']]);
            $this->s->audit($actor,'platform_slip_retry','platform_slips',$slip);
        });
    }
}
