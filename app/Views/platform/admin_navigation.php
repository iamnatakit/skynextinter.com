<?php
use App\Libraries\Platform\Access;
$groupModules=['affiliates','affiliate_profile','users','menus','downline_users','external_users'];
$showGroup=false;
foreach($groupModules as $key){if(Access::can($user,$key,$store??null)){$showGroup=true;break;}}
?>
<?php foreach(Access::MODULES as $key=>$label): ?>
    <?php if(in_array($key,[...$groupModules,'user_management'],true) || !Access::can($user,$key,$store??null)){continue;} ?>
    <a class="<?= $currentModule===$key?'selected':'' ?>" href="<?= site_url('admin/'.$key) ?>"><span class="nav-dot"></span><?= esc($label) ?></a>
<?php endforeach ?>
<?php if($showGroup): ?>
<details class="sn-admin-user-group" <?= in_array($currentModule,[...$groupModules,'user_management'],true)?'open':'' ?>>
    <summary>จัดการผู้ใช้</summary>
    <?php if(Access::can($user,'user_management',$store??null)): ?><a class="<?= $currentModule==='user_management'?'selected':'' ?>" href="<?= site_url('admin/user_management') ?>">ภาพรวม / ค่า Platform</a><?php endif ?>
    <?php foreach($groupModules as $key): if(!Access::can($user,$key,$store??null)){continue;} ?>
        <a class="<?= $currentModule===$key?'selected':'' ?>" href="<?= site_url('admin/'.$key) ?>"><?= esc(Access::MODULES[$key]) ?></a>
    <?php endforeach ?>
</details>
<?php endif ?>
<a class="<?= $currentModule==='billing'?'selected':'' ?>" href="<?= site_url('platform/billing') ?>"><span class="nav-dot"></span>สิทธิ์ใช้งาน Platform</a>
