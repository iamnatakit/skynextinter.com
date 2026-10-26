<?php
$context=session('affiliate_context');$referrerStore=new \App\Libraries\Platform\Store();$network=new \App\Libraries\Platform\Affiliate($referrerStore);
$referrer=$network->resolve(is_array($context)?$context:null,(string)service('request')->getCookie('sn_affiliate'));
if($referrer && $referrer['affiliate_public'] && !empty($referrer['avatar_version']) && (new \App\Libraries\Platform\Membership($referrerStore))->status((int)$referrer['id'])['allowed']): ?>
<aside class="sn-referrer-card" aria-label="ผู้แนะนำของคุณ"><?= view('platform/profile_avatar',['owner'=>$referrer]) ?><span>ผู้แนะนำของคุณ<br><strong><?= esc($referrer['public_name']) ?></strong></span><a class="sn-referrer-link" href="<?= site_url('p/'.$referrer['referral_code']) ?>">รู้จักผู้แนะนำ</a></aside>
<?php endif ?>
