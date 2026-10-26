<?php
namespace App\Libraries\Platform;

use RuntimeException;

/** Re-encoded public portraits, separate from private identity documents. */
class ProfilePhoto
{
    public function __construct(private Store $s) {}
    public function save(int $owner,string $bytes): void
    {
        if(!$this->s->one('SELECT id FROM users WHERE id=? AND active=1',[$owner])){throw new RuntimeException('ไม่พบสมาชิก');}
        if(strlen($bytes)>5*1024*1024){throw new RuntimeException('รูปต้องมีขนาดไม่เกิน 5 MB');}
        $info=@getimagesizefromstring($bytes);
        if(!$info || !in_array($info['mime'],['image/jpeg','image/png','image/webp'],true) || $info[0]*$info[1]>12000000){throw new RuntimeException('ใช้รูป JPG, PNG หรือ WebP ไม่เกิน 12 ล้านพิกเซล');}
        $source=@imagecreatefromstring($bytes);if(!$source){throw new RuntimeException('เปิดรูปภาพไม่สำเร็จ');}
        $size=min($info[0],$info[1]);$edge=min(640,$size);$image=imagecreatetruecolor($edge,$edge);
        try{
            imagefill($image,0,0,imagecolorallocate($image,255,255,255));
            imagecopyresampled($image,$source,0,0,(int)(($info[0]-$size)/2),(int)(($info[1]-$size)/2),$edge,$edge,$size,$size);
            ob_start();imagejpeg($image,null,88);$clean=ob_get_clean();
        }finally{imagedestroy($source);imagedestroy($image);}
        $this->s->transaction(function()use($owner,$clean){
            $this->s->exec('INSERT INTO member_photos(user_id,image,version,updated_at) VALUES(?,CAST(? AS BLOB),?,?) ON CONFLICT(user_id) DO UPDATE SET image=excluded.image,version=excluded.version,updated_at=excluded.updated_at',[$owner,$clean,bin2hex(random_bytes(12)),Store::now()]);
            $this->s->audit($owner,'profile_photo_updated','user',$owner);
        });
    }
    public function remove(int $owner): void
    { $this->s->transaction(function()use($owner){$this->s->exec('DELETE FROM member_photos WHERE user_id=?',[$owner]);$this->s->audit($owner,'profile_photo_removed','user',$owner);}); }
    public function own(int $owner): ?array
    { return $this->s->one('SELECT * FROM member_photos WHERE user_id=?',[$owner]); }
    public function decorate(array $owner): array
    { $owner['avatar_version']=$this->s->one('SELECT version FROM member_photos WHERE user_id=?',[$owner['id']])['version']??null;return $owner; }
    public function published(string $code): ?array
    {
        $owner=(new Affiliate($this->s))->owner($code);
        if(!$owner || !$owner['affiliate_public'] || !(new Membership($this->s))->status((int)$owner['id'])['allowed']){return null;}
        return $this->own((int)$owner['id']);
    }
}
