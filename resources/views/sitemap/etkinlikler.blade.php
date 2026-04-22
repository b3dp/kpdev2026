<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($etkinlikler as $etkinlik)
<url>
<loc>{{ route('etkinlikler.show', $etkinlik->slug) }}</loc>
<lastmod>{{ optional($etkinlik->updated_at)->toAtomString() ?? now()->toAtomString() }}</lastmod>
<changefreq>weekly</changefreq>
<priority>0.7</priority>
</url>
@endforeach
</urlset>
