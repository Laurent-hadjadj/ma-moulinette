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

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('turbo:load', init);

/**
 * [Description for init]
 *
 * @return void
 *
 * Created at: 07/04/2026 14:32:23 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
