<?php
require_once 'includes/functions.php';

$categories = getCategories();
$stations = getStations();

// Обработка удаления категории
if (isset($_POST['delete_category'])) {
    $category_id = $_POST['delete_category'];
    deleteCategory($category_id);
    generateM3U8(); // Перегенерация плейлиста
    header("Location: admin.php");
    exit();
}

// Обработка удаления станции
if (isset($_POST['delete_station'])) {
    $station_id = $_POST['delete_station'];
    deleteStation($station_id);
    generateM3U8(); // Перегенерация плейлиста
    header("Location: admin.php");
    exit();
}

// Обработка добавления категории
if (isset($_POST['categoryName'])) {
    $category_name = $_POST['categoryName'];
    addCategory($category_name);
    generateM3U8(); // Перегенерация плейлиста
    header("Location: admin.php");
    exit();
}


// Обработка редактирования категории
if (isset($_POST['editCategoryId'])) {
    $category_id = $_POST['editCategoryId'];
    $category_name = $_POST['editCategoryName'];
    editCategory($category_id, $category_name);
    generateM3U8(); // Перегенерация плейлиста
    header("Location: admin.php");
    exit();
}

// Обработка редактирования станции
if (isset($_POST['editStationId'])) {
    $station_id = $_POST['editStationId'];
    $station_name = $_POST['editStationName'];
    $station_url = $_POST['editStationUrl'];
    $station_logo = $_POST['editStationLogo'];
    $category_id = $_POST['editStationCategory'];
    editStation($station_id, $station_name, $station_logo, $station_url, $category_id);
    generateM3U8(); // Перегенерация плейлиста
    header("Location: admin.php");
    exit();
}

// Добавление новой станции (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addStation') {
    $station_name = $_POST['stationName'] ?? '';
    $station_url = $_POST['stationUrl'] ?? '';
    $station_logo = $_POST['stationLogo'] ?? '';
    $category_id = $_POST['stationCategory'] ?? '';

    // Проверим, что все данные получены
    if (empty($station_name) || empty($station_url) || empty($category_id)) {
        exit(json_encode(['status' => 'error', 'message' => 'Не все поля заполнены']));
    }

    // Попробуем добавить станцию и проверим результат
    $result = addStation($station_name, $station_logo, $station_url, $category_id);

    if ($result) {
        generateM3U8(); // Обновляем плейлист
        exit(json_encode(['status' => 'success', 'message' => 'Станция успешно добавлена']));
    } else {
        exit(json_encode(['status' => 'error', 'message' => 'Ошибка при добавлении станции']));
    }
}


