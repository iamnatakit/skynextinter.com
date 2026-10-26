<?php
namespace App\Libraries\Platform;

use DateTimeImmutable;
use RuntimeException;

/** Bank verification only. No OCR/LLM output can authorize a payment. */
class SlipVerifier
{
    public function __construct(private ?Store $settings = null) {}
    public static function value(string $key, ?Store $store = null): string
    { return (string)($store?->setting(strtolower($key), '') ?: env($key, '')); }
    public static function ready(string $provider, ?Store $store = null): bool
    {
        return (bool)self::value('SLIP_RECEIVER_BANK', $store) && (bool)self::value('SLIP_RECEIVER_ACCOUNT', $store) &&
            match ($provider) {
                'easyslip' => (bool)self::value('EASYSLIP_API_KEY', $store),
                'slipok' => (bool)self::value('SLIPOK_API_KEY', $store) && ctype_digit(self::value('SLIPOK_BRANCH_ID', $store)),
                default => false,
            };
    }

    public function verify(string $provider, string $path, string $mime, array $order): array
    {
        if (!self::ready($provider, $this->settings)) { throw new RuntimeException('ยังตั้งค่าบริการตรวจสลิปและบัญชีผู้รับไม่ครบ'); }
        if (!is_file($path) || filesize($path)>4*1024*1024) { throw new RuntimeException('สลิปสำหรับตรวจอัตโนมัติต้องไม่เกิน 4 MB'); }
        $amount=number_format($order['total_satang']/100,2,'.','');
        $file=new \CURLFile($path,$mime,'slip.'.(['image/png'=>'png','image/webp'=>'webp'][$mime]??'jpg'));
        if ($provider==='easyslip') {
            $response=Http::request('https://api.easyslip.com/v2/verify/bank','POST',
                ['Authorization: Bearer '.self::value('EASYSLIP_API_KEY', $this->settings)],
                ['image'=>$file,'matchAccount'=>'true','matchAmount'=>$amount,'checkDuplicate'=>'true','remark'=>$order['number']]);
        } else {
            $response=Http::request('https://api.slipok.com/api/line/apikey/'.self::value('SLIPOK_BRANCH_ID', $this->settings),'POST',
                ['x-authorization: '.self::value('SLIPOK_API_KEY', $this->settings)],['files'=>$file,'log'=>'true','amount'=>$amount]);
        }
        return $this->validate($provider,$response,$order,self::value('SLIP_RECEIVER_BANK', $this->settings),self::value('SLIP_RECEIVER_ACCOUNT', $this->settings));
    }

    /** Public for contract tests; runtime input comes exclusively from the fixed HTTPS APIs above. */
    public function validate(string $provider,array $response,array $order,string $bank,string $account): array
    {
        if (!preg_match('/^\d{3}$/D',$bank) || !preg_match('/^\d{6,20}$/D',$account)) { throw new RuntimeException('ตั้งค่ารหัสธนาคาร 3 หลักและเลขบัญชีผู้รับเฉพาะตัวเลข'); }
        if (($response['success']??null)!==true) { throw new RuntimeException('ผู้ให้บริการยังไม่ยืนยันสลิป กรุณาให้เจ้าหน้าที่ตรวจสอบ'); }
        $data=$response['data']??[];
        if ($provider==='easyslip') {
            $raw=$data['rawSlip']??[];
            $matched=$data['matchedAccount']??[];
            if (($data['isDuplicate']??null)!==false || ($data['isAmountMatched']??null)!==true ||
                ($matched['bank']['code']??'')!==$bank || preg_replace('/\D/','',$matched['bankNumber']??'')!==$account ||
                ($raw['receiver']['bank']['id']??'')!==$bank || ($raw['amount']['local']['currency']??'')!=='THB') {
                throw new RuntimeException('บัญชีผู้รับ ยอดเงิน สกุลเงิน หรือสถานะสลิปซ้ำไม่ผ่านการตรวจสอบ');
            }
            $amount=$raw['amount']['amount']??null;
            if ($this->satang($data['amountInSlip']??null)!==(int)$order['total_satang']) { throw new RuntimeException('ยอดเงินไม่ตรงคำสั่งซื้อ'); }
            $reference=$raw['transRef']??''; $date=$raw['date']??''; $country=$raw['countryCode']??''; $sender=$raw['sender']['bank']['id']??'';
        } elseif ($provider==='slipok') {
            // log=true instructs SlipOK to verify the receiver registered to this branch and reject duplicates.
            $masked=preg_replace('/[-\s]/','',(string)($data['receiver']['account']['value']??''));
            if (($data['success']??null)!==true || ($data['receivingBank']??'')!==$bank ||
                strlen($masked)!==strlen($account) || strlen(preg_replace('/\D/','',$masked))<4 ||
                !preg_match('/^[0-9xX*]+$/D',$masked)) { throw new RuntimeException('ข้อมูลบัญชีผู้รับไม่ตรงหรือไม่เพียงพอ'); }
            for($i=0;$i<strlen($account);$i++) {
                if(ctype_digit($masked[$i]) && $masked[$i]!==$account[$i]) { throw new RuntimeException('บัญชีผู้รับไม่ตรง'); }
            }
            if(isset($data['paidLocalCurrency']) && !in_array((string)$data['paidLocalCurrency'],['764','THB'],true)) { throw new RuntimeException('สกุลเงินไม่ถูกต้อง'); }
            $amount=$data['amount']??null; $reference=$data['transRef']??''; $date=$data['transTimestamp']??'';
            $country=$data['countryCode']??''; $sender=$data['sendingBank']??'';
        } else { throw new RuntimeException('ไม่รองรับบริการนี้'); }
        if ($country!=='TH' || $this->satang($amount)!==(int)$order['total_satang'] || !preg_match('/^[A-Za-z0-9_-]{5,100}$/D',$reference) || !preg_match('/^\d{3}$/D',$sender)) { throw new RuntimeException('ยอดเงินหรือข้อมูลธุรกรรมไม่ถูกต้อง'); }
        // Require an explicit timezone; PHP must never guess the server timezone.
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}T.*(?:Z|[+-]\d{2}:\d{2})$/D',$date)) { throw new RuntimeException('เวลาธุรกรรมไม่ถูกต้อง'); }
        $paidAt=(new DateTimeImmutable($date))->getTimestamp();
        $created=(new DateTimeImmutable($order['created_at'].' UTC'))->getTimestamp();
        if ($paidAt<$created-300 || $paidAt>time()+300 || $paidAt<time()-86400) { throw new RuntimeException('สลิปอยู่นอกช่วงเวลาคำสั่งซื้อ กรุณาให้เจ้าหน้าที่ตรวจสอบ'); }
        return ['transaction_key'=>hash('sha256',$sender.':'.$reference),'reference'=>$sender.':'.$reference];
    }

    private function satang(mixed $amount): int
    {
        if(!is_int($amount)&&!is_float($amount)&&!is_string($amount)){throw new RuntimeException('ไม่พบยอดเงินจากผู้ให้บริการ');}
        return Store::amount((string)$amount);
    }
}
