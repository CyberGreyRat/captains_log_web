-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 31. Aug 2026 um 14:44
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `captainslog_db`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `acceptance_criteria_templates`
--

CREATE TABLE `acceptance_criteria_templates` (
  `id` int(11) NOT NULL,
  `requirement_type` varchar(20) NOT NULL,
  `category` varchar(100) NOT NULL,
  `criterion_text` text NOT NULL,
  `keywords` varchar(500) DEFAULT NULL,
  `source_type` enum('system','user','learned') NOT NULL DEFAULT 'system',
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `criterion_hash` char(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `attachment_links`
--

CREATE TABLE `attachment_links` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attachment_id` bigint(20) UNSIGNED NOT NULL,
  `entity_type` enum('requirement','use_case','task','issue','milestone','asset','risk','user_story') NOT NULL,
  `entity_id` bigint(20) UNSIGNED NOT NULL,
  `entity_key` varchar(100) DEFAULT NULL,
  `entity_title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `audit_batches`
--

CREATE TABLE `audit_batches` (
  `id` char(36) NOT NULL,
  `project_id` varchar(36) DEFAULT NULL,
  `batch_type` varchar(50) NOT NULL,
  `source_name` varchar(255) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `total_records` int(11) NOT NULL DEFAULT 0,
  `successful_records` int(11) NOT NULL DEFAULT 0,
  `failed_records` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `project_id` varchar(36) DEFAULT NULL,
  `batch_id` char(36) DEFAULT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` varchar(100) NOT NULL,
  `entity_key` varchar(100) DEFAULT NULL,
  `entity_title` varchar(255) DEFAULT NULL,
  `action` enum('CREATE','UPDATE','DELETE','IMPORT','LINK','UNLINK','COMMENT','LOGIN','EXPORT') NOT NULL,
  `old_data` longtext DEFAULT NULL,
  `new_data` longtext DEFAULT NULL,
  `changed_fields` longtext DEFAULT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  `actor_name` varchar(100) DEFAULT NULL,
  `source_type` varchar(50) NOT NULL DEFAULT 'database',
  `source_name` varchar(255) DEFAULT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `request_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `evidences`
--

CREATE TABLE `evidences` (
  `id` int(11) NOT NULL,
  `requirement_id` int(11) DEFAULT NULL,
  `project_id` varchar(36) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `console_output` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `evidences`
--
DELIMITER $$
CREATE TRIGGER `audit_evidences_delete` AFTER DELETE ON `evidences` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.project_id,@audit_batch_id,'evidence',CAST(OLD.id AS CHAR),CAST(OLD.requirement_id AS CHAR),OLD.file_path,'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'requirement_id', OLD.requirement_id,
            'project_id', OLD.project_id,
            'file_path', OLD.file_path,
            'console_output', OLD.console_output,
            'uploaded_by', OLD.uploaded_by,
            'created_at', OLD.created_at
        ),COALESCE(@audit_user_id,OLD.uploaded_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_evidences_insert` AFTER INSERT ON `evidences` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'evidence',CAST(NEW.id AS CHAR),CAST(NEW.requirement_id AS CHAR),NEW.file_path,'CREATE',JSON_OBJECT(
            'id', NEW.id,
            'requirement_id', NEW.requirement_id,
            'project_id', NEW.project_id,
            'file_path', NEW.file_path,
            'console_output', NEW.console_output,
            'uploaded_by', NEW.uploaded_by,
            'created_at', NEW.created_at
        ),COALESCE(@audit_user_id,NEW.uploaded_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_evidences_update` AFTER UPDATE ON `evidences` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'evidence',CAST(NEW.id AS CHAR),CAST(NEW.requirement_id AS CHAR),NEW.file_path,'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'requirement_id', OLD.requirement_id,
            'project_id', OLD.project_id,
            'file_path', OLD.file_path,
            'console_output', OLD.console_output,
            'uploaded_by', OLD.uploaded_by,
            'created_at', OLD.created_at
        ),JSON_OBJECT(
            'id', NEW.id,
            'requirement_id', NEW.requirement_id,
            'project_id', NEW.project_id,
            'file_path', NEW.file_path,
            'console_output', NEW.console_output,
            'uploaded_by', NEW.uploaded_by,
            'created_at', NEW.created_at
        ),COALESCE(@audit_user_id,NEW.uploaded_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `iso14001_templates`
--

CREATE TABLE `iso14001_templates` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `phase` varchar(50) DEFAULT 'Produktion'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `issues`
--

CREATE TABLE `issues` (
  `id` int(11) NOT NULL,
  `project_id` varchar(36) NOT NULL,
  `issue_key` varchar(30) NOT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `issue_type` enum('bug','change_request','customer_feedback','question','deviation','improvement') NOT NULL DEFAULT 'bug',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','in_progress','waiting_response','ready_for_test','approved','closed','rejected') NOT NULL DEFAULT 'open',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `severity` enum('none','low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `category` varchar(100) DEFAULT NULL,
  `reporter_user_id` int(11) DEFAULT NULL,
  `assignee_user_id` int(11) DEFAULT NULL,
  `external_reporter` varchar(150) DEFAULT NULL,
  `external_assignee` varchar(150) DEFAULT NULL,
  `reported_at` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `source_type` enum('manual','excel','customer','test','cli','api') NOT NULL DEFAULT 'manual',
  `source_document` varchar(255) DEFAULT NULL,
  `source_sheet` varchar(100) DEFAULT NULL,
  `source_row` int(11) DEFAULT NULL,
  `external_response` text DEFAULT NULL,
  `internal_response` text DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `issues`
--
DELIMITER $$
CREATE TRIGGER `audit_issues_delete` AFTER DELETE ON `issues` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.project_id,@audit_batch_id,'issue',CAST(OLD.id AS CHAR),OLD.issue_key,OLD.title,'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'issue_key', OLD.issue_key,
            'external_id', OLD.external_id,
            'issue_type', OLD.issue_type,
            'title', OLD.title,
            'description', OLD.description,
            'status', OLD.status,
            'priority', OLD.priority,
            'severity', OLD.severity,
            'category', OLD.category,
            'reporter_user_id', OLD.reporter_user_id,
            'assignee_user_id', OLD.assignee_user_id,
            'external_reporter', OLD.external_reporter,
            'external_assignee', OLD.external_assignee,
            'reported_at', OLD.reported_at,
            'due_date', OLD.due_date,
            'resolved_at', OLD.resolved_at,
            'source_type', OLD.source_type,
            'source_document', OLD.source_document,
            'source_sheet', OLD.source_sheet,
            'source_row', OLD.source_row,
            'external_response', OLD.external_response,
            'internal_response', OLD.internal_response,
            'resolution', OLD.resolution,
            'created_by', OLD.created_by,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),COALESCE(@audit_user_id,OLD.created_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_issues_insert` AFTER INSERT ON `issues` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'issue',CAST(NEW.id AS CHAR),NEW.issue_key,NEW.title,'CREATE',JSON_OBJECT(
            'id', NEW.id,
            'issue_key', NEW.issue_key,
            'external_id', NEW.external_id,
            'issue_type', NEW.issue_type,
            'title', NEW.title,
            'description', NEW.description,
            'status', NEW.status,
            'priority', NEW.priority,
            'severity', NEW.severity,
            'category', NEW.category,
            'reporter_user_id', NEW.reporter_user_id,
            'assignee_user_id', NEW.assignee_user_id,
            'external_reporter', NEW.external_reporter,
            'external_assignee', NEW.external_assignee,
            'reported_at', NEW.reported_at,
            'due_date', NEW.due_date,
            'resolved_at', NEW.resolved_at,
            'source_type', NEW.source_type,
            'source_document', NEW.source_document,
            'source_sheet', NEW.source_sheet,
            'source_row', NEW.source_row,
            'external_response', NEW.external_response,
            'internal_response', NEW.internal_response,
            'resolution', NEW.resolution,
            'created_by', NEW.created_by,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),COALESCE(@audit_user_id,NEW.created_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_issues_update` AFTER UPDATE ON `issues` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'issue',CAST(NEW.id AS CHAR),NEW.issue_key,NEW.title,'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'issue_key', OLD.issue_key,
            'external_id', OLD.external_id,
            'issue_type', OLD.issue_type,
            'title', OLD.title,
            'description', OLD.description,
            'status', OLD.status,
            'priority', OLD.priority,
            'severity', OLD.severity,
            'category', OLD.category,
            'reporter_user_id', OLD.reporter_user_id,
            'assignee_user_id', OLD.assignee_user_id,
            'external_reporter', OLD.external_reporter,
            'external_assignee', OLD.external_assignee,
            'reported_at', OLD.reported_at,
            'due_date', OLD.due_date,
            'resolved_at', OLD.resolved_at,
            'source_type', OLD.source_type,
            'source_document', OLD.source_document,
            'source_sheet', OLD.source_sheet,
            'source_row', OLD.source_row,
            'external_response', OLD.external_response,
            'internal_response', OLD.internal_response,
            'resolution', OLD.resolution,
            'created_by', OLD.created_by,
            'created_at', OLD.created_at,
            'updated_at', OLD.updated_at
        ),JSON_OBJECT(
            'id', NEW.id,
            'issue_key', NEW.issue_key,
            'external_id', NEW.external_id,
            'issue_type', NEW.issue_type,
            'title', NEW.title,
            'description', NEW.description,
            'status', NEW.status,
            'priority', NEW.priority,
            'severity', NEW.severity,
            'category', NEW.category,
            'reporter_user_id', NEW.reporter_user_id,
            'assignee_user_id', NEW.assignee_user_id,
            'external_reporter', NEW.external_reporter,
            'external_assignee', NEW.external_assignee,
            'reported_at', NEW.reported_at,
            'due_date', NEW.due_date,
            'resolved_at', NEW.resolved_at,
            'source_type', NEW.source_type,
            'source_document', NEW.source_document,
            'source_sheet', NEW.source_sheet,
            'source_row', NEW.source_row,
            'external_response', NEW.external_response,
            'internal_response', NEW.internal_response,
            'resolution', NEW.resolution,
            'created_by', NEW.created_by,
            'created_at', NEW.created_at,
            'updated_at', NEW.updated_at
        ),COALESCE(@audit_user_id,NEW.created_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `issue_comments`
--

CREATE TABLE `issue_comments` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `comment_type` enum('internal','customer','supplier','technical','test_result','decision') NOT NULL DEFAULT 'internal',
  `comment_text` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `external_author` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `issue_comments`
--
DELIMITER $$
CREATE TRIGGER `audit_issue_comments_delete` AFTER DELETE ON `issue_comments` FOR EACH ROW BEGIN
    DECLARE v_project_id VARCHAR(36);
    DECLARE v_issue_key VARCHAR(30);
    SELECT project_id, issue_key INTO v_project_id, v_issue_key FROM issues WHERE id=OLD.issue_id LIMIT 1;
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (v_project_id,@audit_batch_id,'issue_comment',CAST(OLD.id AS CHAR),v_issue_key,CONCAT('Kommentar zu ',v_issue_key),'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'issue_id', OLD.issue_id,
            'comment_type', OLD.comment_type,
            'comment_text', OLD.comment_text,
            'created_by', OLD.created_by,
            'external_author', OLD.external_author,
            'created_at', OLD.created_at
        ),COALESCE(@audit_user_id,OLD.created_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_issue_comments_insert` AFTER INSERT ON `issue_comments` FOR EACH ROW BEGIN
    DECLARE v_project_id VARCHAR(36);
    DECLARE v_issue_key VARCHAR(30);
    SELECT project_id, issue_key INTO v_project_id, v_issue_key FROM issues WHERE id=NEW.issue_id LIMIT 1;
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (v_project_id,@audit_batch_id,'issue_comment',CAST(NEW.id AS CHAR),v_issue_key,CONCAT('Kommentar zu ',v_issue_key),'COMMENT',JSON_OBJECT(
            'id', NEW.id,
            'issue_id', NEW.issue_id,
            'comment_type', NEW.comment_type,
            'comment_text', NEW.comment_text,
            'created_by', NEW.created_by,
            'external_author', NEW.external_author,
            'created_at', NEW.created_at
        ),COALESCE(@audit_user_id,NEW.created_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_issue_comments_update` AFTER UPDATE ON `issue_comments` FOR EACH ROW BEGIN
    DECLARE v_project_id VARCHAR(36);
    DECLARE v_issue_key VARCHAR(30);
    SELECT project_id, issue_key INTO v_project_id, v_issue_key FROM issues WHERE id=NEW.issue_id LIMIT 1;
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (v_project_id,@audit_batch_id,'issue_comment',CAST(NEW.id AS CHAR),v_issue_key,CONCAT('Kommentar zu ',v_issue_key),'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'issue_id', OLD.issue_id,
            'comment_type', OLD.comment_type,
            'comment_text', OLD.comment_text,
            'created_by', OLD.created_by,
            'external_author', OLD.external_author,
            'created_at', OLD.created_at
        ),JSON_OBJECT(
            'id', NEW.id,
            'issue_id', NEW.issue_id,
            'comment_type', NEW.comment_type,
            'comment_text', NEW.comment_text,
            'created_by', NEW.created_by,
            'external_author', NEW.external_author,
            'created_at', NEW.created_at
        ),COALESCE(@audit_user_id,NEW.created_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `issue_history`
--

CREATE TABLE `issue_history` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `project_id` varchar(36) NOT NULL,
  `action` varchar(50) NOT NULL,
  `change_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`change_data`)),
  `modified_by` int(11) DEFAULT NULL,
  `hostname` varchar(255) DEFAULT 'LocalPC',
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `issue_imports`
--

CREATE TABLE `issue_imports` (
  `id` int(11) NOT NULL,
  `project_id` varchar(36) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_hash` char(64) NOT NULL,
  `sheet_name` varchar(100) DEFAULT NULL,
  `imported_rows` int(11) NOT NULL DEFAULT 0,
  `skipped_rows` int(11) NOT NULL DEFAULT 0,
  `failed_rows` int(11) NOT NULL DEFAULT 0,
  `import_status` enum('started','completed','completed_with_errors','failed') NOT NULL DEFAULT 'started',
  `import_log` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`import_log`)),
  `imported_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `issue_imports`
--
DELIMITER $$
CREATE TRIGGER `audit_issue_imports_delete` AFTER DELETE ON `issue_imports` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.project_id,@audit_batch_id,'issue_import',CAST(OLD.id AS CHAR),OLD.file_hash,OLD.original_filename,'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'original_filename', OLD.original_filename,
            'file_hash', OLD.file_hash,
            'sheet_name', OLD.sheet_name,
            'imported_rows', OLD.imported_rows,
            'skipped_rows', OLD.skipped_rows,
            'failed_rows', OLD.failed_rows,
            'import_status', OLD.import_status,
            'import_log', OLD.import_log,
            'imported_by', OLD.imported_by,
            'created_at', OLD.created_at
        ),COALESCE(@audit_user_id,OLD.imported_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_issue_imports_insert` AFTER INSERT ON `issue_imports` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'issue_import',CAST(NEW.id AS CHAR),NEW.file_hash,NEW.original_filename,'CREATE',JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'original_filename', NEW.original_filename,
            'file_hash', NEW.file_hash,
            'sheet_name', NEW.sheet_name,
            'imported_rows', NEW.imported_rows,
            'skipped_rows', NEW.skipped_rows,
            'failed_rows', NEW.failed_rows,
            'import_status', NEW.import_status,
            'import_log', NEW.import_log,
            'imported_by', NEW.imported_by,
            'created_at', NEW.created_at
        ),COALESCE(@audit_user_id,NEW.imported_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_issue_imports_update` AFTER UPDATE ON `issue_imports` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'issue_import',CAST(NEW.id AS CHAR),NEW.file_hash,NEW.original_filename,'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'original_filename', OLD.original_filename,
            'file_hash', OLD.file_hash,
            'sheet_name', OLD.sheet_name,
            'imported_rows', OLD.imported_rows,
            'skipped_rows', OLD.skipped_rows,
            'failed_rows', OLD.failed_rows,
            'import_status', OLD.import_status,
            'import_log', OLD.import_log,
            'imported_by', OLD.imported_by,
            'created_at', OLD.created_at
        ),JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'original_filename', NEW.original_filename,
            'file_hash', NEW.file_hash,
            'sheet_name', NEW.sheet_name,
            'imported_rows', NEW.imported_rows,
            'skipped_rows', NEW.skipped_rows,
            'failed_rows', NEW.failed_rows,
            'import_status', NEW.import_status,
            'import_log', NEW.import_log,
            'imported_by', NEW.imported_by,
            'created_at', NEW.created_at
        ),COALESCE(@audit_user_id,NEW.imported_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `issue_requirements`
--

CREATE TABLE `issue_requirements` (
  `issue_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `relation_type` enum('affects','caused_by','resolved_by','verified_by','derived') NOT NULL DEFAULT 'affects',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `issue_tasks`
--

CREATE TABLE `issue_tasks` (
  `issue_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `relation_type` enum('investigation','implementation','test','documentation','follow_up') NOT NULL DEFAULT 'implementation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `projects`
--

CREATE TABLE `projects` (
  `id` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `projects`
--
DELIMITER $$
CREATE TRIGGER `audit_projects_delete` AFTER DELETE ON `projects` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.id,@audit_batch_id,'project',OLD.id,OLD.id,OLD.name,'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'name', OLD.name,
            'description', OLD.description,
            'created_by', OLD.created_by,
            'created_at', OLD.created_at
        ),COALESCE(@audit_user_id,OLD.created_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_projects_insert` AFTER INSERT ON `projects` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.id,@audit_batch_id,'project',NEW.id,NEW.id,NEW.name,'CREATE',JSON_OBJECT(
            'id', NEW.id,
            'name', NEW.name,
            'description', NEW.description,
            'created_by', NEW.created_by,
            'created_at', NEW.created_at
        ),COALESCE(@audit_user_id,NEW.created_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_projects_update` AFTER UPDATE ON `projects` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.id,@audit_batch_id,'project',NEW.id,NEW.id,NEW.name,'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'name', OLD.name,
            'description', OLD.description,
            'created_by', OLD.created_by,
            'created_at', OLD.created_at
        ),JSON_OBJECT(
            'id', NEW.id,
            'name', NEW.name,
            'description', NEW.description,
            'created_by', NEW.created_by,
            'created_at', NEW.created_at
        ),COALESCE(@audit_user_id,NEW.created_by),COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `project_attachments`
--

CREATE TABLE `project_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `project_id` varchar(100) NOT NULL,
  `attachment_key` varchar(30) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'Sonstiges',
  `description` text DEFAULT NULL,
  `storage_type` enum('upload','link') NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `stored_filename` varchar(255) DEFAULT NULL,
  `relative_path` varchar(700) DEFAULT NULL,
  `mime_type` varchar(150) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `sha256` char(64) DEFAULT NULL,
  `version_label` varchar(80) DEFAULT NULL,
  `status` enum('working','review','released','obsolete') NOT NULL DEFAULT 'working',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `project_members`
--

CREATE TABLE `project_members` (
  `project_id` varchar(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_role` varchar(100) DEFAULT NULL,
  `expertise` varchar(255) DEFAULT NULL,
  `availability` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `project_members`
--
DELIMITER $$
CREATE TRIGGER `audit_project_members_delete` AFTER DELETE ON `project_members` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.project_id,@audit_batch_id,'project_member',CONCAT(OLD.project_id,':',OLD.user_id),CAST(OLD.user_id AS CHAR),CONCAT('Nutzer ',OLD.user_id),'DELETE',JSON_OBJECT(
            'project_id', OLD.project_id,
            'user_id', OLD.user_id,
            'project_role', OLD.project_role,
            'expertise', OLD.expertise,
            'availability', OLD.availability,
            'is_active', OLD.is_active,
            'joined_at', OLD.joined_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_project_members_insert` AFTER INSERT ON `project_members` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'project_member',CONCAT(NEW.project_id,':',NEW.user_id),CAST(NEW.user_id AS CHAR),CONCAT('Nutzer ',NEW.user_id),'CREATE',JSON_OBJECT(
            'project_id', NEW.project_id,
            'user_id', NEW.user_id,
            'project_role', NEW.project_role,
            'expertise', NEW.expertise,
            'availability', NEW.availability,
            'is_active', NEW.is_active,
            'joined_at', NEW.joined_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_project_members_update` AFTER UPDATE ON `project_members` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'project_member',CONCAT(NEW.project_id,':',NEW.user_id),CAST(NEW.user_id AS CHAR),CONCAT('Nutzer ',NEW.user_id),'UPDATE',JSON_OBJECT(
            'project_id', OLD.project_id,
            'user_id', OLD.user_id,
            'project_role', OLD.project_role,
            'expertise', OLD.expertise,
            'availability', OLD.availability,
            'is_active', OLD.is_active,
            'joined_at', OLD.joined_at
        ),JSON_OBJECT(
            'project_id', NEW.project_id,
            'user_id', NEW.user_id,
            'project_role', NEW.project_role,
            'expertise', NEW.expertise,
            'availability', NEW.availability,
            'is_active', NEW.is_active,
            'joined_at', NEW.joined_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `project_report_settings`
--

CREATE TABLE `project_report_settings` (
  `project_id` varchar(36) NOT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `header_text` varchar(500) DEFAULT NULL,
  `footer_text` varchar(500) DEFAULT NULL,
  `accent_color` varchar(7) NOT NULL DEFAULT '#1f4e79',
  `company_name` varchar(255) DEFAULT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `project_roles`
--

CREATE TABLE `project_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `project_sboms`
--

CREATE TABLE `project_sboms` (
  `id` int(11) NOT NULL,
  `project_id` varchar(36) NOT NULL,
  `sbom_data` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `project_sboms`
--
DELIMITER $$
CREATE TRIGGER `audit_project_sboms_delete` AFTER DELETE ON `project_sboms` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.project_id,@audit_batch_id,'sbom',CAST(OLD.id AS CHAR),CAST(OLD.id AS CHAR),CONCAT('SBOM ',OLD.id),'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'sbom_data', OLD.sbom_data,
            'created_at', OLD.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_project_sboms_insert` AFTER INSERT ON `project_sboms` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'sbom',CAST(NEW.id AS CHAR),CAST(NEW.id AS CHAR),CONCAT('SBOM ',NEW.id),'CREATE',JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'sbom_data', NEW.sbom_data,
            'created_at', NEW.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_project_sboms_update` AFTER UPDATE ON `project_sboms` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'sbom',CAST(NEW.id AS CHAR),CAST(NEW.id AS CHAR),CONCAT('SBOM ',NEW.id),'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'sbom_data', OLD.sbom_data,
            'created_at', OLD.created_at
        ),JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'sbom_data', NEW.sbom_data,
            'created_at', NEW.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `project_tasks`
--

CREATE TABLE `project_tasks` (
  `id` int(11) NOT NULL,
  `project_id` varchar(36) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `wbs_code` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `assignee` varchar(100) DEFAULT NULL,
  `effort_mt` decimal(10,2) DEFAULT NULL,
  `performance_pct` int(11) DEFAULT 100,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `progress_pct` int(11) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `is_auto_progress` tinyint(1) DEFAULT 1,
  `linked_reqs` longtext DEFAULT NULL CHECK (json_valid(`linked_reqs`)),
  `category` varchar(100) DEFAULT 'Allgemein',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `project_tasks`
--
DELIMITER $$
CREATE TRIGGER `audit_project_tasks_delete` AFTER DELETE ON `project_tasks` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.project_id,@audit_batch_id,'task',CAST(OLD.id AS CHAR),OLD.wbs_code,OLD.title,'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'parent_id', OLD.parent_id,
            'wbs_code', OLD.wbs_code,
            'title', OLD.title,
            'assignee', OLD.assignee,
            'effort_mt', OLD.effort_mt,
            'performance_pct', OLD.performance_pct,
            'start_date', OLD.start_date,
            'end_date', OLD.end_date,
            'progress_pct', OLD.progress_pct,
            'is_auto_progress', OLD.is_auto_progress,
            'linked_reqs', OLD.linked_reqs,
            'category', OLD.category,
            'description', OLD.description
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_project_tasks_insert` AFTER INSERT ON `project_tasks` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'task',CAST(NEW.id AS CHAR),NEW.wbs_code,NEW.title,'CREATE',JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'parent_id', NEW.parent_id,
            'wbs_code', NEW.wbs_code,
            'title', NEW.title,
            'assignee', NEW.assignee,
            'effort_mt', NEW.effort_mt,
            'performance_pct', NEW.performance_pct,
            'start_date', NEW.start_date,
            'end_date', NEW.end_date,
            'progress_pct', NEW.progress_pct,
            'is_auto_progress', NEW.is_auto_progress,
            'linked_reqs', NEW.linked_reqs,
            'category', NEW.category,
            'description', NEW.description
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_project_tasks_update` AFTER UPDATE ON `project_tasks` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'task',CAST(NEW.id AS CHAR),NEW.wbs_code,NEW.title,'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'parent_id', OLD.parent_id,
            'wbs_code', OLD.wbs_code,
            'title', OLD.title,
            'assignee', OLD.assignee,
            'effort_mt', OLD.effort_mt,
            'performance_pct', OLD.performance_pct,
            'start_date', OLD.start_date,
            'end_date', OLD.end_date,
            'progress_pct', OLD.progress_pct,
            'is_auto_progress', OLD.is_auto_progress,
            'linked_reqs', OLD.linked_reqs,
            'category', OLD.category,
            'description', OLD.description
        ),JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'parent_id', NEW.parent_id,
            'wbs_code', NEW.wbs_code,
            'title', NEW.title,
            'assignee', NEW.assignee,
            'effort_mt', NEW.effort_mt,
            'performance_pct', NEW.performance_pct,
            'start_date', NEW.start_date,
            'end_date', NEW.end_date,
            'progress_pct', NEW.progress_pct,
            'is_auto_progress', NEW.is_auto_progress,
            'linked_reqs', NEW.linked_reqs,
            'category', NEW.category,
            'description', NEW.description
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `requirements`
--

CREATE TABLE `requirements` (
  `id` int(11) NOT NULL,
  `serial_number` int(11) DEFAULT NULL,
  `display_number` int(11) NOT NULL,
  `project_id` varchar(36) DEFAULT NULL,
  `req_key` varchar(20) NOT NULL,
  `source_reference` varchar(100) DEFAULT NULL,
  `source_document` varchar(255) DEFAULT NULL,
  `source_page` int(11) DEFAULT NULL,
  `external_key` varchar(100) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `rationale` text DEFAULT NULL,
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attributes`)),
  `parents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parents`)),
  `children` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`children`)),
  `status` enum('open','in_progress','implemented','tested') DEFAULT 'open',
  `source_contact` varchar(255) DEFAULT NULL,
  `effort` varchar(50) DEFAULT NULL,
  `acceptance_criteria` text DEFAULT NULL,
  `review_status` varchar(50) DEFAULT 'Neu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `requirements`
--
DELIMITER $$
CREATE TRIGGER `audit_requirements_delete` AFTER DELETE ON `requirements` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.project_id,@audit_batch_id,CASE WHEN OLD.type='AST' THEN 'asset' WHEN OLD.type='GOAL' THEN 'goal' ELSE 'requirement' END,CAST(OLD.id AS CHAR),OLD.req_key,OLD.title,'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'req_key', OLD.req_key,
            'type', OLD.type,
            'title', OLD.title,
            'description', OLD.description,
            'rationale', OLD.rationale,
            'attributes', OLD.attributes,
            'parents', OLD.parents,
            'children', OLD.children,
            'status', OLD.status,
            'source_contact', OLD.source_contact,
            'effort', OLD.effort,
            'acceptance_criteria', OLD.acceptance_criteria,
            'review_status', OLD.review_status
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_requirements_insert` AFTER INSERT ON `requirements` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,CASE WHEN NEW.type='AST' THEN 'asset' WHEN NEW.type='GOAL' THEN 'goal' ELSE 'requirement' END,CAST(NEW.id AS CHAR),NEW.req_key,NEW.title,'CREATE',JSON_OBJECT(
            'id', NEW.id,
            'req_key', NEW.req_key,
            'type', NEW.type,
            'title', NEW.title,
            'description', NEW.description,
            'rationale', NEW.rationale,
            'attributes', NEW.attributes,
            'parents', NEW.parents,
            'children', NEW.children,
            'status', NEW.status,
            'source_contact', NEW.source_contact,
            'effort', NEW.effort,
            'acceptance_criteria', NEW.acceptance_criteria,
            'review_status', NEW.review_status
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_requirements_update` AFTER UPDATE ON `requirements` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,CASE WHEN NEW.type='AST' THEN 'asset' WHEN NEW.type='GOAL' THEN 'goal' ELSE 'requirement' END,CAST(NEW.id AS CHAR),NEW.req_key,NEW.title,'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'req_key', OLD.req_key,
            'type', OLD.type,
            'title', OLD.title,
            'description', OLD.description,
            'rationale', OLD.rationale,
            'attributes', OLD.attributes,
            'parents', OLD.parents,
            'children', OLD.children,
            'status', OLD.status,
            'source_contact', OLD.source_contact,
            'effort', OLD.effort,
            'acceptance_criteria', OLD.acceptance_criteria,
            'review_status', OLD.review_status
        ),JSON_OBJECT(
            'id', NEW.id,
            'req_key', NEW.req_key,
            'type', NEW.type,
            'title', NEW.title,
            'description', NEW.description,
            'rationale', NEW.rationale,
            'attributes', NEW.attributes,
            'parents', NEW.parents,
            'children', NEW.children,
            'status', NEW.status,
            'source_contact', NEW.source_contact,
            'effort', NEW.effort,
            'acceptance_criteria', NEW.acceptance_criteria,
            'review_status', NEW.review_status
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `requirement_history`
--

CREATE TABLE `requirement_history` (
  `id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `req_key` varchar(50) NOT NULL,
  `project_id` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `rationale` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `parents` text DEFAULT NULL,
  `children` text DEFAULT NULL,
  `modified_by` int(11) NOT NULL,
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hostname` varchar(255) DEFAULT 'localhost',
  `action` text DEFAULT NULL,
  `attributes` longtext DEFAULT NULL CHECK (json_valid(`attributes`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `requirement_import_batches`
--

CREATE TABLE `requirement_import_batches` (
  `id` char(36) NOT NULL,
  `project_id` varchar(36) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `source_format` varchar(20) DEFAULT NULL,
  `extraction_mode` varchar(30) DEFAULT NULL,
  `file_type` varchar(20) NOT NULL,
  `profile_id` int(11) DEFAULT NULL,
  `import_mode` enum('skip','update') NOT NULL DEFAULT 'skip',
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `created_rows` int(11) NOT NULL DEFAULT 0,
  `updated_rows` int(11) NOT NULL DEFAULT 0,
  `skipped_rows` int(11) NOT NULL DEFAULT 0,
  `failed_rows` int(11) NOT NULL DEFAULT 0,
  `status` enum('preview','running','completed','completed_with_errors','failed') NOT NULL DEFAULT 'preview',
  `result_json` longtext DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `requirement_import_profiles`
--

CREATE TABLE `requirement_import_profiles` (
  `id` int(11) NOT NULL,
  `project_id` varchar(36) DEFAULT NULL,
  `profile_name` varchar(120) NOT NULL,
  `source_format` varchar(20) DEFAULT NULL,
  `extraction_mode` varchar(30) DEFAULT NULL,
  `configuration_json` longtext DEFAULT NULL,
  `file_type` enum('xlsx','xls','csv','pdf') NOT NULL,
  `sheet_name` varchar(150) DEFAULT NULL,
  `header_row` int(11) NOT NULL DEFAULT 1,
  `mapping_json` longtext NOT NULL,
  `pdf_config_json` longtext DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `requirement_relations`
--

CREATE TABLE `requirement_relations` (
  `parent_requirement_id` int(11) NOT NULL,
  `child_requirement_id` int(11) NOT NULL,
  `relation_type` varchar(30) NOT NULL DEFAULT 'fulfills',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `risk_issue_links`
--

CREATE TABLE `risk_issue_links` (
  `risk_id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `risk_requirement_links`
--

CREATE TABLE `risk_requirement_links` (
  `risk_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `link_group` enum('control','verification') NOT NULL DEFAULT 'control',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `risk_task_links`
--

CREATE TABLE `risk_task_links` (
  `risk_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `stakeholders`
--

CREATE TABLE `stakeholders` (
  `id` int(11) NOT NULL,
  `project_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `expertise` varchar(255) DEFAULT NULL,
  `availability` varchar(255) DEFAULT NULL,
  `influence` varchar(50) DEFAULT 'Low',
  `interest` varchar(50) DEFAULT 'Low',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `stakeholders`
--
DELIMITER $$
CREATE TRIGGER `audit_stakeholders_delete` AFTER DELETE ON `stakeholders` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.project_id,@audit_batch_id,'stakeholder',CAST(OLD.id AS CHAR),CAST(OLD.id AS CHAR),OLD.name,'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'name', OLD.name,
            'email', OLD.email,
            'phone', OLD.phone,
            'role', OLD.role,
            'position', OLD.position,
            'expertise', OLD.expertise,
            'availability', OLD.availability,
            'influence', OLD.influence,
            'interest', OLD.interest,
            'created_at', OLD.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_stakeholders_insert` AFTER INSERT ON `stakeholders` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'stakeholder',CAST(NEW.id AS CHAR),CAST(NEW.id AS CHAR),NEW.name,'CREATE',JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'name', NEW.name,
            'email', NEW.email,
            'phone', NEW.phone,
            'role', NEW.role,
            'position', NEW.position,
            'expertise', NEW.expertise,
            'availability', NEW.availability,
            'influence', NEW.influence,
            'interest', NEW.interest,
            'created_at', NEW.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_stakeholders_update` AFTER UPDATE ON `stakeholders` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'stakeholder',CAST(NEW.id AS CHAR),CAST(NEW.id AS CHAR),NEW.name,'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'name', OLD.name,
            'email', OLD.email,
            'phone', OLD.phone,
            'role', OLD.role,
            'position', OLD.position,
            'expertise', OLD.expertise,
            'availability', OLD.availability,
            'influence', OLD.influence,
            'interest', OLD.interest,
            'created_at', OLD.created_at
        ),JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'name', NEW.name,
            'email', NEW.email,
            'phone', NEW.phone,
            'role', NEW.role,
            'position', NEW.position,
            'expertise', NEW.expertise,
            'availability', NEW.availability,
            'influence', NEW.influence,
            'interest', NEW.interest,
            'created_at', NEW.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `task_templates`
--

CREATE TABLE `task_templates` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `default_effort` decimal(10,2) DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `api_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_stories`
--

CREATE TABLE `user_stories` (
  `id` int(11) NOT NULL,
  `project_id` varchar(50) NOT NULL,
  `us_key` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `us_role` varchar(255) DEFAULT NULL,
  `us_action` text DEFAULT NULL,
  `us_benefit` text DEFAULT NULL,
  `acceptance_criteria` text DEFAULT NULL,
  `story_points` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `user_stories`
--
DELIMITER $$
CREATE TRIGGER `audit_user_stories_delete` AFTER DELETE ON `user_stories` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.project_id,@audit_batch_id,'user_story',CAST(OLD.id AS CHAR),OLD.us_key,OLD.title,'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'us_key', OLD.us_key,
            'title', OLD.title,
            'us_role', OLD.us_role,
            'us_action', OLD.us_action,
            'us_benefit', OLD.us_benefit,
            'acceptance_criteria', OLD.acceptance_criteria,
            'story_points', OLD.story_points,
            'created_at', OLD.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_user_stories_insert` AFTER INSERT ON `user_stories` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'user_story',CAST(NEW.id AS CHAR),NEW.us_key,NEW.title,'CREATE',JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'us_key', NEW.us_key,
            'title', NEW.title,
            'us_role', NEW.us_role,
            'us_action', NEW.us_action,
            'us_benefit', NEW.us_benefit,
            'acceptance_criteria', NEW.acceptance_criteria,
            'story_points', NEW.story_points,
            'created_at', NEW.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_user_stories_update` AFTER UPDATE ON `user_stories` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'user_story',CAST(NEW.id AS CHAR),NEW.us_key,NEW.title,'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'us_key', OLD.us_key,
            'title', OLD.title,
            'us_role', OLD.us_role,
            'us_action', OLD.us_action,
            'us_benefit', OLD.us_benefit,
            'acceptance_criteria', OLD.acceptance_criteria,
            'story_points', OLD.story_points,
            'created_at', OLD.created_at
        ),JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'us_key', NEW.us_key,
            'title', NEW.title,
            'us_role', NEW.us_role,
            'us_action', NEW.us_action,
            'us_benefit', NEW.us_benefit,
            'acceptance_criteria', NEW.acceptance_criteria,
            'story_points', NEW.story_points,
            'created_at', NEW.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `use_cases`
--

CREATE TABLE `use_cases` (
  `id` int(11) NOT NULL,
  `project_id` varchar(50) NOT NULL,
  `uc_key` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `primary_actor` varchar(255) DEFAULT NULL,
  `preconditions` text DEFAULT NULL,
  `main_scenario` text DEFAULT NULL,
  `alt_scenario` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `use_cases`
--
DELIMITER $$
CREATE TRIGGER `audit_use_cases_delete` AFTER DELETE ON `use_cases` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (OLD.project_id,@audit_batch_id,'use_case',CAST(OLD.id AS CHAR),OLD.uc_key,OLD.title,'DELETE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'uc_key', OLD.uc_key,
            'title', OLD.title,
            'primary_actor', OLD.primary_actor,
            'preconditions', OLD.preconditions,
            'main_scenario', OLD.main_scenario,
            'alt_scenario', OLD.alt_scenario,
            'created_at', OLD.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_use_cases_insert` AFTER INSERT ON `use_cases` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'use_case',CAST(NEW.id AS CHAR),NEW.uc_key,NEW.title,'CREATE',JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'uc_key', NEW.uc_key,
            'title', NEW.title,
            'primary_actor', NEW.primary_actor,
            'preconditions', NEW.preconditions,
            'main_scenario', NEW.main_scenario,
            'alt_scenario', NEW.alt_scenario,
            'created_at', NEW.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_use_cases_update` AFTER UPDATE ON `use_cases` FOR EACH ROW BEGIN
    INSERT INTO audit_log (project_id,batch_id,entity_type,entity_id,entity_key,entity_title,action,old_data,new_data,actor_user_id,actor_name,source_type,source_name,hostname,request_id)
    VALUES (NEW.project_id,@audit_batch_id,'use_case',CAST(NEW.id AS CHAR),NEW.uc_key,NEW.title,'UPDATE',JSON_OBJECT(
            'id', OLD.id,
            'project_id', OLD.project_id,
            'uc_key', OLD.uc_key,
            'title', OLD.title,
            'primary_actor', OLD.primary_actor,
            'preconditions', OLD.preconditions,
            'main_scenario', OLD.main_scenario,
            'alt_scenario', OLD.alt_scenario,
            'created_at', OLD.created_at
        ),JSON_OBJECT(
            'id', NEW.id,
            'project_id', NEW.project_id,
            'uc_key', NEW.uc_key,
            'title', NEW.title,
            'primary_actor', NEW.primary_actor,
            'preconditions', NEW.preconditions,
            'main_scenario', NEW.main_scenario,
            'alt_scenario', NEW.alt_scenario,
            'created_at', NEW.created_at
        ),@audit_user_id,COALESCE(@audit_actor_name,CURRENT_USER()),COALESCE(@audit_source_type,'database'),@audit_source_name,COALESCE(@audit_hostname,@@hostname),@audit_request_id);
END
$$
DELIMITER ;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `acceptance_criteria_templates`
--
ALTER TABLE `acceptance_criteria_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_acceptance_criterion_hash` (`criterion_hash`),
  ADD KEY `idx_acceptance_type_active` (`requirement_type`,`is_active`),
  ADD KEY `idx_acceptance_category` (`category`),
  ADD KEY `idx_acceptance_usage` (`usage_count`);

--
-- Indizes für die Tabelle `attachment_links`
--
ALTER TABLE `attachment_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attachment_link` (`attachment_id`,`entity_type`,`entity_id`),
  ADD KEY `idx_attachment_entity` (`entity_type`,`entity_id`);

--
-- Indizes für die Tabelle `audit_batches`
--
ALTER TABLE `audit_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_batches_project` (`project_id`,`created_at`);

--
-- Indizes für die Tabelle `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_project_date` (`project_id`,`created_at`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`,`created_at`),
  ADD KEY `idx_audit_action` (`action`,`created_at`),
  ADD KEY `idx_audit_actor` (`actor_user_id`,`created_at`),
  ADD KEY `idx_audit_batch` (`batch_id`),
  ADD KEY `idx_audit_project_entity_date` (`project_id`,`entity_type`,`created_at`),
  ADD KEY `idx_audit_project_action_date` (`project_id`,`action`,`created_at`),
  ADD KEY `idx_audit_project_actor_date` (`project_id`,`actor_user_id`,`created_at`),
  ADD KEY `idx_audit_project_source_date` (`project_id`,`source_type`,`created_at`),
  ADD KEY `idx_audit_project_key_date` (`project_id`,`entity_key`,`created_at`);

--
-- Indizes für die Tabelle `evidences`
--
ALTER TABLE `evidences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requirement_id` (`requirement_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indizes für die Tabelle `iso14001_templates`
--
ALTER TABLE `iso14001_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_issues_project_key` (`project_id`,`issue_key`),
  ADD UNIQUE KEY `uq_issues_source_row` (`project_id`,`source_document`,`source_sheet`,`source_row`),
  ADD KEY `idx_issues_project_status` (`project_id`,`status`),
  ADD KEY `idx_issues_assignee` (`assignee_user_id`),
  ADD KEY `fk_issues_reporter` (`reporter_user_id`),
  ADD KEY `fk_issues_created_by` (`created_by`);

--
-- Indizes für die Tabelle `issue_comments`
--
ALTER TABLE `issue_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_issue_comments_issue` (`issue_id`,`created_at`),
  ADD KEY `fk_issue_comments_user` (`created_by`);

--
-- Indizes für die Tabelle `issue_history`
--
ALTER TABLE `issue_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_issue_history_issue` (`issue_id`,`modified_at`),
  ADD KEY `fk_issue_history_project` (`project_id`),
  ADD KEY `fk_issue_history_user` (`modified_by`);

--
-- Indizes für die Tabelle `issue_imports`
--
ALTER TABLE `issue_imports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_issue_import_file` (`project_id`,`file_hash`,`sheet_name`),
  ADD KEY `fk_issue_imports_user` (`imported_by`);

--
-- Indizes für die Tabelle `issue_requirements`
--
ALTER TABLE `issue_requirements`
  ADD PRIMARY KEY (`issue_id`,`requirement_id`,`relation_type`),
  ADD KEY `idx_issue_requirements_requirement` (`requirement_id`);

--
-- Indizes für die Tabelle `issue_tasks`
--
ALTER TABLE `issue_tasks`
  ADD PRIMARY KEY (`issue_id`,`task_id`,`relation_type`),
  ADD KEY `idx_issue_tasks_task` (`task_id`);

--
-- Indizes für die Tabelle `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indizes für die Tabelle `project_attachments`
--
ALTER TABLE `project_attachments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_attachment_key` (`project_id`,`attachment_key`),
  ADD KEY `idx_attachment_project` (`project_id`),
  ADD KEY `idx_attachment_created_by` (`created_by`);

--
-- Indizes für die Tabelle `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`project_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indizes für die Tabelle `project_report_settings`
--
ALTER TABLE `project_report_settings`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `fk_report_settings_user` (`updated_by`);

--
-- Indizes für die Tabelle `project_roles`
--
ALTER TABLE `project_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_project_roles_name` (`role_name`),
  ADD KEY `idx_project_roles_active` (`is_active`),
  ADD KEY `fk_project_roles_creator` (`created_by`);

--
-- Indizes für die Tabelle `project_sboms`
--
ALTER TABLE `project_sboms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indizes für die Tabelle `project_tasks`
--
ALTER TABLE `project_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_project_tasks_parent` (`parent_id`),
  ADD KEY `idx_project_tasks_completed_by` (`completed_by`);

--
-- Indizes für die Tabelle `requirements`
--
ALTER TABLE `requirements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_requirement_project_key` (`project_id`,`req_key`),
  ADD UNIQUE KEY `uq_requirements_project_display_number` (`project_id`,`display_number`);

--
-- Indizes für die Tabelle `requirement_history`
--
ALTER TABLE `requirement_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_requirement_history_project_date` (`project_id`,`modified_at`),
  ADD KEY `idx_requirement_history_key_date` (`req_key`,`modified_at`);

--
-- Indizes für die Tabelle `requirement_import_batches`
--
ALTER TABLE `requirement_import_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_req_import_batches_project` (`project_id`,`created_at`),
  ADD KEY `fk_req_import_batches_profile` (`profile_id`),
  ADD KEY `fk_req_import_batches_user` (`created_by`);

--
-- Indizes für die Tabelle `requirement_import_profiles`
--
ALTER TABLE `requirement_import_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_req_import_profiles_project` (`project_id`,`file_type`),
  ADD KEY `fk_req_import_profiles_user` (`created_by`);

--
-- Indizes für die Tabelle `requirement_relations`
--
ALTER TABLE `requirement_relations`
  ADD PRIMARY KEY (`parent_requirement_id`,`child_requirement_id`,`relation_type`),
  ADD KEY `idx_requirement_relations_child` (`child_requirement_id`),
  ADD KEY `fk_requirement_relations_user` (`created_by`);

--
-- Indizes für die Tabelle `risk_issue_links`
--
ALTER TABLE `risk_issue_links`
  ADD PRIMARY KEY (`risk_id`,`issue_id`),
  ADD KEY `idx_risk_issue_target` (`issue_id`,`risk_id`),
  ADD KEY `idx_risk_issue_creator` (`created_by`);

--
-- Indizes für die Tabelle `risk_requirement_links`
--
ALTER TABLE `risk_requirement_links`
  ADD PRIMARY KEY (`risk_id`,`requirement_id`,`link_group`),
  ADD KEY `idx_risk_req_target` (`requirement_id`,`risk_id`),
  ADD KEY `idx_risk_req_creator` (`created_by`);

--
-- Indizes für die Tabelle `risk_task_links`
--
ALTER TABLE `risk_task_links`
  ADD PRIMARY KEY (`risk_id`,`task_id`),
  ADD KEY `idx_risk_task_target` (`task_id`,`risk_id`),
  ADD KEY `idx_risk_task_creator` (`created_by`);

--
-- Indizes für die Tabelle `stakeholders`
--
ALTER TABLE `stakeholders`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `task_templates`
--
ALTER TABLE `task_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indizes für die Tabelle `user_stories`
--
ALTER TABLE `user_stories`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `use_cases`
--
ALTER TABLE `use_cases`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `acceptance_criteria_templates`
--
ALTER TABLE `acceptance_criteria_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `attachment_links`
--
ALTER TABLE `attachment_links`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `evidences`
--
ALTER TABLE `evidences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `iso14001_templates`
--
ALTER TABLE `iso14001_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `issue_comments`
--
ALTER TABLE `issue_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `issue_history`
--
ALTER TABLE `issue_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `issue_imports`
--
ALTER TABLE `issue_imports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `project_attachments`
--
ALTER TABLE `project_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `project_roles`
--
ALTER TABLE `project_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `project_sboms`
--
ALTER TABLE `project_sboms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `project_tasks`
--
ALTER TABLE `project_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `requirements`
--
ALTER TABLE `requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `requirement_history`
--
ALTER TABLE `requirement_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `requirement_import_profiles`
--
ALTER TABLE `requirement_import_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `stakeholders`
--
ALTER TABLE `stakeholders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `task_templates`
--
ALTER TABLE `task_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `user_stories`
--
ALTER TABLE `user_stories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `use_cases`
--
ALTER TABLE `use_cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `attachment_links`
--
ALTER TABLE `attachment_links`
  ADD CONSTRAINT `fk_attachment_link` FOREIGN KEY (`attachment_id`) REFERENCES `project_attachments` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `evidences`
--
ALTER TABLE `evidences`
  ADD CONSTRAINT `evidences_ibfk_1` FOREIGN KEY (`requirement_id`) REFERENCES `requirements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evidences_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evidences_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints der Tabelle `issues`
--
ALTER TABLE `issues`
  ADD CONSTRAINT `fk_issues_assignee` FOREIGN KEY (`assignee_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_issues_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_issues_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issues_reporter` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `issue_comments`
--
ALTER TABLE `issue_comments`
  ADD CONSTRAINT `fk_issue_comments_issue` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_comments_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `issue_history`
--
ALTER TABLE `issue_history`
  ADD CONSTRAINT `fk_issue_history_issue` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_history_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_history_user` FOREIGN KEY (`modified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `issue_imports`
--
ALTER TABLE `issue_imports`
  ADD CONSTRAINT `fk_issue_imports_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_imports_user` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `issue_requirements`
--
ALTER TABLE `issue_requirements`
  ADD CONSTRAINT `fk_issue_requirements_issue` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_requirements_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `requirements` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `issue_tasks`
--
ALTER TABLE `issue_tasks`
  ADD CONSTRAINT `fk_issue_tasks_issue` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_tasks_task` FOREIGN KEY (`task_id`) REFERENCES `project_tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints der Tabelle `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `project_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `project_report_settings`
--
ALTER TABLE `project_report_settings`
  ADD CONSTRAINT `fk_report_settings_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_report_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `project_roles`
--
ALTER TABLE `project_roles`
  ADD CONSTRAINT `fk_project_roles_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `project_sboms`
--
ALTER TABLE `project_sboms`
  ADD CONSTRAINT `project_sboms_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `project_tasks`
--
ALTER TABLE `project_tasks`
  ADD CONSTRAINT `project_tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `requirements`
--
ALTER TABLE `requirements`
  ADD CONSTRAINT `requirements_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `requirement_import_batches`
--
ALTER TABLE `requirement_import_batches`
  ADD CONSTRAINT `fk_req_import_batches_profile` FOREIGN KEY (`profile_id`) REFERENCES `requirement_import_profiles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_req_import_batches_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_req_import_batches_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `requirement_import_profiles`
--
ALTER TABLE `requirement_import_profiles`
  ADD CONSTRAINT `fk_req_import_profiles_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_req_import_profiles_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `requirement_relations`
--
ALTER TABLE `requirement_relations`
  ADD CONSTRAINT `fk_requirement_relations_child` FOREIGN KEY (`child_requirement_id`) REFERENCES `requirements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_requirement_relations_parent` FOREIGN KEY (`parent_requirement_id`) REFERENCES `requirements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_requirement_relations_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `risk_issue_links`
--
ALTER TABLE `risk_issue_links`
  ADD CONSTRAINT `fk_risk_issue_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_risk_issue_issue` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_risk_issue_risk` FOREIGN KEY (`risk_id`) REFERENCES `requirements` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `risk_requirement_links`
--
ALTER TABLE `risk_requirement_links`
  ADD CONSTRAINT `fk_risk_req_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_risk_req_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `requirements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_risk_req_risk` FOREIGN KEY (`risk_id`) REFERENCES `requirements` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `risk_task_links`
--
ALTER TABLE `risk_task_links`
  ADD CONSTRAINT `fk_risk_task_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_risk_task_risk` FOREIGN KEY (`risk_id`) REFERENCES `requirements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_risk_task_task` FOREIGN KEY (`task_id`) REFERENCES `project_tasks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
