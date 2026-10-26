<section id="sn-home-articles" aria-label="บทความล่าสุด">
    <?php if ($articles): ?>
    <div class="sn-home-article-grid">
        <?php foreach ($articles as $article): ?>
        <article class="sn-home-article">
            <a class="sn-home-article-cover" href="<?= site_url('blog/' . $article['slug']) ?>" tabindex="-1" aria-hidden="true">
                <?php if ($article['cover_image']): ?>
                <img src="<?= base_url($article['cover_image']) ?>" alt="" loading="lazy" width="640" height="420">
                <?php else: ?>
                <span><?= esc($article['category'] ?: 'เกษตรน่ารู้') ?></span>
                <?php endif ?>
            </a>
            <h3><a href="<?= site_url('blog/' . $article['slug']) ?>"><?= esc($article['title']) ?></a></h3>
            <a class="sn-home-article-read" href="<?= site_url('blog/' . $article['slug']) ?>">อ่านต่อ<span class="sn-home-article-sr"> <?= esc($article['title']) ?></span></a>
        </article>
        <?php endforeach ?>
    </div>
    <?php else: ?>
    <p class="sn-home-articles-empty">ยังไม่มีบทความที่เผยแพร่</p>
    <?php endif ?>
</section>
<style>
#sn-home-articles{padding:12px 10px 24px;clear:both;scroll-margin-top:150px}
#sn-home-articles .sn-home-article-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}
#sn-home-articles .sn-home-article{display:flex;flex-direction:column;align-items:center;padding:10px 10px 30px;background:#efeadd;border-radius:24px;box-shadow:0 3px 6px #0002;min-width:0}
#sn-home-articles .sn-home-article-cover{display:flex;align-items:center;justify-content:center;aspect-ratio:1.52;width:100%;overflow:hidden;border-radius:18px;background:linear-gradient(135deg,#dbe9c9,#a9c894);color:#245231;font-size:24px}
#sn-home-articles .sn-home-article-cover img{display:block;width:100%;height:100%;object-fit:cover}
#sn-home-articles h3{font-size:18px;line-height:1.6;font-weight:500;text-align:center;margin:16px 8px 20px}
#sn-home-articles h3 a{color:#243c4b;text-decoration:none}
#sn-home-articles .sn-home-article-read{margin-top:auto;display:inline-block;padding:7px 16px;border-radius:4px;background:#2b7c30;color:#fff;font-size:14px;font-weight:600;text-decoration:none}
#sn-home-articles a:focus-visible{outline:3px solid #286b32;outline-offset:4px}
#sn-home-articles .sn-home-article-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);white-space:nowrap}
#sn-home-articles .sn-home-articles-empty{text-align:center;padding:30px}
@media(max-width:767px){#sn-home-articles .sn-home-article-grid{grid-template-columns:1fr;gap:20px}#sn-home-articles .sn-home-article{padding-bottom:24px}}
</style>
