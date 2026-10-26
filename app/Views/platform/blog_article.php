<article class="sn-blog sn-article">
    <nav class="sn-blog-breadcrumb" aria-label="เส้นทางหน้าเว็บ">
        <a href="<?= site_url() ?>">หน้าแรก</a>
        <span>/</span>
        <a href="<?= site_url($article['category']===\App\Libraries\Platform\Blog::EXPERIENCE_CATEGORY?'about/product-experiences':'blog') ?>"><?= $article['category']===\App\Libraries\Platform\Blog::EXPERIENCE_CATEGORY?'ประสบการณ์ผู้ใช้สินค้า':'บทความ' ?></a>
        <span>/</span>
        <span><?= esc($article['category']?:'เกษตรน่ารู้') ?></span>
    </nav>

    <div class="sn-blog-meta">
        <a href="<?= site_url('blog').'?'.http_build_query(['category'=>$article['category']]) ?>"><?= esc($article['category']?:'เกษตรน่ารู้') ?></a>
        <time datetime="<?= esc(str_replace(' ','T',$article['published_at']).'Z','attr') ?>"><?= p_date($article['published_at']) ?></time>
        <span>โดย <?= esc($article['author']?:'ทีมงาน SKYNEXT INTER') ?></span>
    </div>

    <p class="sn-article-deck"><?= esc($article['excerpt']) ?></p>

    <?php if($article['cover_image']): ?>
        <img class="sn-article-image" src="<?= base_url($article['cover_image']) ?>" alt="<?= esc($article['title'],'attr') ?>" width="1200" height="675">
    <?php endif ?>

    <div class="sn-article-body">
        <?php foreach(preg_split('/\R\s*\R/u', $article['body']) as $block):
            $block = trim($block);
            if ($block === '') continue;

            // Heading 2
            if (str_starts_with($block, '## ')) {
                echo '<h2>' . esc(substr($block, 3)) . '</h2>';
            }
            // Heading 3
            elseif (str_starts_with($block, '### ')) {
                echo '<h3>' . esc(substr($block, 4)) . '</h3>';
            }
            // Image: ![caption](url)
            elseif (preg_match('/^!\[(.*?)\]\((.*?)\)$/u', $block, $m)) {
                $caption = trim($m[1]);
                $src = trim($m[2]);
                $imgUrl = str_starts_with($src, 'http') ? $src : base_url($src);
                echo '<figure class="sn-article-figure">';
                echo '<img src="' . esc($imgUrl, 'attr') . '" alt="' . esc($caption, 'attr') . '" loading="lazy">';
                if ($caption !== '') {
                    echo '<figcaption>' . esc($caption) . '</figcaption>';
                }
                echo '</figure>';
            }
            // Callout / Blockquote: lines starting with >
            elseif (str_starts_with($block, '>')) {
                $lines = array_map(fn($l) => preg_replace('/^>\s*/', '', $l), explode("\n", $block));
                $text = implode("\n", $lines);
                // Simple inline bold replacement
                $html = preg_replace('/\*\*(.*?)\*\*/u', '<strong>$1</strong>', esc($text));
                echo '<div class="sn-article-callout">' . nl2br($html) . '</div>';
            }
            // Bullet list: lines starting with - or *
            elseif (preg_match('/^[*-]\s+/m', $block)) {
                echo '<ul>';
                foreach (explode("\n", $block) as $li) {
                    $li = trim($li);
                    if ($li === '') continue;
                    $cleanLi = preg_replace('/^[*-]\s+/u', '', $li);
                    $html = preg_replace('/\*\*(.*?)\*\*/u', '<strong>$1</strong>', esc($cleanLi));
                    echo '<li>' . $html . '</li>';
                }
                echo '</ul>';
            }
            // Table: contains |---
            elseif (str_contains($block, '|---')) {
                $rows = array_filter(array_map('trim', explode("\n", $block)));
                echo '<div class="sn-article-table-wrap"><table class="sn-article-table">';
                $isHeader = true;
                foreach ($rows as $row) {
                    if (str_contains($row, '|---')) {
                        $isHeader = false;
                        continue;
                    }
                    $cols = array_values(array_filter(array_map('trim', explode('|', $row)), fn($c) => $c !== ''));
                    echo '<tr>';
                    foreach ($cols as $col) {
                        $tag = $isHeader ? 'th' : 'td';
                        $html = preg_replace('/\*\*(.*?)\*\*/u', '<strong>$1</strong>', esc($col));
                        echo "<{$tag}>{$html}</{$tag}>";
                    }
                    echo '</tr>';
                }
                echo '</table></div>';
            }
            // Regular paragraph
            else {
                $html = preg_replace('/\*\*(.*?)\*\*/u', '<strong>$1</strong>', esc($block));
                echo '<p>' . nl2br($html) . '</p>';
            }
        endforeach; ?>
    </div>

    <div class="sn-article-footer">
        <a class="button secondary" href="<?= site_url('blog') ?>">← กลับหน้ารวมบทความ</a>
        <a href="<?= site_url('shop') ?>">เลือกดูสินค้าการเกษตร →</a>
    </div>

    <?php if($related): ?>
        <section class="sn-blog-related">
            <h2>อ่านเรื่องอื่นต่อ</h2>
            <?php foreach($related as $item): ?>
                <a href="<?= site_url('blog/'.$item['slug']) ?>">
                    <small><?= esc($item['category']) ?></small>
                    <strong><?= esc($item['title']) ?></strong>
                    <span aria-hidden="true">↗</span>
                </a>
            <?php endforeach ?>
        </section>
    <?php endif ?>
</article>
