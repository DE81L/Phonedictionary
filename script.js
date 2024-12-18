document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modal");
    const addBtn = document.getElementById("addBtn");
    const editButtons = document.querySelectorAll(".editBtn");
    const deleteButtons = document.querySelectorAll(".deleteBtn");
    const modalForm = document.getElementById("modalForm");
    const tableModal = document.getElementById("tableModal");
    const addTableBtn = document.getElementById("addTableBtn");
    const deleteTableBtn = document.querySelector('.deleteTableBtn');
    const current_table = document.body.getAttribute('data-current-table');
    const loginModal = document.getElementById('loginModal');
    const loginBtn = document.getElementById('loginBtn');
    const quickInfoModal = document.getElementById('quickInfoModal');
    const editQuickInfoBtn = document.getElementById('editQuickInfoBtn');
    const createTemplateBtn = document.getElementById("createTemplateBtn");
    const templateModal = document.getElementById("templateModal");
    const addColumnBtn = document.getElementById("addColumnBtn");
    const columnsContainer = document.getElementById("columnsContainer");
    const examplesModal = document.getElementById('examplesModal');
    const showExamplesBtn = document.getElementById('showExamplesBtn');
    const selectAllCheckbox = document.getElementById('selectAll');
    const recordCheckboxes = document.querySelectorAll('.record-checkbox');
    const floatingDeleteBtn = document.getElementById('floatingDeleteBtn');
    const floatingSidebarToggleBtn = document.getElementById('floatingSidebarToggleBtn');

    const globalSearchInput = document.getElementById('globalSearchInput');
    const columnSearchContainer = document.getElementById('columnSearchContainer');
    const applySearchBtn = document.getElementById('applySearchBtn');
    const columnSearchIcons = document.querySelectorAll('.column-search-icon');

    const dataTable = document.getElementById('dataTable');

    let activeColumnSearches = {}; 

    function saveScrollPosition() {
        localStorage.setItem('scrollPosition', window.scrollY);
    }

    window.addEventListener('load', () => {
        const savedPosition = localStorage.getItem('scrollPosition');
        if (savedPosition !== null) {
            window.scrollTo(0, parseInt(savedPosition, 10));
            localStorage.removeItem('scrollPosition');
        }
    });

    if (createTemplateBtn) {
        createTemplateBtn.addEventListener('click', () => {
            templateModal.classList.add('show');
        });
    }

    const templateCloseBtn = document.querySelector(".template-close");
    if (templateCloseBtn) {
        templateCloseBtn.addEventListener('click', () => {
            templateModal.classList.remove('show');
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
            modal.classList.add('show');
            modalForm.action = "add_edit.php?action=add&table=" + current_table;
        };
    }

    editButtons.forEach(button => {
        button.onclick = () => {
            document.getElementById("modalTitle").innerText = "Редактировать запись";
            modal.classList.add('show');
            modalForm.action = "add_edit.php?action=edit&table=" + current_table;

            const id = button.getAttribute("data-id");
            fetch('get_record.php?table=' + current_table + '&id=' + id)
                .then(response => response.json())
                .then(data => {
                    for (let key in data) {
                        if (modalForm.elements[key]) {
                            modalForm.elements[key].value = data[key];
                        }
                    }
                    modalForm.elements['id'].value = id;
                });
        };
    });

    deleteButtons.forEach(button => {
        button.onclick = () => {
            const id = button.getAttribute("data-id");
            if (confirm("Вы уверены, что хотите удалить запись?")) {
                const currentPage = getCurrentPage();
                saveScrollPosition();
                fetch('delete.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        id: id,
                        table: current_table,
                        page: currentPage
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'index.php?table=' + current_table + '&page=' + data.page;
                    } else {
                        alert(data.message || 'Ошибка при удалении');
                    }
                });
            }
        };
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-close') || e.target.classList.contains('btn-close')) {
            const modalToClose = e.target.closest('.modal');
            if (modalToClose) {
                modalToClose.classList.remove('show');
            }
        }
    });

    if (addTableBtn) {
        addTableBtn.onclick = () => {
            tableModal.classList.add('show');
        };
    }

    if (deleteTableBtn) {
        deleteTableBtn.onclick = () => {
            if (confirm('Вы точно хотите удалить таблицу?')) {
                window.location.href = 'confirm_delete_table.php?table=' + current_table;
            }
        };
    }

    if (showExamplesBtn) {
        showExamplesBtn.addEventListener('click', () => {
            examplesModal.classList.add('show');
        });
    }

    const examplesCloseBtn = document.querySelector('.examples-close');
    if (examplesCloseBtn) {
        examplesCloseBtn.addEventListener('click', () => {
            examplesModal.classList.remove('show');
        });
    }

    if (editQuickInfoBtn) {
        editQuickInfoBtn.addEventListener('click', () => {
            quickInfoModal.classList.add('show');
            fetch('get_quick_info.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('quickInfoContent').value = data;
                });
        });
    }

    const quickInfoCloseBtn = document.querySelector('.quickInfo-close');
    if (quickInfoCloseBtn) {
        quickInfoCloseBtn.addEventListener('click', () => {
            quickInfoModal.classList.remove('show');
        });
    }

    if (loginBtn) {
        loginBtn.onclick = () => {
            loginModal.classList.add('show');
        };
    }

    const loginCloseBtn = document.querySelector('.login-close');
    if (loginCloseBtn) {
        loginCloseBtn.onclick = () => {
            loginModal.classList.remove('show');
        };
    }

    function getCurrentPage() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('page') || '1';
    }

    function updateFloatingDeleteBtnVisibility() {
        const checkedBoxes = document.querySelectorAll('.record-checkbox:checked');
        floatingDeleteBtn.style.display = (checkedBoxes.length > 0) ? 'block' : 'none';
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            const checkAll = selectAllCheckbox.checked;
            document.querySelectorAll('.record-checkbox').forEach(cb => cb.checked = checkAll);
            updateFloatingDeleteBtnVisibility();
        });
    }

    recordCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const allChecked = Array.from(document.querySelectorAll('.record-checkbox')).every(cb => cb.checked);
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
            }
            updateFloatingDeleteBtnVisibility();
        });
    });

    floatingDeleteBtn.addEventListener('click', () => {
        const checkedBoxes = document.querySelectorAll('.record-checkbox:checked');
        const ids = Array.from(checkedBoxes).map(cb => cb.getAttribute('data-id'));

        if (ids.length > 0 && confirm('Вы уверены, что хотите удалить выбранные записи?')) {
            const currentPage = getCurrentPage();
            saveScrollPosition();
            fetch('bulk_delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ids: ids,
                    table: current_table,
                    page: currentPage
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php?table=' + current_table + '&page=' + data.page;
                } else {
                    alert('Произошла ошибка при удалении записей');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при удалении записей');
            });
        }
    });

    columnSearchIcons.forEach(icon => {
        icon.addEventListener('click', () => {
            const columnName = icon.getAttribute('data-column');
            if (activeColumnSearches[columnName]) {
                columnSearchContainer.removeChild(activeColumnSearches[columnName]);
                delete activeColumnSearches[columnName];
            } else {
                const input = document.createElement('div');
                input.classList.add('mb-2');
                let displayName = icon.parentElement.textContent.trim();
                input.innerHTML = `
                    <label>Поиск по колонке "${displayName}": 
                        <input type="text" class="form-control column-search-input" data-column="${columnName}">
                    </label>
                `;
                columnSearchContainer.appendChild(input);
                activeColumnSearches[columnName] = input;
            }
            columnSearchContainer.style.display = Object.keys(activeColumnSearches).length > 0 ? 'block' : 'none';
        });
    });

    applySearchBtn.addEventListener('click', () => {
        const globalQuery = globalSearchInput.value.trim();
        const columnQueries = {};
        if (Object.keys(activeColumnSearches).length > 0) {
            const columnInputs = columnSearchContainer.querySelectorAll('.column-search-input');
            columnInputs.forEach(inp => {
                const col = inp.getAttribute('data-column');
                columnQueries[col] = inp.value.trim();
            });
        }

        // AJAX запрос на search.php
        fetch('search.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                table: current_table,
                globalQuery: globalQuery,
                columnQueries: columnQueries
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Обновляем таблицу
                const tbody = dataTable.querySelector('tbody');
                tbody.innerHTML = '';
                data.data.forEach(row => {
                    let tr = document.createElement('tr');
                    let checkboxTd = document.createElement('td');
                    let cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.classList.add('record-checkbox');
                    cb.setAttribute('data-id', row.id);
                    cb.addEventListener('change', updateFloatingDeleteBtnVisibility);
                    checkboxTd.appendChild(cb);
                    tr.appendChild(checkboxTd);

                    colNames.forEach(col => {
                        let td = document.createElement('td');
                        td.textContent = row[col] || '';
                        tr.appendChild(td);
                    });

                    if (actionColumn === true) {
                        let actionTd = document.createElement('td');
                        let btnGroup = document.createElement('div');
                        btnGroup.classList.add('btn-group');
                        const disable_edit = (current_table === 'users' && row.id == 1);
                        const disable_delete = (current_table === 'users' && row.id == 1);

                        if (!disable_edit) {
                            let editBtn = document.createElement('button');
                            editBtn.classList.add('btn','btn-sm','btn-edit','action-btn','editBtn');
                            editBtn.setAttribute('data-id', row.id);
                            editBtn.innerHTML = '<i class="bi bi-pencil-square"></i>';
                            editBtn.addEventListener('click', () => {
                                document.getElementById("modalTitle").innerText = "Редактировать запись";
                                modal.classList.add('show');
                                modalForm.action = "add_edit.php?action=edit&table=" + current_table;

                                fetch('get_record.php?table=' + current_table + '&id=' + row.id)
                                    .then(response => response.json())
                                    .then(data => {
                                        for (let key in data) {
                                            if (modalForm.elements[key]) {
                                                modalForm.elements[key].value = data[key];
                                            }
                                        }
                                        modalForm.elements['id'].value = row.id;
                                    });
                            });
                            btnGroup.appendChild(editBtn);
                        }

                        if (!disable_delete) {
                            let delBtn = document.createElement('button');
                            delBtn.classList.add('btn','btn-sm','btn-delete','action-btn','deleteBtn');
                            delBtn.setAttribute('data-id', row.id);
                            delBtn.innerHTML = '<i class="bi bi-trash"></i>';
                            delBtn.addEventListener('click', () => {
                                if (confirm("Вы уверены, что хотите удалить запись?")) {
                                    const currentPage = getCurrentPage();
                                    saveScrollPosition();
                                    fetch('delete.php', {
                                        method: 'POST',
                                        headers: {'Content-Type': 'application/json'},
                                        body: JSON.stringify({
                                            id: row.id,
                                            table: current_table,
                                            page: currentPage
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            window.location.href = 'index.php?table=' + current_table + '&page=' + data.page;
                                        } else {
                                            alert(data.message || 'Ошибка при удалении');
                                        }
                                    });
                                }
                            });
                            btnGroup.appendChild(delBtn);
                        }

                        actionTd.appendChild(btnGroup);
                        tr.appendChild(actionTd);
                    }

                    tbody.appendChild(tr);
                });

                updateFloatingDeleteBtnVisibility();
                // После поиска уберем пагинацию (так как показываем все результаты)
                let paginationNav = document.getElementById('paginationNav');
                if (paginationNav) paginationNav.style.display = 'none';
            } else {
                alert(data.message || 'Ошибка при поиске');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка при поиске');
        });
    });

    function makeColumnsResizable(table) {
        const cols = table.querySelectorAll('th');
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

    const table = document.getElementById('dataTable');
    if (table) {
        makeColumnsResizable(table);
    }

    // Логика скрытия/показа боковой панели
    floatingSidebarToggleBtn.addEventListener('click', () => {
        document.documentElement.classList.toggle('sidebar-collapsed');
    });

});
