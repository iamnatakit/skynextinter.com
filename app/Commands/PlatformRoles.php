<?php
namespace App\Commands;

use App\Libraries\Platform\{Store,Membership};
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PlatformRoles extends BaseCommand
{
    protected $group='Platform';
    protected $name='platform:roles';
    protected $description='Expand team role constraint and reconcile already confirmed purchases/annual payments.';
    public function run(array $params)
    {
        $s=new Store();$s->migrateTeamRoles();$s->migrateProductPV();$m=new Membership($s);
        $s->transaction(function() use($s,$m) {
            foreach($s->all("SELECT DISTINCT i.user_id FROM platform_invoices i JOIN platform_transfers t ON t.invoice_id=i.id WHERE i.status='paid'") as $row){$m->qualifyAnnual((int)$row['user_id']);}
            foreach($s->all("SELECT id FROM orders WHERE status IN ('paid','processing','shipped','completed')") as $row){$m->qualifyPurchase((int)$row['id']);}
        });
        CLI::write('Team roles ready. Existing confirmed payments reconciled; Admin permissions unchanged.');
    }
}
