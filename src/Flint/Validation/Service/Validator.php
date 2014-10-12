<?php
namespace Flint\Validation\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class Validator
{
    protected $validators = [];
    protected $messages     = [];

    public function __construct(array $availableValidators = [], array $messages = [])
    {
        $this->validators = $availableValidators;
        $this->config     = $messages;
    }

    public function getErrors($data, $rulesetKey, $simple = true)
    {
        $data  = ($data instanceof Request) ? $data->request->all() : $data;
        $rules = isset($this->validators[$rulesetKey]) ? $this->validators[$rulesetKey] : [];

        $v = new \Valitron\Validator($data);
        $v->lang('ru');
        $v->rules($rules);

        $errors = $v->validate() ? null : $v->errors();

        if ($errors) {
            if ($simple) {
                foreach ($errors as &$error) {
                    $error = $error[0];
                }
            }
        }

        return $errors;
    }

    /**
     * Validate Request data by given ruleset
     *
     * @param Request|array $data
     * @param string  $rulesetKey
     *
     * @return bool
     * @throws InvalidArgumentException If validation failed
     */
    public function validate($data, $rulesetKey, $throw = true)
    {

        $errors = $this->getErrors($data, $rulesetKey, !$throw);
        if ($errors && $throw) {
            throw new InvalidArgumentException(json_encode($errors));
        }

        return true;
    }
}
