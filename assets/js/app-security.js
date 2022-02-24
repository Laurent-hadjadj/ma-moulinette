/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

/**
 * description
 * On charge l'API dexie et on créé les schémas si, il n'existe pas.
 */

let db = new Dexie('REFERENCE');
db.version(1).stores({
  liste_projet: '++id, id_maven, name, date_enregistrement',
  mon_projet: '++id, id_maven, key, date, project_version, type, date_enregistrement',
  mes_mesures: '++id, id_maven, lines, coverage, duplication, issues, date_enregistrement',
  mes_anomalies: '++id, id_maven, debt, debt_minute, rule, type, severity, [type+severity], date_enregistrement',
  mes_notes_maintainability: '++id, id_maven, maintainability_value, period_date, period_version, date_enregistrement',
  mes_notes_security: '++id, id_maven, security_value, period_date, period_version, date_enregistrement',
  mes_notes_reliability: '++id, id_maven, reliability_value, period_date, period_version, date_enregistrement',
  rapport_owasp_top10: '++id, id_maven, effortTotal, issues,' +
    'a1, a1_blocker, a1_critical, a1_major, a1_minor,' + 'a2, a2_blocker, a2_critical, a2_major, a2_minor,' +
    'a3, a3_blocker, a3_critical, a3_major, a3_minor,' + 'a4, a4_blocker, a4_critical, a4_major, a4_minor,' +
    'a5, a5_blocker, a5_critical, a5_major, a5_minor,' + 'a6, a6_blocker, a6_critical, a6_major, a6_minor,' +
    'a7, a7_blocker, a7_critical, a7_major, a7_minor,' + 'a8, a8_blocker, a8_critical, a8_major, a8_minor,' +
    'a9, a9_blocker, a9_critical, a9_major, a9_minor,' + 'a10, a10_blocker, a10_critical, a10_major, a10_minor, date_enregistrement',
  rapport_hotspot: '++id, id_maven, key, probability, status, niveau, date_enregistrement',
  rapport_hotspot_details: '++id, sonarqube_rule, severity, status, file, line, description, message, hotspot_key, date_enregistrement',
  rapport_hotspot_owasp: '++id, id_maven, owasp_menace,probability, status, [id_maven+owasp_menace+status+probability], date_enregistrement',
});

let date_options = {
  year: "numeric", month: "numeric", day: "numeric",
  hour: "numeric", minute: "numeric", second: "numeric",
  hour12: false
};

const note = ['', 'A', 'B', 'C', 'D', 'E'];
const couleur = ['', 'badge-vert1', 'badge-vert2', 'badge-jaune', 'badge-orange', 'badge-rouge'];

const liste_owasp2017 = [
  "", "A1 - Attaques d'injection", "A2 - Authentification défaillante", "A3 - Fuites de données sensibles",
  "A4 - Entités externes XML (XXE)", "A5 - Contrôle d'accès défaillant", "A6 - Configurations défaillantes",
  "A7 - Attaques cross-site scripting (XSS)", "A8 - Désérialisation sans validation", "A9 - Composants tiers vulnérables",
  "A10 - Journalisation et surveillance insuffisantes"]

/**
 * description
 * Authentification Soanarqube et API
 */
let serveur = 'http://192.168.138.128:8080/'
let url = serveur + '/api/';
let token = 'MTM4ZGMxN2Q2MGEwMWQ0OGNiNTAyYTliMzZjNmUxY2E3ZjVjY2NmOTo=';

/**
 * description
 *  Tests Unitaire
 */
