<!doctype html>
<html lang="id">
<body style="font-family: Arial, sans-serif; color:#1f2937; line-height:1.5;">
    <h2 style="margin:0 0 12px;">{{ $heading }}</h2>

    <table cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%; font-size:14px;">
        <thead>
            <tr style="background:#f3f4f6; text-align:left;">
                <th>Varian</th>
                <th>SKU</th>
                <th>Stok</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td>{{ $r['name'] }}</td>
                    <td>{{ $r['sku'] }}</td>
                    <td>{{ $r['stock'] }} {{ $r['unit'] }}</td>
                    <td>{{ $r['note'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($footer)
        <p style="margin-top:16px; color:#6b7280; font-size:12px;">{{ $footer }}</p>
    @endif
</body>
</html>