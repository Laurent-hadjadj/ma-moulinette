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

/** Import des dépendances */
import '../css/actuator-ajouter.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

const newItem = (e) => {
  const collectionHolder = $(e.currentTarget.dataset.collection);
  const item = $("<div></div>").addClass("grid-x cell");
  item.html(collectionHolder.data("prototype").replace(/__name__/g, collectionHolder.data("index")));
  item.find(".js-bouton-supprimer").on("click", () => item.remove());
  collectionHolder.append(item);
  collectionHolder.data("index", collectionHolder.data("index") + 1);
};

$(".js-boutton-supprimer").on("click", (e) => $(e.currentTarget).closest(".small-4").remove());
$(".js-boutton-ajouter").on("click", newItem);