function test_owasp_severity() {

  let a1_blocker = 0, a1_critical = 0, a1_major = 0, a1_minor = 0,
    a2_blocker = 0, a2_critical = 0, a2_major = 0, a2_minor = 0,
    a3_blocker = 0, a3_critical = 0, a3_major = 0, a3_minor = 0,
    a4_blocker = 0, a4_critical = 0, a4_major = 0, a4_minor = 0,
    a5_blocker = 0, a5_critical = 0, a5_major = 0, a5_minor = 0,
    a6_blocker = 0, a6_critical = 0, a6_major = 0, a6_minor = 0,
    a7_blocker = 0, a7_critical = 0, a7_major = 0, a7_minor = 0,
    a8_blocker = 0, a8_critical = 0, a8_major = 0, a8_minor = 0,
    a9_blocker = 0, a9_critical = 0, a9_major = 0, a9_minor = 0,
    a10_blocker = 0, a10_critical = 0, a10_major = 0, a10_minor = 0;

  const issues = [
    { "severity": "CRITICAL", "status": "OPEN", "tags": ["owasp-a6", "owasp-a1", "spring"] },
    { "severity": "MAJOR", "status": "OPEN", "tags": ["owasp-a4",] },
    { "severity": "MINOR", "status": "CLOSE", "tags": ["owasp-a2",] },
    { "severity": "BLOCKER", "status": "OPEN", "tags": ["owasp-a9",] },
    { "severity": "MINOR", "status": "OPEN", "tags": ["owasp-a10",] },
    { "severity": "MINOR", "status": "OPEN", "tags": ["owasp-a10",] }];

  issues.map(function (issue) {
    let severity = issue.severity;
    if (issue.status == 'OPEN' || issue.status == 'CONFIRMED' || issue.status == 'REOPENED') {
      for (let a = 1; a < 11; a++) {
        if (issue.tags.find(el => el == 'owasp-a' + a) != undefined) {
          console.log(a);
          switch (a) {
            case 1: if (severity == 'BLOCKER') { a1_blocker++ };
              if (severity == 'CRITICAL') { a1_critical++ };
              if (severity == 'MAJOR') { a1_major++ };
              if (severity == 'MINOR') { a1_minor++ };
              break;
            case 2: if (severity == 'BLOCKER') { a2_blocker++ };
              if (severity == 'CRITICAL') { a2_critical++ };
              if (severity == 'MAJOR') { a2_major++ };
              if (severity == 'MINOR') { a2_minor++ };
              break;
            case 3: if (severity == 'BLOCKER') { a3_blocker++ };
              if (severity == 'CRITICAL') { a3_critical++ };
              if (severity == 'MAJOR') { a3_major++ };
              if (severity == 'MINOR') { a3_minor++ };
              break;
            case 4: if (severity == 'BLOCKER') { a4_blocker++ };
              if (severity == 'CRITICAL') { a4_critical++ };
              if (severity == 'MAJOR') { a4_major++ };
              if (severity == 'MINOR') { a4_minor++ };
              break;
            case 5: if (severity == 'BLOCKER') { a5_blocker++ };
              if (severity == 'CRITICAL') { a5_critical++ };
              if (severity == 'MAJOR') { a5_major++ };
              if (severity == 'MINOR') { a6_minor++ };
              break;
            case 6: if (severity == 'BLOCKER') { a6_blocker++ };
              if (severity == 'CRITICAL') { a6_critical++ };
              if (severity == 'MAJOR') { a6_major++ };
              if (severity == 'MINOR') { a6_minor++ };
              break;
            case 7: if (severity == 'BLOCKER') { a7_blocker++ };
              if (severity == 'CRITICAL') { a7_critical++ };
              if (severity == 'MAJOR') { a7_major++ };
              if (severity == 'MINOR') { a7_minor++ };
              break;
            case 8: if (severity == 'BLOCKER') { a8_blocker++ };
              if (severity == 'CRITICAL') { a8_critical++ };
              if (severity == 'MAJOR') { a8_major++ };
              if (severity == 'MINOR') { a8_minor++ };
              break;
            case 9: if (severity == 'BLOCKER') { a9_blocker++ };
              if (severity == 'CRITICAL') { a9_critical++ };
              if (severity == 'MAJOR') { a9_major++ };
              if (severity == 'MINOR') { a9_minor++ };
              break;
            case 10: if (severity == 'BLOCKER') { a10_blocker++ };
              if (severity == 'CRITICAL') { a10_critical++ };
              if (severity == 'MAJOR') { a10_major++ };
              if (severity == 'MINOR') { a10_minor++ };
              break;
          }
        }
      }
    }
  })

  return [
    a1_blocker, a1_critical, a1_major, a1_minor, a2_blocker, a2_critical, a2_major, a2_minor,
    a3_blocker, a3_critical, a3_major, a3_minor, a4_blocker, a4_critical, a4_major, a4_minor,
    a5_blocker, a5_critical, a5_major, a5_minor, a6_blocker, a6_critical, a6_major, a6_minor,
    a7_blocker, a7_critical, a7_major, a7_minor, a8_blocker, a8_critical, a8_major, a8_minor,
    a9_blocker, a9_critical, a9_major, a9_minor, a10_blocker, a10_critical, a10_major, a10_minor];
}

