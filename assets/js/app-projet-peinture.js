/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

// Intégration de jquery
import $ from 'jquery';

// On importe les paramètres serveur
import {serveur} from './properties.js';

/** On importe les constantes */
import {contentType, dateOptions, http_406,
        un, deux, trois, quatre, cinq, dix, cent, dixMille} from './constante.js';

/**
 * [Description for log]
 * Affiche la log.
 *
 * @param mixed txt
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:57:03 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const log=function(txt) {
  const textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += `${new Intl.DateTimeFormat('default',
  dateOptions).format(new Date())} ${txt}\n`;
};

/**
 * [Description for remplissage]
 * Fonction de remplissage des tableaux.
 *
 * @param mixed mavenKey
 *
 * @return [type]
 *
 * Created at: 13/12/2022, 10:08:02 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
export const remplissage=function(mavenKey) {
  const data = { mavenKey };

  /** On récupère les informations sur les versions, et le dernier audit. */
  const optionsInfo = {
    url: `${serveur()}/api/peinture/projet/version`, type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(optionsInfo).then(t=> {
    /*
     * On regarde le code http de retour.
     * Si la requête à un résultat, il est toujours égal à 200
     * sinon 406 pour signaler que le projet n'a pas encore été analysé.
     */
    if (t[0] === http_406){
        log(' - ERROR : Récupération de la version.');
        log(t.message);
        return;
      }

    const nom = mavenKey.split(':');
    $('#nom-projet').html(nom[1]);
    $('#clef-projet').html(mavenKey);
    $('#version-release').html(t.release);
    $('#version-snapshot').html(t.snapshot);
    $('#version-autre').html(t.autre);

    const version = document.getElementById('version-autre');
    version.dataset.label = JSON.stringify(t.label);
    version.dataset.dataset = JSON.stringify(t.dataset);
    $('#version').html(t.projet);
    $('#date-version').html(new Intl.DateTimeFormat('default', dateOptions).format(new Date(t.date)));

    /** Historique */
    const t1 = document.getElementById('version-release');
    const t2 = document.getElementById('version-snapshot');
    const t3 = document.getElementById('version-autre');
    const t4 = document.getElementById('date-version');
    t1.dataset.release=(t.release);
    t2.dataset.snapshot=(t.snapshot);
    t3.dataset.autre=(t.autre);
    t4.dataset.dateVersion=(t.date);
  });

  /** On récupère les exclusions noSonar */
  const optionsNoSonar = {
    url: `${serveur()}/api/peinture/projet/nosonar/details`, type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(optionsNoSonar).then(t=> {
    $('#suppress-warning').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.s1309));
    $('#no-sonar').html( new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nosonar));
    const t5 = document.getElementById('suppress-warning');
    const t6 = document.getElementById('no-sonar');
    t5.dataset.s1309=(t.s1309);
    t6.dataset.nosonar=(t.nosonar);
  });

    /** On récupère les todo tags */
    const optionsTodo = {
      url: `${serveur()}/api/peinture/projet/todo/details`, type: 'GET',
      dataType: 'json', data, contentType };

    $.ajax(optionsTodo).then(t=> {
      /** On injecte dans la fenêtre modale les résultats */
      $('#todo-liste').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.todo));
      $('#js-java').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.java));
      $('#js-javascript').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.javascript));
      $('#js-typescript').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.typescript));
      $('#js-html').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.html));
      $('#js-xml').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.xml));

      /* On ajoute la liste détaillée des fichiers */
      let l, cutRule, cutComponent;
      /** On efface le tableau */
      $('#tableau-liste-detaillee').html('');
      t.details.forEach(element => {
        cutRule=element.rule.split(':');
        cutComponent=element.component.split(':');
        l=`<tr><td><strong>${cutRule[0]}</strong></td><td>${cutComponent[2]}</td><td>${element.line}</td></tr>`;
        $('#tableau-liste-detaillee').append(l);
      });

      const t50 = document.getElementById('todo-liste');
      const t51 = document.getElementById('js-java');
      const t52 = document.getElementById('js-javascript');
      const t53 = document.getElementById('js-typescript');
      const t54 = document.getElementById('js-html');
      const t55 = document.getElementById('js-xml');
      const t56 = document.getElementById('tableau-liste-detaillee');
      t50.dataset.todo=(t.todo);
      t51.dataset.java=(t.java);
      t52.dataset.javascript=(t.javascript);
      t53.dataset.typescrypt=(t.typescript);
      t54.dataset.html=(t.html);
      t55.dataset.xml=(t.xml);
      t56.dataset.listeFichier=(t.details);
    });

  /**
   * On récupère les informations du projet :
   * lignes, couverture fonctionnelle, duplication, tests unitaires et le nombre de défaut.
   */
  const optionsProjet = {
    url: `${serveur()}/api/peinture/projet/information`, type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(optionsProjet).then(t=> {
    if (t[0] === http_406){
      log(' - ERROR : Récupération des informations.');
      log(t.message);
      return;
    }
    $('#nombre-ligne').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.lines));
    $('#nombre-ligne-de-code').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.ncloc));
    $('#couverture').html(new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(parseInt(t.coverage,10)/cent));
    $('ratio-dette-technique').html(new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(parseInt(t.duplication,10)/cent));
    $('#duplication').html(new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(parseInt(t.duplication,10)/cent));
    $('#tests-unitaires').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.tests));
    $('#nombre-defaut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.issues));

    //Historique
    const t7 = document.getElementById('nombre-ligne');
    const t8 = document.getElementById('nombre-ligne-de-code');
    const t9 = document.getElementById('couverture');
    const t9a = document.getElementById('ratio-dette-technique');
    const t10 = document.getElementById('duplication');
    const t11 = document.getElementById('tests-unitaires');
    const t12 = document.getElementById('nombre-defaut');

    t7.dataset.nombreLigne=(t.lines);
    t8.dataset.nombreLigneDeCode=(t.ncloc);
    t9.dataset.coverage=(t.coverage);
    t9a.dataset.coverage=(t.sqaledebtRatio);
    t10.dataset.duplication=(t.duplication);
    t11.dataset.testsUnitaires=(t.tests);
    t12.dataset.nombreDefaut=(t.issues);
  });

  /** On récupère les informations sur la dette technique et les anomalies. */
  const optionsAnomalie = {
    url: `${serveur()}/api/peinture/projet/anomalie`, type: 'GET',
          dataType: 'json', data, contentType };

  $.ajax(optionsAnomalie).then(t=> {
    if (t[0] === http_406) {
      log(' - ERROR : Récupération des anomalies.');
      log(t.message);
      return;
    }

    /* Dette technique */
    $('#dette').html(t.dette);
    $('#js-dette-reliability').html(t.detteReliability);
    $('#js-dette-vulnerability').html(t.detteVulnerability);
    $('#js-dette-code-smell').html(t.detteCodeSmell);

    /** Historique */
    const t13 = document.getElementById('js-dette');
    t13.dataset.detteMinute=t.detteMinute;

    /* Nombre d'anomalie */
    $('#nombre-bug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bug));
    $('#nombre-vulnerabilite').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerability));
    $('#nombre-mauvaise-pratique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmell));
    /** 10000 = le nombre max des retours possibles */
    if (t.codeSmell===dixMille) {
      $('#nombre-mauvaise-pratique').css('color', '#771404');
    }

    /** Historique */
    const t14 = document.getElementById('nombre-bug');
    const t15 = document.getElementById('nombre-vulnerabilite');
    const t16 = document.getElementById('nombre-mauvaise-pratique');
    t14.dataset.nombreBug=(t.bug);
    t15.dataset.nombreVulnerability=(t.vulnerability);
    t16.dataset.nombreCodeSmell=(t.codeSmell);

    /* Répartition modules*/
    let i1, i2, i3, p1, p2, p3, e1='', e2='', e3='';
    const html01='<span style="color:#fff;">0</span>';
    const html02='<span style="color:#fff;">00</span>';

    const totalModule=parseInt(t.frontend+t.backend+t.autre,10);

    if (totalModule !==0) {
      if (t.frontend!==0) {
        p1=t.frontend/totalModule;
        if (p1*cent>dix && p1*cent<cent) {
          e1=html01;
        }
        if (p1*cent<dix) {
          e1=html02;
        }
        i1=`<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.frontend)}</span> ${e1}
          <span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(t.frontend/totalModule)}</span>`;
      } else {
        i1=`<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0)}</span>`;
      }
      $('#nombre-frontend').html(i1);

      if (t.backend!==0) {
        p2=t.backend/totalModule;
        if (p2*cent>dix && p2*cent<cent) {
          e2=html01;
        }
        if (p2*cent<dix) {
          e2=html02;
        }
        i2=`<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.backend)}</span> ${e2}
          <span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(t.backend/totalModule)}</span>`;
      } else {
        i2=`<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0)}</span>`;
      }
      $('#nombre-backend').html(i2);

      if (t.autre!==0) {
        p3=t.autre/totalModule;
        if (p3*cent>dix && p3*cent<cent) {
          e3=html01;
        }
        if (p3*cent<dix) {
          e3=html02;
        }
        i3=`<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.autre)}</span> ${e3}
            <span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(t.autre/totalModule)}</span>`;
      } else {
        i3=`<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0)}</span>`;
      }
      $('#nombre-autre').html(i3);

      /** Historique */
      const t17 = document.getElementById('nombre-frontend');
      const t18 = document.getElementById('nombre-backend');
      const t19 = document.getElementById('nombre-autre');
      t17.dataset.nombreFrontend=t.frontend;
      t18.dataset.nombreBackend=t.backend;
      t19.dataset.nombreAutre=t.autre;
      } else {
          $('#nombre-frontend').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          $('#nombre-backend').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          $('#nombre-autre').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          const t20 = document.getElementById('nombre-frontend');
          const t21 = document.getElementById('nombre-backend');
          const t22 = document.getElementById('nombre-autre');
          t20.dataset.nombreFrontend=0;
          t21.dataset.nombreBackend=0;
          t22.dataset.nombreAutre=0;
          }

    /* Répartition des anomalies par sévérité */
    $('#nombre-anomalie-bloquant').html(t.blocker);
    $('#nombre-anomalie-critique').html(t.critical);
    $('#nombre-anomalie-info').html(t.info);
    $('#nombre-anomalie-majeur').html(t.major);
    $('#nombre-anomalie-mineur').html(t.minor);

    const t23 = document.getElementById('nombre-anomalie-bloquant');
    const t24 = document.getElementById('nombre-anomalie-critique');
    const t25 = document.getElementById('nombre-anomalie-info');
    const t26 = document.getElementById('nombre-anomalie-majeur');
    const t27 = document.getElementById('nombre-anomalie-mineur');
    t23.dataset.nombreAnomalieBloquant=t.blocker;
    t24.dataset.nombreAnomalieCritique=t.critical;
    t25.dataset.nombreAnomalieInfo=t.info;
    t26.dataset.nombreAnomalieMajeur=t.major;
    t27.dataset.nombreAnomalieMineur=t.minor;

    /** On récupère les notes sonarqube pour la version courante */
    let couleur1, couleur2, couleur3 = '';
    const tNotes = ['', 'A', 'B', 'C', 'D', 'E'];

    const noteVert1='note-vert1';
    const noteVert2='note-vert2';
    const noteJaune='note-jaune';
    const noteOrange='note-orange';
    const noteRouge='note-rouge';

    if (t.noteReliability === un ) {
      couleur1 = noteVert1;
    }
    if (t.noteSecurity === un) {
      couleur2 = noteVert1;
    }
    if (t.noteSqale === un) {
      couleur3 = noteVert1;
    }

    if (t.noteReliability === deux) {
      couleur1 = noteVert2;
    }
    if (t.noteSecurity === deux) {
      couleur2 = noteVert2;
    }
    if (t.noteSqale === deux) {
      couleur3 = noteVert2;
    }

    if (t.noteReliability === trois) {
      couleur1 = noteJaune;
    }
    if (t.noteSecurity === trois) {
      couleur2 = noteJaune;
    }
    if (t.noteSqale === trois) {
      couleur3 = noteJaune;
    }

    if (t.noteReliability === quatre) {
      couleur1 = noteOrange;
    }
    if (t.noteSecurity === quatre) {
      couleur2 = noteOrange;
    }
    if (t.noteSqale === quatre) {
      couleur3 = noteOrange;
    }

    if (t.noteReliability === cinq) {
      couleur1 = noteRouge;
    }
    if (t.noteSecurity === cinq) {
      couleur2 = noteRouge;
    }
    if (t.noteSqale === cinq) {
      couleur3 = noteRouge;
    }

    const noteReliability = tNotes[parseInt(t.noteReliability,10)];
    const noteSecurity = tNotes[parseInt(t.noteSecurity,10)];
    const noteSqale = tNotes[parseInt(t.noteSqale,10)];

    $('#note-reliability').html(`<span class="${couleur1}">${noteReliability}</span>`);
    $('#note-security').html(`<span class="${couleur2}">${noteSecurity}</span>`);
    $('#note-sqale').html(`<span class="${couleur3}">${noteSqale}</span>`);
  });

  /** On récupère la sévérité par type. */
  const optionsAnomalieDetails = {
    url: `${serveur()}/api/peinture/projet/anomalie/details`, type: 'GET',
          dataType: 'json', data, contentType };

  $.ajax(optionsAnomalieDetails).then(t=> {

    if (t[0] === http_406) {
      log(' - ERROR : Récupération du détails des anomalies.');
      log(t.message);
      return;
    }

    $('#js-bug-blocker').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bugBlocker));
    $('#js-bug-critical').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bugCritical));
    $('#js-bug-major').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bugMajor));
    $('#js-bug-minor').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bugMinor));
    $('#js-bug-info').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bugInfo));

    const t28 = document.getElementById('js-bug-blocker');
    const t29 = document.getElementById('js-bug-critical');
    const t30 = document.getElementById('js-bug-major');
    const t31 = document.getElementById('js-bug-minor');
    const t32 = document.getElementById('js-bug-info');
    t28.dataset.bugBlocker=t.bugBlocker;
    t29.dataset.bugCritical=t.bugCritical;
    t30.dataset.bugMajor=t.bugMajor;
    t31.dataset.bugMinor=t.bugMinor;
    t32.dataset.bugInfo=t.bugInfo;

    $('#js-vulnerability-blocker').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilityBlocker));
    $('#js-vulnerability-critical').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilityCritical));
    $('#js-vulnerability-major').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilityMajor));
    $('#js-vulnerability-minor').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilityMinor));
    $('#js-vulnerability-info').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilityInfo));

    const t33 = document.getElementById('js-vulnerability-blocker');
    const t34 = document.getElementById('js-vulnerability-critical');
    const t35 = document.getElementById('js-vulnerability-major');
    const t36 = document.getElementById('js-vulnerability-minor');
    const t37 = document.getElementById('js-vulnerability-info');
    t33.dataset.vulnerabilityBlocker=t.vulnerabilityBlocker;
    t34.dataset.vulnerabilityCritical=t.vulnerabilityCritical;
    t35.dataset.vulnerabilityMajor=t.vulnerabilityMajor;
    t36.dataset.vulnerabilityMinor=t.vulnerabilityMinor;
    t37.dataset.vulnerabilityInfo=t.vulnerabilityInfo;

    $('#js-code-smell-blocker').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmellBlocker));
    $('#js-code-smell-critical').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmellCritical));
    $('#js-code-smell-major').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmellMajor));
    $('#js-code-smell-minor').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmellMinor));
    $('#js-code-smell-info').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmellInfo));

    const t38 = document.getElementById('js-code-smell-blocker');
    const t39 = document.getElementById('js-code-smell-critical');
    const t40 = document.getElementById('js-code-smell-major');
    const t41 = document.getElementById('js-code-smell-minor');
    const t42 = document.getElementById('js-code-smell-info');
    t38.dataset.vulnerabilityBlocker=t.codeSmellBlocker;
    t39.dataset.vulnerabilityCritical=t.codeSmellCritical;
    t40.dataset.vulnerabilityMajor=t.codeSmellMajor;
    t41.dataset.vulnerabilityMinor=t.codeSmellMinor;
    t42.dataset.vulnerabilityInfo=t.codeSmellInfo;
  });

  /** On récupère les hotspots. */
  const optionsHotspots = {
    url: `${serveur()}/api/peinture/projet/hotspots`, type: 'GET',
          dataType: 'json', data, contentType };

  $.ajax(optionsHotspots).then(t=> {
    let couleur='';

    if (t[0] === http_406) {
      log(' - ERROR : Récupération des anomalies.');
      log(t.message);
      return;
    }

    if (t.note === 'E') {
        couleur = 'note-rouge';
    }
    if (t.note === 'D') {
        couleur = 'note-orange';
    }
    if (t.note === 'C') {
        couleur = 'note-jaune';
    }
    if (t.note === 'B') {
        couleur = 'note-vert2';
      }
    if (t.note === 'A') {
        couleur = 'note-vert1';
    }

    $('#note-hotspot').html(`<span class="${couleur}">${t.note}</span>`);
  });
};
