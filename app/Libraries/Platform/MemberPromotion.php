<?php
namespace App\Libraries\Platform;

use RuntimeException;

class MemberPromotion
{
    public function __construct(private Store $s) {}
    public function preference(int $owner): array
    { return $this->s->one('SELECT * FROM member_promotion_preferences WHERE user_id=?',[$owner])??['target'=>'home','salespage_id'=>null]; }
    public function save(array $owner,string $target,int $page): void
    {
        if(!$owner['active'] || !in_array($target,['home','salespage'],true)){throw new RuntimeException('เลือกหน้าโปรโมทให้ถูกต้อง');}
        if($target==='salespage' && (!(new MemberWorkspace($this->s))->canPages($owner) || !$this->s->one("SELECT id FROM member_salespages WHERE id=? AND user_id=? AND status='published'",[$page,$owner['id']]))){throw new RuntimeException('เลือก Sales Page ที่เผยแพร่แล้วของคุณเท่านั้น');}
        $this->s->transaction(function()use($owner,$target,$page){
            $this->s->exec('INSERT INTO member_promotion_preferences(user_id,target,salespage_id,updated_at) VALUES(?,?,?,?) ON CONFLICT(user_id) DO UPDATE SET target=excluded.target,salespage_id=excluded.salespage_id,updated_at=excluded.updated_at',[$owner['id'],$target,$target==='salespage'?$page:null,Store::now()]);
            $this->s->audit($owner['id'],'promotion_destination_updated','user',$owner['id'],$target);
        });
    }
    /** A page that was later hidden/deleted/disabled falls back to the homepage. */
    public function destination(array $owner): string
    {
        $choice=$this->preference((int)$owner['id']);
        if($choice['target']==='salespage' && (new MemberWorkspace($this->s))->canPages($owner)){
            $page=$this->s->one("SELECT slug FROM member_salespages WHERE id=? AND user_id=? AND status='published'",[$choice['salespage_id'],$owner['id']]);
            if($page){return 's/'.$owner['referral_code'].'/'.$page['slug'];}
        }
        return '';
    }
}
