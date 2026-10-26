<section id="sn-home-products" class="sn-products" aria-label="สินค้าแนะนำ">
    <header class="sn-products-heading"><h2>สินค้าแนะนำ</h2></header>
    <div class="sn-product-grid">
        <?php foreach ($products as $product): ?>
            <?= view('platform/product_card', ['product' => $product]) ?>
        <?php endforeach ?>
    </div>
    <?php if (!$products): ?><p>ยังไม่มีสินค้าแนะนำ</p><?php endif ?>
    <a class="sn-product-all" href="<?= site_url('shop') ?>">ดูสินค้าทั้งหมด →</a>
</section>
