<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo lang('mess_daily_sheet'); ?> <?php echo escape_output($date); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f2f2f5; font-family: Arial, Helvetica, sans-serif; color: #222; font-size: 12.5px; }
        .page { width: 210mm; max-width: 100%; margin: 14px auto; background: #fff; padding: 12mm; }
        .mess-sheet-title { border-bottom: 2px solid #222; padding-bottom: 6px; margin-bottom: 8px; font-size: 15px; }
        .mess-muted { color: #666; font-size: 11.5px; }
        h5 { font-size: 13px; margin: 14px 0 5px; text-transform: uppercase; letter-spacing: .04em; }
        .mess-table { width: 100%; border-collapse: collapse; }
        .mess-table th, .mess-table td { border: 1px solid #cfcfd6; padding: 4px 6px; text-align: left; vertical-align: top; }
        .mess-table th { background: #f1f1f4; font-size: 11.5px; }
        .mess-num { text-align: right; white-space: nowrap; }
        .mess-due { color: #c62828; font-weight: bold; }
        .mess-badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 11px; font-weight: bold; background: #eee; }
        .mess-badge.expired { background: #fde2e2; color: #c62828; }
        .mess-badge.active { background: #e3f6ea; color: #1f7a45; }
        .mess-ticks td.box { width: 58px; text-align: center; }
        .mess-ticks td.box.wide { width: 90px; }
        .actions { width: 210mm; max-width: 100%; margin: 0 auto; padding-top: 10px; display: flex; gap: 8px; }
        .actions button { padding: 9px 18px; border: 0; border-radius: 6px; background: #7367f0; color: #fff; font-size: 14px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .page { margin: 0; width: 100%; padding: 0; }
            .actions { display: none; }
            @page { size: A4; margin: 10mm; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="actions"><button type="button" onclick="window.print()"><?php echo lang('print'); ?></button></div>
<div class="page">
    <?php $this->load->view('mess/_sheetBody'); ?>
</div>
</body>
</html>
