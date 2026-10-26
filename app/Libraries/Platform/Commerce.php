<?php

namespace App\Libraries\Platform;

use RuntimeException;

class Commerce
{
    public function __construct(public Store $store) {}

    public function register(string $name, string $email, string $password, string $referral = '', string $phone = '', array $registration = []): int
    {
        if (mb_strlen(trim($name)) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 200 || strlen($password) < 12 || strlen($password) > 200 || strlen($phone)>40) { throw new RuntimeException('กรอกชื่อ อีเมล และรหัสผ่านอย่างน้อย 12 ตัวอักษร'); }
        $plan=RegistrationPlan::validate($registration);
        // Web registration always passes province; legacy internal import/test callers remain compatible.
        $province='';if(array_key_exists('province',$registration)){[$province,$phone]=ThaiProvinces::registration((string)$registration['province'],$phone);}
        return $this->store->transaction(function () use ($name, $email, $password, $referral, $phone, $plan, $province) {
            $sponsor = $referral !== '' ? (new Affiliate($this->store))->owner(trim($referral)) : null;
            if ($referral !== '' && !$sponsor) { throw new RuntimeException('ไม่พบรหัสผู้แนะนำ'); }
            if ($this->store->one('SELECT id FROM users WHERE email=?', [strtolower(trim($email))])) { throw new RuntimeException('อีเมลนี้ถูกใช้งานแล้ว'); }
            $id=$this->store->insert('users', ['name' => trim($name), 'email' => strtolower(trim($email)), 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $plan['role']??'affiliate', 'phone' => trim($phone), 'referral_code' => Affiliate::nextCode($this->store), 'sponsor_id' => $sponsor['id'] ?? null, 'created_at' => Store::now()]);
            (new RegistrationPlan($this->store))->attach($id,$plan);
            if(!$sponsor){(new PendingReferrals($this->store))->enqueue($id);}
            if($province!==''){$this->store->exec('UPDATE users SET public_province=? WHERE id=?',[$province,$id]);}
            return $id;
        });
    }

