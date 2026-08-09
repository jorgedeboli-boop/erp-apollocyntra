/*
$("#firmar_cierre").live("click",function(event){
    event.preventDefault();
    var typeitem = $("#firmar_cierre").attr("data-typeitem");
    var iditem = "656565";
    var recibe_euros = "0000";
    console.log("aguarde3: 1");
    mostrarLoader("Solicitando firma, por favor aguarde...");
    insertarSignatureDocument(iditem, typeitem, recibe_euros);
});
*/

function testswal(){
    Swal.fire({
        title: "Solicitar firma!",   
        text: "Presione solicitar firma para que cliente pueda firmar desde el dispositivo de firma",  
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'Solicitar firma',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d'
    })
    .then((result) => {
       
    });
}
/*
$("#enviar_contrato").live("click",function(){
    var iditem = $("#enviar_contrato").attr("data-iditem");
    var typeitem = $("#enviar_contrato").attr("data-typeitem");
    enviaremailclienteSwal(iditem, typeitem);
});
*/
function enviaremailclienteSwal(iditem, typeitem){
    var typeitem = typeitem;
    var iditem = iditem;
    Swal.fire({
        title: 'Enviar por correo',
        text: 'Ingrese el correo electronico del cliente',
        input: 'email',
        inputPlaceholder: 'ingrese correo electronico del cliente',
        showCancelButton: true,
        confirmButtonText: 'Enviar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        inputValidator: (value) => {
            if (!value) {
                return 'Debe ingresar un correo electrónico'
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            sendemialpdf(iditem, typeitem, result.value);
        }
    })
}

function sendemialpdf(iditem, typeitem, email_destino){
    var typeitem = typeitem;
    var iditem = iditem;
    var email_destino = email_destino;
    
    var datos = {
        "typeitem": typeitem,
        "iditem": iditem,
        "email_destino": email_destino
    }; 
    
    $.ajax({
        type:'POST',   
        url: 'signatures/Send_document.php',
        data: datos,
        success:function(respuesta){
            if(respuesta.status == 'ok'){
                console.log("enviado");
            }else{
                console.log("no enviado");
            }
        }
    });
    
}
$(document).on("click", "#solicitar_firma", function(){
    var iditem = $("#solicitar_firma").attr("data-iditem");
    var typeitem = $("#solicitar_firma").attr("data-typeitem");
    var recibe_euros = $("#solicitar_firma").attr("data-recibe_euros");
    var sucursal_signature = $("#solicitar_firma").attr("data-sucursal_signature");
    if(typeitem === "lote"){
        var texto_parset = "el cliente";
    }else{
        var texto_parset = "el empleado";
    }
     
    Swal.fire({
        title: "Solicitar firma!",   
        text: "Presione solicitar firma para que " + texto_parset + " pueda firmar desde el dispositivo de firma",  
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'Solicitar firma',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d'
    })
    .then((result) => {
        if (result.isConfirmed) {
            console.log("aguarde3: 2");
            mostrarLoader("Solicitando firma, por favor aguarde...");
            insertarSignatureDocument(iditem, typeitem, recibe_euros, sucursal_signature);
        }
    });
    
});

$(document).on("click", "#solicitar_firma_existente", function(){
    var id_signature = $("#solicitar_firma_existente").attr("data-id_signature");
    
    var iditem = $("#solicitar_firma").attr("data-iditem");
    var typeitem = $("#solicitar_firma").attr("data-typeitem");
    var recibe_euros = $("#solicitar_firma").attr("data-recibe_euros");
    
    if(typeitem === "lote"){
        var texto_parset = "el cliente";
    }else{
        var texto_parset = "el empleado";
    }
     
    Swal.fire({
        title: "Solicitar firma!",   
        text: "Presione solicitar firma para que " + texto_parset + " pueda firmar desde el dispositivo de firma",  
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'Solicitar firma',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d'
    })
    .then((result) => {
        if (result.isConfirmed) {
            console.log("aguarde3: 3");
            mostrarLoader("Solicitando firma, por favor aguarde...");
            repetirSignatureDocument(id_signature, typeitem);
        }
    });
    
});

$(document).on("click", "#repetir_firma", function(){
    var id_signature = $("#repetir_firma").attr("data-id_signature");
    var typeitem = $("#repetir_firma").attr("data-typeitem");
    if(typeitem === "lote"){
        var texto_parset = "el cliente";
    }else{
        var texto_parset = "el empleado";
    }
     Swal.fire({
        title: "Repetir firma!",
         text: "Presione repetir firma para que " + texto_parset + " pueda firmar desde el dispositivo de firma",  
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'Repetir firma',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d'
    })
    .then((result) => {
        if (result.isConfirmed) {
            console.log("aguarde3: 4");
            mostrarLoader("Solicitando firma, por favor aguarde...");
            repetirSignatureDocument(id_signature, typeitem);
        }
    });
    
});

