-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 29, 2021 at 03:32 AM
-- Server version: 5.7.28
-- PHP Version: 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `affinity`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `superid` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `google_auth_code` varchar(250) NOT NULL,
  `manage_companyids` varchar(255) NOT NULL DEFAULT '0' COMMENT 'comma seperated list of companyids that can be managed; if -1 then all.',
  `failed_login_attempts` tinyint(4) NOT NULL DEFAULT '0',
  `expiry_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `createdon` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `appusage`
--

CREATE TABLE `appusage` (
  `userid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `usagetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usageif` enum('admin','affinities','native','email','officeraven','peoplehero') NOT NULL DEFAULT 'affinities' COMMENT 'Usage Interface'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `app_versions`
--

CREATE TABLE `app_versions` (
  `id` int(11) NOT NULL,
  `platform` enum('iOS','Android') NOT NULL,
  `app_version` varchar(255) NOT NULL,
  `bundle_id` varchar(255) NOT NULL,
  `isactive` tinyint(4) NOT NULL DEFAULT '2',
  `start_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `budgets_other_funding`
--

CREATE TABLE `budgets_other_funding` (
  `funding_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL DEFAULT '0',
  `chapterid` int(11) NOT NULL DEFAULT '0',
  `budget_year_id` int(11) NOT NULL DEFAULT '0',
  `funding_date` date NOT NULL,
  `funding_source` varchar(128) NOT NULL,
  `funding_amount` decimal(13,4) NOT NULL,
  `funding_currency` char(3) NOT NULL DEFAULT 'USD' COMMENT '3 digit international currency code',
  `funding_description` varchar(1024) NOT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedby` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `budgets_v2`
--

CREATE TABLE `budgets_v2` (
  `budget_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL DEFAULT '0',
  `chapterid` int(11) NOT NULL DEFAULT '0',
  `budget_year_id` int(11) NOT NULL DEFAULT '0',
  `budget_amount` decimal(13,4) NOT NULL,
  `budget_allocated_to_sub_accounts` decimal(13,4) NOT NULL DEFAULT '0.0000' COMMENT 'This is the amount of budget that has been sub allocated to groups, chapters or channels. budget_allocated + budget_assigned can never exceed budget_amount',
  `budget_currency` char(3) NOT NULL DEFAULT 'USD' COMMENT '3 digit international currency code',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedby` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `budgetuses`
--

CREATE TABLE `budgetuses` (
  `usesid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL DEFAULT '0',
  `chapterid` int(11) NOT NULL DEFAULT '0',
  `channelid` int(11) NOT NULL DEFAULT '0',
  `eventid` int(11) NOT NULL DEFAULT '0',
  `eventtype` varchar(255) NOT NULL DEFAULT '',
  `branchid` int(11) NOT NULL DEFAULT '0',
  `budgeted_amount` decimal(13,4) NOT NULL DEFAULT '0.0000',
  `budget_approval_status` tinyint(4) DEFAULT '0' COMMENT '0 draft, 1 Requested, 2 Approved, 3 Denied',
  `budget_approved_by` varchar(64) DEFAULT NULL COMMENT 'email of the person who approved budget, or auto for automatic approval',
  `budget_details` varchar(1024) DEFAULT NULL COMMENT 'Details about how the budget will be used',
  `budget_year_id` int(11) NOT NULL DEFAULT '0',
  `usedamount` decimal(13,4) NOT NULL DEFAULT '0.0000',
  `description` varchar(500) NOT NULL DEFAULT '',
  `date` date NOT NULL,
  `charge_code_id` int(11) NOT NULL DEFAULT '0',
  `modifiedon` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `budgetuses_items`
--

CREATE TABLE `budgetuses_items` (
  `itemid` int(11) NOT NULL,
  `usesid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `item` varchar(255) NOT NULL,
  `expensetypeid` int(11) DEFAULT NULL,
  `item_used_amount` decimal(13,4) NOT NULL DEFAULT '0.0000',
  `createdon` datetime NOT NULL,
  `modifiedon` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `budget_charge_codes`
--

CREATE TABLE `budget_charge_codes` (
  `charge_code_id` int(11) NOT NULL,
  `charge_code` varchar(255) DEFAULT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `createdby` int(11) NOT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `budget_expense_types`
--

CREATE TABLE `budget_expense_types` (
  `expensetypeid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `expensetype` varchar(128) NOT NULL,
  `isactive` tinyint(4) NOT NULL DEFAULT '1',
  `createdby` int(11) NOT NULL COMMENT 'Id of the user who created it',
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `budget_requests`
--

CREATE TABLE `budget_requests` (
  `request_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL,
  `chapterid` int(11) NOT NULL DEFAULT '0',
  `budget_usesid` int(11) DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_amount` int(11) NOT NULL,
  `purpose` varchar(256) NOT NULL,
  `description` varchar(255) NOT NULL,
  `need_by` date NOT NULL,
  `request_date` datetime NOT NULL,
  `request_modified_date` datetime NOT NULL,
  `request_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 Requested, 2 Approved, 3 Denied',
  `amount_approved` int(11) NOT NULL,
  `approved_by` int(11) NOT NULL,
  `approved_date` datetime NOT NULL,
  `approver_comment` varchar(500) NOT NULL,
  `is_active` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `budget_years`
--

CREATE TABLE `budget_years` (
  `budget_year_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `zone_id` int(11) NOT NULL DEFAULT '0',
  `budget_year_title` varchar(255) NOT NULL,
  `budget_year_start_date` date NOT NULL,
  `budget_year_end_date` date NOT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdby` int(11) NOT NULL DEFAULT '0',
  `isactive` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `chapterleads`
--

CREATE TABLE `chapterleads` (
  `leadid` int(11) NOT NULL,
  `chapterid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `grouplead_typeid` int(11) NOT NULL,
  `priority` varchar(255) NOT NULL,
  `assignedby` int(11) NOT NULL,
  `assigneddate` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `chapterleads`
--
DELIMITER $$
CREATE TRIGGER `before_insert_chapterleads` BEFORE INSERT ON `chapterleads` FOR EACH ROW begin
        if (select groupid from chapters where chapterid=new.chapterid) != (new.groupid)
        then signal SQLSTATE '45000' set message_text = 'Cannot match chapter to a group in your company';
        end if;
	    if (select companyid from groups where groupid=new.groupid) != (select companyid from users where userid=new.userid)
	    then signal SQLSTATE '45000' set message_text = 'Cannot lead chapters that do not belong to your company';
        end if;
    end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_chapterleads` BEFORE UPDATE ON `chapterleads` FOR EACH ROW begin
        if (select groupid from chapters where chapterid=new.chapterid) != (new.groupid)
        then signal SQLSTATE '45000' set message_text = 'Cannot match chapter to a group in your company';
        end if;
	    if (select companyid from groups where groupid=new.groupid) != (select companyid from users where userid=new.userid)
	    then signal SQLSTATE '45000' set message_text = 'Cannot lead chapters that do not belong to your company';
        end if;
    end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `chapters`
--

CREATE TABLE `chapters` (
  `chapterid` int(11) NOT NULL,
  `companyid` int(11) DEFAULT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL,
  `chaptername` varchar(64) DEFAULT NULL,
  `colour` varchar(50) NOT NULL,
  `leads` varchar(255) NOT NULL,
  `createdon` datetime NOT NULL,
  `userid` int(11) NOT NULL,
  `about` text NOT NULL,
  `branchids` varchar(4096) NOT NULL DEFAULT '0',
  `regionids` varchar(255) NOT NULL DEFAULT '0',
  `virtual_event_location` varchar(255) DEFAULT NULL,
  `latitude` varchar(100) NOT NULL DEFAULT '0',
  `longitude` varchar(100) NOT NULL DEFAULT '0',
  `modifiedon` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `chapters`
--
DELIMITER $$
CREATE TRIGGER `before_update_chapters` BEFORE UPDATE ON `chapters` FOR EACH ROW begin
    if ((new.groupid !=old.groupid) || (new.companyid !=old.companyid))
    then
        signal SQLSTATE '45000'
        set message_text = 'Cannot change groupid or companyid once set';
    end if;
end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `commentlikes`
--

CREATE TABLE `commentlikes` (
  `commentid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `userid` int(11) NOT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `commentid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `topicid` int(11) NOT NULL,
  `topictype` enum('posts','events','highlights','newsletters','teams','team_lists') DEFAULT NULL,
  `parent_commentid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `comment` text,
  `attachment` varchar(255) DEFAULT NULL,
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT NULL,
  `isactive` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `companyid` int(11) NOT NULL,
  `companyname` varchar(255) NOT NULL,
  `contactperson` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `zipcode` varchar(100) NOT NULL,
  `plan` varchar(100) NOT NULL DEFAULT '6',
  `logo` varchar(255) NOT NULL,
  `status` tinyint(4) NOT NULL COMMENT '1 for approved,2 for not approved',
  `paymentstauts` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0 for paid, 1 for not paid',
  `domain` varchar(4095) NOT NULL,
  `subdomain` varchar(64) NOT NULL,
  `aes_suffix` char(8) NOT NULL,
  `loginmethod` tinyint(4) NOT NULL DEFAULT '4' COMMENT '0 - Username/Password, 1 - GSuite, 2 - Microsoft Azure AD, 3 - SAML2, 4 - ANY',
  `loginscreen_background` varchar(255) NOT NULL,
  `affinity_home_banner` varchar(255) NOT NULL,
  `email_from_label` varchar(255) NOT NULL,
  `email_settings` tinyint(4) NOT NULL DEFAULT '0',
  `s3_folder` varchar(32) NOT NULL,
  `web_banner_title` varchar(100) NOT NULL,
  `web_banner_subtitle` varchar(100) NOT NULL,
  `show_group_overlay` tinyint(4) NOT NULL DEFAULT '1',
  `group_landing_page` enum('about','announcements','events') NOT NULL DEFAULT 'announcements',
  `vendor_support_email` varchar(255) NOT NULL DEFAULT 'support@teleskope.atlassian.net',
  `vendor_feedback_email` varchar(255) NOT NULL DEFAULT 'feedback@teleskope.io',
  `erg_users_guide` varchar(255) DEFAULT NULL,
  `erg_support_link` varchar(255) DEFAULT NULL,
  `erg_feedback_link` varchar(255) DEFAULT NULL,
  `customer_privacy_link` varchar(250) NOT NULL DEFAULT '',
  `require_policy_consent` tinyint(4) NOT NULL DEFAULT '0',
  `createdon` datetime NOT NULL,
  `approvedate` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL COMMENT '1 for active 2 for inactive',
  `in_maintenance` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1 - Data Migration, 2 - Configuration Update'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `companies`
--
DELIMITER $$
CREATE TRIGGER `before_update_companies` BEFORE UPDATE ON `companies` FOR EACH ROW begin
    if (new.companyid != old.companyid) then
        signal SQLSTATE '45000' set message_text = 'companyid once assigned cannot be updated';
    end if;
end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `companybranches`
--

CREATE TABLE `companybranches` (
  `branchid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `employees` int(11) NOT NULL DEFAULT '0',
  `branchname` varchar(255) NOT NULL,
  `branchtype` varchar(255) NOT NULL,
  `street` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `zipcode` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `regionid` int(11) NOT NULL,
  `createdon` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `companybranches`
--
DELIMITER $$
CREATE TRIGGER `before_update_companybranches` BEFORE UPDATE ON `companybranches` FOR EACH ROW begin     if (new.companyid != old.companyid)     then         signal SQLSTATE '45000' set message_text = 'companyid of the companybranch once assigned cannot be updated';     end if; end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `company_analytics`
--

CREATE TABLE `company_analytics` (
  `analyticid` varchar(64) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0' COMMENT '0 - means this is a shared analytic which can be used by any company',
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `analyticname` varchar(16) NOT NULL,
  `analyticdescription` varchar(128) DEFAULT '',
  `analytictype` tinyint(4) NOT NULL,
  `analyticmeta` varchar(4095) NOT NULL COMMENT 'JSON object containing the metadata for the analytic',
  `purpose` enum('download','transfer') NOT NULL DEFAULT 'download',
  `createdby` int(11) NOT NULL COMMENT 'Userid of the person who created this job',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) DEFAULT '1' COMMENT '2 - Draft, 1 - Active, 100 - Delete'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `company_contacts`
--

CREATE TABLE `company_contacts` (
  `companyid` int(11) NOT NULL,
  `contactrole` enum('Business','Technical','Security') NOT NULL COMMENT 'various ENUM options are Business, Technial, Security',
  `firstname` varchar(64) NOT NULL,
  `lastname` varchar(64) NOT NULL,
  `email` varchar(128) NOT NULL,
  `phonenumber` varchar(32) NOT NULL,
  `title` varchar(128) NOT NULL,
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='List of contacts in company';

-- --------------------------------------------------------

--
-- Table structure for table `company_customizations`
--

CREATE TABLE `company_customizations` (
  `companyid` int(11) NOT NULL,
  `customization` text NOT NULL,
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `company_email_settings`
--

CREATE TABLE `company_email_settings` (
  `companyid` int(11) NOT NULL,
  `custom_smtp` tinyint(1) NOT NULL DEFAULT '0',
  `custom_imap` tinyint(1) NOT NULL DEFAULT '0',
  `smtp_from_email` varchar(128) DEFAULT NULL COMMENT 'From email address to use',
  `smtp_replyto_email` varchar(128) DEFAULT NULL,
  `smtp_host` varchar(128) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT '587',
  `smtp_username` varchar(64) DEFAULT NULL,
  `smtp_password` varchar(64) DEFAULT NULL,
  `smtp_secure` char(3) DEFAULT NULL COMMENT 'tls or ssl',
  `imap_host` varchar(64) DEFAULT NULL,
  `imap_port` int(11) DEFAULT '465',
  `imap_username` varchar(64) DEFAULT NULL,
  `imap_password` varchar(64) DEFAULT NULL,
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Email Settings for company';

-- --------------------------------------------------------

--
-- Table structure for table `company_footer_links`
--

CREATE TABLE `company_footer_links` (
  `link_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) DEFAULT NULL,
  `link_title` varchar(128) DEFAULT NULL,
  `link` varchar(255) NOT NULL,
  `link_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 external link, 2 uploaded link',
  `link_section` enum('left','middle','right') DEFAULT NULL,
  `modifiedby` int(11) NOT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '2' COMMENT '1 active, 2 draft, 0 inactive, 100 deleted'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `company_login_settings`
--

CREATE TABLE `company_login_settings` (
  `settingid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `scope` enum('teleskope','affinities','officeraven','talentpeak', 'peoplehero') NOT NULL DEFAULT 'teleskope' COMMENT 'Application Scope of the SAML setting',
  `isdefault` tinyint(4) DEFAULT '0',
  `settingname` varchar(32) NOT NULL DEFAULT 'Default',
  `loginmethod` enum('microsoft','saml2','username') DEFAULT NULL,
  `login_btn_label` varchar(40) NOT NULL,
  `login_btn_description` varchar(250) DEFAULT NULL,
  `customization` varchar(1024) DEFAULT NULL,
  `login_silently` tinyint(4) DEFAULT '0',
  `debug_mode` tinyint(1) DEFAULT '0' COMMENT 'Disable it for production',
  `attributes` text COMMENT 'JSON encoded attributes ',
  `allowed_email_domains` varchar(1024) DEFAULT NULL,
  `isactive` tinyint(4) NOT NULL DEFAULT '2' COMMENT '2 for draft, 1 for active',
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `company_reports`
--

CREATE TABLE `company_reports` (
  `reportid` varchar(64) NOT NULL COMMENT 'subdomain_timeinmillis',
  `companyid` int(11) NOT NULL DEFAULT '0' COMMENT '0 - means this is a shared report which can be used by any company',
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `reportname` varchar(16) NOT NULL,
  `reportdescription` varchar(128) DEFAULT '',
  `reporttype` tinyint(4) NOT NULL,
  `reportmeta` varchar(4095) NOT NULL COMMENT 'JSON object containing the metadata for the report',
  `purpose` enum('download','transfer') NOT NULL DEFAULT 'download',
  `createdby` int(11) NOT NULL COMMENT 'Userid of the person who created this job',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) DEFAULT '1' COMMENT '2 - Draft, 1 - Active, 100 - Delete'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `company_security_settings`
--

CREATE TABLE `company_security_settings` (
  `companyid` int(11) NOT NULL,
  `admin_inactivity_max` int(11) NOT NULL DEFAULT '120' COMMENT 'Logout from Admin after timeout minutes',
  `admin_session_max` int(11) NOT NULL DEFAULT '1440' COMMENT 'Maximum session life in minutes',
  `apps_inactivity_max` int(11) NOT NULL DEFAULT '2880' COMMENT 'Logout from Apps after this timeout in minutes',
  `apps_session_max` int(11) NOT NULL DEFAULT '10080' COMMENT 'Maximum session life in minutes for applications',
  `admin_whitelist_ip` varchar(255) DEFAULT NULL,
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `company_zones`
--

CREATE TABLE `company_zones` (
  `zoneid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zonename` varchar(20) NOT NULL,
  `app_type` enum('affinities','officeraven','talentpeak','peoplehero') DEFAULT NULL,
  `customization` text,
  `email_from_label` varchar(127) NOT NULL,
  `email_from` varchar(128) DEFAULT NULL COMMENT 'From email address to use; only applicable if custom stmp settings are used',
  `email_replyto` varchar(128) DEFAULT NULL COMMENT 'Reply to address to use',
  `email_settings` tinyint(4) NOT NULL DEFAULT '0',
  `banner_background` varchar(255) DEFAULT NULL,
  `banner_title` varchar(100) DEFAULT NULL,
  `calendar_page_banner_title` varchar(20) DEFAULT 'Calendar',
  `admin_content_page_title` varchar(20) DEFAULT 'Admin Content',
  `banner_subtitle` varchar(100) DEFAULT NULL,
  `show_group_overlay` tinyint(4) DEFAULT '1',
  `group_landing_page` enum('about','announcements','events') DEFAULT 'announcements',
  `users_guide` varchar(255) DEFAULT NULL,
  `support_link` varchar(255) DEFAULT NULL,
  `feedback_link` varchar(255) DEFAULT NULL,
  `regionids` varchar(255) DEFAULT '0' COMMENT 'Comma seperated list of regionids that are assigned to this zone.',
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP,
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '2'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `contactus`
--

CREATE TABLE `contactus` (
  `contactid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `createdon` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `departmentid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `addedby` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `departments`
--
DELIMITER $$
CREATE TRIGGER `before_update_departments` BEFORE UPDATE ON `departments` FOR EACH ROW begin     if (new.companyid != old.companyid)     then         signal SQLSTATE '45000' set message_text = 'companyid of the department once assigned cannot be updated';     end if; end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `eai_accounts`
--

CREATE TABLE `eai_accounts` (
  `accountid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `module` enum('uploader') NOT NULL,
  `passwordhash` varchar(128) DEFAULT NULL,
  `attributes` text COMMENT 'JSON encoded field',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1 for active, 0 for inactive',
  `failed_logins` tinyint(4) DEFAULT '0',
  `last_used` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `eventjoiners`
--

CREATE TABLE `eventjoiners` (
  `joineeid` int(11) NOT NULL,
  `eventid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `joindate` datetime DEFAULT NULL,
  `joinstatus` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 - default or no response; 1 - Yes, 2 - Tentative; 3 - No',
  `joinmethod` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 for unknown, 1 for web, 2 for email',
  `checkedin_date` datetime DEFAULT NULL,
  `checkedin_by` varchar(128) DEFAULT NULL,
  `other_data` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `eventjoiners`
--
DELIMITER $$
CREATE TRIGGER `before_create_eventjoiners` BEFORE INSERT ON `eventjoiners` FOR EACH ROW begin     if (select companyid from events where eventid=new.eventid) != (select companyid from users where userid=new.userid)     then         signal SQLSTATE '45000'         set message_text = 'Cannot allow users to join events in other companies';     end if; end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_eventjoiners` BEFORE UPDATE ON `eventjoiners` FOR EACH ROW begin if (((new.userid != 0) and (new.userid != old.userid)) or (new.eventid != old.eventid)) then signal SQLSTATE '45000' set message_text = 'userid,eventid cannot be updated';end if; end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `eventid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `chapterid` int(11) NOT NULL DEFAULT '0',
  `channelid` int(11) NOT NULL DEFAULT '0',
  `event_series_id` int(11) NOT NULL DEFAULT '0' COMMENT 'If >0, then it means the event is part of a series; if event_series_id is equal to eventid then it means it is the parent of the event series otherwise the value can be used to find the parent of the series ',
  `eventtitle` varchar(255) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `timezone` varchar(32) NOT NULL,
  `calendar_blocks` tinyint(4) NOT NULL DEFAULT '1',
  `eventrecurreing` varchar(100) NOT NULL,
  `eventvanue` varchar(255) NOT NULL,
  `vanueaddress` varchar(500) NOT NULL,
  `event_description` text NOT NULL,
  `latitude` varchar(100) NOT NULL,
  `longitude` varchar(100) NOT NULL,
  `event_contact` varchar(127) NOT NULL DEFAULT '-',
  `eventtype` int(11) NOT NULL,
  `eventclass` enum('event','eventgroup','holiday') NOT NULL DEFAULT 'event',
  `collaborating_groupids` varchar(512) NOT NULL DEFAULT '',
  `collaborating_groupids_pending` varchar(512) NOT NULL DEFAULT '',
  `hostedby` varchar(255) NOT NULL,
  `invited_groups` varchar(255) NOT NULL,
  `event_attendence_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 for Onsite(default), 2 for Online, 3 for Onsite and Online',
  `web_conference_link` varchar(1200) DEFAULT NULL,
  `web_conference_detail` text,
  `web_conference_sp` varchar(32) DEFAULT NULL,
  `checkin_enabled` tinyint(4) DEFAULT '0',
  `max_inperson` int(11) NOT NULL DEFAULT '0' COMMENT 'Maximum number of seats available for inperson event',
  `max_online` int(11) NOT NULL DEFAULT '0' COMMENT 'Maximum number of seats available for online event',
  `max_inperson_waitlist` int(11) NOT NULL DEFAULT '0' COMMENT 'Maximum number of people to allow in inperson waitlist.',
  `max_online_waitlist` int(11) NOT NULL DEFAULT '0' COMMENT 'Maximum number of people to allow in online waitlist',
  `automanage_online_waitlist` tinyint(4) NOT NULL DEFAULT '1',
  `automanage_inperson_waitlist` tinyint(4) NOT NULL DEFAULT '1',
  `highlights` tinyint(4) NOT NULL DEFAULT '0',
  `invited_locations` varchar(4096) NOT NULL DEFAULT '0',
  `rsvp_dueby` datetime DEFAULT NULL COMMENT 'date after which RSVPs will be closed',
  `rsvp_restriction` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 default, 1 Any Number of Events, 2 Single Event Only',
  `isprivate` tinyint(4) NOT NULL DEFAULT '0',
  `custom_fields` varchar(1024) DEFAULT NULL,
  `rsvp_display` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'If true (1) then RSVPs will be shown on Events and Group home page',
  `publishdate` datetime DEFAULT NULL,
  `publish_to_email` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Is set to 1 if publish/update send to email was selected',
  `followup_notes` text,
  `pin_to_top` tinyint(4) NOT NULL DEFAULT '0',
  `version` tinyint(4) NOT NULL DEFAULT '1',
  `add_photo_disclaimer` tinyint(4) NOT NULL DEFAULT '0',
  `addedon` datetime NOT NULL,
  `modifiedon` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `event_counters`
--

CREATE TABLE `event_counters` (
  `eventid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `num_rsvp_0` int(11) NOT NULL DEFAULT '0',
  `num_rsvp_1` int(11) NOT NULL DEFAULT '0',
  `num_rsvp_2` int(11) NOT NULL DEFAULT '0',
  `num_rsvp_3` int(11) NOT NULL DEFAULT '0',
  `num_rsvp_11` int(11) NOT NULL DEFAULT '0',
  `num_rsvp_12` int(11) NOT NULL DEFAULT '0',
  `num_rsvp_15` int(11) DEFAULT '0' COMMENT 'This is needed by a stored procedure',
  `num_rsvp_21` int(11) NOT NULL DEFAULT '0',
  `num_rsvp_22` int(11) NOT NULL DEFAULT '0',
  `num_rsvp_25` int(11) DEFAULT '0' COMMENT 'This is needed by a stored procedure',
  `num_checkedin` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table stores counters for each event';

-- --------------------------------------------------------

--
-- Table structure for table `event_custom_fields`
--

CREATE TABLE `event_custom_fields` (
  `custom_field_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `custom_field_name` varchar(255) DEFAULT NULL,
  `custom_fields_type` tinyint(4) DEFAULT NULL COMMENT '1 Boolean, 2 Multiple Option, 3 Open Field',
  `custom_field_note` varchar(125) DEFAULT NULL,
  `custom_fields_options` varchar(1024) DEFAULT NULL COMMENT 'Json Object',
  `is_required` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 not required 1 required',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '2' COMMENT '0 deleted, 1 active, 2 draft'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `event_highlights`
--

CREATE TABLE `event_highlights` (
  `event_highlight_id` int(11) NOT NULL,
  `eventid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `title` varchar(500) NOT NULL,
  `cover_photo` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1 cover photo, 0 normal photo',
  `media` varchar(500) NOT NULL,
  `addedon` datetime DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `event_highlight_comments`
--

CREATE TABLE `event_highlight_comments` (
  `event_highlight_commentid` int(11) NOT NULL,
  `event_highlight_id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `postedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `event_highlight_likes`
--

CREATE TABLE `event_highlight_likes` (
  `event_highlight_likeid` int(11) NOT NULL,
  `event_highlight_id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `likedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `event_speakers`
--

CREATE TABLE `event_speakers` (
  `speakerid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `eventid` int(11) DEFAULT NULL,
  `speaker_type` int(11) NOT NULL DEFAULT '0',
  `speech_type` int(11) NOT NULL DEFAULT '0',
  `audience_type` int(11) NOT NULL DEFAULT '0',
  `speech_length` int(11) DEFAULT NULL COMMENT 'In Minutes (15, 30, 45, 60, 75, 90, 105, 120)',
  `speaker_name` varchar(255) DEFAULT NULL,
  `speaker_title` varchar(255) DEFAULT NULL COMMENT 'Speakers Professional Title',
  `speaker_picture` varchar(512) DEFAULT NULL,
  `speaker_bio` varchar(1024) DEFAULT NULL,
  `speaker_fee` int(11) DEFAULT NULL COMMENT 'Fee in USD',
  `expected_attendees` int(11) NOT NULL DEFAULT '0',
  `other` varchar(2048) DEFAULT NULL COMMENT 'Any other justfication. E.g. Why this speaker was chosen, the number of attendees, etc',
  `approved_by` varchar(128) DEFAULT NULL COMMENT 'Approvers Email',
  `approved_on` datetime DEFAULT NULL,
  `approver_note` varchar(128) DEFAULT NULL,
  `approval_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1 Requested, 2 Approved, 3 Denied',
  `createdby` int(11) DEFAULT NULL COMMENT 'Userid of the user who created this speaker',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `event_speaker_fields`
--

CREATE TABLE `event_speaker_fields` (
  `speaker_fieldid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `speaker_fieldtype` enum('speaker_type','speech_type','audience_type') DEFAULT NULL,
  `speaker_fieldlabel` varchar(127) NOT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 - Active, 2 - Draft, 0 - Inactive'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `event_type`
--

CREATE TABLE `event_type` (
  `typeid` int(11) NOT NULL,
  `sys_eventtype` tinyint(4) NOT NULL,
  `companyid` int(11) NOT NULL COMMENT '0 for default else perticuler company',
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `type` varchar(255) NOT NULL,
  `modifiedon` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `faqsadmin`
--

CREATE TABLE `faqsadmin` (
  `faqid` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `createdon` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `faqsmobile`
--

CREATE TABLE `faqsmobile` (
  `faqid` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `createdon` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `groupleads`
--

CREATE TABLE `groupleads` (
  `leadid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `regionids` varchar(255) NOT NULL DEFAULT '0',
  `chapterids` varchar(255) NOT NULL DEFAULT '0',
  `grouplead_typeid` int(11) NOT NULL,
  `priority` varchar(512) NOT NULL,
  `assignedby` int(11) NOT NULL,
  `assigneddate` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `groupleads`
--
DELIMITER $$
CREATE TRIGGER `before_insert_groupleads` BEFORE INSERT ON `groupleads` FOR EACH ROW begin if (select companyid from groups where groupid=new.groupid) != (select companyid from users where userid=new.userid)     then         signal SQLSTATE '45000'         set message_text = 'Cannot lead groups that do not belong to your company'; end if; end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_groupleads` BEFORE UPDATE ON `groupleads` FOR EACH ROW begin     if (select companyid from groups where groupid=new.groupid) != (select companyid from users where userid=new.userid)     then         signal SQLSTATE '45000'         set message_text = 'Cannot lead groups that do not belong to your company';     end if; end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `grouplead_type`
--

CREATE TABLE `grouplead_type` (
  `typeid` int(11) NOT NULL,
  `sys_leadtype` tinyint(4) NOT NULL DEFAULT '0',
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `type` varchar(255) NOT NULL,
  `welcome_message` varchar(2048) NOT NULL DEFAULT '',
  `allow_publish_content` tinyint(4) NOT NULL DEFAULT '0',
  `allow_create_content` tinyint(4) NOT NULL DEFAULT '0',
  `allow_manage` tinyint(4) NOT NULL DEFAULT '0',
  `show_on_aboutus` tinyint(4) DEFAULT '1',
  `modifiedon` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `groupmembers`
--

CREATE TABLE `groupmembers` (
  `memberid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `chapterid` varchar(255) NOT NULL DEFAULT '0',
  `channelids` varchar(255) NOT NULL DEFAULT '0',
  `groupjoindate` datetime NOT NULL,
  `anonymous` tinyint(4) NOT NULL DEFAULT '0',
  `notify_events` tinyint(4) NOT NULL DEFAULT '1',
  `notify_posts` tinyint(4) NOT NULL DEFAULT '1',
  `notify_news` tinyint(4) NOT NULL DEFAULT '1',
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `groupmembers`
--
DELIMITER $$
CREATE TRIGGER `before_insert_groupmembers` BEFORE INSERT ON `groupmembers` FOR EACH ROW begin     if (select companyid from groups where groupid=new.groupid) != (select companyid from users where userid=new.userid)     then         signal SQLSTATE '45000'         set message_text = 'Cannot allow users to join groups in other companies';     end if; end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_groupmembers` BEFORE UPDATE ON `groupmembers` FOR EACH ROW begin     if (select companyid from groups where groupid=new.groupid) != (select companyid from users where userid=new.userid)     then         signal SQLSTATE '45000'         set message_text = 'Cannot allow users to join groups in other companies';     end if; end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `groupid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `regionid` varchar(255) NOT NULL DEFAULT '0',
  `group_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 Default- Open Membership ',
  `group_category` enum('ERG','IG') NOT NULL DEFAULT 'ERG',
  `app_type` enum('affinities','officeraven') NOT NULL DEFAULT 'affinities',
  `addedby` int(11) NOT NULL COMMENT '0 by Admin other wise userid',
  `groupname` varchar(255) NOT NULL,
  `groupname_short` char(12) NOT NULL,
  `abouttitle` varchar(255) NOT NULL,
  `aboutgroup` varchar(8000) NOT NULL,
  `about_show_members` tinyint(4) DEFAULT '0',
  `coverphoto` varchar(255) NOT NULL,
  `sliderphoto` varchar(255) DEFAULT NULL COMMENT 'Optional Slider Background photo',
  `overlaycolor` varchar(255) NOT NULL,
  `overlaycolor2` varchar(255) NOT NULL,
  `groupicon` varchar(255) NOT NULL,
  `permatag` varchar(16) NOT NULL,
  `priority` varchar(255) NOT NULL,
  `addedon` varchar(255) NOT NULL,
  `from_email_label` varchar(32) NOT NULL,
  `replyto_email` varchar(64) NOT NULL DEFAULT '' COMMENT 'If set this email will be used for reply to address',
  `chapter_assign_type` enum('auto','by_user_any','by_user_exactly_one','by_user_atleast_one') NOT NULL DEFAULT 'by_user_any',
  `show_overlay_logo` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 show logo on slider overlay, 0 hide',
  `attributes` text,
  `modifiedon` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `groups`
--
DELIMITER $$
CREATE TRIGGER `before_insert_groups` BEFORE INSERT ON `groups` FOR EACH ROW begin
    if (length(new.permatag) < 2) then
        signal SQLSTATE '45100' set message_text = 'Need minimum 2 characters for permatag';
    end if;
end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_groups` BEFORE UPDATE ON `groups` FOR EACH ROW begin
    if (new.companyid != old.companyid)
    then
        signal SQLSTATE '45000' set message_text = 'Group companyid once assigned cannot be updated';
    end if;
    if (length(new.permatag) < 2) then
        signal SQLSTATE '45100' set message_text = 'Need minimum 2 characters for permatag';
    end if;
end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `group_channels`
--

CREATE TABLE `group_channels` (
  `channelid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL,
  `channelname` varchar(64) NOT NULL,
  `colour` varchar(50) NOT NULL,
  `about` text NOT NULL,
  `createdby` int(11) NOT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isactive` tinyint(4) DEFAULT '0' COMMENT '0 is for inactive, 1 is for active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `group_channels`
--
DELIMITER $$
CREATE TRIGGER `before_update_group_channels` BEFORE UPDATE ON `group_channels` FOR EACH ROW begin
    if ((new.groupid !=old.groupid) || (new.companyid !=old.companyid))
    then
        signal SQLSTATE '45000'
        set message_text = 'Cannot change groupid or companyid once set';
    end if;
end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `group_channel_leads`
--

CREATE TABLE `group_channel_leads` (
  `leadid` int(11) NOT NULL,
  `channelid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `grouplead_typeid` int(11) NOT NULL,
  `priority` varchar(255) NOT NULL,
  `assignedby` int(11) NOT NULL,
  `assigneddate` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `group_channel_leads`
--
DELIMITER $$
CREATE TRIGGER `before_insert_group_channel_leads` BEFORE INSERT ON `group_channel_leads` FOR EACH ROW begin
    if (select groupid from group_channels where channelid=new.channelid) != (new.groupid)
    then signal SQLSTATE '45000' set message_text = 'Cannot match channel to a group in your company';
    end if;
    if (select companyid from groups where groupid=new.groupid) != (select companyid from users where userid=new.userid)
    then signal SQLSTATE '45000' set message_text = 'Cannot lead channels that do not belong to your company';
    end if;
end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_group_channel_leads` BEFORE UPDATE ON `group_channel_leads` FOR EACH ROW begin
    if (select groupid from group_channels where channelid=new.channelid) != (new.groupid)
    then signal SQLSTATE '45000' set message_text = 'Cannot match channel to a group in your company';
    end if;
    if (select companyid from groups where groupid=new.groupid) != (select companyid from users where userid=new.userid)
    then signal SQLSTATE '45000' set message_text = 'Cannot lead channels that do not belong to your company';
    end if;
end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `group_communications`
--

CREATE TABLE `group_communications` (
  `communicationid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL,
  `chapterid` int(11) NOT NULL,
  `channelid` int(11) NOT NULL DEFAULT '0',
  `communication_trigger` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1 - Group Join, 2 - Group Leave',
  `templateid` int(11) NOT NULL,
  `template` text NOT NULL,
  `emailsubject` varchar(128) NOT NULL,
  `email_cc_list` varchar(1024) DEFAULT NULL,
  `communication` text NOT NULL,
  `createdby` int(11) NOT NULL,
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '2' COMMENT '0 - Inactive, 1 - Active, 2 - Draft'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Table to store communication emails';

-- --------------------------------------------------------

--
-- Table structure for table `group_linked_groups`
--

CREATE TABLE `group_linked_groups` (
  `groupid` int(11) NOT NULL DEFAULT '0',
  `linked_groupid` int(11) NOT NULL DEFAULT '0',
  `linked_chapterids` varchar(255) DEFAULT '0',
  `linked_channelids` varchar(255) DEFAULT '0',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedby` int(11) NOT NULL DEFAULT '0',
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `group_resources`
--

CREATE TABLE `group_resources` (
  `resource_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) DEFAULT NULL,
  `chapterid` int(11) NOT NULL DEFAULT '0',
  `channelid` int(11) NOT NULL DEFAULT '0',
  `userid` int(11) DEFAULT NULL,
  `resource_name` varchar(128) DEFAULT NULL,
  `resource_type` tinyint(4) NOT NULL DEFAULT '3' COMMENT '1 link, 2 file, 3 folder',
  `resource` varchar(1000) DEFAULT NULL,
  `resource_description` varchar(255) DEFAULT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `folderid` int(11) DEFAULT NULL,
  `size` int(11) NOT NULL DEFAULT '0',
  `extention` varchar(28) DEFAULT NULL,
  `pin_to_top` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 not pinned, 1 pinned to top',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `group_tabs`
--

CREATE TABLE `group_tabs` (
  `tabid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL DEFAULT '0',
  `chapterid` int(11) NOT NULL DEFAULT '0',
  `channelid` int(11) NOT NULL DEFAULT '0',
  `tab_name` varchar(16) NOT NULL,
  `tab_html` text,
  `createdby` int(11) NOT NULL,
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) DEFAULT '2'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `hot_link`
--

CREATE TABLE `hot_link` (
  `link_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `userid` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `alternate_name` varchar(100) NOT NULL,
  `image` varchar(500) NOT NULL,
  `link_type` tinyint(4) NOT NULL COMMENT '1 for external link, 2 for attachement',
  `link` varchar(500) NOT NULL,
  `priority` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL,
  `is_active` tinyint(4) NOT NULL COMMENT '1 active 0 inactive'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `integrations`
--

CREATE TABLE `integrations` (
  `integrationid` int(11) NOT NULL,
  `integration_topic` varchar(32) NOT NULL COMMENT 'Composite String:eg, GRP_{groupid}_{chapterid}_{channelid}, etc',
  `companyid` int(11) NOT NULL,
  `internal_type` tinyint(4) NOT NULL,
  `external_type` tinyint(4) NOT NULL,
  `integration_name` varchar(32) NOT NULL,
  `integration_json` varchar(1024) DEFAULT NULL,
  `createdby` int(11) NOT NULL COMMENT 'who created',
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '2' COMMENT '1 active, 2 draft, 0 inactive'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `integration_records`
--

CREATE TABLE `integration_records` (
  `integrationid` int(11) NOT NULL COMMENT 'Foreign Key -> integrations.integrationid',
  `record_key` varchar(32) NOT NULL COMMENT 'composite key, e.g. EVT_{eventid}',
  `companyid` int(11) NOT NULL,
  `record_value` varchar(255) DEFAULT NULL,
  `updatedon` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `jobid` varchar(255) NOT NULL,
  `jobtype` tinyint(4) NOT NULL,
  `jobsubtype` tinyint(4) NOT NULL,
  `details` mediumtext,
  `options` varchar(255) DEFAULT NULL COMMENT 'JSON encoded list of paramters to aid processing',
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `createdby` int(11) NOT NULL,
  `createdon` datetime NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 means not processed, >0 means in processing stage, -1 means processed successfully',
  `processafter` datetime NOT NULL,
  `processedby` varchar(128) DEFAULT NULL COMMENT 'hostname or ip of server that is processing or processed',
  `processedon` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `leadsinvites`
--

CREATE TABLE `leadsinvites` (
  `leadinviteid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `invitedby` int(11) NOT NULL,
  `createdon` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `memberinvites`
--

CREATE TABLE `memberinvites` (
  `memberinviteid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `invitedby` int(11) NOT NULL,
  `createdon` datetime NOT NULL,
  `status` tinyint(4) NOT NULL,
  `other_data` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `messageid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `userid` int(11) NOT NULL,
  `groupids` varchar(1024) NOT NULL DEFAULT '',
  `chapterids` varchar(4096) NOT NULL DEFAULT '',
  `channelids` varchar(4096) NOT NULL DEFAULT '',
  `regionids` varchar(1024) NOT NULL DEFAULT '',
  `sent_to` varchar(255) NOT NULL,
  `total_recipients` int(11) NOT NULL,
  `recipients` mediumtext NOT NULL,
  `additional_recipients` text,
  `subject` varchar(255) NOT NULL,
  `is_admin` tinyint(4) NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `recipients_base` tinyint(4) NOT NULL DEFAULT '3' COMMENT '1 All users in the zone, 2 Non-group member of the zone, 3 Group members',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '2' COMMENT '1 active, 2 draft, 100 delete'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `newsletters`
--

CREATE TABLE `newsletters` (
  `newsletterid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL DEFAULT '0',
  `chapterid` varchar(1023) NOT NULL DEFAULT '0',
  `channelid` int(11) NOT NULL DEFAULT '0',
  `userid` int(11) NOT NULL,
  `newslettername` varchar(255) NOT NULL,
  `templateid` int(11) NOT NULL,
  `publishdate` datetime DEFAULT NULL,
  `publish_to_email` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Is set to 1 if publish/update send to email was selected',
  `version` tinyint(4) NOT NULL DEFAULT '1',
  `template` text NOT NULL,
  `newsletter` mediumtext NOT NULL,
  `timezone` varchar(56) NOT NULL DEFAULT 'UTC',
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 ready to review, 2 published, 0 inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_attachments`
--

CREATE TABLE `newsletter_attachments` (
  `attachment_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `newsletterid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `attachment` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notificationid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `section` int(11) NOT NULL COMMENT '1 for group, 2 for post, 3 for event, 4 for post replay, 5 for like',
  `userid` int(11) NOT NULL,
  `whodo` int(11) NOT NULL,
  `tableid` int(11) NOT NULL,
  `message` varchar(1000) NOT NULL,
  `datetime` datetime DEFAULT NULL,
  `isread` tinyint(4) NOT NULL COMMENT '1 for read, 2 for unread'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE `post` (
  `postid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `chapterid` int(11) NOT NULL DEFAULT '0',
  `channelid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `post` text NOT NULL,
  `publishdate` datetime DEFAULT NULL,
  `publish_to_email` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Is set to 1 if publish/update send to email was selected',
  `version` tinyint(4) NOT NULL DEFAULT '1',
  `pin_to_top` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 not pined 1 pined',
  `postedon` datetime DEFAULT NULL,
  `modifiedon` datetime DEFAULT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `post`
--
DELIMITER $$
CREATE TRIGGER `before_insert_post` BEFORE INSERT ON `post` FOR EACH ROW begin     if (new.companyid != (select companyid from users where userid=new.userid)) or (new.companyid != (select companyid from groups where groupid=new.groupid))     then         signal SQLSTATE '45000'         set message_text = 'The group or the user set in the post does not belong to this company';     end if; end
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_post` BEFORE UPDATE ON `post` FOR EACH ROW begin     if (new.companyid != (select companyid from users where userid=new.userid)) or (new.companyid != (select companyid from groups where groupid=new.groupid))     then         signal SQLSTATE '45000'         set message_text = 'The group or the user set in the post does not belong to this company';     end if; end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `postcomments`
--

CREATE TABLE `postcomments` (
  `commentid` int(11) NOT NULL,
  `postid` int(11) NOT NULL,
  `parent_commentid` int(11) NOT NULL DEFAULT '0',
  `subcommentid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `comment` text,
  `like_count` int(11) NOT NULL DEFAULT '0' COMMENT 'count of likes for the comment',
  `commentedon` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT NULL,
  `isactive` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `postlikes`
--

CREATE TABLE `postlikes` (
  `likeid` int(11) NOT NULL,
  `postid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `likedon` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `post_comment_likes`
--

CREATE TABLE `post_comment_likes` (
  `commentid` int(11) NOT NULL,
  `userid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `recruiting`
--

CREATE TABLE `recruiting` (
  `recruit_id` int(11) NOT NULL,
  `recruited_by` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `linkedin` varchar(255) NOT NULL,
  `resume` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `added_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `referral`
--

CREATE TABLE `referral` (
  `referral_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `referral_by` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `compnayname` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `added_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `regionid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `region` varchar(255) NOT NULL,
  `userid` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `regions`
--
DELIMITER $$
CREATE TRIGGER `before_update_regions` BEFORE UPDATE ON `regions` FOR EACH ROW begin     if (new.companyid != old.companyid)     then         signal SQLSTATE '45000' set message_text = 'companyid of the regions once assigned cannot be updated';     end if; end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `release_notes`
--

CREATE TABLE `release_notes` (
  `releaseid` int(11) NOT NULL,
  `releasename` varchar(32) NOT NULL COMMENT 'Release name tag in bitbucket',
  `app_type` enum('affinities','officeraven','talentpeak','peoplehero') NOT NULL COMMENT 'Application to which the relates notes are applicable',
  `notes` text NOT NULL,
  `isactive` tinyint(4) DEFAULT '2',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Release Notes for Applications';

-- --------------------------------------------------------

--
-- Table structure for table `stats_company_daily_count`
--

CREATE TABLE `stats_company_daily_count` (
  `stat_date` date NOT NULL,
  `companyid` int(11) NOT NULL,
  `admin_global_level` int(11) DEFAULT NULL,
  `admin_zone_level` int(11) DEFAULT NULL,
  `number_of_zones` int(11) DEFAULT NULL,
  `regions` int(11) DEFAULT NULL,
  `departments` int(11) DEFAULT NULL,
  `branches` int(11) DEFAULT NULL,
  `user_total` int(11) DEFAULT NULL,
  `user_member_unique` int(11) DEFAULT NULL,
  `user_member_total` int(11) DEFAULT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `stats_groups_daily_count`
--

CREATE TABLE `stats_groups_daily_count` (
  `stat_date` date NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `group_chapters` int(11) DEFAULT NULL,
  `group_channels` int(11) DEFAULT NULL,
  `group_admin_1` int(11) DEFAULT NULL,
  `group_admin_2` int(11) DEFAULT NULL,
  `group_admin_3` int(11) DEFAULT NULL,
  `group_admin_4` int(11) DEFAULT NULL,
  `group_admin_5` int(11) DEFAULT NULL,
  `user_members_group` int(11) DEFAULT NULL,
  `user_members_chapters` int(11) DEFAULT NULL,
  `user_members_channels` int(11) DEFAULT NULL,
  `events_draft` int(11) DEFAULT NULL,
  `events_published` int(11) DEFAULT NULL,
  `events_completed` int(11) DEFAULT NULL,
  `posts_draft` int(11) DEFAULT NULL,
  `posts_published` int(11) DEFAULT NULL,
  `newsletters_draft` int(11) DEFAULT NULL,
  `newsletters_published` int(11) DEFAULT NULL,
  `resources_published` int(11) DEFAULT NULL,
  `surveys_draft` int(11) DEFAULT NULL,
  `surveys_published` int(11) DEFAULT NULL,
  `highlights_published` int(11) DEFAULT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `stats_zones_daily_count`
--

CREATE TABLE `stats_zones_daily_count` (
  `stat_date` date NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `zone_admins` int(11) DEFAULT NULL,
  `zone_regions` int(11) DEFAULT NULL,
  `zone_branches` int(11) DEFAULT NULL,
  `user_members_unique` int(11) DEFAULT NULL,
  `user_members_1group` int(11) DEFAULT NULL,
  `user_members_2group` int(11) DEFAULT NULL,
  `user_members_3group` int(11) DEFAULT NULL,
  `user_members_total` int(11) DEFAULT NULL,
  `user_logins` int(11) DEFAULT NULL,
  `emails_out` int(11) DEFAULT NULL,
  `emails_in` int(11) DEFAULT NULL,
  `number_of_groups` int(11) DEFAULT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `surveys_v2`
--

CREATE TABLE `surveys_v2` (
  `surveyid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `topicid` varchar(32) NOT NULL COMMENT 'Composite String:eg, GRM_{groupid}_{chapterid}_{channelid}_{joinleave}, etc',
  `groupid` int(11) NOT NULL,
  `chapterid` int(11) NOT NULL,
  `channelid` int(11) NOT NULL,
  `surveytype` tinyint(4) NOT NULL,
  `surveysubtype` tinyint(4) NOT NULL,
  `anonymous` tinyint(4) NOT NULL COMMENT '0 no 1 yes',
  `survey_json` mediumtext,
  `surveyname` varchar(255) DEFAULT NULL,
  `start_on` datetime DEFAULT NULL,
  `end_on` datetime DEFAULT NULL,
  `createdby` int(11) NOT NULL COMMENT 'who create survey',
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '2' COMMENT '1 active, 2 draft, 0 inactive',
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `survey_responses_v2`
--

CREATE TABLE `survey_responses_v2` (
  `responseid` bigint(20) NOT NULL,
  `surveyid` int(11) DEFAULT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `userid` int(11) DEFAULT NULL COMMENT 'Userid for non anonymous survey, null otherwise',
  `response_json` text COMMENT 'survey.js JSON',
  `profile_json` varchar(1024) DEFAULT '[]' COMMENT 'In case of non anonymous surveys, record the profile of the user, empty otherwise',
  `createdon` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `system_messages`
--

CREATE TABLE `system_messages` (
  `message_id` int(11) NOT NULL,
  `message_type` tinyint(4) NOT NULL COMMENT '1 - System Maintenance, 2 Feature Update, 3 Incident Management',
  `recipients` text,
  `subject` varchar(255) DEFAULT NULL,
  `message` text,
  `recipient_type` varchar(255) NOT NULL COMMENT '1 - Business Contact, 2 Security Contact, 3 Technical Contact ',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updatedon` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL DEFAULT '2' COMMENT '0 deleted, 1 sent, 2 draft'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `teamid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `groupid` int(11) NOT NULL,
  `team_name` varchar(128) DEFAULT NULL,
  `createdby` int(11) NOT NULL,
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `team_start_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) DEFAULT '2'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `team_memberid` int(11) NOT NULL,
  `teamid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `teamroleid` int(11) NOT NULL DEFAULT '0',
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_active_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `team_requests`
--

CREATE TABLE `team_requests` (
  `team_request_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL DEFAULT '0',
  `senderid` int(11) NOT NULL,
  `sender_role_id` int(11) NOT NULL DEFAULT '0',
  `receiverid` int(11) NOT NULL,
  `receiver_role_id` int(11) NOT NULL DEFAULT '0',
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 sent 2 accept, 0 reject'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `team_role_join_requests`
--

CREATE TABLE `team_role_join_requests` (
  `teamroleid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL DEFAULT '0',
  `userid` int(11) NOT NULL,
  `role_survey_response` text,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 request sent, 2 request processed'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `team_role_type`
--

CREATE TABLE `team_role_type` (
  `teamroleid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `sys_team_role_type` tinyint(4) NOT NULL DEFAULT '0',
  `groupid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `min_required` tinyint(4) NOT NULL DEFAULT '1',
  `max_allowed` tinyint(4) NOT NULL DEFAULT '1',
  `welcome_message` text,
  `modifiedon` datetime NOT NULL,
  `isactive` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `team_tasks`
--

CREATE TABLE `team_tasks` (
  `taskid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL DEFAULT '0',
  `zoneid` int(11) NOT NULL DEFAULT '0',
  `teamid` int(11) NOT NULL,
  `parent_taskid` int(11) NOT NULL DEFAULT '0' COMMENT 'Touchpoints can be owned by Action Items. Action Items can be owned by Feedback.',
  `task_type` enum('todo','touchpoint','feedback') CHARACTER SET utf8 DEFAULT 'todo',
  `tasktitle` varchar(255) DEFAULT NULL,
  `assignedto` int(11) DEFAULT NULL,
  `duedate` datetime DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `visibility` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 default, 1 for Feedback recipient, 2 All Team members',
  `createdby` int(11) NOT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `templateid` int(11) NOT NULL,
  `companyid` int(11) DEFAULT NULL,
  `templatename` varchar(255) NOT NULL,
  `templatetype` tinyint(4) NOT NULL COMMENT '1 Newsletter, 2 Announcement , 3 Events, 4 Communications',
  `template` text NOT NULL,
  `createdby` int(11) NOT NULL,
  `createdon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiedon` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isactive` tinyint(4) NOT NULL DEFAULT '2' COMMENT '0 inactive, 1 active, 2 draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userid` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneids` varchar(128) NOT NULL DEFAULT '' COMMENT 'A comma seperated set of zoneids',
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL COMMENT 'Default is NULL',
  `picture` varchar(255) NOT NULL,
  `jobtitle` varchar(255) NOT NULL,
  `opco` varchar(64) DEFAULT NULL,
  `employeetype` varchar(64) DEFAULT NULL COMMENT 'Store attributes that tell if the user is an employee or a contractor',
  `homeoffice` int(11) NOT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `timezone` varchar(32) NOT NULL,
  `confirmationcode` varchar(100) NOT NULL,
  `verificationstatus` tinyint(4) NOT NULL COMMENT '1 confirmed 2 not confirmed',
  `accounttype` tinyint(4) NOT NULL COMMENT '1 for user, 3 for company admin, 5 for zone admin',
  `signuptype` tinyint(4) NOT NULL COMMENT '0 Normal 1 for google',
  `notification` tinyint(4) NOT NULL COMMENT '1 for on 2 for off',
  `ischange` tinyint(4) NOT NULL COMMENT '0 for not changed 1 for changed',
  `firstlogin` tinyint(4) NOT NULL COMMENT '1 for never logged in, 0 otherwise',
  `landing_page` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 for Discover, 1 for my content',
  `department` int(11) NOT NULL,
  `regionid` int(11) NOT NULL DEFAULT '0',
  `devicetype` tinyint(4) NOT NULL COMMENT '1 for ios 2 for android',
  `devicetoken` varchar(255) NOT NULL,
  `createdon` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `externalid` varchar(64) DEFAULT NULL,
  `aad_oid` varchar(64) DEFAULT NULL COMMENT 'Microsoft AAD OID',
  `externalusername` varchar(128) DEFAULT NULL COMMENT 'Used to store external usernames e.g. Microsoft userPrincipalName or outlook preffered_name',
  `extendedprofile` varchar(2048) DEFAULT NULL COMMENT 'Extended Profile In JSON Format',
  `validatedon` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `policy_accepted_on` datetime DEFAULT NULL,
  `isactive` tinyint(4) NOT NULL COMMENT '1 for active 2 for inactive',
  `admin_zones` varchar(32) NOT NULL DEFAULT '' COMMENT 'A comma seperated list of zones that the admin can manage'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `before_update_users` BEFORE UPDATE ON `users` FOR EACH ROW begin     if (new.companyid != old.companyid)     then         signal SQLSTATE '45000' set message_text = 'Users companyid once assigned cannot be updated';     end if; end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users_api_session`
--

CREATE TABLE `users_api_session` (
  `api_session_id` int(11) NOT NULL,
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `devicetype` tinyint(4) NOT NULL COMMENT '1 for ios 2 for androied',
  `devicetoken` varchar(1000) NOT NULL,
  `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users_common_session`
--

CREATE TABLE `users_common_session` (
  `companyid` int(11) NOT NULL,
  `zoneid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `session_data` varchar(2048) DEFAULT NULL COMMENT 'Serialized data',
  `modifiedon` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users_failed_login`
--

CREATE TABLE `users_failed_login` (
  `email` varchar(255) NOT NULL,
  `attempts` tinyint(4) DEFAULT '1',
  `createdon` datetime NOT NULL,
  `modifiedon` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users_password_reset`
--

CREATE TABLE `users_password_reset` (
  `email` varchar(255) NOT NULL,
  `request_id` varchar(255) NOT NULL,
  `expireson` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`superid`);

--
-- Indexes for table `app_versions`
--
ALTER TABLE `app_versions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budgets_other_funding`
--
ALTER TABLE `budgets_other_funding`
  ADD PRIMARY KEY (`funding_id`);

--
-- Indexes for table `budgets_v2`
--
ALTER TABLE `budgets_v2`
  ADD PRIMARY KEY (`budget_id`),
  ADD UNIQUE KEY `budgets_v2_companyid_zoneid_budget_year_groupid_chapterid_uindex` (`companyid`,`zoneid`,`budget_year_id`,`groupid`,`chapterid`);

--
-- Indexes for table `budgetuses`
--
ALTER TABLE `budgetuses`
  ADD PRIMARY KEY (`usesid`);

--
-- Indexes for table `budgetuses_items`
--
ALTER TABLE `budgetuses_items`
  ADD PRIMARY KEY (`itemid`),
  ADD KEY `budgetuses_items_budgetuses_usesid_fk` (`usesid`);

--
-- Indexes for table `budget_charge_codes`
--
ALTER TABLE `budget_charge_codes`
  ADD PRIMARY KEY (`charge_code_id`);

--
-- Indexes for table `budget_expense_types`
--
ALTER TABLE `budget_expense_types`
  ADD PRIMARY KEY (`expensetypeid`);

--
-- Indexes for table `budget_requests`
--
ALTER TABLE `budget_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `budget_years`
--
ALTER TABLE `budget_years`
  ADD PRIMARY KEY (`budget_year_id`);

--
-- Indexes for table `chapterleads`
--
ALTER TABLE `chapterleads`
  ADD PRIMARY KEY (`leadid`),
  ADD KEY `chapterleads_users_userid` (`userid`);

--
-- Indexes for table `chapters`
--
ALTER TABLE `chapters`
  ADD PRIMARY KEY (`chapterid`),
  ADD KEY `chapterid` (`chapterid`),
  ADD KEY `chapters_groups_groupid_fk` (`groupid`);

--
-- Indexes for table `comments`
--
ALTER TABLE topic_comments
  ADD PRIMARY KEY (`commentid`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`companyid`),
  ADD UNIQUE KEY `companies_subdomain_uindex` (`subdomain`);

--
-- Indexes for table `companybranches`
--
ALTER TABLE `companybranches`
  ADD PRIMARY KEY (`branchid`),
  ADD KEY `companybranches_branchname_uk` (`companyid`,`branchname`,`city`,`state`,`country`);

--
-- Indexes for table `company_analytics`
--
ALTER TABLE `company_analytics`
  ADD PRIMARY KEY (`analyticid`);

--
-- Indexes for table `company_contacts`
--
ALTER TABLE `company_contacts`
  ADD UNIQUE KEY `company_contacts_companyid_contactrole_uindex` (`companyid`,`contactrole`);

--
-- Indexes for table `company_customizations`
--
ALTER TABLE `company_customizations`
  ADD PRIMARY KEY (`companyid`);

--
-- Indexes for table `company_email_settings`
--
ALTER TABLE `company_email_settings`
  ADD PRIMARY KEY (`companyid`);

--
-- Indexes for table `company_footer_links`
--
ALTER TABLE `company_footer_links`
  ADD PRIMARY KEY (`link_id`);

--
-- Indexes for table `company_login_settings`
--
ALTER TABLE `company_login_settings`
  ADD PRIMARY KEY (`settingid`);

--
-- Indexes for table `company_reports`
--
ALTER TABLE `company_reports`
  ADD PRIMARY KEY (`reportid`);

--
-- Indexes for table `company_security_settings`
--
ALTER TABLE `company_security_settings`
  ADD PRIMARY KEY (`companyid`);

--
-- Indexes for table `company_zones`
--
ALTER TABLE `company_zones`
  ADD PRIMARY KEY (`zoneid`);

--
-- Indexes for table `contactus`
--
ALTER TABLE `contactus`
  ADD PRIMARY KEY (`contactid`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`departmentid`);

--
-- Indexes for table `eai_accounts`
--
ALTER TABLE `eai_accounts`
  ADD PRIMARY KEY (`accountid`);

--
-- Indexes for table `eventjoiners`
--
ALTER TABLE `eventjoiners`
  ADD PRIMARY KEY (`joineeid`),
  ADD KEY `eventjoiners_userid_eventid_index` (`userid`,`eventid`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`eventid`),
  ADD KEY `events_companyid_groupid_idx` (`companyid`,`groupid`);

--
-- Indexes for table `event_counters`
--
ALTER TABLE `event_counters`
  ADD PRIMARY KEY (`eventid`);

--
-- Indexes for table `event_custom_fields`
--
ALTER TABLE `event_custom_fields`
  ADD PRIMARY KEY (`custom_field_id`);

--
-- Indexes for table `event_highlights`
--
ALTER TABLE `event_highlights`
  ADD PRIMARY KEY (`event_highlight_id`);

--
-- Indexes for table `event_highlight_comments`
--
ALTER TABLE `event_highlight_comments`
  ADD PRIMARY KEY (`event_highlight_commentid`);

--
-- Indexes for table `event_highlight_likes`
--
ALTER TABLE `event_highlight_likes`
  ADD PRIMARY KEY (`event_highlight_likeid`);

--
-- Indexes for table `event_speakers`
--
ALTER TABLE `event_speakers`
  ADD PRIMARY KEY (`speakerid`);

--
-- Indexes for table `event_speaker_fields`
--
ALTER TABLE `event_speaker_fields`
  ADD PRIMARY KEY (`speaker_fieldid`);

--
-- Indexes for table `event_type`
--
ALTER TABLE `event_type`
  ADD PRIMARY KEY (`typeid`);

--
-- Indexes for table `faqsadmin`
--
ALTER TABLE `faqsadmin`
  ADD PRIMARY KEY (`faqid`);

--
-- Indexes for table `faqsmobile`
--
ALTER TABLE `faqsmobile`
  ADD PRIMARY KEY (`faqid`);

--
-- Indexes for table `groupleads`
--
ALTER TABLE `groupleads`
  ADD PRIMARY KEY (`leadid`),
  ADD KEY `groupleads_groupid_fk` (`groupid`),
  ADD KEY `groupleads_userid_fk` (`userid`);

--
-- Indexes for table `grouplead_type`
--
ALTER TABLE `grouplead_type`
  ADD PRIMARY KEY (`typeid`);

--
-- Indexes for table `groupmembers`
--
ALTER TABLE `groupmembers`
  ADD PRIMARY KEY (`memberid`),
  ADD UNIQUE KEY `unique_member` (`groupid`,`userid`),
  ADD KEY `groupmembers_userid_fk` (`userid`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`groupid`),
  ADD UNIQUE KEY `groups_permatag_company_uk` (`permatag`,`companyid`),
  ADD KEY `groups_companyid_idx` (`companyid`),
  ADD KEY `post_companyid_groupid_idx` (`companyid`,`groupid`);

--
-- Indexes for table `group_channels`
--
ALTER TABLE `group_channels`
  ADD PRIMARY KEY (`channelid`);

--
-- Indexes for table `group_channel_leads`
--
ALTER TABLE `group_channel_leads`
  ADD PRIMARY KEY (`leadid`),
  ADD KEY `channel_leads_users_userid` (`userid`);

--
-- Indexes for table `group_communications`
--
ALTER TABLE `group_communications`
  ADD PRIMARY KEY (`communicationid`);

--
-- Indexes for table `group_linked_groups`
--
ALTER TABLE `group_linked_groups`
  ADD PRIMARY KEY (`groupid`,`linked_groupid`);

--
-- Indexes for table `group_resources`
--
ALTER TABLE `group_resources`
  ADD PRIMARY KEY (`resource_id`);

--
-- Indexes for table `group_tabs`
--
ALTER TABLE `group_tabs`
  ADD PRIMARY KEY (`tabid`);

--
-- Indexes for table `hot_link`
--
ALTER TABLE `hot_link`
  ADD PRIMARY KEY (`link_id`),
  ADD KEY `hot_link_companies_companyid_fk` (`companyid`);

--
-- Indexes for table `integrations`
--
ALTER TABLE `integrations`
  ADD PRIMARY KEY (`integrationid`);

--
-- Indexes for table `integration_records`
--
ALTER TABLE `integration_records`
  ADD PRIMARY KEY (`integrationid`,`record_key`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`jobid`);

--
-- Indexes for table `leadsinvites`
--
ALTER TABLE `leadsinvites`
  ADD PRIMARY KEY (`leadinviteid`);

--
-- Indexes for table `memberinvites`
--
ALTER TABLE `memberinvites`
  ADD PRIMARY KEY (`memberinviteid`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`messageid`);

--
-- Indexes for table `newsletters`
--
ALTER TABLE `newsletters`
  ADD PRIMARY KEY (`newsletterid`);

--
-- Indexes for table `newsletter_attachments`
--
ALTER TABLE `newsletter_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `newsletter_attachments_newsletters_newsletterid_fk` (`newsletterid`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notificationid`);

--
-- Indexes for table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`postid`);

--
-- Indexes for table `postcomments`
--
ALTER TABLE `postcomments`
  ADD PRIMARY KEY (`commentid`);

--
-- Indexes for table `postlikes`
--
ALTER TABLE `postlikes`
  ADD PRIMARY KEY (`likeid`);

--
-- Indexes for table `recruiting`
--
ALTER TABLE `recruiting`
  ADD PRIMARY KEY (`recruit_id`);

--
-- Indexes for table `referral`
--
ALTER TABLE `referral`
  ADD PRIMARY KEY (`referral_id`);

--
-- Indexes for table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`regionid`);

--
-- Indexes for table `release_notes`
--
ALTER TABLE `release_notes`
  ADD PRIMARY KEY (`releaseid`);

--
-- Indexes for table `stats_company_daily_count`
--
ALTER TABLE `stats_company_daily_count`
  ADD UNIQUE KEY `stats_company_daily_count_pk` (`stat_date`,`companyid`);

--
-- Indexes for table `stats_groups_daily_count`
--
ALTER TABLE `stats_groups_daily_count`
  ADD PRIMARY KEY (`stat_date`,`zoneid`,`groupid`);

--
-- Indexes for table `stats_zones_daily_count`
--
ALTER TABLE `stats_zones_daily_count`
  ADD PRIMARY KEY (`stat_date`,`zoneid`);

--
-- Indexes for table `surveys_v2`
--
ALTER TABLE `surveys_v2`
  ADD PRIMARY KEY (`surveyid`);

--
-- Indexes for table `survey_responses_v2`
--
ALTER TABLE `survey_responses_v2`
  ADD PRIMARY KEY (`responseid`),
  ADD KEY `survey_responses_v2_surveys_v2_surveyid_fk` (`surveyid`);

--
-- Indexes for table `system_messages`
--
ALTER TABLE `system_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`teamid`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`team_memberid`),
  ADD KEY `team_members_teams_teamid_fk` (`teamid`);

--
-- Indexes for table `team_requests`
--
ALTER TABLE `team_requests`
  ADD PRIMARY KEY (`team_request_id`);

--
-- Indexes for table `team_role_join_requests`
--
ALTER TABLE `team_role_join_requests`
  ADD UNIQUE KEY `unique_index` (`teamroleid`,`userid`);

--
-- Indexes for table `team_role_type`
--
ALTER TABLE `team_role_type`
  ADD PRIMARY KEY (`teamroleid`);

--
-- Indexes for table `team_tasks`
--
ALTER TABLE `team_tasks`
  ADD PRIMARY KEY (`taskid`),
  ADD KEY `team_tasks_teams_teamid_fk` (`teamid`),
  ADD KEY `team_tasks_team_tasks_taskid_fk` (`parent_taskid`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`templateid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `email_uniq` (`email`),
  ADD UNIQUE KEY `users_externalid_uindex` (`externalid`),
  ADD UNIQUE KEY `users_aad_oid_uindex` (`aad_oid`),
  ADD KEY `users_companyid_idx` (`companyid`);

--
-- Indexes for table `users_api_session`
--
ALTER TABLE `users_api_session`
  ADD PRIMARY KEY (`api_session_id`),
  ADD KEY `users_api_session_users_userid_fk` (`userid`);

--
-- Indexes for table `users_common_session`
--
ALTER TABLE `users_common_session`
  ADD PRIMARY KEY (`companyid`,`zoneid`,`userid`),
  ADD KEY `users_common_session_users_userid_fk` (`userid`);

--
-- Indexes for table `users_failed_login`
--
ALTER TABLE `users_failed_login`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `users_password_reset`
--
ALTER TABLE `users_password_reset`
  ADD PRIMARY KEY (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `superid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `app_versions`
--
ALTER TABLE `app_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets_other_funding`
--
ALTER TABLE `budgets_other_funding`
  MODIFY `funding_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets_v2`
--
ALTER TABLE `budgets_v2`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgetuses`
--
ALTER TABLE `budgetuses`
  MODIFY `usesid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgetuses_items`
--
ALTER TABLE `budgetuses_items`
  MODIFY `itemid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_charge_codes`
--
ALTER TABLE `budget_charge_codes`
  MODIFY `charge_code_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_expense_types`
--
ALTER TABLE `budget_expense_types`
  MODIFY `expensetypeid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_requests`
--
ALTER TABLE `budget_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_years`
--
ALTER TABLE `budget_years`
  MODIFY `budget_year_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chapterleads`
--
ALTER TABLE `chapterleads`
  MODIFY `leadid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chapters`
--
ALTER TABLE `chapters`
  MODIFY `chapterid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE topic_comments
  MODIFY `commentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `companyid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `companybranches`
--
ALTER TABLE `companybranches`
  MODIFY `branchid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_footer_links`
--
ALTER TABLE `company_footer_links`
  MODIFY `link_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_login_settings`
--
ALTER TABLE `company_login_settings`
  MODIFY `settingid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_zones`
--
ALTER TABLE `company_zones`
  MODIFY `zoneid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contactus`
--
ALTER TABLE `contactus`
  MODIFY `contactid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `departmentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eai_accounts`
--
ALTER TABLE `eai_accounts`
  MODIFY `accountid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eventjoiners`
--
ALTER TABLE `eventjoiners`
  MODIFY `joineeid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `eventid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_custom_fields`
--
ALTER TABLE `event_custom_fields`
  MODIFY `custom_field_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_highlights`
--
ALTER TABLE `event_highlights`
  MODIFY `event_highlight_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_highlight_comments`
--
ALTER TABLE `event_highlight_comments`
  MODIFY `event_highlight_commentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_highlight_likes`
--
ALTER TABLE `event_highlight_likes`
  MODIFY `event_highlight_likeid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_speakers`
--
ALTER TABLE `event_speakers`
  MODIFY `speakerid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_speaker_fields`
--
ALTER TABLE `event_speaker_fields`
  MODIFY `speaker_fieldid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_type`
--
ALTER TABLE `event_type`
  MODIFY `typeid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqsadmin`
--
ALTER TABLE `faqsadmin`
  MODIFY `faqid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqsmobile`
--
ALTER TABLE `faqsmobile`
  MODIFY `faqid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groupleads`
--
ALTER TABLE `groupleads`
  MODIFY `leadid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grouplead_type`
--
ALTER TABLE `grouplead_type`
  MODIFY `typeid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groupmembers`
--
ALTER TABLE `groupmembers`
  MODIFY `memberid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `groupid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_channels`
--
ALTER TABLE `group_channels`
  MODIFY `channelid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_channel_leads`
--
ALTER TABLE `group_channel_leads`
  MODIFY `leadid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_communications`
--
ALTER TABLE `group_communications`
  MODIFY `communicationid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_resources`
--
ALTER TABLE `group_resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_tabs`
--
ALTER TABLE `group_tabs`
  MODIFY `tabid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hot_link`
--
ALTER TABLE `hot_link`
  MODIFY `link_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `integrations`
--
ALTER TABLE `integrations`
  MODIFY `integrationid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leadsinvites`
--
ALTER TABLE `leadsinvites`
  MODIFY `leadinviteid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memberinvites`
--
ALTER TABLE `memberinvites`
  MODIFY `memberinviteid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `messageid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newsletters`
--
ALTER TABLE `newsletters`
  MODIFY `newsletterid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newsletter_attachments`
--
ALTER TABLE `newsletter_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notificationid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post`
--
ALTER TABLE `post`
  MODIFY `postid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `postcomments`
--
ALTER TABLE `postcomments`
  MODIFY `commentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `postlikes`
--
ALTER TABLE `postlikes`
  MODIFY `likeid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recruiting`
--
ALTER TABLE `recruiting`
  MODIFY `recruit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referral`
--
ALTER TABLE `referral`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `regionid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `release_notes`
--
ALTER TABLE `release_notes`
  MODIFY `releaseid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `surveys_v2`
--
ALTER TABLE `surveys_v2`
  MODIFY `surveyid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_responses_v2`
--
ALTER TABLE `survey_responses_v2`
  MODIFY `responseid` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_messages`
--
ALTER TABLE `system_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `teamid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `team_memberid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_requests`
--
ALTER TABLE `team_requests`
  MODIFY `team_request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_role_type`
--
ALTER TABLE `team_role_type`
  MODIFY `teamroleid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_tasks`
--
ALTER TABLE `team_tasks`
  MODIFY `taskid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `templateid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_api_session`
--
ALTER TABLE `users_api_session`
  MODIFY `api_session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budgetuses_items`
--
ALTER TABLE `budgetuses_items`
  ADD CONSTRAINT `budgetuses_items_budgetuses_usesid_fk` FOREIGN KEY (`usesid`) REFERENCES `budgetuses` (`usesid`) ON DELETE CASCADE;

--
-- Constraints for table `chapterleads`
--
ALTER TABLE `chapterleads`
  ADD CONSTRAINT `chapterleads_users_userid_fk` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`);

--
-- Constraints for table `company_contacts`
--
ALTER TABLE `company_contacts`
  ADD CONSTRAINT `company_contacts_companies_companyid_fk` FOREIGN KEY (`companyid`) REFERENCES `companies` (`companyid`) ON DELETE CASCADE;

--
-- Constraints for table `company_customizations`
--
ALTER TABLE `company_customizations`
  ADD CONSTRAINT `company_customizations_companies_companyid_fk` FOREIGN KEY (`companyid`) REFERENCES `companies` (`companyid`);

--
-- Constraints for table `company_email_settings`
--
ALTER TABLE `company_email_settings`
  ADD CONSTRAINT `company_email_settings_companies_companyid_fk` FOREIGN KEY (`companyid`) REFERENCES `companies` (`companyid`);

--
-- Constraints for table `company_security_settings`
--
ALTER TABLE `company_security_settings`
  ADD CONSTRAINT `company_security_settings_companies_companyid_fk` FOREIGN KEY (`companyid`) REFERENCES `companies` (`companyid`) ON DELETE CASCADE;

--
-- Constraints for table `groupleads`
--
ALTER TABLE `groupleads`
  ADD CONSTRAINT `groupleads_userid_fk` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`);

--
-- Constraints for table `groupmembers`
--
ALTER TABLE `groupmembers`
  ADD CONSTRAINT `groupmembers_userid_fk` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`);

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_company_id_fk` FOREIGN KEY (`companyid`) REFERENCES `companies` (`companyid`);

--
-- Constraints for table `group_channel_leads`
--
ALTER TABLE `group_channel_leads`
  ADD CONSTRAINT `channel_leads_users_userid_fk` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`);

--
-- Constraints for table `hot_link`
--
ALTER TABLE `hot_link`
  ADD CONSTRAINT `hot_link_companies_companyid_fk` FOREIGN KEY (`companyid`) REFERENCES `companies` (`companyid`);

--
-- Constraints for table `newsletter_attachments`
--
ALTER TABLE `newsletter_attachments`
  ADD CONSTRAINT `newsletter_attachments_newsletters_newsletterid_fk` FOREIGN KEY (`newsletterid`) REFERENCES `newsletters` (`newsletterid`) ON DELETE CASCADE;

--
-- Constraints for table `survey_responses_v2`
--
ALTER TABLE `survey_responses_v2`
  ADD CONSTRAINT `survey_responses_v2_surveys_v2_surveyid_fk` FOREIGN KEY (`surveyid`) REFERENCES `surveys_v2` (`surveyid`);

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_teams_teamid_fk` FOREIGN KEY (`teamid`) REFERENCES `teams` (`teamid`);

--
-- Constraints for table `team_tasks`
--
ALTER TABLE `team_tasks`
  ADD CONSTRAINT `team_tasks_teams_teamid_fk` FOREIGN KEY (`teamid`) REFERENCES `teams` (`teamid`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_company_id_fk` FOREIGN KEY (`companyid`) REFERENCES `companies` (`companyid`);

--
-- Constraints for table `users_api_session`
--
ALTER TABLE `users_api_session`
  ADD CONSTRAINT `users_api_session_users_userid_fk` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON UPDATE CASCADE;

--
-- Constraints for table `users_common_session`
--
ALTER TABLE `users_common_session`
  ADD CONSTRAINT `users_common_session_users_userid_fk` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
