<?php

/**
 * The child class should should define all form fields as public variables. The __construct method
 * should specify their types, specify their labels, and their options (for checkboxes, selects, etc),
 * execute parent::__construct() after the field types are specified. TYPE_RAW and TYPE_PASSWORD
 * fields will not be sanitized and will probably need special handling for printing saved values.
 *
 * The child class will probably want to have its own print_html() method.
 *
 * To use the class instantiate unsing $formObj = new my_form_class();
 *
 * Than submitted values will be in the object public variables, and you use print_html() to print
 * the form html.
 *
 * @author abe
 */
abstract class core_form
{

    const ENC_STANDARD = 'application/x-www-form-urlencoded';
    const ENC_MULTIPART = 'multipart/form-data';

    private $_form_id;
    protected $submitted = NULL;
    protected $encoding = self::ENC_STANDARD;

    private static $_ignore = array('_form_id', 'submitted', 'encoding');

    const TYPE_TEXT = 1;
    const TYPE_TEXTAREA = 2;
    const TYPE_SELECT = 3;
    const TYPE_CHECKBOX = 11;
    const TYPE_CHECKBOXES = 4;
    const TYPE_RADIO = 5;
    const TYPE_EMAIL = 6;
    const TYPE_US_PHONE = 7;
    const TYPE_US_STATE = 8;
    const TYPE_ZIP = 12;
    const TYPE_PASSWORD = 9;
    const TYPE_FILE = 10;
    const TYPE_DATE = 13;
    const TYPE_RAW = 14;


    private static $_fieldTypes = array();
    private static $_fieldLabels = array();
    private static $_fieldOptions = array();

    public function print_html($postToUrl, $submitButtonLabel = 'Submit')
    {
        if ($this->submitted()) {
            echo '<p>The form as been submitted</p>';
            return;
        }
        ?>
        <form id="<?= $this->form_id() ?>" method="post" enctype="<?= $this->encoding ?>"
              action="<?= $this->format_post_url($postToUrl) ?>"><?php
        foreach ($this as $field => $value) {
            if (in_array($field, self::$_ignore)) {
                continue;
            }
            if (!$this->saved_value($field) && $value) {
                $this->set_saved_value($field, $value);
            }
            $this->print_html_field_block($field);
        }
        ?><p><input type="submit" value="<?= $submitButtonLabel ?>"></p></form><?php
    }

    public function submitted()
    {
        return $this->submitted !== NULL;
    }

    public function success()
    {
        return $this->submitted === true;
    }

    public function clear_saved()
    {
        $_SESSION[core_config::sessionVar()][__CLASS__]['saved_value'][get_class($this)] = array();
    }

    public function __construct($allowMultipleFormInstances = false)
    {
        if (!($form_id = req_get::txt('form'))
            || empty($_SESSION[core_config::sessionVar()][__CLASS__]['form_ids'][get_class($this)][$form_id])
        ) {
            if (!$allowMultipleFormInstances) {
                $this->clear_form_instances();
            }
            return;
        }
        $this->submitted = true;
        if (!$allowMultipleFormInstances) {
            $this->clear_form_instances();
        } else {
            unset($_SESSION[core_config::sessionVar()][__CLASS__]['form_ids'][get_class($this)][$form_id]);
        }
        $this->_form_id = $form_id; //needed to access the posted fields
        foreach ($this as $field => &$value) {
            if (in_array($field, self::$_ignore)) {
                continue;
            }
            $value = $this->post_value($field);
        }
    }

    protected function format_post_url($postToUrl)
    {
        return $postToUrl . (strpos($postToUrl, '?') !== false ? '&' : '?') . 'form=' . $this->form_id();
    }

    protected function clear_form_instances()
    {
        $_SESSION[core_config::sessionVar()][__CLASS__]['form_ids'][get_class($this)] = array();
    }

    protected function set_field_type($field, $type = self::TYPE_TEXT)
    {
        self::$_fieldTypes[get_class($this)][$field] = $type;
        if ($type == self::TYPE_FILE) {
            $this->encoding = self::ENC_MULTIPART;
        }
    }

    protected function set_field_label($field, $label)
    {
        self::$_fieldLabels[get_class($this)][$field] = $label;
    }

