
document.addEventListener('DOMContentLoaded', () => {
    const deleteButtons = document.querySelectorAll(
        '.delete-material-button'
    );

    deleteButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const id = button.dataset.id;
            const title = button.dataset.title;

            const confirmed = confirm(
                `Ты действительно хочешь удалить материал «${title}»?`
            );

            if (!confirmed) return;

            button.disabled = true;

            try {
                const formData = new FormData();
                formData.append('id', id);

                const response = await fetch('/php/material_delete.php', {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();

                console.log('HTTP статус:', response.status);
                console.log('Ответ сервера:', responseText);

                let result;

                try {
                    result = JSON.parse(responseText);
                } catch {
                    throw new Error(
                        'Сервер вернул HTML вместо JSON. Проверь Console (F12).'
                    );
                }

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Ошибка удаления материала.'
                    );
                }

                alert(result.message);
                window.location.reload();

            } catch (error) {
                console.error('Ошибка удаления:', error);
                alert(error.message);
                button.disabled = false;
            }
        });
    });
});
