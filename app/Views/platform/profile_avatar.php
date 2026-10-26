<?php if(!empty($owner['affiliate_public']) && !empty($owner['avatar_version'])): ?>
<img class="sn-profile-photo" src="<?= site_url('member-photo/'.$owner['referral_code']).'?v='.esc($owner['avatar_version'],'attr') ?>" alt="รูปของ <?= esc($owner['public_name'],'attr') ?>" width="96" height="96">
<?php else: ?><span class="sn-profile-photo sn-profile-initial" aria-hidden="true"><?= esc(!empty($owner['affiliate_public'])?mb_substr($owner['public_name'],0,1):'S') ?></span><?php endif ?>
