<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($haberler as $haber)
<url>
<loc>{{ route('haberler.show', $haber->slug) }}</loc>
<lastmod>{{ optional($haber->updated_at)->toAtomString() ?? now()->toAtomString() }}</lastmod>
<changefreq>weekly</changefreq>
<priority>0.8</priority>
</url>
@endforeach
</urlset>
