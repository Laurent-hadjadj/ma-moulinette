/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V1.0.0 - 03/01/2026                  ##
##                                                ##
####################################################*/

--- 2026-01-03 : Initialisation du script

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c assistant_ia db_user

DROP TABLE IF EXISTS ma_moulinette.user_agent_analysis;

CREATE TABLE ma_moulinette.user_agent_analysis (
        id BIGSERIAL PRIMARY KEY,
        user_agent_event_id BIGINT NOT NULL,
        device_type VARCHAR(30),
        os_name VARCHAR(50),
        os_version VARCHAR(50),
        browser_name VARCHAR(50),
        browser_version VARCHAR(50),
        is_bot BOOLEAN,
        detector_version VARCHAR(20) NOT NULL,
        event_type VARCHAR(50),
        url VARCHAR(2048),
        session_id VARCHAR(128),
        created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
