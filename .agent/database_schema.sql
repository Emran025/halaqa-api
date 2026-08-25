-- Quran Halaqa Live — MySQL 8.0 schema contract
-- Contract only: convert each logical table into an ordered Laravel migration.
-- All timestamps are stored in UTC. All identifiers are UUID strings unless noted otherwise.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE users (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    role VARCHAR(20) NOT NULL,
    username VARCHAR(60) NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    birth_date DATE NOT NULL,
    country VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    residence VARCHAR(200) NULL,
    avatar_path VARCHAR(500) NULL,
    phone VARCHAR(30) NOT NULL,
    phone_zone VARCHAR(8) NOT NULL,
    whatsapp_phone VARCHAR(30) NULL,
    whatsapp_zone VARCHAR(8) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    remember_token VARCHAR(100) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_role_status (role, status),
    CONSTRAINT chk_users_role CHECK (role IN ('teacher', 'student')),
    CONSTRAINT chk_users_gender CHECK (gender IN ('male', 'female')),
    CONSTRAINT chk_users_status CHECK (status IN ('active', 'inactive', 'suspended'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_profiles (
    user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    teacher_code VARCHAR(40) NOT NULL,
    qualification VARCHAR(250) NOT NULL,
    experience_years SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    bio TEXT NULL,
    available_time TIME NULL,
    max_halaqas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_teacher_profiles_code (teacher_code),
    CONSTRAINT fk_teacher_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_teacher_profiles_experience CHECK (experience_years <= 80)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_profiles (
    user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    memorization_level VARCHAR(120) NULL,
    review_level VARCHAR(120) NULL,
    memorized_juz_count DECIMAL(4,1) UNSIGNED NULL,
    memorized_surah_ids JSON NULL,
    last_completed_unit JSON NULL,
    previous_memorization_notes TEXT NULL,
    stop_reasons TEXT NULL,
    bio TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_student_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_student_profiles_juz CHECK (memorized_juz_count IS NULL OR memorized_juz_count <= 30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(250) NOT NULL,
    certificate_type VARCHAR(100) NOT NULL,
    certificate_type_other VARCHAR(150) NULL,
    riwayah VARCHAR(100) NULL,
    issuing_place VARCHAR(200) NULL,
    issuing_date DATE NULL,
    storage_disk VARCHAR(50) NULL,
    storage_path VARCHAR(500) NULL,
    mime_type VARCHAR(100) NULL,
    file_size_bytes BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_teacher_documents_teacher (teacher_id, deleted_at),
    CONSTRAINT fk_teacher_documents_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE halaqas (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    teacher_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(1000) NULL,
    gender VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    residence VARCHAR(200) NOT NULL,
    avatar_path VARCHAR(500) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    max_students SMALLINT UNSIGNED NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_halaqas_teacher_status (teacher_id, status),
    KEY idx_halaqas_public_filter (status, gender, country),
    CONSTRAINT fk_halaqas_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_halaqas_gender CHECK (gender IN ('male', 'female')),
    CONSTRAINT chk_halaqas_status CHECK (status IN ('active', 'inactive'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE halaqa_memberships (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    halaqa_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    student_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    joined_at DATETIME NOT NULL,
    left_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    active_student_key TINYINT GENERATED ALWAYS AS (IF(status = 'active', 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_active_student_membership (student_id, active_student_key),
    KEY idx_memberships_halaqa_status (halaqa_id, status),
    KEY idx_memberships_student_history (student_id, joined_at),
    CONSTRAINT fk_memberships_halaqa FOREIGN KEY (halaqa_id) REFERENCES halaqas(id) ON DELETE RESTRICT,
    CONSTRAINT fk_memberships_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_memberships_status CHECK (status IN ('active', 'inactive', 'removed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registration_requests (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    student_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    teacher_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    teacher_code_snapshot VARCHAR(40) NULL,
    requested_halaqa_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    routing_mode VARCHAR(30) NOT NULL,
    state VARCHAR(30) NOT NULL DEFAULT 'pending',
    public_message VARCHAR(1000) NULL,
    decision_note VARCHAR(2000) NULL,
    decided_by_teacher_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    submitted_at DATETIME NOT NULL,
    decided_at DATETIME NULL,
    accepted_at DATETIME NULL,
    withdrawn_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    open_student_key TINYINT GENERATED ALWAYS AS (IF(state IN ('pending', 'completion_requested'), 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registration_requests_open_student (student_id, open_student_key),
    KEY idx_registration_requests_teacher_state (teacher_id, state, submitted_at),
    KEY idx_registration_requests_open (routing_mode, state, submitted_at),
    KEY idx_registration_requests_student_state (student_id, state),
    CONSTRAINT fk_registration_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_registration_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_registration_halaqa FOREIGN KEY (requested_halaqa_id) REFERENCES halaqas(id) ON DELETE SET NULL,
    CONSTRAINT fk_registration_decider FOREIGN KEY (decided_by_teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_registration_routing CHECK (routing_mode IN ('specific_teacher', 'all_available_teachers')),
    CONSTRAINT chk_registration_state CHECK (state IN ('pending', 'completion_requested', 'accepted', 'rejected', 'withdrawn', 'cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registration_request_profiles (
    registration_request_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    gender VARCHAR(20) NOT NULL,
    birth_date DATE NOT NULL,
    country VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    residence VARCHAR(200) NULL,
    phone VARCHAR(30) NOT NULL,
    phone_zone VARCHAR(8) NOT NULL,
    whatsapp_phone VARCHAR(30) NULL,
    whatsapp_zone VARCHAR(8) NULL,
    memorization_level VARCHAR(120) NULL,
    review_level VARCHAR(120) NULL,
    memorized_juz_count DECIMAL(4,1) UNSIGNED NULL,
    memorized_surah_ids JSON NULL,
    last_completed_unit JSON NULL,
    previous_memorization_notes TEXT NULL,
    profile_bio TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (registration_request_id),
    CONSTRAINT fk_registration_profile_request FOREIGN KEY (registration_request_id) REFERENCES registration_requests(id) ON DELETE CASCADE,
    CONSTRAINT chk_registration_profile_gender CHECK (gender IN ('male', 'female')),
    CONSTRAINT chk_registration_profile_juz CHECK (memorized_juz_count IS NULL OR memorized_juz_count <= 30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_availability_profiles (
    student_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    preferred_session_duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (student_id),
    CONSTRAINT fk_availability_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_availability_duration CHECK (preferred_session_duration_minutes BETWEEN 10 AND 180)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_availability_slots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    available_from TIME NOT NULL,
    available_to TIME NOT NULL,
    is_preferred BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_availability_slot (student_id, day_of_week, available_from, available_to),
    KEY idx_availability_day (student_id, day_of_week),
    CONSTRAINT fk_availability_slots_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_availability_day CHECK (day_of_week BETWEEN 0 AND 6),
    CONSTRAINT chk_availability_range CHECK (available_from < available_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tracking_types (
    id TINYINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    label_ar VARCHAR(80) NOT NULL,
    label_en VARCHAR(80) NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tracking_types_code (code),
    CONSTRAINT chk_tracking_type_code CHECK (code IN ('memorization', 'review', 'recitation'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tracking_types (id, code, label_ar, label_en, sort_order) VALUES
(1, 'memorization', 'حفظ', 'Memorization', 1),
(2, 'review', 'مراجعة', 'Review', 2),
(3, 'recitation', 'تلاوة', 'Recitation', 3);

CREATE TABLE tracking_units (
    id TINYINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    label_ar VARCHAR(80) NOT NULL,
    label_en VARCHAR(80) NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tracking_units_code (code),
    CONSTRAINT chk_tracking_unit_code CHECK (code IN ('juz', 'hizb', 'halfHizb', 'quarterHizb', 'page'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tracking_units (id, code, label_ar, label_en, sort_order) VALUES
(1, 'juz', 'جزء', 'Juz', 1),
(2, 'hizb', 'حزب', 'Hizb', 2),
(3, 'halfHizb', 'نصف حزب', 'Half Hizb', 3),
(4, 'quarterHizb', 'ربع حزب', 'Quarter Hizb', 4),
(5, 'page', 'صفحة', 'Page', 5);

CREATE TABLE mistake_types (
    id TINYINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    label_ar VARCHAR(100) NOT NULL,
    label_en VARCHAR(100) NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mistake_types_code (code),
    CONSTRAINT chk_mistake_type_code CHECK (code IN ('none', 'memory', 'grammar', 'pronunciation', 'timing'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO mistake_types (id, code, label_ar, label_en, sort_order) VALUES
(0, 'none', 'غير مصنف', 'Unclassified', 0),
(1, 'memory', 'نسيان', 'Memory', 1),
(2, 'grammar', 'نحوي', 'Grammar', 2),
(3, 'pronunciation', 'مخارج حروف', 'Pronunciation', 3),
(4, 'timing', 'وقف وابتداء', 'Stopping and Starting', 4);

CREATE TABLE quran_editions (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(60) NOT NULL,
    name_ar VARCHAR(150) NOT NULL,
    script_name VARCHAR(100) NOT NULL,
    version VARCHAR(50) NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quran_editions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quran_surahs (
    id SMALLINT UNSIGNED NOT NULL,
    edition_id SMALLINT UNSIGNED NOT NULL,
    name_ar VARCHAR(150) NOT NULL,
    name_en VARCHAR(150) NOT NULL,
    name_en_translation VARCHAR(200) NOT NULL,
    number_of_ayahs SMALLINT UNSIGNED NOT NULL,
    first_page_starts_at SMALLINT UNSIGNED NOT NULL,
    revelation_type VARCHAR(20) NOT NULL,
    PRIMARY KEY (id, edition_id),
    UNIQUE KEY uq_quran_surah_number_edition (edition_id, id),
    KEY idx_quran_surahs_edition (edition_id),
    CONSTRAINT fk_quran_surahs_edition FOREIGN KEY (edition_id) REFERENCES quran_editions(id) ON DELETE RESTRICT,
    CONSTRAINT chk_quran_surah_number CHECK (id BETWEEN 1 AND 114),
    CONSTRAINT chk_quran_revelation_type CHECK (revelation_type IN ('Meccan', 'Medinan'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quran_pages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    edition_id SMALLINT UNSIGNED NOT NULL,
    page_number SMALLINT UNSIGNED NOT NULL,
    page_text LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quran_page_edition_number (edition_id, page_number),
    CONSTRAINT fk_quran_pages_edition FOREIGN KEY (edition_id) REFERENCES quran_editions(id) ON DELETE RESTRICT,
    CONSTRAINT chk_quran_page_number CHECK (page_number BETWEEN 1 AND 604)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quran_ayahs (
    id INT UNSIGNED NOT NULL,
    edition_id SMALLINT UNSIGNED NOT NULL,
    surah_id SMALLINT UNSIGNED NOT NULL,
    number_in_surah SMALLINT UNSIGNED NOT NULL,
    text_uthmani TEXT NOT NULL,
    text_emlaey TEXT NOT NULL,
    page_number SMALLINT UNSIGNED NOT NULL,
    juz_number TINYINT UNSIGNED NOT NULL,
    has_sajda BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (id, edition_id),
    UNIQUE KEY uq_quran_ayah_surah_position (edition_id, surah_id, number_in_surah),
    KEY idx_quran_ayah_page (edition_id, page_number),
    KEY idx_quran_ayah_juz (edition_id, juz_number),
    CONSTRAINT fk_quran_ayah_edition FOREIGN KEY (edition_id) REFERENCES quran_editions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_quran_ayah_surah FOREIGN KEY (surah_id, edition_id) REFERENCES quran_surahs(id, edition_id) ON DELETE RESTRICT,
    CONSTRAINT chk_quran_ayah_number CHECK (id BETWEEN 1 AND 6236),
    CONSTRAINT chk_quran_ayah_page CHECK (page_number BETWEEN 1 AND 604),
    CONSTRAINT chk_quran_ayah_juz CHECK (juz_number BETWEEN 1 AND 30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quran_ayah_words (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ayah_id INT UNSIGNED NOT NULL,
    edition_id SMALLINT UNSIGNED NOT NULL,
    word_index SMALLINT UNSIGNED NOT NULL,
    text_uthmani VARCHAR(255) NOT NULL,
    text_emlaey VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quran_word_position (edition_id, ayah_id, word_index),
    CONSTRAINT fk_quran_words_ayah FOREIGN KEY (ayah_id, edition_id) REFERENCES quran_ayahs(id, edition_id) ON DELETE CASCADE,
    CONSTRAINT chk_quran_word_index CHECK (word_index >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quran_range_units (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    edition_id SMALLINT UNSIGNED NOT NULL,
    unit_type_id TINYINT UNSIGNED NOT NULL,
    unit_index SMALLINT UNSIGNED NOT NULL,
    from_surah_id SMALLINT UNSIGNED NOT NULL,
    from_ayah_id INT UNSIGNED NOT NULL,
    from_page SMALLINT UNSIGNED NOT NULL,
    to_surah_id SMALLINT UNSIGNED NOT NULL,
    to_ayah_id INT UNSIGNED NOT NULL,
    to_page SMALLINT UNSIGNED NOT NULL,
    gap DECIMAL(8,4) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quran_range_unit (edition_id, unit_type_id, unit_index),
    KEY idx_quran_range_start (edition_id, from_page),
    CONSTRAINT fk_quran_range_edition FOREIGN KEY (edition_id) REFERENCES quran_editions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_quran_range_unit_type FOREIGN KEY (unit_type_id) REFERENCES tracking_units(id) ON DELETE RESTRICT,
    CONSTRAINT fk_quran_range_from_surah FOREIGN KEY (from_surah_id, edition_id) REFERENCES quran_surahs(id, edition_id) ON DELETE RESTRICT,
    CONSTRAINT fk_quran_range_to_surah FOREIGN KEY (to_surah_id, edition_id) REFERENCES quran_surahs(id, edition_id) ON DELETE RESTRICT,
    CONSTRAINT fk_quran_range_from_ayah FOREIGN KEY (from_ayah_id, edition_id) REFERENCES quran_ayahs(id, edition_id) ON DELETE RESTRICT,
    CONSTRAINT fk_quran_range_to_ayah FOREIGN KEY (to_ayah_id, edition_id) REFERENCES quran_ayahs(id, edition_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE follow_up_plans (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    student_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_by_user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    source_registration_request_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    frequency VARCHAR(30) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    starts_on DATE NULL,
    ends_on DATE NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    approved_by_user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_follow_up_plans_student_status (student_id, status),
    KEY idx_follow_up_plans_dates (status, starts_on, ends_on),
    CONSTRAINT fk_follow_up_plan_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_follow_up_plan_creator FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_follow_up_plan_registration FOREIGN KEY (source_registration_request_id) REFERENCES registration_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_follow_up_plan_approver FOREIGN KEY (approved_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_follow_up_frequency CHECK (frequency IN ('daily', 'onceAWeek', 'twiceAWeek', 'thriceAWeek')),
    CONSTRAINT chk_follow_up_status CHECK (status IN ('draft', 'proposed', 'active', 'paused', 'archived')),
    CONSTRAINT chk_follow_up_dates CHECK (ends_on IS NULL OR starts_on IS NULL OR ends_on >= starts_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE follow_up_plan_details (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    plan_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    tracking_type_id TINYINT UNSIGNED NOT NULL,
    tracking_unit_id TINYINT UNSIGNED NOT NULL,
    amount DECIMAL(8,2) UNSIGNED NOT NULL,
    notes VARCHAR(500) NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_plan_details_plan_order (plan_id, sort_order),
    CONSTRAINT fk_plan_details_plan FOREIGN KEY (plan_id) REFERENCES follow_up_plans(id) ON DELETE CASCADE,
    CONSTRAINT fk_plan_details_type FOREIGN KEY (tracking_type_id) REFERENCES tracking_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_plan_details_unit FOREIGN KEY (tracking_unit_id) REFERENCES tracking_units(id) ON DELETE RESTRICT,
    CONSTRAINT chk_plan_details_amount CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE follow_up_items (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    plan_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    plan_detail_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    student_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    halaqa_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    scheduled_for DATETIME NOT NULL,
    timezone VARCHAR(64) NOT NULL,
    state VARCHAR(20) NOT NULL DEFAULT 'upcoming',
    completed_at DATETIME NULL,
    skipped_at DATETIME NULL,
    skip_reason VARCHAR(500) NULL,
    rescheduled_from_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    notification_sent_at DATETIME NULL,
    last_client_operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    last_operation_by_user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    last_operation_type VARCHAR(20) NULL,
    reschedule_reason VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_follow_up_items_student_date (student_id, scheduled_for, state),
    KEY idx_follow_up_items_teacher_queue (halaqa_id, scheduled_for, state),
    KEY idx_follow_up_items_plan (plan_id, scheduled_for),
    UNIQUE KEY uq_follow_up_items_last_operation (last_client_operation_id),
    CONSTRAINT fk_follow_up_item_plan FOREIGN KEY (plan_id) REFERENCES follow_up_plans(id) ON DELETE RESTRICT,
    CONSTRAINT fk_follow_up_item_detail FOREIGN KEY (plan_detail_id) REFERENCES follow_up_plan_details(id) ON DELETE RESTRICT,
    CONSTRAINT fk_follow_up_item_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_follow_up_item_halaqa FOREIGN KEY (halaqa_id) REFERENCES halaqas(id) ON DELETE SET NULL,
    CONSTRAINT fk_follow_up_item_rescheduled FOREIGN KEY (rescheduled_from_id) REFERENCES follow_up_items(id) ON DELETE SET NULL,
    CONSTRAINT fk_follow_up_item_operation_user FOREIGN KEY (last_operation_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_follow_up_item_state CHECK (state IN ('upcoming', 'due', 'in_progress', 'completed', 'skipped', 'overdue'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE live_sessions (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    halaqa_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    teacher_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    student_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    follow_up_item_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    task_type_id TINYINT UNSIGNED NOT NULL,
    state VARCHAR(40) NOT NULL DEFAULT 'requested',
    scheduled_at DATETIME NULL,
    requested_at DATETIME NOT NULL,
    accepted_at DATETIME NULL,
    connected_at DATETIME NULL,
    ended_at DATETIME NULL,
    end_reason VARCHAR(500) NULL,
    direct_p2p_only BOOLEAN NOT NULL DEFAULT TRUE,
    client_operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    last_client_operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    last_operation_by_user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    last_operation_type VARCHAR(40) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    active_student_key TINYINT GENERATED ALWAYS AS (IF(state IN ('requested', 'accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected'), 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_live_session_client_operation (client_operation_id),
    UNIQUE KEY uq_live_sessions_last_operation (last_client_operation_id),
    UNIQUE KEY uq_live_session_active_student (student_id, active_student_key),
    KEY idx_live_sessions_teacher_state (teacher_id, state, scheduled_at),
    KEY idx_live_sessions_student_state (student_id, state, scheduled_at),
    KEY idx_live_sessions_halaqa_date (halaqa_id, scheduled_at),
    CONSTRAINT fk_live_session_halaqa FOREIGN KEY (halaqa_id) REFERENCES halaqas(id) ON DELETE RESTRICT,
    CONSTRAINT fk_live_session_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_live_session_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_live_session_follow_up FOREIGN KEY (follow_up_item_id) REFERENCES follow_up_items(id) ON DELETE SET NULL,
    CONSTRAINT fk_live_session_task_type FOREIGN KEY (task_type_id) REFERENCES tracking_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_live_session_last_operation_user FOREIGN KEY (last_operation_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_live_session_state CHECK (state IN ('requested', 'accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected', 'direct_connection_unavailable', 'ended', 'cancelled', 'rejected')),
    CONSTRAINT chk_live_session_p2p CHECK (direct_p2p_only = TRUE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE realtime_outbox_messages (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    session_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    recipient_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    dedupe_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    payload JSON NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_attempted_at DATETIME NULL,
    delivered_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_realtime_outbox_dedupe_key (dedupe_key),
    KEY idx_realtime_outbox_pending (delivered_at, created_at),
    KEY idx_realtime_outbox_session_recipient (session_id, recipient_id, created_at),
    CONSTRAINT fk_realtime_outbox_session FOREIGN KEY (session_id) REFERENCES live_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_realtime_outbox_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_realtime_outbox_event_type CHECK (event_type IN ('session.requested', 'session.accepted', 'session.rejected', 'session.state_changed', 'session.ended', 'report.updated', 'realtime.direct_connection_unavailable'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE session_mushaf_states (
    session_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    edition_id SMALLINT UNSIGNED NOT NULL,
    page_number SMALLINT UNSIGNED NOT NULL,
    surah_id SMALLINT UNSIGNED NULL,
    ayah_id INT UNSIGNED NULL,
    range_from_page SMALLINT UNSIGNED NULL,
    range_from_ayah_id INT UNSIGNED NULL,
    range_to_page SMALLINT UNSIGNED NULL,
    range_to_ayah_id INT UNSIGNED NULL,
    updated_by_user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    version BIGINT UNSIGNED NOT NULL DEFAULT 1,
    last_client_operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (session_id),
    KEY idx_session_mushaf_edition_page (edition_id, page_number),
    KEY idx_session_mushaf_updated_by (updated_by_user_id, updated_at),
    KEY idx_session_mushaf_operation (last_client_operation_id),
    CONSTRAINT fk_session_mushaf_session FOREIGN KEY (session_id) REFERENCES live_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_session_mushaf_edition FOREIGN KEY (edition_id) REFERENCES quran_editions(id) ON DELETE RESTRICT,
    CONSTRAINT fk_session_mushaf_surah FOREIGN KEY (surah_id, edition_id) REFERENCES quran_surahs(id, edition_id) ON DELETE RESTRICT,
    CONSTRAINT fk_session_mushaf_ayah FOREIGN KEY (ayah_id, edition_id) REFERENCES quran_ayahs(id, edition_id) ON DELETE RESTRICT,
    CONSTRAINT fk_session_mushaf_range_from_ayah FOREIGN KEY (range_from_ayah_id, edition_id) REFERENCES quran_ayahs(id, edition_id) ON DELETE RESTRICT,
    CONSTRAINT fk_session_mushaf_range_to_ayah FOREIGN KEY (range_to_ayah_id, edition_id) REFERENCES quran_ayahs(id, edition_id) ON DELETE RESTRICT,
    CONSTRAINT fk_session_mushaf_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_session_mushaf_page CHECK (page_number BETWEEN 1 AND 604),
    CONSTRAINT chk_session_mushaf_range_pages CHECK (
        (range_from_page IS NULL AND range_to_page IS NULL)
        OR (range_from_page BETWEEN 1 AND 604 AND range_to_page BETWEEN 1 AND 604 AND range_from_page <= range_to_page)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE session_tasks (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    session_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    client_operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    tracking_type_id TINYINT UNSIGNED NOT NULL,
    sequence_no SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    planned_from_unit_id BIGINT UNSIGNED NULL,
    planned_to_unit_id BIGINT UNSIGNED NULL,
    start_page SMALLINT UNSIGNED NULL,
    start_ayah_id INT UNSIGNED NULL,
    end_page SMALLINT UNSIGNED NULL,
    end_ayah_id INT UNSIGNED NULL,
    current_page SMALLINT UNSIGNED NULL,
    current_ayah_id INT UNSIGNED NULL,
    last_draft_operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    planned_amount DECIMAL(8,2) UNSIGNED NULL,
    actual_amount DECIMAL(8,2) UNSIGNED NULL,
    state VARCHAR(20) NOT NULL DEFAULT 'draft',
    comment TEXT NULL,
    score TINYINT UNSIGNED NULL,
    gap DECIMAL(8,4) NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_session_task_client_operation (client_operation_id),
    UNIQUE KEY uq_session_task_sequence (session_id, sequence_no),
    KEY idx_session_tasks_state (session_id, state),
    KEY idx_session_tasks_last_draft_operation (last_draft_operation_id),
    CONSTRAINT fk_session_task_session FOREIGN KEY (session_id) REFERENCES live_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_session_task_type FOREIGN KEY (tracking_type_id) REFERENCES tracking_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_session_task_from_unit FOREIGN KEY (planned_from_unit_id) REFERENCES quran_range_units(id) ON DELETE SET NULL,
    CONSTRAINT fk_session_task_to_unit FOREIGN KEY (planned_to_unit_id) REFERENCES quran_range_units(id) ON DELETE SET NULL,
    CONSTRAINT chk_session_task_state CHECK (state IN ('draft', 'in_progress', 'completed', 'skipped', 'cancelled')),
    CONSTRAINT chk_session_task_score CHECK (score IS NULL OR score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE daily_trackings (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    membership_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    student_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    date DATE NOT NULL,
    attendance_type VARCHAR(20) NOT NULL,
    note TEXT NULL,
    behavior_note TINYINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_daily_tracking_student_date (student_id, date),
    KEY idx_daily_tracking_membership_date (membership_id, date),
    CONSTRAINT fk_daily_tracking_membership FOREIGN KEY (membership_id) REFERENCES halaqa_memberships(id) ON DELETE RESTRICT,
    CONSTRAINT fk_daily_tracking_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_daily_tracking_attendance CHECK (attendance_type IN ('present', 'absent', 'excused', 'late')),
    CONSTRAINT chk_daily_tracking_behavior CHECK (behavior_note IS NULL OR behavior_note BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tracking_details (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    tracking_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    session_task_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    tracking_type_id TINYINT UNSIGNED NOT NULL,
    from_unit_id BIGINT UNSIGNED NULL,
    to_unit_id BIGINT UNSIGNED NULL,
    actual_amount DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
    state VARCHAR(20) NOT NULL DEFAULT 'draft',
    comment TEXT NULL,
    score TINYINT UNSIGNED NULL,
    gap DECIMAL(8,4) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tracking_details_uuid (uuid),
    KEY idx_tracking_details_tracking (tracking_id),
    KEY idx_tracking_details_task (session_task_id),
    CONSTRAINT fk_tracking_detail_tracking FOREIGN KEY (tracking_id) REFERENCES daily_trackings(id) ON DELETE CASCADE,
    CONSTRAINT fk_tracking_detail_task FOREIGN KEY (session_task_id) REFERENCES session_tasks(id) ON DELETE SET NULL,
    CONSTRAINT fk_tracking_detail_type FOREIGN KEY (tracking_type_id) REFERENCES tracking_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_tracking_detail_from_unit FOREIGN KEY (from_unit_id) REFERENCES quran_range_units(id) ON DELETE SET NULL,
    CONSTRAINT fk_tracking_detail_to_unit FOREIGN KEY (to_unit_id) REFERENCES quran_range_units(id) ON DELETE SET NULL,
    CONSTRAINT chk_tracking_detail_state CHECK (state IN ('draft', 'in_progress', 'completed', 'cancelled')),
    CONSTRAINT chk_tracking_detail_score CHECK (score IS NULL OR score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mistakes (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    tracking_detail_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    ayah_id INT UNSIGNED NOT NULL,
    edition_id SMALLINT UNSIGNED NOT NULL,
    word_index SMALLINT UNSIGNED NOT NULL,
    mistake_type_id TINYINT UNSIGNED NOT NULL,
    source_role VARCHAR(20) NOT NULL,
    note VARCHAR(2000) NULL,
    created_by_user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    client_operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    active_mistake_key TINYINT GENERATED ALWAYS AS (IF(deleted_at IS NULL, 1, NULL)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mistake_client_operation (client_operation_id),
    UNIQUE KEY uq_mistake_position_type (tracking_detail_id, ayah_id, word_index, mistake_type_id, active_mistake_key),
    KEY idx_mistakes_ayah (edition_id, ayah_id, word_index),
    KEY idx_mistakes_type (mistake_type_id, created_at),
    KEY idx_mistakes_creator (created_by_user_id, created_at),
    CONSTRAINT fk_mistake_tracking_detail FOREIGN KEY (tracking_detail_id) REFERENCES tracking_details(uuid) ON DELETE CASCADE,
    CONSTRAINT fk_mistake_ayah FOREIGN KEY (ayah_id, edition_id) REFERENCES quran_ayahs(id, edition_id) ON DELETE RESTRICT,
    CONSTRAINT fk_mistake_type FOREIGN KEY (mistake_type_id) REFERENCES mistake_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_mistake_creator FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_mistake_source_role CHECK (source_role IN ('teacher', 'student')),
    CONSTRAINT chk_mistake_word_index CHECK (word_index >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE task_notes (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    session_task_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    author_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    client_operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    note TEXT NOT NULL,
    ayah_id INT UNSIGNED NULL,
    edition_id SMALLINT UNSIGNED NULL,
    word_index SMALLINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_task_note_client_operation (client_operation_id),
    KEY idx_task_notes_task (session_task_id, created_at),
    CONSTRAINT fk_task_notes_task FOREIGN KEY (session_task_id) REFERENCES session_tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_task_notes_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_task_notes_ayah FOREIGN KEY (ayah_id, edition_id) REFERENCES quran_ayahs(id, edition_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE task_evaluations (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    session_task_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    evaluator_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    evaluator_role VARCHAR(20) NOT NULL,
    score TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_task_evaluation_evaluator (session_task_id, evaluator_id),
    KEY idx_task_evaluations_task (session_task_id),
    CONSTRAINT fk_task_evaluations_task FOREIGN KEY (session_task_id) REFERENCES session_tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_task_evaluations_evaluator FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_task_evaluation_role CHECK (evaluator_role IN ('teacher', 'student')),
    CONSTRAINT chk_task_evaluation_score CHECK (score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE session_reports (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    session_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    state VARCHAR(30) NOT NULL DEFAULT 'draft',
    summary TEXT NULL,
    duration_seconds INT UNSIGNED NULL,
    total_tasks SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    total_mistakes INT UNSIGNED NOT NULL DEFAULT 0,
    mistake_counts JSON NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    teacher_approved_by CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    teacher_approved_at DATETIME NULL,
    teacher_approval_note TEXT NULL,
    student_acknowledged_at DATETIME NULL,
    student_acknowledgment_note TEXT NULL,
    reopened_by CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    reopened_at DATETIME NULL,
    reopen_reason VARCHAR(1000) NULL,
    last_client_operation_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    last_operation_by_user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    last_operation_type VARCHAR(40) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_session_reports_session (session_id),
    KEY idx_session_reports_state (state, updated_at),
    UNIQUE KEY uq_session_reports_last_operation (last_client_operation_id),
    CONSTRAINT fk_session_report_session FOREIGN KEY (session_id) REFERENCES live_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_session_report_teacher FOREIGN KEY (teacher_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_session_report_reopener FOREIGN KEY (reopened_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_session_report_operation_user FOREIGN KEY (last_operation_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_session_report_state CHECK (state IN ('draft', 'pending_student_acknowledgment', 'completed', 'reopened'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    type VARCHAR(80) NOT NULL,
    title VARCHAR(250) NOT NULL,
    body TEXT NOT NULL,
    payload JSON NOT NULL,
    dedupe_key VARCHAR(180) NOT NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_notifications_dedupe_key (dedupe_key),
    KEY idx_notifications_user_read_date (user_id, read_at, created_at),
    CONSTRAINT chk_notifications_type CHECK (type IN ('registration_request', 'session_scheduled', 'session_started', 'session_ended', 'report_ready', 'follow_up_due', 'reminder', 'system')),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE idempotency_keys (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    idempotency_key VARCHAR(120) NOT NULL,
    method VARCHAR(10) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    request_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    response_status SMALLINT UNSIGNED NULL,
    response_body JSON NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_idempotency_user_key (user_id, idempotency_key),
    KEY idx_idempotency_expiry (expires_at),
    CONSTRAINT fk_idempotency_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    name VARCHAR(255) NOT NULL,
    token CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    abilities TEXT NULL,
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_personal_access_tokens_token (token),
    KEY idx_personal_access_tokens_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME NULL,
    PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    event_type VARCHAR(100) NOT NULL,
    subject_type VARCHAR(100) NOT NULL,
    subject_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    request_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL,
    metadata JSON NULL,
    occurred_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_audit_subject (subject_type, subject_id, occurred_at),
    KEY idx_audit_actor_date (actor_id, occurred_at),
    KEY idx_audit_request (request_id),
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Laravel infrastructure tables used when QUEUE_CONNECTION=database.
CREATE TABLE jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_jobs_queue_reserved_available (queue, reserved_at, available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_failed_jobs_uuid (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Registration-time availability snapshot, preserved independently of the current profile.
CREATE TABLE registration_request_availability (
    registration_request_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    preferred_session_duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (registration_request_id),
    CONSTRAINT fk_registration_availability_request FOREIGN KEY (registration_request_id) REFERENCES registration_requests(id) ON DELETE CASCADE,
    CONSTRAINT chk_registration_availability_duration CHECK (preferred_session_duration_minutes BETWEEN 10 AND 180)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registration_request_availability_slots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    registration_request_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    available_from TIME NOT NULL,
    available_to TIME NOT NULL,
    is_preferred BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registration_availability_slot (registration_request_id, day_of_week, available_from, available_to),
    CONSTRAINT fk_registration_availability_slot_request FOREIGN KEY (registration_request_id) REFERENCES registration_requests(id) ON DELETE CASCADE,
    CONSTRAINT chk_registration_availability_day CHECK (day_of_week BETWEEN 0 AND 6),
    CONSTRAINT chk_registration_availability_range CHECK (available_from < available_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
