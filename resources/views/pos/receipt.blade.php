<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu — {{ $order->order_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 5mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            max-width: 80mm;
            margin: 0 auto;
            padding: 10px;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .double-line { border-top: 2px solid #000; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { text-align: left; padding: 4px 0; border-bottom: 1px dashed #000; }
        .items-table td { padding: 3px 0; }
        .items-table .qty { text-align: center; }
        .items-table .price { text-align: right; }
        .total-row { font-size: 14px; font-weight: bold; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Header restaurant -->
    <div class="center">
        <p class="bold" style="font-size: 16px;">{{ $order->restaurant->name }}</p>
        @if($order->restaurant->receipt_header)
            <p>{{ $order->restaurant->receipt_header }}</p>
        @endif
        @if($order->restaurant->address)
            <p>{{ $order->restaurant->address }}</p>
        @endif
        @if($order->restaurant->phone)
            <p>Tel: {{ $order->restaurant->phone }}</p>
        @endif
    </div>

    <div class="double-line"></div>

    <!-- Infos commande -->
    <div class="row"><span>N°:</span><span>{{ $order->order_number }}</span></div>
    <div class="row"><span>Date:</span><span>{{ $order->created_at->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Caissier:</span><span>{{ $order->user?->name ?? '—' }}</span></div>
    <div class="row">
        <span>Table:</span>
        <span>{{ $order->table?->name ?? 'À emporter' }}</span>
    </div>
    @if($order->customer_name)
        <div class="row"><span>Client:</span><span>{{ $order->customer_name }}</span></div>
    @endif

    <div class="line"></div>

    <!-- Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Article</th>
                <th class="qty">Qt</th>
                <th class="price">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="qty">{{ $item->quantity }}</td>
                    <td class="price">{{ number_format($item->subtotal, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <!-- Totaux -->
    @if($order->discount_amount > 0)
        <div class="row"><span>Remise</span><span>-{{ number_format($order->discount_amount, 0, ',', ' ') }}</span></div>
    @endif
    @if($order->tax_amount > 0)
        <div class="row"><span>Taxe</span><span>{{ number_format($order->tax_amount, 0, ',', ' ') }}</span></div>
    @endif
    <div class="double-line"></div>
    <div class="row total-row">
        <span>TOTAL</span>
        <span>{{ number_format($order->total_amount, 0, ',', ' ') }} {{ $order->restaurant->currency ?? 'FC' }}</span>
    </div>
    <div class="double-line"></div>

    <!-- Paiement -->
    <div class="row">
        <span>Paiement</span>
        <span>{{ $order->payment_method_label ?? $order->payment_method }}</span>
    </div>
    @if($order->cash_received)
        <div class="row"><span>Reçu</span><span>{{ number_format($order->cash_received, 0, ',', ' ') }} {{ $order->restaurant->currency ?? 'FC' }}</span></div>
        <div class="row"><span>Monnaie</span><span>{{ number_format($order->change_given ?? 0, 0, ',', ' ') }} {{ $order->restaurant->currency ?? 'FC' }}</span></div>
    @endif
    @if($order->payment_reference)
        <div class="row"><span>Ref</span><span>{{ $order->payment_reference }}</span></div>
    @endif

    <div class="center" style="margin-top: 16px;">
        @if($order->restaurant->receipt_footer)
            <p>{{ $order->restaurant->receipt_footer }}</p>
        @else
            <p>Merci de votre visite !</p>
        @endif
    </div>

    <!-- Boutons impression -->
    <div class="no-print center" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; background: #f59e0b; color: #000; border: none; border-radius: 8px; font-weight: bold;">
            🖨️ Imprimer
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; background: #e5e7eb; color: #374151; border: none; border-radius: 8px; margin-left: 8px;">
            Fermer
        </button>
    </div>
</body>
</html>
