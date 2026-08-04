// Validación de tarjetas de crédito
class CardValidator {
    constructor() {
        this.cardTypes = {
            visa: { pattern: /^4/, length: 16, cvvLength: 3, icon: '💳' },
            mastercard: { pattern: /^5[1-5]/, length: 16, cvvLength: 3, icon: '💳' },
            amex: { pattern: /^3[47]/, length: 15, cvvLength: 4, icon: '💳' }
        };
        
        this.currentYear = new Date().getFullYear();
        this.currentMonth = new Date().getMonth() + 1;
        this.populateYearOptions();
        this.setupEventListeners();
    }

    populateYearOptions() {
        const expYearSelect = document.getElementById('expYear');
        const yearsToShow = 10; // Mostrar 10 años hacia adelante
        
        for (let i = 0; i <= yearsToShow; i++) {
            const year = this.currentYear + i;
            const option = document.createElement('option');
            option.value = year.toString();
            option.textContent = year.toString();
            expYearSelect.appendChild(option);
        }
    }

    setupEventListeners() {
        const cardNumber = document.getElementById('cardNumber');
        const cardName = document.getElementById('cardName');
        const expMonth = document.getElementById('expMonth');
        const expYear = document.getElementById('expYear');
        const cvv = document.getElementById('cvv');
        const termsCheckbox = document.getElementById('termsCheckbox');

        // Formatear número de tarjeta
        cardNumber.addEventListener('input', (e) => {
            this.formatCardNumber(e);
            this.validateCardNumber();
            this.validateForm();
        });

        cardName.addEventListener('input', () => {
            this.validateCardName();
            this.validateForm();
        });

        expMonth.addEventListener('change', () => {
            this.validateExpirationDate();
            this.validateForm();
        });

        expYear.addEventListener('change', () => {
            this.validateExpirationDate();
            this.validateForm();
        });

        cvv.addEventListener('input', (e) => {
            this.validateCVV(e);
            this.validateForm();
        });

        termsCheckbox.addEventListener('change', () => {
            this.validateTerms();
            this.validateForm();
        });
    }

    formatCardNumber(e) {
        let value = e.target.value.replace(/\s+/g, '');
        value = value.replace(/\D/g, '');
        
        // Limitar según el tipo de tarjeta detectado
        const detectedType = this.detectCardType(value);
        if (detectedType) {
            const maxLength = detectedType === 'amex' ? 15 : 16;
            value = value.substring(0, maxLength);
        } else {
            value = value.substring(0, 19);
        }

        // Formatear con espacios cada 4 dígitos (excepto AMEX que es 4-6-5)
        if (detectedType === 'amex') {
            value = value.replace(/(\d{4})(\d{6})(\d+)/, '$1 $2 $3');
            value = value.replace(/(\d{4})(\d+)/, '$1 $2');
        } else {
            value = value.replace(/(\d{4})/g, '$1 ').trim();
        }

        e.target.value = value;
    }

    detectCardType(cardNumber) {
        const cleanNumber = cardNumber.replace(/\s+/g, '');
        
        if (this.cardTypes.visa.pattern.test(cleanNumber)) {
            return 'visa';
        } else if (this.cardTypes.mastercard.pattern.test(cleanNumber)) {
            return 'mastercard';
        } else if (this.cardTypes.amex.pattern.test(cleanNumber)) {
            return 'amex';
        }
        return null;
    }

    updateCardTypeIcon(cardNumber) {
        const iconElement = document.getElementById('cardTypeIcon');
        const type = this.detectCardType(cardNumber);
        
        if (type) {
            iconElement.textContent = this.cardTypes[type].icon;
            iconElement.classList.add('visible');
        } else {
            iconElement.classList.remove('visible');
        }
    }

    validateCardNumber() {
        const cardNumber = document.getElementById('cardNumber');
        const errorElement = document.getElementById('cardNumberError');
        const cleanNumber = cardNumber.value.replace(/\s+/g, '');
        
        this.updateCardTypeIcon(cleanNumber);
        
        if (cleanNumber.length === 0) {
            errorElement.textContent = 'El número de tarjeta es requerido';
            cardNumber.classList.add('error');
            return false;
        }

        const cardType = this.detectCardType(cleanNumber);
        
        if (!cardType) {
            errorElement.textContent = 'Tipo de tarjeta no válido (Visa, Mastercard o American Express)';
            cardNumber.classList.add('error');
            return false;
        }

        const expectedLength = this.cardTypes[cardType].length;
        
        if (cleanNumber.length !== expectedLength) {
            errorElement.textContent = `El número debe tener ${expectedLength} dígitos`;
            cardNumber.classList.add('error');
            return false;
        }

        // Validar algoritmo de Luhn
        if (!this.luhnCheck(cleanNumber)) {
            errorElement.textContent = 'Número de tarjeta inválido';
            cardNumber.classList.add('error');
            return false;
        }

        errorElement.textContent = '';
        cardNumber.classList.remove('error');
        return true;
    }

