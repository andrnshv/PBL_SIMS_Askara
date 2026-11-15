
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Pergerakan Stok - SIMS Askara</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 24px 26px 28px 26px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #334155;
            line-height: 1.45;
        }

        /* HEADER DOKUMEN */
        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .header td {
            vertical-align: middle;
            padding: 13px 15px;
        }

        .header-left {
            width: 60%;
            background: #0A1E45;
            color: #ffffff;
        }

        .header-right {
            width: 40%;
            background: #163663;
            color: #ffffff;
            text-align: right;
        }

        .brand {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .brand-caption {
            color: #cbd5e1;
            font-size: 8px;
            margin-top: 3px;
        }

        .document-label {
            color: #bfdbfe;
            font-size: 8px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .document-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 4px;
        }

        .document-subtitle {
            color: #cbd5e1;
            font-size: 8px;
            margin-top: 2px;
        }

        /* INFORMASI LAPORAN */
        .section-heading {
            color: #0A1E45;
            font-size: 10px;
            font-weight: bold;
            margin: 15px 0 7px;
        }

        .information {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .information td {
            border: 1px solid #dbe3ef;
            padding: 8px 10px;
            vertical-align: top;
        }

        .info-label {
            color: #64748b;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .info-value {
            color: #0A1E45;
            font-size: 9px;
            font-weight: bold;
            margin-top: 3px;
        }

        .filter-note {
            background: #f1f5f9;
            border-left: 3px solid #4C8DF6;
            padding: 7px 10px;
            color: #475569;
            font-size: 8px;
            margin-bottom: 13px;
        }

        /* RINGKASAN */
        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px 0;
            margin: 4px -7px 15px;
        }

        .summary td {
            width: 25%;
            padding: 10px 12px;
            border: 1px solid #dbe3ef;
            background: #f8fafc;
            vertical-align: middle;
        }

        .summary-total {
            border-top: 3px solid #0A1E45 !important;
        }

        .summary-in {
            border-top: 3px solid #15966b !important;
        }

        .summary-out {
            border-top: 3px solid #dc5555 !important;
        }

        .summary-adjustment {
            border-top: 3px solid #d69b24 !important;
        }

        .summary-label {
            color: #64748b;
            font-size: 8px;
            font-weight: bold;
        }

        .summary-value {
            color: #0A1E45;
            font-size: 19px;
            font-weight: bold;
            margin-top: 4px;
        }

        .summary-description {
            color: #94a3b8;
            font-size: 7px;
            margin-top: 2px;
        }

        /* TABEL DATA */
        .table-heading {
            width: 100%;
            border-bottom: 2px solid #0A1E45;
            margin: 12px 0 7px;
            padding-bottom: 6px;
        }

        .table-heading td {
            vertical-align: bottom;
        }

        .table-title {
            color: #0A1E45;
            font-size: 11px;
            font-weight: bold;
        }

        .table-description {
            color: #64748b;
            font-size: 8px;
            text-align: right;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table th {
            background: #0A1E45;
            color: #ffffff;
            font-size: 7px;
            font-weight: bold;
            text-align: left;
            padding: 8px 5px;
            border: 1px solid #0A1E45;
        }

        .data-table td {
            padding: 7px 5px;
            border: 1px solid #e2e8f0;
            font-size: 7.5px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f5f8fc;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .product-name {
            color: #0A1E45;
            font-weight: bold;
        }

        .variant-name {
            color: #64748b;
            font-size: 7px;
        }

        .sku {
            color: #475569;
            font-size: 7px;
        }

        .movement-badge {
            display: inline-block;
            padding: 3px 5px;
            font-size: 7px;
            font-weight: bold;
        }

        .badge-in {
            background: #dcfce7;
            color: #166534;
        }

        .badge-out {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-adjustment {
            background: #fef3c7;
            color: #92400e;
        }

        .quantity-positive {
            color: #15803d;
            font-weight: bold;
        }

        .quantity-negative {
            color: #dc2626;
            font-weight: bold;
        }

        .quantity-neutral {
            color: #475569;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 22px !important;
            color: #64748b;
        }

        /* CATATAN DAN FOOTER */
        .report-note {
            margin-top: 9px;
            padding: 7px 9px;
            background: #f8fafc;
            color: #64748b;
            font-size: 7.5px;
        }

        .footer {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            margin-top: 20px;
            padding-top: 8px;
            color: #64748b;
            font-size: 7.5px;
        }

        .footer td {
            vertical-align: middle;
        }

        .footer-right {
            text-align: right;
            color: #0A1E45;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- IDENTITAS DOKUMEN -->
    <table class="header">
        <tr>
            <td class="header-left">
                <div class="brand">SIMS ASKARA</div>
                <div class="brand-caption">
                    Sistem Informasi Manajemen Persediaan
                </div>
            </td>

            <td class="header-right">
                <div class="document-label">INVENTORY REPORT</div>
                <div class="document-title">
                    LAPORAN PERGERAKAN STOK
                </div>
                <div class="document-subtitle">
                    Dokumen Riwayat Perubahan Persediaan
                </div>
            </td>
        </tr>
    </table>

    <!-- INFORMASI LAPORAN -->
    <div class="section-heading">INFORMASI LAPORAN</div>

    <table class="information">
        <tr>
            <td width="36%">
                <div class="info-label">Periode Laporan</div>
                <div class="info-value">
                    {{ $start ? \Carbon\Carbon::parse($start)->format('d/m/Y') : 'Semua tanggal' }}
                    s.d.
                    {{ $end ? \Carbon\Carbon::parse($end)->format('d/m/Y') : 'Sekarang' }}
                </div>
            </td>

            <td width="32%">
                <div class="info-label">Tanggal dan Waktu Cetak</div>
                <div class="info-value">{{ $generatedAt }}</div>
            </td>

            <td width="32%">
                <div class="info-label">Dicetak Oleh</div>
                <div class="info-value">{{ $generatedBy }}</div>
            </td>
        </tr>
    </table>

    @if ($variantInfo || $q || $type)
        <div class="filter-note">
            <strong>Filter yang diterapkan:</strong>
            @if ($variantInfo)
                Varian: {{ $variantInfo }};
            @endif
            @if ($q)
                Pencarian: {{ $q }};
            @endif
            @if ($type)
                Jenis pergerakan: {{ ucfirst($type) }};
            @endif
        </div>
    @endif

    <!-- RINGKASAN -->
    <div class="section-heading">RINGKASAN PERGERAKAN STOK</div>

    <table class="summary">
        <tr>
            <td class="summary-total">
                <div class="summary-label">TOTAL MOVEMENT</div>
                <div class="summary-value">
                    {{ number_format($totalRows, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Seluruh catatan sesuai filter
                </div>
            </td>

            <td class="summary-in">
                <div class="summary-label">MOVEMENT MASUK (+)</div>
                <div class="summary-value">
                    {{ number_format($incomingCount, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Jumlah catatan perubahan positif
                </div>
            </td>

            <td class="summary-out">
                <div class="summary-label">MOVEMENT KELUAR (-)</div>
                <div class="summary-value">
                    {{ number_format($outgoingCount, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Jumlah catatan perubahan negatif
                </div>
            </td>

            <td class="summary-adjustment">
                <div class="summary-label">PENYESUAIAN STOK</div>
                <div class="summary-value">
                    {{ number_format($adjustmentCount, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Jumlah catatan adjustment
                </div>
            </td>
        </tr>
    </table>

    <!-- DETAIL MOVEMENT -->
    <table class="table-heading">
        <tr>
            <td>
                <div class="table-title">DETAIL PERGERAKAN STOK</div>
            </td>
            <td class="table-description">
                {{ number_format($totalRows, 0, ',', '.') }} catatan ditemukan
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">NO.</th>
                <th width="12%">TANGGAL</th>
                <th width="18%">PRODUK / VARIAN</th>
                <th width="9%">SKU</th>
                <th width="10%">JENIS</th>
                <th width="10%" class="text-right">PERUBAHAN</th>
                <th width="9%" class="text-right">STOK AWAL</th>
                <th width="9%" class="text-right">STOK AKHIR</th>
                <th width="10%">REFERENSI</th>
                <th width="9%">PETUGAS</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($movements as $index => $m)
                <tr>
                    <td class="text-center">
                        {{ $index + 1 }}
                    </td>

                    <td>
                        {{ $m['datetime']->format('d/m/Y') }}<br>
                        <span class="variant-name">
                            {{ $m['datetime']->format('H:i') }}
                        </span>
                    </td>

                    <td>
                        <div class="product-name">{{ $m['name'] }}</div>
                        <div class="variant-name">{{ $m['variant'] }}</div>
                    </td>

                    <td class="sku">
                        {{ $m['sku'] ?: '-' }}
                    </td>

                    <td>
                        @if ($m['type'] === 'masuk')
                            <span class="movement-badge badge-in">MASUK</span>
                        @elseif ($m['type'] === 'keluar')
                            <span class="movement-badge badge-out">KELUAR</span>
                        @else
                            <span class="movement-badge badge-adjustment">
                                PENYESUAIAN
                            </span>
                        @endif
                    </td>

                    <td class="text-right">
                        <span class="{{ $m['delta'] > 0 ? 'quantity-positive' : ($m['delta'] < 0 ? 'quantity-negative' : 'quantity-neutral') }}">
                            {{ $m['delta'] > 0 ? '+' : '' }}{{ $m['delta'] }}
                        </span>
                        <br>
                        <span class="variant-name">{{ $m['unit'] }}</span>
                    </td>

                    <td class="text-right">
                        {{ number_format($m['before'], 0, ',', '.') }}
                        <br>
                        <span class="variant-name">{{ $m['unit'] }}</span>
                    </td>

                    <td class="text-right">
                        <strong>
                            {{ number_format($m['after'], 0, ',', '.') }}
                        </strong>
                        <br>
                        <span class="variant-name">{{ $m['unit'] }}</span>
                    </td>

                    <td>{{ $m['ref'] }}</td>

                    <td>{{ $m['officer'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="empty">
                        Tidak terdapat data pergerakan stok
                        sesuai filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="report-note">
        <strong>Catatan:</strong>
        Laporan menampilkan maksimal 50 movement terbaru.
        Ringkasan dihitung berdasarkan seluruh data yang memenuhi filter.
    </div>

    <!-- FOOTER -->
    <table class="footer">
        <tr>
            <td width="60%">
                SIMS Askara | Sistem Informasi Manajemen Persediaan
            </td>
            <td width="40%" class="footer-right">
                DOKUMEN LAPORAN PERSEDIAAN
            </td>
        </tr>
    </table>

</body>
</html>