/**
 * description
 * Ouverture et/ou Création de la base de données.
 */
function open_database() { db.open().then().catch(function (e) { console.log('Oh euh : ' + e); }); }



/**
 * description
 * Fonction de remplissage dy tableau avec les infos OWASP.
 */
function remplissage_owasp() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }

  let c, n, i;
  db.rapport_owasp_top10.where({ id_maven: id_maven }).first().then((r) => {
    // Informations
    let total = parseInt(r.a1 + r.a2 + r.a3 + r.a4 + r.a5 + r.a6 + r.a7 + r.a8 + r.a9 + r.a10);
    let bloquante = parseInt(r.a1_blocker + r.a2_blocker + r.a3_blocker + r.a4_blocker + r.a5_blocker + r.a6_blocker + r.a7_blocker + r.a8_blocker + r.a9_blocker + r.a10_blocker);
    let critique = parseInt(r.a1_critical + r.a2_critical + r.a3_critical + r.a4_critical + r.a5_critical + r.a6_critical + r.a7_critical + r.a8_critical + r.a9_critical + r.a10_critical);
    let majeure = parseInt(r.a1_major + r.a2_major + r.a3_major + r.a4_major + r.a5_major + r.a6_major + r.a7_major + r.a8_major + r.a9_major + r.a10_major);
    let mineure = parseInt(r.a1_minor + r.a2_minor + r.a3_minor + r.a4_minor + r.a5_minor + r.a6_minor + r.a7_minor + r.a8_minor + r.a9_minor + r.a10_minor);

    $('#nombre_faille_owasp').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(total));
    $('#nombre_faille_bloquante').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(bloquante));
    $('#nombre_faille_critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(critique));
    $('#nombre_faille_majeure').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(majeure));
    $('#nombre_faille_mineure').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(mineure));

    // Détails A1
    if (parseInt(r.a1_blocker + r.a1_critical + r.a1_major + r.a1_minor) == 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a1_minor) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a1_major) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a1_critical) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a1_blocker) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a1) + '</span> <span class="badge ' + c + '">' + n + '</span>';
    $('#a1').html(i);

    // Détails A2
    if (parseInt(r.a2_blocker + r.a2_critical + r.a2_major + r.a2_minor) == 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a2_minor) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a2_major) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a2_critical) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a2_blocker) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a2) + '</span> <span class="badge ' + c + '">' + n + '</span>';
    $('#a2').html(i);

    // Détails A3
    if (parseInt(r.a3_blocker + r.a3_critical + r.a3_major + r.a3_minor) == 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a3_minor) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a3_major) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a3_critical) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a3_blocker) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a3) + '</span> <span class="badge ' + c + '">' + n + '</span>';
    $('#a3').html(i);

    // Détails A4
    if (parseInt(r.a4_blocker + r.a1_critical + r.a1_major + r.a1_minor) == 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a4_minor) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a4_major) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a4_critical) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a4_blocker) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a4) + '</span> <span class="badge ' + c + '">' + n + '</span>';
    $('#a4').html(i);

    // Détails A5
    if (parseInt(r.a5_blocker + r.a5_critical + r.a5_major + r.a5_minor) == 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a5_minor) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a5_major) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a5_critical) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a5_blocker) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a5) + '</span> <span class="badge ' + c + '">' + n + '</span>';
    $('#a5').html(i);

    // Détails A6
    if (parseInt(r.a6_blocker + r.a6_critical + r.a6_major + r.a6_minor) == 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a6_minor) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a6_major) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a6_critical) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a6_blocker) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a6) + '</span> <span class="badge ' + c + '">' + n + '</span>';
    $('#a6').html(i);

    // Détails A7
    if (parseInt(r.a7_blocker + r.a7_critical + r.a7_major + r.a7_minor) == 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a7_minor) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a7_major) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a7_critical) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a7_blocker) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a7) + '</span> <span class="badge ' + c + '">' + n + '</span>';
    $('#a7').html(i);

    // Détails A8
    if (parseInt(r.a8_blocker + r.a8_critical + r.a8_major + r.a8_minor) == 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a8_minor) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a8_major) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a8_critical) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a8_blocker) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a8) + '</span> <span class="badge ' + c + '">' + n + '</span>';
    $('#a8').html(i);

    // Détails A9
    if (parseInt(r.a9_blocker + r.a9_critical + r.a9_major + r.a9_minor) == 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a9_minor) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a9_major) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a9_critical) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a9_blocker) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a9) + '</span> <span class="badge ' + c + '">' + n + '</span>';
    $('#a9').html(i);

    // Détails A10
    if (parseInt(r.a10_blocker + r.a10_critical + r.a10_major + r.a10_minor) == 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a10_minor) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a10_major) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a10_critical) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a10_blocker) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a10) + '</span> <span class="badge ' + c + '">' + n + '</span>';
    $('#a10').html(i);
  })
}


