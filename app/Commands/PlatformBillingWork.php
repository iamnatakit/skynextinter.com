<?php
namespace App\Commands;
use App\Libraries\Platform\{Store,Membership,PlatformBilling,PlatformMailer};
use CodeIgniter\CLI\{BaseCommand,CLI};
class PlatformBillingWork extends BaseCommand
{
    protected $group='Platform';
    protected $name='platform:billing-work';
    protected $description='Queue expired Platform notices, verify renewal slips and deliver queued email once.';
    public function run(array $params)
    {
        $lock=fopen(WRITEPATH.'platform-billing-worker.lock','c');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) { return; }
        try {
            $s=new Store();
            CLI::write(json_encode(['queued_expiration_emails'=>(new Membership($s))->queueExpirations(),
                'verified_renewals'=>(new PlatformBilling($s))->work(),'emails_sent'=>(new PlatformMailer($s))->work()],JSON_UNESCAPED_UNICODE));
        } finally { flock($lock,LOCK_UN);fclose($lock); }
    }
}
