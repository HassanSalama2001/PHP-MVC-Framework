<?php

namespace app\core;

abstract class Model
{
    public const RULE_REQUIRED = 'required';
    public const RULE_EMAIL = 'email';
    public const RULE_MIN = 'min';
    public const RULE_MAX = 'max';
    public const RULE_MATCH = 'match';
    public const RULE_UNIQUE = 'unique';
    public function loadData($data)
    {
        foreach ($data as $key => $value){
            if(property_exists($this, $key)){
                $this->{$key} = $value;
            }
        }
    }

    abstract public function rules():array;

    public function labels(): array
    {
        return [];
    }

    public function getLabel($attribute)
    {
       return $this->labels()[$attribute] ?? $attribute;
    }


    public array $errors = [];
    public function validate()
    {
        foreach ($this->rules() as $attribute => $rules){
            $value = $this->{$attribute};
            foreach ($rules as $rule){
               $ruleName = $rule;
               if(!is_string($ruleName)){
                   $ruleName = $rule[0];
               }
               if ($ruleName === self::RULE_REQUIRED && !$value){ //This means if the $rule was RULE_REQUIRED and the data was not entered then there must be an error message
                    $this->addErrorForRule($attribute, self::RULE_REQUIRED);
               }
               if ($ruleName === self::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)){ //This means if the $rule was RULE_EMAIL and the data entered was not in Email format then there must be an error message
                    $this->addErrorForRule($attribute, self::RULE_EMAIL);
               }
               if ($ruleName === self::RULE_MIN && strlen($value) < $rule['min']){ //This means if the $rule was RULE_MIN and the length of the data entered was less than the length specified in the rule['min'] then there must be an error message
                    $this->addErrorForRule($attribute, self::RULE_MIN, $rule); //here we send the whole rule so that I can iterate on it in the addErrors function and replace the {$attribute} with the value correctly
               }
               if ($ruleName === self::RULE_MAX && strlen($value) > $rule['max']){ //This means if the $rule was RULE_MAX and the length of the data entered was more than the length specified in the rule['max'] then there must be an error message
                    $this->addErrorForRule($attribute, self::RULE_MAX, $rule); //here we send the whole rule so that I can iterate on it in the addErrors function and replace the {$attribute} with the value correctly
               }
               if ($ruleName === self::RULE_MATCH && $value !== $this->{$rule['match']}){ //This means if the $rule was RULE_MATCH and the data entered were not the same as the data in the field specified in the rule['match'] then there must be an error message
                   $rule['match'] = $this->getLabel($rule['match']);
                   $this->addErrorForRule($attribute, self::RULE_MATCH, $rule); //here we send the whole rule so that I can iterate on it in the addErrors function and replace the {$attribute} with the value correctly
               }
               if ($ruleName === self::RULE_UNIQUE){
                   $className = $rule['class'];
                   $uniqueAttribute = $rule['attribute'] ?? $attribute;
                   $tableName = $className::tableName();
                   $statement = Application::$app->db->prepare("SELECT * FROM $tableName WHERE $uniqueAttribute = :attr");
                   $statement->bindValue(":attr", $value);
                   $statement->execute();
                   $record = $statement->fetchObject();
                   if($record) {
                       $this->addErrorForRule($attribute, self::RULE_UNIQUE, ['field' => $this->getLabel($attribute)]); //here we send the whole rule so that I can iterate on it in the addErrors function and replace the {$attribute} with the value correctly
                   }
               }
            }
        }

        return empty($this->errors);
    }

    private function addErrorForRule(string $attribute, string $rule, $params = [])
    {
        $message = $this->errorMessages()[$rule] ?? '';
        foreach ($params as $key => $value){
            $message = str_replace("{{$key}}", $value, $message); //here I replace the {$key} like {min} in the RULE_MIN with the specified value in the rule['min']
        }
        $this->errors[$attribute][] = $message;
    }

    public function addError(string $attribute, string $message)
    {
        $this->errors[$attribute][] = $message;
    }

    public function errorMessages()
    {
        return[
            self::RULE_REQUIRED => 'This field is required',
            self::RULE_EMAIL => 'This field must be valid email address',
            self::RULE_MIN => 'Min length of this field must be {min}',
            self::RULE_MAX => 'Max length of this field must be {max}',
            self::RULE_MATCH => 'This field must be the same as {match}',
            self::RULE_UNIQUE => 'Record with this {field} already exist',
        ];
    }

    public function hasError($attribute)
    {
        return $this->errors[$attribute] ?? false;
    }

    public function getFirstError($attribute)
    {
        return $this->errors[$attribute][0] ?? false;
    }
}