    public function quote(array $cart, string $coupon = '', ?int $user = null): array
    {
        if (!$cart || count($cart) > 50) { throw new RuntimeException('ตะกร้าสินค้าว่างหรือมีสินค้ามากเกินไป'); }
        $items = []; $subtotal = 0; $pv = 0;
        $member=ProductPricing::eligible($user!==null?$this->store->one('SELECT role,active FROM users WHERE id=?',[$user]):null);
        foreach ($cart as $id => $quantity) {
            if (!is_numeric($quantity) || (int)$quantity != $quantity || $quantity < 1 || $quantity > 100) { throw new RuntimeException('จำนวนสินค้าไม่ถูกต้อง'); }
            $p = $this->store->one('SELECT * FROM products WHERE id=? AND active=1', [(int)$id]);
            if ($p && AnnualWebsitePackage::matches($p)) { throw new RuntimeException('แพ็กเกจเว็บไซต์รายปีชำระผ่านหน้าสิทธิ์ใช้งาน Platform เท่านั้น'); }
            if (!$p || !empty($p['inquiry_only']) || $p['stock'] < $quantity) { throw new RuntimeException('สินค้าบางรายการไม่พร้อมขายหรือมีสต็อกไม่เพียงพอ'); }
            $p=ProductPricing::apply($p,$member);
            $p['quantity'] = (int)$quantity; $items[] = $p; $subtotal += $p['price_satang'] * (int)$quantity;
            $pv += (int)$p['pv'] * (int)$quantity;
        }
        $promo = null; $discount = 0;
        if (trim($coupon) !== '') {
            $promo = $this->store->one('SELECT * FROM promotions WHERE code=? AND active=1 AND starts_at<=? AND ends_at>=? AND used<usage_limit', [trim($coupon), Store::now(), Store::now()]);
            if (!$promo || $subtotal < $promo['minimum_satang']) { throw new RuntimeException('โค้ดส่วนลดใช้ไม่ได้ หมดอายุ หรือยอดยังไม่ถึงขั้นต่ำ'); }
            $discount = $promo['kind'] === 'percent' ? intdiv($subtotal * min(10000, $promo['value']), 10000) : $promo['value'];
            $discount = min($subtotal, $discount);
        }
        $shipping = max(0, (int)$this->store->setting('shipping_satang', '0'));
        return ['items' => $items, 'pv' => $pv, 'subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $shipping, 'total' => $subtotal - $discount + $shipping, 'promo' => $promo];
    }

    public function checkout(int $user, array $cart, string $coupon, array $shipping, string $key): int
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $key)) { throw new RuntimeException('รหัสคำสั่งซื้อไม่ถูกต้อง'); }
        foreach (['recipient' => 120, 'phone' => 40, 'address' => 1000] as $field => $max) {
            if (mb_strlen(trim($shipping[$field] ?? '')) < 3 || mb_strlen($shipping[$field]) > $max) { throw new RuntimeException('กรอกข้อมูลจัดส่งให้ครบถ้วน'); }
        }
        return $this->store->transaction(function () use ($user, $cart, $coupon, $shipping, $key) {
            $existing = $this->store->one('SELECT id FROM orders WHERE user_id=? AND idempotency_key=?', [$user, $key]);
            if ($existing) { return (int)$existing['id']; }
            $quote = $this->quote($cart, $coupon, $user);
            $id = $this->store->insert('orders', ['number' => 'SN' . gmdate('ymd') . strtoupper(bin2hex(random_bytes(4))), 'user_id' => $user, 'idempotency_key' => $key, 'subtotal_satang' => $quote['subtotal'], 'discount_satang' => $quote['discount'], 'shipping_satang' => $quote['shipping'], 'total_satang' => $quote['total'], 'promo_id' => $quote['promo']['id'] ?? null, 'recipient' => trim($shipping['recipient']), 'phone' => trim($shipping['phone']), 'address' => trim($shipping['address']), 'created_at' => Store::now(), 'updated_at' => Store::now()]);
            foreach ($quote['items'] as $p) {
                if ($this->store->exec('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?', [$p['quantity'], $p['id'], $p['quantity']]) !== 1) { throw new RuntimeException('สต็อกไม่พอ'); }
                $this->store->insert('order_items', ['order_id' => $id, 'product_id' => $p['id'], 'name' => $p['name'], 'price_satang' => $p['price_satang'], 'pv'=>(int)$p['pv'], 'quantity' => $p['quantity']]);
            }
            if ($quote['promo']) { $this->store->exec('UPDATE promotions SET used=used+1 WHERE id=?', [$quote['promo']['id']]); }
            $this->store->audit($user, 'created', 'order', $id);
            return $id;
        });
    }

    public function submitPayment(int $order, int $user, array $data): int
    {
        return $this->store->transaction(function () use ($order, $user, $data) {
            $o = $this->store->one('SELECT * FROM orders WHERE id=? AND user_id=?', [$order, $user]);
            if (!$o || $o['status'] !== 'awaiting_payment') { throw new RuntimeException('คำสั่งซื้อไม่อยู่ในสถานะรับหลักฐาน'); }
            if ((int)$data['amount_satang'] !== (int)$o['total_satang']) { throw new RuntimeException('ยอดชำระต้องตรงกับยอดคำสั่งซื้อ'); }
        if ($this->store->one('SELECT id FROM payments WHERE slip_hash=?', [$data['slip_hash']]) || $this->store->one('SELECT id FROM platform_slips WHERE slip_hash=?', [$data['slip_hash']])) { throw new RuntimeException('หลักฐานนี้เคยถูกใช้แล้ว'); }
            $id = $this->store->insert('payments', ['order_id' => $order] + $data + ['created_at' => Store::now()]);
            $this->store->exec("UPDATE orders SET status='payment_review', updated_at=? WHERE id=?", [Store::now(), $order]);
            $this->store->audit($user, 'payment_submitted', 'order', $order);
            SlipPayments::queue($this->store,$id);
            return $id;
        });
    }

    public function reviewPayment(int $id, int $actor, bool $approve, string $note): void
    {
        if (mb_strlen(trim($note)) < 3) { throw new RuntimeException('กรุณาระบุผลการตรวจสอบ'); }
        $this->store->transaction(function () use ($id, $actor, $approve, $note) {
            $p = $this->store->one('SELECT * FROM payments WHERE id=?', [$id]);
            if (!$p || $p['status'] !== 'pending') { throw new RuntimeException('หลักฐานนี้ตรวจสอบแล้ว'); }
            $o = $this->store->one('SELECT * FROM orders WHERE id=?', [$p['order_id']]);
            if ($approve && (int)$o['user_id']===$actor && !$this->store->one("SELECT id FROM users WHERE id=? AND role='admin'",[$actor])) { throw new RuntimeException('ไม่สามารถอนุมัติการชำระเงินของตนเอง'); }
            if ($o['status'] !== 'payment_review') { throw new RuntimeException('สถานะคำสั่งซื้อไม่ถูกต้อง'); }
            $this->store->exec('UPDATE payments SET status=?,reviewed_by=?,review_note=? WHERE id=?', [$approve ? 'approved' : 'rejected', $actor, $note, $id]);
            $this->store->exec('UPDATE orders SET status=?,updated_at=? WHERE id=?', [$approve ? 'paid' : 'awaiting_payment', Store::now(), $o['id']]);
            if ($approve) { (new Membership($this->store))->qualifyPurchase((int)$o['id']); }
            $this->store->audit($actor, $approve ? 'payment_approved' : 'payment_rejected', 'order', $o['id'], $note);
        });
    }

    public function transition(int $id, ?int $actor, string $next, string $note = '', ?string $expected = null): void
    {
        $this->store->transaction(function () use ($id, $actor, $next, $note, $expected) {
            $o = $this->store->one('SELECT * FROM orders WHERE id=?', [$id]);
            if($expected !== null && (!$o || $o['status'] !== $expected)){throw new RuntimeException('สถานะคำสั่งซื้อเปลี่ยนแล้ว');}
            $allowed = ['awaiting_payment' => ['cancelled'], 'payment_review' => ['cancelled'], 'paid' => ['processing','refund_pending'], 'processing' => ['shipped','refund_pending'], 'shipped' => ['completed','refund_pending'], 'completed' => ['refund_pending'], 'refund_pending' => ['refunded']];
            if (!$o || !in_array($next, $allowed[$o['status']] ?? [])) { throw new RuntimeException('เปลี่ยนสถานะนี้ไม่ได้'); }
            if (in_array($next, ['shipped','refund_pending','refunded']) && strlen(trim($note)) < 3) { throw new RuntimeException('ระบุเลขพัสดุหรือหลักฐานการคืนเงิน'); }
            if (in_array($next, ['cancelled','refunded'])) {
                foreach ($this->store->all('SELECT product_id,quantity FROM order_items WHERE order_id=?', [$id]) as $item) { $this->store->exec('UPDATE products SET stock=stock+? WHERE id=?', [$item['quantity'], $item['product_id']]); }
                if ($o['promo_id']) { $this->store->exec('UPDATE promotions SET used=MAX(0,used-1) WHERE id=?', [$o['promo_id']]); }
                $this->store->exec("UPDATE payments SET status='rejected',reviewed_by=?,review_note=? WHERE order_id=? AND status='pending'", [$actor, 'Order cancelled', $id]);
            }
            $this->store->exec('UPDATE orders SET status=?,tracking=CASE WHEN ?=\'shipped\' THEN ? ELSE tracking END,updated_at=? WHERE id=?', [$next, $next, $note, Store::now(), $id]);
            if($next==='refunded') { $this->store->exec('UPDATE payment_tokens SET revoked_at=? WHERE order_id=? AND revoked_at IS NULL',[Store::now(),$id]); }
            $this->store->audit($actor, $next, 'order', $id, $note);
        });
    }
    public function expireUnpaid(): int
    {
        $count=0;
        foreach($this->store->all("SELECT id FROM orders WHERE status='awaiting_payment' AND created_at<?",[gmdate('Y-m-d H:i:s',time()-86400)]) as $order){
            try{$this->transition($order['id'],null,'cancelled','Unpaid order expired after 24 hours','awaiting_payment');$count++;}
            catch(RuntimeException $e){/* State changed after selection; preserve the newer state. */}
        }
        return $count;
    }
}
