<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport Financier</title>
    <style>
        @page { margin: 15mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.4; }

        .header { text-align: center; border-bottom: 3px solid #1e3a5f; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 20px; color: #1e3a5f; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 1px; }
        .header .subtitle { font-size: 11px; color: #666; }
        .header .restaurant-name { font-size: 14px; font-weight: bold; color: #333; margin-top: 4px; }

        .section-title { font-size: 13px; font-weight: bold; color: #1e3a5f; margin: 16px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #ddd; text-transform: uppercase; }

        .summary-grid { display: table; width: 100%; margin-bottom: 12px; }
        .summary-cell { display: table-cell; width: 25%; padding: 8px; text-align: center; border: 1px solid #e0e0e0; }
        .summary-cell .label { font-size: 9px; color: #888; text-transform: uppercase; margin-bottom: 3px; }
        .summary-cell .value { font-size: 16px; font-weight: bold; color: #1e3a5f; }
        .summary-cell .value.negative { color: #dc2626; }
        .summary-cell .value.positive { color: #16a34a; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        thead th { background: #1e3a5f; color: #fff; padding: 7px 8px; font-size: 10px; text-transform: uppercase; text-align: left; }
        tbody td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }

        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 9px; color: #999; text-align: center; }
        .footer .page-number:after { content: counter(page); }
    </style>
</head>
<body>

    <!-- EN-TÊTE -->
    <div class="header">
        <h1>Rapport Financier</h1>
        @if($restaurant)
            <div class="restaurant-name">{{ $restaurant->name }}</div>
        @else
            <div class="restaurant-name">Tous les restaurants</div>
        @endif
        <div class="subtitle">
            Période : {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            &nbsp;|&nbsp; Généré le {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- SYNTHÈSE FINANCIÈRE (Rapport X/Z) -->
    <div class="section-title">Synthèse Financière</div>
    <div class="summary-grid">
        <div class="summary-cell">
            <div class="label">Ventes Brutes</div>
            <div class="value">{{ number_format($salesSummary->gross_revenue ?? 0, 0, ',', ' ') }} FC</div>
        </div>
        <div class="summary-cell">
            <div class="label">Ventes Nettes</div>
            <div class="value positive">{{ number_format($paidSummary->net_revenue ?? 0, 0, ',', ' ') }} FC</div>
        </div>
        <div class="summary-cell">
            <div class="label">Annulations</div>
            <div class="value negative">{{ number_format($cancelledSummary->cancelled_amount ?? 0, 0, ',', ' ') }} FC</div>
        </div>
        <div class="summary-cell">
            <div class="label">Panier Moyen</div>
            <div class="value">{{ number_format($salesSummary->average_order_value ?? 0, 0, ',', ' ') }} FC</div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-cell">
            <div class="label">Total Tickets</div>
            <div class="value">{{ $salesSummary->total_orders ?? 0 }}</div>
        </div>
        <div class="summary-cell">
            <div class="label">Tickets Payés</div>
            <div class="value positive">{{ $paidSummary->paid_orders ?? 0 }}</div>
        </div>
        <div class="summary-cell">
            <div class="label">Tickets Annulés</div>
            <div class="value negative">{{ $cancelledSummary->cancelled_count ?? 0 }}</div>
        </div>
        <div class="summary-cell">
            <div class="label">Remises Accordées</div>
            <div class="value">{{ number_format($salesSummary->total_discounts ?? 0, 0, ',', ' ') }} FC</div>
        </div>
    </div>

    <!-- VENTES PAR CATÉGORIE -->
    <div class="section-title">Ventes par Catégorie</div>
    <table>
        <thead>
            <tr>
                <th>Catégorie</th>
                <th class="text-center">Qté Vendue</th>
                <th class="text-right">Chiffre d'Affaires</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesByCategory as $cat)
                <tr>
                    <td>{{ $cat->category_name }}</td>
                    <td class="text-center font-bold">{{ $cat->total_quantity }}</td>
                    <td class="text-right">{{ number_format($cat->total_revenue, 0, ',', ' ') }} FC</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center">Aucune donnée</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- MODES DE PAIEMENT -->
    <div class="section-title">Répartition par Mode de Paiement</div>
    <table>
        <thead>
            <tr>
                <th>Mode de Paiement</th>
                <th class="text-center">Transactions</th>
                <th class="text-right">Montant</th>
                <th class="text-right">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesByPaymentMethod as $pm)
                <tr>
                    <td>{{ $pm->label }}</td>
                    <td class="text-center font-bold">{{ $pm->transaction_count }}</td>
                    <td class="text-right">{{ number_format($pm->total_amount, 0, ',', ' ') }} FC</td>
                    <td class="text-right">{{ $pm->percentage }}%</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">Aucune donnée</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- PIED DE PAGE -->
    <div class="footer">
        Rapport généré automatiquement par POS Pro — Page <span class="page-number"></span>
    </div>

</body>
</html>
