<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Hasil Stock Opname - SIMS Askara</title>

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

        /* HEADER */
        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .header td {
            padding: 13px 15px;
            vertical-align: middle;
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

        /* SECTION */
        .section-heading {
            color: #0A1E45;
            font-size: 10px;
            font-weight: bold;
            margin: 15px 0 7px;
        }

        /* INFORMATION */
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
            letter-spacing: 0.4px;
        }

        .info-value {
            color: #0A1E45;
            font-size: 9px;
            font-weight: bold;
            margin-top: 3px;
        }

        .notes {
            background: #f1f5f9;
            border-left: 3px solid #4C8DF6;
            padding: 8px 10px;
            font-size: 8px;
            color: #475569;
            margin-bottom: 13px;
        }

        /* SUMMARY */
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

        .summary-match {
            border-top: 3px solid #15966b !important;
        }

        .summary-surplus {
            border-top: 3px solid #4C8DF6 !important;
        }

        .summary-shortage {
            border-top: 3px solid #dc5555 !important;
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

        /* DETAIL TABLE */
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

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table th {
            background: #0A1E45;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: bold;
            text-align: left;
            padding: 8px 6px;
            border: 1px solid #0A1E45;
        }

        .data-table td {
            padding: 8px 6px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            vertical-align: middle;
            word-wrap: break-word;
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

        .difference-positive {
            color: #15803d;
            font-weight: bold;
        }

        .difference-negative {
            color: #dc2626;
            font-weight: bold;
        }

        .difference-zero {
            color: #64748b;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 22px !important;
            color: #64748b;
        }

        /* FOOTER */
        .report-note {
            margin-top: 9px;
            padding: 8px 10px;
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

    <!-- HEADER DOKUMEN -->
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
                    LAPORAN HASIL STOCK OPNAME
                </div>
                <div class="document-subtitle">
                    Dokumen Pemeriksaan Fisik Persediaan
                </div>
            </td>
        </tr>
    </table>

    <!-- INFORMASI OPNAME -->
    <div class="section-heading">INFORMASI STOCK OPNAME</div>

    <table class="information">
        <tr>
            <td width="25%">
                <div class="info-label">Nomor Opname</div>
                <div class="info-value">{{ $opname->opname_no }}</div>
            </td>

            <td width="25%">
                <div class="info-label">Tanggal Opname</div>
                <div class="info-value">
                    {{ \Carbon\Carbon::parse($opname->opname_date)->format('d/m/Y') }}
                </div>
            </td>

            <td width="25%">
                <div class="info-label">Gudang</div>
                <div class="info-value">{{ $warehouse }}</div>
            </td>

            <td width="25%">
                <div class="info-label">Status</div>
                <div class="info-value">SELESAI</div>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <div class="info-label">Petugas Pemeriksa</div>
                <div class="info-value">{{ $opname->officer }}</div>
            </td>

            <td colspan="2">
                <div class="info-label">Waktu Penyelesaian</div>
                <div class="info-value">
                    {{ $opname->completed_at
                        ? \Carbon\Carbon::parse($opname->completed_at)->format('d/m/Y H:i')
                        : '-' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- CATATAN -->
    <div class="notes">
        <strong>Catatan Opname:</strong>
        {{ $opname->notes ?: 'Tidak ada catatan.' }}
    </div>

    <!-- RINGKASAN -->
    <div class="section-heading">RINGKASAN HASIL PEMERIKSAAN</div>

    <table class="summary">
        <tr>
            <td class="summary-total">
                <div class="summary-label">TOTAL VARIAN</div>
                <div class="summary-value">
                    {{ number_format($totalVariants, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Varian yang diperiksa
                </div>
            </td>

            <td class="summary-match">
                <div class="summary-label">STOK SESUAI</div>
                <div class="summary-value">
                    {{ number_format($matchedCount, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Tidak terdapat selisih
                </div>
            </td>

            <td class="summary-surplus">
                <div class="summary-label">STOK LEBIH</div>
                <div class="summary-value">
                    {{ number_format($surplusCount, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Stok fisik melebihi sistem
                </div>
            </td>

            <td class="summary-shortage">
                <div class="summary-label">STOK KURANG</div>
                <div class="summary-value">
                    {{ number_format($shortageCount, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Stok fisik di bawah sistem
                </div>
            </td>
        </tr>
    </table>

    <!-- DETAIL PEMERIKSAAN -->
    <table class="table-heading">
        <tr>
            <td>
                <div class="table-title">DETAIL HASIL PEMERIKSAAN</div>
            </td>

            <td class="table-description">
                {{ number_format($totalVariants, 0, ',', '.') }} varian diperiksa
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">NO.</th>
                <th width="26%">PRODUK / VARIAN</th>
                <th width="14%">SKU</th>
                <th width="13%" class="text-right">STOK SISTEM</th>
                <th width="13%" class="text-right">HITUNG FISIK</th>
                <th width="12%" class="text-right">SELISIH</th>
                <th width="18%">CATATAN</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($items as $index => $item)
                <tr>
                    <td class="text-center">
                        {{ $index + 1 }}
                    </td>

                    <td>
                        <div class="product-name">
                            {{ $item->product_name }}
                        </div>
                        <div class="variant-name">
                            {{ $item->variant_name }}
                        </div>
                    </td>

                    <td class="sku">
                        {{ $item->sku ?: '-' }}
                    </td>

                    <td class="text-right">
                        {{ number_format($item->system_qty, 0, ',', '.') }}
                        {{ $item->unit }}
                    </td>

                    <td class="text-right">
                        {{ number_format($item->physical_qty, 0, ',', '.') }}
                        {{ $item->unit }}
                    </td>

                    <td class="text-right">
                        <span class="{{ $item->difference > 0
                            ? 'difference-positive'
                            : ($item->difference < 0
                                ? 'difference-negative'
                                : 'difference-zero') }}">
                            {{ $item->difference > 0 ? '+' : '' }}
                            {{ number_format($item->difference, 0, ',', '.') }}
                        </span>
                        <br>
                        <span class="variant-name">{{ $item->unit }}</span>
                    </td>

                    <td>
                        {{ $item->reason ?: '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">
                        Tidak terdapat detail pemeriksaan pada dokumen ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="report-note">
        <strong>Keterangan:</strong>
        Stok lebih menunjukkan jumlah fisik lebih besar daripada stok sistem.
        Stok kurang menunjukkan jumlah fisik lebih kecil daripada stok sistem.
        Selisih dihitung berdasarkan jumlah stok fisik dikurangi stok sistem.
    </div>

    <!-- FOOTER -->
    <table class="footer">
        <tr>
            <td width="60%">
                SIMS Askara | Sistem Informasi Manajemen Persediaan
            </td>

            <td width="40%" class="footer-right">
                LAPORAN HASIL STOCK OPNAME
            </td>
        </tr>
    </table>

</body>
</html>