/**
 * description
 * Calcul la note des hotspot
 */
function calcul_note_hotspot(taux) {
  let c, n
  if (taux > 0.79) { c = couleur[1]; n = note[1] }
  if (taux > 0.71 && taux < 0.81) { c = couleur[2]; n = note[2] }
  if (taux > 0.51 && taux < 0.71) { c = couleur[3]; n = note[3] }
  if (taux > 0.31 && taux < 0.51) { c = couleur[4]; n = note[4] }
  if (taux < 0.31) { c = couleur[5]; n = note[5] }
  return [c, n]
}

/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot.
 */
function remplissage_hotspot() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }

  let i;
  // On compte le nombre de hotspot au status REVIEWED
  db.rapport_hotspot.where('status').equals('REVIEWED').count()
    .then((r) => { $('#hotspot-reviewed').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)); sessionStorage.setItem('hotspot-reviewed', r) });
  // On compte le nombre de hotspot au status TO_REVIEW
  db.rapport_hotspot.where('status').equals('TO_REVIEW').count()
    .then((r) => { $('#hotspot-to-review').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)); sessionStorage.setItem('hotspot-to-review', r) });
  // On compte le nombre de total de hotspot
  db.rapport_hotspot.where('status').anyOf(['REVIEWED', 'TO_REVIEW']).count()
    .then((r) => { $('#hotspot-total').html(r); sessionStorage.setItem('hotspot-total', r) });

  let leTaux = 1 - (sessionStorage.getItem('hotspot-to-review') / sessionStorage.getItem('hotspot-total'));
  let _note = calcul_note_hotspot(leTaux);
  i = '<span>' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
  $('#note-hotspot').html(i);
}

/**
* description
* Fonction de remplissage du tableau avec les infos hotspot owasp A1.
*/
function remplissage_hotspot_owasp_a1() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status]').equals([id_maven, 'a1', 'TO_REVIEW']).count().then((r) => {
    let leTaux = 1 - (r / sessionStorage.getItem('hotspot-total'));
    let _note = calcul_note_hotspot(leTaux);
    let i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
    $('#h1').html(i);
  })
}

function remplissage_details_hotspot_owasp_a1() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a1', 'TO_REVIEW', 'HIGH']).count().then((r) => {
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a1', 'TO_REVIEW', 'MEDIUM']).count().then((r) => {
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a1', 'TO_REVIEW', 'LOW']).count().then((r) => {
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
}

/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot owasp A2.
 */
function remplissage_hotspot_owasp_a2() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status]').equals([id_maven, 'a2', 'TO_REVIEW']).count().then((r) => {
    let leTaux = 1 - (r / sessionStorage.getItem('hotspot-total'));
    let _note = calcul_note_hotspot(leTaux);
    let i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
    $('#h2').html(i);
  })
}

function remplissage_details_hotspot_owasp_a2() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a2', 'TO_REVIEW', 'HIGH']).count().then((r) => {
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a2', 'TO_REVIEW', 'MEDIUM']).count().then((r) => {
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a2', 'TO_REVIEW', 'LOW']).count().then((r) => {
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
}

/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot owasp A3.
 */
function remplissage_hotspot_owasp_a3() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status]').equals([id_maven, 'a3', 'TO_REVIEW']).count().then((r) => {
    let leTaux = 1 - (r / sessionStorage.getItem('hotspot-total'));
    let _note = calcul_note_hotspot(leTaux);
    let i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
    $('#h3').html(i);
  })
}

