<?php

namespace app\core\db;

use app\core\Application;
use app\core\Model;

abstract class DbModel extends Model
{
    abstract public function tableName(): string;
    abstract public function attributes(): array;
    abstract public function primaryKey(): string;

    public function save()
    {
        $tableName = $this->tableName();
        $attributes = $this->attributes();
        $params = array_map(fn($attr) => ":$attr", $attributes);
        $statememnt = self::prepare("INSERT INTO $tableName (".implode(',', $attributes).") 
                    VALUES (".implode(',', $params).")");
        foreach ($attributes as $attribute){
            $statememnt->bindValue(":$attribute", $this->{$attribute});
        }

        $statememnt->execute();
        return true;
    }

    public function findOne($where) // [email => abc@example.com, firstname => abc]
    {
        $tableName = static::tableName(); // here I am using static:: as the table name changes according to the class calling the method and for that reason I cannot use self::
        $attributes = array_keys($where);
        $sql = implode("AND " ,array_map(fn($attr) => "$attr = :$attr", $attributes)); // email = :email AND firstname = :firstname
        $statement = self::prepare("SELECT * FROM $tableName WHERE $sql");
        foreach ($where as $key => $item){
            $statement->bindValue(":$key", $item);
        }

        $statement->execute();
        return $statement->fetchObject(static::class);
    }

    public static function prepare($sql)
    {
        return Application::$app->db->pdo->prepare($sql);
    }
}