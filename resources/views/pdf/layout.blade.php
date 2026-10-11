<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28mm 18mm 22mm 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        header { position: fixed; top: -20mm; left: 0; right: 0; border-bottom: 2px solid #1c2b84; padding-bottom: 6px; }
        header .org { font-size: 13px; font-weight: bold; color: #1c2b84; }
        header .sub { font-size: 9px; color: #64748b; }
        footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 4px; }
        h1 { font-size: 18px; color: #121a4f; margin: 0 0 4px; }
        h2 { font-size: 13px; color: #1c2b84; margin: 18px 0 6px; }
        p.lead { color: #475569; margin: 0 0 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-weight: bold; color: #334155; }
        table.kv th { width: 35%; }
        .num { text-align: right; }
        .muted { color: #64748b; }
        .summary td { border: none; background: #eef4ff; text-align: center; padding: 10px; }
        .summary .big { font-size: 18px; font-weight: bold; color: #1c2b84; display: block; }
        .sign { margin-top: 48px; width: 45%; border-top: 1px solid #334155; padding-top: 4px; float: right; text-align: center; }
    </style>
</head>
<body>
    <header>
        <div class="org">Government Plot Compliance Monitoring System</div>
        <div class="sub">Industrial &amp; Commercial Plot Allotment Cell</div>
    </header>
    <footer>Generated on {{ now()->format('d M Y, H:i') }} · System-generated document</footer>
    @yield('content')
</body>
</html>
