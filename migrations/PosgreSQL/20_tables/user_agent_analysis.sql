/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V1.0.0 - 09/06/2026                  ##
##                                                ##
####################################################*/

--- 2026-06-09 : Intégration table user_agent_analysis dans le schéma ma_moulinette

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette;

DROP TABLE IF EXISTS ma_moulinette.user_agent_analysis;

CREATE TABLE ma_moulinette.user_agent_analysis
(
    id               BIGSERIAL PRIMARY KEY,
    device_type      VARCHAR(30),
    os_name          VARCHAR(50),
    os_version       VARCHAR(50),
    browser_name     VARCHAR(50),
    browser_version  VARCHAR(50),
    is_bot           BOOLEAN,
    detector_version VARCHAR(20)   NOT NULL,
    event_type       VARCHAR(50),
    url              VARCHAR(2048),
    session_id       VARCHAR(128),
    visitor_id       VARCHAR(36)   NOT NULL,
    user_id          BIGINT,
    created_at       TIMESTAMPTZ   NOT NULL DEFAULT now()
);
