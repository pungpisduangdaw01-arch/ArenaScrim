// คำนวณเปอร์เซ็นต์ในฟอร์มบันทึกผลการซ้อมอัตโนมัติ
// - % เงิน / % ดาเมจ / % รับดาเมจ = ค่าของผู้เล่น ÷ ผลรวมของทั้งทีม
// - % ร่วมไฟต์ = (Kill + Assist) ÷ Kill รวมของทีม
// คำนวณเฉพาะผู้เล่นที่เลือกฮีโร่แล้ว และเฉพาะเมื่อทีมมีค่ารวมมากกว่า 0 (ไม่ทับค่าที่กรอกเองถ้ายังไม่ได้ใส่ตัวเลขดิบ)
(function () {
    const form = document.getElementById('matchForm');
    if (!form) return;

    const rows = () => Array.from(form.querySelectorAll('.player-row'))
        .filter(row => row.querySelector('select[name$="[hero]"]').value !== '');

    const num = (row, cls) => parseFloat(row.querySelector('.' + cls).value) || 0;
    const pct = (part, total) => (total > 0 ? (part / total) * 100 : 0).toFixed(1);

    function fill(sourceCls, targetCls) {
        const active = rows();
        const total = active.reduce((sum, row) => sum + num(row, sourceCls), 0);
        if (total <= 0) return;
        active.forEach(row => {
            row.querySelector('.' + targetCls).value = pct(num(row, sourceCls), total);
        });
    }

    function fillFight() {
        const active = rows();
        const teamKills = active.reduce((sum, row) => sum + num(row, 'calc-kills'), 0);
        if (teamKills <= 0) return;
        active.forEach(row => {
            const share = Math.min(100, (num(row, 'calc-kills') + num(row, 'calc-assists')) / teamKills * 100);
            row.querySelector('.share-fight').value = share.toFixed(1);
        });
    }

    form.addEventListener('input', function (e) {
        const cl = e.target.classList;
        if (cl.contains('calc-gold')) fill('calc-gold', 'share-gold');
        if (cl.contains('calc-damage')) fill('calc-damage', 'share-damage');
        if (cl.contains('calc-taken')) fill('calc-taken', 'share-taken');
        if (cl.contains('calc-kills') || cl.contains('calc-assists')) fillFight();
    });

    // ผู้เล่นที่ลงเล่นต้องกรอก Gold, MVP, ดาเมจที่ทำ และดาเมจที่รับมากกว่า 0 (เช็กก่อนส่ง เพื่อไม่ให้ข้อมูลที่กรอกหาย)
    form.addEventListener('submit', function (e) {
        const missing = rows().filter(row =>
            ['[gold]', '[mvp_score]', '[damage_dealt]', '[damage_taken]']
                .some(f => !(parseFloat(row.querySelector('input[name$="' + f + '"]').value) > 0))
        );
        if (missing.length) {
            e.preventDefault();
            alert('ผู้เล่นที่ลงเล่นต้องกรอก Gold, MVP, ดาเมจที่ทำ และดาเมจที่รับมากกว่า 0\n(ยังไม่ครบ ' + missing.length + ' คน)');
        }
    });

    // เปลี่ยนฮีโร่ (ลงเล่น / ไม่ได้ลงเล่น) แล้วคำนวณใหม่ทั้งหมด
    form.addEventListener('change', function (e) {
        if (e.target.name && e.target.name.endsWith('[hero]')) {
            fill('calc-gold', 'share-gold');
            fill('calc-damage', 'share-damage');
            fill('calc-taken', 'share-taken');
            fillFight();
        }
    });
})();