function remplissage_details_hotspot_owasp_a3() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a3', 'TO_REVIEW', 'HIGH']).count().then((r) => {
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a3', 'TO_REVIEW', 'MEDIUM']).count().then((r) => {
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a3', 'TO_REVIEW', 'LOW']).count().then((r) => {
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
}

/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot owasp A4.
 */
function remplissage_hotspot_owasp_a4() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status]').equals([id_maven, 'a4', 'TO_REVIEW']).count().then((r) => {
    let leTaux = 1 - (r / sessionStorage.getItem('hotspot-total'));
    let _note = calcul_note_hotspot(leTaux);
    let i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
    $('#h4').html(i);
  })
}

function remplissage_details_hotspot_owasp_a4() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a4', 'TO_REVIEW', 'HIGH']).count().then((r) => {
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a4', 'TO_REVIEW', 'MEDIUM']).count().then((r) => {
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a4', 'TO_REVIEW', 'LOW']).count().then((r) => {
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
}
/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot owasp A5.
 */
function remplissage_hotspot_owasp_a5() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status]').equals([id_maven, 'a5', 'TO_REVIEW']).count().then((r) => {
    let leTaux = 1 - (r / sessionStorage.getItem('hotspot-total'));
    let _note = calcul_note_hotspot(leTaux);
    let i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
    $('#h5').html(i);
  })
}

function remplissage_details_hotspot_owasp_a5() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a5', 'TO_REVIEW', 'HIGH']).count().then((r) => {
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a5', 'TO_REVIEW', 'MEDIUM']).count().then((r) => {
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a5', 'TO_REVIEW', 'LOW']).count().then((r) => {
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
}

/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot owasp A6.
 */
function remplissage_hotspot_owasp_a6() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status]').equals([id_maven, 'a6', 'TO_REVIEW']).count().then((r) => {
    let leTaux = 1 - (r / sessionStorage.getItem('hotspot-total'));
    let _note = calcul_note_hotspot(leTaux);
    let i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
    $('#h6').html(i);
  })
}

function remplissage_details_hotspot_owasp_a6() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a6', 'TO_REVIEW', 'HIGH']).count().then((r) => {
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a6', 'TO_REVIEW', 'MEDIUM']).count().then((r) => {
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a6', 'TO_REVIEW', 'LOW']).count().then((r) => {
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
}

/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot owasp A7.
 */
function remplissage_hotspot_owasp_a7() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status]').equals([id_maven, 'a7', 'TO_REVIEW']).count().then((r) => {
    let leTaux = 1 - (r / sessionStorage.getItem('hotspot-total'));
    let _note = calcul_note_hotspot(leTaux);
    let i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
    $('#h7').html(i);
  })
}

function remplissage_details_hotspot_owasp_a7() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a7', 'TO_REVIEW', 'HIGH']).count().then((r) => {
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a7', 'TO_REVIEW', 'MEDIUM']).count().then((r) => {
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a7', 'TO_REVIEW', 'LOW']).count().then((r) => {
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
}

/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot owasp A8.
 */
function remplissage_hotspot_owasp_a8() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status]').equals([id_maven, 'a8', 'TO_REVIEW']).count().then((r) => {
    let leTaux = 1 - (r / sessionStorage.getItem('hotspot-total'));
    let _note = calcul_note_hotspot(leTaux);
    let i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
    $('#h8').html(i);
  })
}

function remplissage_details_hotspot_owasp_a8() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a8', 'TO_REVIEW', 'HIGH']).count().then((r) => {
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a8', 'TO_REVIEW', 'MEDIUM']).count().then((r) => {
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a8', 'TO_REVIEW', 'LOW']).count().then((r) => {
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
}

/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot owasp A7.
 */
function remplissage_hotspot_owasp_a9() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status]').equals([id_maven, 'a9', 'TO_REVIEW']).count().then((r) => {
    let leTaux = 1 - (r / sessionStorage.getItem('hotspot-total'));
    let _note = calcul_note_hotspot(leTaux);
    let i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
    $('#h9').html(i);
  })
}

