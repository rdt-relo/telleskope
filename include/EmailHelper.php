<?php
date_default_timezone_set('UTC');

class EmailHelper extends Teleskope
{

    protected function __construct(int $cid = 0, array $fields = array())
    {
        parent::__construct(-1, $cid, $fields);
    }

    public function __destruct()
    {
    }

    public static function OutlookFixes(string $message): string
    {
        // Wrap all images in a table and center the table
        //$message = preg_replace('/<figure>(.*?)<\/figure>/', '<!--[if mso]><table align="center"><tr><td style="text-align:center"><![endif]--><figure style="margin:0 !important;" >$1</figure><!--[if mso]></td></tr></table><![endif]-->', $message);
        $message = preg_replace('/<figure([^>]*?)>(.*?)<\/figure>/', '<!--[if mso]><table align="center"><tr><td style="text-align:center"><![endif]--><figure$1>$2</figure><!--[if mso]></td></tr></table><![endif]-->', $message);
        $message = preg_replace('/<figcaption>/', '<!--[if mso]><br>&nbsp;<![endif]--><figcaption style="text-align:center;">', $message);
        $message = preg_replace('/<blockquote>(.*?)<\/blockquote>/', '<blockquote><!--[if mso]><br>&nbsp;<![endif]-->$1<!--[if mso]><br>&nbsp;<![endif]--></blockquote>', $message);
        if (strpos($message,"data-img-600px-safe") == false) {
            // If the html fragment images already contain width element for widths less than 600 they will contain "data-img-600px-safe" attribute.
            // If so then do not add a width tag, otherwise add a 600 pixels maximum tag.
            $message = str_replace('<img ', '<img width="600" ', $message);
        }

        return $message;
    }

    public static function GetEmailTemplateForGenericHTMLContent (string $content_subheader, string $content_subfooter, string $content, string $email_open_pixel=''): string
    {
        global $_COMPANY, $_ZONE;
        $template = file_get_contents(SITE_ROOT . '/email/template_generic.html');

        $appname = $_ZONE->val('app_type') === 'officeraven' ? 'Office Raven' : 'Affinities';
        $logo = $_COMPANY->val('logo');

        $replace_vars = ['[%COMPANY_NAME%]', '[%COMPANY_LOGO%]',
            '[%CONTENT_SUBHEADER%]', '[%CONTENT_SUBFOOTER%]','[%CONTENT_CONTENT%]','[%APP_NAME%]',
            '[%OPEN_PIXEL%]', '[%PUBLISHED_BY_INFO%]',
        ];
        $replacement_vars = [$_COMPANY->val('companyname'), $logo,
            $content_subheader, $content_subfooter, $content, $appname,
            $email_open_pixel, self::GetPublishedByInfo(),
        ];

        return str_replace($replace_vars, $replacement_vars, $template);
    }
    
    public static function GetEmailTemplateForMessage (string $content_subheader, string $content_subfooter, string $content, string $email_open_pixel, Message $message = null, string $grouplogo = ''): string
    {
        global $_COMPANY, $_ZONE;
        $template = file_get_contents(SITE_ROOT . '/email/template_message.html');

        $appname = $_ZONE->val('app_type') === 'officeraven' ? 'Office Raven' : 'Affinities';

        $logo = $_COMPANY->getAppCustomization()['emails']['show_group_logo_instead_of_company_logo'] ? $grouplogo : '';
        if (empty($logo)) {
            $logo = $_COMPANY->val('logo');
        }

        $generic_disclaimer_html = trim($_COMPANY->getAppCustomization()['emails']['generic_disclaimer_html']);

        $replace_vars = ['[%COMPANY_NAME%]', '[%COMPANY_LOGO%]',
            '[%CONTENT_SUBHEADER%]', '[%CONTENT_SUBFOOTER%]','[%CONTENT_CONTENT%]','[%APP_NAME%]',
            '[%OPEN_PIXEL%]', '[%GENERIC_DISCLAIMER_HTML%]',
            '[%ATTACHMENTS_CONTENT%]',
            '[%PUBLISHED_BY_INFO%]',
        ];
        $replacement_vars = [$_COMPANY->val('companyname'), $logo,
            $content_subheader, $content_subfooter, $content, $appname,
            $email_open_pixel, $generic_disclaimer_html,
            $message?->renderAttachmentsComponent('v21') ?? '',
            self::GetPublishedByInfo(),
        ];

        return str_replace($replace_vars, $replacement_vars, $template);
    }

