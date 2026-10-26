<?php
namespace App\Libraries\Platform;

use RuntimeException;

class Blog
{
    public const EXPERIENCE_CATEGORY = 'ประสบการณ์ผู้ใช้สินค้า';
    public function __construct(private Store $s) {}

    public function latest(): array
    {
        return $this->s->all("SELECT id,slug,title,excerpt,category,cover_image,published_at FROM blog WHERE status='published' AND published_at<=? ORDER BY published_at DESC,id DESC LIMIT 3", [Store::now()]);
    }

    public function published(string $query='',string $category=''): array
    {
        return $this->s->all("SELECT id,slug,title,excerpt,category,cover_image,published_at FROM blog WHERE status='published' AND published_at<=? AND (title LIKE ? OR excerpt LIKE ? OR body LIKE ?) AND (?='' OR category=?) ORDER BY published_at DESC,id DESC",
            [Store::now(),'%'.$query.'%','%'.$query.'%','%'.$query.'%',$category,$category]);
    }

    public function article(string $slug): ?array
    {
        return $this->s->one("SELECT b.*,u.name author FROM blog b LEFT JOIN users u ON u.id=b.author_id WHERE b.slug=? AND b.status='published' AND b.published_at<=?",[$slug,Store::now()]);
    }

    public function save(int $id,array $input,array $actor): int
    {
        if(!Access::can($actor,'blog')){throw new RuntimeException('ไม่มีสิทธิ์จัดการบทความ');}
        $data=[];
        foreach(['title'=>180,'slug'=>120,'excerpt'=>500,'category'=>100,'body'=>16000,'cover_image'=>200,'status'=>12] as $key=>$max) {
            if(isset($input[$key]) && !is_string($input[$key])){throw new RuntimeException('รูปแบบข้อมูลบทความไม่ถูกต้อง');}
            $value=trim($input[$key]??'');
            if(mb_strlen($value)>$max){throw new RuntimeException('ข้อมูลบทความยาวเกินกำหนด');}
            $data[$key]=$value;
        }
        if(mb_strlen($data['title'])<3 || mb_strlen($data['body'])<30 || $data['excerpt']===''){throw new RuntimeException('กรอกชื่อบทความ คำโปรย และเนื้อหาอย่างน้อย 30 ตัวอักษร');}
        if($data['slug']===''){$data['slug']='article-'.bin2hex(random_bytes(8));}
        if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$data['slug'])){throw new RuntimeException('ลิงก์บทความใช้ a-z, 0-9 และขีดกลางเท่านั้น');}
        if(!in_array($data['status'],['draft','published','archived'],true)){throw new RuntimeException('สถานะบทความไม่ถูกต้อง');}
        if($data['cover_image']!=='' && (!preg_match('~^(?:mirror/assets/[a-f0-9]{24}|platform/uploads/[a-f0-9]{32}|platform/assets/rice-hero)\.(?:png|jpg|jpeg|webp|gif)$~D',$data['cover_image']) || !is_file(FCPATH.$data['cover_image']))) {throw new RuntimeException('ใช้รูปที่อัปโหลดหรือรูปภายในระบบเท่านั้น');}
        return $this->s->transaction(function() use($id,$data,$actor) {
            $old=$id?$this->s->one('SELECT * FROM blog WHERE id=?',[$id]):null;
            if($id && !$old){throw new RuntimeException('ไม่พบบทความ');}
            if($this->s->one('SELECT id FROM blog WHERE slug=? AND id<>?',[$data['slug'],$id])){throw new RuntimeException('ลิงก์บทความนี้ถูกใช้แล้ว');}
            $data['updated_at']=Store::now();
            $data['published_at']=$old['published_at']??($data['status']==='published'?Store::now():null);
            if($id){
                $this->s->exec('UPDATE blog SET '.implode(',',array_map(fn($key)=>$key.'=?',array_keys($data))).' WHERE id=?',[...array_values($data),$id]);
            } else {
                $id=$this->s->insert('blog',$data+['author_id'=>$actor['id'],'created_at'=>Store::now()]);
            }
            $this->s->audit($actor['id'],$old?'article_updated':'article_created','blog',$id,$data['status']);
            return $id;
        });
    }

    public function archive(int $id,array $actor): void
    {
        if(!Access::can($actor,'blog')){throw new RuntimeException('ไม่มีสิทธิ์');}
        $this->s->transaction(function() use($id,$actor) {
            if(!$this->s->exec("UPDATE blog SET status='archived',updated_at=? WHERE id=?",[Store::now(),$id])){throw new RuntimeException('ไม่พบบทความ');}
            $this->s->audit($actor['id'],'article_archived','blog',$id);
        });
    }
}
