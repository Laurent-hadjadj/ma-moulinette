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

/**
 * description
 * Authentification Soanarqube et API
 */
let url = 'http://192.168.138.128:8080/api/';
let token = 'MTM4ZGMxN2Q2MGEwMWQ0OGNiNTAyYTliMzZjNmUxY2E3ZjVjY2NmOTo=';
let listeProjet = [];

/**
 * description
 *  On vérifie que le navigateur est compatible avec les fonctions de type générateur.
 *  function*
 */
try { eval("(function* (){})"); } catch (e) {
  log(' - ERREUR : Votre navigateur n\'est pas compatible avec les fonctions génératrice. Utilisez un navigateur récent comme Chrome, Opera, Firefox ou Edge.');
  log(' - ERREUR : ' + e);
}

/**
 * description
 * Affiche la log.
 */
function log(txt) {
  let textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += new Intl.DateTimeFormat('default', date_options).format(new Date()) + txt + '\n';
}

/**
 * description
 * Initialisation de la log.
 */
function dit_bonjour() { log(' - Initialisation de la log...'); }

/**
 * description
 * Active la gomme pour nettoyer la log.
 */
$('.gomme-svg').on('click', function (e) {
  $('.log').val('');
});

/**
 * description
 * Propriétés du selecteur de recherche.
 */
function match(params, data) {
  if ($.trim(params.term) === '') {
    return data;
  }
  if (typeof data.text === 'undefined') {
    return null;
  }

  if (data.text.indexOf(params.term) > -1) {
    var modifiedData = $.extend({}, data, true);
    modifiedData.text += ' (trouvé)';
    return modifiedData;
  }
  return null;
}

/**
 * description 
 * Converti une date au format xxdaahxxmin en minutes
 */
function date_to_minutes(str) {
  let j, h, m, mm, jour = 0, heure = 0, minute = 0, total = 0;
  j = str.split('d'); //2, 1h1min
  //jour [1, 1h1min]
  if (j.length == 1) { h = j[0].split('h'); }
  if (j.length == 2) { jour = j[0]; h = j[1].split('h'); }

  //heure [1, 1min]
  if (h.length == 1) { m = h[0].split('min'); }
  if (h.length == 2) { heure = h[0]; m = h[1].split('min'); }

  //minute
  if (m.length == 1) { m = j[0].split('min'); }
  if (m.length == 2) { mm = m[0].split('min'); minute = mm[0] }
  total = (jour * 24 * 60) + (heure * 60) + parseInt(minute);
  return total;
}

/**
 * description
 * Converti les minutes en jours, heures et minutes
 */
function minutes_to(minutes) {
  let j, h, m;
  j = Math.floor(minutes / 1440); // 60*24
  h = Math.floor((minutes - (j * 1440)) / 60);
  m = Math.round(minutes % 60);

  if (j > 0) {
    return (j + "j, " + h + "h, " + m + "m");
  } else {
    return (h + "h," + m + "m");
  }
}

/**
 * description
 * Ouverture et/ou Création de la base de données.
 */
function open_database() {
  db.open()
    .then(function (response) {
      log(' - INFO : Ouverture de la base de données.');
      log(' - INFO  : Version du schéma : ' + response.verno);
    })
    .catch('NoSuchDatabaseError', function () {
      log(' - INFO : Création de la base de référence.');
      //créaTion de la base REFERENCE
      db.open().catch(function (e) {
        log(' - ERREUR : Lors de l\'ouverture : ' + e);
      });

      //on Ajoute un enregistrement NaN
      log(' - INFO : La base a été correctement crée. Tout va bien !');
      db.transaction('rw', db.liste_projet, function () { db.liste_projet.add({ projet: 'NaN', clef: 'NaN' }); })
        .then().catch(function (e) { console.log(e) });
    }).catch(function (e) { log('Oh euh : ' + e); });
}

/**
 * description
 * Récupère le nombre de projet dans la base.
 */
function count_projet() { return db.table('liste_projet').count() }

/**
 * description
 * Création du selecteur de projet.
 */
