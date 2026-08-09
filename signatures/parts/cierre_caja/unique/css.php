<!-- CSS CUSTOM cierre_caja - unique  -->
 <style>
    /* Input de Caja */
    #inputCaja {
            border: 2px solid #00cfe8 !important;
            border-radius: 6px !important;
            text-align: center;
            font-size: 1.3rem;
            font-weight: 600;
            height: 50px !important;
            color: #5e5873;
            width: 100%;
        }
        
        #inputCaja:focus {
            border-color: #00cfe8 !important;
            box-shadow: 0 0 0 3px rgba(0, 207, 232, 0.2) !important;
        }
        
        /* Columna de totales sticky */
        .totales-column {
            position: sticky;
            top: 20px;
        }
        /* Inputs sin borde */
        .unidades {
            text-align: center;
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            height: auto !important;
            padding: 4px !important;
            color: #6e6b7b;
            font-weight: 500;
            background: none !important;
        }
        
        .unidades:focus {
            border: none !important;
            box-shadow: none !important;
            outline: none !important;
        }
        
        /* Ocultar flechitas del input number */
        .unidades::-webkit-outer-spin-button,
        .unidades::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .unidades {
            -moz-appearance: textfield;
            text-align: center;
        }
        
        .total-linea-display {
            text-align: center;
            font-weight: 600;
            color: #5e5873;
            text-align: center;
        }
        
        /* Cajas de totales */
        .total-box {
            background: inherit;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 20px;
        }
        
        .total-box .icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        
        .total-box .icon-wrapper i {
            font-size: 30px;
            color: white;
        }
        
        .total-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .total-label {
            font-size: 0.95rem;
            color: #6e6b7b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        #table_arqueo thead tr th {
            padding-block: 0.999rem !important;
        }
        #table_arqueo > tbody {
            vertical-align: inherit;
            font-size: 15px !important;
        }
        #table_arqueo > :not(caption) > * > * {
            padding: 4px 17px !important;
        }
 </style>