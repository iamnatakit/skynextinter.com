<?php
namespace App\Libraries\Platform;

use RuntimeException;

/** Owner-scoped pages and encrypted private membership records. Never accept an owner from form input. */
class MemberWorkspace
{
    public const FIELDS = [
        'prefix'=>'คำนำหน้า','first_name'=>'ชื่อจริง','last_name'=>'นามสกุล','english_name'=>'ชื่อภาษาอังกฤษ',
        'national_id'=>'เลขบัตรประชาชน 13 หลัก','birth_date'=>'วันเกิด','nationality'=>'สัญชาติ','occupation'=>'อาชีพ',
        'mobile'=>'โทรศัพท์มือถือ','email'=>'อีเมลติดต่อ','address'=>'บ้านเลขที่ / หมู่ / ซอย / ถนน',
        'subdistrict'=>'แขวง / ตำบล','district'=>'เขต / อำเภอ','province'=>'จังหวัด','postcode'=>'รหัสไปรษณีย์',
        'shipping_address'=>'ที่อยู่จัดส่ง (หากต่างจากที่อยู่หลัก)','bank_name'=>'ชื่อธนาคาร','bank_branch'=>'สาขาธนาคาร',
        'bank_account'=>'เลขบัญชี','bank_account_name'=>'ชื่อบัญชี','beneficiary_name'=>'ชื่อผู้รับผลประโยชน์',
        'beneficiary_relation'=>'ความสัมพันธ์กับผู้รับผลประโยชน์','beneficiary_phone'=>'โทรศัพท์ผู้รับผลประโยชน์',
        'emergency_name'=>'ผู้ติดต่อฉุกเฉิน','emergency_phone'=>'โทรศัพท์ฉุกเฉิน',
    ];
    private string $root;
    public function __construct(private Store $s, ?string $root=null) { $this->root=$root??(string)env('MEMBER_WORKSPACE_STORAGE',WRITEPATH.'private/member-workspaces'); }
    public function canPages(array $user): bool
    { return Access::can($user,'salespages',$this->s) && (new Membership($this->s))->status((int)$user['id'])['allowed']; }
    public function menus(array $user): array
    {
        if($user['role']==='member'){return ['workspace'=>'ระบบสมาชิกผู้ซื้อ','shop'=>'เลือกซื้อสินค้า','account'=>'โปรไฟล์ / คำสั่งซื้อของฉัน','payment'=>'ช่องทางชำระเงิน'];}
        $menus=['workspace'=>'ภาพรวม Workspace','workspace/profile'=>'โปรไฟล์สมาชิก','workspace/documents'=>'เอกสารสมัครสมาชิก'];
        if($this->canPages($user)){$menus['workspace/salespages']='Sales Page ของฉัน';}
        if(Access::can($user,'affiliates',$this->s) && (new Membership($this->s))->status((int)$user['id'])['allowed']){$menus['affiliate']='ลิงก์แนะนำ / สายงาน';}
        $menus['account']='คำสั่งซื้อ / บัญชี';$menus['platform/billing']='สิทธิ์ใช้งาน Platform';
        if(Access::isAdmin($user)){$menus['workspace/review']='ตรวจเอกสารสมาชิก';}
        return $menus;
    }
    private function key(): string
    {
        if(!is_dir($this->root) && !mkdir($this->root,0700,true) && !is_dir($this->root)){throw new RuntimeException('เตรียมพื้นที่เอกสารไม่สำเร็จ');}
        $path=$this->root.'/encryption.key';
        if(!is_file($path)){
            $handle=@fopen($path,'x+b');
            if($handle){try{if(!flock($handle,LOCK_EX)||fwrite($handle,random_bytes(32))!==32){throw new RuntimeException('สร้างกุญแจไม่สำเร็จ');}fflush($handle);}finally{fclose($handle);}chmod($path,0600);}
        }
        $handle=fopen($path,'rb');if(!$handle){throw new RuntimeException('ไม่พบกุญแจเอกสาร');}
        try{flock($handle,LOCK_SH);$key=stream_get_contents($handle);}finally{fclose($handle);}
        if(strlen($key)!==32){throw new RuntimeException('กุญแจเอกสารไม่ถูกต้อง');}return $key;
    }
    private function encrypt(string $text): string
    { $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($text,'aes-256-gcm',$this->key(),OPENSSL_RAW_DATA,$iv,$tag,'skynext-member-v1');if($cipher===false){throw new RuntimeException('เข้ารหัสไม่สำเร็จ');}return base64_encode($iv.$tag.$cipher); }
    private function decrypt(string $encoded): string
    { $bytes=base64_decode($encoded,true);if($bytes===false||strlen($bytes)<28){throw new RuntimeException('ข้อมูลเอกสารไม่ถูกต้อง');}$plain=openssl_decrypt(substr($bytes,28),'aes-256-gcm',$this->key(),OPENSSL_RAW_DATA,substr($bytes,0,12),substr($bytes,12,16),'skynext-member-v1');if($plain===false){throw new RuntimeException('เปิดข้อมูลเข้ารหัสไม่สำเร็จ');}return $plain; }
    public function profile(int $owner): array
    { $row=$this->s->one('SELECT * FROM member_profiles WHERE user_id=?',[$owner]);return ['data'=>$row&&$row['private_data']?json_decode($this->decrypt($row['private_data']),true,512,JSON_THROW_ON_ERROR):[], 'submitted_at'=>$row['submitted_at']??null]; }
    public function saveProfile(int $owner,array $input): void
    {
        if(($input['private_consent']??'')!=='1'){throw new RuntimeException('กรุณายินยอมให้จัดเก็บข้อมูลเพื่อสมัครและดูแลสมาชิก');}
        $data=[];foreach(self::FIELDS as $key=>$label){$v=trim((string)($input[$key]??''));if(mb_strlen($v)>($key==='address'||$key==='shipping_address'?1000:200)){throw new RuntimeException($label.' ยาวเกินไป');}$data[$key]=$v;}
        if($data['email']!==''&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL)){throw new RuntimeException('อีเมลไม่ถูกต้อง');}
        if($data['postcode']!==''&&!preg_match('/^[0-9]{5}$/D',$data['postcode'])){throw new RuntimeException('รหัสไปรษณีย์ต้องมี 5 หลัก');}
        if($data['birth_date']!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$data['birth_date'])||!($date=\DateTimeImmutable::createFromFormat('!Y-m-d',$data['birth_date']))||$date->format('Y-m-d')!==$data['birth_date']||$date>new \DateTimeImmutable('today'))){throw new RuntimeException('วันเกิดไม่ถูกต้อง');}
        if($data['national_id']!==''){
            $n=$data['national_id'];if(!preg_match('/^[0-9]{13}$/D',$n)){throw new RuntimeException('เลขบัตรประชาชนต้องมี 13 หลัก');}$sum=0;for($i=0;$i<12;$i++){$sum+=(int)$n[$i]*(13-$i);}if((11-$sum%11)%10!==(int)$n[12]||preg_match('/^(\d)\1{12}$/D',$n)){throw new RuntimeException('เลขบัตรประชาชนไม่ผ่านการตรวจรูปแบบ');}
        }
        if($data['bank_account']!==''&&!preg_match('/^[0-9 -]{6,30}$/D',$data['bank_account'])){throw new RuntimeException('เลขบัญชีต้องเป็นตัวเลข');}
        $submitted=($input['submit_application']??'')==='1';
        if($submitted){foreach(['first_name','last_name','national_id','birth_date','mobile','address','subdistrict','district','province','postcode','bank_name','bank_account','bank_account_name'] as $required){if($data[$required]===''){throw new RuntimeException('กรุณากรอก '.self::FIELDS[$required]);}}
            foreach(['identity','bankbook'] as $kind){if(!$this->s->one("SELECT id FROM member_documents WHERE user_id=? AND kind=? AND status!='rejected'",[$owner,$kind])){throw new RuntimeException('กรุณาแนบเอกสารบัตรประชาชนและสมุดบัญชีให้ครบ');}}
        }
        $cipher=$this->encrypt(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        $this->s->transaction(function()use($owner,$data,$submitted,$cipher){
            $old=$this->profile($owner)['data'];
            foreach(['identity'=>['prefix','first_name','last_name','national_id','birth_date'],'bankbook'=>['bank_name','bank_account','bank_account_name']] as $kind=>$keys){
                foreach($keys as $key){if(($old[$key]??'')!==$data[$key]){$this->s->exec("UPDATE member_documents SET status='pending',review_note='',reviewed_by=NULL,reviewed_at=NULL WHERE user_id=? AND kind=?",[$owner,$kind]);break;}}
            }
            $this->s->exec('INSERT INTO member_profiles(user_id,private_data,submitted_at,updated_at) VALUES(?,?,?,?) ON CONFLICT(user_id) DO UPDATE SET private_data=excluded.private_data,submitted_at=excluded.submitted_at,updated_at=excluded.updated_at',[$owner,$cipher,$submitted?Store::now():null,Store::now()]);
            $this->s->audit($owner,$submitted?'membership_application_submitted':'member_profile_saved','user',$owner);
        });
    }
    public function documents(int $owner): array { return $this->s->all('SELECT id,kind,mime,size,status,review_note,created_at FROM member_documents WHERE user_id=? ORDER BY kind',[$owner]); }
    public function upload(int $owner,string $kind,string $bytes,bool $consent): int
    {
        if(!$consent){throw new RuntimeException('กรุณายินยอมให้จัดเก็บเอกสารเพื่อสมัครสมาชิก');}
        if(!in_array($kind,['identity','bankbook'],true)||strlen($bytes)<10||strlen($bytes)>5*1024*1024){throw new RuntimeException('ใช้ไฟล์ JPG, PNG หรือ PDF ไม่เกิน 5 MB');}
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if(!in_array($mime,['image/jpeg','image/png','application/pdf'],true)){throw new RuntimeException('รองรับเฉพาะ JPG, PNG หรือ PDF');}
        if(str_starts_with($mime,'image/')&&!@getimagesizefromstring($bytes)){throw new RuntimeException('ไฟล์ภาพเสียหาย');}
        if($mime==='application/pdf'&&!str_starts_with($bytes,'%PDF-')){throw new RuntimeException('ไฟล์ PDF ไม่ถูกต้อง');}
        $cipher=$this->encrypt($bytes);$storage=bin2hex(random_bytes(24)).'.enc';$path=$this->root.'/'.$storage;
        if(file_put_contents($path,$cipher,LOCK_EX)===false){throw new RuntimeException('บันทึกไฟล์ไม่สำเร็จ');}chmod($path,0600);
        try{$old=$this->s->transaction(function()use($owner,$kind,$storage,$mime,$bytes){$old=$this->s->one('SELECT storage_key FROM member_documents WHERE user_id=? AND kind=?',[$owner,$kind]);$this->s->exec("INSERT INTO member_documents(user_id,kind,storage_key,mime,size,created_at) VALUES(?,?,?,?,?,?) ON CONFLICT(user_id,kind) DO UPDATE SET storage_key=excluded.storage_key,mime=excluded.mime,size=excluded.size,status='pending',review_note='',reviewed_by=NULL,reviewed_at=NULL,created_at=excluded.created_at",[$owner,$kind,$storage,$mime,strlen($bytes),Store::now()]);$this->s->exec('UPDATE member_profiles SET submitted_at=NULL WHERE user_id=?',[$owner]);$this->s->audit($owner,'member_document_uploaded','user',$owner,$kind);return $old;});}
        catch(\Throwable $e){@unlink($path);throw $e;}
        if($old){@unlink($this->root.'/'.$old['storage_key']);}
        return (int)$this->s->one('SELECT id FROM member_documents WHERE user_id=? AND kind=?',[$owner,$kind])['id'];
    }
    public function download(array $actor,int $id): array
    {
        $row=$this->s->one('SELECT * FROM member_documents WHERE id=? AND (user_id=? OR CAST(? AS INTEGER)=1)',[$id,$actor['id'],Access::isAdmin($actor)?1:0]);
        if(!$row||empty($actor['active'])){throw new RuntimeException('ไม่พบเอกสารหรือไม่มีสิทธิ์');}
        if(!preg_match('/^[a-f0-9]{48}\.enc$/D',$row['storage_key'])){throw new RuntimeException('ไฟล์ไม่ถูกต้อง');}
        $cipher=@file_get_contents($this->root.'/'.$row['storage_key']);if($cipher===false){throw new RuntimeException('ไม่พบไฟล์เอกสาร');}
        $this->s->audit($actor['id'],'member_document_downloaded','document',$id);
        return ['bytes'=>$this->decrypt($cipher),'mime'=>$row['mime'],'name'=>$row['kind'].'.'.(['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'][$row['mime']])];
    }
    public function review(array $actor,int $id,string $status,string $note): void
    {
        if(!Access::isAdmin($actor)||!in_array($status,['approved','rejected'],true)||mb_strlen(trim($note))<3||mb_strlen($note)>500){throw new RuntimeException('ไม่มีสิทธิ์หรือกรอกผลตรวจและหมายเหตุไม่ครบ');}
        if(!$this->s->exec('UPDATE member_documents SET status=?,review_note=?,reviewed_by=?,reviewed_at=? WHERE id=?',[$status,trim($note),$actor['id'],Store::now(),$id])){throw new RuntimeException('ไม่พบเอกสาร');}
        $this->s->audit($actor['id'],'member_document_'.$status,'document',$id);
    }
    public function pages(int $owner): array { return $this->s->all('SELECT * FROM member_salespages WHERE user_id=? ORDER BY id DESC',[$owner]); }
    public function page(int $owner,int $id): ?array { return $this->s->one('SELECT * FROM member_salespages WHERE id=? AND user_id=?',[$id,$owner]); }
    public function savePage(array $actor,int $id,array $input): int
    {
        if(!$this->canPages($actor)){throw new RuntimeException('ไม่มีสิทธิ์สร้าง Sales Page หรือสิทธิ์ Platform หมดอายุ');}
        $owner=(int)$actor['id'];if($id&&!$this->page($owner,$id)){throw new RuntimeException('ไม่พบหน้าของคุณ');}
        $data=[];foreach(['slug'=>70,'title'=>150,'headline'=>200,'intro'=>5000,'benefits'=>3000,'offer'=>3000,'cta'=>60] as $key=>$limit){$data[$key]=trim((string)($input[$key]??''));if(mb_strlen($data[$key])>$limit){throw new RuntimeException('ข้อความยาวเกินกำหนด');}}
        if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$data['slug'])||$data['title']===''||$data['headline']===''){throw new RuntimeException('กรอกชื่อหน้า หัวข้อ และ URL ภาษาอังกฤษตัวเล็ก ตัวเลข หรือขีดกลาง');}
        $data['cta']=$data['cta']?:'สอบถามรายละเอียด';$data['theme']=in_array($input['theme']??'', ['green','gold'],true)?$input['theme']:'green';$data['cover']=in_array($input['cover']??'', ['platform','business','none'],true)?$input['cover']:'platform';
        $data['status']=in_array($input['status']??'', ['draft','published','archived'],true)?$input['status']:'draft';
        $ids=array_values(array_unique(array_filter(array_map('intval',is_array($input['product_ids']??null)?$input['product_ids']:[]),fn($v)=>$v>0)));if(count($ids)>12){throw new RuntimeException('เลือกสินค้าได้สูงสุด 12 รายการ');}
        foreach($ids as $product){if(!$this->s->one('SELECT id FROM products WHERE id=? AND active=1',[$product])){throw new RuntimeException('สินค้าไม่พร้อมเผยแพร่');}}
        $data['product_ids']=json_encode($ids);$data['updated_at']=Store::now();
        return $this->s->transaction(function()use($id,$owner,$data){if($this->s->one('SELECT id FROM member_salespages WHERE user_id=? AND slug=? AND id!=?',[$owner,$data['slug'],$id])){throw new RuntimeException('URL นี้ถูกใช้ใน Workspace ของคุณแล้ว');}if($id){$keys=array_keys($data);$this->s->exec('UPDATE member_salespages SET '.implode(',',array_map(fn($k)=>$k.'=?',$keys)).' WHERE id=? AND user_id=?',[...array_values($data),$id,$owner]);}else{$id=$this->s->insert('member_salespages',$data+['user_id'=>$owner,'created_at'=>Store::now()]);}$this->s->audit($owner,'salespage_saved','salespage',$id,$data['status']);return $id;});
    }
}
