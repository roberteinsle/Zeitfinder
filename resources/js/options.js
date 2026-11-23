// Dynamic date/time rows on the "create poll" and "manage poll" pages.
export function initOptionRows() {
    document.querySelectorAll('[data-option-rows]').forEach((container) => {
        const list = container.querySelector('[data-option-list]');
        const template = container.querySelector('template[data-option-template]');
        const name = container.dataset.optionRows;
        let counter = list.querySelectorAll('[data-option-row]').length;

        const addRow = (values = {}) => {
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('[data-option-row]');
            const index = counter++;
            row.querySelectorAll('[data-field]').forEach((input) => {
                const field = input.dataset.field;
                input.name = `${name}[${index}][${field}]`;
                if (values[field] !== undefined) {
                    input.value = values[field];
                }
            });
            list.appendChild(fragment);
            refresh();

            return row;
        };

        const refresh = () => {
            const rows = list.querySelectorAll('[data-option-row]');
            rows.forEach((row) => {
                const remove = row.querySelector('[data-remove-option]');
                if (remove) {
                    remove.hidden = container.hasAttribute('data-min-one') && rows.length <= 1;
                }
            });
            const counterEl = container.querySelector('[data-option-count]');
            if (counterEl) {
                counterEl.textContent = String(rows.length);
            }
        };

        container.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove-option]');
            if (remove) {
                remove.closest('[data-option-row]').remove();
                refresh();

                return;
            }

            const add = event.target.closest('[data-add-option]');
            if (add) {
                // Continue from the last row: next day, same times.
                const rows = list.querySelectorAll('[data-option-row]');
                const last = rows[rows.length - 1];
                const values = {};
                if (last) {
                    const date = last.querySelector('[data-field="date"]').value;
                    if (date) {
                        const next = new Date(`${date}T12:00:00`);
                        next.setDate(next.getDate() + (add.dataset.addOption === 'same-day' ? 0 : 1));
                        values.date = next.toISOString().slice(0, 10);
                    }
                    values.start = last.querySelector('[data-field="start"]').value;
                    values.end = last.querySelector('[data-field="end"]').value;
                }
                const row = addRow(values);
                row.querySelector('[data-field="date"]').focus();
            }
        });

        if (list.querySelectorAll('[data-option-row]').length === 0 && container.hasAttribute('data-min-one')) {
            addRow();
        }
        refresh();
    });
}
