<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .invoice-table td, .invoice-table th {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .invoice-table th {
            background-color: #f2f2f2;
            text-align: left;
        }
        .total {
            font-weight: bold;
        }
        .logo {
            width: 150px;
        }
        .header-table {
            width: 100%;
        }
        .header-table td {
            vertical-align: top;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table class="header-table">
            <tr>
                <td>
                    <img src="<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/photos/logotipo_sucursal_3_1762264284.jpeg" class="logo" alt="Company Logo">
                </td>
                <td style="text-align:right;">
                    <h1>Invoice</h1>
                    <p>Date: <?php echo date('F d, Y'); ?></p>
                    <p>Invoice #: 222</p>
                </td>
            </tr>
        </table>
        
        <table class="header-table" style="margin-top: 20px;">
            <tr>
                <td>
                    <strong>Billed To:</strong><br>
                    JORGE DE BOLI<br>
                    CALLE FANTASIA 123<br>
                    MADRID<br>
                    28001<br>
                    SPAIN<br>
                </td>
                <td style="text-align:right;">
                    <strong>From:</strong><br>
                    Your Company Name<br>
                    CALLE FANTASIA 123<br>
                    MADRID<br>
                    28001<br>
                    SPAIN<br>
                    Your Company Address
                </td>
            </tr>
        </table>
        
        <table class="invoice-table">
            <tr>
                <th>Description</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Amount</th>
            </tr>
            <tr>
                <td>Adelanto de capital</td>
                <td>1</td>
                <td>100.00</td>
                <td>100.00</td>
            </tr>
            <tr>
                <td colspan="3" class="total">Subtotal</td>
                <td class="total">100.00</td>
            </tr>
            <tr>
                <td colspan="3" class="total">Tax</td>
                <td class="total">10.00</td>
            </tr>
            <tr>
                <td colspan="3" class="total">Total</td>
                <td class="total">110.00</td>
            </tr>
        </table>
    </div>
</body>
</html>