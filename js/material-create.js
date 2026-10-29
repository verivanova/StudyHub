
document.addEventListener('DOMContentLoaded', () => {
    const openButton = document.getElementById('open-material-form');
    const modal = document.getElementById('material-modal');
    const closeButton = document.getElementById('close-material-form');
    const form = document.getElementById('material-create-form');
    const message = document.getElementById('material-form-message');
    const saveButton = document.getElementById('save-material-button');
    const formTitle = document.getElementById('material-form-title');
    const materialId = document.getElementById('material-id');

    if (!modal || !closeButton || !form || !message || !saveButton) {
        return;
    }

    function openCreateForm() {
        form.reset();
        materialId.value = '';
        formTitle.textContent = 'Добавить материал';
        saveButton.textContent = 'Сохранить материал';
        message.textContent = '';
        modal.hidden = false;
    }

    if (openButton) {
        openButton.addEventListener('click', openCreateForm);
    }

    closeButton.addEventListener('click', () => {
        modal.hidden = true;
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.hidden = true;
        }
    });

    // Подготавливаем форму для редактирования выбранного материала.
    window.editMaterial = (material) => {
        form.reset();

        materialId.value = material.id;
        form.elements.title.value = material.title || '';
        form.elements.description.value = material.description || '';
        form.elements.category_id.value = String(material.category_id || '');
        form.elements.content.value = material.content || '';
        form.elements.features.value = material.features || '';

        formTitle.textContent = 'Редактировать материал';
        saveButton.textContent = 'Сохранить изменения';
        message.textContent = 'Если обложку менять не нужно, оставь поле пустым.';
        modal.hidden = false;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const isEditing = materialId.value !== '';
        message.textContent = 'Сохраняем материал…';
        saveButton.disabled = true;

        try {
            const response = await fetch(
                isEditing ? '/php/material_update.php' : '/php/material_create.php',
                {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin'
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Не удалось сохранить материал.'
                );
            }

            message.textContent = result.message ||
                (isEditing ? 'Материал обновлён!' : 'Материал добавлен!');

            setTimeout(() => {
                window.location.reload();
            }, 700);
        } catch (error) {
            message.textContent =
                error.message || 'Ошибка соединения с сервером.';
        } finally {
            saveButton.disabled = false;
        }
    });
});
