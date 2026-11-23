import { Passkeys, UserCancelledError } from '@laravel/passkeys';

function showError(container, error) {
    const target = container.querySelector('[data-passkey-error]');
    if (!target || error instanceof UserCancelledError) {
        return;
    }
    target.textContent = target.dataset.message || 'Das hat nicht geklappt. Bitte versuche es erneut.';
    target.hidden = false;
}

async function run(button, action) {
    button.disabled = true;
    try {
        await action();
    } catch (error) {
        showError(button.closest('[data-passkey-scope]') ?? document.body, error);
    } finally {
        button.disabled = false;
    }
}

export function initPasskeys() {
    const supported = Passkeys.isSupported();

    document.querySelectorAll('[data-passkey-unsupported]').forEach((el) => {
        el.hidden = supported;
    });
    document.querySelectorAll('[data-passkey-supported]').forEach((el) => {
        el.hidden = !supported;
    });

    if (!supported) {
        return;
    }

    // Sign in with a passkey.
    document.querySelectorAll('[data-passkey-login]').forEach((button) => {
        button.addEventListener('click', () =>
            run(button, async () => {
                const response = await Passkeys.verify({ remember: true });
                window.location.assign(response.redirect || '/konto');
            }),
        );
    });

    // Register a new passkey for the signed-in user.
    document.querySelectorAll('[data-passkey-register]').forEach((button) => {
        button.addEventListener('click', () =>
            run(button, async () => {
                const input = document.getElementById(button.dataset.passkeyRegister);
                const name = (input?.value || '').trim() || defaultName();
                await Passkeys.register({ name });
                if (button.dataset.then === 'confirm') {
                    const response = await confirm();
                    window.location.assign(response.redirect || '/admin');
                } else {
                    window.location.reload();
                }
            }),
        );
    });

    // Confirm the current session with an existing passkey (admin step-up).
    document.querySelectorAll('[data-passkey-confirm]').forEach((button) => {
        button.addEventListener('click', () =>
            run(button, async () => {
                const response = await confirm();
                window.location.assign(response.redirect || '/admin');
            }),
        );
    });
}

function confirm() {
    return Passkeys.verify({ routes: { options: '/passkeys/confirm/options', submit: '/passkeys/confirm' } });
}

function defaultName() {
    const ua = navigator.userAgent;
    if (/iPhone|iPad/.test(ua)) return 'iPhone/iPad';
    if (/Android/.test(ua)) return 'Android';
    if (/Mac/.test(ua)) return 'Mac';
    if (/Windows/.test(ua)) return 'Windows';

    return 'Passkey';
}
