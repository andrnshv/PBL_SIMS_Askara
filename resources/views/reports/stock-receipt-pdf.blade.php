<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Penerimaan Barang - SIMS Askara</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 28px 30px;
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
            margin-bottom: 20px;
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
            font-size: 13px;
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
            margin: 16px 0 8px;
        }

        /* DOCUMENT INFORMATION */
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
            padding: 9px 11px;
            background: #f1f5f9;
            border-left: 3px solid #4C8DF6;
            color: #475569;
            font-size: 8px;
            margin: 12px 0;
        }

        /* SUMMARY */
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 17px;
        }

        .summary td {
            width: 50%;
            padding: 12px;
            border: 1px solid #dbe3ef;
            background: #f8fafc;
        }

        .summary-accent {
            border-top: 3px solid #0A1E45 !important;
        }

        .summary-label {
            color: #64748b;
            font-size: 8px;
            font-weight: bold;
        }

        .summary-value {
            color: #0A1E45;
            font-size: 20px;
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
            margin: 14px 0 8px;
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
            font-size: 8px;
            text-align: left;
            padding: 9px 7px;
            border: 1px solid #0A1E45;
        }

        .data-table td {
            padding: 9px 7px;
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

        .quantity {
            color: #15803d;
            font-weight: bold;
            font-size: 9px;
        }

        .empty {
            text-align: center;
            padding: 22px !important;
            color: #64748b;
        }

        /* SIGNATURE */
        .signature {
            width: 100%;
            border-collapse: collapse;
            margin-top: 35px;
        }

        .signature td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 5px 20px;
            color: #475569;
            font-size: 8px;
        }

        .signature-space {
            height: 55px;
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
            margin-top: 22px;
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
                <div class="document-label">INVENTORY DOCUMENT</div>
                <div class="document-title">
                    BUKTI PENERIMAAN BARANG
                </div>
                <div class="document-subtitle">
                    Goods Receipt Document
                </div>
            </td>
        </tr>
    </table>

    <!-- INFORMASI PENERIMAAN -->
    <div class="section-heading">INFORMASI PENERIMAAN</div>

    <table class="information">
        <tr>
            <td width="50%">
                <div class="info-label">NOMOR PENERIMAAN</div>
                <div class="info-value">{{ $receipt->receipt_no }}</div>
            </td>

            <td width="50%">
                <div class="info-label">TANGGAL PENERIMAAN</div>
                <div class="info-value">
                    {{ \Carbon\Carbon::parse($receipt->receipt_date)->format('d F Y') }}
                </div>
            </td>
        </tr>

        <tr>
            <td>
                <div class="info-label">SUPPLIER</div>
                <div class="info-value">{{ $receipt->supplier ?: '-' }}</div>
            </td>

            <td>
                <div class="info-label">PURCHASE ORDER</div>
                <div class="info-value">{{ $receipt->po_number ?: '-' }}</div>
            </td>
        </tr>

        <tr>
            <td>
                <div class="info-label">NOMOR SURAT JALAN</div>
                <div class="info-value">{{ $receipt->delivery_ref ?: '-' }}</div>
            </td>

            <td>
                <div class="info-label">PETUGAS PENERIMA</div>
                <div class="info-value">{{ $receipt->officer }}</div>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <div class="info-label">LOKASI GUDANG</div>
                <div class="info-value">{{ $warehouse }}</div>
            </td>
        </tr>
    </table>

    <!-- CATATAN -->
    <div class="notes">
        <strong>Catatan Penerimaan:</strong>
        {{ $receipt->notes ?: 'Tidak ada catatan.' }}
    </div>

    <!-- RINGKASAN -->
    <div class="section-heading">RINGKASAN PENERIMAAN</div>

    <table class="summary">
        <tr>
            <td class="summary-accent">
                <div class="summary-label">TOTAL BARIS BARANG</div>
                <div class="summary-value">
                    {{ number_format($totalLines, 0, ',', '.') }}
                </div>
                <div class="summary-description">
                    Jumlah varian barang yang diterima
                </div>
            </td>

            <td class="summary-accent">
                <div class="summary-label">STATUS DOKUMEN</div>
                <div class="summary-value" style="font-size: 15px;">
                    TERCATAT
                </div>
                <div class="summary-description">
                    Penerimaan barang telah tersimpan di sistem
                </div>
            </td>
        </tr>
    </table>

    <!-- DETAIL BARANG -->
    <table class="table-heading">
        <tr>
            <td>
                <div class="table-title">DETAIL BARANG DITERIMA</div>
            </td>

            <td class="table-description">
                {{ number_format($totalLines, 0, ',', '.') }} baris barang
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="7%" class="text-center">NO.</th>
                <th width="43%">PRODUK / VARIAN</th>
                <th width="22%">SKU</th>
                <th width="28%" class="text-right">JUMLAH DITERIMA</th>
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
                        <span class="quantity">
                            +{{ number_format($item->qty_received, 0, ',', '.') }}
                        </span>
                        {{ $item->unit }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty">
                        Tidak terdapat detail barang pada dokumen penerimaan ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- AREA TANDA TANGAN -->
    <table class="signature">
        <tr>
            <td>
                Diterima oleh,
                <div class="signature-space"></div>
                <div class="signature-line">
                    {{ $receipt->officer }}
                </div>
                Petugas Penerima
            </td>

            <td>
                Dokumen dicetak pada,
                <div class="signature-space"></div>
                <div class="signature-line">
                    {{ $generatedAt }}
                </div>
                Tanggal dan Waktu Cetak
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
                BUKTI PENERIMAAN BARANG
            </td>
        </tr>
    </table>

</body>
</html>
