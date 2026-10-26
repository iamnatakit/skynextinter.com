<?php
$headings = ['ส่วนประกอบที่สื่อผลิตภัณฑ์กล่าวถึง', 'จุดเด่นของรูปแบบสินค้า', 'วิธีรับประทาน', 'ข้อควรทราบ', 'ต่างสายงาน ก็สมัครใช้บริการได้', 'รายละเอียดแพ็กเกจ', 'จุดประสงค์การใช้งาน', 'ขั้นตอนสมัครใช้บริการ', 'เงื่อนไขบริการ', 'รายละเอียดสินค้า', 'จุดเด่นของสูตร', 'จุดเด่น', 'จุดเด่นที่ระบุในสื่อผลิตภัณฑ์', 'เหมาะกับใคร', 'เหมาะสำหรับ', 'แนวทางเลือกใช้', 'วิธีใช้', 'การเก็บรักษา', 'การเก็บรักษาและข้อควรระวัง', 'ข้อมูลสินค้า'];

$blocks = preg_split('/\R\s*\R/u', trim(\App\Libraries\Platform\ProductPricing::publicDescription($product['description'],!empty($pricingMember))));
?>
<div class="sn-product-copy">
<?php foreach ($blocks as $block): ?>
    <?php $lines = preg_split('/\R/u', $block); $heading = in_array($lines[0], $headings, true) ? array_shift($lines) : null; ?>
    <?php if ($heading): ?><h3><?= esc($heading) ?></h3><?php endif ?>
    <?php if (in_array($heading,['ข้อมูลสินค้า','รายละเอียดแพ็กเกจ'],true)): ?>
        <div class="sn-product-specs"><table><caption>รายละเอียดสินค้าและบริการ</caption><tbody>
        <?php foreach ($lines as $line): ?>
            <?php $pair = explode(': ', $line, 2); ?>
            <tr><th scope="row"><?= esc($pair[0]) ?></th><td><?= esc($pair[1] ?? '') ?></td></tr>
        <?php endforeach ?>
        </tbody></table></div>
    <?php elseif (isset($lines[0]) && str_starts_with($lines[0], '• ')): ?>
        <ul><?php foreach ($lines as $line): ?><li><?= esc(mb_substr($line, 2)) ?></li><?php endforeach ?></ul>
    <?php else: ?>
        <p><?= nl2br(esc(implode("\n", $lines))) ?></p>
    <?php endif ?>
<?php endforeach ?>
</div>
