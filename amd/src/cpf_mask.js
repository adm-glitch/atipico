/**
 * CPF field masking and validation for signup/profile pages.
 * Applies mask 000.000.000-00 to #id_profile_field_cpf.
 */
define([], function() {
    'use strict';

    /**
     * Apply CPF mask: digits only, formatted as 000.000.000-00
     */
    function applyMask(value) {
        var digits = value.replace(/\D/g, '').substring(0, 11);
        if (digits.length <= 3)  { return digits; }
        if (digits.length <= 6)  { return digits.slice(0,3) + '.' + digits.slice(3); }
        if (digits.length <= 9)  { return digits.slice(0,3) + '.' + digits.slice(3,6) + '.' + digits.slice(6); }
        return digits.slice(0,3) + '.' + digits.slice(3,6) + '.' + digits.slice(6,9) + '-' + digits.slice(9);
    }

    /**
     * Validate CPF checksum (Brazilian algorithm).
     * Returns true if the CPF is mathematically valid.
     */
    function isValidCPF(cpf) {
        var digits = cpf.replace(/\D/g, '');
        if (digits.length !== 11) { return false; }
        // All same digits are invalid
        if (/^(\d)\1{10}$/.test(digits)) { return false; }

        var sum, rest, i;

        // First digit check
        sum = 0;
        for (i = 0; i < 9; i++) { sum += parseInt(digits[i]) * (10 - i); }
        rest = (sum * 10) % 11;
        if (rest === 10 || rest === 11) { rest = 0; }
        if (rest !== parseInt(digits[9])) { return false; }

        // Second digit check
        sum = 0;
        for (i = 0; i < 10; i++) { sum += parseInt(digits[i]) * (11 - i); }
        rest = (sum * 10) % 11;
        if (rest === 10 || rest === 11) { rest = 0; }
        if (rest !== parseInt(digits[10])) { return false; }

        return true;
    }

    return {
        init: function() {
            var input = document.getElementById('id_profile_field_cpf');
            if (!input) { return; }

            // Set placeholder and input attributes
            input.setAttribute('placeholder', '000.000.000-00');
            input.setAttribute('maxlength', '14');
            input.setAttribute('inputmode', 'numeric');

            // Apply mask on every keystroke
            input.addEventListener('input', function() {
                var pos = input.selectionStart;
                var raw = input.value;
                var masked = applyMask(raw);
                input.value = masked;
                // Keep cursor in a sensible position
                try { input.setSelectionRange(pos, pos); } catch(e) {}
            });

            // Validate on blur — show inline error
            input.addEventListener('blur', function() {
                var existing = input.parentNode.querySelector('.cpf-error');
                if (existing) { existing.remove(); }

                var val = input.value.trim();
                if (val === '') { return; } // required check handled by Moodle

                if (!isValidCPF(val)) {
                    input.style.borderColor = '#e74c3c';
                    var err = document.createElement('span');
                    err.className = 'cpf-error';
                    err.style.cssText = 'color:#e74c3c;font-size:0.8rem;display:block;margin-top:4px;';
                    err.textContent = 'CPF inválido. Verifique os dígitos e tente novamente.';
                    input.parentNode.appendChild(err);
                } else {
                    input.style.borderColor = '';
                }
            });

            // Clear error on focus
            input.addEventListener('focus', function() {
                var existing = input.parentNode.querySelector('.cpf-error');
                if (existing) { existing.remove(); }
                input.style.borderColor = '';
            });
        }
    };
});