function repetirSignatureDocument(id_signature, typeitem){
     var datos = {
        "id_signature": id_signature, 
        "typeitem": typeitem
    };    
    $.ajax({
        type:'POST',   
        url:'signatures/repetirSignature.php',
        data: datos,
        success:function(respuesta){
            if(respuesta.status == 'ok'){
                var id_signature = respuesta.id_signature;
                updateCancelStateSignature(id_signature);
                setTimeout(consultarStateSignature(id_signature),5000);
            }else{
                console.log("check consultarSignature");
            }
        }
    });

}

function insertarSignatureDocument(iditem, typeitem, recibe_euros, sucursal_signature){
     var datos = {
        "iditem": iditem,
        "typeitem": typeitem,
         "recibe_euros": recibe_euros,
         "sucursal_signature": sucursal_signature
    };
    
    $.ajax({
        type:'POST',   
        url:'signatures/insertSignature.php',
        data: datos,
        success:function(respuesta){
            if(respuesta.status == 'ok'){
                var id_signature = respuesta.id_signature;
                consultarStateSignature(id_signature);
            }else{
                console.log("check consultarSignature");
            }
        }
    });

}

function insertarSignatureDocumentCreateLote(iditem, typeitem, recibe_euros, sucursal_signature) {
    // Devuelve una Promise<boolean> para poder hacer await/then y redireccionar.
    // true  => firma completada o autorizada sin firma
    // false => cancelada o error
    return new Promise(function(resolve) {
        var datos = {
            "iditem": iditem,
            "typeitem": typeitem,
            "recibe_euros": recibe_euros,
            "sucursal_signature": sucursal_signature
        };

        $.ajax({
            type: 'POST',
            url: 'signatures/insertSignature.php',
            data: datos,
            dataType: 'json',
            success: function(respuesta) {
                if (respuesta && respuesta.status === 'ok' && respuesta.id_signature) {
                    consultarStateSignatureCreateLote(respuesta.id_signature)
                        .then(function(ok) { resolve(!!ok); })
                        .catch(function() { resolve(false); });
                } else {
                    console.log("insertarSignatureDocumentCreateLote: check consultarSignature");
                    resolve(false);
                }
            },
            error: function() {
                resolve(false);
            }
        });
    });
}

function consultarStateSignatureCreateLote(id_signature) {
    // Devuelve Promise<boolean>: true cuando state_signature es true/autorizada_no_firma; false si cancelada.
    return new Promise(function(resolve) {
        var datos = { "id_signature": id_signature };

        $.ajax({
            type: 'POST',
            url: 'signatures/consultar_Signatures_doc.php',
            data: datos,
            dataType: 'json',
            success: function(respuesta) {
                if (respuesta && respuesta.status === 'ok') {
                    var id_signature_parset = respuesta.id_signature;
                    var state_signature = respuesta.state_signature;
                    var cancel_signature = respuesta.cancel_signature;

                    if (state_signature === "true" || state_signature === "autorizada_no_firma") {
                        resolve(true);
                        return;
                    }
                    if (cancel_signature === "true") {
                        resolve(false);
                        return;
                    }

                    setTimeout(function() {
                        consultarStateSignatureCreateLote(id_signature_parset).then(resolve);
                    }, 5000);
                } else {
                    console.log("consultarStateSignatureCreateLote: check consultarSignature");
                    setTimeout(function() {
                        consultarStateSignatureCreateLote(id_signature).then(resolve);
                    }, 5000);
                }
            },
            error: function() {
                setTimeout(function() {
                    consultarStateSignatureCreateLote(id_signature).then(resolve);
                }, 5000);
            }
        });
    });
}

function consultarStateSignature(id_signature){
           console.log("consultarStateSignature: "+id_signature);
           var datos = {
              "id_signature": id_signature
          };
          $.ajax({
              type:'POST',   
              url:'signatures/consultar_Signatures_doc.php',
              data: datos,
              success:function(respuesta){
                  console.log("consultarStateSignature: respuesta ajax 1");
                  if(respuesta.status == 'ok'){
                      var id_signature_Parset = respuesta.id_signature;
                      var state_signature = respuesta.state_signature;
                      var cancel_signature = respuesta.cancel_signature;
                      //location.reload();
                      if(state_signature === "true"){
                         console.log("consultarStateSignature: reload");
                           location.reload();
                      }else if(state_signature === "autorizada_no_firma"){
                         console.log("consultarStateSignature: reload autorizada_no_firma");
                           location.reload();
                      }else{
                           if(cancel_signature === "true"){
                                 console.log("consultarStateSignature: reload cancel_signature");
                                   location.reload();
                              }else{
                                 console.log("consultarStateSignature: setTimeout");
                                    setTimeout(consultarStateSignature(id_signature_Parset),5000);
                              }
                      }
                  }else{
                        console.log("consultarStateSignature: check consultarSignature");
                  }
              }
          });

}

function updateCancelStateSignature(id_signature){
    var datos = {
        "id_signature": id_signature
    };

    $.ajax({
        type:'POST',   
        url:'signatures/updateCancelStateSignature.php',
        data: datos,
        success:function(respuesta){

        }
    });

}

function insertarSignatureDocumentTest (){
    alert("test");
}
/*console.log("test");*/