/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
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
import '../../../styles/mon-application/actuator-ajouter.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import '../../auth/details.js';

/* MODIF 2026-07-23 : logique pour ajouté des clé pour Actuator + passage de la limite de 15 clés (cf. Actuator::$actuatorInfo, Assert\Count max: 15). */
const MAX_CLES = 15;

const compterCles = (collectionHolder) => collectionHolder.find('input[id$="_actuatorInfoCle"]').length;

const rafraichirBoutonAjouter = (collectionHolder) => {
  $(".js-bouton-ajouter").prop("disabled", compterCles(collectionHolder) >= MAX_CLES);
};

const newItem = (e) => {
  const collectionHolder = $(e.currentTarget.dataset.collection);
  if (compterCles(collectionHolder) >= MAX_CLES) {
    return;
  }
  const item = $("<div></div>").addClass("grid-x cell");
  item.html(collectionHolder.data("prototype").replace(/__name__/g, collectionHolder.data("index")));
  item.find(".js-bouton-supprimer").on("click", () => {
    item.remove();
    rafraichirBoutonAjouter(collectionHolder);
  });
  collectionHolder.append(item);
  collectionHolder.data("index", collectionHolder.data("index") + 1);
  rafraichirBoutonAjouter(collectionHolder);
};

$(".js-bouton-supprimer").on("click", (e) => $(e.currentTarget).closest(".small-4").remove());
$(".js-bouton-ajouter").on("click", newItem);

rafraichirBoutonAjouter($("#bars"));
