<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($sayfalar as $sayfa)
<url>
<loc>{{ url('/kurumsal/' . $sayfa['slug']) }}</loc>
<lastmod>{{ optional($sayfa['updated_at'])->toAtomString() ?? now()->toAtomString() }}</lastmod>
<changefreq>weekly</changefreq>
<priority>0.7</priority>
</url>
@endforeach
</urlset>
