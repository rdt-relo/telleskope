<?php
// Do not use require_once as this class is included in Company.php.

class TskpTemplate extends Teleskope
{
    // Define import type constants
    const TEMPLATE_TYPE_GROUP = 'group';

    const TEMPLATE_APP_TYPE = [
        'affinities',
        'officeraven',
        'talentpeak'
    ];
    // Add more constants for other import types if needed


    /**
     * Creates or updates a template import record in the database.
     *
     * @param string $sourceTemplateId The source Template ID.
     * @param string $templateName The name of the template.
     * @param string $templateType The type of import (e.g., 'group', 'newsletter', 'survey', 'announcement').
     * @param mixed $data The data associated with the template import.
     * @param string $templateDescription The description of the template.
     *
     * @return bool True if the template import was successfully created or updated, false otherwise.
     */
    public static function CreateOrUpdateTemplate(string $sourceTemplateId, string $templateName, string $templateType, string $templateAppType, $data, $templateDescription)
    {
        // Validate and sanitize the data
        $validatedData = self::ValidateAndSanitizeData($data);
        if (!$validatedData) {
            return false; // Data is invalid, return false
        }
        $existingTemplate = self::GetTskpTemplate($sourceTemplateId);

        if ($existingTemplate) {
            if ($existingTemplate['is_active'] != 2) {
                return 'Error: The template was already imported and it is in the active state';
            } elseif ($existingTemplate['template_type'] != $templateType) {
                return 'Error: The template was already imported for a different template type';
            } elseif ($existingTemplate['template_app_type'] != $templateAppType) {
                return 'Error: The template was already imported for a different application type';
            }
            // Return true if the template import was successfully updated, false otherwise.
            $retVal = self::DBMutatePS(
                "UPDATE `tskp_templates` SET `template_name` = ?, `template_data` = ?, `template_description` = ?, `imported_at` = NOW() WHERE `source_template_id` = ?",
                'sxxs',
                $templateName,
                $validatedData,
                $templateDescription,
                $sourceTemplateId
            );
        } else {
            // Template import doesn't exist, create a new record.
            $retVal = self::DBInsertPS(
                "INSERT INTO `tskp_templates` (`source_template_id`, `template_name`, `template_type`, `template_app_type`, `template_data`,`template_description`, `imported_at`, `is_active`) VALUES (?, ?, ?, ?, ?, ?, NOW(), 2)",
                'ssssxx',
                $sourceTemplateId,
                $templateName,
                $templateType,
                $templateAppType,
                $validatedData,
                $templateDescription
            );
        }

        return true;
    }

    public static function UpdateTemplate(string $sourceTemplateId, string $templateName, $templateDescription)
    {
        return self::DBMutatePS(
            "UPDATE `tskp_templates` SET `template_name` = ?, `template_description` = ? WHERE `source_template_id` = ?",
            'sxs',
            $templateName,
            $templateDescription,
            $sourceTemplateId
        );
    }
    /**
     * Validates and sanitizes the JSON data.
     *
     * @param string $data The JSON data to validate and sanitize.
     *
     * @return string|false The validated and sanitized data if valid, false otherwise.
     */
    private static function ValidateAndSanitizeData($data): string|false
    {
        // Decode the JSON data
        $decodedData = json_decode($data, true);

        if ($decodedData === null) {
            return false; // Invalid JSON, return false
        }

        // Check for required fields in the JSON data
        if (
            !isset($decodedData['source_template_id']) ||
            !is_string($decodedData['source_template_id']) ||
            empty($decodedData['source_template_id'])
        ) {
            return false; // Invalid data, return false
        }

        // TODO: Perform additional checks and sanitization for the required params 
        // Remove the embedded images if exist

        // Remove <figure> elements from specific fields if they exist
        if (isset($decodedData['attributes']['teamroles'])) {
            $decodedData = self::RemoveFigureElements($decodedData, 'joinrequest_message', 'teamroles');
        }
        if (isset($decodedData['attributes']['team_action_item_template'])) {
            $decodedData = self::RemoveFigureElements($decodedData, 'description', 'team_action_item_template');
        }
        if (isset($decodedData['attributes']['team_touch_point_template'])) {
            $decodedData = self::RemoveFigureElements($decodedData, 'description', 'team_touch_point_template');
        }
        // If data is valid, return the validated and sanitized data
        return json_encode($decodedData);
    }


    /**
     * Retrieves all template imports from the database.
     * @param bool $active_only set to false to get all rows
     * @return array An array of template imports.
     */
    public static function GetAllTemplates(bool $active_only = true, string $template_app_type = ''): array
    {
        $importTemplateType = self::TEMPLATE_TYPE_GROUP;

        $app_type_filter = '';
        if ($template_app_type) {
            if (!in_array($template_app_type, self::TEMPLATE_APP_TYPE)) {
                return array();
            }
            $app_type_filter = " AND template_app_type='{$template_app_type}'";
        }

        $active_filter = '';
        if ($active_only) {
            $active_filter = ' AND is_active=1';
        }

        return self::DBGet("SELECT * FROM `tskp_templates` WHERE `template_type` = '{$importTemplateType}' {$app_type_filter} {$active_filter}");
    }

    /**
     * Retrieves a specific template import by its source template ID.
     *
     * @param string $sourceTemplateId The source template ID.
     *
     * @return array|null The template import if found, null otherwise.
     */
    public static function GetTskpTemplate(string $sourceTemplateId)
    {
        $templateData = self::DBGet("SELECT * FROM `tskp_templates` WHERE `source_template_id` = '{$sourceTemplateId}'");
        if ($templateData) {
            return $templateData[0];
        }
        return null;
    }

    /**
     * Deletes a template import by its source template ID.
     *
     * @param string $sourceTemplateId The source template ID.
     *
     * @return bool True if the template import was successfully deleted, false otherwise.
     */
    public static function DeleteTemplate(string $sourceTemplateId)
    {
        // Delete the template import from the database
        return self::DBMutatePS('DELETE FROM tskp_templates WHERE `source_template_id`= ?', 's', $sourceTemplateId);
    }

    public static function SetActiveState(string $sourceTemplateId, int $state)
    {
        return self::DBMutatePS('UPDATE tskp_templates SET is_active=? WHERE `source_template_id`= ?', 'is', $state, $sourceTemplateId);
    }

    private static function RemoveFigureElements($data, $field, $nestedField): array
    {
        foreach ($data['attributes'][$nestedField] as &$item) {
            if (isset($item[$field])) {
                $item[$field] = preg_replace('/<figure>.*<\/figure>/', '', $item[$field]);
                $item[$field] = str_replace(array("\r", "\n"), '', $item[$field]);
            }
        }
        return $data;
    }
}