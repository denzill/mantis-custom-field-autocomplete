<?php

/**
 * Plugin adding custom fields with autocomplete feature (dropdown menu)
 *
 * @author: Denis V. Kachurin <d.kachurin@isida.by>
 */
class CustomFieldsAutocompletePlugin extends MantisPlugin {

    public static $loadFiles = false;

    /**
     *  A method that populates the plugin information and minimum requirements.
     */
    public function register() {
        $this->name = plugin_lang_get('title');
        $this->description = plugin_lang_get('description');
        $this->page = "";

        $this->version = '1.0';
        $this->requires = array(
            'MantisCore' => '2.0',
        );

        $this->author = 'Denis V. Kachurin';
        $this->contact = 'd.kachurin@isida.by';
        $this->url = '';
    }

    /**
     * Add hooks
     * @return type
     */
    public function hooks() {
        return array(
            'EVENT_MANAGE_PROJECT_UPDATE_FORM' => 'insertField',
            'EVENT_MANAGE_PROJECT_UPDATE'      => 'updateField',
            'EVENT_PLUGIN_INIT'                => 'registerCustomFieldType',
            'EVENT_LAYOUT_PAGE_FOOTER'         => 'injectFiles',
        );
    }

    /**
     * Save configuration option in database
     * 
     * @page  Manage project page
     * 
     * @hook EVENT_MANAGE_PROJECT_UPDATE_FORM
     * 
     * @param string $event
     * @param int $prj_id
     */
    public function updateField($event, $prj_id) {
        $t_custom_fields = custom_field_get_linked_ids($prj_id);
        foreach ($t_custom_fields as $t_field_id) {
            $t_desc = custom_field_get_definition($t_field_id);
            if ($t_desc['type'] == CUSTOM_FIELD_TYPE_AUTOCOMPLETE) {
                $value = gpc_get_string('autocomplete-field-' . $t_field_id, '');
                if (plugin_config_get('autocomplete-field-' . $t_field_id, '', false, NULL, $prj_id) != $value) {
                    plugin_config_set('autocomplete-field-' . $t_field_id, $value, NO_USER, $prj_id);
                }
            }
        }
    }

    /**
     * Output project settings (available values)
     * 
     * @page Manage project page
     * 
     * @hook EVENT_MANAGE_PROJECT_UPDATE
     * 
     * @param string $event
     * @param int $prj_id
     */
    public function insertField($event, $prj_id) {
        $t_custom_fields = custom_field_get_linked_ids($prj_id);
        foreach ($t_custom_fields as $t_field_id) {
            $t_desc = custom_field_get_definition($t_field_id);
            if ($t_desc['type'] == CUSTOM_FIELD_TYPE_AUTOCOMPLETE) {
                if ($prj_id !== NULL) {
                    $value = plugin_config_get('autocomplete-field-' . $t_field_id, '', false, NULL, $prj_id);
                    if ($value == '') {
                        $value = $t_desc['possible_values'];
                    }
                }
                echo('<tr ' . helper_alternate_class() . ' >' . PHP_EOL);
                echo('<td class="category">' . $t_desc['name'] . '</td>' . PHP_EOL);
                echo('<td><input name="autocomplete-field-' . $t_field_id . '" type="text" class="form-control" value="' . $value . '"></td>' . PHP_EOL . '</tr>' . PHP_EOL);
            }
        }
    }

    /**
     * Custom filed type registration
     * 
     * @hook EVENT_PLUGIN_INIT
     * @global array $g_custom_field_types
     * @global array $g_custom_field_type_definition
     * @global string $g_custom_field_type_enum_string
     */
    public function registerCustomFieldType() {
        global $g_custom_field_types;
        global $g_custom_field_type_definition;
        global $g_custom_field_type_enum_string;

        define('CUSTOM_FIELD_TYPE_AUTOCOMPLETE', -25); // Inique number (i hope).

        $g_custom_field_types[CUSTOM_FIELD_TYPE_AUTOCOMPLETE] = 'IsidaAutocomplete';
        $g_custom_field_type_definition[CUSTOM_FIELD_TYPE_AUTOCOMPLETE] = array(
            '#display_possible_values'         => TRUE,
            '#display_valid_regexp'            => TRUE,
            '#display_length_min'              => TRUE,
            '#display_length_max'              => TRUE,
            '#display_default_value'           => TRUE,
            '#special_field'                   => TRUE,
            '#function_return_distinct_values' => null,
            '#function_value_to_database'      => null,
            '#function_database_to_value'      => null,
            '#function_print_input'            => 'CustomFieldsAutocompletePlugin::printAutocompleteField',
            '#function_string_value'           => "CustomFieldsAutocompletePlugin::stringValueAutocompleteField",
            '#function_string_value_for_email' => null,
        );
        $g_custom_field_type_enum_string .= "," . CUSTOM_FIELD_TYPE_AUTOCOMPLETE . ":" . plugin_lang_get('autocomplete');
    }

    /**
     * 
     * @global array $g_plugin_current
     * @param array $l_field_def
     * @param mixed $l_custom_field_value
     */
    public static function printAutocompleteField($l_field_def, $l_custom_field_value) {
        global $g_plugin_current;

        /* Dirty hack for getting project setting for this custom field */
        array_unshift($g_plugin_current, 'CustomFieldsAutocomplete');
        $t_project_id = helper_get_current_project();
        $t_values = explode('|', plugin_config_get('autocomplete-field-' . $l_field_def['id'], '', false, NULL, $t_project_id));
        array_shift($g_plugin_current);
        
        self::$loadFiles = true; // Need inject js and css
        if ($l_custom_field_value != '') {
            $t_value = $l_custom_field_value;
        } else {
            $t_value = $t_values[0];
        }
        ?>
        <div class="dropdown <?php echo 'autocomplete-field-' . $l_field_def['id']; ?>">
            <input autocomplete="off" class="dropdown-toggle custom-value" id="<?php echo 'autocomplete-field-' . $l_field_def['id']; ?>" 
                   name="<?php echo 'custom_field_' . $l_field_def['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" 
                   value="<?php echo $t_value ?>">
            <i class="ace-icon fa fa-angle-down bigger-110 dropdown-toggle" data-toggle="dropdown"></i>
            <ul id="<?php echo 'autocomplete-field-' . $l_field_def['id'] . "-values"; ?>" class="user-menu dropdown-menu list dropdown-yellow no-margin auto-values" 
                aria-labelledby="<?php echo 'autocomplete-field-' . $l_field_def['id']; ?>">
                    <?php
                    foreach ($t_values as $value) {
                        echo ('<li><a data-value="' . $value . '" data-field="autocomplete-field-' . $l_field_def['id']
                        . '" class="project-link auto-value" href="#">' . $value . '</a></li>');
                    }
                    ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Output value (may be add some filter)
     * 
     * @param mixed $value
     */
    public static function stringValueAutocompleteField($value) {
        echo($value);
    }

    /**
     * inject CSS and JS files (only if needed)
     * 
     * @hook EVENT_LAYOUT_PAGE_FOOTER
     */
    public function injectFiles() {
        if (self::$loadFiles) {
            echo ('<link rel = "stylesheet" type = "text/css" href = "' . plugin_file('autocomplete.css') . '"/>');
            echo ('<script type="text/javascript" src="' . plugin_file('autocomplete.js') . '"></script>');
        }
    }

}