    luhnCheck(cardNumber) {
        let sum = 0;
        let isEven = false;

        for (let i = cardNumber.length - 1; i >= 0; i--) {
            let digit = parseInt(cardNumber[i]);

            if (isEven) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }

            sum += digit;
            isEven = !isEven;
        }

        return sum % 10 === 0;
    }

    validateCardName() {
        const cardName = document.getElementById('cardName');
        const errorElement = document.getElementById('cardNameError');
        const value = cardName.value.trim();

        if (value.length === 0) {
            errorElement.textContent = 'El nombre del titular es requerido';
            cardName.classList.add('error');
            return false;
        }

        if (value.length < 3) {
            errorElement.textContent = 'El nombre debe tener al menos 3 caracteres';
            cardName.classList.add('error');
            return false;
        }

        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(value)) {
            errorElement.textContent = 'El nombre solo puede contener letras';
            cardName.classList.add('error');
            return false;
        }

        errorElement.textContent = '';
        cardName.classList.remove('error');
        return true;
    }

    validateExpirationDate() {
        const expMonth = document.getElementById('expMonth');
        const expYear = document.getElementById('expYear');
        const monthError = document.getElementById('expMonthError');
        const yearError = document.getElementById('expYearError');
        
        let isValid = true;

        if (!expMonth.value) {
            monthError.textContent = 'Selecciona el mes';
            expMonth.classList.add('error');
            isValid = false;
        } else {
            monthError.textContent = '';
            expMonth.classList.remove('error');
        }

        if (!expYear.value) {
            yearError.textContent = 'Selecciona el año';
            expYear.classList.add('error');
            isValid = false;
        } else {
            yearError.textContent = '';
            expYear.classList.remove('error');
        }

        if (isValid && expMonth.value && expYear.value) {
            const selectedMonth = parseInt(expMonth.value);
            const selectedYear = parseInt(expYear.value);

            // Validar que el año sea 2025 en adelante
            if (selectedYear < 2025) {
                yearError.textContent = 'El año debe ser 2025 en adelante';
                expYear.classList.add('error');
                isValid = false;
            }

            // Validar que no sea una fecha pasada
            if (selectedYear === this.currentYear && selectedMonth < this.currentMonth) {
                monthError.textContent = 'La fecha de expiración no puede ser anterior a la fecha actual';
                expMonth.classList.add('error');
                isValid = false;
            }

            if (selectedYear < this.currentYear) {
                yearError.textContent = 'El año no puede ser anterior al año actual';
                expYear.classList.add('error');
                isValid = false;
            }
        }

        return isValid;
    }

    validateCVV(e) {
        const cvv = document.getElementById('cvv');
        const errorElement = document.getElementById('cvvError');
        const cardNumber = document.getElementById('cardNumber').value.replace(/\s+/g, '');
        
        let value = e ? e.target.value.replace(/\D/g, '') : cvv.value.replace(/\D/g, '');
        if (e) {
            e.target.value = value;
        } else {
            cvv.value = value;
        }

        if (value.length === 0) {
            errorElement.textContent = '';
            cvv.classList.remove('error');
            return false;
        }

        if (cardNumber.length === 0) {
            errorElement.textContent = 'Primero ingresa el número de tarjeta';
            cvv.classList.add('error');
            return false;
        }

        const cardType = this.detectCardType(cardNumber);
        if (!cardType) {
            errorElement.textContent = 'Primero ingresa un número de tarjeta válido';
            cvv.classList.add('error');
            return false;
        }

        const expectedLength = this.cardTypes[cardType].cvvLength;
        
        if (value.length < expectedLength) {
            errorElement.textContent = cardType === 'amex' 
                ? 'American Express requiere 4 dígitos' 
                : 'Visa/Mastercard requieren 3 dígitos';
            cvv.classList.add('error');
            return false;
        }

        if (value.length > expectedLength) {
            value = value.substring(0, expectedLength);
            cvv.value = value;
        }

        errorElement.textContent = '';
        cvv.classList.remove('error');
        return true;
    }

    validateTerms() {
        const termsCheckbox = document.getElementById('termsCheckbox');
        const errorElement = document.getElementById('termsError');

        if (!termsCheckbox.checked) {
            errorElement.textContent = 'Debes aceptar los términos y condiciones';
            return false;
        }

        errorElement.textContent = '';
        return true;
    }

    validateForm() {
        const isCardNameValid = this.validateCardName();
        const isCardNumberValid = this.validateCardNumber();
        const isExpirationValid = this.validateExpirationDate();
        const isCVVValid = this.validateCVV(null);
        const isTermsValid = this.validateTerms();

        const continueBtn = document.getElementById('continueBtn');
        const isValid = isCardNameValid && isCardNumberValid && isExpirationValid && isCVVValid && isTermsValid;

        continueBtn.disabled = !isValid;
        continueBtn.classList.toggle('active', isValid);

        return isValid;
    }
}

// Inicializar validador cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.cardValidator = new CardValidator();
});

