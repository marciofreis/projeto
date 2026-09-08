document.addEventListener('DOMContentLoaded', () => {
    const map = document.querySelector('.foot-map.is-editable');
    const input = document.getElementById('marcasJson');
    const list = document.getElementById('footMarksList');
    const picker = document.getElementById('footPicker');
    const pickerTitle = document.getElementById('footPickerTitle');
    const pickerTipo = document.getElementById('footPickerTipo');
    const pickerNota = document.getElementById('footPickerNota');
    if (!map || !input || !list) {
        return;
    }

    const tipos = {
        calo: 'Calo',
        rachadura: 'Rachadura',
        encravada: 'Unha encravada',
        micose: 'Micose',
        verruga: 'Verruga',
        fissura: 'Fissura',
        ulcera: 'Úlcera',
        inflamacao: 'Inflamação',
        outro: 'Outro',
    };
    const zonas = {
        hallux: 'Hálux',
        d2: '2º dedo',
        d3: '3º dedo',
        d4: '4º dedo',
        d5: '5º dedo',
        antepe: 'Antepé',
        arco: 'Arco',
        calcaneo: 'Calcanhar',
        interdigital: 'Interdígitos',
    };

    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[char]));

    let pendingZone = null;

    const readMarks = () => {
        try {
            const parsed = JSON.parse(input.value || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    };

    const render = (marks) => {
        input.value = JSON.stringify(marks);
        list.innerHTML = marks.map((mark, index) => {
            const pe = mark.pe === 'direito' ? 'Direito' : 'Esquerdo';
            const extra = mark.observacao ? ` · ${escapeHtml(mark.observacao)}` : '';
            return `<li data-index="${index}"><span class="mark-chip mark-${mark.tipo || 'outro'}">${tipos[mark.tipo] || 'Marca'}</span> ${pe} · ${zonas[mark.zona] || mark.zona}${extra}<button type="button" class="mark-remove" data-index="${index}" aria-label="Remover">&times;</button></li>`;
        }).join('');
        map.querySelectorAll('.foot-zone').forEach((zone) => {
            const has = marks.some((mark) => mark.pe === zone.dataset.pe && mark.zona === zone.dataset.zona);
            zone.classList.toggle('has-mark', has);
        });
    };

    const closePicker = () => {
        pendingZone = null;
        if (picker) {
            picker.hidden = true;
        }
    };

    map.addEventListener('click', (event) => {
        const zone = event.target.closest('.foot-zone');
        if (!zone || !picker) {
            return;
        }
        pendingZone = zone;
        pickerTitle.textContent = `${zone.dataset.pe === 'direito' ? 'Pé direito' : 'Pé esquerdo'} · ${zonas[zone.dataset.zona] || zone.dataset.zona}`;
        pickerNota.value = '';
        picker.hidden = false;
        pickerTipo.focus();
    });

    document.getElementById('footPickerCancel')?.addEventListener('click', closePicker);
    document.getElementById('footPickerSave')?.addEventListener('click', () => {
        if (!pendingZone) {
            return;
        }
        const marks = readMarks();
        marks.push({
            pe: pendingZone.dataset.pe,
            zona: pendingZone.dataset.zona,
            tipo: pickerTipo.value,
            observacao: pickerNota.value.trim(),
        });
        render(marks);
        closePicker();
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('.mark-remove');
        if (!button) {
            return;
        }
        const marks = readMarks();
        marks.splice(Number(button.dataset.index), 1);
        render(marks);
    });
});
