<?php if ($show_create_admin_modal): ?>
<!-- Create Admin Modal -->
<div id="createAdminModal" class="modal" tabindex="-1" style="display: block;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Создание администратора</h5>
            </div>
            <div class="modal-body">
                <form id="createAdminForm" method="post" action="create_admin.php">
                    <div class="mb-3">
                        <label class="form-label">Имя пользователя:</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль:</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Создать администратора</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Login Modal -->
<div id="loginModal" class="modal" tabindex="-1" <?php if (!$show_create_admin_modal && !isset($_SESSION['user']) && empty($tables)) echo 'style="display: block;"'; ?>>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Вход</h5>
                <button type="button" class="btn-close login-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="login.php">
                    <div class="mb-3">
                        <label class="form-label">Имя пользователя:</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль:</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Войти</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Record Modal -->
<div id="modal" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modalTitle">Добавить запись</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="modalForm" method="post">
                    <input type="hidden" name="id" id="recordId">
                    <?php if ($columns): ?>
                        <?php foreach ($columns as $col => $display_col): ?>
                            <?php if ($col != 'id'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?= htmlspecialchars($display_col) ?>:</label>
                                    <?php if ($current_table == 'users' && $col == 'password'): ?>
                                        <input type="password" name="<?= $col ?>" class="form-control" required>
                                    <?php else: ?>
                                        <input type="text" name="<?= $col ?>" class="form-control" required>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-success">Сохранить</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Quick Info Modal -->
<div id="quickInfoModal" class="modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Редактировать информацию</h5>
                <button type="button" class="btn-close quickInfo-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="quickInfoForm" method="post" action="update_quick_info.php">
                    <input type="hidden" name="current_table" value="<?= htmlspecialchars($current_table ?? '') ?>">
                    <div class="mb-3">
                        <textarea name="content" id="quickInfoContent" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Сохранить</button>
                </form>
                <div class="markup-help mt-4">
                    <h5>Доступные функции:</h5>
                    <ul>
                        <li>Заголовок 1: <code>&lt;h1&gt;Текст&lt;/h1&gt;</code></li>
                        <li>Заголовок 2: <code>&lt;h2&gt;Текст&lt;/h2&gt;</code></li>
                        <li>Заголовок 3: <code>&lt;h3&gt;Текст&lt;/h3&gt;</code></li>
                        <li>Заголовок 4: <code>&lt;h4&gt;Текст&lt;/h4&gt;</code></li>
                        <li>Заголовок 5: <code>&lt;h5&gt;Текст&lt;/h5&gt;</code></li>
                        <li>Заголовок 6: <code>&lt;h6&gt;Текст&lt;/h6&gt;</code></li>
                        <li>Цветной текст: <code>&lt;color:цвет&gt;Текст&lt;/color&gt;</code> (цвет может быть названием или HEX кодом)</li>
                        <li>Перенос строки: <code>&lt;br&gt;</code></li>
                        <li>Изменение размера текста: <code>&lt;s:размер&gt;Текст&lt;/s&gt;</code> (размер в пикселях)</li>
                    </ul>
                    <h5>Примеры:</h5>
                    <p><code>&lt;h1&gt;Большой заголовок&lt;/h1&gt;</code> будет отображаться как:</p>
                    <h1>Большой заголовок</h1>
                    <p><code>&lt;color:#FF0000&gt;Красный текст&lt;/color&gt;</code> будет отображаться как:</p>
                    <span style="color:#FF0000;">Красный текст</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Table Modal -->
<div id="tableModal" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Создать таблицу по шаблону</h5>
                <button type="button" class="btn-close table-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="tableForm" method="post" action="add_table.php">
                    <div class="mb-3">
                        <label class="form-label">Имя таблицы (латиницей):</label>
                        <input type="text" name="table_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Отображаемое имя таблицы:</label>
                        <input type="text" name="display_table_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Выберите шаблон:</label>
                        <select name="template_name" id="templateSelect" class="form-select" required>
                            <option value="">-- Выберите шаблон --</option>
                            <?php
                            $stmt = $pdo->query("SELECT template_name, display_template_name FROM templates");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <option value="<?= htmlspecialchars($row['template_name']) ?>"><?= htmlspecialchars($row['display_template_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Создать таблицу</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Create Template Modal -->
<div id="templateModal" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Создать новый шаблон</h5>
                <button type="button" class="btn-close template-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm" method="post" action="add_template.php">
                    <div class="mb-3">
                        <label class="form-label">Имя шаблона (латиницей):</label>
                        <input type="text" name="template_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Отображаемое имя шаблона:</label>
                        <input type="text" name="display_template_name" class="form-control" required>
                    </div>
                    <div id="columnsContainer">
                        <h5>Колонки:</h5>
                        <button type="button" id="addColumnBtn" class="btn btn-secondary mb-2">Добавить колонку</button>
                    </div>
                    <button type="submit" class="btn btn-primary">Создать шаблон</button>
                </form>
            </div>
        </div>
    </div>
</div>
