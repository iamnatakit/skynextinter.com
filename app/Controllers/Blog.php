<?php
namespace App\Controllers;

use App\Libraries\Platform\Blog as Articles;
use App\Libraries\Platform\Store;
use CodeIgniter\Exceptions\PageNotFoundException;

class Blog extends Platform
{
    public function experiences()
    {
        $query=$this->request->getGet('q');
        $query=is_string($query)?mb_substr(trim($query),0,120):'';
        return $this->page('blog_index',[
            'title'=>Articles::EXPERIENCE_CATEGORY,'indexable'=>true,
            'description'=>'รวมบทความและเรื่องราวประสบการณ์การใช้ผลิตภัณฑ์ของบริษัท สกายเน็กซ์ อินเตอร์ จำกัด',
            'articles'=>(new Articles($this->store))->published($query,Articles::EXPERIENCE_CATEGORY),
            'query'=>$query,'category'=>Articles::EXPERIENCE_CATEGORY,'categories'=>[],
            'experiencePage'=>true,
        ]);
    }
    public function index()
    {
        $query=$this->request->getGet('q');$category=$this->request->getGet('category');
        $query=is_string($query)?mb_substr(trim($query),0,120):'';
        $category=is_string($category)?mb_substr(trim($category),0,100):'';
        return $this->page('blog_index',['title'=>'บทความ','indexable'=>true,'description'=>'ความรู้เรื่องดิน ปุ๋ย น้ำ และการวางแผนเกษตร จาก SKYNEXT INTER',
            'articles'=>(new Articles($this->store))->published($query,$category),'query'=>$query,'category'=>$category,
            'categories'=>$this->store->all("SELECT DISTINCT category FROM blog WHERE status='published' AND published_at<=? AND category<>'' ORDER BY category",[Store::now()])]);
    }
    public function show(string $slug)
    {
        $article=(new Articles($this->store))->article($slug);
        if(!$article){throw PageNotFoundException::forPageNotFound();}
        return $this->page('blog_article',['title'=>$article['title'],'indexable'=>true,'description'=>$article['excerpt'],'article'=>$article,
            'related'=>$this->store->all("SELECT slug,title,category FROM blog WHERE status='published' AND published_at<=? AND id<>? ORDER BY published_at DESC,id DESC LIMIT 3",[Store::now(),$article['id']])]);
    }
}
