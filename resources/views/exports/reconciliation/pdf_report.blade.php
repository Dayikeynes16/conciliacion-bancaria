<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Estatus</title>
    <style>
        @page { margin: 15mm 20mm; size: A4 portrait; } /* Portrait as per request hint (A4 usually implies portrait unless specified, but user had landscape. Reference look fits portrait better often, but keeping user's prev preference or switching? "A4 portrait" was in my thought, but user current code is "A4 landscape". I will stick to what the user had or what fits best. The reference images look "vertical" stacked cards. I will try PORTRAIT as it fits the "Premium Document" feel better, but if columns are wide, landscape is safer. Let's stick to Portrait for the "Document" feel unless data forces wide. Actually, the user's previous code was landscape. The reference images look like vertical A4. I will switch to A4 PORTRAIT to match the "Document" aesthetic of the reference images provided in previous turn context (implied). If tables are too wide, I'll adjust. */
        
        /* WAIT - user request said "Match these reference visuals". Reference images usually look like vertical docs. I will use Portrait. */
        @page { margin: 12mm 12mm; size: A4 portrait; }

        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 9pt; 
            color: #1F2937; /* Gray-800 */
            line-height: 1.3; 
            background-color: #F8FAFC; /* Slate-50 */
            margin: 0;
            padding: 0;
        }

        /* Utility Classes */
        .w-full { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .text-xs { font-size: 7pt; }
        .text-sm { font-size: 8pt; }
        .text-base { font-size: 9pt; }
        .text-lg { font-size: 11pt; }
        .text-xl { font-size: 14pt; }
        .text-2xl { font-size: 18pt; }
        
        /* Colors */
        .text-blue { color: #2563EB; }   /* Blue-600 */
        .text-green { color: #16A34A; }  /* Green-600 */
        .text-red { color: #DC2626; }    /* Red-600 */
        .text-gray { color: #6B7280; }   /* Gray-500 */
        .text-white { color: #ffffff; }

        .bg-blue-light { background-color: #EFF6FF; } /* Blue-50 */
        .bg-green-light { background-color: #F0FDF4; } /* Green-50 */
        .bg-red-light { background-color: #FEF2F2; } /* Red-50 */
        
        /* Block Layout Components */
        .container {
            width: 100%;
        }

        .header-block {
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 2px solid #3B82F6;
        }

        .card {
            background-color: #ffffff;
            border: 1px solid #E2E8F0; /* Slate-200 */
            border-radius: 8px; /* Rounded corners */
            padding: 15px;
            margin-bottom: 15px;
            page-break-inside: avoid; /* Prevent card splitting */
        }
        
        /* Grid Layouts via Tables (DomPDF support) */
        .grid-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 10px; /* Gap between "grid items" */
            margin: 0 -10px; /* Negative margin to offset spacing */
        }
        .grid-cell {
            vertical-align: top;
            /* width handled inline */
        }

        /* Data Tables (Inner) */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .data-table th {
            text-align: left;
            color: #64748B; /* Slate-500 */
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #E2E8F0;
            padding: 6px 4px;
            font-size: 7pt;
        }
        .data-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: top;
            color: #334155; /* Slate-700 */
        }
        .data-table tr:last-child td { border-bottom: none; }

        /* Badges & Pills */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background-color: #DCFCE7; color: #166534; }
        .badge-warning { background-color: #FEF3C7; color: #92400E; }
        .badge-danger { background-color: #FEE2E2; color: #991B1B; }
        .badge-info { background-color: #E0F2FE; color: #0369A1; } /* Blue-100/Blue-700 */
        .badge-neutral { background-color: #F1F5F9; color: #475569; }

        /* KPI Strip */
        .kpi-strip {
            background-color: #0F172A; /* Slate-900 */
            color: white;
            border-radius: 8px;
            padding: 12px 0;
            margin-bottom: 20px;
        }
        .kpi-cell {
            text-align: center;
            border-right: 1px solid #334155;
            width: 25%;
        }
        .kpi-cell:last-child { border-right: none; }
        .kpi-label { font-size: 7pt; text-transform: uppercase; color: #94A3B8; letter-spacing: 0.5px; }
        .kpi-value { font-size: 11pt; font-weight: bold; margin-top: 2px; }

        /* Section Headings */
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #0F172A;
            margin-bottom: 10px;
            border-left: 4px solid #3B82F6;
            padding-left: 10px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: -10mm;
            left: 0; right: 0;
            font-size: 7pt;
            color: #94A3B8;
            border-top: 1px solid #E2E8F0;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <!-- PAGE 1: Header + Executive Summary -->

    <!-- Header Block -->
    <div class="header-block">
        <table class="w-full">
            <tr>
                <td width="60%">
                    <!-- Icon + Title -->
                    <table style="border-collapse: collapse;">
                        <tr>
                            <td style="padding-right: 10px;">
                                <div style="background-color: #2563EB; color: white; width: 32px; height: 32px; border-radius: 6px; text-align: center; line-height: 32px; font-size: 18px; font-family: 'DejaVu Sans', sans-serif;">🏛</div>
                            </td>
                            <td>
                                <div class="text-xl font-bold" style="color: #0F172A;">REPORTE DE ESTATUS</div>
                                <div class="text-xs font-bold text-blue uppercase" style="letter-spacing: 1px;">Conciliación Bancaria</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="40%" class="text-right">
                    <div class="text-xs text-gray uppercase">Periodo de Reporte</div>
                    <div class="text-lg font-bold" style="color: #0F172A;">
                        @if($filters['date_from'] && $filters['date_to'])
                            {{ \Carbon\Carbon::parse($filters['date_from'])->locale('es')->isoFormat('D MMM Y') }} - {{ \Carbon\Carbon::parse($filters['date_to'])->locale('es')->isoFormat('D MMM Y') }}
                        @elseif($filters['month'] && $filters['year'])
                            {{ strtoupper(\Carbon\Carbon::createFromDate($filters['year'], $filters['month'], 1)->locale('es')->isoFormat('MMMM Y')) }}
                        @else
                            HISTÓRICO COMPLETO
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Executive Summary Section -->
    <div style="margin-bottom: 10px;">
        <span class="text-xs font-bold text-gray uppercase" style="letter-spacing: 1px;">▌ RESUMEN EJECUTIVO</span>
    </div>

    <!-- 2x2 Summary Cards -->
    <table class="grid-table" style="margin-bottom: 15px;">
        <tr>
            <!-- Card 1 -->
            <td class="grid-cell" width="50%">
                <div class="card">
                    <div style="margin-bottom: 8px;">
                        <span style="color: #2563EB; font-size: 14pt; font-family: 'DejaVu Sans', sans-serif;">✔</span>
                        <span class="text-xs font-bold text-gray uppercase" style="margin-left: 5px;">Facturas Conciliadas</span>
                    </div>
                    <div class="text-2xl font-bold" style="color: #0F172A;">${{ number_format($summary['conciliated_invoices'], 2) }}</div>
                    <div style="margin-top: 10px; border-top: 1px solid #F1F5F9; padding-top: 8px;">
                        <span class="badge badge-success" style="float: right;">COMPLETADO</span>
                    </div>
                </div>
            </td>
            <!-- Card 2 -->
            <td class="grid-cell" width="50%">
                <div class="card">
                    <div style="margin-bottom: 8px;">
                        <span style="color: #2563EB; font-size: 14pt; font-family: 'DejaVu Sans', sans-serif;">💵</span>
                        <span class="text-xs font-bold text-gray uppercase" style="margin-left: 5px;">Pagos Conciliados</span>
                    </div>
                    <div class="text-2xl font-bold" style="color: #0F172A;">${{ number_format($summary['conciliated_movements'], 2) }}</div>
                    <div style="margin-top: 10px; border-top: 1px solid #F1F5F9; padding-top: 8px;">
                        <span class="badge badge-success" style="float: right;">COMPLETADO</span>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <!-- Card 3 -->
            <td class="grid-cell" width="50%">
                <div class="card">
                    <div style="margin-bottom: 8px;">
                        <span style="color: #F59E0B; font-size: 14pt; font-family: 'DejaVu Sans', sans-serif;">📄</span>
                        <span class="text-xs font-bold text-gray uppercase" style="margin-left: 5px;">Facturas Pendientes</span>
                    </div>
                    <div class="text-2xl font-bold" style="color: #0F172A;">${{ number_format($summary['pending_invoices'], 2) }}</div>
                    <div style="margin-top: 10px; border-top: 1px solid #F1F5F9; padding-top: 8px;">
                        <span class="badge badge-warning" style="float: right;">EN PROCESO</span>
                    </div>
                </div>
            </td>
            <!-- Card 4 -->
            <td class="grid-cell" width="50%">
                <div class="card">
                    <div style="margin-bottom: 8px;">
                        <span style="color: #F59E0B; font-size: 14pt; font-family: 'DejaVu Sans', sans-serif;">⏳</span>
                        <span class="text-xs font-bold text-gray uppercase" style="margin-left: 5px;">Pagos Pendientes</span>
                    </div>
                    <div class="text-2xl font-bold" style="color: #0F172A;">${{ number_format($summary['pending_movements'], 2) }}</div>
                    <div style="margin-top: 10px; border-top: 1px solid #F1F5F9; padding-top: 8px;">
                        <span class="badge badge-warning" style="float: right;">EN PROCESO</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- KPI Strip -->
    @php $globalDiff = $summary['conciliated_movements'] - $summary['conciliated_invoices']; @endphp
    <table class="w-full kpi-strip">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Grupos Conciliados</div>
                <div class="kpi-value text-white">{{ $conciliatedGroups->count() }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Facturas Pend.</div>
                <div class="kpi-value" style="color: #FBBF24;">{{ $pendingInvoices->count() }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Pagos Pend.</div>
                <div class="kpi-value" style="color: #FBBF24;">{{ $pendingMovements->count() }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Diferencia Global</div>
                <div class="kpi-value" style="{{ abs($globalDiff) > 0.01 ? 'color: #EF4444;' : 'color: #34D399;' }}">
                    ${{ number_format($globalDiff, 2) }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Page Break Summary -->
    <div style="page-break-after: always;"></div>

    <!-- SECTION: Conciliadas -->
    <div class="section-title">Sección: Conciliadas</div>
    
    @foreach($conciliatedGroups as $group)
        <div class="card">
            @php $diff = $group['difference']; @endphp
            
            <!-- Group Header -->
            <table class="w-full" style="margin-bottom: 12px;">
                <tr>
                    <td>
                        <div class="text-base font-bold" style="color: #0F172A;">
                            Conciliación • {{ \Carbon\Carbon::parse($group['date'])->format('d M Y') }}
                        </div>
                        <div class="text-xs text-gray">
                            Usuario: {{ $group['user'] }} • ID: {{ $group['short_id'] }}
                        </div>
                    </td>
                    <td class="text-right">
                         <span class="badge {{ $diff < -0.01 ? 'badge-success' : ($diff > 0.01 ? 'badge-danger' : 'badge-info') }}">
                            @if($diff < -0.01)
                                <span style="font-family: 'DejaVu Sans', sans-serif;">✔</span> FAVORABLE
                            @elseif($diff > 0.01)
                                <span style="font-family: 'DejaVu Sans', sans-serif;">⚠</span> DIFERENCIA
                            @else
                                <span style="font-family: 'DejaVu Sans', sans-serif;">✔</span> BALANCEADO
                            @endif
                            : ${{ number_format($diff, 2) }}
                        </span>
                    </td>
                </tr>
            </table>

            <!-- Two Column Layout for Details -->
            <table class="grid-table" style="border-spacing: 0;">
                <tr>
                    <!-- LEFT: Invoices -->
                    <td width="50%" style="padding-right: 15px; border-right: 1px dashed #E2E8F0;">
                        <div style="margin-bottom: 5px;">
                            <span class="badge badge-neutral">FACTURAS (${{ number_format($group['total_invoices'], 2) }})</span>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Razón Social</th>
                                    <th>Fecha</th>
                                    <th>Ref</th>
                                    <th class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group['invoices'] as $inv)
                                <tr>
                                    <td>{{ $inv->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($inv->date)->format('d/m/y') }}</td>
                                    <td>{{ $inv->folio }}</td>
                                    <td class="text-right font-bold">${{ number_format($inv->amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                    <!-- RIGHT: Payments -->
                    <td width="50%" style="padding-left: 15px;">
                        <div style="margin-bottom: 5px;">
                            <span class="badge badge-neutral">PAGOS (${{ number_format($group['total_movements'], 2) }})</span>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Banco / Concepto</th>
                                    <th>Fecha</th>
                                    <th>Ref</th>
                                    <th class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group['movements'] as $mov)
                                <tr>
                                    <td>
                                        <div class="font-bold">{{ $mov->bank_label }}</div>
                                        <div class="text-xs text-gray">{{ $mov->description }}</div>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($mov->date)->format('d/m/y') }}</td>
                                    <td>{{ $mov->reference }}</td>
                                    <td class="text-right font-bold">${{ number_format($mov->amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach

    <!-- SECTION: Pending (New Page) -->
    <div style="page-break-before: always;"></div>
    
    <div class="section-title" style="border-color: #F59E0B;">Pendientes / No Conciliadas</div>

    <!-- Card A: Pending Invoices -->
    <div class="card">
        <div style="border-bottom: 1px solid #E2E8F0; padding-bottom: 10px; margin-bottom: 10px;">
            <table class="w-full">
                <tr>
                    <td>
                        <div class="text-base font-bold text-blue">
                             <span style="font-family: 'DejaVu Sans', sans-serif;">📄</span> CARD A: FACTURAS PENDIENTES (CXC / CXP)
                        </div>
                        <div class="text-xs text-gray">Reportados en sistema pero sin conciliación bancaria asociada</div>
                    </td>
                    <td class="text-right">
                        <div class="text-lg font-bold text-blue">${{ number_format($summary['pending_invoices'], 2) }}</div>
                        <div class="text-xs text-gray">{{ $pendingInvoices->count() }} Registros</div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="15%">Fecha</th>
                    <th width="25%">RFC</th>
                    <th width="45%">Emisor / Razón Social</th>
                    <th width="15%" class="text-right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingInvoices as $inv)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($inv->date)->format('d/m/Y') }}</td>
                    <td>{{ $inv->rfc }}</td>
                    <td>
                        <div class="font-bold">{{ $inv->name }}</div>
                    </td>
                    <td class="text-right font-bold text-blue">${{ number_format($inv->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Card B: Pending Payments -->
    <div class="card">
        <div style="border-bottom: 1px solid #E2E8F0; padding-bottom: 10px; margin-bottom: 10px;">
             <table class="w-full">
                <tr>
                    <td>
                        <div class="text-base font-bold text-green">
                            <span style="font-family: 'DejaVu Sans', sans-serif;">💵</span> CARD B: MOVIMIENTOS BANCARIOS PENDIENTES
                        </div>
                        <div class="text-xs text-gray">Transacciones en firme en bancos no registradas/conciliadas</div>
                    </td>
                    <td class="text-right">
                        <div class="text-lg font-bold text-green">${{ number_format($summary['pending_movements'], 2) }}</div>
                        <div class="text-xs text-gray">{{ $pendingMovements->count() }} Operaciones</div>
                    </td>
                </tr>
            </table>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th width="15%">Fecha</th>
                    <th width="55%">Concepto / Descripción</th>
                    <th width="15%">Banco</th>
                    <th width="15%" class="text-right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingMovements as $mov)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($mov->date)->format('d/m/Y') }}</td>
                    <td>{{ $mov->description }}</td>
                    <td>{{ $mov->bank_label }}</td>
                    <td class="text-right font-bold text-green">${{ number_format($mov->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Auditor Footer REMOVED by user request -->

    <!-- Footer Script -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}  |  Generado: " . date('d/m/Y H:i');
            $size = 7;
            $font = $fontMetrics->getFont("Helvetica, Arial, sans-serif");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $pdf->page_text($pdf->get_width() - $width - 40, $pdf->get_height() - 20, $text, $font, $size, array(0.5, 0.5, 0.5));
        }
    </script>
</body>
</html>
