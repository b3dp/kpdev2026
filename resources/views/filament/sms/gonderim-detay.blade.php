<div class="space-y-3 text-sm">
    @if($kayit->alicilar->isEmpty())
        <p>Alıcı kaydı bulunamadı.</p>
    @else
        <div class="max-h-96 overflow-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-700">Telefon</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-700">Durum</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-700">Paket ID</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-700">Hata Kodu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($kayit->alicilar as $alici)
                        <tr>
                            <td class="px-3 py-2">{{ $alici->telefon }}</td>
                            <td class="px-3 py-2">{{ ucfirst($alici->durum) }}</td>
                            <td class="px-3 py-2">{{ $alici->hermes_packet_id ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $alici->hata_kodu ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
