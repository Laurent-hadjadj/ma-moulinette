/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V1.0.0 - 09/06/2026                  ##
##                                                ##
####################################################*/

--- 2026-06-09 : Intégration table user_agent_event dans le schéma ma_moulinette

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette;

DROP TABLE IF EXISTS ma_moulinette.user_agent_event;

CREATE TABLE ma_moulinette.user_agent_event
(
    id                BIGSERIAL PRIMARY KEY,
    event_type        VARCHAR(50)   NOT NULL,
    url               VARCHAR(2048) NOT NULL,
    user_agent        VARCHAR(1024) NOT NULL,
    session_id        VARCHAR(128),
    user_id           BIGINT,
    visitor_id        VARCHAR(36)   NOT NULL,
    auth_state        VARCHAR(20)   NOT NULL,
    processing_status VARCHAR(20)   NOT NULL,
    ip_hash           VARCHAR(64),
    created_at        TIMESTAMPTZ   NOT NULL DEFAULT now(),
    processed_at      TIMESTAMPTZ
);
