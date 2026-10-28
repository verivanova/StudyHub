document.getElementById('userForm').addEventListener('submit', function(event) {
            event.preventDefault(); 
            
            const login = document.getElementById('login').value;
            const pass = document.getElementById('pass').value;

            if (login === 'admin' && pass === 'admin') {
                localStorage.setItem('isAdmin', 'true');
                alert('Успешный вход!');
                window.location.href = 'index.html';
            } else {
                alert('Неверный логин или пароль (admin/admin)');
            }
        });