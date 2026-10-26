<?php
namespace App\Commands;
use App\Libraries\Platform\{Store,Bot,Commerce,SlipPayments};
use CodeIgniter\CLI\{BaseCommand,CLI};
class PlatformWork extends BaseCommand
{
    protected $group='Platform';
    protected $name='platform:work';
    protected $description='Process queued inbound chats and outgoing replies once (schedule every minute).';
    public function run(array $params) { $store=new Store();$result=(new Bot($store))->work();$result['verified_slips']=(new SlipPayments($store))->work();$result['expired_orders']=(new Commerce($store))->expireUnpaid();$store->exec('DELETE FROM referral_tokens WHERE expires<?',[time()]);$store->exec('DELETE FROM rate_limits WHERE expires<?',[time()]);CLI::write(json_encode($result,JSON_UNESCAPED_UNICODE)); }
}
