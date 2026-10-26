<?php
namespace App\Commands;

use App\Libraries\Platform\{Store,Blog};
use CodeIgniter\CLI\{BaseCommand,CLI};

class BlogSeed extends BaseCommand
{
    protected $group='Platform';
    protected $name='platform:seed-blog';
    protected $description='Publish the eight original starter articles once; preserve existing edits.';
    public function run(array $params)
    {
        $store=new Store();$store->install();
        $actor=$store->one("SELECT * FROM users WHERE role='admin' AND active=1 ORDER BY id LIMIT 1");
        if(!$actor){CLI::error('Run php spark platform:install first.');return;}
        $count=0;$blog=new Blog($store);
        foreach(json_decode(file_get_contents(APPPATH.'Database/blog_seed.json'),true,512,JSON_THROW_ON_ERROR) as $article) {
            if($store->one('SELECT id FROM blog WHERE slug=?',[$article['slug']])){continue;}
            $blog->save(0,$article+['status'=>'published','cover_image'=>''],$actor);$count++;
        }
        CLI::write('Published starter articles: '.$count.'. Existing articles were preserved.');
    }
}
