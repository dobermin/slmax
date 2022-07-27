<?php

/**
 * Автор: Павел Позняк
 *
 * Дата реализации: 27.07.2022 17:46
 *
 * Дата изменения: 27.07.2022 21:30
 *
 * Класс для работы со списками пользователей.
 * Класс использует функционал класса @User.
 * Конструктор класса осуществляет поиск @id пользователей по всем полям БД при помощи метода @search().
 * Имеется возможность поиска по условию (больше, меньше, не равно).
 * Метод @getUserList() возвращает массив экземпляров класса @User из массива с @id пользователей, полученного в конструкторе.
 * Метод @deleteUsers() удаляет из БД пользователей с помощью экземпляров класса @User в соответствии с массивом, полученным в конструкторе.
 */

if (!class_exists("User")) die("Ошибка подключения класса User");

class UserList
{
    private array $listId;
    private Column $column;
    private Operation $operation;
    private string $value;

    private PDO $pdo;

    /**
     * @param Column $column
     * @param Operation $operation
     * @param string $value
     */
    public function __construct(Column $column, Operation $operation, string $value)
    {
        $this->pdo = new PDO('mysql:host=localhost;dbname=slmax', 'root', 'root');
        $this->column = $column;
        $this->operation = $operation;
        $this->value = $value;
        $this->listId = $this->search();
    }

    /**
     * @return array
     */
    private function search(): array
    {
        $sql = "SELECT `id` FROM `Users`";
        switch ($this->operation) {
            case Operation::After:
                $sql = "SELECT `id` FROM `Users` WHERE {$this->column->name} > ?";
                break;
            case Operation::Less:
                $sql = "SELECT `id` FROM `Users` WHERE {$this->column->name} < ?";
                break;
            case Operation::Not:
                $sql = "SELECT `id` FROM `Users` WHERE {$this->column->name} <> ?";
                break;
            default:
                break;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->value]);

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * @return array
     */
    public function getUserList(): array
    {
        $userList = [];
        foreach ($this->listId as $list) {
            $userList[] = new User($list->id);
        }
        return $userList;
    }

    /**
     * @param $userList
     * @return void
     */
    public function deleteUsers($userList): void
    {
        foreach ($userList as $user) {
            $user->delete();
        }
    }

}


enum Column
{
    case Id;
    case FirstName;
    case LastName;
    case Birthday;
    case Gender;
    case BirthPlace;
}

enum Operation
{
    case After;
    case Less;
    case Not;
}