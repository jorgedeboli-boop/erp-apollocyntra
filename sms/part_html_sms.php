<style>
    #codigo_sms {
        width: 376px !important;
        height: 68px !important;
        padding: 7px 0px 9px 0px;
        line-height: 0px;
        font-size: 53px;
        text-align: center;
        letter-spacing: 6px;
        font-weight: bold;
        text-transform: uppercase;
        display: block;
        margin: 0 auto !important;
    }
    .lds-ring {
        display: none;
        position: fixed;
        width: 100%;
        height: 100%;
        z-index: 999999999999999;
        background: rgba(255,255,255,0.95);
        left: 0;
        top: 0;
        padding: 150px 50%;
    }
        .lds-ring div {
          box-sizing: border-box;
          display: block;
          position: absolute;
          width: 64px;
          height: 64px;
          margin: 8px;
          border: 8px solid #999;
          border-radius: 50%;
          animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
          border-color: #999 transparent transparent transparent;
        }
        .lds-ring div:nth-child(1) {
          animation-delay: -0.45s;
        }
        .lds-ring div:nth-child(2) {
          animation-delay: -0.3s;
        }
        .lds-ring div:nth-child(3) {
          animation-delay: -0.15s;
        }
        #titleloader {
            display: block;
            font-size: 19px;
            color: #999;
            position: fixed;
            left: 36px;
            right: 0;
            width: 100%;
            height: auto;
            top: 291px;
            text-align: center;
        }
        @keyframes lds-ring {
          0% {
            transform: rotate(0deg);
          }
          100% {
            transform: rotate(360deg);
          }
        }
</style>

<div class="lds-ring"><div></div><div></div><div></div><div></div><span id="titleloader">......</span></div>

<div class="modal hide fade" id="sms_code" style="width:410px; left:60%;">
    <div class="modal-header">
        <h3 class="tituloheader3">Autorización de pago <span id="id_autorization"></span></h3>
    </div>
    <div class="modal-body">
        <h4 class="titulodebecorbrar" style="font-size:27px !important; margin-bottom: 15px !important;
        margin-top: 0px !important;" >Solicitar código SMS al cliente</h4>
        <div class="control-group" id="modalbodysms_code">
            <div class="controls">
                <input type="text" name="codigo_sms" id="codigo_sms" style="width:80%; height:30px;" maxlength="6" autocomplete="off" />
                <input id="id_sms" type="hidden" value=""  autocomplete="off" />
            </div>
        </div>
    </div>
    <div class="modal-footer" id="modalfootersms_code">
        <input type="button" class="btn btn-success btn-large" style="width: 100% !important;" id="btn_check_code_sms" value="Comprobar autorización" />
    </div>
</div>

<div class="modal hide fade" id="nophone_modal" style="width:410px; left:60%;">
    <div class="modal-header">
        <h3 class="tituloheader3">Autorización de pago <span id="id_autorization"></span></h3>
    </div>
    <div class="modal-body">
        <h4 class="titulodebecorbrar" style="font-size:27px !important; margin-bottom: 15px !important;
        margin-top: 0px !important;" >Solicitar autorización a central</h4>
        <div class="control-group" id="modalbodysms_code">
            <div class="controls">
                <input type="text" name="codigo_sms" id="codigo_sms" style="width:80%; height:30px;" maxlength="6" autocomplete="off" />
                <input id="id_sms" type="hidden" value=""  autocomplete="off" />
            </div>
        </div>
    </div>
    <div class="modal-footer" id="modalfootersms_code">
        <input type="button" class="btn btn-success btn-large" style="width: 100% !important;" id="btn_solicitar_auto_sms" value="Solicitar autorización" />
    </div>
</div>