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

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/profil.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';
import '../../common/foundation.js';
import '../../auth/details.js';

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';

/** La gestion des messagesJS */
import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe les constantes */
import { http_200, http_400, http_401, http_403, http_404, http_500, contentType, paletteCouleur, matrice, dateOptions, dateOptionsShort } from '../../common/constante.js';

import {encode} from '../../common/encode.js';
import {Chart, registerables} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import zoomPlugin from 'chartjs-plugin-zoom';

/** On enregistre les classes et les plugins dans chart.js */
Chart.register(...registerables);
Chart.register(ChartDataLabels);
Chart.register(zoomPlugin);

/** Librairie de tirage aléatoire, c'est une chance !, remplace random */
import Chance from 'chance';

const path = `<path d="M9.142.079c-.932.228-1.804 1.055-2.07 1.963l-.08.288h-.987c-.829 0-1.007.016-1.116.087-.173.125-.276.402-.276.755v.3H3.6c-1.512 0-2.026.168-2.725.87-.401.402-.666.858-.786 1.364-.119.527-.119 15.88 0 16.407.19.805.802 1.571 1.544 1.92a3.7 3.7 0 00.694.255c.195.038 1.663.06 4.54.06 3.873 0 4.268-.006 4.41-.093.297-.174.362-.609.14-.87l-.13-.152-4.383-.027-4.377-.027-.363-.18c-.411-.2-.65-.445-.85-.88l-.141-.3V6l.179-.364c.2-.413.444-.652.877-.853.282-.136.352-.142 1.344-.158l1.04-.022v.332c0 .701.184 1.169.607 1.566.482.445.276.424 4.648.424h3.873l.309-.153c.336-.168.628-.456.807-.81.092-.18.13-.364.152-.777l.032-.544h1.057c1.03 0 1.062.006 1.36.142.373.179.688.483.866.842.13.267.13.283.157 2.333.028 2.229.033 2.24.325 2.392.2.103.342.103.548-.006.303-.152.32-.272.32-2.234 0-2.088-.033-2.387-.32-2.98-.347-.728-.856-1.195-1.61-1.484-.351-.13-.444-.141-1.549-.163l-1.18-.021v-.397c0-.348-.017-.424-.13-.555l-.13-.152-1.046-.027-1.04-.027-.065-.223C12.176.617 10.62-.28 9.142.079zm1.408 1.24c.553.255.916.772.997 1.413.087.696.168.74 1.409.74h.92v.924c0 1.049-.043 1.207-.351 1.337-.271.114-7.092.114-7.363 0-.336-.141-.357-.217-.357-1.294v-.968h.926c1.208 0 1.29-.043 1.376-.734.07-.554.39-1.06.829-1.315.53-.315 1.078-.348 1.614-.103z"/><path d="M3.762 9.354c-.308.152-.363.663-.092.919l.141.13 5.954.016c6.625.017 6.203.038 6.365-.353a.564.564 0 00-.178-.669c-.136-.109-.2-.109-6.106-.103-4.014 0-6.002.022-6.084.06zm0 3.48c-.308.151-.363.662-.092.918l.141.13 4.22.017c4.703.016 4.475.032 4.632-.354a.564.564 0 00-.179-.668c-.135-.11-.205-.11-4.371-.104-2.812 0-4.27.022-4.35.06zm15.261.01c-3.288.587-5.77 3.153-6.235 6.442-.081.577-.038 1.963.081 2.506a7.525 7.525 0 001.988 3.725c1.09 1.125 2.335 1.815 3.884 2.158.786.174 2.205.174 2.985 0 1.544-.348 2.72-.99 3.803-2.072 1.079-1.087 1.718-2.267 2.065-3.816.173-.783.173-2.207 0-2.995a7.397 7.397 0 00-.688-1.936c-.401-.777-.819-1.332-1.463-1.962a7.472 7.472 0 00-3.684-1.99c-.526-.12-2.194-.158-2.736-.06zm2.6 1.223a6.422 6.422 0 014.768 4.66c.552 2.152-.092 4.468-1.674 6.061-2.183 2.19-5.515 2.49-8.105.734-.763-.517-1.636-1.528-2.08-2.408-.52-1.044-.753-2.403-.601-3.583.352-2.827 2.508-5.083 5.298-5.54.623-.103 1.777-.065 2.395.076z"/><path d="M19.83 16.405a.571.571 0 00.141.902c.342.19.759-.021.824-.424.08-.527-.585-.859-.965-.478zm.19 2.202c-.358.157-.347.07-.347 2.816 0 1.712.017 2.528.06 2.62.152.326.66.386.92.109l.13-.141.017-2.496c.016-2.778.016-2.756-.341-2.908-.222-.093-.228-.093-.439 0zM3.762 16.312c-.308.153-.363.664-.092.92l.141.13h6.263l.14-.13c.277-.262.217-.773-.108-.925-.179-.082-6.17-.076-6.344.005z"/>`;
/**
 * [Description for refreshQuality]
 *
 * @return void
 *
 * Created at: 07/05/2023, 21:02:59 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
  const refreshQuality = async function() {
  const optionsRefresh = {
        url: `${serveur()}/api/quality/profiles`,
        type: 'POST',
        dataType: 'json',
        contentType
      };

  /** On appel l'API */
  const t = await $.ajax(optionsRefresh);

  // 📌 Vérification des erreurs
  const errorCodes = [http_400, http_401, http_403, http_404, http_500];
  if (errorCodes.includes(t.code)){
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      return;
  }

  let id = 0, str_container = '', str_total='', total = 0;

  /** On efface le container */
  $('#js-container-langage').html('');
  const profils = t.liste_profil;

  /** on recréé le container */
  profils.forEach(profil => {
  id = id + 1;

  str_container += `
    <div class="small-12 medium-12 large-6 cell">
      <div class="callout secondary box-langage">
        <h3 class="h5 claire-hand">${profil.langage}</h3>

        <table class="hover">
          <caption><span class="show-for-sr">Détails du langage ${profil.langage}</span></caption>
          <thead>
            <tr>
              <th scope="col" class="open-sans text-center"></th>
              <th scope="col" id="profil-version-${id}" class="open-sans text-center">Version</th>
              <th scope="col" id="profil-rule-${id}" class="open-sans text-center">Règle</th>
              <th scope="col" id="profil-${id}" class="open-sans text-center">Date</th>
            </tr>
          </thead>
          <tbody>
            <tr class="open-sans">
              <td id="profil-${id}"
                  class="js-profil-information profil-font-size"
                  data-profil="${profil.profil}"
                  role="button"
                  tabindex="${id}"
                  data-language="${profil.langage}"
                  aria-labelledby="title-${id} desc-${id}">
                <svg id="i-${id}" version="1.1" xmlns="http://www.w3.org/2000/svg"
                    width="100%" height="100%" viewBox="0 0 28 28"
                    class="profil-information-fixe-svg"
                    role="img"
                    aria-labelledby="title-${id} desc-${id}">
                  <title id="title-${id}">Afficher les informations du profil ${profil.langage}</title>
                  <desc id="desc-${id}">Cliquer pour voir les détails du langage ${profil.langage} associé au profil ${profil.profil}</desc>
                  ${path}
                </svg>
              </td>
              <td class="profil-font-size">${profil.profil}</td>
              <td class="text-center mini-stat color-noir">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(profil.rule)}</td>
              <td class="text-left profil-font-size">
                <span class="show-for-small-only">${new Intl.DateTimeFormat('default', dateOptionsShort).format(new Date(profil.date))}</span>
                <span class="show-for-medium">${new Intl.DateTimeFormat('default', dateOptions).format(new Date(profil.date))}</span>
              </td>
            </tr>
          </tbody>
        </table>

        <footer class="grid-x grid-margin-x align-right margin-top-1">
          <div class="cell shrink">
            <button id="bouton-language-${profil.langage}"
                    class="button open-sans focus-light js-bouton-autre-profil"
                    type="button"
                    data-language="${profil.langage}"
                    aria-label="Affiche la liste des autres langages">
              <span class="show-for-small-only open-sans color-blanc">
                <span aria-hidden="true">📂</span>Détails
              </span>
              <span class="show-for-medium open-sans color-blanc">
                <span aria-hidden="true">📂</span>Afficher les autres profils
              </span>
            </button>
          </div>
        </footer>
      </div>
    </div>`;
    total = total + profil.rule;
  });

  str_total = new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(total);

  /** Affiche le container */
  $('.js-total').html(str_total);
  $('#js-container-langage').html(str_container);
  showMessage('success', 'La liste des profils qualités a été mise à jour.');
  setTimeout(()=>hideMessage(),3000);
};

