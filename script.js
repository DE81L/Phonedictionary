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
    const createTemplateBtn = document.getElementById("createTemplateBtn");
    const templateModal = document.getElementById("templateModal");
    const templateCloseBtn = document.querySelector(".template-close");
    const addColumnBtn = document.getElementById("addColumnBtn");
    const columnsContainer = document.getElementById("columnsContainer");
    const createAdminModal = document.getElementById('createAdminModal');
    const createAdminForm = document.getElementById('createAdminForm');

    if (createAdminModal) {
        createAdminModal.style.display = 'flex';
    }

    if (createTemplateBtn) {
        createTemplateBtn.addEventListener('click', () => {
            templateModal.style.display = "flex";
        });
    }

    if (templateCloseBtn) {
        templateCloseBtn.addEventListener('click', () => {
            templateModal.style.display = "none";
        });
    }

    if (addColumnBtn && columnsContainer) {
        addColumnBtn.onclick = () => {
            const timestamp = Date.now();
            const columnDiv = document.createElement("div");
            columnDiv.classList.add("column-entry");
    
            columnDiv.innerHTML = `
                <label>Имя колонки (латиницей): <input type="text" name="columns[${timestamp}][column_name]" required></label><br>
                <label>Отображаемое имя колонки: <input type="text" name="columns[${timestamp}][display_column_name]" required></label><br>
                <label>Тип данных:
                    <select name="columns[${timestamp}][data_type]" required>
                        <option value="VARCHAR(255)">VARCHAR(255)</option>
                        <option value="INT">INT</option>
                        <option value="TEXT">TEXT</option>
                        <option value="DATE">DATE</option>
                        <option value="DATETIME">DATETIME</option>
                    </select>
                </label><br>
                <button type="button" class="removeColumnBtn">Удалить колонку</button>
                <hr>
            `;
    
            columnsContainer.appendChild(columnDiv);
    
            const removeBtn = columnDiv.querySelector(".removeColumnBtn");
            removeBtn.onclick = () => {
                columnsContainer.removeChild(columnDiv);
            };
        };
    }
    
    
    if (addBtn) {
        addBtn.onclick = () => {
            document.getElementById("modalTitle").innerText = "Добавить запись";
            modalForm.reset();
            modal.style.display = "flex";
            modalForm.action = "add_edit.php?action=add&table=" + current_table;
        };
    }

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

    closeBtns.forEach(btn => {
        btn.onclick = () => {
            btn.parentElement.parentElement.style.display = "none";
        };
    });

    deleteButtons.forEach(button => {
        button.onclick = () => {
            const id = button.getAttribute("data-id");
            if (confirm("Вы уверены, что хотите удалить запись?")) {
                window.location.href = "delete.php?table=" + current_table + "&id=" + id;
            }
        };
    });

    if (addTableBtn) {
        addTableBtn.onclick = () => {
            tableModal.style.display = "flex";
        };
    }

    if (deleteTableBtn) {
        deleteTableBtn.onclick = () => {
            if (confirm('Вы точно хотите удалить таблицу?')) {
                window.location.href = 'confirm_delete_table.php?table=' + current_table;
            }
        };
    }

    window.onclick = (event) => {
        if (event.target === modal) {
            modal.style.display = "none";
        }
        if (event.target === tableModal) {
            tableModal.style.display = "none";
        }
        if (event.target === templateModal) {
            templateModal.style.display = "none";
        }
        if (event.target === quickInfoModal) {
            quickInfoModal.style.display = "none";
        }
        if (event.target === loginModal) {
            loginModal.style.display = "none";
        }
        if (event.target === createAdminModal) {
            createAdminModal.style.display = "none";
        }
    };

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

    if (editQuickInfoBtn) {
        editQuickInfoBtn.addEventListener('click', () => {
            quickInfoModal.style.display = 'flex';
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

    const table = document.querySelector('table');
    if (table) {
        makeColumnsResizable(table);
    }
});
