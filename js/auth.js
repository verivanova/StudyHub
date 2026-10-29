document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('userForm');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const login = document.getElementById('login').value.trim();
        const password = document.getElementById('pass').value;
        const button = form.querySelector('button[type="submit"]');

        if (button) button.disabled = true;

        try {
            const response = await fetch('/php/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ login, password })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                window.location.href = result.redirect;
            } else {
                alert(result.message || 'Не удалось войти.');
            }
        } catch (error) {
            alert('Не удалось связаться с сервером. Проверь, запущен ли Apache.');
        } finally {
            if (button) button.disabled = false;
        }
    });
});
