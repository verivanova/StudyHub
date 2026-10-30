
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
    const modalContent = modal.querySelector('.material-modal-content');

if (modalContent) {
    modalContent.addEventListener('wheel', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const maxScroll =
            modalContent.scrollHeight - modalContent.clientHeight;

        modalContent.scrollTop = Math.max(
            0,
            Math.min(maxScroll, modalContent.scrollTop + event.deltaY)
        );
    }, { passive: false });
}

    const materialFile = form.elements.material_file;
    const materialUrl = form.elements.material_url;

    const currentAttachment = document.createElement('div');
    currentAttachment.className = 'current-attachment';
    currentAttachment.hidden = true;
    message.parentNode.insertBefore(currentAttachment, message);

    function showCurrentAttachment(filePath) {
        currentAttachment.replaceChildren();
        currentAttachment.hidden = true;

        if (!filePath) return;

        const heading = document.createElement('strong');
        heading.textContent = 'Текущее вложение';

        const link = document.createElement('a');
        link.textContent = /^https:\/\//i.test(filePath)
            ? 'Открыть текущую ссылку'
            : 'Открыть текущий файл';

        if (/^https:\/\//i.test(filePath)) {
            link.href = filePath;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        } else if (/^\/uploads\/materials\/[a-zA-Z0-9._-]+$/.test(filePath)) {
            link.href = filePath;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';

            const filename = filePath.split('/').pop();
            link.textContent = filename;
        } else {
            return;
        }

        currentAttachment.append(heading, link);
        currentAttachment.hidden = false;
    }

    function openCreateForm() {
        form.reset();
        materialId.value = '';
        formTitle.textContent = 'Добавить материал';
        saveButton.textContent = 'Сохранить материал';
        message.textContent = '';
        showCurrentAttachment('');
        document.body.style.overflow = 'hidden';
        modal.hidden = false;
    }

    if (openButton) {
        openButton.addEventListener('click', openCreateForm);
    }

    function closeMaterialModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
}

closeButton.addEventListener('click', closeMaterialModal);

modal.addEventListener('click', (event) => {
    if (event.target === modal) {
        closeMaterialModal();
    }
});

    if (materialFile && materialUrl) {
        materialFile.addEventListener('change', () => {
            if (materialFile.files.length > 0) {
                materialUrl.value = '';
            }
        });

        materialUrl.addEventListener('input', () => {
            if (materialUrl.value.trim() !== '') {
                materialFile.value = '';
            }
        });
    }

    function featuresToText(value) {
        if (!value) return '';

        try {
            const parsed = JSON.parse(value);

            if (Array.isArray(parsed)) {
                return parsed.join('\n');
            }
        } catch (error) {
        }

        return value;
    }

    window.editMaterial = (material) => {
        form.reset();

        materialId.value = material.id;
        form.elements.title.value = material.title || '';
        form.elements.description.value = material.description || '';
        form.elements.category_id.value = String(material.category_id || '');
        form.elements.content.value = material.content || '';
        form.elements.features.value = featuresToText(material.features);
        form.elements.difficulty.value = material.difficulty || '';
        form.elements.tip.value = material.tip || '';

        const filePath = material.file_path || '';

        if (materialUrl) {
            materialUrl.value = /^https?:\/\//i.test(filePath)
                ? filePath
                : '';
        }

        showCurrentAttachment(filePath);

        formTitle.textContent = 'Редактировать материал';
        saveButton.textContent = 'Сохранить изменения';

        message.textContent =
            'Чтобы сохранить прежнее вложение, оставь поля загрузки пустыми.';
        document.body.style.overflow = 'hidden';
        modal.hidden = false;
    };

    document.querySelectorAll('.edit-material-button').forEach((button) => {
        button.addEventListener('click', () => {
            window.editMaterial({
                id: button.dataset.id,
                title: button.dataset.title,
                description: button.dataset.description,
                category_id: button.dataset.categoryId,
                content: button.dataset.content,
                features: button.dataset.features,
                difficulty: button.dataset.difficulty,
                tip: button.dataset.tip,
                file_path: button.dataset.filePath
            });
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const isEditing = materialId.value !== '';
        message.textContent = 'Сохраняем материал…';
        saveButton.disabled = true;

        try {
            const response = await fetch(
                isEditing
                    ? '/php/material_update.php'
                    : '/php/material_create.php',
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
                (isEditing
                    ? 'Материал обновлён!'
                    : 'Материал добавлен!');

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
