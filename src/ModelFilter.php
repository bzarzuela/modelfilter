<?php

namespace Bzarzuela;

/**
 * Enables filtering of models in Laravel applications
 * @package App\Bzarzuela
 */
class ModelFilter
{
    protected $key;
    protected $rules;

    /**
     * Initialize with a unique key for the model.
     * Usually good to use the model's table name.
     *
     * @param string $key
     */
    public function __construct($key = null)
    {
        if (! is_null($key)) {
            $this->setKey($key);
        }
    }

    /**
     * Sets a unique key to hold the filter form data in the session.
     * @param string $key Key name. Usually the model's table name
     */
    public function setKey($key)
    {
        $this->key = $key;

        // Initialize if we're the first instance.
        if (! session()->has('bzarzuela.filters')) {
            session(['bzarzuela.filters' => [$key => []]]);
        }

        return $this;
    }

    /**
     * Define how the filter behaves for different types of fields.
     * Each rule follows this format:
     * [ $form_field_name => [$type, $model_field_name]]
     *
     * The following types are available:
     * primary - A special type which must be first on the rules and will bypass all other rules.
     *  $model_field_name is optional for this type.
     * in - The form data should be stored as an array already
     * from - Assumes a timestamp field and will automatically create a where condition like
     *  where($model_field_name, '>=', date('Y-m-d 00:00:00, strtotime($form_field_value))
     * to - Same as the from except the where operand becomes <= and the time constant is 23:59:59
     *
     * @param array $rules
     */
    public function setRules($rules)
    {
        $this->rules = $rules;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Usually populated with the output of $request->except('_token');
     *
     * @param $form_data
     */
    public function setFormData($form_data)
    {
        $this->remember('form_data', $form_data);
    }

    /**
     * Gets either the whole form data or just a field inside.
     *
     * @param null $field
     * @return array|null
     */
    public function getFormData($field = null)
    {
        $filters = session('bzarzuela.filters');

        if (isset($filters[$this->key]['form_data'])) {

            if (! is_null($field)) {

                if (! isset($filters[$this->key]['form_data'][$field])) {
                    return null;
                }

                return $filters[$this->key]['form_data'][$field];
            }

            return $filters[$this->key]['form_data'];
        }

        if (! is_null($field)) {
            return null;
        }

        return [];
    }

    /**
     * Applies the form data according to the different rules that were initially configured.
     * For example:
     * $tickets = $this->filter(Ticket::query())->paginate(30);
     *
     * @param $query
     * @return mixed
     */
    public function filter($query)
    {
        foreach ($this->getRules() as $name => $rule) {

            if ($this->getFormData($name) == '') {
                continue;
            }

            $type = $rule[0];

            switch ($type) {
                case 'primary':
                    $query->where($name, '=', $this->getFormData($name));
                    // Exit out of the loop, we don't need to process other conditions
                    break 2;

                case 'in':
                    $query->whereIn($rule[1], $this->getFormData($name));
                    break;

                case 'from':
                    $query->where($rule[1], '>=', date('Y-m-d 00:00:00', strtotime($this->getFormData($name))));
                    break;

                case 'to':
                    $query->where($rule[1], '<=', date('Y-m-d 23:59:59', strtotime($this->getFormData($name))));
                    break;

                case 'like':
                    $field = $name;
                    if (isset($rule[1])) {
                        $field = $rule[1];
                    }
                    $query->where($field, 'like', $this->getFormData($name) . '%');
                    break;

                default:
                    $field = $name;
                    if (isset($rule[1])) {
                        $field = $rule[1];
                    }
                    $query->where($field, '=', $this->getFormData($name));
                    break;
            }
        }

        return $query;
    }

    /**
     * @param $name
     * @param $value
     */
    private function remember($name, $value)
    {
        $filters = session('bzarzuela.filters');

        $filters[$this->key][$name] = $value;

        session(['bzarzuela.filters' => $filters]);
    }
}
