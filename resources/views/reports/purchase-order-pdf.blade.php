<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Purchase Order - SIMS Askara</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 25px 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #334155;
            line-height: 1.5;
        }

        /* HEADER */
        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .header td {
            padding: 14px;
            vertical-align: middle;
        }

        .header-left {
            width: 55%;
            background: #0A1E45;
            color: #ffffff;
        }

        .header-right {
            width: 45%;
            background: #163663;
            color: #ffffff;
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
            margin-top: 4px;
        }

        .document-label {
            color: #bfdbfe;
            font-size: 8px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .document-title {
            font-size: 15px;
            font-weight: bold;
            margin-top: 5px;
        }

        .document-subtitle {
            color: #cbd5e1;
            font-size: 8px;
            margin-top: 3px;
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
            padding: 9px 10px;
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
            color: #475569;
            font-size: 8px;
            margin: 12px 0;
        }

        /* STATUS */
        .status {
            display: inline-block;
            padding: 4px 8px;
            font-size: 8px;
            font-weight: bold;
        }

        .status-draft {
            background: #e2e8f0;
            color: #475569;
        }

        .status-ordered {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-partial {
            background: #fef3c7;
            color: #92400e;
        }

        .status-received {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        /* SUMMARY */
        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px 0;
            margin: 8px -7px 16px;
        }

        .summary td {
            width: 33.33%;
            padding: 11px 12px;
            border: 1px solid #dbe3ef;
            background: #f8fafc;
            vertical-align: middle;
        }

        .summary-primary {
            border-top: 3px solid #0A1E45 !important;
        }

        .summary-items {
            border-top: 3px solid #4C8DF6 !important;
        }

        .summary-received {
            border-top: 3px solid #15966b !important;
        }

        .summary-label {
            color: #64748b;
            font-size: 8px;
            font-weight: bold;
        }

        .summary-value {
            color: #0A1E45;
            font-size: 18px;
            font-weight: bold;
            margin-top: 4px;
        }

        .summary-description {
            color: #94a3b8;
            font-size: 7px;
            margin-top: 3px;
        }

        /* DETAIL TABLE */
        .table-heading {
            width: 100%;
            border-bottom: 2px solid #0A1E45;
            margin: 13px 0 8px;
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

        .product-name {
            color: #0A1E45;
            font-weight: bold;
        }

        .variant-name {
            color: #64748b;
            font-size: 7px;
        }

        .money {
            color: #0A1E45;
            font-weight: bold;
        }

        /* TOTAL */
        .total-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .total-table td {
            padding: 10px;
            border: 1px solid #dbe3ef;
            background: #f1f5f9;
        }

        .total-label {
            text-align: right;
            color: #475569;
            font-weight: bold;
        }

        .total-value {
            text-align: right;
            color: #0A1E45;
            font-size: 13px;
            font-weight: bold;
        }

        /* RECEIPT HISTORY */
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt-table th,
        .receipt-table td {
            border: 1px solid #dbe3ef;
            padding: 7px;
            font-size: 8px;
            text-align: left;
        }

        .receipt-table th {
            background: #eaf0fa;
            color: #0A1E45;
        }

        .empty {
            text-align: center;
            color: #64748b;
            padding: 15px !important;
        }

        /* SIGNATURE */
        .signature {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        .signature td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 5px 25px;
            color: #475569;
            font-size: 8px;
        }

        .signature-space {
            height: 48px;
        }

        .signature-line {
            border-top: 1px solid #94a3b8;
            padding-top: 5px;
            color: #0A1E45;
            font-weight: bold;
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

    <!-- HEADER -->
    <table class="header">
        <tr>
            <td class="header-left">
                <div class="brand">SIMS ASKARA</div>
                <div class="brand-caption">
                    Sistem Informasi Manajemen Persediaan
                </div>
            </td>

            <td class="header-right">
                <div class="document-label">PROCUREMENT DOCUMENT</div>
                <div class="document-title">PURCHASE ORDER</div>
                <div class="document-subtitle">
                    Dokumen Pemesanan Barang
                </div>
            </td>
        </tr>
    </table>

    <!-- INFORMASI PO -->
    <div class="section-heading">INFORMASI PURCHASE ORDER</div>

    <table class="information">
        <tr>
            <td width="25%">
                <div class="info-label">NOMOR PURCHASE ORDER</div>
                <div class="info-value">{{ $po->po_number }}</div>
            </td>

            <td width="25%">
                <div class="info-label">TANGGAL PEMESANAN</div>
                <div class="info-value">
                    {{ \Carbon\Carbon::parse($po->order_date)->format('d F Y') }}
                </div>
            </td>

            <td width="25%">
                <div class="info-label">ESTIMASI KEDATANGAN</div>
                <div class="info-value">
                    {{ $po->expected_date
                        ? \Carbon\Carbon::parse($po->expected_date)->format('d F Y')
                        : '-' }}
                </div>
            </td>

            <td width="25%">
                <div class="info-label">STATUS PO</div>
                <div class="info-value">
                    <span class="status status-{{ $po->status }}">
                        {{ $statusLabel }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- INFORMASI SUPPLIER -->
    <div class="section-heading">INFORMASI SUPPLIER</div>

    <table class="information">
        <tr>
            <td width="50%">
                <div class="info-label">NAMA SUPPLIER</div>
                <div class="info-value">{{ $po->supplier }}</div>
            </td>

            <td width="50%">
                <div class="info-label">NOMOR TELEPON</div>
                <div class="info-value">
                    {{ $po->supplier_phone ?: '-' }}
                </div>
            </td>
        </tr>

        <tr>
            <td>
                <div class="info-label">DIBUAT OLEH</div>
                <div class="info-value">{{ $po->creator }}</div>
            </td>

            <td>
                <div class="info-label">TANGGAL CETAK</div>
                <div class="info-value">{{ $generatedAt }}</div>
            </td>
        </tr>
    </table>

    <!-- CATATAN -->
    <div class="notes">
        <strong>Catatan Pemesanan:</strong>
        {{ $po->notes ?: 'Tidak ada catatan pemesanan.' }}
    </div>

    <!-- RINGKASAN -->
    <div class="section-heading">RINGKASAN PEMESANAN</div>

    <table class="summary">
        <tr>
            <td class="summary-primary">
                <div class="summary-label">TOTAL NILAI PO</div>
                <div class="summary-value">
                    Rp {{ number_format($total, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Nilai keseluruhan pesanan
                </div>
            </td>

            <td class="summary-items">
                <div class="summary-label">BARIS BARANG</div>
                <div class="summary-value">
                    {{ number_format($totalLines, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Jumlah varian yang dipesan
                </div>
            </td>

            <td class="summary-received">
                <div class="summary-label">KUANTITAS DITERIMA</div>
                <div class="summary-value">
                    {{ number_format($receivedLines, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Jumlah baris varian dengan penerimaan
                </div>
            </td>
        </tr>
    </table>

    <!-- DETAIL PESANAN -->
    <table class="table-heading">
        <tr>
            <td>
                <div class="table-title">RINCIAN PESANAN BARANG</div>
            </td>
            <td class="table-description">
                {{ number_format($totalLines, 0, ',', '.') }} baris barang
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">NO.</th>
                <th width="20%">PRODUK / VARIAN</th>
                <th width="10%">SKU</th>
                <th width="10%" class="text-right">DIPESAN</th>
                <th width="10%" class="text-right">DITERIMA</th>
                <th width="10%" class="text-right">SISA</th>
                <th width="14%" class="text-right">HARGA SATUAN</th>
                <th width="15%" class="text-right">SUBTOTAL</th>
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

                    <td>{{ $item->sku ?: '-' }}</td>

                    <td class="text-right">
                        {{ number_format($item->qty_ordered, 0, ',', '.') }}
                        {{ $item->unit }}
                    </td>

                    <td class="text-right">
                        {{ number_format($item->qty_received, 0, ',', '.') }}
                        {{ $item->unit }}
                    </td>

                    <td class="text-right">
                        {{ number_format(max($item->qty_ordered - $item->qty_received, 0), 0, ',', '.') }}
                        {{ $item->unit }}
                    </td>

                    <td class="text-right">
                        Rp {{ number_format($item->unit_cost, 0, ',', '.') }}
                    </td>

                    <td class="text-right money">
                        Rp {{ number_format($item->qty_ordered * $item->unit_cost, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">
                        Purchase Order belum memiliki detail barang.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TOTAL -->
    <table class="total-table">
        <tr>
            <td width="75%" class="total-label">
                TOTAL NILAI PURCHASE ORDER
            </td>

            <td width="25%" class="total-value">
                Rp {{ number_format($total, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <!-- RIWAYAT PENERIMAAN -->
    <div class="table-heading" style="margin-top: 20px;">
        <table width="100%">
            <tr>
                <td>
                    <div class="table-title">RIWAYAT PENERIMAAN BARANG</div>
                </td>
                <td class="table-description">
                    {{ number_format($receipts->count(), 0, ',', '.') }} dokumen penerimaan
                </td>
            </tr>
        </table>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th width="8%">NO.</th>
                <th width="35%">NOMOR PENERIMAAN</th>
                <th width="27%">TANGGAL PENERIMAAN</th>
                <th width="30%">SURAT JALAN</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($receipts as $index => $receipt)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $receipt->receipt_no }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($receipt->receipt_date)->format('d/m/Y') }}
                    </td>
                    <td>{{ $receipt->delivery_ref ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty">
                        Belum ada penerimaan barang untuk Purchase Order ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <table class="signature">
        <tr>
            <td>
                Dibuat oleh,
                <div class="signature-space"></div>
                <div class="signature-line">
                    {{ $po->creator }}
                </div>
                Pembuat Purchase Order
            </td>

            <td>
                Diterima oleh,
                <div class="signature-space"></div>
                <div class="signature-line">
                    ........................................
                </div>
                Pihak Supplier
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <table class="footer">
        <tr>
            <td width="60%">
                SIMS Askara | Sistem Informasi Manajemen Persediaan
            </td>

            <td width="40%" class="footer-right">
                DOKUMEN PURCHASE ORDER
            </td>
        </tr>
    </table>

</body>
</html>
