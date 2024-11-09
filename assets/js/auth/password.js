/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) Lilmod & Lelamed - 2015-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/**
 * Refactor de la librairie password.js
 * @link https://github.com/elboletaire/password-strength-meter
 * @license GPL-3.0
 *
 * Réécriture complètes des règles de complexité (conformité, biais, exigences);
 * Mesures de l'entropy ;
 * Amélioration de la partie init et affichage du score :
 *   - colorisation du pourcentage en fonction de l'échelle de valeurs ;
 *   - prise en compte des seuils d'entropy ;
 *   - ajout de contrôles pour détecter les erreurs ;
 *   - optimisation du code.
*/

import $ from 'jquery';
window.jQuery = $;

(function($) {
  'use strict';

  const Password = function($object, options) {
    const defaults = {
      debug: false,
      enterPass: 'Saisi un mot de passe.',
      shortPass: 'Mot de passe trop court.',
      steps: {
        23: 'Très faible.',
        49: 'Très faible.',
        62: 'Faible.',
        77: 'Moyen.',
        99: 'Fort.',
        100: 'Très fort.'
      },
      showPercent: false,
      showText: true,
      animate: true,
      animateSpeed: 'fast',
      minimumLength: 6,
      closestSelector: 'div',
      useColorBarImage: false,
      customColorBarRGB: {
        red: [0, 209],
        green: [100, 220],
        blue: 0,
      },
      classOption: 'open-sans',
    };

    options = $.extend({}, defaults, options);
    if ($object.length === 0) {
      localStorage.setItem('error', 'L’élément $object est introuvable.');
      return;
    }

    const patterns = {
      upper: /[A-Z]/g,
      lower: /[a-z]/g,
      digit: /[0-9]/g,
      symbol: /[#\$%&@\^`~.,:;'\"\/\\|_\-+\*<>=\(\)\[\]{}!?€¤£§\s]/g,
      symbol1: /[#$%&@^`~]/g,
      symbol2: /[.,:;]/g,
      symbol3: /['"]/g,
      symbol4: /[\/\\|_]/g,
      symbol5: /[-+*<>=()\[\]{}]/g,
      symbol6: /[!?€¤£§\s]/g,
      identicalUpper: /([A-Z])\1+/g,
      identicalLower: /([a-z])\1+/g,
      identicalDigit: /([0-9])\1+/g,
      consecutiveUpper: /([A-Z])+/g,
      consecutiveLower: /([a-z])+/g,
      consecutiveDigit: /([0-9])+/g,
      date: /(19\d\d|20[0-3]\d)/g,
      sequentialLetters: /(?=(abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz))/g,
      sequentialDigits: /(?=(012|123|234|345|456|567|678|789))/g,
    };

    /** Définir le nombre de bits pour chaque type de caractère */
    const weights = {
      upper: 26,
      lower: 26,
      digit: 10,
      symbol1: 8,
      symbol2: 4,
      symbol3: 2,
      symbol4: 5,
      symbol5: 12,
      symbol6: 8,
    };

    /**
     * [Description for calculateScore]
     *
     * @param mixed password
     *
     * @return [type]
     *
     * Created at: 15/10/2024 10:58:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Lilmod & Lelamed - Creative Common CC-BY-NC-SA 4.0.
     */
    Password.prototype.calculateScore = (password) => {
      let complexity = 0;
      let entropy = 0;
      let bit = 0;
      let biais = 0;
      let sequence = 0;
      let exigence = 0;

      /** Si le mot de passe est < à la longueur minimale alors on sort */
      if (password.length < options.minimumLength) return 0;

      /* nombre de caractères : un mot de passe de 20 caractère à 100 %*/
      const NombreChars = password.length;
      complexity += NombreChars * 5;

      /* nombre de caractères en fonction des patterns */
      const charCounts = {
          upper: (password.match(patterns.upper) || []).length,
          lower: (password.match(patterns.lower) || []).length,
          digit: (password.match(patterns.digit) || []).length,
          symbol1: (password.match(patterns.symbol1) || []).length,
          symbol2: (password.match(patterns.symbol2) || []).length,
          symbol3: (password.match(patterns.symbol3) || []).length,
          symbol4: (password.match(patterns.symbol4) || []).length,
          symbol5: (password.match(patterns.symbol5) || []).length,
          symbol6: (password.match(patterns.symbol6) || []).length,
      };

      const keyWeights = {
        lower: 1,
        upper: 2,
        digit: 3,
        symbol: 4
      };

      // Boucle à travers les types de caractères pour calculer la complexité, le nombre de bit
      for (const [key, count] of Object.entries(charCounts)) {
          if (count > 0) {
            complexity += count * (keyWeights[key] || keyWeights['symbol']);
            bit += weights[key];
          }
      }

      let nombreAuMilieu = 0;
      /**
       *  Vérifie si la longueur du mot de passe est suffisante
       *  et s'il contient des chiffres encadrés par des lettres.
       */
      if (NombreChars >= options.minimumLength) {
        /** Exclu le premier et le dernier caractère */
        const milieu = password.slice(1, -1);
        /** Compte les chiffres au milieu */
        nombreAuMilieu = (milieu.match(/\d/g) || []).length;
        complexity += nombreAuMilieu * 3;
        biais += complexity > 0 ? 0 : -2;
      }

      /**
       *  Vérifie si la longueur du mot de passe est suffisante
       *  et s'il contient des symboles encadrés par des lettres.
       */
      let symboleAuMilieu = 0;

      if (NombreChars >= options.minimumLength) {
        /** Exclu le premier et le dernier caractère */
        const milieu = password.slice(1, -1);
        /** Compte les symboles au milieu */
        symboleAuMilieu = (milieu.match(/[^a-zA-Z0-9]/g) || []).length;
        complexity += symboleAuMilieu * 3;
        biais += complexity > 0 ? 0 : -2;
      }

      /** Lettres uniquement */
      if (/^[a-zA-Z]+$/.test(password)) biais += NombreChars;
      /** Chiffres uniquement */
      if (/^\d+$/.test(password)) biais += NombreChars;

      /** Règle de caractère consécutifs et de séquences */
      const sequences = {
        ConsecutiveMajuscule: (password.match(patterns.consecutiveUpper) || []).length,
        ConsecutiveMinuscule: (password.match(patterns.consecutiveLower) || []).length,
        ConsecutiveChiffre: (password.match(patterns.consecutiveDigit) || []).length,
        IdenticalMajuscule: (password.match(patterns.consecutiveUpper) || []).length,
        IdenticalMinuscule: (password.match(patterns.consecutiveLower) || []).length,
        IdenticalChiffre: (password.match(patterns.consecutiveDigit) || []).length,
        ConsecutiveDate: (password.match(patterns.date) || []).length,
        SequenceLettre: (password.match(patterns.sequentialLetters) || []).length,
        SequenceChiffre: (password.match(patterns.sequentialDigits) || []).length,
      };
      /** Soustraire (à la fin) les points pour les séquences et répétitions */
      for (const [key, value] of Object.entries(sequences)) {
        sequence += value;
    }

      /** Calcul Exigences : 12 caractères et au moins 1 caractère de chaque type */
      if (NombreChars >= 12) {
          let exigences = [
          Math.floor((password.match(patterns.upper) || []).length > 0 ? 1 : 0),
          Math.floor((password.match(patterns.lower) || []).length > 0 ? 1 : 0),
          Math.floor((password.match(patterns.digit) || []).length > 0 ? 1 : 0),
          Math.floor((password.match(patterns.symbol) || []).length > 0 ? 1 : 0)
          ].reduce((a, b) => a + b);
          exigence = (exigences === 4) ? 5 : 0;
      }

      /** calcul complexité finale */
      complexity = complexity - biais - sequence*2 + exigence;

      /* Calcul de l'entropie de Shannon */
      entropy = Math.round(NombreChars * Math.log2(bit));

      if (options.debug) {
        const items = {'caractères': NombreChars, complexity, entropy, bit, biais, sequence, exigence};
        sessionStorage.setItem('debug', JSON.stringify(items));
      }

      /** On enregistre les informations de complexité du mot de passe. */
      localStorage.setItem('complexity', complexity);
      localStorage.setItem('entropy', entropy);
      localStorage.setItem('bit', bit);

      /** Retourne le score (moyenne de la complexité et de l'entropie sur 100). */
      return calculateScore(complexity, entropy);
    };

    /** fonction de normalisation du score sur 100 % */
    const calculateScore = function(complexity, entropy) {
      const rawScore = (complexity + entropy) / 2;
      const normalizedScore = Math.min(Math.floor((rawScore / 128) * 100), 100); // Limite à 100%
      localStorage.setItem('score', normalizedScore);
      return normalizedScore;
    };

    /** Fonction de gestion de l'affichage du texte en fonction du score */
    const scoreText=function(score) {
      /** si score <O alors score=0 sinon score */
      score = score < 0 ? 0 : score;
      const sortedStepKeys = Object.keys(options.steps)
        .map(Number) // Convertir les clés en nombres
        .sort((a, b) => a - b); // Trier dans l'ordre croissant
      let text = options.shortPass; // Valeur par défaut du texte

      for (const stepVal of sortedStepKeys) {
        if (score >= stepVal) {
          text = options.steps[stepVal]; // Mettre à jour le texte correspondant
        }
      }
      return text;
    }

    /** Fonction de gestion de la couleur de la barre de progression en fonction du pourcentage */
    const addColorBarStyle=function($colorBar, percent) {
      if (options.useColorBarImage) {
        $colorBar.css({
          backgroundPosition: `0px -${percent}px`,
          width: percent + '%'
        });
      } else {
        const colors = calculateColorFromPercentage(percent);
        $colorBar.css({
          'background-image': 'none',
          'background-color': 'rgb(' + colors.red.toString() + ', ' + colors.green.toString() + ', ' + colors.blue.toString() + ')',
          width: percent + '%'
        });
      }

      return $colorBar;
    }

    /** Fonction de gestion de la couleur du pourcentage en fonction score */
    const calculateColorFromPercentage=function(percent) {
      let minRed = 0, maxRed = 240, minGreen = 0, maxGreen = 240, blue = 10;

      if (Object.hasOwn(options.customColorBarRGB, 'red')) {
        minRed = options.customColorBarRGB.red[0];
        maxRed = options.customColorBarRGB.red[1];
      }

      if (Object.hasOwn(options.customColorBarRGB, 'green')) {
        minGreen = options.customColorBarRGB.green[0];
        maxGreen = options.customColorBarRGB.green[1];
      }

      if (Object.hasOwn(options.customColorBarRGB, 'blue')) {
        blue = options.customColorBarRGB.blue;
      }

      const green = (percent * maxGreen / 50);
      const red = (2 * maxRed) - (percent * maxRed / 50);

      return {
        red: Math.min(Math.max(red, minRed), maxRed),
        green: Math.min(Math.max(green, minGreen), maxGreen),
        blue: blue
      }
    }

    /** Fonction principale d'initialisation de la barre de score
     *  et de mise à jour des informations
     */
    const init=function(){
      let shown = true;
      let $text = options.showText;
      let $percentage = options.showPercent;
      const ClassOption = options.classOption;

      const $closest = $object.closest(options.closestSelector);
      if ($closest.length === 0) {
        localStorage.setItem('error',`Élément parent introuvable avec le sélecteur : ${options.closestSelector}`);
        return;
      }

      /** On ajoute les classes d'affichage de la barre de progression. */
      let $grayBar = $('<div>').addClass('.pass-gray-bar'); // barre grise
      let $colorBar = $('<div>').addClass('pass-color-bar'); // échelle de couleurs.

      /** On ajoute une class CSS pour surcharger le CSS (ex. la police). */
      let $insert = $('<div>').addClass(`pass-wrapper ${ClassOption}`).append(
        $grayBar.append($colorBar));

        /** Ajoute une pseudo class pour l'affichage de la barre de progression*/
      $object.closest(options.closestSelector).addClass('pass-strength-visible');
      if (options.animate) {
        $insert.css('display', 'none');
        shown = false;
        $object.closest(options.closestSelector).removeClass('pass-strength-visible');
      }

      /** initialisation de l'affichage du pourcentage */
      if (options.showPercent) {
        $percentage = $('<span>').addClass('pass-percent').text('0%');
        $insert.append($percentage);
      }

      /** créé l'element pour le texte d'information */
      if (options.showText) {
        $text = $('<span>').addClass('pass-text').html(options.enterPass);
        $insert.append($text);
      }

      /* ajoute le texte d'information */
      $closest.append($insert);

      /******* Fin de l'initialisation ********/

      /******* Gestion de l’événement KeyUp */
      $object.keyup(function() {
        let field = options.field || '';
        if (field && $(field).length === 0) {
          localStorage.setItem('error','Le champ de saisie spécifié est introuvable.');
          return;
        }
        field = $(field).val();

        const score = Password.prototype.calculateScore($object.val(), field);

        $object.trigger('password.score', [score]);
        let percent = score < 0 ? 0 : score;

        if ($colorbar.length === 0) {
          localStorage.setItem('error', '$colorbar introuvable');
          return;
        }
        $colorBar = addColorBarStyle($colorBar, percent);

        /** On change la couleur du pourcentage en fonction de la qualité du mot de passe */
        if (options.showPercent) {
          const colors = calculateColorFromPercentage(percent);
          const ColorRed=(colors.red-10).toString();
          const ColorGreen=(colors.green-60).toString();
          const ColorBlue=10;

          $percentage.css({
            'color': 'rgb(' + ColorRed + ', ' + ColorGreen + ', ' + ColorBlue + ')', width: percent + '%'
          });
          $percentage.html(percent + '% ');
        }

        if (options.showText) {
          let text = scoreText(score);
          if (!$object.val().length && score <= 0) {
            text = options.enterPass;
          }

          if ($text.html() !== $('<div>').html(text).html()) {
            $text.html(text);
            $object.trigger('password.text', [text, score]);
          }
        }

      });

    if (options.animate) {
      $object.focus(function() {
        if (!shown) {
          $insert.slideDown(options.animateSpeed, function () {
            shown = true;
            $object.closest(options.closestSelector).addClass('pass-strength-visible');
          });
        }
      });

      $object.blur(function() {
        if (!$object.val().length && shown) {
          $insert.slideUp(options.animateSpeed, function () {
            shown = false;
            $object.closest(options.closestSelector).removeClass('pass-strength-visible')
          });
        }
      });
    }

    return this;
    }

    return init.call(this);
  };

  $.Password = Password;
  $.fn.password = function(options) {
    return this.each(function() {
      new Password($(this), options);
    });
  };

})(jQuery);