    /**
     *
     * @param string $field
     * @param array $options Associative array
     */
    protected function set_field_options($field, $options)
    {
        self::$_fieldOptions[get_class($this)][$field] = $options;
    }

    protected function set_saved_value($field, $value)
    {
        $_SESSION[core_config::sessionVar()][__CLASS__]['saved_value'][get_class($this)][$field] = $value;
    }

    protected function field_type($field)
    {
        if (isset(self::$_fieldTypes[get_class($this)][$field])) {
            return self::$_fieldTypes[get_class($this)][$field];
        }
        return self::TYPE_TEXT;
    }

    protected function field_label($field)
    {
        if (isset(self::$_fieldLabels[get_class($this)][$field])) {
            return self::$_fieldLabels[get_class($this)][$field];
        }
        return '';
    }

    /**
     *
     * @param type $field
     * @return array
     */
    protected function field_options($field)
    {
        if (isset(self::$_fieldOptions[get_class($this)][$field])) {
            return self::$_fieldOptions[get_class($this)][$field];
        }
        return array();
    }


    protected function saved_value($field)
    {
        if (isset($_SESSION[core_config::sessionVar()][__CLASS__]['saved_value'][get_class($this)][$field])) {
            return $_SESSION[core_config::sessionVar()][__CLASS__]['saved_value'][get_class($this)][$field];
        }
    }

    protected function print_html_field_block($field, $wrapperTag = 'p')
    {
        echo '<', $wrapperTag, ' 
      class="form-group ', $this->field_class($field), '-block core_form-', get_class($this), '-', $field, '-block">';
        if (($label = $this->field_label($field))) {
            ?><label for="<?= $this->field_name($field) ?>" class="core_form-field-label"><?= $label ?></label><?php
        }
        $this->print_html_field($field);
        echo '</', $wrapperTag, '>';
    }

    protected function print_html_field($field)
    {
        switch ($this->field_type($field)) {
            case self::TYPE_CHECKBOX:
                $this->print_html_field_checkbox($field);
                return;
            case self::TYPE_CHECKBOXES:
                $this->print_html_field_checkboxes($field);
                return;
            case self::TYPE_PASSWORD:
                $this->print_html_field_password($field);
                return;
            case self::TYPE_RADIO:
                $this->print_html_field_radio($field);
                return;
            case self::TYPE_SELECT:
                $this->print_html_field_select($field);
                return;
            case self::TYPE_TEXTAREA:
                $this->print_html_field_textarea($field);
                return;
            case self::TYPE_FILE:
                $this->print_html_field_file($field);
                return;
            default:
                $this->print_html_field_text($field);
                return;
        }
    }

    protected function field_name($field)
    {
        return $field . '-' . md5($field . $this->form_id());
    }

    protected function field_class($field)
    {
        $pre = 'core_form-field-';
        switch ($this->field_type($field)) {
            case self::TYPE_CHECKBOX:
            case self::TYPE_CHECKBOXES:
                return $pre . 'checkbox';
            case self::TYPE_EMAIL:
                return $pre . 'email';
            case self::TYPE_FILE:
                return $pre . 'file';
            case self::TYPE_PASSWORD:
                return $pre . 'password';
            case self::TYPE_RADIO:
                return $pre . 'radio';
            case self::TYPE_SELECT:
                return $pre . 'select';
            case self::TYPE_TEXT:
                return $pre . 'text';
            case self::TYPE_TEXTAREA:
                return $pre . 'textarea';
            case self::TYPE_US_PHONE:
                return $pre . 'phone';
            case self::TYPE_US_STATE:
                return $pre . 'us_state';
            case self::TYPE_ZIP:
                return $pre . 'zip';
            case self::TYPE_DATE:
                return $pre . 'date';
            case self::TYPE_RAW:
                return $pre . 'raw';
        }
    }

    protected function form_id()
    {
        if ($this->_form_id === NULL) {
            $this->_form_id = uniqid(get_class($this)) . md5(rand(0, 1000));
            $_SESSION[core_config::sessionVar()][__CLASS__]['form_ids'][get_class($this)][$this->_form_id] = true;
        }
        return $this->_form_id;
    }

