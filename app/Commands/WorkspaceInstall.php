<?php
namespace App\Commands;

use App\Libraries\Platform\Store;
use CodeIgniter\CLI\{BaseCommand,CLI};

class WorkspaceInstall extends BaseCommand
{
    protected $group='Platform';
    protected $name='workspace:install';
    protected $description='Add private member workspaces and sales page permissions without importing products or creating accounts.';
    public function run(array $params)
    {
        $store=new Store();if(!$store->installed()){CLI::error('Install the base platform first.');return;}
        $store->install();CLI::write('Workspace tables and role permissions ready.');
    }
}