/**
  * [Description for autreProfil]
  *
  * @param mixed langage
  *
  * @return [type]
  *
  * Created at: 15/07/2025 09:04:03 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
  */
const autreProfil = async function(langage) {
  const dataRefresh = { langage };
  const optionsRefresh = {
    url: `${serveur()}/api/quality/off`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(dataRefresh),
    contentType
  };

  const t = await $.ajax(optionsRefresh);

  // 📌 Vérification des erreurs
  const errorCodes = [http_400, http_401, http_403, http_404, http_500];
  if (errorCodes.includes(t.code)){
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      return;
    }

  const profils = t.listeProfil;
  const nombreProfils = t.countProfil?.request[0]?.total || 0;

  // Mise à jour des titres de la modale
  const titre = document.querySelector('.js-titre-modal');
  const sousTitre = document.getElementById('js-nombre-profil');
  const tableBody = document.getElementById('js-contenu-autre-profil');

  titre.innerHTML = `<span aria-hidden="true">🧑‍💻</span>Profils ${langage}`;
  sousTitre.innerHTML = nombreProfils === 1
    ? `<span aria-hidden="true">📋</span>Il y a 1 profil disponible dans SonarQube`
    : `<span aria-hidden="true">📋</span>Il y a ${nombreProfils} profils disponibles dans SonarQube`;

  // Construction du tableau
  let str = '';
  profils.forEach(profil => {
    str += `
      <tr class="open-sans">
        <td></td>
        <td class="text-left">${profil.profil}</td>
        <td class="text-center">${new Intl.NumberFormat('fr-FR').format(profil.rule)}</td>
        <td class="text-center">
          <span class="show-for-small-only">${new Intl.DateTimeFormat('fr-FR', dateOptionsShort).format(new Date(profil.date))}</span>
          <span class="show-for-medium">${new Intl.DateTimeFormat('fr-FR', dateOptions).format(new Date(profil.date))}</span>
        </td>
      </tr>`;
  });

  tableBody.innerHTML = str;

  // Ouverture de la modale Foundation
  $('#modal-autre-profil').foundation('open');
};

