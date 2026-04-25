# SEO ve LCP Notlari

Tarih: 25 Nisan 2026
Kapsam: https://2026.kestanepazari.org.tr/kurumsal/akil-oyunlari-atolyesi (mobil PSI)

## Hedefler

- Performance: 91 -> 95+
- Accessibility: 89 -> 95+
- Best Practices: 100 korunacak
- SEO: 100 korunacak

## Oncelikli Teknik Gorevler

1. LCP gorselini preload + high priority yap
- Etki: Cok yuksek
- Sure: 20-40 dk
- Oncelik: P0
- Kabul kriteri: LCP gorsel request'i kritik isteklerde erken gelir.

2. Hero gorselde responsive srcset/sizes ve dogru boyut
- Etki: Cok yuksek
- Sure: 1-2 saat
- Oncelik: P0
- Kabul kriteri: Mobilde gereksiz buyuk gorsel inmez.

3. Galeri ve kart gorsellerine net width/height
- Etki: Yuksek
- Sure: 30-60 dk
- Oncelik: P0
- Kabul kriteri: "Image elements do not have explicit width and height" uyarisi kapanir/azalir.

4. aria-hidden icinde focusable elemanlari temizle
- Etki: Yuksek
- Sure: 30-90 dk
- Oncelik: P0
- Kabul kriteri: Ilgili accessibility hatasi kapanir.

5. Kontrast oranlarini duzelt
- Etki: Yuksek
- Sure: 1-2 saat
- Oncelik: P0
- Kabul kriteri: Kontrast uyarilari kapanir.

## Ikinci Asama

6. Kritik CSS ayrisma (above-the-fold)
- Etki: Yuksek
- Sure: 2-4 saat
- Oncelik: P1

7. Kritik olmayan JS defer/async ve kosullu yukleme
- Etki: Yuksek
- Sure: 1-3 saat
- Oncelik: P1

8. Lightbox/galeri JS lazy-init
- Etki: Orta-Yuksek
- Sure: 45-90 dk
- Oncelik: P1

9. Statik asset cache politikasi (immutable + long max-age)
- Etki: Orta-Yuksek
- Sure: 45-90 dk
- Oncelik: P1

10. Heading hiyerarsisi (h1-h2-h3)
- Etki: Orta
- Sure: 30-60 dk
- Oncelik: P1

## Temizlik ve Optimizasyon

11. Link purpose tekillestirme
- Etki: Orta
- Sure: 30-60 dk
- Oncelik: P2

12. Kullanilmayan JS temizligi ve bundle kucultme
- Etki: Orta
- Sure: 1-3 saat
- Oncelik: P2

## Uygulama Sirasi

1. P0 gorsel + LCP + width/height
2. P0 accessibility duzeltmeleri
3. P1 render-blocking ve JS optimizasyonu
4. P1 cache iyilestirmeleri
5. P2 bundle/cleanup

## Definition of Done

- Mobil Lighthouse: Performance >= 95
- Mobil Lighthouse: Accessibility >= 95
- LCP hedef bandi: <= 2.5 sn
- CLS <= 0.01 korunacak
- Kritik accessibility hatalari kapanacak
- Regresyon testleri gececek
