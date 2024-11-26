<?php if ($show_create_admin_modal): ?>
<!-- Create Admin Modal -->
<div id="createAdminModal" class="modal" style="display: flex;">
    <div class="modal-content">
        <h2>Создание администратора</h2>
        <form id="createAdminForm" method="post" action="create_admin.php">
            <label>Имя пользователя: <input type="text" name="username" required></label><br>
            <label>Пароль: <input type="password" name="password" required></label><br>
            <button type="submit">Создать администратора</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Login Modal -->
<div id="loginModal" class="modal" <?php if (!$show_create_admin_modal && !isset($_SESSION['user']) && empty($tables)) echo 'style="display: flex;"'; ?>>
    <div class="modal-content">
        <span class="close login-close">&times;</span>
        <h2>Вход</h2>
        <form method="post" action="login.php">
            <label>Имя пользователя: <input type="text" name="username" required></label><br>
            <label>Пароль: <input type="password" name="password" required></label><br>
            <button type="submit">Войти</button>
        </form>
    </div>
</div>

<!-- Add/Edit Record Modal -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Добавить запись</h2>
        <form id="modalForm" method="post">
            <input type="hidden" name="id" id="recordId">
            <?php if ($columns): ?>
                <?php foreach ($columns as $col => $display_col): ?>
                    <?php if ($col != 'id'): ?>
                        <label><?= htmlspecialchars($display_col) ?>:
                            <?php if ($current_table == 'users' && $col == 'password'): ?>
                                <input type="password" name="<?= $col ?>" required>
                            <?php else: ?>
                                <input type="text" name="<?= $col ?>" required>
                            <?php endif; ?>
                        </label><br>
                    <?php endif; ?>
                <?php endforeach; ?>
                <button type="submit">Сохранить</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Quick Info Modal -->
<div id="quickInfoModal" class="modal">
    <div class="modal-content">
        <span class="close quickInfo-close">&times;</span>
        <h2>Редактировать информацию</h2>
        <div class="modal-body">
            <form id="quickInfoForm" method="post" action="update_quick_info.php">
                <textarea name="content" id="quickInfoContent" rows="5" required></textarea><br>
                <button type="submit">Сохранить</button>
            </form>
            <div class="markup-help">
                <h3>Доступные функции:</h3>
                <ul>
                    <li>Заголовок 1: <code>&lt;h1&gt;Текст&lt;/h1&gt;</code></li>
                    <li>Заголовок 2: <code>&lt;h2&gt;Текст&lt;/h2&gt;</code></li>
                    <li>Заголовок 3: <code>&lt;h3&gt;Текст&lt;/h3&gt;</code></li>
                    <li>Заголовок 4: <code>&lt;h4&gt;Текст&lt;/h4&gt;</code></li>
                    <li>Заголовок 5: <code>&lt;h5&gt;Текст&lt;/h5&gt;</code></li>
                    <li>Заголовок 6: <code>&lt;h6&gt;Текст&lt;/h6&gt;</code></li>
                    <li>Цветной текст: <code>&lt;color:цвет&gt;Текст&lt;/color&gt;</code> (цвет может быть названием или HEX кодом)</li>
                    <li>Перенос строки: <code>&lt;br&gt;</code></li>
                </ul>
                <h3>Примеры:</h3>
                <p><code>&lt;h1&gt;Большой заголовок&lt;/h1&gt;</code> будет отображаться как:</p>
                <h1>Большой заголовок</h1>
                <p><code>&lt;color:#FF0000&gt;Красный текст&lt;/color&gt;</code> будет отображаться как:</p>
                <span style="color:#FF0000;">Красный текст</span>
            </div>
        </div>
    </div>
</div>

<!-- Create Table Modal -->
<div id="tableModal" class="modal">
    <div class="modal-content">
        <span class="close table-close">&times;</span>
        <h2>Создать таблицу по шаблону</h2>
        <form id="tableForm" method="post" action="add_table.php">
            <label>Имя таблицы (латиницей): <input type="text" name="table_name" required></label><br>
            <label>Отображаемое имя таблицы: <input type="text" name="display_table_name" required></label><br>
            <label>Выберите шаблон:
                <select name="template_name" id="templateSelect" required>
                    <option value="">-- Выберите шаблон --</option>
                    <?php
                    $stmt = $pdo->query("SELECT template_name, display_template_name FROM templates");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <option value="<?= htmlspecialchars($row['template_name']) ?>"><?= htmlspecialchars($row['display_template_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </label><br>
            <button type="submit">Создать таблицу</button>
        </form>
    </div>
</div>

<!-- Create Template Modal -->
<div id="templateModal" class="modal">
    <div class="modal-content">
        <span class="close template-close">&times;</span>
        <h2>Создать новый шаблон</h2>
        <form id="templateForm" method="post" action="add_template.php">
            <label>Имя шаблона (латиницей): <input type="text" name="template_name" required></label><br>
            <label>Отображаемое имя шаблона: <input type="text" name="display_template_name" required></label><br>
            <div id="columnsContainer">
                <h3>Колонки:</h3>
                <button type="button" id="addColumnBtn">Добавить колонку</button>
            </div>
            <button type="submit">Создать шаблон</button>
        </form>
    </div>
</div>
