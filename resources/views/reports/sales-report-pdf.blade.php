<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan - SIMS Askara</title>

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
            width: 55%;
            background: #0A1E45;
            color: #fff;
        }

        .header-right {
            width: 45%;
            background: #163663;
            color: #fff;
            text-align: right;
        }

        .brand {
            font-size: 16px;
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
            font-weight: bold;
            letter-spacing: 1px;
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
            letter-spacing: .4px;
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

        /* SUMMARY */
        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px 0;
            margin: 4px -7px 15px;
        }

        .summary td {
            width: 33.33%;
            padding: 11px 12px;
            border: 1px solid #dbe3ef;
            background: #f8fafc;
            vertical-align: middle;
        }

        .summary-total {
            border-top: 3px solid #0A1E45 !important;
        }

        .summary-revenue {
            border-top: 3px solid #15966b !important;
        }

        .summary-units {
            border-top: 3px solid #4C8DF6 !important;
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
            margin-top: 3px;
        }

        /* TABLE */
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
            color: #fff;
            font-size: 7.5px;
            font-weight: bold;
            text-align: left;
            padding: 8px 6px;
            border: 1px solid #0A1E45;
        }

        .data-table td {
            padding: 8px 6px;
            border: 1px solid #e2e8f0;
            font-size: 7.5px;
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

        .invoice {
            color: #0A1E45;
            font-weight: bold;
        }

        .muted {
            color: #64748b;
            font-size: 7px;
        }

        .money {
            color: #0A1E45;
            font-weight: bold;
        }

        /* STATUS */
        .status {
            display: inline-block;
            padding: 3px 6px;
            font-size: 7px;
            font-weight: bold;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-other {
            background: #e2e8f0;
            color: #475569;
        }

        .empty {
            text-align: center;
            padding: 22px !important;
            color: #64748b;
        }

        /* NOTES */
        .report-note {
            margin-top: 9px;
            padding: 7px 9px;
            background: #f8fafc;
            color: #64748b;
            font-size: 7.5px;
        }

        /* FOOTER */
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
                <div class="document-label">SALES REPORT</div>
                <div class="document-title">
                    LAPORAN PENJUALAN
                </div>
                <div class="document-subtitle">
                    Dokumen Rekapitulasi Transaksi Penjualan
                </div>
            </td>
        </tr>
    </table>

    <!-- INFORMASI LAPORAN -->
    <div class="section-heading">INFORMASI LAPORAN</div>

    <table class="information">
        <tr>
            <td width="35%">
                <div class="info-label">PERIODE LAPORAN</div>
                <div class="info-value">
                    {{ $start ? \Carbon\Carbon::parse($start)->format('d/m/Y') : 'Semua tanggal' }}
                    s.d.
                    {{ $end ? \Carbon\Carbon::parse($end)->format('d/m/Y') : 'Sekarang' }}
                </div>
            </td>

            <td width="32%">
                <div class="info-label">TANGGAL DAN WAKTU CETAK</div>
                <div class="info-value">{{ $generatedAt }}</div>
            </td>

            <td width="33%">
                <div class="info-label">DICETAK OLEH</div>
                <div class="info-value">{{ $generatedBy }}</div>
            </td>
        </tr>
    </table>

    @if ($search || $sortLabel)
        <div class="filter-note">
            <strong>Filter yang diterapkan:</strong>

            @if ($search)
                Pencarian: {{ $search }};
            @endif

            @if ($sortLabel)
                Pengurutan: {{ $sortLabel }};
            @endif
        </div>
    @endif

    <!-- RINGKASAN PENJUALAN -->
    <div class="section-heading">RINGKASAN PENJUALAN</div>

    <table class="summary">
        <tr>
            <td class="summary-total">
                <div class="summary-label">TOTAL TRANSAKSI</div>
                <div class="summary-value">
                    {{ number_format($stats['transactions'], 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Seluruh transaksi sesuai filter
                </div>
            </td>

            <td class="summary-revenue">
                <div class="summary-label">TOTAL PENJUALAN</div>
                <div class="summary-value" style="font-size: 17px;">
                    Rp {{ number_format($stats['revenue'], 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Total nilai transaksi sesuai data sistem
                </div>
            </td>

            <td class="summary-units">
                <div class="summary-label">TOTAL ITEM TERJUAL</div>
                <div class="summary-value">
                    {{ number_format($stats['units'], 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Total kuantitas unit terjual
                </div>
            </td>
        </tr>
    </table>

    <!-- DETAIL TRANSAKSI -->
    <table class="table-heading">
        <tr>
            <td>
                <div class="table-title">DETAIL TRANSAKSI PENJUALAN</div>
            </td>

            <td class="table-description">
                {{ number_format($totalRows, 0, ',', '.') }} transaksi ditemukan
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">NO.</th>
                <th width="19%">NOMOR INVOICE</th>
                <th width="16%">TANGGAL & WAKTU</th>
                <th width="19%">KASIR</th>
                <th width="10%" class="text-center">JENIS ITEM</th>
                <th width="10%" class="text-center">TOTAL UNIT</th>
                <th width="12%" class="text-right">TOTAL PENJUALAN</th>
                <th width="9%">STATUS</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($transactions as $index => $t)
                <tr>
                    <td class="text-center">
                        {{ $index + 1 }}
                    </td>

                    <td class="invoice">
                        {{ $t['invoice'] }}
                    </td>

                    <td>
                        {{ $t['datetime']->format('d/m/Y') }}<br>
                        <span class="muted">
                            {{ $t['datetime']->format('H:i') }}
                        </span>
                    </td>

                    <td>
                        {{ $t['cashier'] }}
                    </td>

                    <td class="text-center">
                        {{ number_format($t['items'], 0, ',', '.') }}
                    </td>

                    <td class="text-center">
                        {{ number_format($t['units'], 0, ',', '.') }}
                    </td>

                    <td class="text-right money">
                        Rp {{ number_format($t['total'], 0, ',', '.') }}
                    </td>

                    <td>
                        @if ($t['status'] === 'completed')
                            <span class="status status-completed">
                                SELESAI
                            </span>
                        @elseif ($t['status'] === 'cancelled')
                            <span class="status status-cancelled">
                                DIBATALKAN
                            </span>
                        @else
                            <span class="status status-other">
                                {{ strtoupper($t['status']) }}
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">
                        Tidak terdapat transaksi yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="report-note">
        <strong>Catatan:</strong>
        Ringkasan dihitung berdasarkan seluruh transaksi yang sesuai filter.
        Detail laporan menampilkan maksimal 50 transaksi sesuai urutan yang dipilih.
        Status transaksi ditampilkan sesuai data yang tersimpan pada sistem.
    </div>

    <!-- FOOTER -->
    <table class="footer">
        <tr>
            <td width="60%">
                SIMS Askara | Sistem Informasi Manajemen Persediaan
            </td>

            <td width="40%" class="footer-right">
                DOKUMEN LAPORAN PENJUALAN
            </td>
        </tr>
    </table>

</body>
</html>
