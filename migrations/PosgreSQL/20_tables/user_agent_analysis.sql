/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V1.1.0 - 05/02/2026                  ##
##                                                ##
####################################################*/

--- 2026-01-03 : Initialisation du script
--- 2026-02-05 : Suppression de user_agent_event_id, ajout de user_id et visitor_id

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette db_user;

DROP TABLE IF EXISTS ma_moulinette.user_agent_analysis;

CREATE TABLE ma_moulinette.user_agent_analysis (
        id BIGSERIAL PRIMARY KEY,
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
        user_id BIGINT,
        visitor_id VARCHAR(36) NOT NULL,
        created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
