<?php
interface EaiPermission
{
}

enum EaiGraphPermission: string implements EaiPermission
{
    case GetZones = 'get_zones';
    case GetGroups = 'get_groups';
    case GetGroupChapters = 'get_group_chapters';
    case GetGroupChannels = 'get_group_channels';
    case GetMembers = 'get_members';
    case GetLeads = 'get_leads';
    case GetEvents = 'get_events';
    case GetAuditLogs = 'get_audit_logs';
    case GetUser = 'get_user';
    case GetAllUsers = 'get_all_users';
    case CreateUser = 'create_user';
    case UpdateUser = 'update_user';
}

enum EaiUploaderPermission: string implements EaiPermission
{
    case PostUserDataSync = 'post_user_data_sync';
    case PostUserDataDelete = 'post_user_data_delete';
}