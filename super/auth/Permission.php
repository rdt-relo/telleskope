<?php

enum Permission: string
{
    // Permissions that are not company specific
    case GlobalManageGuides = 'global_manage_guides';
    case GlobalManageReleaseNotes = 'global_manage_release_notes';
    case GlobalManageTrainingVideos = 'global_manage_training_videos';
    case GlobalSystemMessaging = 'global_system_messaging';
    case GlobalManageAppFaqs = 'global_manage_app_faqs';
    case GlobalManageAdminFaqs = 'global_manage_admin_faqs';
    case GlobalManageAppVersions = 'global_manage_app_versions';
    case GlobalManageTemplates = 'global_manage_templates';

    // Company Specific Permissions
    case ChangeCompanyServiceMode = 'change_company_service_mode';
    case CheckUser = 'check_user';
    case CreateNewCompany = 'create_new_company';
    case EditCompanyInfo = 'edit_company_info';
    case DeleteCompany = 'delete_company';
    case ManageEaiAccounts = 'manage_eai_accounts';
    case ManageEmailSettings = 'manage_email_settings';
    case ManageLoginMethods = 'manage_login_methods';
    case ManageScheduledJobs = 'manage_scheduled_jobs';
    case ManageZones = 'manage_zones';
    case MasqAdmin = 'masq_admin';
    case MasqAffinity = 'masq_affinity';
    case MasqOfficeraven = 'masq_officeraven';
    case MasqTalentpeak = 'masq_talentpeak';
    case MasqPeoplehero = 'masq_peoplehero';
    case MergeUser = 'merge_user';
    case ViewCloudwatchLogs = 'view_cloudwatch_logs';
    case ViewCompanyInfo = 'view_company_info';
    case ViewCompanyJobs = 'view_company_jobs';
    case ViewCompanyUsers = 'view_company_users';
    case ViewHrisFileBrowser = 'view_hris_file_browser';
    case ManagePSKs = 'manage_psks';
    case ManageDomains = 'manage_domains';
    case ManageCompanySettings = 'manage_company_settings';
}
