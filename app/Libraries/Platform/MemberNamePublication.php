<?php
namespace App\Libraries\Platform;

use RuntimeException;

/** Names enter this public table only after the authenticated owner explicitly opts in. */
class MemberNamePublication
{
    public function __construct(private Store $s) {}
    public function current(int $owner): ?array
    { return $this->s->one('SELECT full_name,consent_at FROM member_name_publications WHERE user_id=?',[$owner]); }
    public function save(int $owner,bool $consent): void
    {
        $user=$this->s->one('SELECT name FROM users WHERE id=? AND active=1',[$owner]);
        if(!$user){throw new RuntimeException('ไม่พบบัญชีสมาชิก');}
        $this->s->transaction(function()use($owner,$consent,$user){
            if($consent){$this->s->exec('INSERT INTO member_name_publications(user_id,full_name,consent_at) VALUES(?,?,?) ON CONFLICT(user_id) DO UPDATE SET full_name=excluded.full_name,consent_at=excluded.consent_at',[$owner,$user['name'],Store::now()]);}
            else{$this->s->exec('DELETE FROM member_name_publications WHERE user_id=?',[$owner]);}
            $this->s->audit($owner,$consent?'signup_name_publication_accepted':'signup_name_publication_revoked','user',$owner);
        });
    }
}
