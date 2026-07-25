// Convenience only. Every rule here is also enforced on the server in
// index.php — a browser check can be bypassed with any HTTP client and is not
// a security control.
window.addEventListener('DOMContentLoaded', () => {
    const loginPanel = document.querySelector('.put');
    const registerPanel = document.querySelector('#register-panel');
    const showRegister = document.querySelector('#show-register');
    const showLogin = document.querySelector('#show-login');

    if (showRegister && loginPanel && registerPanel) {
        showRegister.addEventListener('click', () => {
            loginPanel.style.display = 'none';
            registerPanel.style.display = 'block';
        });
    }

    if (showLogin && loginPanel && registerPanel) {
        showLogin.addEventListener('click', () => {
            registerPanel.style.display = 'none';
            loginPanel.style.display = 'block';
        });
    }

    // The old version blocked < > and () in the password fields. Passwords are
    // hashed and never rendered as HTML, so those characters are harmless there
    // — the rule only shrank the pool of usable passwords. Length is checked
    // instead.
    const password = document.querySelector('#pass1');
    const confirmPassword = document.querySelector('#repass1');

    if (password && confirmPassword) {
        const compare = () => {
            confirmPassword.setCustomValidity(
                confirmPassword.value !== '' && confirmPassword.value !== password.value
                    ? '兩次輸入的密碼不相同'
                    : ''
            );
        };

        // Bound to both fields. The old code registered a handler on #pass1 but
        // read and cleared #pass inside it, so the registration password was
        // never actually validated.
        password.addEventListener('input', compare);
        confirmPassword.addEventListener('input', compare);
    }
});