function remplissage_deails_hotspot_owasp_a9() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a9', 'TO_REVIEW', 'HIGH']).count().then((r) => {
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a9', 'TO_REVIEW', 'MEDIUM']).count().then((r) => {
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a10', 'TO_REVIEW', 'LOW']).count().then((r) => {
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
}

/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot owasp A10.
 */
function remplissage_hotspot_owasp_a10() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status]').equals([id_maven, 'a10', 'TO_REVIEW']).count().then((r) => {
    let leTaux = 1 - (r / sessionStorage.getItem('hotspot-total'));
    let _note = calcul_note_hotspot(leTaux);
    let i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + '</span> <span class="badge ' + _note[0] + '">' + _note[1] + '</span>';
    $('#h10').html(i);
  })
}
function remplissage_details_hotspot_owasp_a10() {
  let id_maven = localStorage.getItem('projet');
  if (id_maven == undefined) { return }
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a10', 'TO_REVIEW', 'HIGH']).count().then((r) => {
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a10', 'TO_REVIEW', 'MEDIUM']).count().then((r) => {
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
  db.rapport_hotspot_owasp.where('[id_maven+owasp_menace+status+probability]').equals([id_maven, 'a10', 'TO_REVIEW', 'LOW']).count().then((r) => {
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r));
  })
}


function remplissage_hotspot_details() {
  let numero = 0, mon_numero, ligne, c;
  db.rapport_hotspot_details.orderBy('severity').toArray().then((r) => {
    r.map(function (hotspot) {
      numero++;
      if (numero < 10) { mon_numero = '0' + numero } else { mon_numero = numero }
      if (hotspot.severity == 'LOW') { c = 'severity-jaune' }
      if (hotspot.severity == 'MEDIUM') { c = 'severity-orange' }
      if (hotspot.severity == 'HIGH') { c = 'severity-rouge' }

      ligne = '<tr>';
      ligne += '<td class="stat-note">' + mon_numero + '</td>';
      ligne += '<td><a href="' + serveur + 'coding_rules?open=' + hotspot.sonarqube_rule + '&q=' + hotspot.sonarqube_rule + '">' + hotspot.sonarqube_rule + '</a></td>';
      ligne += '<td class="' + c + '">' + hotspot.severity + '</td>';
      ligne += '<td class="component">' + hotspot.file + '</td>';
      ligne += '<td>' + hotspot.line + '</td>';
      ligne += '<td>' + hotspot.description + '</td>';
      ligne += '<td>' + hotspot.message + '</td>';
      ligne += '<td>' + hotspot.status + '</td>';
      ligne += '</tr>';
      $('#tbody').append(ligne);
    })
  })
}

$('.js-details').on('click', function () {
  let id = $(this).attr('id').split('-');
  $('.details-titre').html(liste_owasp2017[id[1]]);
  if (id[1] == '1') { remplissage_details_hotspot_owasp_a1() }
  if (id[1] == '2') { remplissage_details_hotspot_owasp_a2() }
  if (id[1] == '3') { remplissage_details_hotspot_owasp_a3() }
  if (id[1] == '4') { remplissage_details_hotspot_owasp_a4() }
  if (id[1] == '5') { remplissage_details_hotspot_owasp_a5() }
  if (id[1] == '6') { remplissage_details_hotspot_owasp_a6() }
  if (id[1] == '7') { remplissage_details_hotspot_owasp_a7() }
  if (id[1] == '8') { remplissage_details_hotspot_owasp_a8() }
  if (id[1] == '9') { remplissage_details_hotspot_owasp_a9() }
  if (id[1] == '10') { remplissage_details_hotspot_owasp_a10() }

  $('#details').foundation('open');
})

/*************** Main du programme **************/
// On ouvre la base de données locale
open_database();
remplissage_owasp();
remplissage_hotspot();
remplissage_hotspot_owasp_a1();
remplissage_hotspot_owasp_a2();
remplissage_hotspot_owasp_a3();
remplissage_hotspot_owasp_a4();
remplissage_hotspot_owasp_a5();
remplissage_hotspot_owasp_a6();
remplissage_hotspot_owasp_a7();
remplissage_hotspot_owasp_a8();
remplissage_hotspot_owasp_a9();
remplissage_hotspot_owasp_a10();

remplissage_hotspot_details();

// Tests unitaire
//console.log(test_owasp_severity());