function select_projet() {
  let liste = [], objet = {};
  count_projet().then((count) => {
    if (count > 0) {
      return db.liste_projet.orderBy('name').keys(
        function (projet) {
          for (var a = 0, len = count; a < len; a++) {
            objet = { id: a, text: projet[0] };
            liste.push(objet);
          }
          $('.js-projet').select2({
            matcher: match,
            placeholder: 'Cliquez pour ouvrir la liste',
            allowClear: true,
            width: '100%',
            minimumInputLength: 2,
            minimumResultsForSearch: 20,
            language: "fr",
            data: liste
          });
          $('.analyse').removeClass('hide');
        });
    }
    else { log(' - ERROR : Le référentiel des projets est vide !') }
  })
}


/**
 * description
 * Vérifie si le serveur sonarqube est UP.
 * http://{url}}/api/system/status
 */
function get_statut(_url, _token) {
  let statut = 'system/status';
  let options = {
    url: _url + statut, type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token, }
  }

  return $.ajax(options)
    .then(function (data) { log(' - INFO : État du serveur sonarqube : ' + data.status); })
    .catch((message) => {
      setTimeout(() => { stop_spinner(); }, 2000);
      log(' - ERREUR : État du serveur sonarqube : DOWN (' + message.statusText + ')'); return (message.statusText);
    }
    )
}


/**
 * description
 * Récupère les informations du projet (id de l'enregistrement, date de l'analyse, version, type de version)
 * http://{url}/api/project_analyses/search?project={key}
 */
function update_projet(_url, _token, _key) {
  let search_project = 'project_analyses/search?project=';
  let options = {
    url: _url + search_project + _key, type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token }
  }

  return $.ajax(options).then(
    (t) => {
      //id_maven = la clé du projet ;
      //key = la clé de l'enregistrement ;
      //date = date de l'analyse ;
      //projetVersion = version de l'application
      //type = SNAPHOT ou RELEASE
      //date_enregistrement = date de la mise à jour en local.

      log(' - INFO : Le nombre de version disponible est  : ' + t.analyses.length);
      // Prépartion des informations.
      let id_maven = _key;
      let date_enregistrement = new Intl.DateTimeFormat('default', date_options).format(new Date());
      db.table('mon_projet').clear();
      let analyses = t.analyses;
      analyses.map(function (analyse) {
        var splite = analyse.projectVersion.split('-');
        if (splite[1] == undefined) { splite[1] = 'N.C'; }
        db.transaction('rw', db.mon_projet, function () {
          db.mon_projet.put(
            { id_maven: id_maven, key: analyse.key, date: analyse.date, project_version: analyse.projectVersion, type: splite[1], date_enregistrement });
        })
      })
    })
}

/**
 * description
 * Met à jour les indicateurs du projet (lignes, couvertures, duplication, défauts).
 * http://{url}/api/components/app?component={key}
 */
function update_mesure(_url, _token, _key) {
  let components_app = 'components/app?component=';
  let options = {
    url: _url + components_app + _key, type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token }
  }

  return $.ajax(options)
    .then((t) => {
      //id_maven = la clé du projet ;
      //lines = nombre de ligne ;
      //coverage = Taux de couverture ;
      //issues = Nombre de défaut ;
      //date_enregistrement = date de la mise à jour en local ;

      db.table('mes_mesures').clear();
      let date = new Date();
      let mesures = [t.measures];
      mesures.map(function (mesure) {
        db.transaction('rw', db.mes_mesures, function () {
          db.mes_mesures.put(
            { id_maven: _key, lines: mesure.lines, coverage: mesure.coverage, duplication: mesure.duplicationDensity, issues: mesure.issues, date_enregistrement: date });
        }).then().catch((e) => { log(' - Error :' + e) })
      })
    })
}

/**
 * description
 * Récupère les indicateurs et leur niveau de sévérité.
 */
function update_anomalie(_url, _token, _key) {
  let issues_search = 'issues/search?componentKeys=';
  let paging = '&ps=500&p=1';
  let states = '&statuses=OPEN,CONFIRMED,REOPENED&resolutions=&s=STATUS&asc=no';
  let type = '&types=BUG,VULNERABILITY,CODE_SMELL';
  let options = {
    url: _url + issues_search + _key + paging + states + type,
    type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token }
  }
  return $.ajax(options).then((t) => {
    let issues, date;
    issues = t.issues;
    date = new Date();
    db.table('mes_anomalies').clear()
      .then(issues.map(function (issue) {
        db.transaction('rw', db.mes_anomalies, function () {
          db.mes_anomalies.put({ id_maven: _key, debt: issue.debt, debt_minute: date_to_minutes(issue.debt), rule: issue.rule, type: issue.type, severity: issue.severity, date_enregistrement: date })
        }).then().catch((e) => { console.log(e) })
          .catch((e) => { console.log(e) })
      }))
  });
}