$('#js-container-langage').on('click', '.js-bouton-autre-profil', (e) => {
  /* On récupère l'id */
  const elm = e.currentTarget;
  const language = elm.dataset.language;

  // Appel de l'API et mise à jour de la modale
  autreProfil(language);

  // Accessibilité : focus automatique sur le titre de la modale
  setTimeout(() => {
    document.getElementById('titre-modal')?.focus();
  }, 100); // Petit délai pour laisser le DOM s'afficher
});

/**
 * [Description for shuffle]
 * Mélangeur de couleur
 *
 * @param mixed a
 *
 * @return array
 *
 * Created at: 19/12/2022, 21:51:11 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const shuffle = function(a) {
  let j, x, i;
  /** On crée un nouvel objet chance */
  const chance = new Chance();

  /** On mélange la matrice */
  for (i = a.length - 1; i > 0; i--) {
    j = chance.natural({ min: 0, max: i });
    x = a[i];
    a[i] = a[j];
    a[j] = x;
  }
  return a;
};

/**
 * [Description for palette]
 * Renvoie une nouvelle palette de couleur
 *
 * @return array
 *
 * Created at: 19/12/2022, 21:52:05 (Europe/Paris)
 * @author Laurent HADJADJ <laurent_h@me.com>
 */
const palette=function() {
  const nouvellePalette = [];
  shuffle(matrice);
  matrice.forEach(el => {
    nouvellePalette.push(paletteCouleur[el]);
  });
  return nouvellePalette;
};

/**
 * [Description for dessineMoiUnMouton]
 * Affiche le graphique des sources
 *
 * @param mixed label
 * @param mixed dataset
 *
 * @return object
 *
 * Created at: 19/12/2022, 21:53:26 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const dessineMoiUnMouton=function(label, dataset) {
  const nouvellePalette = palette();
  const data =
  {
    labels: label,
    datasets: [{
      data: dataset, backgroundColor: nouvellePalette, borderWidth: 1,
      datalabels: { align: 'center', anchor: 'center'}}]};

  const options = {
    animations: { tension: { duration: 2000, easing: 'linear', loop: false } },
    maintainAspectRatio: false,
    responsive: true,
    plugins: {
      title: { display: false },
      tooltip: { enabled: true },
      legend: {},
      datalabels: {
        color: '#fff',
        font: function (context) {
          const w = context.chart.width;
          return {
            size: w < 512 ? 12 : 14, weight: 'bold'};
        }
      }
    }
  };

  const chartStatus = Chart.getChart('graphique-langage');
  if (chartStatus !== undefined) {
    chartStatus.destroy();
  }

  const ctx = document.getElementById('graphique-langage').getContext('2d');
  const charts = new Chart(ctx, { type: 'doughnut', data, options });
  if (charts === null) {
    sessionStorage.setItem('info','youpi ! charts ne peut pas être null !!!');
  }
};

/** Création du graphique par language */
$('.js-profil-graphique').on('click', async () => {
  const options = {
        url: `${serveur()}/api/quality/langage`, type: 'POST', dataType: 'json', contentType };
  const t = await $.ajax(options);
    /**
   * const label = t.label;
   * const dataset = t.dataset;
   */
  const { label, dataset } = t;
  /** On appel la fonction de dessin */
  dessineMoiUnMouton(label, dataset);
  /** on affiche le container */
  $('.graphique-langage-container').show();
});

/**
 * Événement
 * Appel la fonction d'affichage de la liste des modifications du profil.
 */
$('.js-profil-information').on('click', (e) => {
  /* On récupère l'id */
  const target = e.currentTarget.id;
  const elm = document.getElementById(target);

  /* On récupère le nom du langage. */
  const language = elm.dataset.language;
  /* On récupère le nom du profil. */
  const profil=elm.dataset.profil;

  /** on créé un hash avec la méthode reduce() comme clé de salt */
  const salt = language.split('').reduce((hash, char) => {
    return char.charCodeAt(0) + (hash << 6) + (hash << 16) - hash;
} , 0);

  /** on créé un token pour encoder les paramètres */
  const param=`${salt}|${language}|${profil}`;
  const a=encode(btoa(param));
  location.href=`${serveur()}/profil/details?token=${a}`;
});

/**
 * Événement
 * Appel la fonction de mise à jour de la liste des référentiels
 */
$('#bouton-refresh-profil').on('click', ()=>{
  refreshQuality();
});
