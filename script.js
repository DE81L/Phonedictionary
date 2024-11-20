document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modal");
    const addBtn = document.getElementById("addBtn");
    const closeBtns = document.querySelectorAll(".close");
    const editButtons = document.querySelectorAll(".editBtn");
    const deleteButtons = document.querySelectorAll(".deleteBtn");
    const modalForm = document.getElementById("modalForm");
    const tableModal = document.getElementById("tableModal");
    const addTableBtn = document.getElementById("addTableBtn");
    const tableCloseBtn = document.querySelector(".table-close");
    const deleteTableBtn = document.querySelector('.deleteTableBtn');
    const current_table = document.body.getAttribute('data-current-table');
    const searchInput = document.getElementById('searchInput');
    const loginModal = document.getElementById('loginModal');
    const loginBtn = document.getElementById('loginBtn');
    const loginCloseBtn = document.querySelector('.login-close');
    const quickInfoModal = document.getElementById('quickInfoModal');
    const quickInfoForm = document.getElementById('quickInfoForm');
    const editQuickInfoBtn = document.getElementById('editQuickInfoBtn');
    const quickInfoCloseBtn = document.querySelector('.quickInfo-close');

    // Открытие модального окна для добавления записи
    if (addBtn) {
        addBtn.onclick = () => {
            document.getElementById("modalTitle").innerText = "Добавить запись";
            modalForm.reset();
            modal.style.display = "flex";
            modalForm.action = "add_edit.php?action=add&table=" + current_table;
        };
    }

    // Открытие модального окна для редактирования записи
    editButtons.forEach(button => {
        button.onclick = () => {
            const id = button.getAttribute("data-id");
            document.getElementById("modalTitle").innerText = "Редактировать запись";
            modal.style.display = "flex";
            modalForm.action = "add_edit.php?action=edit&table=" + current_table;
            fetch('get_record.php?table=' + current_table + '&id=' + id)
                .then(response => response.json())
                .then(data => {
                    for (let key in data) {
                        if (modalForm.elements[key]) {
                            modalForm.elements[key].value = data[key];
                        }
                    }
                });
        };
    });

    // Закрытие модальных окон
    closeBtns.forEach(btn => {
        btn.onclick = () => {
            btn.parentElement.parentElement.style.display = "none";
        };
    });

    // Удаление записи
    deleteButtons.forEach(button => {
        button.onclick = () => {
            const id = button.getAttribute("data-id");
            if (confirm("Вы уверены, что хотите удалить запись?")) {
                window.location.href = "delete.php?table=" + current_table + "&id=" + id;
            }
        };
    });

    // Открытие модального окна для добавления таблицы
    if (addTableBtn) {
        addTableBtn.onclick = () => {
            tableModal.style.display = "flex";
        };
    }

    // Обработчик для кнопки удаления таблицы
    if (deleteTableBtn) {
        deleteTableBtn.onclick = () => {
            if (confirm('Вы точно хотите удалить таблицу?')) {
                // Перенаправляем на страницу подтверждения удаления
                window.location.href = 'confirm_delete_table.php?table=' + current_table;
            }
        };
    }

    // Закрытие модальных окон при клике вне их
    window.onclick = (event) => {
        if (event.target === modal) {
            modal.style.display = "none";
        }
        if (event.target === tableModal) {
            tableModal.style.display = "none";
        }
        if (event.target === quickInfoModal) {
            quickInfoModal.style.display = "none";
        }
        if (event.target === loginModal) {
            loginModal.style.display = "none";
        }
    };

    // Функционал поиска
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const filter = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let match = false;

                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(filter)) {
                        match = true;
                    }
                });

                row.style.display = match ? '' : 'none';
            });
        });
    }

    // Модальное окно быстрого информационного блока
    if (editQuickInfoBtn) {
        editQuickInfoBtn.addEventListener('click', () => {
            quickInfoModal.style.display = 'flex';
            // Получаем текущее содержимое
            fetch('get_quick_info.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('quickInfoContent').value = data;
                });
        });
    }

    if (quickInfoCloseBtn) {
        quickInfoCloseBtn.addEventListener('click', () => {
            quickInfoModal.style.display = 'none';
        });
    }

    // Открытие модального окна входа
    if (loginBtn) {
        loginBtn.onclick = () => {
            loginModal.style.display = 'flex';
        };
    }

    if (loginCloseBtn) {
        loginCloseBtn.onclick = () => {
            loginModal.style.display = 'none';
        };
    }

    // Resizable columns
    function makeColumnsResizable(table) {
        const cols = table.querySelectorAll('th');
        const tableHeight = table.offsetHeight;

        cols.forEach((col) => {
            const resizer = col.querySelector('.resizer');
            if (resizer) {
                resizer.addEventListener('mousedown', initResize);
            }

            let startX, startWidth;

            function initResize(e) {
                startX = e.pageX;
                startWidth = col.offsetWidth;
                document.documentElement.addEventListener('mousemove', doResize);
                document.documentElement.addEventListener('mouseup', stopResize);
            }

            function doResize(e) {
                const width = startWidth + e.pageX - startX;
                col.style.width = width + 'px';
            }

            function stopResize() {
                document.documentElement.removeEventListener('mousemove', doResize);
                document.documentElement.removeEventListener('mouseup', stopResize);
            }
        });
    }

    // Make columns resizable
    const table = document.querySelector('table');
    if (table) {
        makeColumnsResizable(table);
    }
});