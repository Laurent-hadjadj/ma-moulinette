/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V1.0.0 - 14/05/2026                  ##
##                                                ##
####################################################*/

--- 2026-05-14 : Ajout de la table logger_detail

/*
La table `logger` actuelle agrège 1 ligne par projet (compteurs info/warn/error/debug).
Le plugin track-logger-method v2.0.0 retourne maintenant pour chaque occurrence :
  - le framework utilisé (SLF4J, Commons Logging, java.util.logging…)
  - le fichier et la ligne concernés
Ces données permettent un drill-down par fichier/classe + analyse par techno.

RETRO-COMPAT plugin v1.x : les rows sont insérées même si `framework` et
`line_number` sont null (plugin v1 ne fournit pas ces champs). Le file_path
reste toujours disponible (vient du champ `component` de l'API Sonar, présent en v1+v2).
*/

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette;

CREATE TABLE IF NOT EXISTS ma_moulinette.logger_details (
    id                    BIGSERIAL    PRIMARY KEY,
    maven_key             VARCHAR(255) NOT NULL,
    project_version       VARCHAR(64),
    level                 VARCHAR(16)  NOT NULL,
    framework             VARCHAR(64),
    file_path             TEXT         NOT NULL,
    file_name             VARCHAR(255) NOT NULL,
    class_name            VARCHAR(255),
    line_number           INTEGER,
    sonar_issue_key       VARCHAR(64),
    mode_collecte         VARCHAR(32),
    utilisateur_collecte  VARCHAR(320),
    date_enregistrement   TIMESTAMP WITH TIME ZONE NOT NULL
);
