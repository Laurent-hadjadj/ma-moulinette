/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('turbo:load', init);

/**
 * [Description for init]
 *
 * @return void
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
function init() {
  // Récupère les éléments du DOM
  const tagsField = document.querySelector('.js-tags');
  const groupeField = document.querySelector('.js-groupe');

  // Ajoute un écouteur d'événement sur le champ "Tags disponibles"
  if (tagsField && groupeField) {
    tagsField.addEventListener('change', function () {
      const value = this.value;

      // Si une valeur est sélectionnée remplir ce champ avec la valeur sélectionnée
        groupeField.value = value;
    });
  }
}