/**
* description
* Récupère la note pour la fiabilité.
* http://{url}/api/measures/component?component={key}&metricKeys=reliability_rating&additionalFields=periods
*/
function update_rating_reliability(_url, _token, _key) {
  let measures_component = 'measures/component?component=';
  let metricLeys = '&metricKeys=reliability_rating&additionalFields=periods';
  let options = {
    url: _url + measures_component + _key + metricLeys,
    type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token }
  }

  return $.ajax(options).then((t) => {
    let date = new Date();
    let components = [t.component];
    db.table('mes_notes_reliability').clear().then(() => {
      db.transaction('rw', db.mes_notes_reliability, function () {
        db.mes_notes_reliability.put({ id_maven: _key, reliability_value: components[0].measures[0].value, period_date: t.periods[0].date, period_version: t.periods[0].parameter, date_enregistrement: date })
      }).then().catch((e) => { log(' - ERROR : ' + e) })
        .catch((e) => { log(' - ERROR : ' + e) })
    })
  })
}

/**
 * description
 * Récupère la note pour la sécurité.
 * http://{url}/api/measures/component?component={key}&metricKeys=security_rating&additionalFields=periods
 */
function update_rating_security(_url, _token, _key) {
  let measures_component = 'measures/component?component=';
  let metricLeys = '&metricKeys=security_rating&additionalFields=periods';
  let options = {
    url: _url + measures_component + _key + metricLeys,
    type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token }
  }
  return $.ajax(options).then((t) => {
    let date = new Date();
    let components = [t.component];
    db.table('mes_notes_security').clear().then(() => {
      db.transaction('rw', db.mes_notes_security, function () {
        db.mes_notes_security.put({ id_maven: _key, security_value: components[0].measures[0].value, period_date: t.periods[0].date, period_version: t.periods[0].parameter, date_enregistrement: date })
      }).then().catch((e) => { log(' - ERROR : ' + e) })
        .catch((e) => { log(' - ERROR : ' + e) })
    })
  })
}

/**
 * description
 * Récupère la note pour la maintenabilité.
 * http://{url}/api/measures/component?component={key}&metricKeys=sqale_rating&additionalFields=periods
 */
function update_rating_sqale(_url, _token, _key) {
  let measures_component = 'measures/component?component=';
  let metricLeys = '&metricKeys=sqale_rating&additionalFields=periods';
  let options = {
    url: _url + measures_component + _key + metricLeys,
    type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token }
  }

  return $.ajax(options).then((t) => {
    let date = new Date();
    let components = [t.component];
    db.table('mes_notes_maintainability').clear().then(() => {
      db.transaction('rw', db.mes_notes_maintainability, function () {
        db.mes_notes_maintainability.put({ id_maven: _key, maintainability_value: components[0].measures[0].value, period_date: t.periods[0].date, period_version: t.periods[0].parameter, date_enregistrement: date })
      }).then().catch((e) => { log(' - ERROR : ' + e) })
        .catch((e) => { log(' - ERROR : ' + e) })
    })
  })
}

/**
 * description
 * Traite en fonction du type de faille OWASP la sévérité
 */
