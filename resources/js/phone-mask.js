/**
 * Formats US phone numbers as (123)-456-7894 while typing, in any input marked with
 * `data-phone` (or inside an element marked with it, like a Flux input wrapper).
 * A leading country code 1 is dropped; longer international numbers are left untouched.
 * Runs in the capture phase so Livewire's wire:model reads the already formatted value.
 */
export function formatUsPhone(value) {
    let digits = value.replace(/\D/g, '');

    if (digits.length === 11 && digits.startsWith('1')) {
        digits = digits.slice(1);
    }

    if (digits.length > 10) {
        return value;
    }

    if (digits.length === 0) {
        return '';
    }

    if (digits.length <= 3) {
        return `(${digits}`;
    }

    if (digits.length <= 6) {
        return `(${digits.slice(0, 3)})-${digits.slice(3)}`;
    }

    return `(${digits.slice(0, 3)})-${digits.slice(3, 6)}-${digits.slice(6)}`;
}

document.addEventListener('input', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || !input.closest('[data-phone]')) {
        return;
    }

    const formatted = formatUsPhone(input.value);

    if (formatted !== input.value) {
        input.value = formatted;
    }
}, true);
