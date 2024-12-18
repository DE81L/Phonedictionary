<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Create example table
$table_name = 'example_directory';
$display_table_name = 'Пример справочника';

// Check if table already exists
$stmt = $pdo->prepare("SHOW TABLES LIKE ?");
$stmt->execute([$table_name]);
if ($stmt->rowCount() == 0) {
    // Create the table
    $sql = "CREATE TABLE `$table_name` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `department` VARCHAR(255) NOT NULL,
        `position` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);

    // Add table metadata
    $stmt = $pdo->prepare("INSERT INTO table_metadata (table_name, display_table_name) VALUES (?, ?)");
    $stmt->execute([$table_name, $display_table_name]);

    // Define columns for metadata
    $basic_columns = [
        ['column_name' => 'department', 'data_type' => 'VARCHAR(255)', 'display_column_name' => 'Отдел'],
        ['column_name' => 'position', 'data_type' => 'VARCHAR(255)', 'display_column_name' => 'Должность'],
        ['column_name' => 'full_name', 'data_type' => 'VARCHAR(255)', 'display_column_name' => 'ФИО'],
        ['column_name' => 'phone', 'data_type' => 'VARCHAR(255)', 'display_column_name' => 'Телефон'],
        ['column_name' => 'email', 'data_type' => 'VARCHAR(255)', 'display_column_name' => 'Email']
    ];

    // Add column metadata
    $stmt = $pdo->prepare("INSERT INTO column_metadata (table_name, column_name, display_column_name, data_type) VALUES (?, ?, ?, ?)");
    foreach ($basic_columns as $column) {
        $stmt->execute([
            $table_name,
            $column['column_name'],
            $column['display_column_name'],
            $column['data_type']
        ]);
    }

    // Sample data
    $departments = [
        'IT', 'Бухгалтерия', 'HR', 'Продажи', 'Маркетинг', 'Логистика', 
        'Юридический', 'Финансы', 'Производство', 'Склад', 'Безопасность',
        'Администрация', 'Техподдержка', 'Аналитика', 'Разработка', 'Закупки',
        'Транспортный', 'Клиентский сервис', 'Документооборот', 'Обучение',
        'Планирование', 'Контроль качества', 'Исследования', 'Эксплуатация',
        'Проектный офис', 'Внешние связи', 'Хозяйственный', 'Архив', 'Канцелярия',
        'Медицинский'
    ];
    
    $positions = [
        'Менеджер', 'Специалист', 'Руководитель', 'Ассистент', 'Координатор',
        'Директор', 'Заместитель директора', 'Начальник отдела', 'Ведущий специалист',
        'Старший специалист', 'Младший специалист', 'Стажер', 'Консультант',
        'Аналитик', 'Инженер', 'Главный специалист', 'Администратор', 'Архивариус',
        'Делопроизводитель', 'Секретарь', 'Техник', 'Оператор', 'Контролер',
        'Методист', 'Эксперт', 'Супервайзер', 'Куратор', 'Технолог', 'Экономист',
        'Бухгалтер', 'Юрист', 'Программист', 'Системный администратор', 'Дизайнер',
        'Тестировщик'
    ];
    
    $first_names = [
        'Александр', 'Елена', 'Дмитрий', 'Ольга', 'Иван', 'Мария', 'Сергей', 'Анна',
        'Михаил', 'Татьяна', 'Андрей', 'Екатерина', 'Алексей', 'Наталья', 'Владимир',
        'Светлана', 'Николай', 'Юлия', 'Павел', 'Галина', 'Роман', 'Ирина', 'Виктор',
        'Людмила', 'Евгений', 'Валентина', 'Игорь', 'Надежда', 'Денис', 'Марина',
        'Артем', 'Оксана', 'Максим', 'Яна', 'Антон', 'Вера', 'Илья', 'Дарья',
        'Кирилл', 'Любовь', 'Никита', 'Софья', 'Олег', 'Полина', 'Константин',
        'Евгения', 'Георгий', 'Алина', 'Станислав', 'Ксения'
    ];
    
    $last_names = [
        'Иванов', 'Петров', 'Сидоров', 'Смирнов', 'Кузнецов', 'Попов', 'Васильев', 'Соколов',
        'Михайлов', 'Новиков', 'Федоров', 'Морозов', 'Волков', 'Алексеев', 'Лебедев',
        'Семенов', 'Егоров', 'Павлов', 'Козлов', 'Степанов', 'Николаев', 'Орлов',
        'Андреев', 'Макаров', 'Никитин', 'Захаров', 'Зайцев', 'Соловьев', 'Борисов', 'Яковлев',
        'Григорьев', 'Романов', 'Воробьев', 'Сергеев', 'Кузьмин', 'Фролов', 'Александров',
        'Дмитриев', 'Королев', 'Гусев', 'Киселев', 'Ильин', 'Максимов', 'Поляков', 'Сорокин',
        'Виноградов', 'Ковалев', 'Белов', 'Медведев', 'Антонов', 'Тарасов', 'Жуков',
        'Баранов', 'Филиппов', 'Комаров', 'Давыдов', 'Беляев', 'Герасимов', 'Богданов',
        'Осипов', 'Сидоренко', 'Матвеев', 'Титов', 'Марков', 'Миронов', 'Крылов'
    ];
    
    $middle_names = [
        'Александрович', 'Дмитриевич', 'Сергеевич', 'Иванович', 'Петрович',
        'Николаевич', 'Михайлович', 'Андреевич', 'Владимирович', 'Алексеевич',
        'Валентинович', 'Павлович', 'Викторович', 'Евгеньевич', 'Игоревич',
        'Константинович', 'Максимович', 'Данилович', 'Степанович', 'Георгиевич',
        'Артемович', 'Антонович', 'Кириллович', 'Олегович', 'Романович',
        'Александровна', 'Дмитриевна', 'Сергеевна', 'Ивановна', 'Петровна',
        'Николаевна', 'Михайловна', 'Андреевна', 'Владимировна', 'Алексеевна',
        'Валентиновна', 'Павловна', 'Викторовна', 'Евгеньевна', 'Игоревна',
        'Константиновна', 'Максимовна', 'Даниловна', 'Степановна', 'Георгиевна',
        'Артемовна', 'Антоновна', 'Кирилловна', 'Олеговна', 'Романовна'
    ];

    // Generate 1000 random entries
    $stmt = $pdo->prepare("INSERT INTO `$table_name` (department, position, full_name, phone, email) VALUES (?, ?, ?, ?, ?)");
    
    for ($i = 0; $i < 1000; $i++) {
        $department = $departments[array_rand($departments)];
        $position = $positions[array_rand($positions)];
        
        $first_name = $first_names[array_rand($first_names)];
        $last_name = $last_names[array_rand($last_names)];
        
        // Match middle name gender with first name
        $is_female = in_array(substr($first_name, -1), ['а', 'я']);
        $filtered_middle_names = array_filter($middle_names, function($middle_name) use ($is_female) {
            return $is_female === in_array(substr($middle_name, -3), ['вна']);
        });
        $middle_name = $filtered_middle_names[array_rand($filtered_middle_names)];
        
        $full_name = "$last_name $first_name $middle_name";
        
        // Generate more varied phone numbers
        $phone_formats = [
            '+7' . rand(900, 999) . sprintf('%07d', rand(0, 9999999)),
            '8' . rand(900, 999) . sprintf('%07d', rand(0, 9999999)),
            '+7 (' . rand(900, 999) . ') ' . sprintf('%03d', rand(0, 999)) . '-' . sprintf('%02d', rand(0, 99)) . '-' . sprintf('%02d', rand(0, 99)),
            '8 (' . rand(900, 999) . ') ' . sprintf('%03d', rand(0, 999)) . '-' . sprintf('%04d', rand(0, 9999))
        ];
        $phone = $phone_formats[array_rand($phone_formats)];
        
        // Generate email with company domain variations
        $email_domains = ['example.com', 'company.ru', 'corp.local', 'business.org', 'mail.ru', 
                         'office.net', 'department.org', 'enterprise.com', 'work.ru', 'team.local'];
        $email = strtolower(transliterate($first_name)) . '.' . 
                strtolower(transliterate($last_name)) . '@' . 
                $email_domains[array_rand($email_domains)];

        $stmt->execute([$department, $position, $full_name, $phone, $email]);
    }
}

// Helper function to transliterate Russian names to Latin
function transliterate($string) {
    $converter = array(
        'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
        'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
        'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
        'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
        'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
        'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
        'э' => 'e',    'ю' => 'yu',   'я' => 'ya',
        'А' => 'A',    'Б' => 'B',    'В' => 'V',    'Г' => 'G',    'Д' => 'D',
        'Е' => 'E',    'Ё' => 'E',    'Ж' => 'Zh',   'З' => 'Z',    'И' => 'I',
        'Й' => 'Y',    'К' => 'K',    'Л' => 'L',    'М' => 'M',    'Н' => 'N',
        'О' => 'O',    'П' => 'P',    'Р' => 'R',    'С' => 'S',    'Т' => 'T',
        'У' => 'U',    'Ф' => 'F',    'Х' => 'H',    'Ц' => 'C',    'Ч' => 'Ch',
        'Ш' => 'Sh',   'Щ' => 'Sch',  'Ь' => '',     'Ы' => 'Y',    'Ъ' => '',
        'Э' => 'E',    'Ю' => 'Yu',   'Я' => 'Ya'
    );
    return strtr($string, $converter);
}

// Redirect to the new table
header("Location: index.php?table=$table_name");
exit;
?> 