// Обновление порядка станций
if (isset($_POST['update_station_order'])) {
    $orderedIds = json_decode($_POST['update_station_order'], true);
    if ($orderedIds) {
        foreach ($orderedIds as $index => $station_id) {
            updateStationOrder($station_id, $index);
        }
        generateM3U8();
        echo 'Очередность станций обновлена';
    }
    exit();
}
if (!function_exists('getStations')) {
    function getStations($category_id = null) {
        $pdo = getDBConnection();
        $query = "SELECT * FROM stations";

        if ($category_id) {
            $query .= " WHERE category_id = :category_id";
        }

        $query .= " ORDER BY `order` ASC";
        $stmt = $pdo->prepare($query);

        if ($category_id) {
            $stmt->execute(['category_id' => $category_id]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель RADIO ONLINE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
   <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
    h1 {
        text-align: center;
        color: #00bfff;
        text-shadow: 0 0 10px #00bfff, 0 0 20px #00bfff; /* Эффект свечения */
        margin: 10px 0;
        font-size: 36px;
        font-weight: bold;
        text-transform: uppercase;
    }
    </style>
</head>
<body class="bg-dark text-light">
    <div class="container mt-4 bg-dark text-light">
        <h1>Админ панель RADIO ONLINE</h1>

        <div class="display-flex">
        <!-- Добавить категорию -->
        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Добавить категорию</button>
        <!-- добавления станции -->
         <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addStationModal">Добавить станцию</button>
         </div>

        <!-- Модальное окно для добавления категории -->
        <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="bg-dark text-light modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCategoryModalLabel">Добавить категорию</h5>
                        <button type="button" class="bg-dark text-light btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="admin.php" method="post">
                            <div class="mb-3">
                                <label for="categoryName" class="form-label">Название категории</label>
                                <input type="text" class="form-control" id="categoryName" name="categoryName" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Добавить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно для редактирования категории -->
        <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="bg-dark text-light modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCategoryModalLabel">Редактировать категорию</h5>
                        <button type="button" class="bg-dark text-light btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="admin.php" method="post">
                            <div class="mb-3">
                                <label for="editCategoryName" class="form-label">Название категории</label>
                                <input type="text" class="form-control" id="editCategoryName" name="editCategoryName" required>
                            </div>
                            <input type="hidden" id="editCategoryId" name="editCategoryId">
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<h3 class="bg-dark text-light mt-5">Радиостанции</h3>
<?php foreach ($categories as $category): ?>
    <ul  class="list-group mb-4 stationsList">
        <!-- Вывод категории -->
        <li class=" list-group-item active d-flex justify-content-between align-items-center">
            <?= $category['name'] ?>
            <div class="display-flex">
                <!-- Кнопка для редактирования категории -->
                <button class="btn btn-sm btn-link text-warning p-0 " data-bs-toggle="modal" data-bs-target="#editCategoryModal" data-id="<?= $category['id'] ?>" data-name="<?= $category['name'] ?>">
                    <i class="fas fa-edit"></i>
                </button>
                <form action="admin.php" method="post" style="display:inline;">
                    <input type="hidden" name="delete_category" value="<?= $category['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            </div>
        </li>

        <!-- Вывод станций для текущей категории -->
        <?php
        $stations = getStations($category['id']); // Получаем станции для текущей категории
        if (count($stations) > 0):
        ?>
            <?php foreach ($stations as $station): ?>
                <li class="bg-dark text-light list-group-item d-flex justify-content-between align-items-center stationsList" data-id="<?= $station['id'] ?>">
                    <div class="d-flex">
                        <img src="<?= $station['logo'] ?>" alt="Logo" style="width: 30px; height: 30px; margin-right: 10px; border-radius: 5px;">
                        <?= $station['name'] ?>
                    </div>
                    <div class="d-flex">
                        <!-- Button for preview -->
                        <button class="btn btn-sm btn-link text-info p-0 preview-station" data-url="<?= $station['url'] ?>">
                            <i class="fas fa-play-circle"></i>
                        </button>
                        <!-- Edit button -->
                        <button class="btn btn-sm btn-link text-warning p-0" data-bs-toggle="modal" data-bs-target="#editStationModal" data-id="<?= $station['id'] ?>" data-name="<?= $station['name'] ?>" data-url="<?= $station['url'] ?>" data-logo="<?= $station['logo'] ?>" data-category="<?= $station['category_id'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <!-- Delete form -->
                        <form action="admin.php" method="post" style="display:inline;">
                            <input type="hidden" name="delete_station" value="<?= $station['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Если нет станций в категории -->
            <li class="bg-dark text-light list-group-item text-muted">Нет станций</li>
        <?php endif; ?>
    </ul>
<?php endforeach; ?>
    <!-- Модальное окно для добавления станции -->
    <div class="modal fade" id="addStationModal" tabindex="-1" aria-labelledby="addStationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="bg-dark text-light modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStationModalLabel">Добавить новую станцию</h5>
                    <button type="button" class="bg-dark text-light btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStationForm">
                        <div class="mb-3">
                            <label for="stationName" class="form-label">Название станции</label>
                            <input type="text" class="form-control" id="stationName" name="stationName" required>
                        </div>

                        <div class="mb-3">
                            <label for="stationLogo" class="form-label">URL логотипа</label>
                            <input type="text" class="form-control" id="stationLogo" name="stationLogo">
                        </div>

                        <div class="mb-3">
                            <label for="stationUrl" class="form-label">URL станции</label>
                            <input type="url" class="form-control" id="stationUrl" name="stationUrl" required>
                        </div>

                        <div class="mb-3">
                            <label for="stationCategory" class="form-label">Категория</label>
                            <select class="form-control" id="stationCategory" name="stationCategory" required>
                                <?php foreach (getCategories() as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Добавить станцию</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Модальное окно для редактирования станции -->
    <div class="modal fade" id="editStationModal" tabindex="-1" aria-labelledby="editStationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="bg-dark text-light modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStationModalLabel">Редактировать станцию</h5>
                    <button type="button" class="bg-dark text-light btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="admin.php" method="post">
                        <div class="mb-3">
                            <label for="editStationName" class="form-label">Название станции</label>
                            <input type="text" class="form-control" id="editStationName" name="editStationName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStationLogo" class="form-label">Логотип</label>
                            <input type="text" class="form-control" id="editStationLogo" name="editStationLogo" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStationUrl" class="form-label">URL станции</label>
                            <input type="text" class="form-control" id="editStationUrl" name="editStationUrl" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStationCategory" class="form-label">Категория</label>
                            <select class="form-control" id="editStationCategory" name="editStationCategory" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" id="editStationId" name="editStationId">
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<video id="hlsPlayer" style="position: fixed; bottom: 20px; right: 20px; display: none; width: 300px;" controls>
    Your browser does not support the video tag.
</video>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const hlsPlayer = document.getElementById('hlsPlayer');
    const previewButtons = document.querySelectorAll('.preview-station');

    previewButtons.forEach(button => {
        button.addEventListener('click', () => {
            const stationUrl = button.getAttribute('data-url');

            if (!stationUrl) {
                alert('URL станции не найден!');
                return;
            }

            // Reset player
            hlsPlayer.pause();
            hlsPlayer.style.display = 'none';

            // Check for HLS support
            if (Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(stationUrl);
                hls.attachMedia(hlsPlayer);
                hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    hlsPlayer.style.display = 'block';
                    hlsPlayer.play().catch(error => {
                        console.error('Ошибка воспроизведения HLS:', error);
                        alert('Не удалось воспроизвести станцию.');
                    });
                });
            } else if (hlsPlayer.canPlayType('application/vnd.apple.mpegurl')) {
                // Fallback for browsers with native HLS support (e.g., Safari)
                hlsPlayer.src = stationUrl;
                hlsPlayer.style.display = 'block';
                hlsPlayer.play().catch(error => {
                    console.error('Ошибка воспроизведения HLS:', error);
                    alert('Не удалось воспроизвести станцию.');
                });
            } else {
                alert('Ваш браузер не поддерживает воспроизведение HLS.');
            }
        });
    });
});


// Функция для инициализации сортировки
function initSortable() {
    const stationLists = document.getElementsByClassName('stationsList');
    Array.from(stationLists).forEach(stationList => {
        new Sortable(stationList, {
            onEnd: async (evt) => {
                const orderedIds = Array.from(evt.from.children).map(item => item.dataset.id);
                const data = new FormData();
                data.append('update_station_order', JSON.stringify(orderedIds));

                try {
                    const response = await fetch('admin.php', { method: 'POST', body: data });
                    const result = await response.text();
                    console.log(result);
                } catch (error) {
                    console.error('Ошибка обновления очередности:', error);
                }
            }
        });
    });
}

// Инициализация сортировки при загрузке страницы
document.addEventListener('DOMContentLoaded', initSortable);

// Обработчик для открытия модального окна редактирования категории
const editCategoryModal = document.getElementById('editCategoryModal');
if (editCategoryModal) {
    editCategoryModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const categoryId = button.getAttribute('data-id');
        const categoryName = button.getAttribute('data-name');

        document.getElementById('editCategoryName').value = categoryName;
        document.getElementById('editCategoryId').value = categoryId;
    });
}

