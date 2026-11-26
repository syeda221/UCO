<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>UCO Container QR Stickers</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 16px; color: #141513; }
        h1 { font-size: 14px; margin: 0 0 12px 0; }
        table.grid { width: 100%; border-collapse: separate; border-spacing: 10px; }
        td.sticker {
            width: 33.3%;
            border: 1.5px solid #1A1C19;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            vertical-align: top;
            page-break-inside: avoid;
        }
        .brand { font-size: 9px; font-weight: bold; letter-spacing: 0.5px; color: #5D5F5B; margin-bottom: 6px; }
        .qr img { width: 110px; height: 110px; }
        .code { font-size: 13px; font-weight: bold; margin-top: 6px; font-family: monospace; }
        .meta { font-size: 9px; color: #5D5F5B; margin-top: 3px; }
    </style>
</head>
<body>
    <h1>UCO Trace Ghana — Qualiplast Container QR Stickers</h1>
    @if($items->isEmpty())
        <p style="font-size: 12px;">No active containers selected.</p>
    @else
        <table class="grid">
            @foreach($items->chunk(3) as $row)
                <tr>
                    @foreach($row as $item)
                        <td class="sticker">
                            <div class="brand">UCO TRACE GHANA</div>
                            <div class="qr"><img src="{{ $item['qr_data_uri'] }}" alt="QR"></div>
                            <div class="code">{{ $item['container']->qr_code }}</div>
                            <div class="meta">
                                {{ $item['container']->qualiplast_tier }} ({{ number_format($item['container']->capacity_liters, 0) }}L)
                            </div>
                            <div class="meta">
                                {{ $item['container']->assignedVendor ? $item['container']->assignedVendor->vendor_code . ' - ' . $item['container']->assignedVendor->name : 'Unassigned Stock' }}
                            </div>
                        </td>
                    @endforeach
                    @for($i = $row->count(); $i < 3; $i++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
