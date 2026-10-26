<?php
$context=session('affiliate_context');
$referrerStore=new \App\Libraries\Platform\Store();
$referrer=(new \App\Libraries\Platform\Affiliate($referrerStore))->resolve(
    is_array($context)?$context:null,
    (string)service('request')->getCookie('sn_affiliate')
);
if($referrer && $referrer['affiliate_public'] && (new \App\Libraries\Platform\Membership($referrerStore))->status((int)$referrer['id'])['allowed']):
?>
<section class="sn-footer-referrer" aria-label="ข้อมูลผู้แนะนำ">
    <?= view('platform/profile_avatar',['owner'=>$referrer]) ?>
    <div class="sn-footer-referrer-copy">
        <span>ผู้แนะนำของคุณ</span>
        <h3><?= esc($referrer['public_name']) ?></h3>
        <?php if($referrer['affiliate_headline']): ?><p><?= esc($referrer['affiliate_headline']) ?></p><?php endif ?>
        <small>รหัสแนะนำ <?= esc($referrer['referral_code']) ?></small>
    </div>
    <div class="sn-footer-referrer-actions">
        <a class="sn-footer-referrer-profile" href="<?= site_url('p/'.$referrer['referral_code']) ?>">ดูข้อมูลผู้แนะนำ</a>
        <?php if($referrer['line_url']): ?><a class="sn-footer-referrer-line" href="<?= site_url('contact-us/'.$referrer['referral_code']) ?>">ติดต่อทาง LINE</a><?php endif ?>
    </div>
</section>
<?php endif ?>