// Обработчик для открытия модального окна редактирования станции
const editStationModal = document.getElementById('editStationModal');
if (editStationModal) {
    editStationModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const stationId = button.getAttribute('data-id');
        const stationName = button.getAttribute('data-name');
        const stationUrl = button.getAttribute('data-url');
        const stationLogo = button.getAttribute('data-logo');
        const categoryId = button.getAttribute('data-category');

        document.getElementById('editStationName').value = stationName;
        document.getElementById('editStationUrl').value = stationUrl;
        document.getElementById('editStationLogo').value = stationLogo;
        document.getElementById('editStationCategory').value = categoryId;
        document.getElementById('editStationId').value = stationId;
    });
}

// Функция для добавления новой станции (AJAX)
const addStationForm = document.getElementById('addStationForm');
if (addStationForm) {
    addStationForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'addStation');

        try {
            const response = await fetch('admin.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            console.log('Ответ от сервера:', data);

            if (data.status === 'success') {
                alert('Станция успешно добавлена!');
                location.reload();
            } else {
                console.error('Ошибка:', data.message);
                alert('Ошибка при добавлении станции: ' + data.message);
            }
        } catch (error) {
            console.error('Ошибка при отправке запроса:', error);
            alert('Ошибка при добавлении станции');
        }
    });
}

// Функция для обновления сортировки после изменения станции
function refreshSortable() {
    initSortable();
}

// Инициализация сортировки после добавления или удаления станции
document.addEventListener('stationAddedOrDeleted', refreshSortable);
</script>
</body>
</html>