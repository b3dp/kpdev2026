<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url>
<loc>{{ route('bagis.index') }}</loc>
<lastmod>{{ now()->toAtomString() }}</lastmod>
<changefreq>daily</changefreq>
<priority>0.9</priority>
</url>
@foreach($bagislar as $bagis)
<url>
<loc>{{ route('bagis.show', $bagis->slug) }}</loc>
<lastmod>{{ optional($bagis->updated_at)->toAtomString() ?? now()->toAtomString() }}</lastmod>
<changefreq>weekly</changefreq>
<priority>0.8</priority>
</url>
@endforeach
</urlset>