    public static function GetEmailTemplateForDiscussion (string $groupname, string $grouplogo, string $chaptername, string $content_subtitle, string $content_title, string $content_subheader, string $content_subfooter, string $content, string $content_url, string $content_button, string $email_open_pixel, string $groupcolor1='#0077b5', string $groupcolor2='#0077b5'): string
    {
        return self::GetEmailTemplateForPost($groupname, $grouplogo, $chaptername, $content_subtitle, $content_title, $content_subheader, $content_subfooter, $content, $content_url, $content_button, $email_open_pixel, $groupcolor1, $groupcolor2, null);
    }
    public static function GetEmailTemplateForPost (string $groupname, string $grouplogo, string $chaptername, string $content_subtitle, string $content_title, string $content_subheader, string $content_subfooter, string $content, string $content_url, string $content_button, string $email_open_pixel, string $groupcolor1='#0077b5', string $groupcolor2='#0077b5', ?Post $post = null): string
    {
        global $_COMPANY, $_ZONE;
        $template = file_get_contents(SITE_ROOT . '/email/template_post.html');

        if (empty($groupname) || $groupname == 'All') {
            $groupname = 'one or more groups or one of the global administrators or zone administrators sent you this email';
        } else {
            $groupname = $groupname. ' or one of the global administrators or zone administrators sent you this email';
        }

        $appname = $_ZONE->val('app_type') === 'officeraven' ? 'Office Raven' : 'Affinities';

        $logo = $_COMPANY->getAppCustomization()['emails']['show_group_logo_instead_of_company_logo'] ? $grouplogo : '';
        if (empty($logo)) {
            $logo = $_COMPANY->val('logo');
        }

        $companyurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));

        $generic_disclaimer_html = trim($_COMPANY->getAppCustomization()['emails']['generic_disclaimer_html']);

        $content_button = 'Like and Comment';
        if (!$_COMPANY->getAppCustomization()['post']['comments']
            || !$_COMPANY->getAppCustomization()['post']['likes']
        ) {
            $content_button = sprintf('View %s', Post::GetCustomName(false));
        }

        $replace_vars = ['[%COMPANY_NAME%]', '[%COMPANY_LOGO%]', '[%COMPANY_URL%]',
            '[%GROUP_NAME%]', 'GROUP_LOGO', '[%CHAPTER_NAME%]', '#000001', '#000002',
            '[%CONTENT_SUBTITLE%]', '[%CONTENT_TITLE%]', '[%CONTENT_SUBHEADER%]', '[%CONTENT_SUBFOOTER%]',
            '[%CONTENT_CONTENT%]', '[%CONTENT_URL%]', '[%CONTENT_BUTTON%]', '[%APP_NAME%]',
            '[%OPEN_PIXEL%]', '[%GENERIC_DISCLAIMER_HTML%]',
            '[%ATTACHMENTS_CONTENT%]', '[%PUBLISHED_BY_INFO%]',
        ];
        $replacement_vars = [$_COMPANY->val('companyname'), $logo, $companyurl,
            $groupname, $grouplogo, $chaptername, $groupcolor1, $groupcolor2,
            $content_subtitle, $content_title, $content_subheader, $content_subfooter,
            $content, $content_url, $content_button, $appname,
            $email_open_pixel, $generic_disclaimer_html,
            $post?->renderAttachmentsComponent('v13') ?? '',
            self::GetPublishedByInfo(),
        ];

        return str_replace($replace_vars, $replacement_vars, $template);
    }

    public static function GetEmailTemplateForEvent (string $groupname, string $grouplogo, string $chaptername, string $content_subtitle, string $content_title, string $content_subheader, string $content_subfooter, string $content, string $content_url, string $content_button, string $event_date, string $event_header, string $email_open_pixel, string $groupcolor1='#0077b5', string $groupcolor2='#0077b5', array $eventVolunteerRequests = array(), ?Event $event = null): string
    {
        global $_COMPANY, $_ZONE;
        $template = file_get_contents(SITE_ROOT . '/email/template_event.html');

        if (empty($groupname) || $groupname == 'All') {
            $groupname = 'one or more groups or one of the global administrators or zone administrators sent you this email';
        } else {
            $groupname = $groupname. ' or one of the global administrators or zone administrators sent you this email';
        }
        $appname = $_ZONE->val('app_type') === 'officeraven' ? 'Office Raven' : 'Affinities';

        $logo = $_COMPANY->getAppCustomization()['emails']['show_group_logo_instead_of_company_logo'] ? $grouplogo : '';
        if (empty($logo)) {
            $logo = $_COMPANY->val('logo');
        }

        $companyurl = $_COMPANY->getAppURL($_ZONE->val('app_type'));
        
        if (!empty($eventVolunteerRequests)){
            $roles = "";
            foreach($eventVolunteerRequests as $volunteer){
                $roles  .= "<li>{$volunteer['volunteerRole']}: {$volunteer['volunteerNeeds']} role(s)</li>";
            }
            $volunteerRequired = <<<EOMEOM
            <div style="border: 0.5px solid #0077b5; padding-left:15px" >
                <p><strong>We need volunteers!</strong></p>
                <ul>
                {$roles}
                </ul>
                <p>If you are interested in volunteering, please go to the event page to sign up.</p>
            </div>
EOMEOM;
            $content .= $volunteerRequired;
        }
        
        $generic_disclaimer_html = trim($_COMPANY->getAppCustomization()['emails']['generic_disclaimer_html'] ?? '');

        $top_event_button_url = '';
        $top_event_button_text = '';
        if ($_COMPANY->getAppCustomization()['emails']['show_event_top_button_in_email']) {
            $top_event_button_url = $content_url;
            $top_event_button_text = $content_button;
        }

        $replace_vars = ['[%COMPANY_NAME%]', '[%COMPANY_LOGO%]', '[%COMPANY_URL%]',
            '[%GROUP_NAME%]', 'GROUP_LOGO', '[%CHAPTER_NAME%]', '#000001', '#000002',
            '[%CONTENT_SUBTITLE%]', '[%CONTENT_TITLE%]', '[%CONTENT_SUBHEADER%]', '[%CONTENT_SUBFOOTER%]',
            '[%TOP_EVENT_BUTTON_URL%]', '[%TOP_EVENT_BUTTON_TEXT%]',
            '[%CONTENT_CONTENT%]', '[%CONTENT_URL%]', '[%CONTENT_BUTTON%]', '[%APP_NAME%]',
            '[%EVENT_DATE%]', '[%EVENT_HEADER%]',
            '[%OPEN_PIXEL%]', '[%GENERIC_DISCLAIMER_HTML%]',
            '[%ATTACHMENTS_CONTENT%]', '[%PUBLISHED_BY_INFO%]',
        ];
        $replacement_vars = [$_COMPANY->val('companyname'), $logo, $companyurl,
            $groupname, $grouplogo, $chaptername, $groupcolor1, $groupcolor2,
            $content_subtitle, $content_title, $content_subheader, $content_subfooter,
            $top_event_button_url, $top_event_button_text,
            $content, $content_url, $content_button, $appname,
            $event_date, $event_header,
            $email_open_pixel, $generic_disclaimer_html,
            $event?->renderAttachmentsComponent('v14') ?? '',
            self::GetPublishedByInfo(),
        ];

        return str_replace($replace_vars, $replacement_vars, $template);
    }

    public static function GetEmailTemplateForNewsletter (string $content_subheader, string $content_subfooter, string $content, string $content_url, string $content_button, string $email_open_pixel): string
    {
        if (!empty($content_subheader)) {
            $note = '<div style="margin-top:10px;margin-bottom:10px; background-color:#80808026; padding:20px;"><b>Note:&nbsp;</b>' . stripcslashes($content_subheader) . '</div>';
            $content = $note . $content;
        }

        $published_by = self::GetPublishedByInfo();
        if ($published_by) {
            $content = str_replace(
                '</body>',
                '<div style="text-align: center;padding:5px 0 10px 0;">'.$published_by.'</div></body>',
                $content);
        }

        if (!empty($email_open_pixel)) {
            $content = str_replace(
                '</body>',
                '<div style="display:none;">' .$email_open_pixel. '</div></body>',
                $content);
        }

        return $content;
    }
    
    public static function EventVolunteerRoleOrganizerAssign(string $receiverName,string $roleName,string $eventName,string $eventDate,string $eventTime,string $eventCreatorOrHostedBy,string  $fromLabel, bool $isEventPublished, string $volunteerDescription=''): array
    {
        $subject = /*gettext*/(
            'Event Volunteer Role Assigned'
        );

        $descriptionPart = '';
        if ($volunteerDescription) {
            $descriptionPart = <<< EOMEOM
                Following is this role's description:
                <br/> 
                <div style="border: 1px solid darkgray; margin: 20px 10px; padding: 10px; text-align: left;">
                {$volunteerDescription}
                </div>
EOMEOM;
        }

        if ($isEventPublished)
            $message =  sprintf(/*gettext*/(
                'To %1$s:' . '<br/>' .
                '<br/>' .
                'You have been assigned the role of %2$s for the %3$s on %4$s at %5$s. You will receive an email containing the event details. If you have questions or concerns, reach out to %6$s' . '<br/>' .
                '%8$s'.
                '<br/>' .
                'Thank you!' . '<br/>' .
                '<br/>' .
                '%7$s'
            ), $receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy,$fromLabel,$descriptionPart);
        else
            $message = sprintf(/*gettext*/ (
                'To %1$s:' . '<br/>' .
                '<br/>' .
                'You have been assigned the role of %2$s for the %3$s on %4$s at %5$s. The event has not been published yet, but once it has been published you will receive an email containing the event details. If you have questions or concerns, reach out to %6$s' . '<br/>' .
                '%8$s'.
                '<br/>' .
                'Thank you!' . '<br/>' .
                '<br/>' .
                '%7$s'
            ), $receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy,$fromLabel,$descriptionPart);
        return array('subject'=>$subject,'message'=>$message);
    }

    public static function EventVolunteerRoleOrganizerUpdates(string $receiverName,string $roleName,string $eventName,string $eventDate,string $eventTime,string $eventCreatorOrHostedBy,string  $fromLabel, bool $isEventPublished, string $volunteerDescription = ''): array
    {
        $subject = /*gettext*/(
            'Event Volunteer Role Updated'
        );


        $descriptionPart = '';
        if ($volunteerDescription) {
            $descriptionPart = <<< EOMEOM
                Following is this role's description:
                <br/> 
                <div style="border: 1px solid darkgray; margin: 20px 10px; padding: 10px; text-align: left;">
                {$volunteerDescription}
                </div>
EOMEOM;
        }

        if ($isEventPublished)
            $message = sprintf(/*gettext*/ (
                'To %1$s:' . '<br/>' .
                '<br/>' .
                'Your role of %2$s for the %3$s on %4$s at %5$s has been updated! You will receive an email containing the event details. If you have questions or concerns, reach out to %6$s. ' . '<br/>' .
                '%8$s'.
                '<br/>' .
                'Thank you!' . '<br/>' .
                '<br/>' .
                '%7$s'), $receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy, $fromLabel,$descriptionPart);
        else
            $message = sprintf(/*gettext*/ (
                'To %1$s:' . '<br/>' .
                '<br/>' .
                'Your role of %2$s for the %3$s on %4$s at %5$s has been updated! The event has not been published yet, but once it has been published you will receive an email containing the event details. If you have questions or concerns, reach out to %6$s. ' . '<br/>' .
                '%8$s'.
                '<br/>' .
                'Thank you!' . '<br/>' .
                '<br/>' .
                '%7$s'
            ), $receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy, $fromLabel,$descriptionPart);

        return array('subject' => $subject, 'message' => $message);
    }

    public static function EventVolunteerRoleOrganizerRemove(string $receiverName,string $roleName,string $eventName,string $eventDate,string $eventTime,string $eventCreatorOrHostedBy,string  $fromLabel): array
    {
        $subject = /*gettext*/(
            'Event Volunteer Role Removed'
        );

        $message = sprintf(/*gettext*/(
            'To %1$s:' . '<br/>' .
            '<br/>' .
            'Your role of %2$s for the %3$s on %4$s at %5$s has been removed. If you have questions or concerns, reach out to %6$s. ' . '<br/>' .
            '<br/>' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%7$s'
        ), $receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy, $fromLabel);
        return array('subject'=>$subject,'message'=>$message);
    }

    public static function EventVolunteerRoleSomeoneSignsup(string $receiverName,string $roleName,string $eventName,string $eventDate,string $eventTime,string $eventCreatorOrHostedBy,string  $fromLabel, string $volunteerDescription = ''): array
    {
        // 'Thank you for signing up for the role of %2$s for %3$s on %4$s at %5$s! This position has not been confirmed yet; you will receive an additional email with confirmation. If you have questions or concerns, reach out to %6$s' . '<br/>' .
        $subject = /*gettext*/(
            'Thank you for signing up as an Event Volunteer!'
        );


        $descriptionPart = '';
        if ($volunteerDescription) {
            $descriptionPart = <<< EOMEOM
                Following is this role's description:
                <br/> 
                <div style="border: 1px solid darkgray; margin: 20px 10px; padding: 10px; text-align: left;">
                {$volunteerDescription}
                </div>
EOMEOM;
        }

        $message = sprintf(/*gettext*/ (
            'To %1$s:' . '<br/>' .
            '<br/>' .
            'Thank you for signing up for the role of %2$s for %3$s on %4$s at %5$s! This role has been approved, and we are so grateful for your time and effort! If you have questions or concerns, reach out to %6$s' . '<br/>' .
            '<br/>' .
            '%8$s'.
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%7$s'
        ), $receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy, $fromLabel, $descriptionPart);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function EventVolunteerRoleSomeoneUpdates(string $oldRoleName,string $receiverName,string $roleName,string $eventName,string $eventDate,string $eventTime,string $eventCreatorOrHostedBy,string  $fromLabel, string $volunteerDescription=''): array
    {
        //'Your request to update your role from %2$s to %3$s for %4$s on %5$s at %6$s has been received! This position has not been confirmed yet; you will receive an additional email with confirmation. If you have questions or concerns, reach out to %7$s. ' . '<br/>' .
        $subject = /*gettext*/(
            'Event Volunteer Role Change Request'
        );
        $descriptionPart = $volunteerDescription ? "Following is this role's description: <br/> $volunteerDescription" : '';
        $message = sprintf(/*gettext*/ (
            'To %1$s:' . '<br/>' .
            '<br/>' .
            'Your request to update your role from %2$s to %3$s for %4$s on %5$s at %6$s has been received and updated! If you have questions or concerns, reach out to %7$s. ' . '<br/>' .
            '%9$s'.
            '<br/>' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%8$s'
        ), $receiverName, $oldRoleName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy, $fromLabel,$descriptionPart);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function EventVolunteerRoleApproval(string $receiverName,string $roleName,string $eventName,string $eventDate,string $eventTime,string $eventCreatorOrHostedBy,string  $fromLabel): array
    {
        $subject = /*gettext*/(
            'Thank you and Congrats on your new volunteer role! '
        );

        $message = sprintf(/*gettext*/ (
            'To %1$s:' . '<br/>' .
            '<br/>' .
            'Thank you for signing up for the role of %2$s for %3$s on %4$s at %5$s! This role has been approved, and we are so grateful for your time and effort! Thank you for signing up. If you have questions or concerns, reach out to %6$s. ' . '<br/>' .
            '<br/>' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%7$s'
        ), $receiverName, $roleName, $eventName, $eventDate, $eventTime, $eventCreatorOrHostedBy, $fromLabel);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function GroupBudgetUpdated(string $groupName, string $approverName, string $budgetYear, string $budgetAmount): array
    {
        global $_COMPANY;

        $groupNameWithExtension = $groupName.' '.$_COMPANY->getAppCustomization()['group']['name-short'];

        $subject = sprintf(/*gettext*/(
        'Budget Updated for %1$s'
        ),$groupNameWithExtension);


        $message = sprintf(/*gettext*/(
            'To %1$s Leaders (budgets):' . '<br/>' .
            '<br/>' .
            'Budget for %2$s has been updated by %3$s.' .
            '<br/>' .
            'Budget Summary ' . '<br/>' .
            '<p class="info-block">' .
            'Budget Year: %4$s' . '<br/>' .
            'Budget Amount: %5$s' . '<br/>' .
            '</p>' .
            '<br/>' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%3$s'
        ),$groupName,$groupNameWithExtension,$approverName,$budgetYear,$budgetAmount);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function GroupBudgetRequest(string $groupName, string $approverName, string $requesterName, string $requestedAmount, string $requestPurpose, string $requestDate, string $requestDescription, ?BudgetRequest $budget_request = null): array
    {
        global $_COMPANY, $_ZONE;
        $adminUrl = Url::GetZoneAwareUrlBase($_ZONE->id(), 'admin') . 'budget#budgetRequests';

        $subject = sprintf(/*gettext*/(
            'New Budget Request from %1$s'
        ),$groupName);

        $message = sprintf(/*gettext*/(
            'To %2$s Administrators (budgets):' . '<br/>' .
            '<br/>' .
            'There is a new budget request from %1$s. Please log in to your <a href="%8$s">admin account </a> and select the budget tab. From there you can approve or deny this request. ' . '<br/>' .
            '<br/>' .
            'Budget Request Summary ' . '<br/>' .
            '<p class="info-block">' .
            'Requested By: %3$s' . '<br/>' .
            'Requested Amount: %4$s' . '<br/>' .
            'Purpose: %5$s' . '<br/>' .
            'Use Date: %6$s' . '<br/>' .
            'Description: %7$s' . '<br/>' .
            '%10$s' .
            '</p>' .
            '%9$s' .
            '<br/>' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%1$s'
        ),$groupName,$approverName,$requesterName,$requestedAmount,$requestPurpose,$requestDate,$requestDescription,$adminUrl, $budget_request?->renderAttachmentsComponent('v15') ?? '', $budget_request?->renderCustomFieldsComponent('v6') ?? '');

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function GroupBudgetRequestUpdate(string $groupName, string $approverName, string $requesterName, string $requestedAmount, string $requestPurpose, string $requestDate, string $requestDescription, ?BudgetRequest $budget_request = null)
    {
        global $_COMPANY, $_ZONE;
        $adminUrl = Url::GetZoneAwareUrlBase($_ZONE->id(), 'admin') . 'budget#budgetRequests';

        $subject = sprintf(/*gettext*/(
            'Updated Budget Request from %1$s'
        ),$groupName);

        $message = sprintf(/*gettext*/ (
            'To %2$s Administrators (budgets):' . '<br/>' .
            '<br/>' .
            'There is an updated request from %1$s. Please log in to your <a href="%8$s">admin account </a> and select the budget tab. From there you can approve or deny this request. ' . '<br/>' .
            '<br/>' .
            'Budget Request Summary ' . '<br/>' .
            '<p class="warning-block">' .
            ' Requested By: %3$s' . '<br/>' .
            ' Requested Amount: %4$s' . '<br/>' .
            ' Purpose: %5$s' . '<br/>' .
            ' Use Date: %6$s' . '<br/>' .
            ' Description: %7$s' . '<br/>' .
            '%10$s' .
            '</p>' .
            '%9$s' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%1$s'
        ), $groupName, $approverName, $requesterName, $requestedAmount, $requestPurpose, $requestDate, $requestDescription,$adminUrl, $budget_request?->renderAttachmentsComponent('v15') ?? '', $budget_request?->renderCustomFieldsComponent('v6') ?? '');

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function GroupBudgetRequestApproved(string $groupName, string $requesterName, string $requestPurpose, string $requestedAmount, string $approvedAmount, string $approverComment, string $approverName, ?BudgetRequest $budget_request = null)
    {
        global $_COMPANY;

        $subject = sprintf(/*gettext*/(
            'Approved: Budget Request for %1$s'
        ),$groupName);

        $message = sprintf(/*gettext*/(
            'To %2$s:' . '<br/>' .
            '<br/>' .
            'Congratulations! Your budget has been approved by the admin. Below are the details of your approved budget. ' . '<br/>' .
            '<br/>' .
            'Budget Request Details ' . '<br/>' .
            '<p class="success-block">' .
            'Purpose: %3$s' . '<br/>' .
            'Requested Amount: %4$s' . '<br/>' .
            'Approval Status: Approved' . '<br/>' .
            'Approved Amount: %5$s' . '<br/>' .
            'Approver Comment: %6$s' . '<br/>' .
            'Approver Name: %7$s' . '<br/>' .
            '%9$s' .
            '</p>' .
            '%8$s' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%7$s'
        ),$groupName,$requesterName,$requestPurpose,$requestedAmount,$approvedAmount,$approverComment,$approverName, $budget_request?->renderAttachmentsComponent('v15') ?? '',  $budget_request?->renderCustomFieldsComponent('v6') ?? '');

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function GroupBudgetRequestDenied(string $groupName, string $requesterName, string $approverName, string $approverEmail, string $requestPurpose, string $requestedAmount, string $approvedAmount, string $approverComment, ?BudgetRequest $budget_request = null)
    {
        global $_COMPANY;

        $subject = sprintf(/*gettext*/(
            'Denied: Budget Request for %1$s'
        ),$groupName);

        $message = sprintf(/*gettext*/(
            'To %2$s:' . '<br/>' .
            '<br/>' .
            'Your proposed budget has been denied. Please reach out to %3$s (%4$s) with any questions or concerns. ' . '<br/>' .
            '<br/>' .
            'Budget Request Details ' . '<br/>' .
            '<p class="error-block">' .
            'Purpose: %5$s' . '<br/>' .
            'Requested Amount: %6$s' . '<br/>' .
            'Approval Status: Denied' . '<br/>' .
            'Approved Amount: %7$s' . '<br/>' .
            'Approver Comment: %8$s' . '<br/>' .
            'Processed By: %3$s' . '<br/>' .
            '%10$s' .
            '</p>' .
            '%9$s' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%3$s'
        ), $groupName,$requesterName,$approverName,$approverEmail,$requestPurpose,$requestedAmount,$approvedAmount,$approverComment, $budget_request?->renderAttachmentsComponent('v15') ?? '', $budget_request?->renderCustomFieldsComponent('v6') ?? '');

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function ChapterBudgetUpdated(string $chapterName, int $groupid, string $groupName, string $approverName, string $budgetYear, string $budgetAmount): array
    {
        global $_COMPANY;


        $subject = sprintf(/*gettext*/(
        'Budget Updated for %1$s > %2$s'
        ),$groupName,$chapterName);


        $message = sprintf(/*gettext*/(
            'To %1$s > %2$s Leaders (budgets):' . '<br/>' .
            '<br/>' .
            'Budget for %1$s > %2$s has been updated by %3$s.' . '<br/>' .
            '<br/>' .
            'Budget Summary ' . '<br/>' .
            '<p class="info-block">' .
            'Budget Year: %4$s' . '<br/>' .
            'Updated Budget Amount: %5$s' . '<br/>' .
            '</p>' .
            '<br/>' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%3$s'
        ),$groupName,$chapterName,$approverName,$budgetYear,$budgetAmount);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function ChapterBudgetRequest(string $chapterName, int $groupid, string $groupName, string $approverName, string $requesterName, string $requestedAmount, string $requestPurpose, string $requestDate, string $requestDescription, ?BudgetRequest $budget_request = null)
    {
        global $_COMPANY, $_ZONE;
        $appUrl = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'manage?id=' . $_COMPANY->encodeId($groupid) . '#budget';

        $subject = sprintf(/*gettext*/(
            'New Budget Request from %1$s > %2$s'
        ),$groupName,$chapterName,);

        $message = sprintf(/*gettext*/(
            'To %3$s Leaders (budgets):' . '<br/>' .
            '<br/>' .
            'There is a new budget request from %2$s > %1$s. Please log in to  your <a href="%9$s">application account </a> and select the %2$s > Manage > Budget > View Past Requests. From there you can approve or deny this request. ' . '<br/>' .
            '<br/>' .
            'Budget Request Summary ' . '<br/>' .
            '<p class="info-block">' .
            'Requested By: %4$s' . '<br/>' .
            'Requested Amount: %5$s' . '<br/>' .
            'Purpose: %6$s' . '<br/>' .
            'Use Date: %7$s' . '<br/>' .
            'Description: %8$s' . '<br/>' .
            '%11$s' .
            '</p>' .
            '%10$s' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%2$s > %1$s'
        ), $chapterName, $groupName, $approverName, $requesterName, $requestedAmount, $requestPurpose, $requestDate, $requestDescription, $appUrl, $budget_request?->renderAttachmentsComponent('v15') ?? '', $budget_request?->renderCustomFieldsComponent('v6') ?? '');

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function ChapterBudgetRequestUpdate(string $chapterName, int $groupid, string $groupName, string $approverName, string $requesterName, string $requestedAmount, string $requestPurpose, string $requestDate, string $requestDescription, ?BudgetRequest $budget_request = null)
    {
        global $_COMPANY, $_ZONE;
        $appUrl = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'manage?id=' . $_COMPANY->encodeId($groupid) . '#budget';

        $subject = sprintf(/*gettext*/(
            'Updated Budget Request from %1$s > %2$s'
        ),$groupName,$chapterName);

        $message = sprintf(/*gettext*/(
            'To %3$s Leaders (budgets):' . '<br/>' .
            '<br/>' .
            'There is an updated budget request from %2$s > %1$s. Please log in to  your <a href="%9$s">application account </a> and select the %2$s > Manage > Budget > View Past Requests. From there you can approve or deny this request. ' . '<br/>' .
            '<br/>' .
            'Budget Request Summary ' . '<br/>' .
            '<p class="warning-block">' .
            'Requested By: %4$s' . '<br/>' .
            'Requested Amount: %5$s' . '<br/>' .
            'Purpose: %6$s' . '<br/>' .
            'Use Date: %7$s' . '<br/>' .
            'Description: %8$s' . '<br/>' .
            '%11$s' .
            '</p>' .
            '%10$s' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%2$s > %1$s'
        ), $chapterName, $groupName, $approverName, $requesterName, $requestedAmount, $requestPurpose, $requestDate, $requestDescription, $appUrl, $budget_request?->renderAttachmentsComponent('v15') ?? '', $budget_request?->renderCustomFieldsComponent('v6') ?? '');

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function ChapterBudgetRequestApproved(string $chapterName, int $groupid, string $groupName, string $requesterName, string $requestPurpose, string $requestedAmount, string $approvedAmount, string $approverComment, string $approverName, ?BudgetRequest $budget_request = null)
    {
        global $_COMPANY;

        $subject = sprintf(/*gettext*/(
            'Approved: Budget Request for %1$s > %2$s'
        ),$groupName,$chapterName);

        $groupNameWithExtension = $groupName.' '.$_COMPANY->getAppCustomization()['group']['name-short'];

        $message = sprintf(/*gettext*/(
            'To %3$s:' . '<br/>' .
            '<br/>' .
            'Congratulations! Your budget has been approved by %2$s. Below are the details of your approved budget. ' . '<br/>' .
            '<br/>' .
            'Budget Request Details ' . '<br/>' .
            '<p class="success-block">' .
            'Purpose: %4$s' . '<br/>' .
            'Requested Amount: %5$s' . '<br/>' .
            'Approval Status: Approved' . '<br/>' .
            'Approved Amount: %6$s' . '<br/>' .
            'Approver Comment: %7$s' . '<br/>' .
            'Approver Name: %8$s' . '<br/>' .
            '%10$s' .
            '</p>' .
            '%9$s' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%2$s'), $chapterName, $groupNameWithExtension, $requesterName, $requestPurpose, $requestedAmount, $approvedAmount, $approverComment, $approverName, $budget_request?->renderAttachmentsComponent('v15') ?? '', $budget_request?->renderCustomFieldsComponent('v6') ?? '');

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function ChapterBudgetRequestDenied(string $chapterName, int $groupid, string $groupName, string $requesterName, string $approverName, string $approverEmail, string $requestPurpose, string $requestedAmount, string $approvedAmount, string $approverComment, ?BudgetRequest $budget_request = null)
    {
        global $_COMPANY;

        $subject = sprintf(/*gettext*/(
            'Denied: Budget Request for %1$s > %2$s'
        ),$groupName,$chapterName);

        $groupNameWithExtension = $groupName.' '.$_COMPANY->getAppCustomization()['group']['name-short'];

        $message = sprintf(/*gettext*/(
            'To %3$s:' . '<br/>' .
            '<br/>' .
            'Your proposed budget request has been denied. Please reach out to %4$s (%5$s) with any questions or concerns. ' . '<br/>' .
            '<br/>' .
            'Budget Request Details ' . '<br/>' .
            '<p class="error-block">' .
            'Purpose: %6$s' . '<br/>' .
            'Requested Amount: %7$s' . '<br/>' .
            'Approval Status: Denied' . '<br/>' .
            'Approved Amount: %8$s' . '<br/>' .
            'Approver Comment: %9$s' . '<br/>' .
            'Processed by: %4$s' . '<br/>' .
            '%11$s' .
            '</p>' .
            '%10$s' .
            'Thank you!' . '<br/>' .
            '<br/>' .
            '%2$s'
        ), $chapterName, $groupNameWithExtension, $requesterName, $approverName, $approverEmail, $requestPurpose, $requestedAmount, $approvedAmount, $approverComment, $budget_request?->renderAttachmentsComponent('v15') ?? '', $budget_request?->renderCustomFieldsComponent('v6') ?? '');

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    ##
    ## Survey Email Section
    ##
    public static function NewSurveyResponse(int $groupid, string $surveySubmittedBy,string $surveyName,string $groupName)
    {
        global $_COMPANY;
        $subject = /*gettext*/('New Survey Response');
        $message = sprintf(/*gettext*/(
            'A new response from %1$s has been submitted for %2$s ! Check out the results in the Manage > Surveys section of %3$s!' . '<br>' .
            '<br>' .
            'Thank you!' . '<br>' .
            '<br>' .
            '%3$s'
        ),$surveySubmittedBy,$surveyName,$groupName);

        $template = $_COMPANY->getEmailTemplateForSurveyEmails($groupid);
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function SurveySubmissionConfirmation(int $groupid, string $surveySubmittedBy,string $surveyName,string $groupName)
    {
        global $_COMPANY;
        $subject = /*gettext*/('Thank You for Completing a Survey!');
        //'Thank you for submitting your responses to the %2$s on the %3$s section!Your opinion and voice is important and valued, and your responses will be reviewed soon! If you wish to make changes to your survey, you may use this edit survey response link.' . '<br>' .
        $message = sprintf(/*gettext*/(
            'To %1$s:' . '<br/>' .
            '<br>' .
            'Thank you for submitting your responses to the %2$s!' . '<br>' .
            '<br>' .
            'Thank you!' . '<br>' .
            '<br>' .
            '%3$s'
        ),$surveySubmittedBy,$surveyName,$groupName);

        $template = $_COMPANY->getEmailTemplateForSurveyEmails($groupid);
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function TeamInactivity(string $toName, string $teamName, string $teamCustomName, string $teamUrl, string $groupName, string $groupCustomName, string $inactivityDays, string $notificationDaysAfter, string $notificationFrequency)
    {      
        global $_COMPANY;
        $subject = sprintf(/*gettext*/(
            'Your %2$s needs an update, it\'s time check-in'
        ), $teamName, $teamCustomName);

        $teamName = htmlspecialchars($teamName);
        $message = sprintf(/*gettext*/(
                    'Hi %1$s,' . '<br/>' .
                    '<br/>' .
                    'Your %3$s (%2$s) in the %5$s %6$s has not posted or logged any activity since the last %7$s days. As a reminder, if you are inactive in the system for more than %8$s days you will continue to receive these notifications every %9$s days.' . '<br/>' .
                    '<br/>' .
                    'The below link will take your %3$s page. If you have questions or need additional assistance, please reach out to your administrator' . '<br/>' .
                    '<br/>' .
                    '<br/>' .
                    'Link : <a href="%4$s">%4$s</a>' .
                    '<br/>' .
                    '<br/>'
        ),$toName, $teamName, $teamCustomName, $teamUrl, $groupName, $groupCustomName, $inactivityDays, $notificationDaysAfter, $notificationFrequency);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function TeamUpcomingReminder(string $toName, string $teamName, string $teamCustomName, string $teamUrl, string $groupName, string $groupCustomName, string $taskType, string $taskTitle, string $taskDueDate)
    {
        global $_COMPANY;
        $subject = sprintf(/*gettext*/(
            'Reminder, your %2$s has an upcoming %3$s'
        ), $teamName, $teamCustomName, $taskType);

        $teamName = htmlspecialchars($teamName);
        $message = sprintf(/*gettext*/(
                    'Hi %1$s,' . '<br/>' .
                    '<br/>' .
                    'A friendly reminder from your %3$s (%2$s) in %5$s %6$s'. '<br/>' .
                    '<br/>' .
                    'Your <strong>%7$s (%8$s) </strong> is due on <strong>%9$s</strong>'  . '<br/>' .
                    '<br/>' .
                    'The below link will take you to the homepage of the %2$s %3$s site. If you have questions or need additional assistance, please reach out to your administrator' . '<br/>' .
                    '<br/>' .
                    '<br/>' .
                    'Link : <a href="%4$s">%4$s</a>' .
                    '<br/>'
        ), $toName, $teamName, $teamCustomName, $teamUrl, $groupName, $groupCustomName, $taskType, $taskTitle, $taskDueDate);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function TeamOverdueReminder(string $toName, string $teamName, string $teamCustomName, string $teamUrl, string $groupName, string $groupCustomName, string $taskType, string $taskTitle, string $taskDueDate)
    {
        global $_COMPANY;
        $subject = sprintf(/*gettext*/(
        'REMINDER: Your %2$s has an overdue %3$s'
        ), $teamName, $teamCustomName, $taskType);
        $button = self::BuildHtmlButton('Go to my ' . $teamCustomName, $teamUrl);

        $teamName = htmlspecialchars($teamName);
        $message = sprintf(/*gettext*/(
            'Dear %1$s,' . '<br/>' .
            '<br/>' .
            'This is a friendly reminder from your %3$s (%2$s) that you have an overdue %7$s'. '<br/>' .
            '<br/>' .
            'Your <strong>%8$s %7$s</strong> was originally scheduled for <strong>%9$s</strong>, but these dates are flexible. You can reschedule or update the status of your %7$s if you are working to complete it.'. '<br/>'.
            '<br/>' .
            '<br/>' .
            '%10$s' .
            '<br/>'
        ), $toName, $teamName, $teamCustomName, $teamUrl, $groupName, $groupCustomName, $taskType, $taskTitle, $taskDueDate, $button);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function TopicApproval_requestNotifyRequester (int $topicId, string $topicType, string $topicTitle, string $requesterName, string $processorName)
    {
        global $_COMPANY, $_ZONE;
        $subject = sprintf(/*gettext*/('New approval request for %1$s %2$s'), $topicType, $topicTitle);
        $encTopicId = $_COMPANY->encodeIdForReport($topicId);

        $message = sprintf(/*gettext*/(
            'Dear %1$s:' . '<br/>' .
            '<br/>' .
            'We received your approval request for %2$s <b>%3$s</b> (%2$s ID : %5$s).' . '<br/>' .
            '<br/>' .
            'Once your approval request is processed, you will receive an additional notification for approval or denial.' . '<br/>' .
            'You can track the approval status by selecting "View Approval Status" from the menu you used to generate the approval request.' . '<br/>' .
            'Sincerely' . '<br/>' .
            '<br/>' .
            '%4$s'
        ), $requesterName, $topicType, $topicTitle, $processorName, $encTopicId);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function TopicApproval_requestNotifyApprovers(int $topicId, string $topicType, string $topicTitle, string $requesterName, array $actionUrls, string $requestNote, Approval $approval)
    {
        global $_COMPANY, $_ZONE;
        $subject = sprintf(/*gettext*/('New approval request for %1$s %2$s'), $topicType, $topicTitle);
        $encTopicId = $_COMPANY->encodeIdForReport($topicId);

        $requestNote = trim(stripcslashes($requestNote));
        $requestNoteHtml = '';
        if ($requestNote) {
            $requestNoteHtml = <<<HTML
            <div style="background-color:#80808026; padding:20px;">
              <strong>Note:&nbsp;</strong>
              {$requestNote}
            </div>
HTML;
        }

        $message = sprintf(/*gettext*/(
            '%6$s' .
            '<p>' .
            'There is a new approval request for %2$s <b>%3$s</b> (%2$s ID : %7$s) from %1$s. ' . '<br/>' .
            '<br/>' .
            'Please log in to your application account and select the <a href="%4$s">My Approvals </a><strong>> %2$s approvals</strong> tab under profile. From there you can approve, deny or assign this request. ' .
            '</p>'
        ), $requesterName, $topicType, $topicTitle, $actionUrls['my_approvals'],  $actionUrls['admin'], $requestNoteHtml, $encTopicId);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function TopicApproval_assignedNotifyNewApprover(int $topicId, string $topicType, string $topicTitle, string $assignerName, string $newApproverName, array $actionUrls)
    {
        global $_COMPANY, $_ZONE;
        $subject = sprintf(/*gettext*/($topicType.' Approval Assignment'));
        $encTopicId = $_COMPANY->encodeIdForReport($topicId);

        $message = sprintf(/*gettext*/(
            'Dear %1$s:' . '<br/>' .
            '<br/>' .
            '%6$s has assigned you to work on the approval request for %2$s <b>%3$s</b> (%2$s ID: %7$s).' . '<br/>' .
            '<br/>' .
            'Please log in to your application account and select the <a href="%4$s">My Approvals </a><strong>> %2$s Approvals</strong> tab under Profile. From there you can approve, deny or reassign this request. ' .
            'If you are a Platform Administrator, you can also manage this request from the <a href="%5$s">Admin Panel </a> %2$s Approval tab.' . '<br/>' .
            '<br/>' .
            '<br/>' .
            ''
        ), $newApproverName, $topicType, $topicTitle, $actionUrls['my_approvals'],  $actionUrls['admin'], $assignerName, $encTopicId);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message = str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function TopicApproval_newNoteNotification (int $topicId, string $topicType, string $topicTitle, string $noteAddedBy, string $note, TopicApprovalLog $approval_log)
    {
        global $_COMPANY, $_ZONE;
        $subject = sprintf(/*gettext*/('New note added to approval request %1$s %2$s'), $topicType, $topicTitle);
        $encTopicId = $_COMPANY->encodeIdForReport($topicId);

        $message = sprintf(/*gettext*/(
            '%3$s has added a new note on the approval request for %1$s <b>%2$s</b> (%1$s ID : %6$s).' . '<br/>' .
            '<br/>' .
            'Note: %4$s ' .
            '<br/> %5$s'
        ), $topicType, $topicTitle, $noteAddedBy, $note,
            $approval_log->renderAttachmentsComponent('v27'), $encTopicId
        );

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function TopicApproval_approved (int $topicId, int $approvalStage, string $approvalStageStatus, string $topicTitle, ?User $requester, User $approver, string $approverNote, TopicApprovalLog $approval_log, string $stage_approval_email_subject, string $stage_approval_email_body)
    {
        global $_COMPANY, $_ZONE;
        $encTopicId = $_COMPANY->encodeIdForReport($topicId);
        $replace_vars = [
            '[[APPROVER_FIRST_NAME]]', '[[APPROVER_LAST_NAME]]', '[[REQUESTER_FIRST_NAME]]', '[[REQUESTER_LAST_NAME]]', '[[APPROVAL_TOPIC_TITLE]]', '[[APPROVAL_TOPIC_ID]]', '[[APPROVAL_STAGE]]', '[[APPROVAL_STAGE_STATUS]]', '[[APPROVER_NOTE]]', '[[APPROVAL_LOG_ATTACHMENTS]]'
        ];
        $replacement_vars = [
            $approver->val('firstname'), $approver->val('lastname'),  ($requester ? $requester->val('firstname') : '-'), ($requester? $requester->val('lastname') : '-'), $topicTitle, $encTopicId, $approvalStage, $approvalStageStatus, $approverNote, $approval_log->renderAttachmentsComponent('v27')
        ];

        $stage_approval_email_subject = str_replace($replace_vars, $replacement_vars, $stage_approval_email_subject);
        $stage_approval_email_body = str_replace($replace_vars, $replacement_vars, $stage_approval_email_body);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$stage_approval_email_body,$template);
        
        return array('subject'=>$stage_approval_email_subject,'message'=>$message);
    }

    public static function TopicApproval_denied (int $topicId, int $approvalStage, string $approvalStageStatus, string $topicTitle, ?User $requester, User $approver, string $approverNote, TopicApprovalLog $approval_log, string $stage_denial_email_subject, string $stage_denial_email_body)
    {
        global $_COMPANY, $_ZONE;
        $encTopicId = $_COMPANY->encodeIdForReport($topicId);
        $replace_vars = [
            '[[APPROVER_FIRST_NAME]]', '[[APPROVER_LAST_NAME]]', '[[REQUESTER_FIRST_NAME]]', '[[REQUESTER_LAST_NAME]]', '[[APPROVAL_TOPIC_TITLE]]', '[[APPROVAL_TOPIC_ID]]', '[[APPROVAL_STAGE]]', '[[APPROVAL_STAGE_STATUS]]', '[[APPROVER_NOTE]]', '[[APPROVAL_LOG_ATTACHMENTS]]'
        ];
        $replacement_vars = [
            $approver->val('firstname'), $approver->val('lastname'), ($requester ? $requester->val('firstname') : '-'), ($requester? $requester->val('lastname') : '-'), $topicTitle, $encTopicId, $approvalStage, $approvalStageStatus, $approverNote, $approval_log->renderAttachmentsComponent('v27')
        ];

        $stage_denial_email_subject = str_replace($replace_vars, $replacement_vars, $stage_denial_email_subject);
        $stage_denial_email_body = str_replace($replace_vars, $replacement_vars, $stage_denial_email_body);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$stage_denial_email_body,$template);

        return array('subject'=>$stage_denial_email_subject,'message'=>$message);
    }

    public static function TopicApproval_notifyNextStageApprovers(int $topicId, string $topicType, string $topicTitle, string $requesterName, array $actionUrls, string $previousStageAction, int $previousStageNumber, int $nextStageNumber)
    {
        global $_COMPANY, $_ZONE;
        $topicTitleSub = html_entity_decode($topicTitle);
        $subject = sprintf(/*gettext*/('Approval request for %1$s %2$s - stage %3$d %4$s '), $topicType, $topicTitleSub, $previousStageNumber, $previousStageAction);
        $encTopicId = $_COMPANY->encodeIdForReport($topicId);

        $message = sprintf(/*gettext*/(
            '<br/>' .
            'Approval request for %2$s <b>%3$s</b> (%2$s ID : %9$s) from %1$s was %7$s in stage %6$d. It now requires a stage %8$d decision.' . '<br/>' .
            '<br/>' .
            'Please log in to your application account and select the <a href="%4$s">My Approvals </a><strong>> %2$s approvals</strong> tab under profile. From there you can approve, deny or assign this request. ' .
            'If you are a platform administrator, you can also manage this request from <a href="%5$s">Admin Panel</a> %2$s approval tab.' . '<br/>' .
            '<br/>'
        ), $requesterName, $topicType, $topicTitle, $actionUrls['my_approvals'],  $actionUrls['admin'], $previousStageNumber, $previousStageAction, $nextStageNumber, $encTopicId);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function InviteUserForTeamByRoleEmailTeamplate (User $requestReceiver, User $requestSender, string $requestedRoleName, string $requestAcceptLink, string $groupName, string $teamCustomName)
    {
        global $_COMPANY, $_ZONE;

        $requestReceiverName = $requestReceiver->getFullName();
        $requestSenderName = $requestSender->getFullName();
        $requestSenderEmail = $_COMPANY->isValidAndRoutableEmail($requestSender->val('email')) ? $requestSender->val('email') : '';
        $requestSenderAttributesAsHtml = <<< EOMEOM
            <br>
            <strong>Name</strong>: {$requestSender->getFullName()}<br>
            <strong>Email</strong>: {$requestSenderEmail}<br>
            <strong>Job Title</strong>: {$requestSender->val('jobtitle')}<br>
            <strong>Department</strong>: {$requestSender->getDepartmentName()}<br>
            <strong>Location</strong>: {$requestSender->getBranchName()}<br>
            <strong>Timezone</strong>: {$requestSender->val('timezone')}<br>
            <br>
EOMEOM;

        $subject = sprintf(/*gettext*/('%s: New %s Request'), $groupName, $requestedRoleName);

        $raw_message = sprintf(/*gettext*/(
            'Dear %1$s,' . '<br>' .
            '<br>' .
            '%2$s has requested you as their <strong>%3$s</strong> in %5$s. Below is information on %2$s'.'<br>'.
            '%7$s' .
            '<br>'.
            'To view and accept or decline this request please visit the link below:'.'<br>'.
            '<a href="%4$s">%4$s</a>'.'<br>' .
            '<br>'.
            'You can always visit %5$s in order to see your existing %6$s and additional details.'. '<br>'.
            '<br>'.
            'Thank you' . '<br>' .
            '%5$s'
            
        ), $requestReceiverName, $requestSenderName, $requestedRoleName, $requestAcceptLink, $groupName, $teamCustomName, $requestSenderAttributesAsHtml);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$raw_message, $template);

        return array('subject'=>$subject,'message'=>$message, 'raw_message' => $raw_message);
    }

    public static function CancelTeamRoleRequestEmailToRecipientTemplate (string $requestSenderName, string $requestReceiverName, string $senderRoleName, string $requestedRoleName, string $discoverLink, string $groupName, string $teamCustomName)
    {
        global $_COMPANY, $_ZONE;
        $subject = sprintf(/*gettext*/('%s: %s Request Canceled'), $groupName, $requestedRoleName);
        $raw_message = sprintf(/*gettext*/(
            'Dear %1$s,' . '<br>' .
            '<br>' .
            'The request from %2$s for you to be their <strong>%3$s</strong> has been canceled.' . '<br>' .
            '<br>' .
            'This could be due to several reasons, such as %2$s finding another match, capacity constraints, or conflicting commitments.' . '<br>' .
            '<br>' .
            'Visit the <a href="%6$s"> %4$s > My %5$s > Discover Tab</a> to see other recommended matches.' . '<br>' .
            '<br>'.
            'Thank you' . '<br>' .
            '%4$s'

        ), $requestReceiverName, $requestSenderName, $requestedRoleName, $groupName, $teamCustomName, $discoverLink);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$raw_message, $template);

        return array('subject'=>$subject,'message'=>$message, 'raw_message' => $raw_message);
    }

    public static function DeclineTeamRoleRequestEmailTeamplate (User $requestReceiver, User $requestSender, string $requestedRoleName, string $discoverLink, string $groupName, string $teamCustomName, string $rejectionReason = '')
    {
        global $_COMPANY, $_ZONE;

        $requestReceiverName = $requestReceiver->getFullName();
        $requestSenderName = $requestSender->getFullName();

        $subject = sprintf(/*gettext*/('%s: %s Request Declined'), $groupName, $requestedRoleName);

        if ($rejectionReason) {
            $rejectionReason = '<p  style="padding-left: 5px; margin: 10px 0px; color: #666666; font-size: 13px; line-height: 20px; background-color:rgba(179, 38, 30, 0.27);"><b>Rejection Reason:</b><br> '.$rejectionReason.'</p>';
        }

        $raw_message = sprintf(/*gettext*/(
            'Dear %1$s,' . '<br>' .
            '<br>' .
            'Your request for %2$s to be your <strong>%3$s</strong> has been declined.' . '<br>' .
            '%7$s'.
            '<br>' .
            'There could be various reasons behind this decision such as multiple requests, capacity constraints, or prior commitments.' . '<br>' .
            '<br>' .
            'Mentoring is about finding the right connection. Visit the <a href="%6$s"> %4$s > My %5$s > Discover Tab</a> to see other recommended matches.' . '<br>' .
            '<br>'.
            'Thank you' . '<br>' .
            '%4$s'

        ), $requestSenderName, $requestReceiverName, $requestedRoleName, $groupName, $teamCustomName, $discoverLink, $rejectionReason);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$raw_message, $template);

        return array('subject'=>$subject,'message'=>$message, 'raw_message' => $raw_message);
    }

    public static function GetPublishedByInfo(): string
    {
        global $_JOB, $_USER, $_COMPANY;

        if (!$_COMPANY->getAppCustomization()['emails']['add_emailed_by_block']) {
            return '';
        }

        $published_by_userid = $_JOB?->val('createdby') ?? $_USER?->id();

        if (!$published_by_userid) {
            return '';
        }

        $published_by_user = User::GetUser($published_by_userid);

        if (!$published_by_user) {
            return '';
        }

        return <<<HTML
              <span>
                Emailed by {$published_by_user->getFullName()} ({$published_by_user->val('email')})
              </span>
HTML;
    }

    public static function JoinCircleNotificationToMentorTemplate (string $groupMetaName, string $teamMetaName, string $teamName, string $roleName, string $mentorName, string $menteeName,string $groupName, string $date)
    {
        global $_COMPANY, $_ZONE;
        $subject = sprintf(/*gettext*/('You have new %2$s Members in %1$s'),$teamName, $teamMetaName);
        $message = sprintf(/*gettext*/(
            'Dear %7$s,'.
            '<br/>' .
            '<br/>' .
            '%3$s has joined your %1$s %6$s.'.
            '<br/>' .
            '<br/>' .
            'Please ensure they are aware of any upcoming Touch Points that have been scheduled.'.
            '<br/>' .
            '<br/>' .
            'Thanks,'.
            '<br/>' .
            '%4$s'
        ), $teamName, $roleName, $menteeName, $groupName, $date, $teamMetaName, $mentorName);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function LeaveCircleNotificationToMentorTemplate (string $groupMetaName, string $teamMetaName, string $teamName, string $roleName, string $mentorName, string $menteeName, string $groupName, string $date)
    {
        global $_COMPANY, $_ZONE;
        $subject = sprintf(/*gettext*/('%1$s has left your %2$s %3$s'),$menteeName,$teamName,$teamMetaName);
        $message = sprintf(/*gettext*/(
            'Dear %7$s,'.
            '<br/>' .
            '<br/>' .
            'We are writing to inform you that %3$s is no longer participating in your %1$s %4$s.'.
            '<br/>' .
            '<br/>' .
            'Thank you for your dedication to the program.'.
            '<br/>' .
            '<br/>' .
            'Thanks,' .
            '<br/>' .
            '%4$s'
        ), $teamName, $roleName, $menteeName, $groupName, $date, $teamMetaName, $mentorName);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function TeamRoleRequestFollowupEmailTemplateToReceiver (User $requestReceiver, User $requestSender, string $requestedRoleName, string $requestDate, string $requestUrl, string $groupCustomName, string $groupName)
    {
        global $_COMPANY, $_ZONE;
        $requestReceiverName = $requestReceiver->getFullName();
        $requestSenderName = $requestSender->getFullName();
        $groupProgramName = $groupName . ' ' . $groupCustomName;
        $subject = sprintf(/*gettext*/('%s: Pending %s Request'), $groupProgramName, $requestedRoleName);

        $message = sprintf(/*gettext*/(
            'Dear %1$s,'.
            '<br/>' .
            '<br/>' .
            'This is a friendly reminder regarding the %2$s request you received from %3$s on %4$s. We understand that your time is valuable, but your response to this request is important to both the participants and our program. You can accept or decline the request by following the link:'.
            '<br/>' .
            '<br/>' .
            '<a href="%5$s">%5$s</a>'.
            '<br/>' .
            '<br/>' .
            'Thanks,' .
            '<br/>' .
            '%6$s'
        ), $requestReceiverName, $requestedRoleName, $requestSenderName, $requestDate, $requestUrl, $groupProgramName);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function TeamRoleRequestFollowupEmailTemplateToSender (User $requestReceiver, User $requestSender, string $requestedRoleName, string $requestDate, string $requestUrl, string $groupCustomName, string $groupName)
    {
        global $_COMPANY, $_ZONE;
        $requestReceiverName = $requestReceiver->getFullName();
        $requestSenderName = $requestSender->getFullName();
        $groupProgramName = $groupName . ' ' . $groupCustomName;
        $subject = sprintf(/*gettext*/('Your %s request is waiting on %s'), $requestedRoleName, $requestReceiverName);

        $message = sprintf(/*gettext*/(
            'Dear %1$s,'.
            '<br/>' .
            '<br/>' .
            'Your recent %2$s request is waiting for approval from %3$s. We wanted to give you an update that we sent a reminder to %3$s. If you don\'t want to proceed with the request, you can easily delete it through this link:' .
            '<br/>' .
            '<br/>' .
            '<a href="%5$s">%5$s</a>'.
            '<br/>' .
            '<br/>' .
            'Thanks,' .
            '<br/>' .
            '%6$s'
        ), $requestSenderName, $requestedRoleName, $requestReceiverName, $requestDate, $requestUrl, $groupProgramName);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function BuildHtmlButton(string $buttonLabel, string $buttonLink)
    {
        return <<< EOMEOM
        <table cellpadding="0" cellspacing="0" border="0" width="auto" style="width: auto; font-size: 16px; font-weight: normal; background-color: #0077bf; color: #ffffff !important; border-radius: 5px; border-collapse: separate; margin: auto;" class="">
            <tr>
                <td align="center" valign="top" style="vertical-align: top; line-height: 1; text-align: center; font-family: TeleskopeNewsletter, Lato, Helvetica, Arial, sans-serif; border-radius: 8px;" bgcolor="#0077bf">
                    <a class="link-in-button" target="_blank" style="display: inline-block; box-sizing: border-box; cursor: pointer; text-decoration: none; margin: 0px; font-family: TeleskopeNewsletter, Lato, Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; background-color: #0077bf; color: #ffffff !important; border-radius: 8px; padding: 14px 40px; border: 1px solid #0077bf;" href="{$buttonLink}">{$buttonLabel}</a>
                </td>
            </tr>
        </table>
EOMEOM;

    }

    public static function EmailTemplateWithdrwalNotificationToInvitedUser (string $requestSenderName, string $requestReceiverName, string $requestedRoleName, string $requestUrl, string $groupCustomName, string $groupName, string $teamCustomeName)
    {
        global $_COMPANY, $_ZONE;
        $groupProgramName = $groupName . ' ' . $groupCustomName;
        $subject = sprintf(/*gettext*/('%1$s: %2$s role join request canceled'), $groupProgramName, $requestedRoleName);

        $message = sprintf(/*gettext*/(

            'Dear %1$s,'.
            '<br/>' .
            '<br/>' .
            '%2$s has canceled their request for you to become their %3$s. Note, there can be many reasons for why the request was canceled.'.
            '<br/>' .
            '<br/>' .
            'You can always visit %4$s in order to find other users to connect with by following the link:'.
            '<br/>' .
            '<a href="%5$s">%5$s</a>'.
            '<br/>' .
            '<br/>' .
            'We apologize for any inconvenience this may cause. We value your time and appreciate your interest in being a %3$s.'.
            '<br/>' .
            '<br/>' .
            'Thanks,' .
            '<br/>' .
            '%4$s'
        ), $requestReceiverName, $requestSenderName, $requestedRoleName, $groupProgramName, $requestUrl);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }

    public static function PointsCreditDailyEarningsNotification(string $action, string $recipient_name): array
    {
        global $_COMPANY;

        if ($action === 'credit_points_to_members') {
            $subject = 'Points credited to members';
            $success_msg = 'Points have been successfully credited to members';
        } elseif ($action === 'credit_points_group_leads') {
            $subject = 'Points credited to group leads';
            $success_msg = 'Points have been successfully credited to group leads';
        }

        $message = sprintf('
            Hi %1$s
            <br>
            <br>
            %2$s
            <br>
            <br>
            Thanks
        ', $recipient_name, $success_msg);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message = str_replace('#messagehere#', $message, $template);

        return [
            'subject' => $subject,
            'message' => $message
        ];
    }

   
    public static function GetEmailTemplateForRecognitionPersonRecognized(string $fromRecognition,string $recognition,string $behalfOf,string $recognizedbyTeamName,int $recognizedto)
    {
       global $_USER;

        $subject = "Recognition received from {$fromRecognition}";
        $recognitionMsg = "You have received recognition from {$fromRecognition}.";
 
        if($recognizedto == $_USER->id()) {
            $recognitionMsg = "You have recognized your own efforts.";
        }

        if($behalfOf == "Team")
        {
            $recognitionMsg = "You have received recognition from {$fromRecognition} on behalf of a team {$recognizedbyTeamName}";
            $subject = "Recognition Received from {$fromRecognition} on behalf of {$recognizedbyTeamName}";
            if($recognizedto == $_USER->id()){$recognitionMsg = "You have recognized your own efforts on behalf of a team {$recognizedbyTeamName}";}
        }

        $message = <<<EOMEOM
        <p>{$recognitionMsg}</p>
        <br>
        <p>
            <blockquote>{$recognition}</blockquote>
        </p>
        <br>
        <br>		
        <p>Thanks!</p>
        <p></p>  
EOMEOM;
        return array('subject'=>$subject,'message'=>$message);
    }


   
    public static function GetEmailTemplateForRecognitionPersonRecognizing(string $toRecognition,string $recognition,string $behalfOf,string $recognizedbyTeamName)
    {
        $subject = "You recognized {$toRecognition}";
        $recognitionMsg = "Your recognition for {$toRecognition} has been saved.";
        if($behalfOf == 'Team')
        {
            $recognitionMsg = "Your recognition for {$toRecognition} on behalf of {$recognizedbyTeamName} has been saved.";
            $subject = "You recognized {$toRecognition} on behalf of {$recognizedbyTeamName}";
        }

        $message = <<<EOMEOM
        <p>{$recognitionMsg}</p>
        <p>
            <blockquote>{$recognition}</blockquote>
        </p>
        <br>
        <p>Thank you for acknowledging their efforts!</p>
        <p></p>
EOMEOM;
        return array('subject'=>$subject,'message'=>$message);
    }

    public static function EmailTemplateCancelOutstandingRequestsEmail (string $personName, string $requestedRoleName, string $requestUrl, string $groupCustomName, string $groupName, string $messageContext)
    {
        global $_COMPANY, $_ZONE;
        $groupProgramName = $groupName . ' ' . $groupCustomName;
        $subject = sprintf(/*gettext*/('%1$s: %2$s outstanding request has been cancelled'), $groupProgramName, $requestedRoleName);

        $message = sprintf(/*gettext*/(

            'Dear %1$s,'.
            '<br/>' .
            '<br/>
                %2$s
            <br/>' .
            '<br/>' .
            'You can always visit %3$s to check further details by following the link:'.
            '<br/>' .
            '<a href="%4$s">%4$s</a>'.
            '<br/>' .
            '<br/>' .
            'Thanks,' .
            '<br/>' .
            '%3$s'
        ), $personName, $messageContext, $groupProgramName, $requestUrl);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$message,$template);

        return array('subject'=>$subject,'message'=>$message);
    }


    public static function EmailTemplateForOnTeamMemberTermination(Group $group, ?array $roleType): array
    {	
		global $_COMPANY;

		$programName = $group->val('groupname');
		$programCustomName = $_COMPANY->getAppCustomization()['group']['name-short'];
        $teamCustomName = Team::GetTeamCustomMetaName($group->getTeamProgramType(), 0);

		$subject = "[[TEAM_MEMBER_WHO_LEFT_ROLE]] for [[TEAM_NAME]] {$teamCustomName} is no longer available!";
		$message = '';
		$message .= "<p>Hi [[PERSON_FIRST_NAME]],</p>";
		$message .="<p>We have noticed that your [[TEAM_MEMBER_WHO_LEFT_ROLE]] ([[TEAM_MEMBER_WHO_LEFT]]) for your {$teamCustomName}, [[TEAM_NAME]], has left the firm. </p>";
		$message .= "<p>Thanks</p>";
		$message .= "<p>{$programName} {$programCustomName}</p>";


        if (!empty($roleType)) {
			$subject = $roleType['member_termination_email_subject']?:$subject;
            $message = $roleType['member_termination_message']?:$message;
		}
        return array($subject, $message);
    }

    public static function EmailReconcileReminderTemplate (int $eventid, string $eventTitle, string $email_subject, string $email_body)
    {
        global $_COMPANY, $_ZONE;
        $replace_vars = array('[[EVENT_ID]]', '[[EVENT_TITLE]]', '[[EVENT_URL]]');
        
        $appUrl = $_COMPANY->getAppURL($_ZONE->val('app_type')) . 'eventview?id=' . $_COMPANY->encodeId($eventid);
        $encEventId = $_COMPANY->encodeIdForReport($eventid);
        $appUrlLink = "<a href='{$appUrl}'>{$appUrl}</a>";
        $replacement_vars = [
            $encEventId, $eventTitle,  $appUrlLink
        ];
        $email_subject = str_replace($replace_vars, $replacement_vars, $email_subject);
        $email_body = str_replace($replace_vars, $replacement_vars, $email_body);

        $template = $_COMPANY->getEmailTemplateForNonMemberEmails();
        $message	= str_replace('#messagehere#',$email_body,$template);
        
        return array('subject'=>$email_subject,'message'=>$message);
    }

    /**
     * Returns the subject and body template for booking reminder emails.
     * Placeholders like [[RECIPIENT_FIRST_NAME]] are left for later replacement.
     *
     * @param string $subjectTemplate
     * @param string $bodyTemplate
     * @param string $meetingDate
     * @param string $meetingTime
     * @param string $meetingLink
     * @return array{subject: string, body: string}
     */
    public static function getBookingReminderEmailTemplate(
        string $subjectTemplate,
        string $bodyTemplate,
        string $meetingDate,
        string $meetingTime,
        string $meetingLink
    ): array {
        $search = [
            '[[MEETING_DATE]]',
            '[[MEETING_TIME]]',
            '[[MEETING_LINK]]'
        ];
        $replace = [
            $meetingDate,
            $meetingTime,
            $meetingLink
        ];

        $subject = str_replace($search, $replace, $subjectTemplate);
        $body = str_replace($search, $replace, $bodyTemplate);

        return ['subject' => $subject, 'body' => $body];
    }
}

