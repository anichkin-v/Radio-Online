<?php
// Функция для подключения к базе данных
function getDBConnection() {
    try {
        $dbPath = './db/stations.db';

        // Проверяем, существует ли база данных
        $dbExists = file_exists($dbPath);

        // Подключение к базе данных SQLite
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Устанавливаем режим по умолчанию

        // Если базы данных не было, создаем необходимые таблицы
        if (!$dbExists) {
            initializeTables($pdo);
        }

        return $pdo;
    } catch (PDOException $e) {
        die("Не удалось подключиться к базе данных: " . $e->getMessage());
    }
}

// Функция для инициализации таблиц
function initializeTables($pdo) {
    // Создание таблицы категорий
    $query = "
    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL
    );
    ";
    $pdo->exec($query);

    // Создание таблицы радиостанций
    $query = "
    CREATE TABLE IF NOT EXISTS stations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        logo TEXT NOT NULL,
        url TEXT NOT NULL,
        category_id INTEGER,
        `order` INTEGER DEFAULT 0,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    );
    ";
    $pdo->exec($query);

    echo "База данных и таблицы успешно созданы.";
}

// Функция для получения всех категорий
function getCategories() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM categories");
    return $stmt->fetchAll();
}

// Функция для получения всех станций с фильтрацией по категории
function getStations($category_id = null) {
    $pdo = getDBConnection();
    $query = "SELECT * FROM stations";

    if ($category_id) {
        $query .= " WHERE category_id = :category_id";
    }

    $query .= " ORDER BY `order` ASC"; // Сортировка по полю `order` прямо в запросе
    $stmt = $pdo->prepare($query);

    if ($category_id) {
        $stmt->execute(['category_id' => $category_id]);
    } else {
        $stmt->execute();
    }

    return $stmt->fetchAll();
}

// Функция для добавления категории
function addCategory($name) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
    $stmt->execute(['name' => $name]);
}

// Функция для добавления станции с обработкой ошибок
function addStation($name, $logo, $url, $category_id) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO stations (name, logo, url, category_id) VALUES (:name, :logo, :url, :category_id)");
        $stmt->execute([
            'name' => $name,
            'logo' => $logo,
            'url' => $url,
            'category_id' => $category_id
        ]);
        return true; // Если вставка прошла успешно
    } catch (PDOException $e) {
        error_log("Ошибка при добавлении станции: " . $e->getMessage());
        return false;
    }
}


// Функция для редактирования категории
function editCategory($id, $name) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE categories SET name = :name WHERE id = :id");
    $stmt->execute(['name' => $name, 'id' => $id]);
}

// Функция для редактирования станции
function editStation($id, $name, $logo, $url, $category_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE stations SET name = :name, logo = :logo, url = :url, category_id = :category_id WHERE id = :id");
    $stmt->execute([
        'name' => $name,
        'logo' => $logo,
        'url' => $url,
        'category_id' => $category_id,
        'id' => $id
    ]);
}

// Функция для удаления категории
function deleteCategory($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

// Функция для удаления станции
function deleteStation($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM stations WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

// Функция для обновления порядка станций
function updateStationOrder($station_id, $order) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE stations SET `order` = ? WHERE id = ?");
    $stmt->execute([$order, $station_id]);
}

// Функция для генерации плейлиста M3U8
function generateM3U8() {
    $pdo = getDBConnection();
    $file = fopen('playlist.m3u8', 'w');

    fwrite($file, "#EXTM3U\n#PLAYLIST:Radio OnLine\n");

    $categories = getCategories();

    foreach ($categories as $category) {
        fwrite($file, "\n#EXTGRP:" . $category['name'] . "\n");

        $stations = getStations($category['id']); // Получаем станции для категории с сортировкой по порядку

        foreach ($stations as $station) {
            fwrite($file, "#EXTINF:-1 tvg-logo=\"{$station['logo']}\" group-title=\"{$category['name']}\", {$station['name']}\n{$station['url']}\n");
        }
    }

    fclose($file); // Закрыть файл
}
?>