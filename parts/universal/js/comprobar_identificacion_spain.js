/**
 * Script para validar identificaciones españolas
 * DNI, NIE, CIF y Pasaporte
 */

/**
 * Validar DNI español
 * Formato: 8 dígitos + 1 letra de control
 */
function validarDNI(dni) {
    const dniRegex = /^[0-9]{8}[A-Z]$/i;
    
    if (!dniRegex.test(dni)) {
        return { valido: false, mensaje: 'El DNI debe tener 8 dígitos seguidos de una letra' };
    }
    
    const numero = dni.substring(0, 8);
    const letraUsuario = dni.substring(8, 9).toUpperCase();
    const letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
    const letraCorrecta = letras[numero % 23];
    
    if (letraUsuario !== letraCorrecta) {
        return { valido: false, mensaje: 'La letra del DNI no es correcta. Debería ser: ' + letraCorrecta };
    }
    
    return { valido: true, mensaje: 'DNI válido' };
}

/**
 * Validar NIE español
 * Formato: 1 letra (X, Y, Z) + 7 dígitos + 1 letra de control
 */
function validarNIE(nie) {
    const nieRegex = /^[XYZ][0-9]{7}[A-Z]$/i;
    
    if (!nieRegex.test(nie)) {
        return { valido: false, mensaje: 'El NIE debe tener formato: X/Y/Z + 7 dígitos + letra' };
    }
    
    const nieUpper = nie.toUpperCase();
    let numero = nieUpper.substring(0, 8);
    
    // Reemplazar letra inicial por número
    numero = numero.replace('X', '0').replace('Y', '1').replace('Z', '2');
    
    const letraUsuario = nieUpper.substring(8, 9);
    const letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
    const letraCorrecta = letras[parseInt(numero) % 23];
    
    if (letraUsuario !== letraCorrecta) {
        return { valido: false, mensaje: 'La letra del NIE no es correcta. Debería ser: ' + letraCorrecta };
    }
    
    return { valido: true, mensaje: 'NIE válido' };
}

/**
 * Validar CIF español
 * Formato: 1 letra + 7 dígitos + 1 carácter de control (letra o número)
 */
function validarCIF(cif) {
    const cifRegex = /^[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J]$/i;
    
    if (!cifRegex.test(cif)) {
        return { valido: false, mensaje: 'El CIF debe tener formato: Letra + 7 dígitos + letra/número' };
    }
    
    const cifUpper = cif.toUpperCase();
    const letraInicial = cifUpper.substring(0, 1);
    const numeros = cifUpper.substring(1, 8);
    const controlUsuario = cifUpper.substring(8, 9);
    
    // Calcular suma de control
    let sumaPar = 0;
    let sumaImpar = 0;
    
    for (let i = 0; i < 7; i++) {
        const digito = parseInt(numeros[i]);
        
        if (i % 2 === 0) {
            // Posición impar (0, 2, 4, 6) - multiplicar por 2
            let doble = digito * 2;
            if (doble > 9) {
                doble = Math.floor(doble / 10) + (doble % 10);
            }
            sumaImpar += doble;
        } else {
            // Posición par (1, 3, 5) - sumar directamente
            sumaPar += digito;
        }
    }
    
    const sumaTotal = sumaPar + sumaImpar;
    const unidad = sumaTotal % 10;
    const digitoControl = (10 - unidad) % 10;
    
    // Tabla de letras de control
    const letrasControl = 'JABCDEFGHI';
    const letraControl = letrasControl[digitoControl];
    
    // Determinar tipo de control según letra inicial
    const soloNumero = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'U', 'V'];
    const soloLetra = ['N', 'P', 'Q', 'R', 'S', 'W'];
    
    let controlEsperado;
    if (soloNumero.includes(letraInicial)) {
        controlEsperado = digitoControl.toString();
    } else if (soloLetra.includes(letraInicial)) {
        controlEsperado = letraControl;
    } else {
        // K, L, M, X pueden ser letra o número
        controlEsperado = digitoControl.toString() + '/' + letraControl;
    }
    
    // Validar
    if (soloNumero.includes(letraInicial) && controlUsuario === digitoControl.toString()) {
        return { valido: true, mensaje: 'CIF válido' };
    } else if (soloLetra.includes(letraInicial) && controlUsuario === letraControl) {
        return { valido: true, mensaje: 'CIF válido' };
    } else if (!soloNumero.includes(letraInicial) && !soloLetra.includes(letraInicial)) {
        if (controlUsuario === digitoControl.toString() || controlUsuario === letraControl) {
            return { valido: true, mensaje: 'CIF válido' };
        }
    }
    
    return { valido: false, mensaje: 'El dígito/letra de control del CIF no es correcto. Debería ser: ' + controlEsperado };
}

/**
 * Validar Pasaporte
 * Formato: Alfanumérico, entre 5 y 20 caracteres
 */
function validarPasaporte(pasaporte) {
    const pasaporteRegex = /^[A-Z0-9]{5,20}$/i;
    
    if (!pasaporteRegex.test(pasaporte)) {
        return { valido: false, mensaje: 'El pasaporte debe ser alfanumérico, entre 5 y 20 caracteres' };
    }
    
    return { valido: true, mensaje: 'Pasaporte válido' };
}

/**
 * Función principal para validar identificación según tipo
 * @param {string} tipoId - Valor del select tipo_identificacion (1, 2, 3, 4)
 * @param {string} identificacion - Número de identificación a validar
 * @returns {object} { valido: boolean, mensaje: string }
 */
function validarIdentificacionSpain(tipoId, identificacion) {
    if (!identificacion || identificacion.trim() === '') {
        return { valido: false, mensaje: 'Debe introducir el número de identificación' };
    }
    
    switch (tipoId) {
        case '1':
            return validarDNI(identificacion);
        case '2':
            return validarNIE(identificacion);
        case '3':
            return validarPasaporte(identificacion);
        case '4':
            return validarCIF(identificacion);
        default:
            return { valido: false, mensaje: 'Tipo de identificación no reconocido' };
    }
}