    private function post_value($field)
    {
        $fieldName = $this->field_name($field);
        $type = $this->field_type($field);
        if (req_post::is_array($fieldName)) {
            $value = req_post::txt_array($fieldName);
        } elseif ($type == self::TYPE_TEXTAREA) {
            $value = req_post::multi_txt($fieldName);
        } elseif ($type == self::TYPE_PASSWORD || $type == self::TYPE_RAW) {
            $value = req_post::raw($fieldName);
        } elseif ($type == self::TYPE_FILE) {
            $value = new core_form_file($fieldName);
        } else {
            $value = req_post::txt($fieldName);
        }
        $this->set_saved_value($field, $value);
        return $value;
    }

    private function print_html_field_checkbox($field)
    {
        $name = $this->field_name($field);
        ?><label for="<?= $name ?>" class="<?= $this->field_class($field) ?>-label">
        <input type="checkbox" name="<?= $name ?>" id="<?= $name ?>"
               value="1" <?= $this->saved_value($field) ? 'checked' : '' ?>
               class="<?= $this->field_class($field) ?> core_form-<?= get_class($this) ?>-<?= $field ?>">
        <?= $this->field_label($field) ?>
        </label><?php
    }

    private function print_html_field_checkboxes($field)
    {
        $name = $this->field_name($field);
        foreach ($this->field_options($field) as $value => $label) {
            ?><label for="<?= $name ?>-<?= $value ?>" class="<?= $this->field_class($field) ?>-label">
            <input type="checkbox" name="<?= $name ?>" id="<?= $name ?>-<?= $value ?>"
                   value="<?= $value ?>" <?= $this->saved_value($field) == $value ? 'checked' : '' ?>
                   class="<?= $this->field_class($field) ?> core_form-<?= get_class($this) ?>-<?= $field ?>">
            <?= $label ?>
            </label><?php
        }
    }

    private function print_html_field_radio($field)
    {
        $name = $this->field_name($field);
        foreach ($this->field_options($field) as $value => $label) {
            ?><label for="<?= $name ?>-<?= $value ?>" class="<?= $this->field_class($field) ?>-label">
            <input type="radio" name="<?= $name ?>" id="<?= $name ?>-<?= $value ?>"
                   value="<?= $value ?>" <?= $this->saved_value($field) == $value ? 'checked' : '' ?>
                   class="<?= $this->field_class($field) ?> core_form-<?= get_class($this) ?>-<?= $field ?>">
            <?= $label ?>
            </label><?php
        }
    }

    private function print_html_field_select($field)
    {
        $name = $this->field_name($field);
        ?><select name="<?= $name ?>" id="<?= $name ?>"
                  class="form-control <?= $this->field_class($field) ?> core_form-<?= get_class($this) ?>-<?= $field ?>">
        <?php foreach ($this->field_options($field) as $value => $label) { ?>
        <option value="<?= $value ?>" <?= $this->saved_value($field) == $value ? 'selected' : '' ?>>
            <?= $label ?>
        </option>
    <?php } ?></select><?php
    }

    private function print_html_field_text($field)
    {
        $name = $this->field_name($field);
        ?><input type="text" name="<?= $name ?>" id="<?= $name ?>" value="<?= $this->saved_value($field) ?>"
                 class="form-control <?= $this->field_class($field) ?> core_form-<?= get_class($this) ?>-<?= $field ?>"><?php
    }

    private function print_html_field_textarea($field)
    {
        $name = $this->field_name($field);
        ?><textarea name="<?= $name ?>"
                    class="form-control <?= $this->field_class($field) ?> core_form-<?= get_class($this) ?>-<?= $field ?>"
                    id="<?= $name ?>"><?= $this->saved_value($field) ?></textarea><?php
    }

    private function print_html_field_password($field)
    {
        $name = $this->field_name($field);
        ?><input type="password" name="<?= $name ?>" id="<?= $name ?>"
                 value="<?= str_replace('"', '&quot;', $this->saved_value($field)) ?>"
                 class="form-control <?= $this->field_class($field) ?> core_form-<?= get_class($this) ?>-<?= $field ?>"><?php
    }

    private function print_html_field_file($field)
    {
        $name = $this->field_name($field);
        ?><input type="file" name="<?= $name ?>" id="<?= $name ?>"
                 class="form-control  <?= $this->field_class($field) ?> core_form-<?= get_class($this) ?>-<?= $field ?>"><?php
    }
}
