<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url>
    <loc>https://www.domain.com/</loc>
</url>
<?php endforeach; ?>
<?php foreach($products as $product): ?>
<url>
    <loc>https://www.domain.com/shop/<?php echo $product->slug; ?></loc>
</url>
<?php endforeach; ?>
<?php foreach($categories as $category): ?>
<url>
    <loc>https://www.domain.com/shop/category/<?php echo $category->slug; ?></loc>
</url>
<?php endforeach; ?>
</urlset>
