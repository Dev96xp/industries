async function printClientLabel(name, address, city, state, zip, phone) {
    try {
        await qz.websocket.connect();
    } catch (e) {
        if (!qz.websocket.isActive()) {
            alert('QZ Tray is not running. Please start QZ Tray and try again.');
            return;
        }
    }

    try {
        const printers = await qz.printers.find('PM-241');
        const printer  = Array.isArray(printers) ? printers[0] : printers;

        if (!printer) {
            alert('PM-241-BT printer not found. Make sure it is connected.');
            return;
        }

        const cfg = qz.configs.create(printer);

        const addressLine = [city && state ? `${city}, ${state}` : city || state, zip]
            .filter(Boolean).join(' ');

        const labelWidth = 832; // 104mm at 203 DPI

        // Center x calculation: (labelWidth - text_chars * char_width_in_dots) / 2
        const cx = (text, charDots) => Math.max(10, Math.floor((labelWidth - text.length * charDots) / 2));

        const nameUpper  = name.toUpperCase();
        const nameX      = cx(nameUpper, 48);   // font 4 x2 = 24*2 = 48 dots/char
        const phoneX     = cx(phone || '', 32); // font 3 x2 = 16*2 = 32 dots/char
        const addressX   = cx(address || '', 12); // font 2 x1 = 12 dots/char
        const cityX      = cx(addressLine, 12);

        const tspl = [
            'SIZE 104 mm,60 mm',
            'GAP 3 mm,0',
            'DIRECTION 0',
            'CLS',
            // Name — font 4, 2x2 (~25% smaller than before), centered
            `TEXT ${nameX},10,"4",0,2,2,"${nameUpper}"`,
            // Phone — font 3, 2x2, centered
            phone ? `TEXT ${phoneX},90,"3",0,2,2,"${phone}"` : '',
            // Address — font 2, 1x1, centered
            address ? `TEXT ${addressX},180,"2",0,1,1,"${address}"` : '',
            addressLine ? `TEXT ${cityX},205,"2",0,1,1,"${addressLine}"` : '',
            'PRINT 1,1',
        ].filter(Boolean).join('\r\n');

        await qz.print(cfg, [{ type: 'raw', format: 'plain', data: tspl + '\r\n' }]);

    } catch (err) {
        alert('Print error: ' + err.message);
    }
}
