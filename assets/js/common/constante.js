/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Liste des constantes HTTP */
export const http_200=200;
export const http_201=201;
export const http_202=202;
export const http_400=400;
export const http_401=401;
export const http_403=403;
export const http_404=404;
export const http_406=406;
export const http_500=500;
export const http_503=503;
export const http_504=504;

/** Liste des constantes clientHTML */
export const contentType = 'application/json; charset=utf-8';

/** Liste des constantes charJS */
export const matrice = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
  16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31];

export const paletteCouleur = [
  '#065535', '#133337', '#000000', '#ffc0cb', '#008080', '#ff0000', '#ffd700', '#666666',
  '#ff7373', '#fa8072', '#800080', '#800000', '#003366', '#333333', '#20b2aa', '#ffc3a0',
  '#f08080', '#66cdaa', '#f6546a', '#ff6666', '#468499', '#c39797', '#bada55', '#ff7f50',
  '#660066', '#008000', '#088da5', '#808080', '#8b0000', '#0e2f44', '#3b5998', '#cc0000' ];

export const chartColors = {
  rouge: 'rgb(255,99,132)',
  rougeOpacity: 'rgb(255,99,132,0.5)',
  bleu: 'rgb(54,162,235)',
  bleuOpacity: 'rgb(54,162,235,0.5)',
  orange: 'rgb(170,102,51)',
  orangeOpacity: 'rgb(170,102,51,0.5)' };

/** Liste des constantes pour le formatage des dates */
export const dateOptions = {
  year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric', hour12: false };
export const dateOptionsShort = {year: 'numeric', month: 'numeric', day: 'numeric' };
export const dateOptionsVeryShort = {year: '2-digit', month: 'numeric', day: 'numeric' };

/** Tableau des notes SonarQube */
export const note = ['', 'A', 'B', 'C', 'D', 'E'];

/** Tableau des couleurs pour les notes */
export const couleur = ['', 'note-a', 'note-b', 'note-c', 'note-d', 'note-e'];

/** Liste des constante de formatage */
export const espace='&nbsp;';
export const rien='';

/** Liste des constantes d'indice */
export const zero=0;
export const un=1;
export const deux=2;
export const trois=3;
export const quatre=4;
export const cinq=5;
export const six=6;
export const sept=7;
export const huit=8;
export const neuf=9;
export const dix=10;
export const onze=11;
export const vingt=20;
export const vingtNeuf=29;
export const trente=30;
export const trenteDeux=32;
export const quarante=40;
export const cinquante=50;
export const cinquanteDeux=52;
export const cinquanteTrois=53;
export const soixante=60;
export const soixanteQuatre=64;
export const soixanteNeuf=69;
export const soixanteDix=70;
export const quatreVingt=80;
export const quatreVingtDix=90;
export const cent=100;
export const cinqCent=500;
export const mille=1000;
export const deuxMille=2000;
export const troisMille=3000;
export const cinqMille=5000;
export const dixMille=10000;

/** Liste des menaces OWASP 2017 */
export const listeOwasp2017 = [
  '', 'A1 - Attaques d\'injection', 'A2 - Authentification défaillante', 'A3 - Fuites de données sensibles',
  'A4 - Entités externes XML (XXE)', 'A5 - Contrôle d\'accès défaillant', 'A6 - Configurations défaillantes',
  'A7 - Attaques cross-site scripting (XSS)', 'A8 - Désérialisation sans validation', 'A9 - Composants tiers vulnérables',
  'A10 - Journalisation et surveillance insuffisantes'];

/** Liste des menaces OWASP 2021 */
export const listeOwasp2021 =[
  '','A1 - Contrôle d\'accès défaillant','A2 - Défaillances cryptographiques','A3 - Injection','A4 - Conception non sécurisée','A5 - Mauvaise configuration de sécurité','A6 - Composants vulnérables et obsolètes','A7 - Identification et authentification de mauvaise qualité','A8 - Manque d\'intégrité des données et du logiciel','A9 - Carence des systèmes de contrôle et de journalisation','A10 - Falsification de requête côté serveur (SSRF)'];
