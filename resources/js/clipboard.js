export function initClipboard() {
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.getElementById(button.dataset.copy);
            const text = target?.value ?? target?.textContent ?? '';
            try {
                await navigator.clipboard.writeText(text.trim());
            } catch {
                target?.select?.();
                document.execCommand('copy');
            }
            const label = button.querySelector('[data-copy-label]');
            if (label) {
                const original = label.textContent;
                label.textContent = button.dataset.copied || 'Kopiert';
                window.setTimeout(() => (label.textContent = original), 2000);
            }
        });
    });

    document.querySelectorAll('[data-share]').forEach((button) => {
        if (!navigator.share) {
            button.hidden = true;

            return;
        }
        button.addEventListener('click', () => {
            navigator.share({ title: button.dataset.shareTitle, url: button.dataset.share }).catch(() => {});
        });
    });
}
