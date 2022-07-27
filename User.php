<?php

/**
 * Автор: Павел Позняк
 *
 * Дата реализации: 26.07.2022 20:58
 *
 * Дата изменения: 27.07.2022 20:55
 *
 * Утилита для работы с базой данных
 *
 * Класс для работы с базой данных пользователей.
 * Класс предоставляет функционал классу @UserList.
 * Конструктор класса сохраняет пользователя с помощью метода @save(), a при наличии @id - получает пользователя из БД
 * с помошью метода @getUser().
 * Метод @delete() удаляет пользователя из БД по @id.
 * Метод @getAge() преобразует дату рождения пользователя в возраст (сколько полных лет).
 * Метод @getGender() преобразует пол из двоичной системы в текстовую (муж, жен).
 * Метод @formattedUser() возвращает новый экземпляр stdClass со всеми полями класса @User.
 * Методы @validateLetters(), @validateBirthday() и @validateGender() используются для валидации.
 * Метод @existsTable() проверяет существует ли таблица `Users`.
 * Метод @createTable() создает таблицу `Users`.
 */
class User
{
    private int $id;
    private string $firstName;
    private string $lastName;
    private static string $birthday;
    private static int $gender;
    private string $birthPlace;

    private PDO $pdo;

    /**
     * @param int|null $id
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $birthday
     * @param int|null $gender
     * @param string|null $birthPlace
     */
    public function __construct(int $id = null, string $firstName = null, string $lastName = null, string $birthday = null, int $gender = null, string $birthPlace = null)
    {
        $this->pdo = new PDO('mysql:host=localhost;dbname=slmax', 'root', 'root');
        if (!$this->existsTable()) $this->createTable();
        if ($id != null) {
            $this->id = $id;
            $this->getUser();
        } else {
            $this->firstName = $this->validateLetters($firstName);
            $this->lastName = $this->validateLetters($lastName);
            User::$birthday = $this->validateBirthday($birthday);
            User::$gender = $this->validateGender($gender);
            $this->birthPlace = $this->validateLetters($birthPlace);
            $this->save();
        }
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        $sql = "INSERT INTO `Users` (first_name, last_name, birthday, gender, birth_place) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$this->firstName, $this->lastName, self::$birthday, self::$gender, $this->birthPlace]);
    }

    /**
     * @return void
     */
    private function getUser(): void
    {
        $sql = "SELECT * FROM `Users` WHERE `id` = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->id]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        $this->firstName = $user->first_name;
        $this->lastName = $user->last_name;
        User::$birthday = $user->birthday;
        User::$gender = $user->gender;
        $this->birthPlace = $user->birth_place;
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM `Users` WHERE `id` = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$this->id]);
    }

    /**
     * @return string
     */
    public static function getAge(): string
    {
        $diff = date('Ymd') - date('Ymd', strtotime(self::$birthday));

        return substr($diff, 0, -4);
    }

    /**
     * @return string
     */
    public static function getGender(): string
    {
        return self::$gender ? "жен" : "муж";
    }

    /**
     * @return bool
     */
    private function existsTable(): bool
    {
        try {
            return $this->pdo->prepare("SELECT * FROM `Users`")
                ->execute();
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @return stdClass
     */
    public function formattedUser(): stdClass
    {
        $stdClass = new stdClass();
        $stdClass->id = $this->id;
        $stdClass->firstName = $this->firstName;
        $stdClass->lastName = $this->lastName;
        $stdClass->birthday = User::$birthday;
        $stdClass->birthPlace = $this->birthPlace;
        $stdClass->age = User::getAge();
        $stdClass->gender = User::getGender();

        return $stdClass;
    }

    /**
     * @param string $str
     * @return string
     */
    private function validateLetters(string $str): string
    {
        preg_match('/^[a-zа-я]*$/i', strtolower($str), $matches);
        if (sizeof($matches) == 0) die("Ошибка: должны быть только буквы");
        return $str;
    }

    /**
     * @param string $gender
     * @return string
     */
    private function validateGender(string $gender): string
    {
        if (!in_array($gender, [0, 1])) die("Ошибка: 0 - муж, 1 - жен");
        return $gender;
    }

    /**
     * @param string $birthday
     * @return string
     */
    private function validateBirthday(string $birthday): string
    {
        preg_match('/^\d{2}\.\d{2}\.\d{4}$/i', strtolower($birthday), $matches);
        if (sizeof($matches) == 0) die("Ошибка: формат дня рождения должен быть 01.01.1970");
        return $birthday;
    }

    /**
     * @return void
     */
    private function createTable(): void
    {
        $sql = "CREATE TABLE `Users` (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(30) NOT NULL,
            last_name VARCHAR(30) NOT NULL,
            birthday VARCHAR(10),
            gender INTEGER(1),
            birth_place VARCHAR(255)
            )";
        $this->pdo->query($sql);
    }

}