<script type="text/javascript">
    
    $(document).ready(function() {
        
       
        
        
        $('#codigo_sms').val('');
        $('#id_sms').val('');
        
        
        
        function open_sms(){
            
            var state_validate = $("#basic_validate").valid();
            var not_mobile_get_parset = $('#not_mobile_get').val();
            if(state_validate===true){
                if( not_mobile_get_parset === "true" ){
                    console.log("orden: notSMS");
                    requestAuthorizationNotSMS();
                }else{
                    console.log("orden: sendSMS")
                    loadLoader("Enviando SMS");
                    sendSMS();
                }
            }
            
        }
        
        function requestAuthorizationNotSMS(){
            var idlote_sms = $('#idlote').val();
            var importe_sms = $('#iPorcentaje').val();
            var mensaje_sms = "PAGO DEL LOTE Nº " + idlote_sms + " POR EL IMPORTE " + importe_sms + "€";
            console.log("idlote_sms: " + idlote_sms);
            console.log("importe_sms: " + importe_sms);
            datos_sms = {
               "rel_item_sms": idlote_sms,
               "mensaje_sms": mensaje_sms,
               "estado_sms": "false",
               "estado_codigo": "false",
               "type_item_sms": "pago_lote",
               "autorizado_central": "true"
            };
            
            $.ajax({
               url: 'APIORO/sms/insert_codigo_solicitar.php',
               type: 'POST',
               data: datos_sms,
               success: function(data) {
                   
                   var statelogsms = data.statelogsms;
                   
                   var id_sms_parset = data.id_sms;
                   
                   $('#id_sms').val(id_sms_parset);
                   
                   $( "#id_autorization" ).text(id_sms_parset);
                   
                   if( statelogsms === "ok"){

                        loadLoader( "Solicitando autorización Nº: " + id_sms_parset );
                       
                   }else{
                        console.log( "ha ocurrido un error al solicitar la autorización" );
                   }
                   
                   comprobarvarinterval = setInterval(function () {
                        comprobarAutorizaciónNotSMS(id_sms_parset, idlote_sms);
                    }, 5000);
                   
               }
            });
            
            
        }
        
        function comprobarAutorizaciónNotSMS(id_sms_parset, idlote_sms){           
            datos_sms = {
               "id_sms_parset": id_sms_parset
            };
            $.ajax({
               url: 'APIORO/sms/consultar_codigo_solicitar.php',
               type: 'POST',
               data: datos_sms,
               success: function(data) {
                   var statelogsms = data.statelogsms;
                   var autorizado = data.autorizado;
                   var id_sms_parset = data.id_sms_parset;
                   if( autorizado === "true" ){
                       $(".lds-ring").hide();
                        loadLoaderDos("Autorizado, creando lote Nº "+idlote_sms+"...");
                        let start = Date.now();
                        console.log( "comprobarAutorizaciónNotSMS: " + start + " id_sms_parset: " + id_sms_parset );
                       clearInterval(comprobarvarinterval);
                       setTimeout(() => {
                          document.getElementById('basic_validate').submit();
                        }, 5000)
                       
                   }else if( autorizado === "cancelada" ){
                       let start = Date.now();
                       console.log( "CANCELADA: comprobarAutorizaciónNotSMS: " + start + " id_sms_parset: " + id_sms_parset );
                       $(".lds-ring").hide();
                       
                       $('#title_alert_universal').text("Atención!");
                       $('#texto_alert_universal').text("Autorización cancelada!");
                       $('#alert_universal').modal(true);
                       
                       clearInterval(comprobarvarinterval);
                       return false;
                       
                   }else{
                       let start = Date.now();
                       console.log( "ESPERANDO: comprobarAutorizaciónNotSMS: " + start + " id_sms_parset: " + id_sms_parset ); 
                   }
                   
               }
            });
            
        }
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
    
        $("#btn_check_code_sms").click(function(evento) {
            
            evento.preventDefault();
            loadLoader("Comprobando...");
            checkCodeSMS();
            
        });
       
        function loadLoader(title){
           var title = title;
           $("#titleloader").text(title);
           $(".lds-ring").show();         
       }  
        
        function loadLoaderDos(title){
           var title = title;
           $("#titleloader").text(title);
           $(".lds-ring").show();         
       } 
       
        function checkCodeSMS(){
           
           var id_sms = $('#id_sms').val();
           var codigo_sms = $('#codigo_sms').val();
           
           datos_sms = {
               "id_sms": id_sms,
               "codigo_sms": codigo_sms
           };
           
           $.ajax({
               url: 'APIORO/sms/check_codigo_autorizacion.php',
               type: 'POST',
               data: datos_sms,
               success: function(data) {
                   var same_code = data.same_code;
                   $(".lds-ring").hide();
                   if( same_code === "ok"){
                        $('#sms_code').modal("toggle");
                        loadLoaderDos("Creando lote...");
                        document.getElementById('basic_validate').submit();
                        return false;
                   }else{
                       alert( "el codigo NO es el mismo, intentelo nuevamente" );
                   }
               
               }
                   
           });
           
           
       }
       
        function sendSMS(){
           
           var idlote_sms = $('#idlote').val();
           var importe_sms = $('#iPorcentaje').val();
           var movil_sms = $('#telefono').val();
           
           // alert("movil_sms: " + movil_sms);
           
           datos_sms = {
               "idlote_sms": idlote_sms,
               "importe_sms": importe_sms,
               "movil_sms": movil_sms
           };
           
           $.ajax({
               url: 'https://goldservice.matermedia.app/sms.php',
               type: 'POST',
               data: datos_sms,
               success: function(data) {
                   var state_sms = data.statuqouo;
                   var codigo_sms = data.codigo_sms;
                   var id_autorization = data.id_autorization;
                   
                   if( state_sms === "ok"){
                       insertLogSMS(codigo_sms);
                       $(".lds-ring").hide();
                       $('#sms_code').modal("toggle");
                       $( "#codigo_sms" ).focus();
                       
                       console.log( "enviado" );
                   }else{
                       console.log( "no enviado" );
                   }
               }
           });
           
       }
       
        function insertLogSMS(codigo_sms){
           
           var idlote_sms = $('#idlote').val();
           var importe_sms = $('#iPorcentaje').val();
           var movil_sms = $('#telefono').val();
           var codigo_sms_parset = codigo_sms;
           
           datos_sms = {
               "rel_item_sms": idlote_sms,
               "importe_sms": importe_sms,
               "movil_sms": movil_sms,
               "codigo_sms": codigo_sms_parset,
               "estado_sms": "true",
               "estado_codigo": "true",
               "type_item_sms": "pago_lote"
           };
           
           $.ajax({
               url: 'APIORO/sms/insert_sms.php',
               type: 'POST',
               data: datos_sms,
               success: function(data) {
                   var statelogsms = data.statelogsms;
                   var id_sms_parset = data.id_sms;
                   $('#id_sms').val(id_sms_parset);
                   $( "#id_autorization" ).text(id_sms_parset);
                   if( statelogsms === "ok"){
                       console.log( "log SMS creado" );
                   }else{
                       console.log( "log SMS no creado" );
                   }
                   
                   setInterval(function(){
                       $(".lds-ring").hide();
                    }, 5000);
                   
               }
           });
           
       }
    
    });
    
</script>