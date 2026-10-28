document.addEventListener('DOMContentLoaded', () => {
    const materialsList = document.getElementById('materialsList');
    if (!materialsList) return; 

    const isAdmin = localStorage.getItem('isAdmin') === 'true';
    const addBtn = document.getElementById('addMaterialBtn');
    const modal = document.getElementById('materialModal');
    const form = document.getElementById('materialForm');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const modalTitle = document.getElementById('modalTitle');

    const imgFileInput = document.getElementById('mImgFile');
    const imgBase64Input = document.getElementById('mImgBase64');
    const imgPreview = document.getElementById('imgPreview');
    const previewImg = document.getElementById('previewImg');

    if (isAdmin) {
        if (addBtn) addBtn.style.display = 'block';
        
        const loginBtn = document.querySelector('.header-right .second-btn');
        if (loginBtn) {
            loginBtn.textContent = 'Выйти';
            loginBtn.onclick = (event) => {
                event.preventDefault();
                localStorage.removeItem('isAdmin');
                window.location.href = 'index.html'; 
            };
        }
    } else {
        if (addBtn) addBtn.style.display = 'none';
    }

    const defaultMaterials = [
        { id: '1', title: 'Основы HTML и CSS', desc: 'Краткий конспект по основам HTML и CSS...', category: 'prog', categoryName: 'Программирование', img: '../pic/studyhub_assets/2026-10-23_17-46-25.png' },
        { id: '2', title: 'Высшая математика', desc: 'Сборник формул, теорем и примеров...', category: 'math', categoryName: 'Математика', img: '../pic/2026-10-23_17-48-01.png' }
    ];

    let materials = JSON.parse(localStorage.getItem('materials'));
    if (!materials) {
        materials = defaultMaterials;
        localStorage.setItem('materials', JSON.stringify(materials));
    }

    function compressImage(base64Str, maxWidth = 600, maxHeight = 600, quality = 0.7) {
        return new Promise((resolve) => {
            const img = new Image();
            img.src = base64Str;
            img.onload = () => {
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height *= maxWidth / width));
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width *= maxHeight / height));
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                resolve(canvas.toDataURL('image/jpeg', quality));
            };
            img.onerror = () => resolve(base64Str);
        });
    }

    imgFileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 4 * 1024 * 1024) {
                alert('Файл слишком большой (макс. 4 МБ).');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = async function(event) {
                const originalBase64 = event.target.result;
                const compressedBase64 = await compressImage(originalBase64);
                
                imgBase64Input.value = compressedBase64; 
                
                previewImg.src = compressedBase64;
                imgPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    function renderMaterials() {
        materialsList.innerHTML = ''; 
        materials.forEach(m => {
            const card = document.createElement('div');
            card.className = 'popular-card';
            
            let adminButtons = '';
            if (isAdmin) {
                adminButtons = `
                    <div style="margin-top: 15px; display: flex; gap: 10px; width: 100%;">
                        <button onclick="editMaterial('${m.id}')" style="background: #4169F5; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; position: static; font-size: 14px;">✏️ Ред.</button>
                        <button onclick="deleteMaterial('${m.id}')" style="background: #ff4d4d; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; position: static; font-size: 14px;">🗑️ Удалить</button>
                    </div>
                `;
            }

            card.innerHTML = `
                <img src="${m.img}" alt="${m.title}" style="object-fit: cover;">
                <p class="category-icon ${m.category}">${m.categoryName}</p>
                <p>${m.title}</p>
                <p class="second-text">${m.desc}</p>
                <button onclick="window.location.href='material.html'" class="main-btn" style="position: relative; left: 85%; border-radius: 50%; padding: 8px 12px; font-size: 20px;">⭢</button>
                ${adminButtons}
            `;
            materialsList.appendChild(card);
        });
    }

    renderMaterials();

    if (isAdmin) {
        addBtn.addEventListener('click', () => {
            form.reset(); 
            document.getElementById('materialId').value = '';
            imgBase64Input.value = ''; 
            imgPreview.style.display = 'none';
            imgFileInput.value = ''; 
            
            modalTitle.textContent = 'Добавить материал';
            modal.style.display = 'flex';
        });

        closeModalBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = document.getElementById('materialId').value;
            const title = document.getElementById('mTitle').value;
            const desc = document.getElementById('mDesc').value;
            const category = document.getElementById('mCategory').value;
            
            let img = imgBase64Input.value; 

            const categoryNames = {
                'prog': 'Программирование', 'math': 'Математика', 
                'lang': 'Иностранные языки', 'eco': 'Экономика', 'other': 'Другое'
            };

            if (id) {
                const existing = materials.find(m => m.id === id);
                if (!img && existing) img = existing.img;

                materials = materials.map(m => m.id === id ? { ...m, title, desc, category, categoryName: categoryNames[category], img } : m);
            } else {
                if (!img) {
                    alert('Пожалуйста, выберите картинку для обложки.');
                    return;
                }
                materials.push({ id: Date.now().toString(), title, desc, category, categoryName: categoryNames[category], img });
            }

            localStorage.setItem('materials', JSON.stringify(materials));
            modal.style.display = 'none';
            renderMaterials();
        });
    }

    window.editMaterial = (id) => {
        const m = materials.find(mat => mat.id === id);
        if (m && modal) {
            document.getElementById('materialId').value = m.id;
            document.getElementById('mTitle').value = m.title;
            document.getElementById('mDesc').value = m.desc;
            document.getElementById('mCategory').value = m.category;
            
            imgFileInput.value = ''; 
            imgBase64Input.value = m.img; 
            
            previewImg.src = m.img;
            imgPreview.style.display = 'block';

            modalTitle.textContent = 'Редактировать материал';
            modal.style.display = 'flex';
        }
    };

    window.deleteMaterial = (id) => {
        if (confirm('Вы уверены, что хотите удалить этот материал?')) {
            materials = materials.filter(m => m.id !== id);
            localStorage.setItem('materials', JSON.stringify(materials));
            renderMaterials();
        }
    };
});