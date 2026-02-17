<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: index.html');
    exit;
}

require_once '../includes/db.php';
$pdo = getDB();

$type = $_GET['type'] ?? 'registration';
$id = $_GET['id'] ?? 0;

if ($type === 'registration') {
    $stmt = $pdo->prepare("SELECT i.*, u.full_name, u.email, u.phone FROM invoices i LEFT JOIN users u ON i.user_id = u.id WHERE i.id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    $invoice_prefix = 'INV-R';
    $line_item = 'User Registration Fee';
} else {
    $stmt = $pdo->prepare("SELECT inv.*, u.full_name, u.email, u.phone FROM investments inv LEFT JOIN users u ON inv.user_id = u.id WHERE inv.id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    $invoice_prefix = 'INV-I';
    $line_item = 'Investment Contribution';
    // Set default values for investment invoices (no GST breakdown)
    $invoice['base_amount'] = $invoice['amount'];
    $invoice['gst_amount'] = 0;
    $invoice['status'] = 'paid';
}

if (!$invoice) {
    die("Invoice not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo $invoice_prefix . $invoice['id']; ?> - Aalaya</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            padding: 40px;
            color: #0f172a;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .invoice-header {
            background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%);
            color: white;
            padding: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .company-info h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .company-info p {
            opacity: 0.7;
            font-size: 0.9rem;
        }

        .invoice-meta {
            text-align: right;
        }

        .invoice-meta h2 {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .invoice-meta p {
            opacity: 0.7;
            margin-top: 8px;
        }

        .invoice-body {
            padding: 40px;
        }

        .client-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-block h3 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
            margin-bottom: 8px;
        }

        .detail-block p {
            font-weight: 600;
            font-size: 1rem;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .invoice-table th {
            background: #f8fafc;
            text-align: left;
            padding: 16px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
        }

        .invoice-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .invoice-table .text-right {
            text-align: right;
        }

        .totals-section {
            display: flex;
            justify-content: flex-end;
        }

        .totals-table {
            width: 300px;
        }

        .totals-table tr td {
            padding: 12px 0;
        }

        .totals-table tr td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .grand-total {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px !important;
        }

        .grand-total td {
            font-size: 1.25rem !important;
            font-weight: 800 !important;
            color: #2563eb;
        }

        .invoice-footer {
            text-align: center;
            padding: 24px 40px;
            background: #f8fafc;
            font-size: 0.85rem;
            color: #64748b;
        }

        .print-actions {
            text-align: center;
            margin-top: 24px;
        }

        .btn-print {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-print:hover {
            background: #1d4ed8;
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
            .print-actions {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="invoice-container">
    <div class="invoice-header">
        <div class="company-info">
            <!--<h1>AALAYA</h1>-->
            <p>Premium Investment Network</p>
            <p>support@aalaya.info</p>
        </div>
        <div class="invoice-meta">
            <h2><?php echo $invoice_prefix . $invoice['id']; ?></h2>
            <p>Date: <?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></p>
        </div>
    </div>

    <div class="invoice-body">
        <div class="client-details">
            <div class="detail-block">
                <h3>Billed To</h3>
                <p><?php echo htmlspecialchars($invoice['full_name']); ?></p>
                <p style="font-weight: 400; color: #64748b;"><?php echo htmlspecialchars($invoice['email']); ?></p>
                <p style="font-weight: 400; color: #64748b;"><?php echo htmlspecialchars($invoice['phone']); ?></p>
            </div>
            <div class="detail-block" style="text-align: right;">
                <h3>Payment Status</h3>
                <p style="color: <?php echo ($invoice['status'] ?? 'paid') === 'paid' ? '#10b981' : '#f59e0b'; ?>;">
                    <?php echo ucfirst($invoice['status'] ?? 'Completed'); ?>
                </p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $line_item; ?></td>
                    <td class="text-right">₹<?php echo number_format($invoice['amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="totals-section">
            <table class="totals-table">
                <tr class="grand-total">
                    <td>Total Amount</td>
                    <td>₹<?php echo number_format($invoice['amount'], 2); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="invoice-footer">
        Thank you for your trust in Aalaya. This is a computer-generated invoice.
    </div>
</div>

<div class="print-actions">
    <button class="btn-print" onclick="window.print()">
        <i class="bi bi-printer"></i> Print Invoice
    </button>
</div>

</body>
</html>
