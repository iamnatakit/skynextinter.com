<?php
namespace App\Commands;
use App\Libraries\Platform\{Store,AnnualWebsitePackage};
use CodeIgniter\CLI\{BaseCommand,CLI};
class AnnualWebsiteInstall extends BaseCommand
{
    protected $group='Platform';
    protected $name='platform:annual-website';
    protected $description='Add the annual 990-baht website service without replacing existing products.';
    public function run(array $params)
    {
        $s=new Store();if(!$s->installed()){CLI::error('Install the base platform first.');return;}
        CLI::write('Annual website product ID: '.AnnualWebsitePackage::add($s));
    }
}
