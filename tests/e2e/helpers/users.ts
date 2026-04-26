/**
 * Utilisateurs E2E (chargés via fixtures-e2e.sql).
 *
 * Convention : password = courriel (bcrypt cost 13).
 * Au démarrage : seul `interne` est actif. Les 4 autres sont disabled
 * avec ROLE_NONE et seront activés + role-assignés en cours de scenario.
 */

export type E2EUser = {
  email: string;
  password: string;
  prenom: string;
  nom: string;
  /** Rôle initial dans fixtures-e2e.sql (avant activation) */
  initialRole: string;
  /** Rôle(s) cible(s) après activation par l'internal */
  targetRoles: string[];
};

export const USERS = {
  interne: {
    email: 'interne@ma-moulinette.fr',
    password: 'interne@ma-moulinette.fr',
    prenom: 'Interne',
    nom: 'E2E',
    initialRole: 'ROLE_INTERNAL',
    targetRoles: ['ROLE_INTERNAL'],
  },
  josh: {
    email: 'josh.liberman@ma-moulinette.fr',
    password: 'josh.liberman@ma-moulinette.fr',
    prenom: 'Josh',
    nom: 'LIBERMAN',
    initialRole: 'ROLE_NONE',
    targetRoles: ['ROLE_UTILISATEUR'],
  },
  nathan: {
    email: 'nathan.jones@ma-moulinette.fr',
    password: 'nathan.jones@ma-moulinette.fr',
    prenom: 'Nathan',
    nom: 'JONES',
    initialRole: 'ROLE_NONE',
    targetRoles: ['ROLE_COLLECTE'],
  },
  sophie: {
    email: 'sophie.martin@ma-moulinette.fr',
    password: 'sophie.martin@ma-moulinette.fr',
    prenom: 'Sophie',
    nom: 'MARTIN',
    initialRole: 'ROLE_NONE',
    targetRoles: ['ROLE_COLLECTE', 'ROLE_SUIVI'],
  },
  aurelie: {
    email: 'aurelie.petit-coeur@ma-moulinette.fr',
    password: 'aurelie.petit-coeur@ma-moulinette.fr',
    prenom: 'Aurélie',
    nom: 'PETIT COEUR',
    initialRole: 'ROLE_NONE',
    targetRoles: ['ROLE_GESTIONNAIRE'],
  },
} as const satisfies Record<string, E2EUser>;

/**
 * Mapping users → groupes utilisateur (créés en step 2 par l'internal,
 * affectés en step 5 par le gestionnaire).
 */
export const USER_GROUPS = {
  interne: 'ADMIN',
  josh: 'CONSULTATION',
  nathan: 'COLLECTE',
  sophie: 'GESTIONNAIRE METIER',
  aurelie: 'GESTIONNAIRE APPLICATIF',
} as const;

export const ALL_GROUPS = [
  'ADMIN',
  'CONSULTATION',
  'COLLECTE',
  'GESTIONNAIRE METIER',
  'GESTIONNAIRE APPLICATIF',
] as const;
