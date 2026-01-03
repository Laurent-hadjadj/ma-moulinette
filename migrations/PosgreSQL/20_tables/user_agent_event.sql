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

DROP TABLE IF EXISTS ma_moulinette.user_agent_event;

CREATE UNLOGGED TABLE ma_moulinette.user_agent_event (
            id BIGSERIAL PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            url VARCHAR(2048) NOT NULL,
            user_agent VARCHAR(1024) NOT NULL,
            session_id VARCHAR(128),
            user_id BIGINT,
            auth_state VARCHAR(20) NOT NULL,
            processing_status VARCHAR(20) NOT NULL,
            ip_hash VARCHAR(64),
            created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            processed_at TIMESTAMPTZ
);