function owasp_severity(t_issues) {

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

  if (t_issues.length == 0) { log(' - INFO : Il n\'y a pas de failles de type OWASP....') }
  else {
    t_issues.map(function (issue) {
      let severity = issue.severity;
      if (t_issue.status == 'OPEN' || t_issue.status == 'CONFIRMED' || t_issue.status == 'REOPENED') {
        for (let a = 1; a < 11; a++) {
          if (t_issue.tags.find(el => el == 'owasp-a' + a) != undefined) {
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
  }

  return [
    a1_blocker, a1_critical, a1_major, a1_minor, a2_blocker, a2_critical, a2_major, a2_minor,
    a3_blocker, a3_critical, a3_major, a3_minor, a4_blocker, a4_critical, a4_major, a4_minor,
    a5_blocker, a5_critical, a5_major, a5_minor, a6_blocker, a6_critical, a6_major, a6_minor,
    a7_blocker, a7_critical, a7_major, a7_minor, a8_blocker, a8_critical, a8_major, a8_minor,
    a9_blocker, a9_critical, a9_major, a9_minor, a10_blocker, a10_critical, a10_major, a10_minor];
}

/**
* description
* Récupère le top 10 OWASP et construit la vue
* http://{url}/api/issues/search?componentKeys={key}&facets=owaspTop10&owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10
* Attention une faille peut être comptée deux fois ou plus, cela dépend du tag. Donc il est
* possible d'avoir pour la clé une faille de type OWASP-A3 et OWASP-A10

*/
function update_owasp(_url, _token, _key) {
  let issues_search = 'issues/search?componentKeys=';
  let facets = '&facets=owaspTop10&owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10';
  let options = {
    url: _url + issues_search + _key + facets,
    type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token }
  }

  return $.ajax(options).then((t) => {
    let date = new Date();
    let owasp = [];

    for (let a = 0; a < 10; a++) {
      switch (t.facets[0].values[a].val) {
        case 'a1': owasp[0] = t.facets[0].values[a].count;
          break;
        case 'a2': owasp[1] = t.facets[0].values[a].count;
          break;
        case 'a3': owasp[2] = t.facets[0].values[a].count;
          break;
        case 'a4': owasp[3] = t.facets[0].values[a].count;
          break;
        case 'a5': owasp[4] = t.facets[0].values[a].count;
          break;
        case 'a6': owasp[5] = t.facets[0].values[a].count;
          break;
        case 'a7': owasp[6] = t.facets[0].values[a].count;
          break;
        case 'a8': owasp[7] = t.facets[0].values[a].count;
          break;
        case 'a9': owasp[8] = t.facets[0].values[a].count;
          break;
        case 'a10': owasp[9] = t.facets[0].values[a].count;
          break;
      }
    }
    let r = owasp_severity(t.issues);
    db.table('rapport_owasp_top10').clear().then(() => {
      db.transaction('rw', db.rapport_owasp_top10, function () {
        db.rapport_owasp_top10.put({
          id_maven: _key, effort_total: t.effortTotal, issues: t.issues,
          a1: owasp[0], a1_blocker: r[0], a1_critical: r[1], a1_major: r[2], a1_minor: r[3],
          a2: owasp[1], a2_blocker: r[4], a2_critical: r[5], a2_major: r[6], a2_minor: r[7],
          a3: owasp[2], a3_blocker: r[8], a3_critical: r[9], a3_major: r[10], a3_minor: r[11],
          a4: owasp[3], a4_blocker: r[12], a4_critical: r[13], a4_major: r[14], a4_minor: r[18],
          a5: owasp[4], a5_blocker: r[16], a5_critical: r[17], a5_major: r[18], a5_minor: r[22],
          a6: owasp[5], a6_blocker: r[20], a6_critical: r[21], a6_major: r[22], a6_minor: r[26],
          a7: owasp[6], a7_blocker: r[24], a7_critical: r[25], a7_major: r[26], a7_minor: r[30],
          a8: owasp[7], a8_blocker: r[28], a8_critical: r[29], a8_major: r[30], a8_minor: r[34],
          a9: owasp[8], a9_blocker: r[32], a9_critical: r[33], a9_major: r[34], a9_minor: r[38],
          a10: owasp[9], a10_blocker: r[36], a10_critical: r[37], a10_major: r[38], a10_minor: r[39],
          date_enregistrement: date
        })
      }).then().catch((e) => { log(' - ERROR : ' + e) })
        .catch((e) => { log(' - ERROR : ' + e) })
    })
  })
}

/**
* description
* Traitement des hotspot de type owasp pour sonarqube 8.9 et >
* http://{url}/api/hotspots/search?projectKey={key}{owasp}&ps=500&p=1
* Pour chaque faille OWASP on récupère les information. Il est possible d'avoir des doublons (i.e. a cause des tags).
*/
function update_hotspot_owasp(_url, _token, _key, _owasp) {
  let owasp = '&owaspTop10=' + _owasp;
  let paging = '&ps=500&p=1'
  let issues_search = 'hotspots/search?projectKey=';
  let options = {
    url: _url + issues_search + _key + paging + owasp,
    type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token }
  }

  let date = new Date();
  return $.ajax(options).then((t) => {
    let hotspots = t.hotspots;
    hotspots.map(function (hotspot) {
      {
        db.transaction('rw', db.rapport_hotspot_owasp, function () {
          db.rapport_hotspot_owasp.put({
            id_maven: _key, owasp_menace: _owasp,
            probability: hotspot.vulnerabilityProbability,
            status: hotspot.status,
            date_enregistrement: date
          })
        }).then().catch((e) => {
          log(' - ERROR : ' + e)
        })
      }
    })
  })
}

/**
* description
* Traitement des hotspot de type owasp pour sonarqube 8.9 et >
* http://{url}/api/hotspots/search?projectKey={key}&ps=500&p=1
* On récupère les failles a examiner. Les clés sont uniques (i.e. on ne se base pas sur les tags).
*/
function update_hotspot(_url, _token, _key) {
  let paging = '&ps=500&p=1'
  let issues_search = 'hotspots/search?projectKey=';
  let options = {
    url: _url + issues_search + _key + paging,
    type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token }
  }

  let date = new Date();
  return $.ajax(options).then((t) => {
    let hotspots = t.hotspots;
    hotspots.map(function (hotspot) {
      {
        db.table('rapport_hotspot').clear().then(() => {
          db.transaction('rw', db.rapport_hotspot, function () {
            let niveau = 0;
            if (hotspot.vulnerabilityProbability == 'HIGH') { niveau = 1 }
            if (hotspot.vulnerabilityProbability == 'MEDIUM') { niveau = 2 }
            if (hotspot.vulnerabilityProbability == 'LOW') { niveau = 3 }
            db.rapport_hotspot.put({
              id_maven: _key, key: hotspot.key, probability: hotspot.vulnerabilityProbability,
              status: hotspot.status, niveau: niveau, date_enregistrement: date
            })
          })
        }).then().catch((e) => {
          log(' - ERROR : ' + e)
        })
      }
    })
  })
}

/**
 * description
 * Récupère le détails des failles de sécurité.
 * http://{url}/api/hotspots/show?hotspot={hotspotKey}
  */
function get_hotspotkey() {
  db.rapport_hotspot.where('status').equals('TO_REVIEW').sortBy('niveau').then((r) => {
    r.map(key => { update_hotspot_owasp_details(url, token, key.key) })
  })
}

function update_hotspot_owasp_details(_url, _token, _hotspotkey) {
  let show_hotspot = 'hotspots/show?hotspot=';
  let options = {
    url: _url + show_hotspot + _hotspotkey,
    type: 'GET', dataType: 'json', contentType: 'application/json; charset=utf-8', crossDomain: true,
    headers: { "accept": "application/json", "Authorization": "Basic " + _token }
  }

  let date = new Date();
  return $.ajax(options).then((hotspot) => {
    let severity = hotspot.rule.vulnerabilityProbability;
    if (severity === undefined) {
      severity = "MAJOR";
      console.error("Le niveau de sévérité n'est pas connu: ", severity);
    }
    let file = hotspot.component.key.split(':').pop();
    let description = hotspot.rule ? hotspot.rule.name : "/";
    db.transaction('rw', db.rapport_hotspot_details, function () {
      db.rapport_hotspot_details.put(
        {
          sonarqube_rule: hotspot.rule.key, severity: severity, status: hotspot.status,
          file: file, line: hotspot.line,
          description: description, message: hotspot.message,
          hotspot_key: hotspot.key, date_enregistrement: date
        })
    })
  })
}

/**
 * description
 * Événement : Affiche le nom de la clé du projet.
 */
$('select[name="projet"]').change(function () {
  let nom_du_projet = $('select[name="projet"]').text().trim(); //on fait un trim pour virer les espaces !!!
  return db.liste_projet.get({ 'name': nom_du_projet })
    .then((t) => {
      $('#select-result').html('<strong>' + t.id_maven + '</strong>');
      $('.js-analyse').removeClass('analyse-disabled');
      $('.js-analyse').addClass('analyse-enabled');
      $('.js-affiche-resultat').removeClass('affiche-resultat-disabled');
      $('.js-affiche-resultat').addClass('affiche-resultat-enabled');
      // on ajoute la clé dans le local storage pour la page security.html
      localStorage.setItem('projet', t.id_maven);
    })
    .catch((e) => {
      log(' - ERROR : une défaillance du système est sur le point d\'arrivée (lol).')
      log(' - ERROR : ' + e)
    });
});

/**
 * description
 * Événement : Ouvre la page security.
 */
$('.analyse-security').on('click', () => { location.href = "/security.html"; });

/**
* description
* Événement : Ouvre la page statistiques.
*/
$('.rapport-graphique').on('click', () => { location.href = "/graphique.html"; });

/**
 * description
 * Fonction de remplissage des tableaux.
 */
function remplissage(_api_maven) {
  //On récupère la première analyse, la dernière, le nombre  d'analyse, le nombre de Release et de snapshot
  db.liste_projet.where({ id_maven: _api_maven }).first().then((r) => {
    $('#clef-projet').html(r.name);
    let splite = _api_maven.split(':');
    $('#nom-projet').html(splite[1]);
  });

  db.mon_projet.where('type').equals('RELEASE').count().then((r) => { $('#version-release').html(r) });
  db.mon_projet.where('type').equals('SNAPHOT').count().then((r) => { $('#version-snapshot').html(r) });
  db.mon_projet.where('type').equals('N.C').count().then((r) => { $('#version-inconnu').html(r); })
  db.mon_projet.where({ id_maven: _api_maven }).reverse().sortBy('date')
    .then((r) => {
      $('#version').html(r[0].project_version);
      let date_release = r[0].date;
      $('#date-version').html(new Intl.DateTimeFormat('default', date_options).format(new Date(date_release)));
    })

  //On récupère maintenant les indicateurs du projet : lignes, couverture fonctionnelle, duplication et le nombre de défaut.
  db.mes_mesures.where({ id_maven: _api_maven }).first().then((r) => {
    $('#nombre-ligne').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.lines));
    $('#couverture').html(new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(r.coverage / 100));
    $('#duplication').html(new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(r.duplication / 100));
    $('#nombre-defaut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.issues));
  });
  //On récupère anomalies : bug, vulnérabilité et mauvaises pratiques (1 page = 500 lignes max).
  db.mes_anomalies.where('type').equals('BUG').count().then((r) => { $('#nombre-bug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('type').equals('VULNERABILITY').count().then((r) => { $('#nombre-vulnerabilite').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('type').equals('CODE_SMELL').count().then((r) => { $('#nombre-mauvaise-pratique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('type').equals('BUG').count().then((r) => { $('#nombre-bug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  var total = 0;
  db.mes_anomalies.where('debt_minute').notEqual(0)
    .each(item => { total += item.debt_minute; })
    .then(() => { let r = minutes_to(total); $('#dette').html(r) });

  //The query {"type":"BUG","severity":"BLOQUER"} on mes_anomalies would benefit of a compound index [type+severity]
  db.mes_anomalies.where('[type+severity]').equals(['BUG', 'BLOCKER']).count().then((r) => { $('#bug-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('[type+severity]').equals(['BUG', 'CRITICAL']).count().then((r) => { $('#bug-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('[type+severity]').equals(['BUG', 'MAJOR']).count().then((r) => { $('#bug-majeure').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('[type+severity]').equals(['BUG', 'MINOR']).count().then((r) => { $('#bug-mineure').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });

  db.mes_anomalies.where('[type+severity]').equals(['VULNERABILITY', 'BLOCKER']).count().then((r) => { $('#vulnerabilite-bloquant').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('[type+severity]').equals(['VULNERABILITY', 'CRITICAL']).count().then((r) => { $('#vulnerabilite-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('[type+severity]').equals(['VULNERABILITY', 'MAJOR']).count().then((r) => { $('#vulnerabilite-majeure').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('[type+severity]').equals(['VULNERABILITY', 'MINOR']).count().then((r) => { $('#vulnerabilite-mineure').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });

  db.mes_anomalies.where('[type+severity]').equals(['CODE_SMELL', 'BLOCKER']).count().then((r) => { $('#mauvaise-pratique-bloquant').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('[type+severity]').equals(['CODE_SMELL', 'CRITICAL']).count().then((r) => { $('#mauvaise-pratique-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('[type+severity]').equals(['CODE_SMELL', 'MAJOR']).count().then((r) => { $('#mauvaise-pratique-majeure').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });
  db.mes_anomalies.where('[type+severity]').equals(['CODE_SMELL', 'MINOR']).count().then((r) => { $('#mauvaise-pratique-mineure').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r)) });

  //On récupère les notes sonarqube pour la version courante
  let matrice = ['', 'A', 'B', 'C', 'D', 'E'], couleur = '';
  db.mes_notes_reliability.where({ id_maven: _api_maven }).first().then((r) => {
    if (r.reliability_value == 1) { couleur = 'note-vert1' }
    if (r.reliability_value == 2) { couleur = 'note-vert2' }
    if (r.reliability_value == 3) { couleur = 'note-jaune' }
    if (r.reliability_value == 4) { couleur = 'note-orange' }
    if (r.reliability_value == 5) { couleur = 'note-rouge' }
    let note = matrice[parseInt(r.reliability_value)];
    $('#note-reliability').html('<span class="' + couleur + '">' + note + '</span>');
  })

  db.mes_notes_security.where({ id_maven: _api_maven }).first().then((r) => {
    if (r.security_value == 1) { couleur = 'note-vert1' }
    if (r.security_value == 2) { couleur = 'note-vert2' }
    if (r.security_value == 3) { couleur = 'note-jaune' }
    if (r.security_value == 4) { couleur = 'note-orange' }
    if (r.security_value == 5) { couleur = 'note-rouge' }
    let note = matrice[parseInt(r.security_value)];
    $('#note-security').html('<span class="' + couleur + '">' + note + '</span>');
  })

  db.mes_notes_maintainability.where({ id_maven: _api_maven }).first().then((r) => {
    if (r.maintainability_value == 1) { couleur = 'note-vert1' }
    if (r.maintainability_value == 2) { couleur = 'note-vert2' }
    if (r.maintainability_value == 3) { couleur = 'note-jaune' }
    if (r.maintainability_value == 4) { couleur = 'note-orange' }
    if (r.maintainability_value == 5) { couleur = 'note-rouge' }
    let note = matrice[parseInt(r.maintainability_value)];
    $('#note-maintainability').html('<span class="' + couleur + '">' + note + '</span>');
  })
}


/*************** Main du programme **************/
// On dit bonjour
dit_bonjour();
// On ouvre la base de données locale
open_database();
// on met ajour la liste des projets disponible
select_projet();

/**
 * description
 * Lance la collecte des données du projet sélectionné.
 */
$('.js-analyse').on('click', function () {
  log(' - INFO : on lance la collecte...');
  // On récupère la clé du projet
  let api_maven = $('#select-result').text().trim();
  if (api_maven == 'N.C') { log(' - ERROR : Vous devez chosir un projet !!!'); return }
  // on démarre l'analyse du projet
  update_projet(url, token, api_maven)
    .then(update_mesure(url, token, api_maven))
    .then(update_anomalie(url, token, api_maven))
    .then(update_rating_reliability(url, token, api_maven))
    .then(update_rating_security(url, token, api_maven))
    .then(update_rating_sqale(url, token, api_maven))
    .then(update_owasp(url, token, api_maven))
    .then(update_hotspot(url, token, api_maven))
    .then(db.table('rapport_hotspot_owasp').clear())
    .then(update_hotspot_owasp(url, token, api_maven, 'a1'))
    .then(update_hotspot_owasp(url, token, api_maven, 'a2'))
    .then(update_hotspot_owasp(url, token, api_maven, 'a3'))
    .then(update_hotspot_owasp(url, token, api_maven, 'a4'))
    .then(update_hotspot_owasp(url, token, api_maven, 'a5'))
    .then(update_hotspot_owasp(url, token, api_maven, 'a6'))
    .then(update_hotspot_owasp(url, token, api_maven, 'a7'))
    .then(update_hotspot_owasp(url, token, api_maven, 'a8'))
    .then(update_hotspot_owasp(url, token, api_maven, 'a9'))
    .then(update_hotspot_owasp(url, token, api_maven, 'a10'))
    .then(db.table('rapport_hotspot_details').clear())
    .then(get_hotspotkey())
    .then(log(' - INFO : Fin de la collecte...'));
})

/**
 * description
 * On passe à la peinture
 */
$('.js-affiche-resultat').on('click', function () {
  // On récupère la clé du projet
  let api_maven = $('#select-result').text().trim();
  remplissage(api_maven);
})
